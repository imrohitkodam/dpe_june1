<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Table\Table;

class rsseoTableGkeyword extends Table
{
	/**
	 * @param	JDatabase	A database connector object
	 */
	public function __construct($db) {
		parent::__construct('#__rsseo_gkeywords', 'id', $db);
	}
	
	public function check() {
		$db = $this->getDbo();
		
		$this->name = strtolower($this->name);
		
		$db->setQuery("SELECT `id`, `name` FROM `#__rsseo_gkeywords` WHERE `name` = ".$db->q($this->name));
		if ($keywords = $db->loadObjectList()) {
			foreach ($keywords as $keyword) {
				if ($keyword->name === $this->name) {
					$this->id = $keyword->id;
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Method to delete a node and, optionally, its child nodes from the table.
	 *
	 * @param   integer  $pk        The primary key of the node to delete.
	 * @param   boolean  $children  True to delete child nodes, false to move them up a level.
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     http://docs.joomla.org/JTable/delete
	 * @since   2.5
	 */
	public function delete($pk = null, $children = false) {
		$db     = $this->getDbo();
		$query	= $db->getQuery(true);
		
		// Remove keyword data
		$query->delete($db->qn('#__rsseo_gkeywords_data'));
		$query->where($db->qn('idk').' = '.$db->q($pk));
		$db->setQuery($query);
		$db->execute();
		
		return parent::delete($pk, $children);
	}
}