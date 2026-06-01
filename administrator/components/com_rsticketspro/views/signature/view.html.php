<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewSignature extends JViewLegacy
{
	protected $form;
	
	public function display($tpl = null)
	{
		// only staff members can call this
		if (!RSTicketsProHelper::isStaff())
		{
			throw new Exception(JText::_('RST_CANNOT_CHANGE_SIGNATURE'), 403);
		}
		if (!$this->get('isAssigned'))
		{
			throw new Exception(JText::_('RST_CANNOT_CHANGE_SIGNATURE_MUST_BE_STAFF'), 403);
		}

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
		
		JToolbarHelper::apply('signature.apply');
		JToolbarHelper::cancel('signature.cancel');
	}
}