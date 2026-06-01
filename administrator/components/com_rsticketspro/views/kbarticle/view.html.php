<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewKbarticle extends JViewLegacy
{
	protected $form;
	protected $item;
	protected $ticket;
	
	public function display($tpl = null)
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		$this->addToolbar();
		
		$this->form	  = $this->get('Form');
		$this->item	  = $this->get('Item');
		$this->ticket = $this->get('Ticket');
		
		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
		// set title
		JToolbarHelper::title('RSTickets! Pro', 'rsticketspro');

		RSTicketsProToolbarHelper::addToolbar('kbarticles');
		
		JToolbarHelper::apply('kbarticle.apply');
		JToolbarHelper::save('kbarticle.save');
		JToolbarHelper::save2new('kbarticle.save2new');
		JToolbarHelper::save2copy('kbarticle.save2copy');
		JToolbarHelper::cancel('kbarticle.cancel');
	}
}