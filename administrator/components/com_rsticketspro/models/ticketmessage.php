<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelTicketmessage extends JModelAdmin
{
	public function getTable($type = 'Ticketmessages', $prefix = 'RsticketsproTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}
	
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.ticketmessage', 'ticketmessage', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}
	
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$app  = JFactory::getApplication();
		$data = $app->getUserState('com_rsticketspro.edit.ticketmessage.data', array());
		
		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	protected function canDelete($message)
	{
		$messageUserId = $message->user_id;
		$meId		   = JFactory::getUser()->id;
		$isStaff 	   = RSTicketsProHelper::isStaff($meId);
		$permissions   = RSTicketsProHelper::getCurrentPermissions();
		
		$canDeleteReplies 			= $permissions->delete_ticket_replies;
		$canDeleteCustomerReplies 	= $permissions->delete_ticket_replies_customers;
		$canDeleteStaffReplies 		= $permissions->delete_ticket_replies_staff;
		
		return $isStaff && (
			($canDeleteReplies && $messageUserId == $meId) ||
			($canDeleteCustomerReplies && $messageUserId != $meId && !RSTicketsProHelper::isStaff($messageUserId)) ||
			($canDeleteStaffReplies && $messageUserId != $meId && RSTicketsProHelper::isStaff($messageUserId))
		);
	}

	public function deleteattachment(&$pks)
	{
		$pks = (array) $pks;
		$table = $this->getTable('Ticketfiles');

		foreach ($pks as $i => $pk)
		{
			if ($table->load($pk))
			{
				$message = $this->getTable();
				$message->load($table->ticket_message_id);
				if ($this->canDelete($message))
				{
					if (!$table->delete($pk))
					{
						$this->setError($table->getError());

						return false;
					}
				}
				else
				{
					// Prune items that you can't change.
					unset($pks[$i]);
					$error = $this->getError();

					if ($error)
					{
						\JLog::add($error, \JLog::WARNING, 'jerror');

						return false;
					}
					else
					{
						\JLog::add(\JText::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), \JLog::WARNING, 'jerror');

						return false;
					}
				}
			}
			else
			{
				$this->setError($table->getError());

				return false;
			}
		}

		// Clear the component's cache
		$this->cleanCache();

		return true;
	}
}