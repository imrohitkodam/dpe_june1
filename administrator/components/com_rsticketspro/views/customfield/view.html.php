<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewCustomfield extends JViewLegacy
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

		RSTicketsProToolbarHelper::addToolbar('customfields');
		
		JToolbarHelper::apply('customfield.apply');
		JToolbarHelper::save('customfield.save');
		JToolbarHelper::save2new('customfield.save2new');
		JToolbarHelper::save2copy('customfield.save2copy');
		JToolbarHelper::cancel('customfield.cancel');
	}
}