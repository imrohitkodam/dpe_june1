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
		$this->canAccess();

		$this->bulkForm         = $this->get('BulkForm');
		$this->params           = JFactory::getApplication()->getParams('com_rsticketspro');
		$this->globalMessage            = JText::_(RSTicketsProHelper::getConfig('global_message'));
		$this->globalMessagePosition	= RSTicketsProHelper::getConfig('global_message_position');
		$this->dateFormat 	    = RSTicketsProHelper::getConfig('date_format');
		$this->autocloseEnabled = RSTicketsProHelper::getConfig('autoclose_enabled');
		$this->permissions 	    = $this->get('permissions');
		$this->isStaff		    = RSTicketsProHelper::isStaff();
		$this->items 		    = $this->get('Items');
		$this->limitstart	    = $this->get('start');
		$this->pagination 	    = $this->get('Pagination');
		$this->state 		    = $this->get('State');
		$this->totalItems 	    = $this->get('Total');
		$this->isSearching      = $this->get('isSearching');
		$this->showFooter       = RSTicketsProHelper::getConfig('rsticketspro_link');
		$this->footer	        = RSTicketsProHelper::getFooter();
		$this->searches 	    = $this->get('searches');
		$this->hasSearches 	    = !empty($this->searches);
		$this->predefinedSearch = $this->get('predefinedsearch');
		$this->flagged 			= $this->get('flagged');

		$this->setMetadata();
		$this->setPriorityColors();
		
		parent::display($tpl);
	}
	
	protected function setMetadata()
	{
		// Description
		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}
		// Keywords
		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}
		// Robots
		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
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
	
	protected function canAccess()
	{
		if (JFactory::getUser()->get('guest'))
		{
			JFactory::getApplication()->redirect(RSTicketsProHelper::route('index.php?option=com_users&view=login&return=' . base64_encode((string) JUri::getInstance()), false));
		}
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
}