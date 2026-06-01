<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewTickets extends JViewLegacy
{
	public function display($tpl = null)
	{
		$this->addToolbar();

		$this->globalMessage            = JText::_(RSTicketsProHelper::getConfig('global_message'));
		$this->globalMessagePosition	= RSTicketsProHelper::getConfig('global_message_position');
		$this->state 		    = $this->get('State');
		$this->filterForm       = $this->get('FilterForm');
		$this->activeFilters    = $this->get('ActiveFilters');
		$this->bulkForm         = $this->get('BulkForm');
		$this->dateFormat 	    = RSTicketsProHelper::getConfig('date_format');
		$this->autocloseEnabled = RSTicketsProHelper::getConfig('autoclose_enabled');
		$this->permissions 	    = $this->get('permissions');
		$this->isStaff		    = RSTicketsProHelper::isStaff();
		$this->items 		    = $this->get('Items');
		$this->limitstart	    = $this->get('start');
		$this->pagination 	    = $this->get('Pagination');
		$this->totalItems 	    = $this->get('Total');
		$this->isSearching      = $this->get('isSearching');
		$this->searches 	    = $this->get('searches');
		$this->hasSearches 	    = !empty($this->searches);
		$this->predefinedSearch = $this->get('predefinedsearch');
		$this->flagged 			= $this->get('flagged');

		$this->setPriorityColors();

		if (RSTicketsProHelper::getConfig('enable_time_spent'))
		{
			if ($field = $this->filterForm->getField('fullordering', 'list'))
			{
				$field->addOption('COM_RSTICKETSPRO_TIMESPENT_ORDER_ASC', array('value' => 'time_spent ASC'));
				$field->addOption('COM_RSTICKETSPRO_TIMESPENT_ORDER_DESC', array('value' => 'time_spent DESC'));
			}
		}
		
		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
		// set title
		JToolbarHelper::title('RSTickets! Pro', 'rsticketspro');

		RSTicketsProToolbarHelper::addToolbar('tickets');
		
		$permissions = RSTicketsProHelper::getCurrentPermissions();
		if ($permissions)
		{
			if ($permissions->add_ticket || $permissions->add_ticket_staff || $permissions->add_ticket_customers)
			{
				JToolbarHelper::addNew('submit.showform');
			}

			if (!empty($permissions->export_tickets))
			{
				JToolBarHelper::custom('tickets.exportcsv', 'download.png', 'download_f2.png', 'COM_RSTICKETSPRO_EXPORT', false);
			}

			if ($permissions->move_ticket || $permissions->assign_tickets || $permissions->update_ticket || $permissions->change_ticket_status || $permissions->delete_ticket || RSTicketsProHelper::getConfig('autoclose_enabled'))
			{
				if (version_compare(JVERSION, '4.0', '>='))
				{
					$toolbar = JToolbar::getInstance('toolbar');
					$toolbar->popupButton('batch')
						->text('RST_BULK_ACTIONS')
						->selector('rsticketsproBulkModal')
						->listCheck(true);
				}
				else
				{
					JToolbarHelper::modal('rsticketsproBulkModal', 'icon-move', 'RST_BULK_ACTIONS');
				}
			}
		}

		JToolbarHelper::custom('search.advanced', 'search', 'search', JText::_('RST_OPEN_ADVANCED_SEARCH'), false);
	}
	
	protected function showDate($date)
	{
		return JHtml::_('date', $date, $this->dateFormat);
	}

	public function showTotal($duration)
	{
		return RSTicketsProHelper::showTotal($duration, true);
	}

	public function notify($ticket)
	{
		return RSTicketsProHelper::showNotifyIcon($ticket);
	}

	protected function setPriorityColors()
	{
		if ($priorities = $this->get('Priorities'))
		{
			$css      = '';
			$colorize = RSTicketsProHelper::getConfig('color_whole_ticket');
			$class 	  = $colorize ? '' : '.rst_priority_cell';
			foreach ($priorities as $priority)
			{
				if ($priority->bg_color)
				{
					$css .= 'table.adminlist tr.rst_priority_color_'.$priority->id.' td'.$class.' { background-color: '.$this->escape($priority->bg_color).' !important; }'."\n";
				}
				if ($priority->fg_color)
				{
					$css .= 'table.adminlist tr.rst_priority_color_'.$priority->id.' td'.$class.','."\n";
					$css .= 'table.adminlist tr.rst_priority_color_'.$priority->id.' td a'.$class.' { color: '.$this->escape($priority->fg_color).' !important; }'."\n";
				}
			}

			if ($css)
			{
				JFactory::getDocument()->addStyleDeclaration($css);
			}
		}
	}
}