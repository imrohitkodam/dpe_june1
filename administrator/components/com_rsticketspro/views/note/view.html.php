<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewNote extends JViewLegacy
{
	protected $form;
	protected $item;
	protected $field;
	
	public function display($tpl = null)
	{
		$this->form	= $this->get('Form');
		$this->item	= $this->get('Item');

		// DPE HACK 
		$this->field = $this->get('RSFieldset');
		
		$this->ticket_id = $this->get('TicketId');

		parent::display($tpl);
	}
}