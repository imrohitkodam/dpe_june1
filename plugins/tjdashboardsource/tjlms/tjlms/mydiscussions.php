<?php
/**
 * @package    Shika
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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Router\Route;
$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_tjlms', JPATH_ADMINISTRATOR);

/**
 * TjLms plugin for shika
 *
 * @since  1.0.0
 */

class TjlmsMydiscussionsDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_MY_DISCUSSIONS";

	/**
	 * Function to get data of the whole block
	 *
	 * @return Array data.
	 *
	 * @since 1.0.0
	 */
	public function getData()
	{
		$userDiscussions = [];

		try
		{
			// Get integration setting
			$params      = ComponentHelper::getParams('com_tjlms');
			$integration = $params->get('social_integration', '', 'STRING');
			$noOfDiscussions = 5;
			$course_group_discussions_only = 1;

			$esmainfile = JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php';
			$jsmainfile = JPATH_ROOT . '/components/com_community/libraries/core.php';

			if ($integration == 'joomla')
			{
				return $userDiscussions;
			}
			elseif ($integration == 'easysocial' && File::exists($esmainfile))
			{
				$userDiscussions = $this->getESdiscussion($noOfDiscussions);

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
				$userDiscussions = $this->getJSdiscussion($noOfDiscussions, $course_group_discussions_only);

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

			$record = array();
			$record['link'] = '';

			if ($userDiscussions['totalCount'] > $noOfDiscussions && !empty($userDiscussions['viewAll']))
			{
				$record['link'] = $userDiscussions['viewAll'];
			}

			if ($userDiscussions['discussion'])
			{
				$onlyDiscussion = $userDiscussions['discussion'];

				foreach ($onlyDiscussion as $index => $md)
				{
						$text = "<span><a href=\"" . $md->discussion_url . "\" target=\"_blank\" style=\"cursor:pointer\">" . $md->title . "</a></span>";
						$onlyDiscussion[$index]->mydiscussion = $text;
				}

				$record['data'] = $onlyDiscussion;
			}

			return $record;
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Get JS group discussion for all enrolled courses
	 *
	 * @param   int     $noOfDiscussions                Count
	 * @param   string  $course_group_discussions_only  if to take discussion of the groups related to course only
	 *
	 * @return  Array $yourActivities
	 *
	 * @since   1.0.0
	 */
	public function getJSdiscussion($noOfDiscussions, $course_group_discussions_only)
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

			$query->setLimit($noOfDiscussions);

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
	 * Function to get easysocialdiscussions
	 *
	 * @param   integer  $noOfDiscussions  number of discussions.
	 *
	 * @return Array data.
	 *
	 * @since 1.0.0
	 */

	public function getESdiscussion($noOfDiscussions)
	{
		$db = Factory::getDbo();

		$myGroups = $this->getMyGroup();

		$userGroups = $this->getCourseGroups();

		if (!empty($userGroups))
		{
			$myGroups = array_intersect($myGroups, $userGroups);
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
			$query->setLimit($noOfDiscussions);
			$db->setQuery($query);
			$result['discussion'] = $db->loadObjectlist();

			$result['viewAll'] = Route::_('index.php?option=com_easysocial&view=groups&filter=mine');

			$result['totalCount'];
			$result['discussion'];
			$result['viewAll'];
		}

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
	 * Get Data for Tabulator Table
	 *
	 * @return string dataArray
	 *
	 * @since   1.0
	 * */
	public function getDataTabulatorTjdashtable()
	{
		$items = [];

		$data  = $this->getData();

		$items['data']    = (!empty($data['data'])?$data['data']:'');
		$items['columns'] = array(
								array("title" => 'Discussions', "field" => 'mydiscussion',"formatter" => 'html')
								);

		if (!empty($data['link']))
		{
			$items['links'][] = ["title" => 'View All', "link" => $data['link']];
		}

		return json_encode($items);
	}

	/**
	 * Get supported Renderers List
	 *
	 * @return array supported renderes for this data source
	 *
	 * @since   1.0
	 * */
	public function getSupportedRenderers()
	{
		return array('tabulator.tjdashtable' => "PLG_TJDASHBOARDRENDERER_TABULATOR");
	}
}
