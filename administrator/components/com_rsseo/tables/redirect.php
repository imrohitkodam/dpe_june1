<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Table\Table;

class rsseoTableRedirect extends Table
{
	/**
	 * @param	JDatabase	A database connector object
	 */
	public function __construct($db) {
		parent::__construct('#__rsseo_redirects', 'id', $db);
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
		
		// Remove the referrers
		$query->delete($db->qn('#__rsseo_redirects_referer'));
		$query->where($db->qn('rid').' = '.$db->q($pk));
		$db->setQuery($query);
		$db->execute();
		
		return parent::delete($pk, $children);
	}
}