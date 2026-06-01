<?php
/**
 * @package    Climate
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
use Joomla\CMS\Router\Route;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjlmsdashboard_mygroups', JPATH_ADMINISTRATOR);

/**
 * dashboard plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjlmsdashboardMygroups extends CMSPlugin
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
	public function onmygroupsRenderPluginHTML($plg_data, $layout = 'default')
	{
		$result = $this->getData($plg_data);
		$no_of_groups = $this->params->get('no_of_groups', '5', 'INT');
		$html = '';

		if ($result['totalCount'] != 0)
		{
			$comtjlmsHelper = new comtjlmsHelper;

			$this->dash_icons_path = Uri::root(true) . '/media/com_tjlms/images/default/icons/';

			// Load the layout & push variables
			ob_start();
			$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, $this->params->get('layout', $layout));
			include $layout;

			$html = ob_get_contents();
			ob_end_clean();
		}

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
		$db = Factory::getDbo();

		// Get integration setting
		$params      = ComponentHelper::getParams('com_tjlms');
		$integration = $params->get('social_integration', '', 'STRING');

		$mygroups = '';
		$no_of_groups = $this->params->get('no_of_groups', '5', 'INT');
		$course_group_only = $this->params->get('course_group_only', '1', 'INT');

		if ($integration == 'joomla')
		{
			$mygroups = '';
			$total_rows = '';
			$viewAll = '';
		}
		elseif ($integration == 'easysocial')
		{
			$query  = $db->getQuery(true);
			$query->select('grp.title,grp.id,grp.alias');
			$query->from('#__social_clusters as grp');
			$query->join('left', '#__social_clusters_nodes as node ON grp.id=node.cluster_id');
			$query->where('node.uid=' . $plg_data->user_id);

			if ($course_group_only == 1)
			{
				$userGroups = $this->getCourseGroups();

				if ($userGroups)
				{
					$query->where('grp.id IN (' . $userGroups . ')');
				}
				else
				{
					$query->where('grp.id IN (null)');
				}
			}

			$db->setQuery($query);
			$total_rows = $db->execute();

			// Get total number of rows
			$total_rows = $db->getNumRows();

			$query->setLimit($no_of_groups);

			$db->setQuery($query);
			$mygroups = $db->loadObjectlist();

			foreach ($mygroups as $mygroup)
			{
				$mygroup->group_url = Route::_('index.php?option=com_easysocial&view=groups&id=' . $mygroup->id . ':' . $mygroup->alias . '&layout=item');
			}

			$viewAll = Route::_('index.php?option=com_easysocial&view=groups&filter=mine');
		}
		elseif ($integration == 'jomsocial')
		{
			$query  = $db->getQuery(true);
			$query->select('grp.name as title,grp.id');
			$query->from('#__community_groups as grp');
			$query->join('left', '#__community_groups_members as gm ON grp.id=gm.groupid');
			$query->where('gm.memberid=' . $plg_data->user_id);
			$query->where('gm.approved=1');

			if ($course_group_only == 1)
			{
				$userGroups = $this->getCourseGroups();

				if ($userGroups)
				{
					$query->where('grp.id IN (' . $userGroups . ')');
				}
				else
				{
					$query->where('grp.id IN (null)');
				}
			}

			$db->setQuery($query);
			$total_rows = $db->execute();

			// Get total number of rows
			$total_rows = $db->getNumRows();

			$query->setLimit($no_of_groups);

			$db->setQuery($query);
			$mygroups = $db->loadObjectlist();

			foreach ($mygroups as $mygroup)
			{
				$mygroup->group_url = CRoute::_('index.php?option=com_community&view=groups&task=viewgroup&groupid=' . $mygroup->id);
			}

			$viewAll = CRoute::_('index.php?option=com_community&view=groups&task=display');
		}

		$result = array();
		$result['totalCount'] = $total_rows;
		$result['data'] = $mygroups;
		$result['viewAll'] = $viewAll;

		return $result;
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
		$userGroups = $db->loadObjectlist();

		if (!empty($userGroups))
		{
			foreach ($userGroups as $usergruop)
			{
				$userGroupsArray[] = $usergruop->group_id;
			}
		}

		$userGroups = implode(',', $userGroupsArray);

		return $userGroups;
	}
}
