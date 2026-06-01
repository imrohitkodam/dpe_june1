<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
jimport('joomla.application.component.model');
jimport('techjoomla.common');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Access\Access;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Model for courses
 *
 * @since  1.0
 */

class TjlmsModelCourses extends ListModel
{
	public $course_images_size = '';

	public $courses_to_show = '';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   2.2
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id','a.id',
				'title','a.title',
				'state','a.state',
				'ordering','a.ordering',
				'likesforCourse','enrolled_users_cnt',
				'featured','a.featured',
				'created','a.created',
			);
		}

		parent::__construct($config);
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
	 * @since   1.0.0
	 */
	protected function populateState($ordering = 'ordering', $direction = 'ASC')
	{
		// Initialise variables.
		$app    = Factory::getApplication();
		$input  = $app->input;
		$params = $app->getParams();

		// Get from menu settings, if not set then get mainframe limit
		$defaultLimit = $params->get('cat_all_course_pagination_limit', $app->getCfg('list_limit'));
		$limit        = $app->getUserStateFromRequest('list.limit', 'limit', $defaultLimit);

		$this->setState('list.limit', $limit);
		$limitstart = $input->getInt('limitstart', 0);

		if ($limit == 0)
		{
			$this->setState('list.start', 0);
		}
		else
		{
			$this->setState('list.start', $limitstart);
		}

		// Tag Filter
		$tags = $app->getUserStateFromRequest('com_tjlms.filter.filter_tag', 'filter_tag', '');
		$this->setState('filter.tag', $tags);

		// Custom Course Fields Filter
		$courseFields = $app->getUserStateFromRequest('com_tjlms.filter.course_fields', 'course_fields', '');

		if ($courseFields)
		{
			$this->setState('filter.course_fields', array_filter($courseFields));
		}

		// Load the filter state.
		$search = $app->getUserStateFromRequest('com_tjlms.filter.filter_search', 'filter_search', '', 'string');
		$this->setState('com_tjlms.filter.filter_search', $search);

		// Load the creator filter state.
		$filterCreator = $app->getUserStateFromRequest('com_tjlms.filter.creator_filter', 'creator_filter', '', 'string');
		$this->setState('com_tjlms.filter.creator_filter', $filterCreator);

		$courseStatus = $input->get("course_status", '', "String");

		// Load the course status filter state.
		$filterCourseStatus = $app->getUserStateFromRequest('com_tjlms.filter.course_status', 'course_status', '', 'string');
		$courseStatusValue  = $courseStatus ? $courseStatus : $filterCourseStatus;

		$this->setState('com_tjlms.filter.course_status', $courseStatusValue);

		// Load the user id filter state.
		$filterUserId = $app->getUserStateFromRequest('com_tjlms.filter.user_id', 'user_id', 0, 'INT');

		if ($filterUserId)
		{
			$this->setState('com_tjlms.filter.user_id', $filterUserId);
		}

		// Load the popular course limit filter state.
		$filterPopularCourseLimit = $app->getUserStateFromRequest('com_tjlms.filter.popular_course_limit', 'popular_course_limit', 0, 'INT');

		if ($filterPopularCourseLimit)
		{
			$this->setState('com_tjlms.filter.popular_course_limit', $filterPopularCourseLimit);
		}

		// Category menu
		/*$courseCat = $app->getUserStateFromRequest('com_tjlms' . '.filter.category_filter', 'course_cat', '0', 'string');*/
		$courseCat     = $input->get("course_cat", 0, "INT");
		$menu_category = $params->get('show_courses_from_cat', 0);
		$courseCat     = $menu_category ? $menu_category : $courseCat;

		$app->setUserState('com_tjlms.filter.category_filter', $courseCat);
		$this->setState('com_tjlms.filter.category_filter', $courseCat);

		$app->setUserState('filter.menu_category', $menu_category);
		$this->setState('filter.menu_category', $menu_category);

		// Filter mod course type.
		$course_type = $app->getUserStateFromRequest('com_tjlms.filter.course_type', 'course_type', -1, 'INTEGER');
		$this->setState('com_tjlms.filter.course_type', $course_type);

		// Show subcategory prodcuts
		$show_subcat_courses = $params->get('show_subcat_courses');
		$this->setState('filter.show_subcat_courses', $show_subcat_courses);

		// Featured course filter
		$featuredCourse = $input->get("course_featured", 0, "INT");
		$app->setUserState('com_tjlms.filter.featured', $featuredCourse);
		$this->setState('com_tjlms.filter.featured', $featuredCourse);

		/*set menu params in the state*/
		$menuParams = new Registry;

		if ($menu = $app->getMenu()->getActive())
		{
			$menuParams->loadString($menu->getParams());
		}

		if (!$this->course_images_size)
		{
			$this->course_images_size = $menuParams->get('course_images_size', 'S_');
		}

		if (!$this->courses_to_show)
		{
			$this->courses_to_show = $input->get('courses_to_show', 'all', 'STRING');
		}

		$this->setState('params', $menuParams);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.0.0
	 */
	protected function getListQuery()
	{
		// Filter by user id .
		$userId = $this->getState('com_tjlms.filter.user_id');

		if (empty($userId))
		{
			$userId = Factory::getuser()->id;
		}

		$allowedViewLevels  = Access::getAuthorisedViewLevels($userId);
		$implodedViewLevels = implode('","', $allowedViewLevels);

		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select($this->getState('list.select', 'a.*,c.title as cat'));
		$query->from($db->qn('#__tjlms_courses', 'a'));
		$query->join('INNER', $db->qn('#__categories', 'c') . ' ON (' . $db->qn('c.id') . ' = ' . $db->qn('a.catid') . ')');
		$query->where($db->qn('a.state') . '= 1');
		$query->where($db->qn('c.published') . '= 1');

		// Filter by course inclusion
		$courseInclude = $this->getState('com_tjlms.filter.course_include');

		if (!empty($courseInclude))
		{
			$query->where("a.id IN(" . implode(',', $db->q($courseInclude)) . ")");
		}

		// Filter by course exclusion
		$courseExclude = $this->getState('com_tjlms.filter.course_exclude');

		if (!empty($courseExclude))
		{
			$query->where("a.id NOT IN(" . implode(',', $db->q($courseExclude)) . ")");
		}

		// Filter by course_status .
		$courseStatus = $this->getState('com_tjlms.filter.course_status');

		if ($this->courses_to_show == 'enrolled')
		{
			$query->join('INNER', $db->qn('#__tjlms_enrolled_users', 'eu') . ' ON (' . $db->qn('eu.course_id') . ' = ' . $db->qn('a.id') . ')');
			$query->where($db->qn('eu.user_id') . '=' . $db->q((int) $userId));
		}
		elseif ($this->courses_to_show == 'liked')
		{
			$likedQ = $db->getQuery(true);
			$likedQ->select($db->qn('element_id'));
			$likedQ->from($db->qn('#__jlike_content', 'jc'));
			$likedQ->JOIN('LEFT', $db->qn('#__jlike_likes', 'jl') . ' ON (' . $db->qn('jc.id') . ' = ' . $db->qn('jl.content_id') . ')');
			$likedQ->where($db->qn('jl.like') . '=1');
			$likedQ->where($db->qn('jl.userid') . '=' . $db->q((int) $userId));
			$likedQ->where($db->qn('jc.element') . '=' . $db->q('com_tjlms.course'));
			$db->setQuery($likedQ);
			$likedCourses = $db->loadColumn();

			$query->where("a.id IN(" . implode(',', $db->q($likedCourses)) . ")");
		}
		elseif ($this->courses_to_show == 'recommended')
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('eu.course_id');
			$subQuery->from($db->quoteName('#__tjlms_enrolled_users', 'eu'));
			$subQuery->where($db->qn('eu.user_id') . ' = ' . $db->q((int) $userId));
			$db->setQuery($subQuery);
			$enrolledCourses = $db->loadColumn();

			$recquery = $db->getQuery(true);

			// Join over the content for content title & url
			$recquery->select($db->qn('c.id'));
			$recquery->from($db->qn('#__tjlms_courses', 'c'));
			$recquery->join('LEFT', $db->qn('#__jlike_content', 'content') . ' ON (' . $db->qn('content.element_id') . ' = ' . $db->qn('c.id') . ')');
			$recquery->join('LEFT', $db->qn('#__jlike_todos', 'todo') . ' ON (' . $db->qn('todo.content_id') . ' = ' . $db->qn('content.id') . ')');

			if (!empty($enrolledCourses))
			{
				$recquery->where("c.id NOT IN(" . implode(" , ", $db->q($enrolledCourses)) . ")");
			}

			$recquery->where($db->qn('todo.assigned_to') . ' = ' . $db->q((int) $userId));
			$recquery->where($db->qn('todo.type') . ' = ' . $db->q('reco'));
			$recquery->where($db->qn('content.element') . ' = ' . $db->q('com_tjlms.course'));

			$db->setQuery($recquery);
			$recCourses = $db->loadColumn();
			$query->where("a.id IN(" . implode(" , ", $db->q($recCourses)) . ")");
		}
		elseif ($this->courses_to_show == 'notEnrolled')
		{
			$enrquery = $db->getQuery(true);
			$enrquery->select($db->qn('eu1.course_id'));
			$enrquery->from($db->qn('#__tjlms_enrolled_users', 'eu1'));
			$enrquery->where($db->qn('eu1.user_id') . '=' . $db->q((int) $userId));
			$db->setQuery($enrquery);
			$enrolledCourses = $db->loadColumn();

			if (!empty($enrolledCourses))
			{
				$query->where("a.id NOT IN(" . implode(" , ", $db->q($enrolledCourses)) . ")");
			}
		}
		elseif ($this->courses_to_show == 'completed')
		{
			$query->join('INNER', $db->qn('#__tjlms_course_track', 'ctr') . ' ON (' . $db->qn('ctr.course_id') . ' = ' . $db->qn('a.id') . ')');
			$query->where($db->qn('ctr.status') . '=' . $db->q('C'));
			$query->where($db->qn('ctr.user_id') . '=' . $db->q((int) $userId));
		}
		elseif ($this->courses_to_show == 'inprogress')
		{
			$query->join('INNER', $db->qn('#__tjlms_course_track', 'ctr') . ' ON (' . $db->qn('ctr.course_id') . ' = ' . $db->qn('a.id') . ')');
			$query->where($db->qn('ctr.status') . '=' . $db->q('I'));
			$query->where($db->qn('ctr.user_id') . '=' . $db->q((int) $userId));
		}
		elseif ($this->courses_to_show == 'mostPopular')
		{
			$popularCourseLimit = $this->getState('com_tjlms.filter.popular_course_limit');

			$popularQuery = $db->getQuery(true);
			$popularQuery->select($db->qn('eu1.course_id'));
			$popularQuery->from($db->qn('#__tjlms_enrolled_users', 'eu1'));
			$popularQuery->where($db->qn('eu1.user_id') . '=' . $db->q((int) $userId));
			$db->setQuery($popularQuery);
			$enrolledCourses = $db->loadColumn();

			$subQuery = $db->getQuery(true);
			$subQuery->select('eu.course_id, count(*)');
			$subQuery->from($db->quoteName('#__tjlms_enrolled_users', 'eu'));
			$subQuery->where('eu.state = 1');

			if (!empty($enrolledCourses))
			{
				$subQuery->where("eu.course_id NOT IN(" . implode(" , ", $db->q($enrolledCourses)) . ")");
			}

			$subQuery->group('eu.course_id');

			// 2 DESC means from select clause, order by DESC by 2nd column which is count(*).
			$subQuery->order('2 DESC');

			if (!empty($popularCourseLimit))
			{
				$subQuery->setLimit($popularCourseLimit);
			}

			$db->setQuery($subQuery);
			$popularCourses = $db->loadColumn();

			if (!empty($popularCourses))
			{
				$query->where("a.id IN(" . implode(" , ", $db->q($popularCourses)) . ")");
			}
		}

		// Filter by search in title.
		$search = $this->getState('com_tjlms.filter.filter_search');

		if (!empty($search))
		{
			$search = $db->Quote('%' . $db->escape($search, true) . '%');
			$query->where($db->qn('a.title') . ' LIKE ' . $search);
		}

		$filterCreator = $this->getState('com_tjlms.filter.creator_filter');

		if ($filterCreator)
		{
			$query->where($db->qn('a.created_by') . ' = ' . $db->q((int) $filterCreator));
		}

		$featured = $this->getState('com_tjlms.filter.featured');

		if (!empty($featured))
		{
			$query->where($db->qn('a.featured') . ' = ' . $db->q((int) $featured));
		}

		// Filter by course_type .
		$course_type = $this->getState('com_tjlms.filter.course_type');

		if (isset($course_type))
		{
			if ($course_type == 0) // Filter free courses
			{
				$query->where($db->qn('a.type') . ' = 0');
			}
			elseif ($course_type == 1) // Filter paid courses
			{
				$query->where($db->qn('a.type') . ' = 1');
			}
		}

		// Filter by category.
		$filter_menu_category = $this->state->get('filter.menu_category');

		$input    = Factory::getApplication()->input;
		$urlCatId = $input->get('course_cat', 0, 'INT');

		$filter_mod_category  = $this->getState('com_tjlms.filter.category_filter', -1);

		// Get courses with repect to access level
		$query->where('a.access IN ("' . $implodedViewLevels . '")');
		$query->where('c.access IN ("' . $implodedViewLevels . '")');
		$catId = 0;

		if ($filter_mod_category)
		{
			$catId = $filter_mod_category;
		}
		elseif ($urlCatId)
		{
			$catId = $urlCatId;
		}
		elseif ($filter_menu_category)
		{
			$catId = $filter_menu_category;
		}

		$filter_show_subcat_courses = $this->state->get('filter.show_subcat_courses');
		$catId = $catId == -1 ? 0 : $catId;

		if (!is_array($catId))
		{
				$catId = $catId ? array($catId) : array();
		}

		if (count($catId))
		{
			if (!empty($filter_menu_category) && !$filter_show_subcat_courses)
			{
				$catId = implode(',', ArrayHelper::toInteger($catId));

				if ($catId)
				{
					$query->where('a.catid IN (' . $catId . ')');
				}
			}
			else
			{
				$catId = ArrayHelper::toInteger($catId);
				$categoryTable = JTable::getInstance('Category', 'JTable');
				$subCatItemsWhere = array();

				foreach ($catId as $filter_catid)
				{
					$categoryTable->load($filter_catid);
					$subCatItemsWhere[] = '(' .
						'c.lft >= ' . (int) $categoryTable->lft . ' AND ' .
						'c.rgt <= ' . (int) $categoryTable->rgt . ')';
				}

				$query->where('(' . implode(' OR ', $subCatItemsWhere) . ')');
			}
		}

		if (!empty($courseStatus))
		{
			if ($this->courses_to_show != 'enrolled')
			{
				$query->join('INNER', $db->qn('#__tjlms_enrolled_users', 'eu') . ' ON (' . $db->qn('eu.course_id') . ' = ' . $db->qn('a.id') . ')');
				$query->where($db->qn('eu.user_id') . '=' . $db->q((int) $userId));
			}

			if ($courseStatus == 'completedcourses' || $courseStatus == 'incompletedcourses')
			{
				$query->join('INNER', $db->qn('#__tjlms_course_track', 'ct') . ' ON (' . $db->qn('ct.course_id') . ' = ' . $db->qn('a.id') . '
									AND ' . $db->qn('ct.user_id') . ' = ' . $db->q((int) $userId) . ')');

				$query->where($db->qn('ct.user_id') . ' = ' . $db->q((int) $userId));

				if ($courseStatus == 'completedcourses')
				{
					$query->where($db->qn('ct.status') . ' = "C"');
				}

				if ($courseStatus == 'incompletedcourses')
				{
					$query->where('(' . $db->quoteName('ct.status') . '=' . $db->quote('I') . " OR " . $db->quoteName('ct.status') . ' = "")');
				}
			}
		}

		// Filter by a single or group of tags.
		$tagId = $this->getState('filter.tag');

		if (is_array($tagId))
		{
			$tagId = implode(',', ArrayHelper::toInteger($tagId));
		}

		if ($tagId)
		{
			$subQuery = $db->getQuery(true)
			->select('DISTINCT content_item_id')
			->from($db->quoteName('#__contentitem_tag_map'))
			->where('tag_id IN (' . $tagId . ')')
			->where('type_alias = ' . $db->quote('com_tjlms.course'));

			$query->innerJoin('(' . (string) $subQuery . ') AS tagmap ON tagmap.content_item_id = a.id');
		}

		// Filter by custom course fields.
		$courseFields = $this->getState('filter.course_fields');

		if ($courseFields)
		{
			$courseFieldsName = array_keys($courseFields);
			$subQuery = $db->getQuery(true);
			$subQuery->select('id, name');
			$subQuery->from($db->quoteName('#__fields'));
			$subQuery->where($db->qn('context') . ' = "com_tjlms.course"');
			$subQuery->where("name IN(" . implode(',', $db->q($courseFieldsName)) . ")");

			$db->setQuery($subQuery);
			$courseFieldsId = $db->loadObjectlist();

			$query->JOIN('INNER', $db->quoteName('#__fields_values', 'fv')
			. ' ON (' . $db->quoteName('fv.item_id') . ' = ' . $db->quoteName('a.id') . ')');

			$clause .= '(';

			foreach ($courseFieldsId as $key => $field)
			{
				$clause .= '(' . $db->quoteName('fv.field_id') . ' = '
				. $db->quote($field->id) . ' AND ' . $db->quoteName('fv.value') . ' = ' . $db->quote($courseFields[$field->name]) . ') OR ';
			}

			$clause = rtrim(trim($clause), " OR ");
			$clause .= ')';
			$query->where($clause);
			$query->group('a.id');
		}

		// Enrollment Count
		$subQuery = $db->getQuery(true);
		$subQuery->select('count(*)');
		$subQuery->from($db->quoteName('#__tjlms_enrolled_users', 'eu'));
		$subQuery->join('inner', '`#__users` AS su ON eu.user_id = su.id');
		$subQuery->where('eu.course_id = a.id');
		$subQuery->where('eu.state = 1');
		$query->select('(' . $subQuery . ') as enrolled_users_cnt');

		// Get likes count
		$query->select('IFNULL(l.like_cnt, 0) as likesforCourse');
		$query->JOIN('LEFT', '`#__jlike_content` AS l ON (l.element="com_tjlms.course" AND l.element_id = a.id)');

		// Define now dates
		$nowDate = $db->quote(Factory::getDate()->toSql());

		if ($this->courses_to_show == 'upcomingCourses')
		{
			$query->where($db->qn('a.start_date') . '>' . $nowDate);
		}
		else
		{
			// Define null dates
			$nullDate = $db->quote($db->getNullDate());
			$query->where('(a.start_date = ' . $nullDate . ' OR a.start_date <= ' . $nowDate . ')');
		}

		// Add the list ordering clause.
		$query->order($this->getState('list.ordering', $this->_buildContentOrderBy()) . ' ' . $this->getState('list.direction', 'ASC'));

		return $query;
	}

	/**
	 * Build the orderby for the query
	 *
	 * @return  string	$orderby portion of query
	 *
	 * @since   1.5
	 */
	protected function _buildContentOrderBy()
	{
		$app       = Factory::getApplication();
		$db        = $this->getDbo();
		$params    = $this->state->params;
		$itemid    = $app->input->get('id', 0, 'int') . ':' . $app->input->get('Itemid', 0, 'int');
		$orderCol  = $app->getUserStateFromRequest('com_tjlms.courses.list.' . $itemid . '.filter_order', 'filter_order', '', 'string');
		$orderDirn = $app->getUserStateFromRequest('com_tjlms.courses.list.' . $itemid . '.filter_order_Dir', 'filter_order_Dir', '', 'cmd');
		$orderby   = ' ';

		if (!in_array($orderCol, $this->filter_fields))
		{
			$orderCol = null;
		}

		if (!in_array(strtoupper($orderDirn), array('ASC', 'DESC', '')))
		{
			$orderDirn = 'ASC';
		}

		if ($orderCol && $orderDirn)
		{
			$orderby .= $db->escape($orderCol) . ' ' . $db->escape($orderDirn) . ', ';
		}

		$articleOrderby   = $params->get('orderby_pri', 'rdate');
		$articleOrderDate = $params->get('order_date');
		$primary          = $this->orderbyClause($articleOrderby, $articleOrderDate) . ', ';

		$orderby .= $primary . ' a.created ';

		return $orderby;
	}

	/**
	 * Method to get a list of courses.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();
		$db = $this->getDbo();

		if (!empty($items))
		{
			$comparams = ComponentHelper::getParams('com_tjlms');
			$currency  = $comparams->get('currency', '', 'STRING');

			$tjlmsCoursesHelper = new TjlmsCoursesHelper;
			$comtjlmsHelper     = new comtjlmsHelper;

			JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
			$tjlmsModelcourse = new TjlmsModelcourse;

			foreach ($items as $ind => $obj)
			{
				$items[$ind]->price = $items[$ind]->formatted_price = Text::_("COM_TJLMS_COURSE_FREE");

				if ($obj->type != 0)
				{
					$priceRange = $tjlmsModelcourse->coursePriceRange($obj->id);

					if (isset($priceRange->lowestPrice))
					{
						$items[$ind]->price = $priceRange->lowestPrice;
						$items[$ind]->formatted_price = $comtjlmsHelper->getFromattedPrice($items[$ind]->price, $currency);
					}
					else
					{
						$items[$ind]->formatted_price = '';
					}

					if (isset($priceRange->highestPrice))
					{
						$items[$ind]->highestPrice = $priceRange->highestPrice;
						$items[$ind]->formatted_highestPrice = $comtjlmsHelper->getFromattedPrice($items[$ind]->highestPrice, $currency);
					}
					else
					{
						$items[$ind]->formatted_highestPrice = '';
					}

					$items[$ind]->displayPrice = $tjlmsModelcourse->displayCoursePrice($items[$ind]->formatted_price, $items[$ind]->formatted_highestPrice);
				}

				$items[$ind]->enrolled_users_cnt = $comtjlmsHelper->custom_number_format($obj->enrolled_users_cnt);

				$items[$ind]->image = $tjlmsCoursesHelper->getCourseImage(
						array(
							'image' => $obj->image, 'storage' => $obj->storage), 'M_'
						);
				$items[$ind]->url = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $obj->id, false);

				// Course Tags
				$enableTags = $comparams->get('enable_tags', '0');

				if ($enableTags)
				{
					$courseTags = new TagsHelper;
					$items[$ind]->course_tags = $courseTags->getItemTags('com_tjlms.course', $obj->id);
				}

				// Get enrollment data for render layout(buy, enroll).
				$enrollmentData = $tjlmsModelcourse->enrollmentStatus($obj);

				$items[$ind] = (object) array_merge((array) $obj, (array) $enrollmentData);
			}
		}

		return $items;
	}

	/**
	 * Function to get where conditions with categories
	 *
	 * @param   int  $categoryId  category id
	 *
	 * @return  array where conditions
	 *
	 * @since   1.0
	 */
	public function getWhereCategory($categoryId)
	{
		$db = Factory::getDBO();
		$where = array();

		if (JVERSION >= '3.0')
		{
			$cat_tbl   = Table::getInstance('Category', 'JTable');
			$cat_tbl->load((int) $categoryId);
			$rgt       = $cat_tbl->rgt;
			$lft       = $cat_tbl->lft;
			$baselevel = (int) $cat_tbl->level;
			$where[]   = 'c.lft >= ' . (int) $lft;
			$where[]   = 'c.rgt <= ' . (int) $rgt;
		}
		else
		{
			// Create a subquery for the subcategory list
			$subQuery = $db->getQuery(true);
			$subQuery->select($db->qn('sub.id'));
			$subQuery->from($db->qn('#__categories as sub'));
			$subQuery->join('INNER', '#__categories as this ON sub.lft > this.lft AND sub.rgt < this.rgt');
			$subQuery->where($db->qn('this.id') . ' = ' . $db->q((int) $categoryId));

			/* if ($levels >= 0)
			{
			$subQuery->where('sub.level <= this.level + '.$levels);
			}*/
			$db->setQuery($subQuery);
			$result = $db->loadColumn();

			if ($result)
			{
				$result  = implode(',', $db->q($result));
				$where[] = ' c.id IN (' . $result . ',' . $categoryId . ')';
			}
			else
			{
				$where[] = ' c.id = ' . $categoryId;
			}
		}

		return $where;
	}

	/**
	 * Fetch list of all categories including child ones
	 *
	 * @param   int      $catid               category id
	 * @param   boolean  $onchangeSubmitForm  option to specify options only
	 * @param   string   $name                name of the extension for which to fetch categories
	 * @param   string   $class               class
	 * @param   boolean  $getOptionsOnly      category id
	 *
	 * @return  array  list of category
	 *
	 * @since   1.0
	 */
	public function getTjlmsCats($catid = '', $onchangeSubmitForm = 1, $name = 'course_cat', $class = '', $getOptionsOnly = 0)
	{
		$userId               = Factory::getUser()->id;
		$allowedViewLevels    = Access::getAuthorisedViewLevels($userId);

		$filter_menu_category = $catid ? $catid : $this->state->get('filter.menu_category');

		if ($filter_menu_category && $filter_menu_category != -1)
		{
			$implodedViewLevels = implode(',', $this->_db->q($allowedViewLevels));

			$query = $this->_db->getQuery(true);
			$query->select('c.*');
			$query->from($this->_db->qn('#__categories', 'c'));

			$catWhere = $this->getWhereCategory($filter_menu_category);

			if (!empty($catWhere))
			{
				foreach ($catWhere as $cw)
				{
					$query->where($cw);
				}
			}

			$query->where('c.access IN (' . $implodedViewLevels . ')');

			$this->_db->setQuery($query);
			$cats_filter = $this->_db->loadobjectList('id');
		}

		$options = array();

		// Static function options($extension, $config = array('filter.published' => array(0,1)))
		$lang = Factory::getLanguage();
		$tag  = $lang->gettag();

		$lms_cat_options = HTMLHelper::_('category.options',
									'com_tjlms',
									$config = array('filter.published' => array(1), 'filter.language' => array('*', $tag),'filter.access' => $allowedViewLevels)
									);

		$final_cats = array();

		if ($getOptionsOnly == 1)
		{
			return $lms_cat_options;
		}

		$cats = array_merge($options, $lms_cat_options);

		if (!empty($cats))
		{
			$cid = '';

			// Prepare cat id string
			foreach ($cats as $cat)
			{
				$cid .= $cat->value . ',';
			}

			$cat_list = rtrim($cid, ",");

			// Get categories of specific access levels
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn('a.id'));
			$query->from($this->_db->qn('#__categories', 'a'));
			$query->where($this->_db->qn('a.id') . 'IN (' . $cat_list . ')');
			$query->where($this->_db->qn('a.access') . 'IN (' . implode(',', $this->_db->q($allowedViewLevels)) . ')');
			$this->_db->setQuery($query);
			$result = $this->_db->loadColumn();

			foreach ($cats as $index => $cat)
			{
				if (!in_array($cat->value, $result))
				{
					unset($cats[$index]);
				}
			}

			// If menu option is not set then return all categories.
			if (!isset($cats_filter) && empty($cats_filter))
			{
				/* return $cats*/
				return $cats;
			}
			/* Durgesh Added */

			// If menu option is set then get only those category which are selected
			foreach ($cats_filter as $cat_filter)
			{
				foreach ($cats as $cat)
				{
					if ($cat_filter->id == $cat->value)
					{
						$final_cats[] = $cat;
					}
				}
			}
		}

		return $final_cats;
	}

	/**
	 * Function to get status of categories
	 *
	 * @param   int  $categoryId  category id
	 *
	 * @return  INT
	 *
	 * @since   1.0
	 */
	public function getStatusCategory($categoryId)
	{
		if (!$categoryId)
		{
			return false;
		}

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->qn('published'));
		$query->from($db->qn('#__categories'));
		$query->where($db->qn('id') . '= ' . $db->q((int) $categoryId));
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Fetch list of all course creators
	 *
	 * @return  object || false list of course authors
	 *
	 * @since   1.0
	 */
	public function getCourseCreators()
	{
		try
		{
			$lmsparams          = ComponentHelper::getParams('com_tjlms');
			$showNameOrUsername = $lmsparams->get('show_user_or_username', 'name');

			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn(array('c.created_by', 'u.username', 'u.name')));
			$query->from($this->_db->qn('#__tjlms_courses', 'c'));
			$query->join('INNER', $this->_db->qn('#__users', 'u') . ' ON (' . $this->_db->qn('u.id') . ' = ' . $this->_db->qn('c.created_by') . ')');
			$query->where($this->_db->qn('c.state') . '= 1');
			$query->where($this->_db->qn('u.block') . '= 0');
			$query->group($this->_db->qn('c.created_by'));
			$this->_db->setQuery($query);

			$courseCreatorList = $this->_db->loadObjectlist();

			$nameUserNameCreators = array();

			foreach ($courseCreatorList as $creator)
			{
				$nameUserNameCreators[] = HTMLHelper::_('select.option', $creator->created_by, $creator->$showNameOrUsername);
			}

			return $nameUserNameCreators;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function to get tags
	 *
	 * @return  array
	 *
	 * @since   _DEPLOY_VERSION_
	 */
	public function getTags()
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('DISTINCT id, title');
		$query->from($db->qn('#__tags', 't'));
		$query->join('INNER', $this->_db->qn('#__contentitem_tag_map', 'ctm') .
			' ON (' . $this->_db->qn('ctm.tag_id') . ' = ' . $this->_db->qn('t.id') . ')');
		$query->where($this->_db->qn('ctm.type_alias') . '= ' . $db->quote('com_tjlms.course'));
		$query->where($db->qn('t.published') . '= 1');

		$db->setQuery($query);

		return $db->loadObjectlist();
	}

	/**
	 * Translate an order code to a field for secondary category ordering.
	 *
	 * @param   string  $orderby    The ordering code.
	 * @param   string  $orderDate  The ordering code for the date.
	 *
	 * @return  string  The SQL field(s) to order by.
	 *
	 * @since   1.5
	 */
	public static function orderbyClause($orderby, $orderDate = 'created')
	{
		$queryDate = ' a.created ';

		switch ($orderby)
		{
			case 'date' :
				$orderby = $queryDate;
				break;

			case 'rdate' :
				$orderby = $queryDate . ' DESC ';
				break;

			case 'alpha' :
				$orderby = 'a.title';
				break;

			case 'ralpha' :
				$orderby = 'a.title DESC';
				break;

			case 'likes' :
				$orderby = 'likesforCourse DESC';
				break;

			case 'rlikes' :
				$orderby = 'likesforCourse';
				break;

			case 'order' :
				$orderby = 'a.ordering';
				break;

			case 'rorder' :
				$orderby = 'a.ordering DESC';
				break;

			case 'enrolled' :
				$orderby = 'enrolled_users_cnt DESC';
				break;

			case 'renrolled' :
				$orderby = 'enrolled_users_cnt';
				break;

			case 'front' :
				$orderby = 'a.featured DESC, ' . $queryDate . ' DESC ';
				break;

			case 'random' :
				$orderby = Factory::getDbo()->getQuery(true)->Rand();
				break;

			default :
				$orderby = 'a.ordering';
				break;
		}

		return $orderby;
	}
}
