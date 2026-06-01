<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewKnowledgebase extends JViewLegacy
{
	protected $buttons;
	// version info
	protected $code;
	protected $version;
	
	public function display($tpl = null) {
		$this->addToolbar();

		JHtml::stylesheet('com_rsticketspro/dashboard.css', array('relative' => true));
		
		$this->buttons  = $this->get('Buttons');
		$this->code		= $this->get('code');
		$this->version	= (string) new RSTicketsProVersion();
		
		$this->sidebar	= $this->get('SideBar');
		
		parent::display($tpl);
	}
	
	protected function addToolbar() {		
		// set title
		JToolbarHelper::title('RSTickets! Pro', 'rsticketspro');
		
		require_once JPATH_COMPONENT.'/helpers/toolbar.php';
		RSTicketsProToolbarHelper::addToolbar('knowledgebase');
	}
}