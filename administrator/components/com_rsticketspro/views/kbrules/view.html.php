<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewKbrules extends JViewLegacy
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

		RSTicketsProToolbarHelper::addToolbar('kbrules');

		$user = JFactory::getUser();

		if ($user->authorise('kbrule.create', 'com_rsticketspro'))
		{
			JToolbarHelper::addNew('kbrule.add');
		}
		if ($user->authorise('kbrule.edit', 'com_rsticketspro'))
		{
			JToolbarHelper::editList('kbrule.edit');
		}
		if ($user->authorise('kbrule.edit.state', 'com_rsticketspro'))
		{
			JToolbarHelper::publish('kbrules.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('kbrules.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}
		if ($user->authorise('kbrule.delete', 'com_rsticketspro'))
		{
			JToolbarHelper::deleteList('RST_CONFIRM_DELETE', 'kbrules.delete');
		}
	}
}