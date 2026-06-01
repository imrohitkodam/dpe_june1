<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewSearch extends JViewLegacy
{
	protected $form;
	
	public function display($tpl = null)
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		$this->addToolbar();

		$this->form	= $this->get('Form');
		
		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
		// set title
		JToolbarHelper::title('RSTickets! Pro', 'rsticketspro');

		RSTicketsProToolbarHelper::addToolbar('tickets');
		
		JToolbarHelper::custom('search.perform', 'search', 'search', JText::_('RST_SEARCH'), false);
		JToolbarHelper::cancel('search.cancel');
	}
}