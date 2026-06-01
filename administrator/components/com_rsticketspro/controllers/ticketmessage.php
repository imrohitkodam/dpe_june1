<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproControllerTicketmessage extends JControllerForm
{
	// this is for the redirect...
	protected $view_list = 'tickets';

	protected function allowAdd($data = array())
	{
		// false because adding a message to a ticket is done through another controller
		return false;
	}
	
	protected function allowEdit($data = array(), $key = 'id')
	{
		// Get item to remove from the request.
		$messageId = $data[$key];
		
		// Get the models
		$modelMessage = $this->getModel();
		$modelTicket  = $this->getModel('ticket');
		
		// check if the message exists in the database
		$tableMessage = $modelMessage->getTable();
		if ($tableMessage->load($messageId))
		{
			$id = $tableMessage->ticket_id;
			// only staff members can call this
			// & must be able to see the ticket in order to edit this message
			if (!$modelTicket->isStaff() || !$modelTicket->hasPermission($id))
			{
				throw new Exception($modelTicket->getError(), 403);
			}
			
			$messageUserId = $tableMessage->user_id;
			$meId		   = JFactory::getUser()->id;
			$isStaff 	   = RSTicketsProHelper::isStaff($meId);
			$permissions   = RSTicketsProHelper::getCurrentPermissions();
			
			$canUpdateReplies 			= $permissions->update_ticket_replies;
			$canUpdateCustomerReplies 	= $permissions->update_ticket_replies_customers;
			$canUpdateStaffReplies 		= $permissions->update_ticket_replies_staff;
			
			return $isStaff && (
				($canUpdateReplies && $messageUserId == $meId) ||
				($canUpdateCustomerReplies && $messageUserId != $meId && !RSTicketsProHelper::isStaff($messageUserId)) ||
				($canUpdateStaffReplies && $messageUserId != $meId && RSTicketsProHelper::isStaff($messageUserId))
			);
		}
		
		return false;
	}

	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);

		if ($this->getTask() === 'apply')
		{
			$append .= '&saved=1';
		}

		return $append;
	}
}