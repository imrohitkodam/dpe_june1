<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;


/**
 * Rsticket list controller class.
 *
 * @since  __DEPLOY__VERSION__
 */
class DpeControllerRsticket extends AdminController
{

	protected $option  = 'com_rsticketspro';
	protected $context = 'submit';

	/**
	 * This function fetch the admins users of cluster/oraganization
	 *
	 * @return  string   html to build a assignment list view
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getAdminUsersByClusterId()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		$data = $app->input->getArray();

		if (!$data['clusterId'])
		{
			echo new JsonResponse(null, Text::sprintf('COM_DPE_NO_CLUSTER_SELECTED', Text::_('COM_MULTIAGENCY_ORGANISATION')), true);
			$app->close();
		}

		// Get Cluster data
		$clustertable = ClusterFactory::table('Clusters');
		$clustertable->load(array('id' => $data['clusterId']));

		if (!$clustertable->client_id)
		{
			echo new JsonResponse(null, Text::sprintf('COM_DPE_NO_CLUSTER_SELECTED', Text::_('COM_MULTIAGENCY_ORGANISATION')), true);
			$app->close();
		}

		$user = Factory::getUser();

		$rsticketModel = DPE::model('rsticket');
		$clusterAdminUsers = $rsticketModel->getUsersByActions(array('core.adduser'), 'com_multiagency', $clustertable->client_id);

		$selectedEmails = null;
		$customerId = 0;

		if ($data['ticketId'])
		{
			$rsXref = DPE::table('RsticketXref');
			$rsXref->load(array('ticket_id' => $data['ticketId']));

			$selectedEmails = $rsXref->dpe_allow_admin;

			// Get Ticket Details
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_rsticketspro/tables');
			$ticketTable = Table::getInstance('Tickets', 'RsticketsproTable', array());
			$ticketTable->load(array('id' => $data['ticketId']));

			if (property_exists($ticketTable, 'customer_id'))
			{
				$customerId = $ticketTable->customer_id;

				$customerInfo = Factory::getUser($customerId);
				

				$customerInfoObj            = [];
				$customerInfoObj['id']      = $customerInfo->id;
				$customerInfoObj['name']    = $customerInfo->name;
				$customerInfoObj['email']   = $customerInfo->email;
				$customerInfoObj['user_id'] = $customerId;

				$guestUserObj = '';

				$roles = RBACL::getRoleByUser($ticketTable->customer_id);

				/* If ticket customer is guest user then add user object in response
				to show customer id selected in customer dropdown
				*/

				if (empty($roles) && !$customerInfo->authorise('core.manageall', 'com_cluster'))
				{
					$guestUserObj        = new stdClass;
					$guestUserObj->id    = $customerInfo->id;
					$guestUserObj->name  = $customerInfo->name;
					$guestUserObj->email = $customerInfo->email;
				}
			}
			$clusterAdminUsers[] = (object) $customerInfoObj;
		}


			// Removed condition becuze of if no admins found then its taking previous cluster admins
			$clusterAdminUsers = array_unique($clusterAdminUsers, SORT_REGULAR);


			echo new JsonResponse(array($clusterAdminUsers, $selectedEmails, $customerId, $guestUserObj));
			$app->close();
	}
	/**
	 * This function provide the users of cluster
	 *
	 * @return  string   html to build a assignment list view
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getClusterUsers()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		$data = $app->input->getArray();

		if (!$data['clusterId'])
		{
			echo new JsonResponse(null, Text::sprintf('COM_DPE_NO_CLUSTER_SELECTED', Text::_('COM_MULTIAGENCY_ORGANISATION')), true);
			$app->close();
		}

		// Get Cluster data
		$clustertable = ClusterFactory::table('Clusters');
		$clustertable->load(array('id' => $data['clusterId']));

		if (!$clustertable->client_id)
		{
			echo new JsonResponse(null, Text::sprintf('COM_DPE_NO_CLUSTER_SELECTED', Text::_('COM_MULTIAGENCY_ORGANISATION')), true);
			$app->close();
		}

		$user = Factory::getUser();

		$rsticketModel = DPE::model('rsticket');
		$clusterUsers = $rsticketModel->getUsersByActions(array('core.adduser'), 'com_multiagency', $clustertable->client_id);
		$staffUsers   = $rsticketModel->getUsersByActions(array('core.view.all'), 'com_multiagency', $clustertable->client_id);

		if ($staffUsers)
		{
			$clusterUsers = array_merge($clusterUsers, $staffUsers);
		}

		// Get dpe admin roles
		if ($user->authorise('core.manageall', 'com_cluster'))
		{
			$dpeAdminUsers = $rsticketModel->getUsersByActions(array('core.create'), 'com_multiagency');
			$clusterUsers = array_merge($clusterUsers, $dpeAdminUsers);
		}

		$selectedEmails = null;
		$customerId = 0;

		if ($data['ticketId'])
		{
			$rsXref = DPE::table('RsticketXref');
			$rsXref->load(array('ticket_id' => $data['ticketId']));
			$selectedEmails = $rsXref->emails;

			// Get Ticket Details
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_rsticketspro/tables');
			$ticketTable = Table::getInstance('Tickets', 'RsticketsproTable', array());
			$ticketTable->load(array('id' => $data['ticketId']));

			if (property_exists($ticketTable, 'customer_id'))
			{
				$customerId = $ticketTable->customer_id;

				$customerInfo = Factory::getUser($customerId);

				$guestUserObj = '';

				$roles = RBACL::getRoleByUser($ticketTable->customer_id);

				/* If ticket customer is guest user then add user object in response
				to show customer id selected in customer dropdown
				*/

				if (empty($roles) && !$customerInfo->authorise('core.manageall', 'com_cluster'))
				{
					$guestUserObj        = new stdClass;
					$guestUserObj->id    = $customerInfo->id;
					$guestUserObj->name  = $customerInfo->name;
					$guestUserObj->email = $customerInfo->email;
				}
			}
		}

		if ($clusterUsers)
		{
			// This is code written to get unique user object
			$clusterUsers = array_unique($clusterUsers, SORT_REGULAR);

			echo new JsonResponse(array($clusterUsers, $selectedEmails, $customerId, $guestUserObj));
			$app->close();
		}
	}

	/**
	 * This function create ticket from ucm logs
	 *
	 * @return  object   
	 *
	 * @since   __DEPLOY__VERSION__
	 */

	public function addTicketFromUcmLog()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$app      = JFactory::getApplication();
		$input    = $app->input;
		$data     = $input->get('jform', array(), 'array');	

		$fields   = $input->get('rst_custom_fields', array(), 'array');	
		$files    = $input->files->get('jform', null, 'raw');

		JLoader::import('components.com_rsticketspro.models.submit', JPATH_ADMINISTRATOR);
		$model = BaseDatabaseModel::getInstance('Submit', 'RsticketsproModel');

		$context  = "$this->option.edit.$this->context";
		$redirect = RSTicketsProHelper::getConfig('submit_redirect');
		$data['subject']     = strip_tags($data['subject']);
		$session = Factory::getSession();

		if (!$model->save($data, $fields, is_array($files) && isset($files['files']) ? $files['files'] : array()))
		{
			// Save the data in the session.
			$app->setUserState($context . '.data', $data);
			$app->setUserState($context . '.fields', $fields);
			
			$this->setMessage($model->getError(), 'error');

			$message['msg']  =  Text::_($model->getError());
			$message['success'] = 0; 
			echo new JsonResponse($message);
			$app->close();
		}
		else
		{
			// Clear the data in the session
			$app->setUserState($context . '.data', null);
			$app->setUserState($context . '.fields', null);

			$this->setMessage(JText::_('RST_TICKET_SUBMIT_OK', 'info'));

			// DPE - Hack - After saving ticket wants to redirect user on ticket list view.
			if($data['ucmpopup'])
			{	 

				$client   = $input->post->get('client', '', 'STRING');
				$data     = $input->post->get('data', array(), 'ARRAY');
				$formData = $input->post->get('ucmformDatas', array(), 'ARRAY');
				$formData = json_decode(json_encode(json_decode($formData[0])), true);
				$data     = json_decode(json_encode(json_decode($data[0])), true);

				PluginHelper::importPlugin("dpe");
				$result             = Factory::getApplication()->triggerEvent('onAfterUcmLogSave', array($client, $data, $formData));
				$message['msg']     = ($result[0]['result'])?Text::_('COM_DPE_TICKET_GENERATION_SUCCESS'):Text::_('COM_DPE_TICKET_GENERATION_FAIL');
				$message['success'] = ($result[0]['result'])?1:0;
				$message['url'] = $result[0]['url'];


				if ($result)
				{
					echo new JsonResponse($message);
					$app->close();
				}
			}
		}

		if ($model->getError() && $data['ucmpopup'])
		{
			$session->clear('modelError'); 
			$session->set('modelError', $model->getError(), '');
			$this->setRedirect('index.php?option=com_rsticketspro&view=submit&tmpl=component');
		}
		
		
	}

	/**
	 * This function getItemId for the ucm log
	 *
	 * @return  object   
	 *
	 * @since   __DEPLOY__VERSION__
	 */

	public function getItemId()
	{
		$app      = Factory::getApplication();
		$input    = $app->input;
		$client  = $input->get('logClient');	
		JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
		$tjUcmFrontendHelper = new TjucmHelpersTjucm;
		$itemId = $tjUcmFrontendHelper->getItemId('index.php?option=com_tjucm&view=itemform&client='.$client);

		$message['success'] = ($itemId)?1:0;
		$message['url'] = Route::_('index.php?option=com_tjucm&view=itemform&Itemid='.$itemId, false);

		echo new JsonResponse($message);
		$app->close();
	}


	/**
	 * This function Save Log Id in xref table
	 *
	 * @return  object   
	 *
	 * @since   __DEPLOY__VERSION__
	 */

	public function saveLogId()
	{
		$app      = Factory::getApplication();
		$input    = $app->input;
		$logId    = $input->get('logId');
		$ticketId = $input->get('ticketId');


		JLoader::import('components.com_dpe.models.rsticket', JPATH_SITE);
		$model = BaseDatabaseModel::getInstance('Rsticket', 'DpeModel');
		$result = $model->saveLogFromTicket($logId, $ticketId);

		$message['success'] =  ($result)?true:false;
		
		echo new JsonResponse($message);
		$app->close();
	}
}
