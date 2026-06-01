<?php
/**
 * @package     RSTicketsPro
 * @subpackage  ticket_plugin
 *
 * @copyright   (c) 2010 - 2016 RSJoomla!
 * @license     GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

/**
 * TicketPlugin
 *
 * @package     RSTicketsPro
 * @subpackage  ticket_plugin
 * @since       __DEPLOY_VERSION__
 */
class PlgRsticketsproTicket extends CMSPlugin
{
	/**
	 * Function com_rsticketsproonBeforeStoreTicket
	 *
	 * @param   Array  $data  Ticket Data before saving. It include Agency ID
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function com_rsticketsproonBeforeStoreTicket($data)
	{
	}

	/**
	 * Function com_rsticketsproonAfterStoreTicket
	 *
	 * @param   Array  $data    Ticket Data before saving it include Agency ID
	 * @param   Array  $ticket  Ticket Data after saving ticket in database. Doesn't contain Agency Id
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRsticketsproAfterStoreTicket($data, $ticket)
	{
		// If ticket added from email
		if ($data['agent'])
		{
			// On after ticket create from email save cc users in user_cc_emails column
			$data['user_cc']  = implode(',', (array) $data['clusterusers']);
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters         = $clusterUserModel->getUsersClusters($data['user_id']);

			if (count($clusters) == 1)
			{
				foreach ($clusters as $cluster)
				{
					$data['cluster'] = $cluster->cluster_id;
				}
			}
		}

		if ($data['user_id'] || $data['customer_id'])
		{
			$table = DPE::table('RsticketXref');
			$table->load(array('ticket_id' => $data['id']));
			$orgccEmails = new Registry($table->emails);

			// Check removed email(s) and remove from user cc field and dpe cc field
			if (!empty($data['clusterusers']) || (empty($data['clusterusers']) && $orgccEmails && $data['cluster']))
			{
				if (!empty($data['clusterusers']))
				{
					$emailDiff = array_diff( (array) $orgccEmails['email'], $data['clusterusers']);  // DPE HACK

					// Check removed user is member of cluster
					$emails = array();

					foreach ($emailDiff as $email)
					{
						// Get user id by providing email
						$userId = (int) $this->getUserByEmail($email);

						if ($userId)
						{
							JLoader::import("/components/com_cluster/libraries/cluster", JPATH_ADMINISTRATOR);
							$cluster = ClusterCluster::getInstance($data['cluster']);

							$isMember = $cluster->isMember($userId);

							if ($isMember)
							{
								$emails[] = $email;
							}
						}
					}

					$emailDiff = $emails;
				}
				else
				{
					$emailDiff = $orgccEmails['email'];
				}

				if (!empty($emailDiff))
				{
					if (!empty($data['user_cc']) && is_string($data['user_cc']))
					{
						$userccArray     = explode(',', $data['user_cc']);
						$userccArray     = array_diff($userccArray, $emailDiff);
						$data['user_cc'] = implode(',', $userccArray);
					}

					if (!empty($data['dpe_cc']) && is_string($data['dpe_cc']))
					{
						$dpeccArray     = explode(',', $data['dpe_cc']);
						$dpeccArray     = array_diff($dpeccArray, $emailDiff);
						$data['dpe_cc'] = implode(',', $dpeccArray);
					}
				}
			}

			$ticketData['ticket_id'] = $ticket->id;
			$ticketData['agency_id'] = $data['cluster'];
			$dpe_allow_emails = ($table->dpe_allow_admin);

			$adminEmails = new stdClass;
			$user = Factory::getUser();

			if(!$dpe_allow_emails){
				$adminEmails->user_id = (string)$user->id;
			}else{
				$dpe_allow_emails = json_decode($dpe_allow_emails);
				$adminEmails->user_id = (string)$dpe_allow_emails->user_id;
			}


			if (!empty($data['clusterusers']) || (empty($data['clusterusers']) && $orgccEmails && $data['cluster']))
			{
				$userData             = new stdclass;
				$userData->email      = $data['clusterusers'];
				$ticketData['emails'] = json_encode($userData);
			}

			$userCC                       = new stdclass;
			$userCC->email                = array_filter(explode(',', $data['user_cc']));
			$ticketData['user_cc_emails'] = json_encode($userCC);

			$dpeCC                       = new stdclass;
			$dpeCC->email                = array_filter(explode(',', $data['dpe_cc']));
			$ticketData['dpe_cc_emails'] = json_encode($dpeCC);

				$customerId = (string) $data['customer_id']; 

				if(!$data['is_allow']){
					// ignore the admins input if checkbox is not enabled
					$data['admins'] = [$customerId]; // Add only custmer ID  
				}
				else{

				if (!in_array($customerId, $data['admins'])) {
					$data['admins'][] = $customerId;
				}
			}

			$adminEmails->email = $data['admins'];
			$adminEmails->is_allow = $data['is_allow'];

			$ticketData['dpe_allow_admin'] = json_encode($adminEmails);

			$result=$table->save($ticketData);
			if ($table->save($ticketData) === true)
			{
				return true;
			}
		}
	}

	/**
	 * Function to get user id by email
	 *
	 * @param   String  $email  Email
	 *
	 * @return  integer|boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getUserByEmail($email)
	{
		if (empty($email))
		{
			return false;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__users'))
			->where($db->quoteName('email') . ' = ' . $db->quote($email));
		$db->setQuery($query);

		return (int) $db->loadResult();
	}
}
