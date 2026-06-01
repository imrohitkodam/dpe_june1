<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewKbtemplate extends JViewLegacy
{
	protected $tabs;
	protected $field;
	protected $form;
	protected $sidebar;
	
	function display($tpl = null) {		
		$this->addToolbar();
		
		// tabs
		$this->tabs		 = $this->get('RSTabs');
		$this->field	 = $this->get('RSFieldset');
		
		// form
		$this->form		 = $this->get('Form');
		
		$this->sidebar	= $this->get('SideBar');
		
		parent::display($tpl);
	}
	
	protected function addToolbar() {
		// set title
		JToolbarHelper::title('RSTickets! Pro', 'rsticketspro');
		
		require_once JPATH_COMPONENT.'/helpers/toolbar.php';
		RSTicketsProToolbarHelper::addToolbar('kbtemplate');
		
		JToolbarHelper::apply('kbtemplate.apply');
		JToolbarHelper::save('kbtemplate.save');
		JToolbarHelper::cancel('kbtemplate.cancel');
	}
}