<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewStaff extends JViewLegacy
{
	protected $form;
	protected $item;
	protected $field;
	
	public function display($tpl = null)
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		$this->addToolbar();
		
		$this->form	= $this->get('Form');
		$this->item	= $this->get('Item');
		
		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
		// set title
		JToolbarHelper::title('RSTickets! Pro', 'rsticketspro');

		RSTicketsProToolbarHelper::addToolbar('staffs');
		
		JToolbarHelper::apply('staff.apply');
		JToolbarHelper::save('staff.save');
		JToolbarHelper::save2new('staff.save2new');
		JToolbarHelper::cancel('staff.cancel');
	}
}