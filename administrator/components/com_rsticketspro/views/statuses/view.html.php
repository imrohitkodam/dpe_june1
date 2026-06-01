<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewStatuses extends JViewLegacy
{
	protected $items;
	protected $pagination;
	protected $state;
	
	public function display($tpl = null)
	{
		$this->addToolbar();

		$this->items 		 = $this->get('Items');
		$this->pagination 	 = $this->get('Pagination');
		$this->state 		 = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		
		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
		// set title
		JToolbarHelper::title('RSTickets! Pro', 'rsticketspro');

		RSTicketsProToolbarHelper::addToolbar('statuses');

		$user = JFactory::getUser();

		if ($user->authorise('status.create', 'com_rsticketspro'))
		{
			JToolbarHelper::addNew('status.add');
		}
		if ($user->authorise('status.edit', 'com_rsticketspro'))
		{
			JToolbarHelper::editList('status.edit');
		}
		if ($user->authorise('status.edit.state', 'com_rsticketspro'))
		{
			JToolbarHelper::publish('statuses.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('statuses.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}
		if ($user->authorise('status.delete', 'com_rsticketspro'))
		{
			JToolbarHelper::deleteList('RST_CONFIRM_DELETE', 'statuses.delete');
		}
	}
}