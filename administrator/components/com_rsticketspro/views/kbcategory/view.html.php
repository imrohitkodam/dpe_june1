<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewKbcategory extends JViewLegacy
{
	protected $form;
	protected $item;
	
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

		RSTicketsProToolbarHelper::addToolbar('kbcategories');
		
		JToolbarHelper::apply('kbcategory.apply');
		JToolbarHelper::save('kbcategory.save');
		JToolbarHelper::save2new('kbcategory.save2new');
		JToolbarHelper::save2copy('kbcategory.save2copy');
		JToolbarHelper::cancel('kbcategory.cancel');
	}
}