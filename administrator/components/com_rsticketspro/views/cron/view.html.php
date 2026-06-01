<?php
/**
* @version 2.0.0
* @package RSTickets! Pro 2.0.0
* @copyright (C) 2010 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewCron extends JViewLegacy
{
	protected $form;
	protected $item;
	protected $tabs;
	
	public function display($tpl = null)
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		// form
		$this->form	= $this->get('Form');
		$this->item	= $this->get('Item');
		$this->tabs	= $this->get('RSTabs');
		
		$this->addToolbar();

		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
        JFactory::getApplication()->enqueueMessage(JText::_('RST_CRON_WARNING'), 'notice');
		JToolbarHelper::title('RSTickets! Pro <small>['.JText::_('RST_EDIT_ACCOUNT').']</small>','rsticketspro');

		RSTicketsProToolbarHelper::addToolbar('crons');

		JToolbarHelper::apply('cron.apply');
		JToolbarHelper::save('cron.save');
		JToolbarHelper::cancel('cron.cancel');

		if (!empty($this->item->id))
		{
			JToolbarHelper::modal('rsticketsproCronModal', 'icon-refresh', JText::_('RST_ACCOUNT_TEST_CONNECTION'));
		}
	}
}