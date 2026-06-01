<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RSTicketsProUsersHelper
{
	protected static $groups = null;
	protected static $users = null;

	public static function getAdminGroups()
	{
		if (!is_array(self::$groups))
		{
			self::$groups = RSTicketsProConfig::getInstance()->get('admin_groups', array());
		}

		return self::$groups;
	}
	
	public static function getAdminUsers() {
		if (!is_array(self::$users))
		{
			self::$users = array();
			
			if ($groups	= self::getAdminGroups())
			{
				$db 	= JFactory::getDbo();
				$query 	= $db->getQuery(true);
				$query->select('u.*')
					  ->from('#__user_usergroup_map m')
					  ->join('right', '#__users u ON (u.id=m.user_id)')
					  ->where('m.group_id IN ('.implode(',', $groups).')')
					  ->order('u.username ASC')
					  ->group('u.id');
				$db->setQuery($query);
				self::$users = $db->loadObjectList();
			}
		}
		
		return self::$users;
	}
}