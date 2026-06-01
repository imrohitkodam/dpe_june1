<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/version.php';

define('RST_UPLOAD_FOLDER', JPATH_SITE . '/images/tickets/assets/files');
define('RST_CATEGORY_THUMB_FOLDER', JPATH_SITE . '/images/tickets/assets/thumbs');

if (!defined('RST_UPLOAD_FOLDER'))
{
	define('RST_UPLOAD_FOLDER', JPATH_SITE . '/components/com_rsticketspro/assets/files');
}
if (!defined('RST_CATEGORY_THUMB_FOLDER'))
{
	define('RST_CATEGORY_THUMB_FOLDER', JPATH_SITE . '/components/com_rsticketspro/assets/thumbs');
}
if (!defined('RST_ARTICLE_THUMB_FOLDER'))
{
	define('RST_ARTICLE_THUMB_FOLDER', JPATH_SITE . '/components/com_rsticketspro/assets/thumbs/articles');
}

define('RST_STATUS_OPEN', 1);
define('RST_STATUS_CLOSED', 2);
define('RST_STATUS_ON_HOLD', 3);

define('RST_ASSIGNMENT_STATIC', 0);
define('RST_ASSIGNMENT_AUTO', 1);

define('RST_DEPARTMENT_RULE_RANDOM', 1);
define('RST_DEPARTMENT_RULE_SEQUENTIAL', 0);

Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_rsticketspro/tables');
Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_rsticketspro/models/forms');
Form::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_rsticketspro/models/fields');

class RSTicketsProHelper
{
	public static function readConfig($force = false)
	{
		$config = RSTicketsProConfig::getInstance();

		if ($force)
		{
			$config->reload();
		}

		return $config->getData();
	}
	
	public static function cronPluginExists() {
		static $result;

		if (is_null($result)) {
			$result = JPluginHelper::getPlugin('system', 'rsticketsprocron');
		}

		return !empty($result);
	}

	public static function getConfig($name = null)
	{
		$config = RSTicketsProConfig::getInstance();
		if (is_null($name))
		{
			return $config->getData();
		}
		else
		{
			if ($name == 'show_alternative_email' && !self::cronPluginExists())
			{
				return false;
			}

			return $config->get($name);
		}
	}

	public static function saveSystemMessage($ticket_id, $data, $includeUser = true)
	{
		// get the current user
		if ($includeUser)
		{
			$data['user_id'] = Factory::getUser()->id;
		}

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_rsticketspro/tables');

		$message = Table::getInstance('Ticketmessages', 'RsticketsproTable');
		$message->save(array(
			'ticket_id' => $ticket_id,
			'user_id'   => '-1',
			'message'   => serialize($data),
			'date'      => Factory::getDate()->toSql(),
			'html'      => 0
		));
	}

	protected static function getSystemObject($type, $id, $escape = true)
	{
		static $cache;

		// Cache our data so we don't query the db often
		if (!is_array($cache))
		{
			$cache = array();
			$db    = Factory::getDbo();

			// Load departments
			$query = $db->getQuery(true);
			$query->select($db->qn('id'))
				->select($db->qn('name'))
				->from($db->qn('#__rsticketspro_departments'));
			$cache['department'] = $db->setQuery($query)->loadObjectList('id');

			// Load statuses
			$query = $db->getQuery(true);
			$query->select($db->qn('id'))
				->select($db->qn('name'))
				->from($db->qn('#__rsticketspro_statuses'));
			$cache['status'] = $db->setQuery($query)->loadObjectList('id');

			// Load priorities
			$query = $db->getQuery(true);
			$query->select($db->qn('id'))
				->select($db->qn('name'))
				->from($db->qn('#__rsticketspro_priorities'));
			$cache['priority'] = $db->setQuery($query)->loadObjectList('id');

			$cache['user'] = array(
				// Get the 'Unassigned' user.
				0 => (object) array(
					'name' => Text::_('RST_UNASSIGNED')
				)
			);
		}

		if ($type == 'user' && !isset($cache[$type][$id]))
		{
			$userField = RSTicketsProHelper::getConfig('show_user_info');
			$db        = Factory::getDbo();
			$query     = $db->getQuery(true);

			$query->select($db->qn('username'))
				->select($db->qn('name'))
				->select($db->qn('email'))
				->from($db->qn('#__users'))
				->where($db->qn('id') . '=' . $db->q($id));

			if ($user = $db->setQuery($query)->loadObject())
			{
				$cache[$type][$id] = (object) array('name' => $user->{$userField});
			}
		}

		// Found a match
		if (isset($cache[$type][$id]) && isset($cache[$type][$id]->name))
		{
			if ($escape)
			{
				return htmlentities($cache[$type][$id]->name, ENT_COMPAT, 'utf-8');
			}
			else
			{
				return $cache[$type][$id]->name;
			}
		}

		// Failsafe
		return Text::_('RST_SYSMESSAGE_MISSING');
	}


	public static function checkIfEmailExists($email)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('id')
			->from($db->qn('#__users'))
			->where($db->qn('email') . ' = ' . $db->q($email));
		$db->setQuery($query);

		return $db->loadResult();
	}

	protected static function parseSystemMessage($data)
	{
		$message = '';
		$data    = unserialize($data);

		switch ($data['type'])
		{
			case 'department':
				$from    = self::getSystemObject($data['type'], $data['from']);
				$to      = self::getSystemObject($data['type'], $data['to']);
				$user    = self::getSystemObject('user', $data['user_id']);
				$message = Text::sprintf('RST_SYSMESSAGE_TICKET_DEPARTMENT_CHANGE', Text::_($from), Text::_($to), $user);
				break;

			case 'priority':
				$from    = self::getSystemObject($data['type'], $data['from']);
				$to      = self::getSystemObject($data['type'], $data['to']);
				$user    = self::getSystemObject('user', $data['user_id']);
				$message = Text::sprintf('RST_SYSMESSAGE_TICKET_PRIORITY_CHANGE', Text::_($from), Text::_($to), $user);
				break;

			case 'status':
				$from    = self::getSystemObject($data['type'], $data['from']);
				$to      = self::getSystemObject($data['type'], $data['to']);
				$user    = self::getSystemObject('user', $data['user_id']);
				$message = Text::sprintf('RST_SYSMESSAGE_TICKET_STATUS_CHANGE', Text::_($from), Text::_($to), $user);
				break;

			case 'staff':
				$from    = self::getSystemObject('user', $data['from']);
				$to      = self::getSystemObject('user', $data['to']);
				$user    = self::getSystemObject('user', $data['user_id']);
				$message = Text::sprintf('RST_SYSMESSAGE_TICKET_STAFF_CHANGE', $from, $to, $user);
				break;

			case 'autoclose':
				$message = Text::sprintf('RST_SYSMESSAGE_TICKET_AUTO_CLOSE', $data['days']);
				break;
		}

		return $message;
	}

	public static function addHistory($ticket_id, $type = 'view', $user_id = null, $ip = null)
	{
		if (is_null($user_id))
		{
			$user_id = Factory::getUser()->id;
		}
		if (is_null($ip))
		{
			$ip = \Joomla\Utilities\IpHelper::getIp();
		}

		if (!RSTicketsProHelper::getConfig('store_ip'))
        {
            $ip = '0.0.0.0';
        }

		$table = Table::getInstance('Tickethistory', 'RsticketsproTable');
		$table->save(array(
			'ticket_id' => $ticket_id,
			'user_id'   => $user_id,
			'ip'        => $ip,
			'date'      => Factory::getDate()->toSql(),
			'type'      => $type,
		));
	}

	public static function getDepartment($department_id, $reload = false)
	{
		static $cache = array();

		if (!isset($cache[$department_id]) || $reload)
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_rsticketspro/tables');

			$cache[$department_id] = Table::getInstance('Departments', 'RsticketsproTable');
			$cache[$department_id]->load($department_id);
		}

		return $cache[$department_id];
	}

	// deprecated
	public static function getAdminGroups()
	{
		require_once dirname(__FILE__) . '/users.php';

		return RSTicketsProUsersHelper::getAdminGroups();
	}

	// deprecated
	public static function getAdminUsers()
	{
		require_once dirname(__FILE__) . '/users.php';

		return RSTicketsProUsersHelper::getAdminUsers();
	}

	public static function mailRoute($url, $xhtml = true, $Itemid = 0)
	{
		$url .= $Itemid ? '&Itemid=' . $Itemid : '';
		$path = JUri::root(false) . $url;

		return self::route($path, $xhtml, $Itemid, false);
	}

	public static function route($url, $xhtml = true, $Itemid = '', $absolute = false)
	{
		if (!$Itemid && RSTicketsProHelper::getConfig('calculate_itemids'))
		{
			$Itemid = RSTicketsProHelper::_findRoute($url);
		}

		if (strpos($url, 'Itemid=') === false)
		{
			if (!$Itemid)
			{
				$Itemid = Factory::getApplication()->input->getInt('Itemid', 0);
				if ($Itemid)
				{
					$Itemid = 'Itemid=' . $Itemid;
				}
			}
			elseif ($Itemid)
			{
				$Itemid = 'Itemid=' . (int) $Itemid;
			}

			if ($Itemid)
			{
				$url .= (strpos($url, '?') === false) ? '?' . $Itemid : '&' . $Itemid;
			}
		}

		$converted_url = JRoute::_($url, $xhtml);
		if ($absolute)
		{
			$uri           = JUri::getInstance();
			$converted_url = $uri->toString(array('scheme', 'host', 'port')) . $converted_url;
		}

		return $converted_url;
	}

	public static function _findRoute($url)
	{
		$app = Factory::getApplication();
		if ($app->isClient('administrator'))
		{
			return '';
		}
		static $cache;

		if (!is_array($cache))
		{
			$cache = array();
		}

		$hash = md5($url);
		if (isset($cache[$hash]))
		{
			return $cache[$hash];
		}

		$query = array();
		$url   = str_replace('index.php?', '', $url);
		$parts = explode('&', $url);
		foreach ($parts as $part)
		{
			$part            = explode('=', $part, 2);
			$query[$part[0]] = @$part[1];
		}

		if (!isset($query['option']))
		{
			return '';
		}

		if (isset($query['view']) && $query['view'] == 'ticket')
		{
			$query           = array();
			$query['option'] = 'com_rsticketspro';
			$query['view']   = 'tickets';
		}

		if ($app->input->getCmd('option') == 'com_rsticketspro')
		{
			$count = 0;
			foreach ($query as $var => $value)
			{
				if ($app->input->getCmd($var) && $app->input->getCmd($var) == $value)
				{
					$count++;
				}
			}
			if ($count == count($query) && $app->input->getInt('Itemid'))
			{
				return $app->input->getInt('Itemid');
			}
		}

		$menus     = $app->getMenu('site');
		$component = JComponentHelper::getComponent($query['option']);
		$items     = $menus->getItems('component_id', $component->id);

		if ($items)
		{
			foreach ($items as $item)
			{

				if (!isset($item->query))
				{
					continue;
				}

				$count = 0;
				foreach ($item->query as $var => $value)
				{
					if (isset($query[$var]) && $value == $query[$var])
					{
						$count++;
					}
				}

				if ($count == count($query))
				{
					$cache[$hash] = $item->id;
				}
			}
		}

		if (isset($cache[$hash]))
		{
			return $cache[$hash];
		}

		return '';
	}

	public static function getReplyAbove()
	{
		$use_editor = RSTicketsProHelper::getConfig('allow_rich_editor');
		$use_text   = RSTicketsProHelper::getConfig('use_reply_above');
		$text		= RSTicketsProHelper::getConfig('reply_above');
		if ($use_text)
		{
			if ($use_editor)
			{
				return '<p>----------' . $text . '----------</p>';
			}
			else
			{
				return '----------' . $text . '----------';
			}
		}
		
		return '';
	}

	public static function getPriorities($show_please_select = false)
	{
		$return = array();
		$db 	= Factory::getDbo();

		if ($show_please_select)
		{
			$return[] = JHtml::_('select.option', '', Text::_('RST_PLEASE_SELECT_PRIORITY'));
		}

		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__rsticketspro_priorities'))
			->where($db->qn('published') . ' = ' . $db->q(1))
			->order($db->qn('ordering') . ' asc');

		if ($results = $db->setQuery($query)->loadObjectList())
		{
			foreach ($results as $result)
			{
				$return[] = JHtml::_('select.option', $result->id, Text::_($result->name));
			}
		}

		return $return;
	}

	public static function getJSSubjects($subjects)
	{
		if (strpos($subjects, '<code>') !== false)
		{
			$subjects = eval($subjects);
		}

		if (!is_array($subjects)) {
			$values = str_replace(array("\r\n", "\r"), "\n", $subjects);
			$values = explode("\n", $values);
		} else {
			$values = $subjects;
		}

		$return   = array();
		$return[] = "'':'" . Text::_('RST_PLEASE_SELECT_SUBJECT', true) . "'";

		foreach ($values as $value) {
			if (!empty($value)) {
				$return[] = json_encode($value) . ':' . json_encode(Text::_($value));
			}
		}

		return $return;
	}

	public static function getStatuses()
	{
		$return = array();
		$db 	= Factory::getDbo();

		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__rsticketspro_statuses'))
			->where($db->qn('published') . ' = ' . $db->q(1))
			->order($db->qn('ordering') . ' asc');

		if ($results = $db->setQuery($query)->loadObjectList())
		{
			foreach ($results as $result)
			{
				$return[] = JHtml::_('select.option', $result->id, Text::_($result->name));
			}
		}

		return $return;
	}

	public static function getDepartments($show_please_select = false)
	{
		$return = array();
		$db 	= Factory::getDbo();

		if ($show_please_select)
		{
			$return[] = JHtml::_('select.option', '', Text::_('RST_PLEASE_SELECT_DEPARTMENT'));
		}

		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__rsticketspro_departments'))
			->where($db->qn('published') . ' = ' . $db->q(1))
			->order($db->qn('ordering') . ' asc');

		if ($results = $db->setQuery($query)->loadObjectList())
		{
			$force_departments = RSTicketsProHelper::getConfig('staff_force_departments');
			$is_staff          = RSTicketsProHelper::isStaff();
			$departments       = RSTicketsProHelper::getCurrentDepartments();

			foreach ($results as $result)
			{
				if ($is_staff && $force_departments && !in_array($result->id, $departments))
				{
					continue;
				}

				$return[] = JHtml::_('select.option', $result->id, Text::_($result->name));
			}
		}

		return $return;
	}

	public static function getStaff($show_please_select = false, $show_only_can_reply = false)
	{
		$db   	= Factory::getDbo();
		$what 	= RSTicketsProHelper::getConfig('show_user_info');
		$return = array();

		if ($show_please_select)
		{
			$return[] = JHtml::_('select.option', '', Text::_('RST_PLEASE_SELECT_STAFF'));
		}

		if ($show_only_can_reply)
		{
			$query = $db->getQuery(true)
				->select($db->qn('id'))
				->from($db->qn('#__rsticketspro_groups'))
				->where($db->qn('answer_ticket') . ' = ' . $db->q(1));

			$group_ids = $db->setQuery($query)->loadColumn();
		}

		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__rsticketspro_departments'))
			->where($db->qn('published') . ' = ' . $db->q(1))
			->order($db->qn('ordering') . ' asc');

		if ($departments = $db->setQuery($query)->loadObjectList())
		{
			foreach ($departments as $department)
			{
				$optgroup        = new stdClass();
				$optgroup->value = '<OPTGROUP>';
				$optgroup->text  = Text::_($department->name);
				$return[]        = $optgroup;

				$query = $db->getQuery(true)
					->select($db->qn('user_id'))
					->from($db->qn('#__rsticketspro_staff_to_department'))
					->where($db->qn('department_id') . ' = ' . $db->q($department->id));
				$users = $db->setQuery($query)->loadColumn();

				if ($show_only_can_reply)
				{
					if (empty($group_ids))
					{
						$users = array();
					}
					elseif ($users)
					{
						$query = $db->getQuery(true)
							->select($db->qn('user_id'))
							->from($db->qn('#__rsticketspro_staff'))
							->where($db->qn('group_id') . ' IN (' . implode(',', $db->q($group_ids)) . ')')
							->where($db->qn('user_id') . ' IN (' . implode(',', $db->q($users)) . ')');
						$users = $db->setQuery($query)->loadColumn();
					}
				}

				if ($users)
				{
					foreach ($users as $user_id)
					{
						$user     = Factory::getUser($user_id);
						$return[] = JHtml::_('select.option', $user->get('id'), $user->get($what));
					}
				}

				$optgroup        = new stdClass();
				$optgroup->value = '</OPTGROUP>';
				$optgroup->text  = '';
				$return[]        = $optgroup;
			}
		}

		return $return;
	}

	public static function getAvatar($user_id)
	{
		static $avatar_cache = array();
		if (!isset($avatar_cache[$user_id]))
		{
			$avatars = RSTicketsProHelper::getConfig('avatars');
			$icon    = RSTicketsProHelper::isStaff($user_id) ? 'staff' : 'user';
			$src     = JHtml::_('image', 'com_rsticketspro/' . $icon . '-icon.png', '', array(), true, 1);

			switch ($avatars)
			{
				// Gravatar
				case 'gravatar':
					$user  = Factory::getUser($user_id);
					$email = md5(strtolower(trim($user->get('email'))));
					$length = strlen(JUri::root(true).'/');
					$site_url = substr(JUri::root(), 0 , -$length);

					$src = 'https://www.gravatar.com/avatar/' . $email . '?d=' . urlencode($site_url.JHtml::_('image', 'com_rsticketspro/' . $icon . '.png', '', array(), true, 1));
					break;

				// Community Builder
				case 'comprofiler':
					require_once JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php';

					global $_CB_framework;
					$cbUser = CBuser::getInstance($user_id);

					$avatar = $cbUser->getField('avatar', null, 'php', 'none', 'list');
					if (isset($avatar['avatar']))
					{
						$src = $avatar['avatar'];
					}
					else
					{
						$src = false;
					}
					break;

				// JomSocial
				case 'community':
					$file = JPATH_SITE . '/components/com_community/libraries/core.php';
					if (file_exists($file))
					{
						require_once $file;
						$user = CFactory::getUser($user_id);
						$src  = $user->getThumbAvatar();
					}
					break;

				// Kunena
				case 'kunena':
					$file = JPATH_ADMINISTRATOR . '/components/com_kunena/libraries/user/user.php';
					if (file_exists($file))
					{
						require_once $file;
						$user = KunenaUser::getInstance($user_id);
						$src  = $user->getAvatarURL();
					}
					elseif (file_exists(JPATH_LIBRARIES . '/kunena/factory.php') || class_exists('KunenaFactory'))
					{
						require_once JPATH_LIBRARIES . '/kunena/factory.php';
						$profile = KunenaFactory::getUser($user_id);
						$src     = $profile->getAvatarURL('list');
					}
					break;
			}

			$avatar_cache[$user_id] = $src;
		}

		return $avatar_cache[$user_id];
	}

	public static function explode($string)
	{
		$string = str_replace(array("\r\n", "\r"), "\n", $string);

		return explode("\n", $string);
	}

	public static function showCustomField($field, $selected = array(), $editable = true, $department_id = 0)
	{
		require_once dirname(__FILE__) . '/fields.php';

		return RSTicketsProFieldHelper::showCustomField($field, $selected, $editable, $department_id);
	}
	
	public static function canDeleteTimeTracking($user_id =  null, $col = 'can_delete_time_history')
	{
		if (!$user_id)
		{
			$user = Factory::getUser();
			if ($user->get('guest'))
			{
				return false;
			}
		}

		if (RSTicketsProHelper::isAdmin($user_id))
		{
			return true;
		}

		$user_id = (int) $user_id;
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('id'))
			->from($db->qn('#__rsticketspro_staff'))
			->where($db->qn('user_id'). '= '. $db->q($user_id))
			->where($db->qn($col). '= '. $db->q(1));

		$db->setQuery($query);
		if ($db->loadResult())
		{
			return true;
		}

		return false;
	}

	public static function isStaff($user_id = null)
	{
		if (!$user_id)
		{
			if (Factory::getUser()->get('guest'))
			{
				return false;
			}

			return Factory::getSession()->get('rsticketspro.is_staff', false);
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('id'))
			->from($db->qn('#__rsticketspro_staff'))
			->where($db->qn('user_id') . ' = ' . $db->q($user_id));
		if ($db->setQuery($query)->loadResult())
		{
			return true;
		}

		if (RSTicketsProHelper::isAdmin($user_id))
		{
			return true;
		}

		return false;
	}

	public static function getCurrentPermissions()
	{
		if (Factory::getUser()->get('guest'))
		{
			return array();
		}

		return Factory::getSession()->get('rsticketspro.permissions', array());
	}

	public static function getCurrentDepartments()
	{
		if (Factory::getUser()->get('guest'))
		{
			return array();
		}

		return Factory::getSession()->get('rsticketspro.departments', array());
	}

	public static function getPermissions($user_id)
	{
		$return = array();
		$user 	= Factory::getUser($user_id);
		$db 	= Factory::getDbo();

		$query = $db->getQuery(true)
			->select($db->qn('group_id'))
			->from($db->qn('#__rsticketspro_staff'))
			->where($db->qn('user_id') . ' = ' . $db->q($user->id));

		if ($group_id = $db->setQuery($query)->loadResult())
		{
			$query = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__rsticketspro_groups'))
				->where($db->qn('id') . ' = ' . $db->q($group_id));
			$return = $db->setQuery($query)->loadObject();
		}
		elseif (RSTicketsProHelper::isAdmin($user_id))
		{
			$return = Table::getInstance('Groups', 'RsticketsproTable');
		}

		return $return;
	}

	// $user_id = if left null, the current logged in user's signature is retrieved
	// $raw 	= if set to true, it will grab the signature as it appears in the database,
	//			  otherwise it will strip the tags if no rich editor is set
	public static function getSignature($user_id = null, $raw = false)
	{
		$user  = $user_id ? Factory::getUser($user_id) : Factory::getUser();
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->qn('signature'))
			->from($db->qn('#__rsticketspro_staff'))
			->where($db->qn('user_id') . '=' . $db->q($user->id));
		$db->setQuery($query);
		$signature = (string) $db->loadResult();

		if (!$raw)
		{
			$allowEditor = self::getConfig('allow_rich_editor');
			if (!$allowEditor)
			{
				$signature = strip_tags($signature);
			}
		}

		return $signature;
	}

	public static function isAdmin($user_id = null)
	{
		$user = $user_id ? Factory::getUser($user_id) : Factory::getUser();
		$admin_groups = RSTicketsProHelper::getAdminGroups();
		$user_groups = $user->getAuthorisedGroups();
		foreach ($user_groups as $user_group_id)
		{
			if (in_array($user_group_id, $admin_groups))
			{
				return true;
			}
		}

		return false;
	}

	public static function getConsecutiveReplies($ticket_id)
	{
		$replies = 0;
		$db      = Factory::getDbo();
		$query   = $db->getQuery(true)
			->select($db->qn('user_id'))
			->from($db->qn('#__rsticketspro_ticket_messages'))
			->where($db->qn('ticket_id') . '=' . $db->q($ticket_id))
			->where($db->qn('user_id') . '!=' . $db->q('-1'))
			->where($db->qn('submitted_by_staff') . '=' . $db->q('0'))
			->order($db->qn('date') . ' ' . $db->escape('desc'));

		if ($users = $db->setQuery($query)->loadColumn())
		{
			foreach ($users as $user_id)
			{
				if (RSTicketsProHelper::isStaff($user_id))
				{
					break;
				}

				$replies++;
			}
		}

		return $replies;
	}

	public static function getExtension($filename)
	{
		return JFile::getExt($filename);
	}

	public static function isAllowedExtension($ext, $ext_array)
	{
		if (!is_array($ext_array))
		{
			return true;
		}
		if (count($ext_array) == 0)
		{
			return true;
		}
		if (count($ext_array) == 1 && trim($ext_array[0]) == '')
		{
			return true;
		}
		if (in_array('*', $ext_array))
		{
			return true;
		}

		// convert everything to lowercase
		$ext = strtolower($ext);
		array_walk($ext_array, array('RSTicketsProHelper', 'arraytolower'));

		return in_array($ext, $ext_array);
	}

	public static function arraytolower(&$value, $key)
	{
		$value = strtolower($value);
	}

	// deprecated
	public static function getEmail($type)
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/emails.php';

		return RSTicketsProEmailsHelper::getEmail($type);
	}

	public static function getFooter()
	{
		$footer = '<p style="text-align: center"><a href="https://www.rsjoomla.com/joomla-components/joomla-help-desk.html" title="Joomla! Help Desk Ticketing System" target="_blank">Joomla! Help Desk Ticketing System</a> by <a href="https://www.rsjoomla.com" target="_blank" title="Joomla! Extensions">RSJoomla!</a></p>';

		return $footer;
	}

	public static function shorten($string, $max = 255, $more = '...')
	{
		$string_tmp = '';
		$exp        = explode(' ', $string);
		for ($i = 0; $i < count($exp); $i++)
		{
			if (strlen($string_tmp) + strlen($exp[$i]) < $max)
			{
				$string_tmp .= $exp[$i] . ' ';
			}
			else
			{
				break;
			}
		}
		$string = substr($string_tmp, 0, -1) . (strlen($string) > strlen($string_tmp) ? $more : '');

		return RSTicketsProHelper::closeTags($string);
	}

	public static function closeTags($html)
	{
		preg_match_all('#<([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
		$openedtags = $result[1];
		preg_match_all('#</([a-z]+)>#iU', $html, $result);
		$closedtags = $result[1];
		$len_opened = count($openedtags);
		if (count($closedtags) == $len_opened)
		{
			return $html;
		}

		$openedtags = array_reverse($openedtags);
		for ($i = 0; $i < $len_opened; $i++)
		{
			if (!in_array($openedtags[$i], $closedtags))
			{
				$html .= '</' . $openedtags[$i] . '>';
			}
			else
			{
				unset($closedtags[array_search($openedtags[$i], $closedtags)]);
			}
		}

		return $html;
	}

	public static function sendMail($from, $fromname, $recipient, $subject, $body, $mode = 0, $attachments = null, $cc = null, $bcc = null, $replyTo = null, $replyToName = null)
	{
		try
		{
			if (!is_array($recipient))
			{
				$recipient = array($recipient);
			}

			foreach ($recipient as $i => $r)
			{
				$r = trim($r);
				if (!JMailHelper::isEmailAddress($r))
				{
					unset($recipient[$i]);
				}
			}

			if (empty($recipient) || !count($recipient))
			{
				return false;
			}

			// Get a JMail instance
			$mail = Factory::getMailer();

			$mail->ClearReplyTos();
			$mail->setSender(array($from, $fromname));
			$mail->setSubject($subject);
			$mail->setBody($body);

			// Are we sending the email as HTML?
			if ($mode)
			{
				$mail->IsHTML(true);
				$mail->AltBody = strip_tags($body);
			}

			$mail->addRecipient($recipient);
			$mail->ClearReplyTos();
			$mail->addReplyTo($from, $fromname);

			if (!empty($cc))
			{
				$cc = str_replace(array("\r\n", "\r"), "\n", $cc);
				$cc = explode("\n", $cc);
				foreach ($cc as $i => $r)
				{
					$r = trim($r);
					if (!JMailHelper::isEmailAddress($r))
					{
						continue;
					}

					$mail->addCC($r);
				}
			}

			if (!empty($bcc))
			{
				$bcc = str_replace(array("\r\n", "\r"), "\n", $bcc);
				$bcc = explode("\n", $bcc);
				foreach ($bcc as $i => $r)
				{
					$r = trim($r);
					if (!JMailHelper::isEmailAddress($r))
					{
						continue;
					}

					$mail->addBCC($r);
				}
			}

			if (is_array($attachments) && count($attachments))
			{
				foreach ($attachments as $attachment)
				{
					$mail->AddStringAttachment(file_get_contents($attachment->path), $attachment->filename);
				}
			}

			// Take care of reply email addresses
			$hasReplyTo = false;
			if (is_array($replyTo))
			{
				$numReplyTo = count($replyTo);
				for ($i = 0; $i < $numReplyTo; $i++)
				{
					if ($mail->addReplyTo($replyTo[$i], $replyToName[$i]))
					{
						$hasReplyTo = true;
					}
				}
			}
			elseif ($replyTo)
			{
				if ($mail->addReplyTo($replyTo, $replyToName))
				{
					$hasReplyTo = true;
				}
			}

			// Add sender to replyTo only if no replyTo received
			$mail->setSender(array($from, $fromname, !$hasReplyTo));

			return $mail->Send();
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
			return false;
		}
	}

	public static function htmlEscape($val)
	{
		return htmlentities($val, ENT_COMPAT, 'UTF-8');
	}

	public static function sef($id)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		if (Factory::getApplication()->isClient('administrator'))
		{
			return $id;
		}
		else
		{
			$query->select($db->qn('subject'))
				->from($db->qn('#__rsticketspro_tickets'))
				->where($db->qn('id') . ' = ' . (int) $id);
			$db->setQuery($query);
			$name = $db->loadResult();

			return $id . ':' . JFilterOutput::stringURLSafe($name);
		}
	}

	public static function showMessage($message)
	{
		if ($message->html == 1)
		{
			// message was saved in HTML format
			return RSTicketsProHelper::cleanHTML($message);
		}
		elseif ($message->html == 2)
		{
			// message was saved before the REV 9 update
			return '<p>' . nl2br($message->message) . '</p>';
		}
		else
		{
			// message was saved in TEXT format

			// keep tabs and double spaces in proper format
			if ($message->user_id == '-1')
			{
				$message->message = RSTicketsProHelper::parseSystemMessage($message->message);
			}
			else
			{
				$message->message = RSTicketsProHelper::htmlEscape($message->message);
				$message->message = str_replace('  ', '&nbsp;&nbsp;', $message->message);
				$message->message = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $message->message);
			}

			return '<p>' . nl2br($message->message) . '</p>';
		}
	}

	public static function filterText($text, $user = null)
	{
		// Punyencoding utf8 email addresses
		$text = JFilterInput::getInstance()->emailToPunycode($text);

		// Filter settings
		$config = JComponentHelper::getParams('com_config');
		if ($user && !empty($user->id))
		{
			$userGroups = JAccess::getGroupsByUser($user->get('id'));
		}
		else
		{
			static $filter;
			if (!$filter)
			{
				$filter = JFilterInput::getInstance(
					array('a', 'abbr', 'address', 'b', 'br', 'caption', 'center', 'dd', 'dl', 'dt', 'del', 'em', 'font', 'hr', 'i', 'img', 'ins', 'ul', 'li', 'mark', 'ol', 'p', 'span', 'small', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tr', 'th', 'thead', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'),
					array('size', 'src', 'href', 'title', 'rel', 'color', 'face', 'colspan', 'rowspan', 'align', 'bgcolor', 'border', 'cellpadding', 'cellspacing', 'valign', 'alt')
				);
			}
			
			return $filter->clean($text, 'html');
		}

		$filters = $config->get('filters');

		$blackListTags       = array();
		$blackListAttributes = array();

		$customListTags       = array();
		$customListAttributes = array();

		$whiteListTags       = array();
		$whiteListAttributes = array();

		$whiteList  = false;
		$blackList  = false;
		$customList = false;
		$unfiltered = false;

		// Cycle through each of the user groups the user is in.
		// Remember they are included in the Public group as well.
		foreach ($userGroups as $groupId)
		{
			// May have added a group by not saved the filters.
			if (!isset($filters->$groupId))
			{
				continue;
			}

			// Each group the user is in could have different filtering properties.
			$filterData = $filters->$groupId;
			$filterType = strtoupper($filterData->filter_type);

			if ($filterType == 'NH')
			{
				// Maximum HTML filtering.
			}
			elseif ($filterType == 'NONE')
			{
				// No HTML filtering.
				$unfiltered = true;
			}
			else
			{
				// Blacklist or whitelist.
				// Preprocess the tags and attributes.
				$tags           = explode(',', $filterData->filter_tags);
				$attributes     = explode(',', $filterData->filter_attributes);
				$tempTags       = array();
				$tempAttributes = array();

				foreach ($tags as $tag)
				{
					$tag = trim($tag);

					if ($tag)
					{
						$tempTags[] = $tag;
					}
				}

				foreach ($attributes as $attribute)
				{
					$attribute = trim($attribute);

					if ($attribute)
					{
						$tempAttributes[] = $attribute;
					}
				}

				// Collect the blacklist or whitelist tags and attributes.
				// Each list is cummulative.
				if ($filterType == 'BL')
				{
					$blackList           = true;
					$blackListTags       = array_merge($blackListTags, $tempTags);
					$blackListAttributes = array_merge($blackListAttributes, $tempAttributes);
				}
				elseif ($filterType == 'CBL')
				{
					// Only set to true if Tags or Attributes were added
					if ($tempTags || $tempAttributes)
					{
						$customList           = true;
						$customListTags       = array_merge($customListTags, $tempTags);
						$customListAttributes = array_merge($customListAttributes, $tempAttributes);
					}
				}
				elseif ($filterType == 'WL')
				{
					$whiteList           = true;
					$whiteListTags       = array_merge($whiteListTags, $tempTags);
					$whiteListAttributes = array_merge($whiteListAttributes, $tempAttributes);
				}
			}
		}

		// Remove duplicates before processing (because the blacklist uses both sets of arrays).
		$blackListTags        = array_unique($blackListTags);
		$blackListAttributes  = array_unique($blackListAttributes);
		$customListTags       = array_unique($customListTags);
		$customListAttributes = array_unique($customListAttributes);
		$whiteListTags        = array_unique($whiteListTags);
		$whiteListAttributes  = array_unique($whiteListAttributes);

		// Unfiltered assumes first priority.
		if ($unfiltered)
		{
			// Dont apply filtering.
		}
		else
		{
			// Custom blacklist precedes Default blacklist
			if ($customList)
			{
				$filter = JFilterInput::getInstance(array(), array(), 1, 1);

				// Override filter's default blacklist tags and attributes
				if ($customListTags)
				{
					$filter->tagBlacklist = $customListTags;
				}

				if ($customListAttributes)
				{
					$filter->attrBlacklist = $customListAttributes;
				}
			}
			// Blacklists take second precedence.
			elseif ($blackList)
			{
				// Remove the whitelisted tags and attributes from the black-list.
				$blackListTags       = array_diff($blackListTags, $whiteListTags);
				$blackListAttributes = array_diff($blackListAttributes, $whiteListAttributes);

				$filter = JFilterInput::getInstance($blackListTags, $blackListAttributes, 1, 1);

				// Remove whitelisted tags from filter's default blacklist
				if ($whiteListTags)
				{
					$filter->tagBlacklist = array_diff($filter->tagBlacklist, $whiteListTags);
				}
				// Remove whitelisted attributes from filter's default blacklist
				if ($whiteListAttributes)
				{
					$filter->attrBlacklist = array_diff($filter->attrBlacklist, $whiteListAttributes);
				}
			}
			// Whitelists take third precedence.
			elseif ($whiteList)
			{
				// Turn off XSS auto clean
				$filter = JFilterInput::getInstance($whiteListTags, $whiteListAttributes, 0, 0, 0);
			}
			// No HTML takes last place.
			else
			{
				$filter = JFilterInput::getInstance();
			}

			$text = $filter->clean($text, 'RAW'); // DPE HACK To show the Html tags
		}

		return $text;
	}

	public static function cleanHTML($message)
	{
		$html = $message->message;

		// Don't think these should be here regardless of filtering settings
		$html = preg_replace('/<\/?(form|button|script|style|iframe|embed).*?>/is', '', $html);

		$html = static::filterText($html, Factory::getUser($message->user_id));

		if (class_exists('DOMDocument'))
		{
			$doc    = new DOMDocument();
			$errors = libxml_use_internal_errors(true);
			$doc->loadHTML('<?xml version="1.0" encoding="UTF-8"?><html_tags>' . $html . '</html_tags>');
			$doc->encoding = 'UTF-8';
			libxml_clear_errors();
			$html = substr($doc->saveHTML($doc->getElementsByTagName('html_tags')->item(0)), strlen('<html_tags>'), -strlen('</html_tags>'));

			libxml_use_internal_errors($errors);
		}

		return $html;
	}

	public static function trigger($event, $params)
	{
		static $app;

		if (is_null($app))
		{
			$app = Factory::getApplication();

			JPluginHelper::importPlugin('rsticketspro');
		}

		// Prefix our events with 'onRsticketspro'
		$event = substr_replace($event, 'onRsticketspro', 0, 2);
		$app->triggerEvent($event, $params);
	}

	public static function tooltipClass()
	{
		static $loaded = false;

		if (!$loaded)
		{
			$loaded = true;

			static::tooltipLoad();
		}

		return 'hasPopover';
	}

	// Prepare the tooltip text
	public static function tooltipText($title, $content = '')
	{
		if ($content === '')
		{
			if (strpos($title, '::') !== false)
			{
				list($title, $content) = explode('::', $title);
			}
			else
			{
				$content = $title;
			}
		}
		// title="
		$result = htmlspecialchars(trim($title, ':')) . '"';

		// "
		if (version_compare(JVERSION, '4.0', '>='))
		{
			if (Factory::getLanguage()->isRtl())
			{
				$result .= ' data-bs-placement="left" ';
			}

			$result .= ' data-bs-content="' . htmlspecialchars($content);
		}
		else
		{
			if (Factory::getLanguage()->isRtl())
			{
				$result .= ' data-placement="left" ';
			}

			$result .= ' data-content="' . htmlspecialchars($content);
		}

		return $result;
	}

	// Load tooltip
	public static function tooltipLoad()
	{
		JHtml::_('bootstrap.popover', '.hasPopover', array('trigger' => 'hover focus'));
	}

	public static function renderModal($id, $args)
	{
		if (RSTicketsProHelper::getConfig('use_magnific_popup'))
		{
			return static::renderMagnificPopup($id, $args);
		}
		else
		{
			return JHtml::_('bootstrap.renderModal', $id, $args);
		}
	}

	public static function openModal($id)
	{
		if (RSTicketsProHelper::getConfig('use_magnific_popup'))
		{
			return "RSTicketsPro.openMagnificModal(event, '#{$id}');";
		}
		else
		{
			return "jQuery('#{$id}').modal('show');";
		}
	}

	public static function renderMagnificPopup($id = null, $args = array())
	{
		if (is_null($id))
		{
			return '';
		}

		if (!isset($args['title']) || strlen($args['title']) == 0)
		{
			return '';
		}

		if (!isset($args['url']) || strlen($args['url']) == 0)
		{
			return '';
		}

		static $loadFiles;

		if (is_null($loadFiles))
		{
			JHtml::_('jquery.framework');
			JHtml::_('script', 'com_rsticketspro/jquery.magnific-popup.min.js', array('relative' => true, 'version' => 'auto'));
			JHtml::_('stylesheet', 'com_rsticketspro/magnific-popup.css', array('relative' => true, 'version' => 'auto'));

			Text::script('RST_JQUERY_NOT_FOUND');

			$loadFiles = true;
		}

		if (!isset($args['height']))
		{
			$args['height'] = 400;
		}

		$modal_html = '<div id="' . htmlspecialchars($id, ENT_COMPAT, 'utf-8') . '" class="rst-magnific-popup mfp-hide">
				<div class="magnific-header">
					<h3 id="rsdir-owner-reply-header">' . htmlspecialchars($args['title'], ENT_COMPAT, 'utf-8') . '</h3>
				</div>
				<div class="magnific-popup-body">
					<iframe style="height: '.$args['height'].'px;" src="' . htmlspecialchars($args['url'], ENT_COMPAT, 'utf-8') . '"></iframe>
				</div>
				<button title="'.Text::_('RST_CLOSE').'" type="button" class="mfp-close">&times;</button>
			</div>';

		return $modal_html;
	}

	public static function anonymise($id, $anonymiseJoomlaData = null)
    {
        $db         = Factory::getDbo();
        $query      = $db->getQuery(true);
        $subquery   = $db->getQuery(true);

        $fake_text      = Text::_('COM_RSTICKETSPRO_DATA_HAS_BEEN_ANONYMISED');
        $fake_ip        = '0.0.0.0';

		if ($anonymiseJoomlaData === null)
		{
			$anonymiseJoomlaData = RSTicketsProHelper::getConfig('anonymise_joomla_data');
		}

		if ($anonymiseJoomlaData) {
            // Let's create a fake email & fake username
            $fake_email     = JUserHelper::genRandomPassword(mt_rand(10, 16)) . '@' . JUserHelper::genRandomPassword(mt_rand(10, 16));
            $fake_username  = JUserHelper::genRandomPassword(mt_rand(10, 16));

            // Make sure this email is free
            $query->clear()
                ->select($db->qn('id'))
                ->from($db->qn('#__users'))
                ->where($db->qn('email') . ' = ' . $db->q($fake_email));
            while ($db->setQuery($query)->loadResult())
            {
                $fake_email .= JUserHelper::genRandomPassword(mt_rand(1, 2));
                $query->clear()
                    ->select($db->qn('id'))
                    ->from($db->qn('#__users'))
                    ->where($db->qn('email') . ' = ' . $db->q($fake_email));
            }

            // Make sure this username is free
            $query->clear()
                ->select($db->qn('id'))
                ->from($db->qn('#__users'))
                ->where($db->qn('username') . ' = ' . $db->q($fake_username));
            while ($db->setQuery($query)->loadResult())
            {
                $fake_username .= JUserHelper::genRandomPassword(mt_rand(1, 2));
                $query->clear()
                    ->select($db->qn('id'))
                    ->from($db->qn('#__users'))
                    ->where($db->qn('username') . ' = ' . $db->q($fake_username));
            }

            // #__users data
            $query->clear()
                ->update($db->qn('#__users'))
                ->set($db->qn('name') . ' = ' . $db->q($fake_username))
                ->set($db->qn('username') . ' = ' . $db->q($fake_username))
                ->set($db->qn('email') . ' = ' . $db->q($fake_email))
                ->set($db->qn('password') . ' = ' . $db->q(JUserHelper::hashPassword(JUserHelper::genRandomPassword(20))))
                ->where($db->qn('id') . ' = ' . $db->q($id));
            $db->setQuery($query)->execute();
        }

        // Remove custom searches
        $query->clear()
            ->delete($db->qn('#__rsticketspro_searches'))
            ->where($db->qn('user_id') . ' = ' . $db->q($id));
        $db->setQuery($query)->execute();

        // Remove staff to department assignments
        $query->clear()
            ->delete($db->qn('#__rsticketspro_staff_to_department'))
            ->where($db->qn('user_id') . ' = ' . $db->q($id));
        $db->setQuery($query)->execute();

        // Anonymise tickets
        $query->clear()
            ->update($db->qn('#__rsticketspro_tickets'))
            ->set($db->qn('subject') . ' = ' . $db->q($fake_text))
            ->set($db->qn('agent') . ' = ' . $db->q(''))
            ->set($db->qn('ip') . ' = ' . $db->q($fake_ip))
            ->where('(' . $db->qn('customer_id') . ' = ' . $db->q($id) . ') OR (' .  $db->qn('staff_id') . ' = ' . $db->q($id) . ')');
        $db->setQuery($query)->execute();

        // Anonymise ticket messages
        $query->clear()
            ->update($db->qn('#__rsticketspro_ticket_messages'))
            ->set($db->qn('message') . ' = ' . $db->q($fake_text))
            ->where($db->qn('user_id') . ' = ' . $db->q($id));
        $db->setQuery($query)->execute();

        // Remove ticket files
        $subquery->clear()
            ->select($db->qn('id'))
            ->from($db->qn('#__rsticketspro_ticket_messages'))
            ->where($db->qn('user_id') . ' = ' . $db->q($id));
        $query->clear()
            ->select($db->qn('id'))
            ->select($db->qn('ticket_message_id'))
            ->from($db->qn('#__rsticketspro_ticket_files'))
            ->where($db->qn('ticket_message_id') . ' IN (' . (string) $subquery . ')');
        if ($files = $db->setQuery($query)->loadObjectList())
        {
            jimport('joomla.filesystem.file');
            foreach ($files as $file)
            {
                $hash = md5($file->id . ' ' . $file->ticket_message_id);
                JFile::delete(RST_UPLOAD_FOLDER . '/' . $hash);
            }
        }

        // Anonymise ticket custom fields
        $subquery->clear()
            ->select($db->qn('id'))
            ->from($db->qn('#__rsticketspro_tickets'))
            ->where('(' . $db->qn('customer_id') . ' = ' . $db->q($id) . ') OR (' .  $db->qn('staff_id') . ' = ' . $db->q($id) . ')');
        $query->clear()
            ->update($db->qn('#__rsticketspro_custom_fields_values'))
            ->set($db->qn('value') . ' = ' . $db->q($fake_text))
            ->where($db->qn('ticket_id') . ' IN (' . (string) $subquery . ')');
        $db->setQuery($query)->execute();

        // Anonymise ticket history
        $query->clear()
            ->update($db->qn('#__rsticketspro_ticket_history'))
            ->set($db->qn('ip') . ' = ' . $db->q($fake_ip))
            ->where($db->qn('user_id') . ' = ' . $db->q($id));
        $db->setQuery($query)->execute();

        // Anonymise ticket notes
        $query->clear()
            ->update($db->qn('#__rsticketspro_ticket_notes'))
            ->set($db->qn('text') . ' = ' . $db->q($fake_text))
            ->where($db->qn('user_id') . ' = ' . $db->q($id));
        $db->setQuery($query)->execute();
    }

	public static function getAlternativeEmail($user_id =  null) {
		if (empty($user_id)) {
			return '';
		}

		$db = Factory::getDbo();

		$query = $db->getQuery(true);
		$query->select('alternative_email')
			->from($db->qn('#__rsticketspro_tickets'))
			->where($db->qn('customer_id') . '=' . $db->q($user_id))
			->order($db->qn('date') . ' ' . $db->escape('desc'));
		$db->setQuery($query, 0, 1);

		return (string) $db->loadResult();
	}

	public static function showTotal($duration, $split = false)
	{
		$return = '';
		$unit = RSTicketsProHelper::getConfig('time_spent_unit');

		if (RSTicketsProHelper::getConfig('time_spent_type') === 'tracking')
		{
			if ($split)
			{
				$split_data = explode('.', $duration);
				$total_minutes = (int)$split_data[0] * 60;
				$total_minutes += (int)$split_data[1];

				$duration = $total_minutes * 60;
			}

			switch ($unit)
			{
				case 'm':
					$minutes = floor($duration / 60);
					$return = $minutes . ' ' . Text::_('RST_TIME_UNIT_MINUTES');

					$diff = $duration - ($minutes * 60);
					if ($diff > 0)
					{
						$return .= ' ' . $diff . ' ' . Text::_('RST_TIME_UNIT_SECONDS');
					}

					break;

				case 'h':
					$hours = floor($duration / 3600);
					$return = $hours . ' ' . Text::_('RST_TIME_UNIT_HOURS');

					$diff = $duration - ($hours * 3600);
					if ($diff > 0)
					{
						$minutes = floor($diff / 60);
						$return .= ' ' . $minutes . ' ' . Text::_('RST_TIME_UNIT_MINUTES');

						if ($hours == 0 && $minutes == 0) {
							$diff_sec = $duration - ($minutes * 60);
							$return .= ' (' . $diff_sec . ' ' . Text::_('RST_TIME_UNIT_SECONDS').')';
						}
					}
					break;

				case 'd':
					$days = floor($duration / 86400);
					$return = $days . ' ' . Text::_('RST_TIME_UNIT_DAYS');

					$diff = $duration - ($days * 86400);
					if ($diff > 0)
					{
						$hours = floor($diff / 3600);
						$return .= ' ' . $hours . ' ' . Text::_('RST_TIME_UNIT_HOURS');

						$diff = $diff - ($hours * 3600);
						if ($diff > 0)
						{
							$minutes = floor($diff / 60);
							$return .= ' ' . $minutes . ' ' . Text::_('RST_TIME_UNIT_MINUTES');
						}
					}

					break;
			}
		}
		else
		{
			$return = $duration . ' ' . Text::_('RST_TIME_UNIT_' . $unit);
		}

		return $return;
	}

	public static function showNotifyIcon($ticket)
	{
		if (!RSTicketsProHelper::isStaff() || !RSTicketsProHelper::getConfig('autoclose_enabled') || $ticket->last_reply_customer || $ticket->autoclose_sent || $ticket->status_id == RST_STATUS_CLOSED)
		{
			return '';
		}

		$interval = RSTicketsProHelper::getConfig('autoclose_email_interval') * 86400;
		if ($interval < 86400)
		{
			$interval = 86400;
		}

		$now		= Factory::getDate()->toUnix();
		$last_reply = Factory::getDate($ticket->last_reply)->toUnix() + $interval;

		if ($last_reply > $now)
		{
			return '';
		}

		$overdue = floor(($now - $last_reply) / 86400);

		if (!$overdue)
		{
			return '';
		}

		$url = RSTicketsProHelper::route('index.php?option=com_rsticketspro&task=ticket.notify&cid=' . $ticket->id);
		$img = '<i class="rsticketsproicon-attention rst_notify_ticket"></i>';

		return '<span class="'.RSTicketsProHelper::tooltipClass().'" title="'.RSTicketsProHelper::tooltipText(Text::sprintf('RST_TICKET_NOTIFY_DESC', $overdue)).'"><a href="'.$url.'">'.$img.'</a></span>';
	}
	
	public static function KbSEF($item) 
	{
		$alias = (RSTicketsProHelper::getConfig('kb_advanced_sef') && $item->alias) ? $item->alias : JFilterOutput::stringURLSafe($item->name);

		return $item->id . ':' . $alias;
	}
	
	public static function UserPostedFeedback($article_id)
	{
		$article_id	= (int) $article_id;
		$user 		= Factory::getUser();
		$ip 		= \Joomla\Utilities\IpHelper::getIp();
		$db 		= Factory::getDbo();
		$query		= $db->getQuery(true);

		$query	->select('COUNT(' . $db->qn('id') . ')')
				->from($db->qn('#__rsticketspro_kb_content_feedbacks'));
				
		if ($user->id) {
			$query->where($db->qn('user_id') . '=' . $db->q($user->id) . ' OR ' . $db->qn('ip') . '=' . $db->q($ip));
		} else {
			$query->where($db->qn('ip') . '=' . $db->q($ip));
		}
		
		$query->where($db->qn('article_id') . '=' . $db->q($article_id));
		$db->setQuery($query);
		
		return $db->loadResult();
	}
	
	public static function getTagsList($cat_id)
	{
		$db 		= Factory::getDbo();
		$query		= $db->getQuery(true);
		
		$query	->select('DISTINCT(' . $db->qn('t.tag') . ')')
				->from($db->qn('#__rsticketspro_kb_content_tags', 't'))
				->join('left', $db->qn('#__rsticketspro_kb_content', 'a') . ' ON (' . $db->qn('t.article_id') . '=' . $db->qn('a.id') . ')')
				->where($db->qn('a.category_id') . '=' . $db->q($cat_id))
				->order($db->qn('t.id') . ' ASC');
				
		$db->setQuery($query);
		
		return $db->loadObjectList();
	}
}