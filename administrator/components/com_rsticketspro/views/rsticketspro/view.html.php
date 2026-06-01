<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewRsticketspro extends JViewLegacy
{
	protected $buttons;
	// version info
	protected $code;
	protected $version;
	
	public function display($tpl = null)
	{
		$this->addToolbar();
		
		$this->buttons  	= $this->get('Buttons');
		$this->kbbuttons  	= $this->get('Kbbuttons');
		$this->code			= $this->get('code');
		$this->version		= (string) new RSTicketsProVersion();
		
		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
		if (JFactory::getUser()->authorise('core.admin', 'com_rsticketspro'))
		{
			JToolbarHelper::preferences('com_rsticketspro');
		}

		// set title
		JToolbarHelper::title('RSTickets! Pro', 'rsticketspro');

		RSTicketsProToolbarHelper::addToolbar('rsticketspro');
	}
}