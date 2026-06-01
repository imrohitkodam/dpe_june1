<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelKbtemplate extends JModelAdmin
{
	public function __construct() {
		parent::__construct();
	}
	
	public function getForm($data = array(), $loadData = true) {
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.kbtemplate', 'kbtemplate', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}

		return $form;
	}
	
	protected function loadFormData() {
		$data = (array) $this->getConfig()->getData();
		
		return $data;
	}
	
	public function save($data) {		
		// get configuration
		$config = $this->getConfig();
		// get configuration keys
		$keys = array(
			'kb_template_body',
			'kb_template_ticket_body'
		);
		
		foreach ($keys as $key) {
			if (isset($data[$key])) {
				$value = $data[$key];
				
				$config->set($key, $value);
			}
		}
		
		return true;
	}
	
	public function getConfig() {
		return RSTicketsProConfig::getInstance();
	}
	
	public function getSideBar() {
		require_once JPATH_COMPONENT.'/helpers/toolbar.php';
		
		return RSTicketsProToolbarHelper::render();
	}
	
	public function getRSFieldset() {
		require_once JPATH_COMPONENT.'/helpers/adapters/fieldset.php';
		
		$fieldset = new RSFieldset();
		return $fieldset;
	}
	
	public function getRSTabs() {
		require_once JPATH_COMPONENT.'/helpers/adapters/tabs.php';
		
		$tabs = new RSTabs('com-rsticketspro-kbtemplate');
		return $tabs;
	}
}