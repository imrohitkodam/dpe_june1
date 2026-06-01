<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewPredefinedsearches extends JViewLegacy
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

		RSTicketsProToolbarHelper::addToolbar('tickets');
		
		JToolbarHelper::addNew('predefinedsearch.add');
		JToolbarHelper::editList('predefinedsearch.edit');
		JToolbarHelper::divider();
		JToolbarHelper::publish('predefinedsearches.publish', 'JTOOLBAR_PUBLISH', true);
		JToolbarHelper::unpublish('predefinedsearches.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		JToolbarHelper::divider();
		JToolbarHelper::deleteList('RST_CONFIRM_DELETE', 'predefinedsearches.delete');
		JToolbarHelper::cancel('predefinedsearches.cancel');
	}
}