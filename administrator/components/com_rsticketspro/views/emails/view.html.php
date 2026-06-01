<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewEmails extends JViewLegacy
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

		RSTicketsProToolbarHelper::addToolbar('emails');

		$user = JFactory::getUser();

		if ($user->authorise('email.edit', 'com_rsticketspro'))
		{
			JToolbarHelper::editList('email.edit');
		}
		if ($user->authorise('email.edit.state', 'com_rsticketspro'))
		{
			JToolbarHelper::publish('emails.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('emails.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}
	}
}