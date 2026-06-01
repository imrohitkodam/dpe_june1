<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;

class RSTicketsProConfig
{
	protected $config;
	protected $db;

	protected $multiple = array('captcha_enabled_for', 'admin_groups');
	
	public function __construct() {
		$this->db = Factory::getDbo();
		$this->load();
	}
	
	public function get($key, $default=false, $explode=false) {
		if (isset($this->config->{$key})) {
			return $explode ? $this->explode($this->config->{$key}) : $this->config->{$key};
		}
		
		return $default;
	}
	
	public function getKeys() {
		return array_keys((array) $this->config);
	}
	
	public function getData() {
		return $this->config;
	}
	
	public function reload() {
		$this->load();
	}
	
	protected function load() {
		// reset the values
		$this->config = new stdClass();
		
		// prepare the query
		$query 	= $this->db->getQuery(true);
		$query->select('*')->from('#__rsticketspro_configuration');
		$this->db->setQuery($query);
		
		// run the query
		if ($results = $this->db->loadObjectList())
		{
			foreach ($results as $result)
			{
				if (in_array($result->name, $this->multiple))
				{
					$result->value = explode(',', $result->value);
				}
				$this->config->{$result->name} = $result->value;
			}
		}
	}
	
	protected function explode($string) {
		$string = str_replace(array("\r\n", "\r"), "\n", $string);
		return explode("\n", $string);
	}
	
	protected function implode($string) {
		return implode("\n", $string);
	}
	
	protected function convert($key, &$value) {
		if (is_array($value)) {
			$value = implode(",", $value);
		}
	}
	
	public function set($key, $value) {
		if (isset($this->config->{$key})) {
			// convert values to appropriate type
			$this->convert($key, $value);
			
			// refresh our value
			$this->config->{$key} = $value;
			
			// prepare the query
			$query = $this->db->getQuery(true);
			$query->update('#__rsticketspro_configuration')
				  ->set($this->db->qn('value').'='.$this->db->q($value))
				  ->where($this->db->qn('name').'='.$this->db->q($key));
			$this->db->setQuery($query);
			
			// run the query
			return $this->db->execute();
		}
		
		return false;
	}
	
	public static function getInstance() {
		static $inst;
		if (!$inst) {
			$inst = new RSTicketsProConfig();
		}
		
		return $inst;
	}
}