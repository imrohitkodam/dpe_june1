<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewKbarticles extends JViewLegacy
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

		RSTicketsProToolbarHelper::addToolbar('kbarticles');

		$user = JFactory::getUser();

		if ($user->authorise('kbarticle.create', 'com_rsticketspro'))
		{
			JToolbarHelper::addNew('kbarticle.add');
		}
		if ($user->authorise('kbarticle.edit', 'com_rsticketspro'))
		{
			JToolbarHelper::editList('kbarticle.edit');
		}
		if ($user->authorise('kbarticle.edit.state', 'com_rsticketspro'))
		{
			JToolbarHelper::publish('kbarticles.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('kbarticles.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}
		if ($user->authorise('kbarticle.delete', 'com_rsticketspro'))
		{
			JToolbarHelper::deleteList('RST_CONFIRM_DELETE', 'kbarticles.delete');
		}
	}
}