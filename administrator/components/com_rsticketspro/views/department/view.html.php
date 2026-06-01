<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewDepartment extends JViewLegacy
{
	protected $form;
	protected $item;
	protected $tabs;
	protected $php_values;
	
	public function display($tpl = null)
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		$this->addToolbar();

		$this->form			= $this->get('Form');
		$this->item			= $this->get('Item');
		$this->tabs	 		= $this->get('RSTabs');
		$this->php_values 	= $this->get('PHPValues');
		
		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
		// set title
		JToolbarHelper::title('RSTickets! Pro', 'rsticketspro');

		RSTicketsProToolbarHelper::addToolbar('departments');
		
		JToolbarHelper::apply('department.apply');
		JToolbarHelper::save('department.save');
		JToolbarHelper::save2new('department.save2new');
		JToolbarHelper::save2copy('department.save2copy');
		JToolbarHelper::cancel('department.cancel');
	}
}