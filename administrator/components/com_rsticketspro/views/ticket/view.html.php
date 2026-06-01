<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewTicket extends JViewLegacy
{
    protected $app;
    protected $form;

	public function display($tpl = null)
	{
		$this->app = JFactory::getApplication();

		if ($this->app->isClient('administrator'))
		{
			JFactory::getApplication()->input->set('hidemainmenu', true);

			$this->addToolbar();
		}
		else
		{
			$this->params = $this->app->getParams('com_rsticketspro');
		}
		
		// get ticket information
		$this->ticket = $this->get('Item');
		
		$user = JFactory::getUser();
		if (!$user->id) {
			JFactory::getApplication()->enqueueMessage(JText::_('RST_YOU_HAVE_TO_BE_LOGGED_IN'), 'warning');
			$link = base64_encode((string) JUri::getInstance());
			$this->app->redirect(JRoute::_('index.php?option=com_users&view=login&return='.$link, false));
		}
		
		// quick and dirty check so we know if this ticket exists & the user can view it
		if (!$this->hasPermission()) {
			throw new Exception(JText::_('RST_CUSTOMER_CANNOT_VIEW_TICKET'), 403);
		}

		// load the ticket helper
		require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/ticket.php';
		
		$this->ticketMessages 	= $this->get('Ticketmessages');
		$this->otherTickets	 	= $this->get('OtherTickets');
		
		// form
		$this->form	= $this->get('Form');
		$this->fieldsets = $this->form->getFieldsets();

		// tabs
		$this->tabs	= $this->get('RSTabs');

		// accordion
		$this->accordion = $this->get('RSAccordion');

		// plain / flat view
		$this->plain = $this->get('RSPlain');

		// DPE HACK fieldset
		$this->field = $this->get('RSFieldset');

		// permissions
		$this->isStaff		 = $this->get('IsStaff');
		$this->permissions	 = $this->get('StaffPermissions');
		
		// departments
		$this->departments	 = $this->get('Departments');
		$this->department	 = $this->get('Department');
		// statuses
		$this->statuses	 	 = $this->get('Statuses');
		$this->priorities	 = $this->get('Priorities');
		
		// config
		$this->globalMessage 	 = JText::_(RSTicketsProHelper::getConfig('global_message'));
		$this->globalMessagePosition = RSTicketsProHelper::getConfig('global_message_position');
		$this->ticketView	 	 = RSTicketsProHelper::getConfig('ticket_view');
		$this->dateFormat 	 	 = RSTicketsProHelper::getConfig('date_format');
		$this->userField	 	 = RSTicketsProHelper::getConfig('show_user_info');
		$this->hasViewingHistory = RSTicketsProHelper::getConfig('ticket_viewing_history');
		$this->allowEditor		 = RSTicketsProHelper::getConfig('allow_rich_editor');
		$this->allowVoting		 = RSTicketsProHelper::getConfig('show_ticket_voting');
		$this->showSignature	 = RSTicketsProHelper::getConfig('show_signature');
		$this->showSearch		 = RSTicketsProHelper::getConfig('show_kb_search');
		$this->showEmailLink	 = RSTicketsProHelper::getConfig('show_email_link');
		$this->hasConsent	 	 = RSTicketsProHelper::getConfig('forms_consent') && (!$this->isStaff || !RSTicketsProHelper::getConfig('forms_consent_staff_skip'));
		$this->ticketSections = $this->get('TicketSections');
		$this->isPrint		  = $this->get('IsPrint');

		if ($this->ticketView === 'accordion')
		{
			$this->handler = $this->accordion = $this->get('RSAccordion');
		}
		elseif ($this->ticketView === 'tabbed')
		{
			$this->handler = $this->tabs = $this->get('RSTabs');
		}

		$this->plain = $this->get('RSPlain');
		
		// user
		$this->userId		 = $user->id;
		
		// permissions
		$this->canViewHistory = $this->hasViewingHistory && (($this->hasViewingHistory == 1 && $this->isStaff) || ($this->hasViewingHistory == 2));
		$this->canViewNotes	  = $this->isStaff && $this->permissions->view_notes;
		$this->canOpenTicket  = ($this->isStaff && $this->permissions->change_ticket_status) || (!$this->isStaff && RSTicketsProHelper::getConfig('allow_ticket_reopening'));
		$this->canCloseTicket = ($this->isStaff && $this->permissions->change_ticket_status) || (!$this->isStaff && RSTicketsProHelper::getConfig('allow_ticket_closing'));
		$this->canReply		  = !$this->isStaff || ($this->isStaff && $this->permissions->answer_ticket);
		$this->canUpload	  = $this->ticket->department->upload > 0;
		$this->canUpdateReplies 		= $this->isStaff && $this->permissions->update_ticket_replies;
		$this->canUpdateCustomerReplies = $this->isStaff && $this->permissions->update_ticket_replies_customers;
		$this->canUpdateStaffReplies 	= $this->isStaff && $this->permissions->update_ticket_replies_staff;
		$this->canDeleteReplies 		= $this->isStaff && $this->permissions->delete_ticket_replies;
		$this->canDeleteCustomerReplies = $this->isStaff && $this->permissions->delete_ticket_replies_customers;
		$this->canDeleteStaffReplies 	= $this->isStaff && $this->permissions->delete_ticket_replies_staff;
		$this->canAssignTickets			= $this->isStaff && $this->permissions->assign_tickets;
		$this->canDeleteTicket          = $this->isStaff && $this->permissions->delete_ticket;
		$this->showAltEmail             = RSTicketsProHelper::getConfig('show_alternative_email');
		$this->timeSpentInput           = RSTicketsProHelper::getConfig('enable_time_spent') && RSTicketsProHelper::getConfig('time_spent_type') === 'input';
		$this->timeSpentTracking        = RSTicketsProHelper::getConfig('enable_time_spent') && RSTicketsProHelper::getConfig('time_spent_type') === 'tracking';
		$this->canDeleteTimeHistory 	= $this->get('timeTrackingDeleteStatus');

		// time counter
		$this->useTimeCounter = false;
		$this->ticketTimeData = false;
		$this->ticketTimeState = 0;
		if ($this->timeSpentTracking && isset($this->ticketSections['time']) && $this->ticket->status_id != RST_STATUS_CLOSED) {
			$this->useTimeCounter  = true;
			$this->ticketTimeData  = RSTicketsProTicketHelper::getTicketTimeState($this->ticket->id);
			$this->ticketTimeState = $this->ticketTimeData ? (int) $this->ticketTimeData->state : 0;

			if ($this->ticketTimeState) {
				$this->document->addScriptDeclaration("jQuery(document).ready(function(){RSTicketsPro.timeCounter('". $this->ticketTimeData->start."');});");
			}
		}
		$this->ticketIntervals = $this->get('TicketTimeSpentIntervals');

		RSTicketsProHelper::addHistory($this->ticket->id);
		
		// JS Strings
		JText::script('RST_MAX_UPLOAD_FILES_REACHED');
		JText::script('RST_DELETE_TICKET_MESSAGE_CONFIRM');
		JText::script('RST_DELETE_TICKET_ATTACHMENT_CONFIRM');

		// load jQuery & plugins
		if (RSTicketsProHelper::getConfig('jquery', 1)) {
			JHtml::_('jquery.framework');
		}
		
		if ($this->allowVoting) {
			JHtml::_('script', 'com_rsticketspro/jquery.raty.js', array('relative' => true, 'version' => 'auto'));
			JHtml::_('stylesheet', 'com_rsticketspro/jquery.raty.css', array('relative' => true, 'version' => 'auto'));
		}
		
		// if trying to print, bring up the print stylesheet
		if ($this->isPrint) {
			JHtml::_('stylesheet', 'com_rsticketspro/print.css', array('relative' => true, 'version' => 'auto'),  array('media'=>'print'));
		}
		
		if ($this->canAssignTickets) {
			$this->document->addScriptDeclaration("jQuery(document).ready(function(){RSTicketsPro.disableStaff(document.getElementById('ticket_staff_id').value);});");
		}
		
		if ($this->app->isClient('site')) {
			$this->app->getPathWay()->addItem(($this->ticket->code ? '['.$this->ticket->code.'] ' : '').$this->ticket->subject);
		}
		
		parent::display($tpl);
	}

	public function showTotal($duration)
	{
		return RSTicketsProHelper::showTotal($duration);
	}
	
	protected function addToolbar() {
		// set title
		JToolbarHelper::title('RSTickets! Pro', 'rsticketspro');

		require_once JPATH_COMPONENT.'/helpers/toolbar.php';
		RSTicketsProToolbarHelper::addToolbar('tickets');

		JToolbarHelper::custom('kbconvert.manual', 'upload', 'upload', JText::_('RST_CONVERT_TO_KB'), false);
		JToolbarHelper::custom('kbconvert.automatic', 'upload', 'upload', JText::_('RST_CONVERT_TO_KB_AUTOMATIC'), false);
		JToolbarHelper::cancel('ticket.cancel');
	}
	
	protected function showDate($date) {
		return JHtml::_('date', $date, $this->dateFormat);
	}
	
	protected function getAvatar($id) {
		return RSTicketsProHelper::getAvatar($id);
	}
	
	protected function canEditMessage($message) {
		$messageUserId = $message->user_id;
		$meId		   = $this->userId;
		
		return $this->isStaff && (
			($this->canUpdateReplies && $messageUserId == $meId) ||
			($this->canUpdateCustomerReplies && $messageUserId != $meId && !RSTicketsProHelper::isStaff($messageUserId)) ||
			($this->canUpdateStaffReplies && $messageUserId != $meId && RSTicketsProHelper::isStaff($messageUserId))
		);
	}
	
	protected function canDeleteMessage($message) {
		$messageUserId = $message->user_id;
		$meId		   = $this->userId;
		
		return $this->isStaff && (
			($this->canDeleteReplies && $messageUserId == $meId) ||
			($this->canDeleteCustomerReplies && $messageUserId != $meId && !RSTicketsProHelper::isStaff($messageUserId)) ||
			($this->canDeleteStaffReplies && $messageUserId != $meId && RSTicketsProHelper::isStaff($messageUserId))
		);
	}
	
	protected function hasPermission() {
		$model = $this->getModel();
		
		// ticket does exist && user can open it
		return ($this->ticket && $this->ticket->id && $model->hasPermission($this->ticket->id));
	}
}