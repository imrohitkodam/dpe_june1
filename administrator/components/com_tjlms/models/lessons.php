<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;

require_once JPATH_SITE . '/components/com_tjlms/helpers/courses.php';

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.6
 */
class TjlmsModelLessons extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since    1.3.4
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'ordering', 'a.ordering',
				'state', 'a.state',
				'created_by', 'a.created_by',
				'title', 'a.title',
				'start_date', 'a.start_date',
				'end_date', 'a.end_date',
				'format', 'a.format',
				'catid', 'a.catid',
				'in_lib', 'a.in_lib'
				/*'read_count', 'used_count',
				'consented_count'*/
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since 1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.3.32
	 */
	protected function populateState($ordering = 'a.id', $direction = 'desc')
	{
		// Initialise variables.
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$state = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_state');
		$this->setState('filter.state', $state);

		$format = $app->getUserStateFromRequest($this->context . '.filter.format', 'filter_format');
		$this->setState('filter.format', $format);

		$catid = $app->getUserStateFromRequest($this->context . '.filter.catid', 'filter_catid');
		$this->setState('filter.catid', $catid);

		$inLibrary = $app->getUserStateFromRequest($this->context . '.filter.in_lib', 'filter_in_lib');
		$this->setState('filter.in_lib', $inLibrary);

			// DPE  -- Hack - start To add cluster filter and Tag filter on frontend lesson list
			
			PluginHelper::importPlugin('system', 'dpe_tjlms_cluster');
			Factory::getApplication()->triggerEvent('onTjlmsPopulateState', array($this, $this->context));

			// DPE  -- Hack - end


		$tagId = $app->getUserStateFromRequest($this->context . '.filter.tag_id', 'filter_tag_id');
		$this->setState('filter.tag_id', $tagId);

		$limit = $app->getUserStateFromRequest('list.limit', 'limit');
		$this->setState('list.limit', $limit);

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 *
	 * @since	1.3.4
	 */
	protected function getListQuery()
	{
		// Get ACL actions
		$user                 = Factory::getUser();
		$canManageMaterial    = $user->authorise('core.manage.material', 'com_tjlms');
		$canManageMaterialOwn = $user->authorise('core.own.manage.material', 'com_tjlms');

		$query = $this->_db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
					'list.select', 'a.*'
			)
		);
		$query->from($this->_db->qn('#__tjlms_lessons', 'a'));

		// Join over the users for the checked out user
		$query->select($this->_db->qn('uc.name', 'editor'));
		$query->join('LEFT', $this->_db->qn('#__users', 'uc') . 'ON(' . $this->_db->qn('uc.id') . '=' . $this->_db->qn('a.checked_out') . ')');

		// Join over the user field 'created_by'
		$query->select($this->_db->qn(array('created_by.name', 'created_by.username')));

		$query->join('LEFT', $this->_db->qn('#__users', 'created_by') . 'ON(
		' . $this->_db->qn('created_by.id') . '=' . $this->_db->qn('a.created_by') . ')');

		// Join over the content table
		$query->select($this->_db->qn('jc.id', 'contentId'));

		// DPE Hack start

		/*
		$query->join('LEFT', $this->_db->qn('#__jlike_content', 'jc') . 'ON(' . $this->_db->qn('a.id') . '=' . $this->_db->qn('jc.element_id') . ')');
		*/
		$query->select($this->_db->qn('jc.params'));
		$query->join('INNER', $this->_db->qn('#__jlike_content', 'jc') .
		'ON(' . $this->_db->qn('a.id') . '=' . $this->_db->qn('jc.element_id') . ' AND ' . $this->_db->qn('jc.element') . '= "com_tjlms.lesson")');

		if (!$canManageMaterial)
		{
			$assignBy = ' AND ' . $this->_db->qn('jt.assigned_by') . ' = ' . $this->_db->q($user->id);
		}

		// Query updated for DPE

		// Join over the todo table to get user counts

		/*
		$query->select('COUNT(jt.id) AS user_count');
		$query->join('LEFT', $this->_db->qn('#__jlike_todos', 'jt') . 'ON(' . $this->_db->qn('jc.id') . '=' . $this->_db->qn('jt.content_id') . ')');
		*/
		$query->select('COUNT(DISTINCT user.id) AS user_count');
		$query->join('LEFT', $this->_db->qn('#__jlike_todos', 'jt') .
		'ON(' . $this->_db->qn('jc.id') . '=' . $this->_db->qn('jt.content_id') . $assignBy . ')');

		$query->join('LEFT', $this->_db->qn('#__users', 'user') . 'ON(' . $this->_db->qn('jt.assigned_to') . '=' . $this->_db->qn('user.id')
		 . ')');


		// Join over the todo extended table to get interactions count
		$query->select("SUM(te.read) 'read_count',
						SUM(te.used) 'used_count',
						SUM(te.consented) 'consented_count'");
		$query->join('LEFT', $this->_db->qn('#__jlike_todos_extended', 'te') . 'ON(' . $this->_db->qn('jt.id') . '=' . $this->_db->qn('te.todo_id') . ')');

		// DPE Hack end

		// To get the lesson category name
		/*$query->select(array('c.title AS lessoncategory'));
		$query->JOIN('INNER', $this->_db->qn('#__categories', 'c') . ' ON (' . $this->_db->qn('c.id') . ' = ' . $this->_db->qn('a.catid') . ')');

		// To get the lesson media
		$query->select(array('media.path AS imagepath', 'media.source AS imagefile'));
		$query->JOIN('LEFT', $this->_db->qn('#__tj_media_files', 'media') . ' ON (' . $this->_db->qn('media.id') . ' = ' . $this->_db->qn('a.image') . ')');*/

		// DPE - Hack - End

		/*$query->select("COUNT(CASE WHEN te.read = '1' THEN 1 END) 'read_count',
						COUNT(CASE WHEN te.used = '1' THEN 1 END) 'used_count',
						COUNT(CASE WHEN te.consented = '1' THEN 1 END) 'consented_count'");
	$query->join('LEFT', $this->_db->qn('#__jlike_todos_extended', 'te') . 'ON(' . $this->_db->qn('jt.id') . '=' . $this->_db->qn('te.todo_id') . ')');*/

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where($this->_db->qn('a.state') . ' = ' . $this->_db->q((int) $published));
		}
		else
		{
			$query->where($this->_db->qn('a.state') . ' IN (0, 1)');
		}

		// Hack start for get individual lessons

		// Join over the media table to get document format
		$query->select('tm.source');
		$query->join('INNER', $this->_db->qn('#__tjlms_media', 'tm') . 'ON(' . $this->_db->qn('a.media_id') . '=' . $this->_db->qn('tm.id') . ')');

		// Hack end for get individual lessons

		// DPE - Hack - Start - Apply School Filter
		$plgSystemDpeTjlmsCluster = PluginHelper::getPlugin('system', 'dpe_tjlms_cluster');

		if (!empty($plgSystemDpeTjlmsCluster))
		{ 
			$clusterId = $this->getState('filter.clusters');
			
		// DPE Hack
			$agencyTags = array_filter((array) $this->getState('filter.tags'));

			if (is_array($agencyTags))
				{
					foreach($agencyTags as $key => $agencyTag)
					{

						if (!is_int($agencyTag))
						{
							$agencyTags[$key] = (int) $agencyTag;
						}
					}
				 }
			PluginHelper::importPlugin('system', 'dpe_tjlms_cluster');
			Factory::getApplication()->triggerEvent('onTjlmsGetListQuery', array($query, $clusterId, $this->context,$agencyTags));
		}

		// DPE - Hack - End
		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($this->_db->qn('a.id') . ' = ' . $this->_db->q((int) substr($search, 3)));
			}
			else
			{
				$search = '%' . $this->_db->escape($search, true) . '%';
				$query->where($this->_db->qn('a.title') . ' LIKE ' . $this->_db->q($search, false));
			}
		}

		// Filter by lesson format
		$format = $this->getState('filter.format');


		// DPE - Hack - Start
		if (!$canManageMaterial && empty($plgSystemDpeTjlmsCluster))
		{
			$query->where($this->_db->qn('a.created_by') . ' = ' . $this->_db->q($user->id));
		}

		// DPE - Hack - End

		if (!empty($format))
		{
			$query->where($this->_db->qn('a.format') . ' = ' . $this->_db->q($format));
		}

		$query->where($this->_db->qn('a.format') . " NOT IN ('quiz','exercise','feedback')");

		if ($canManageMaterial || $canManageMaterialOwn)
		{
			// Filter by lesson creator
			$created_by = $this->getState('filter.created_by');

			if ($canManageMaterial && !empty($created_by))
			{
				if (is_numeric($created_by))
				{
					$query->where($this->_db->qn('a.created_by') . ' = ' . $this->_db->q($created_by));
				}
				elseif (is_array($created_by))
				{
					$created_by = ArrayHelper::toInteger($created_by);
					$created_by = implode(',', $created_by);
					$query->where('a.created_by IN (' . $created_by . ')');
				}
			}
			elseif(!$canManageMaterial && $canManageMaterialOwn)
			{
				$query->where($this->_db->qn('a.created_by') . ' = ' . $this->_db->q($user->id));
			}
		}
		else
		{
			$query->where($this->_db->qn('a.id') . ' = 0');
		}

		$catId = $this->state->get("filter.catid");

		if (is_array($catId))
		{
			$catId = implode(',', ArrayHelper::toInteger($catId));
		}

		if ($catId)
		{
			$query->where($this->_db->qn('a.catid') . ' IN ' . '(' . $catId . ')');
		}

		$inLibrary = $this->state->get("filter.in_lib");

		if (is_numeric($inLibrary))
		{
			$query->where($this->_db->qn('a.in_lib') . ' = ' . $this->_db->q($inLibrary));
		}

		// Filter by a single or group of tags.
		$tagId = $this->getState('filter.tag_id');

		if (is_array($tagId))
				{
					foreach($tagId as $key => $tagIds)
					{

						if (!is_int($tagIds))
						{
							$tagId[$key] = (int) $tagIds;
						}
					}
				 }

		if (is_array($tagId))
		{
			$tagId = implode(',', ArrayHelper::toInteger($tagId));
		}

		if ($tagId)
		{
			$query->JOIN('INNER', $this->_db->qn('#__contentitem_tag_map', 'tag_map') . ' ON (' . $this->_db->qn('a.id')
					. ' = ' . $this->_db->qn('tag_map.content_item_id') . ')');
			$query->JOIN('INNER', $this->_db->qn('#__content_types', 'ctype') . ' ON (' . $this->_db->qn('tag_map.type_id')
					. ' = ' . $this->_db->qn('ctype.type_id') . ')');
			$query->where($this->_db->quoteName('ctype.type_alias') . ' = ' . ' "com_tjlms.lesson" ');
			$query->where($this->_db->quoteName('tag_map.tag_id') . ' IN ' . '(' . $tagId . ')');
		}

		$limit = $this->getState('list.limit');

		if (!empty($limit))
		{
			$query->setlimit($limit);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.id');
		$orderDirn = $this->state->get('list.direction', 'DESC');

		$query->group('a.id');

		if ($orderCol && $orderDirn)
		{
			$query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
		}
		else
		{
			$query->order($this->_db->qn('a.id') . ' DESC');
		}

		// DPE Hack to show all the compliance data even if the user is not present todo table 
       $query->where('(' . $this->_db->qn('user.id') . ' IS NULL OR ' . $this->_db->qn('user.block') . ' = 0)');

		return $query;
	}

	/**
	 * update lesson entry if new module is assign to it.
	 *
	 * @param   string  $lessonId  A prefix for the store id.
	 * @param   string  $modId     A prefix for the store id.
	 * @param   string  $courseId  A prefix for the store id.
	 *
	 * @return  JSON
	 */
	public function updateLessonsModule( $lessonId, $modId, $courseId )
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->update($this->_db->qn('#__tjlms_lessons'));
			$query->set($this->_db->qn('mod_id') . '=' . $this->_db->q((int) $modId));
			$query->where($this->_db->qn('id') . '=' . $this->_db->q((int) $lessonId));
			$query->where($this->_db->qn('course_id') . '=' . $this->_db->q((int) $courseId));
			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
				echo $this->_db->getErrorMsg();

				return false;
			}

			return true;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * update lesson order as per sorting done.
	 *
	 * @param   string  $key        A prefix for the store id.
	 * @param   string  $newRank    A prefix for the store id.
	 * @param   string  $course_id  A prefix for the store id.
	 * @param   string  $mod_id     A prefix for the store id.
	 *
	 * @return  JSON
	 */
	public function switchOrderLesson($key,$newRank,$course_id,$mod_id)
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->update($this->_db->qn('#__tjlms_lessons'));
			$query->set($this->_db->qn('ordering') . '=' . $this->_db->q((int) $newRank));
			$query->where($this->_db->qn('id') . '=' . $this->_db->q((int) $key));
			$query->where($this->_db->qn('course_id') . '=' . $this->_db->q((int) $course_id));
			$query->where($this->_db->qn('mod_id') . '=' . $this->_db->q((int) $mod_id));

			$this->_db->setQuery($query);
			$this->_db->execute();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * function is used to save sorting of LESSONS.
	 *
	 * @param   string  $course_id  A prefix for the store id.
	 * @param   string  $mod_id     A prefix for the store id.
	 *
	 * @return  JSON
	 */
	public function getLessonsOrderList($course_id,$mod_id)
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn(array('id','ordering')));
			$query->from($this->_db->qn('#__tjlms_lessons', 'l'));
			$query->where($this->_db->qn('course_id') . ' = ' . $this->_db->q((int) $course_id));
			$query->where($this->_db->qn('mod_id') . ' = ' . $this->_db->q((int) $mod_id));
			$this->_db->setQuery($query);

			$lesson_order = $this->_db->loadobjectlist();

				if (!empty($lesson_order) && count($lesson_order) > 0)
				{
					$list = array();

					foreach ($lesson_order as $key => $l_order)
					{
						$list[$l_order->id] = $l_order->ordering;
					}

					return $list;
				}
				else
				{
						return false;
				}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function used to remove associated files
	 *
	 * @param   string  $mediaId   A prefix for the store id.
	 * @param   string  $lessonId  A prefix for the store id.
	 *
	 * @return  JSON
	 *
	 * @since  1.0.0
	 *
	 */
	public function removeAssocFiles($mediaId, $lessonId)
	{
		try
		{
			$query = $this->_db->getQuery(true);

		// Delete record condition
		$conditions = array(
			$this->_db->qn('media_id') . '=' . $mediaId,
			$this->_db->qn('lesson_id') . '=' . $lessonId
		);

		$query->delete($this->_db->qn('#__tjlms_associated_files'));
		$query->where($conditions);
		$this->_db->setQuery($query);

			if ($this->_db->execute())
			{
				return 1;
			}
			else
			{
				return 0;
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	mixed  An array of data items on success, false on failure.
	 *
	 * @since	1.3.4
	 */
	public function getItems()
	{
		$items = parent::getItems();

		$tjlmsparams    = ComponentHelper::getParams('com_tjlms');
		$dateFormatShow = $tjlmsparams->get('date_format_show', 'Y-m-d H:i:s');

		// If emtpy or an error, just return.
		if (!empty($items))
		{
			foreach ($items as &$obj)
			{
				if (!$obj->username && !$obj->name)
				{
					$obj->name = $obj->username = Text::_('COM_TJLMS_BLOCKED_USER');
				}

				if (!empty($obj->start_date) &&  $obj->start_date != '0000-00-00 00:00:00')
				{
					$obj->start_date = HTMLHelper::_('date', $obj->start_date, $dateFormatShow);
				}
				else
				{
					$obj->start_date = '-';
				}

				if (!empty($obj->end_date) &&  $obj->end_date != '0000-00-00 00:00:00')
				{
					$obj->end_date = HTMLHelper::_('date', $obj->end_date, $dateFormatShow);
				}
				else
				{
					$obj->end_date = '-';
				}
			}
		}

		return $items;
	}

	/**
	 * Fetch list of all lessons used in any of the courses
	 *
	 * @param   ARRAY  $lessonIds  Array of the lesson Ids
	 *
	 * @return  object | false list of course authors
	 *
	 * @since   1.3.4
	 */
	public function getLessonsUsedInCourses($lessonIds)
	{
		$query = $this->_db->getQuery(true);
		$query->select($this->_db->qn(array('cl.lesson_id')));
		$query->from($this->_db->qn('#__tjlms_lessons', 'l'));
		$query->join('INNER', $this->_db->qn('#__tjlms_courses_lessons', 'cl') . " ON (l.id = cl.lesson_id )");
		$query->where($this->_db->qn('cl.lesson_id') . ' IN(' . implode(',', $lessonIds) . ')');
		$this->_db->setQuery($query);

		return $this->_db->loadColumn();
	}

	/**
	 * Fetch list of all lessons attempted by user
	 *
	 * @param   ARRAY  $lessonIds  Array of the lesson Ids
	 *
	 * @return  object | false list of course authors
	 *
	 * @since   1.3.4
	 */
	public function getAttemptedLessons($lessonIds)
	{
		$query = $this->_db->getQuery(true);
		$query->select($this->_db->qn(array('lt.lesson_id')));
		$query->from($this->_db->qn('#__tjlms_lessons', 'l'));
		$query->join('INNER', $this->_db->qn('#__tjlms_lesson_track', 'lt') . " ON (l.id = lt.lesson_id )");
		$query->join('INNER', $this->_db->qn('#__users', 'u') . " ON (u.id = lt.user_id)");
		$query->where($this->_db->qn('lt.lesson_id') . ' IN(' . implode(',', $lessonIds) . ')');
		$this->_db->setQuery($query);

		return $this->_db->loadColumn();
	}

	/**
	 * Fetch list of all lessons used in any of the courses
	 *
	 * @param   ARRAY  $lessonIds  Array of the lesson Ids
	 *
	 * @return  ARRAY
	 *
	 * @since   1.3.4
	 *
	 * @deprecated  1.4.0 Use TJLMS lesson library instead
	 */
	public function getLessonTitles($lessonIds)
	{
		$query = $this->_db->getQuery(true);
		$query->select($this->_db->qn(array('l.title')));
		$query->from($this->_db->qn('#__tjlms_lessons', 'l'));
		$query->where($this->_db->qn('l.id') . ' IN(' . implode(',', $lessonIds) . ')');
		$this->_db->setQuery($query);

		return $this->_db->loadColumn();
	}

	/**
	 * Fetch list of all lesson creators
	 *
	 * @param   Int  $forFilter  If used to show the list in the filter check permissions
	 *
	 * @return  object | false list of course authors
	 *
	 * @since   1.3.4
	 */
	public function getLessonCreators($forFilter = 0)
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn(array('l.created_by', 'u.username', 'u.name')));
			$query->from($this->_db->qn('#__tjlms_lessons', 'l'));
			$query->join('INNER', $this->_db->qn('#__users', 'u') . ' ON (' . $this->_db->qn('u.id') . ' = ' . $this->_db->qn('l.created_by') . ')');
			$query->where($this->_db->qn('l.state') . '= 1');
			$query->where($this->_db->qn('u.block') . '= 0');

			if ($forFilter == 1)
			{
				// Get ACL actions
				$user      = Factory::getUser();

				$canManageMaterial      = $user->authorise('core.manage.material', 'com_tjlms');
				$canManageMaterialOwn   = $user->authorise('core.own.manage.material', 'com_tjlms');

				if ($canManageMaterial)
				{
					$query->group($this->_db->qn('l.created_by'));
				}
				elseif ($canManageMaterialOwn)
				{
					$query->where($this->_db->qn('l.created_by') . ' = ' . (int) $user->id);
				}
				else
				{
					$query->where($this->_db->qn('l.id') . ' = 0');
				}
			}

			$this->_db->setQuery($query);

			return $this->_db->loadObjectlist();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}
}
