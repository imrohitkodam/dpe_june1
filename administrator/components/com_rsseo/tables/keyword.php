<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Table\Table;

class rsseoTableKeyword extends Table
{
	/**
	 * @param	JDatabase	A database connector object
	 */
	public function __construct($db) {
		parent::__construct('#__rsseo_keywords', 'id', $db);
	}
	
	public function check() {
		$db = $this->getDbo();
		$db->setQuery("SELECT `id`, `keyword` FROM `#__rsseo_keywords` WHERE `keyword` = ".$db->q($this->keyword));
		if ($keywords = $db->loadObjectList()) {
			foreach ($keywords as $keyword) {
				if ($keyword->keyword === $this->keyword) {
					$this->id = $keyword->id;
				}
			}
		}
		
		$this->limit = (int) $this->limit;
		
		return true;
	}
}