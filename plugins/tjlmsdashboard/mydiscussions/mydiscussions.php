<?php
/**
 * @package    Climate-Kic
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Router\Route;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjlmsdashboard_mydiscussions', JPATH_ADMINISTRATOR);

/**
 * dashboard plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjlmsdashboardMydiscussions extends CMSPlugin
{
	/**
	 * Plugin that supports creating the tjlms dashboard
	 *
	 * @param   string   &$subject  The context of the content being passed to the plugin.
	 * @param   integer  $config    Optional page number. Unused. Defaults to zero.
	 *
	 * @since 1.0.0
	 */

	public function __construct(&$subject, $config)
	{
		$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

		if (!class_exists('comtjlmsHelper'))
		{
			JLoader::register('comtjlmsHelper', $path);
			JLoader::load('comtjlmsHelper');
		}

		$comtjlmsHelper = new comtjlmsHelper;

		parent::__construct($subject, $config);
	}

	/**
	 * Function to render the whole block
	 *
	 * @param   ARRAY  $plg_data  data to be used to create whole block
	 * @param   ARRAY  $layout    Layout to be used
	 *
	 * @return  complete html.
	 *
	 * @since 1.0.0
	 */
	public function onmydiscussionsRenderPluginHTML($plg_data, $layout = 'default')
	{
		$mydiscussions = $this->getData($plg_data);
		$no_of_discussions = $this->params->get('no_of_discussions');
		$html = '';

		$comtjlmsHelper = new comtjlmsHelper;
		$this->dash_icons_path = Uri::root(true) . '/media/com_tjlms/images/default/icons/';

		// Load the layout & push variables
		ob_start();
		$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, $this->params->get('layout', $layout));
		include $layout;

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Function to get data of the whole block
	 *
	 * @param   ARRAY  $plg_data  data to be used to create whole block
	 *
	 * @return  data.
	 *
	 * @since 1.0.0
	 */
	public function getData($plg_data)
	{
		$user = Factory::getUser();
		$userDiscussions = '';

		// Get integration setting
		$params      = ComponentHelper::getParams('com_tjlms');
		$integration = $params->get('social_integration', '', 'STRING');
		$no_of_discussions = $this->params->get('no_of_discussions');
		$course_group_discussions_only = $this->params->get('course_group_discussions_only');

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$esmainfile = JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php';
		$jsmainfile = JPATH_ROOT . '/components/com_community/libraries/core.php';

		if ($integration == 'joomla')
		{
			return $userDiscussions;
		}
		elseif ($integration == 'easysocial' && File::exists($esmainfile))
		{
			$userDiscussions = $this->getESdiscussion($no_of_discussions, $course_group_discussions_only);

			require_once JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php';
			$app = FD::table('App');
			$app->load(array('group' => SOCIAL_TYPE_GROUP, 'element' => 'discussions', 'type' => SOCIAL_TYPE_APPS));

			if (!empty($userDiscussions['discussion']))
			{
				foreach ($userDiscussions['discussion'] as $userdiscussion)
				{
					$group                          = Foundry::group($userdiscussion->uid);
					$userdiscussion->discussion_url = FRoute::apps(
																	array(
																		'layout' => 'canvas',
																		'customView' => 'item',
																		'uid' => $group->getAlias(),
																		'type' => SOCIAL_TYPE_GROUP,
																		'id' => $app->getAlias(),
																		'discussionId' => $userdiscussion->id
																	)
																	);
				}
			}
		}
		elseif ($integration == 'jomsocial' && File::exists($jsmainfile))
		{
			$userDiscussions = $this->getJSdiscussion($no_of_discussions, $course_group_discussions_only);

			require_once JPATH_ROOT . '/components/com_community/libraries/core.php';

			if (!empty($userDiscussions['discussion']))
			{
				foreach ($userDiscussions['discussion'] as $userdiscussion)
				{
					$group_url = 'index.php?option=com_community&view=groups&task=viewdiscussion';
					$userdiscussion->discussion_url = CRoute::_($group_url . '&groupid=' . $userdiscussion->groupid . '&topicid=' . $userdiscussion->id, false);
				}
			}
		}

		return $userDiscussions;
	}

	/**
	 * Function to get groupids related o course
	 *
	 * @return  data.
	 *
	 * @since 1.0.0
	 */
	public function getCourseGroups()
	{
		$user = Factory::getUser();
		$userGroupsArray = array();

		// Get group ID's
		$db = Factory::getDbo();
		$query  = $db->getQuery(true);
		$query->select('c.group_id');
		$query->from('#__tjlms_courses as c');
		$query->join('INNER', '#__tjlms_enrolled_users as e ON e.course_id=c.id');
		$query->where('e.user_id=' . $user->id);
		$query->where('c.group_id != 0');
		$db->setQuery($query);

		$userGroups = $db->loadColumn();

		return $userGroups;
	}

	/**
	 * Function to get groupids related o course
	 *
	 * @return  data.
	 *
	 * @since 1.0.0
	 */
	public function getMyGroup()
	{
		$user = Factory::getUser();
		$userGroupsArray = array();

		// Get group ID's
		$db = Factory::getDbo();
		$query  = $db->getQuery(true);
		$query->select('c.cluster_id');
		$query->from('#__social_clusters_nodes as c');
		$query->where('c.uid=' . $user->id);
		$query->where('c.state = 1');
		$db->setQuery($query);
		$res = $db->loadColumn();

		return $res;
	}

	/**
	 * Get JS group discussion for all enrolled courses
	 *
	 * @param   int     $no_of_discussions              Count
	 * @param   string  $course_group_discussions_only  if to take discussion of the groups related to course only
	 *
	 * @return  obj $yourActivities
	 *
	 * @since   1.0.0
	 */
	public function getJSdiscussion($no_of_discussions, $course_group_discussions_only)
	{
		$db = Factory::getDbo();

		$myGroups = $this->getMyGroup();

		if ($course_group_discussions_only == 1)
		{
			$userGroups = $this->getCourseGroups();

			if (!empty($userGroups))
			{
				$myGroups = array_intersect($myGroups, $userGroups);
			}
		}

			$result = array();
			$result['totalCount'] = "";
			$result['discussion'] = "";
			$result['viewAll'] = "";

		if (!empty($myGroups))
		{
			$query = $db->getQuery(true);
			$query->select('gd.title,gd.id,gd.groupid');
			$query->from('#__community_groups_discuss as gd');

			$userGroups = implode(', ', $myGroups);
			$query->where('gd.id IN (' . $userGroups . ')');
			$query->order('gd.created DESC');

			$db->setQuery($query);
			$result['totalCount'] = $db->execute();

			// Get total number of rows
			$result['totalCount'] = $db->getNumRows();

			$query->setLimit($no_of_discussions);

			$db->setQuery($query);
			$result['discussion'] = $db->loadObjectlist();

			$result['viewAll'] = CRoute::_('index.php?option=com_community&view=groups&task=display');

			$result['totalCount'];
			$result['discussion'];
			$result['viewAll'];
		}

			return $result;
	}

	/**
	 * Get ES group discussion for all enrolled courses
	 *
	 * @param   int     $no_of_discussions              Count
	 * @param   string  $course_group_discussions_only  if to take discussion of the groups related to course only
	 *
	 * @return  obj $yourActivities
	 *
	 * @since   1.0.0
	 */
	public function getESdiscussion($no_of_discussions, $course_group_discussions_only)
	{
		$db = Factory::getDbo();

		$myGroups = $this->getMyGroup();

		if ($course_group_discussions_only == 1)
		{
			$userGroups = $this->getCourseGroups();

			if (!empty($userGroups))
			{
				$myGroups = array_intersect($myGroups, $userGroups);
			}
		}

			$result = array();
			$result['totalCount'] = "";
			$result['discussion'] = "";
			$result['viewAll'] = "";

		if (!empty($myGroups))
		{
			$query = $db->getQuery(true);
			$query->select('gd.title,gd.uid,gd.id');
			$query->from('#__social_discussions as gd');
			$query->where('gd.parent_id = 0');
			$query->where('gd.type="group"');

			$userGroupstr = implode(',', $myGroups);
			$query->where('gd.uid IN (' . $userGroupstr . ')');
			$query->order('gd.created DESC');

			$db->setQuery($query);
			$result['totalCount'] = $db->execute();

			// Get total number of rows
			$result['totalCount'] = $db->getNumRows();
			$query->setLimit($no_of_discussions);
			$db->setQuery($query);
			$result['discussion'] = $db->loadObjectlist();

			$result['viewAll'] = Route::_('index.php?option=com_easysocial&view=groups&filter=mine');

			$result['totalCount'];
			$result['discussion'];
			$result['viewAll'];
		}

		return $result;
	}
}
