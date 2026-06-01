<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewGroup extends JViewLegacy
{
	protected $form;
	protected $item;
	protected $tabs;
	
	public function display($tpl = null)
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		$this->addToolbar();

		$this->form	= $this->get('Form');
		$this->item	= $this->get('Item');
		$this->tabs	= $this->get('RSTabs');
		
		parent::display($tpl);
	}

	protected function addToolbar()
	{
		// set title
		JToolbarHelper::title('RSTickets! Pro', 'rsticketspro');

		RSTicketsProToolbarHelper::addToolbar('groups');
		
		JToolbarHelper::apply('group.apply');
		JToolbarHelper::save('group.save');
		JToolbarHelper::save2new('group.save2new');
		JToolbarHelper::save2copy('group.save2copy');
		JToolbarHelper::cancel('group.cancel');
	}
}