<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewPriorities extends JViewLegacy
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

		RSTicketsProToolbarHelper::addToolbar('priorities');

		$user = JFactory::getUser();

		if ($user->authorise('priority.create', 'com_rsticketspro'))
		{
			JToolbarHelper::addNew('priority.add');
		}
		if ($user->authorise('priority.edit', 'com_rsticketspro'))
		{
			JToolbarHelper::editList('priority.edit');
		}
		if ($user->authorise('priority.edit.state', 'com_rsticketspro'))
		{
			JToolbarHelper::publish('priorities.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('priorities.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}
		if ($user->authorise('priority.delete', 'com_rsticketspro'))
		{
			JToolbarHelper::deleteList('RST_CONFIRM_DELETE', 'priorities.delete');
		}
	}
}