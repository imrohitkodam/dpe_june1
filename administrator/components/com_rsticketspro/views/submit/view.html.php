<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewSubmit extends JViewLegacy
{
	public function display($tpl = null)
	{
		$this->checkPermissions();

		JFactory::getApplication()->input->set('hidemainmenu', true);

		$this->addToolbar();

		$this->globalMessage 		    = JText::_(RSTicketsProHelper::getConfig('global_message'));
		$this->globalMessagePosition	= RSTicketsProHelper::getConfig('global_message_position');
		$this->submitMessage 		    = JText::_(RSTicketsProHelper::getConfig('submit_message'));
		$this->submitMessagePosition	= RSTicketsProHelper::getConfig('submit_message_position');
		$this->form  				= $this->get('Form');
		$this->show_footer         	= RSTicketsProHelper::getConfig('rsticketspro_link');
		$this->departments         	= $this->get('Departments');
		$this->customFields        	= $this->get('CustomFields');
		$this->user                	= JFactory::getUser();
		$this->permissions         	= $this->get('Permissions');
		$this->isStaff             	= RSTicketsProHelper::isStaff();
		$this->canChangeSubmitType 	= $this->isStaff && $this->permissions && ($this->permissions->add_ticket_customers || $this->permissions->add_ticket_staff);
		$this->showAltEmail        	= RSTicketsProHelper::getConfig('show_alternative_email');

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		// set title
		JToolbarHelper::title('RSTickets! Pro', 'rsticketspro');

		RSTicketsProToolbarHelper::addToolbar('tickets');

		JToolbarHelper::addNew('submit.save', JText::_('RST_SUBMIT'));
		JToolbarHelper::cancel('submit.cancel');
	}

	protected function checkPermissions()
	{
		$permissions = RSTicketsProHelper::getCurrentPermissions();
		if (!$permissions || (!$permissions->add_ticket && !$permissions->add_ticket_staff && !$permissions->add_ticket_customers))
		{
			throw new Exception(JText::_('RST_STAFF_CANNOT_SUBMIT_TICKET'), 403);
		}
	}
}