<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\Router\RouterBase;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Categories\Categories;

// Add Table Path
Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

/**
 * Routing class from com_tjlms
 *
 * @subpackage  com_tjlms
 *
 * @since       1.0.0
 */
class TjlmsRouter extends RouterBase
{
	private  $views = array(
						'activities','buy','category','certificate',
						'reports','student_course_report','teacher_report',
						'course','courses','dashboard','enrolment',
						'lesson', 'managelessons', 'lessonform', 'lessons', 'assesslesson', 'assessments', 'orders','coupons','coupon','enrolluser'
						);

	private  $special_views = array('course', 'courses', 'lesson');

	private  $views_needing_courseid = array('buy', 'certificate', 'reports');

	private  $views_needing_lessonid = array('reports');

	private  $views_needing_tmpl = array('certificate','lesson', 'assesslesson');

	/**
	 * Build the route for the com_tjlms component
	 *
	 * @param   array  &$query  An array of URL arguments
	 *
	 * @return  array  The URL arguments to use to assemble the subsequent URL.
	 *
	 * @since   1.0.0
	 */
	public function build(&$query)
	{
		$segments = array();

		// Get a menu item based on Itemid or currently active
		$app = Factory::getApplication();
		$menu = $app->getMenu();
		$params = ComponentHelper::getParams('com_tjlms');
		$db = Factory::getDbo();

		if (isset($query['task']) && $query['task'] == 'lesson.downloadMedia')
		{
			$segments[] = 'action';
			$segments[] = $query['task'];

			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
			$mediaModel = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');
			$res = $mediaModel->getItem($query['mid']);

			$segments[] = $res->source;

			unset($query['task']);

			return $segments;
		}

		// We need a menu item.  Either the one specified in the query, or the current active one if none specified
		if (empty($query['Itemid']))
		{
			$menuItem = $menu->getActive();
			$menuItemGiven = false;
		}
		else
		{
			$menuItem = $menu->getItem($query['Itemid']);
			$menuItemGiven = true;
		}

		// Check again
		if ($menuItemGiven && isset($menuItem) && $menuItem->component != 'com_tjlms')
		{
			$menuItemGiven = false;
			unset($query['Itemid']);
		}

		// Check if view is set.
		if (isset($query['view']))
		{
			$view = $query['view'];
		}
		else
		{
			// We need to have a view in the query or it is an invalid URL
			return $segments;
		}

		// Add the view only for normal views, for special its just the slug
		if (isset($query['view']) && !in_array($query['view'], $this->special_views))
		{
			$segments[] = $query['view'];
			unset($query['view']);
		}

		/* Handle the special views */
		if ($view == 'courses' )
		{
			unset($query['view']);
			unset($query['courses_to_show']);

			if (isset($query['course_cat']))
			{
				$catId = (int) $query['course_cat'];

				if ($catId == -1)
				{
					$segments[] = 'all';
					unset($query['course_cat']);
				}
				elseif ($catId )
				{
					$category = Table::getInstance('Category', 'JTable', array('dbo', $db));
					$category->load(array('id' => $catId, 'extension' => 'com_tjlms'));

					$segments[] = $category->alias;
					unset($query['course_cat']);
				}
			}
		}

		if ($view == 'course')
		{
			if (isset($query['id']))
			{
				$course_table = $this->_getCourseRow($query['id'], 'id');

				$segments[] = $course_table->alias;
				unset($query['id']);
				unset($query['view']);
			}
		}

		if ($view == 'lesson')
		{
			if (isset($query['lesson_id']))
			{
				$lesson_table = $this->_getLessonRow($query['lesson_id'], 'id');
				$course_id = isset($query['course_id']) ? $query['course_id'] : $lesson_table->course_id;

				if ($course_id)
				{
					$course_table = $this->_getCourseRow($course_id, 'id');
					$segments[] = $course_table->alias;
				}
				else
				{
					$segments[] = 'all-lessons';
				}

				$segments[] = $lesson_table->alias;
				unset($query['lesson_id']);
				unset($query['course_id']);
				unset($query['view']);
				unset($query['tmpl']);
			}
		}
		/* End Handle the special views */

		/* Handle normal views */
		if ($view == 'orders')
		{
			if (isset($query['orderid']))
			{
				$segments[] = $query['orderid'];
				unset($query['orderid']);
			}
		}

		/* Handle normal views */
		if ($view == 'coupon')
		{
			unset($query['layout']);

			if (isset($query['id']))
			{
				$segments[] = $query['id'];
				unset($query['id']);
			}
		}

		if ($menuItemGiven && isset($menuItem) && $view == 'assessments')
		{
			unset($segments[0]);
		}

		if (in_array($view, $this->views_needing_courseid) && isset($query['course_id']))
		{
			$course_table = $this->_getCourseRow($query['course_id'], 'id');

			$segments[] = $course_table->alias;
			unset($query['course_id']);
		}

		if (in_array($view, $this->views_needing_lessonid) && isset($query['lesson_id']))
		{
			$lesson_table = $this->_getLessonRow($query['lesson_id'], 'id');

			$segments[] = $lesson_table->alias;
			unset($query['lesson_id']);
		}
		/* End Handle normal views */

		if (in_array($view, $this->views_needing_tmpl))
		{
			unset($query['tmpl']);
		}

		return $segments;
	}

	/**
	 * Parse the segments of a URL.
	 *
	 * @param   array  &$segments  The segments of the URL to parse.
	 *
	 * @return  array  The URL attributes to be used by the application.
	 *
	 * @since   3.3
	 */
	public function parse(&$segments)
	{
		$item = $this->menu->getActive();
		$vars = array();
		$db = Factory::getDbo();

		// Count route segments
		$count = count($segments);

		/*
		 * count = 1 : Courses / Course or non-querystring needing views
		 */
		if ($count == 1)
		{
			$category_table = Table::getInstance('Category', 'JTable', array('dbo', $db));
			$category_table->load(array('alias' => $segments[0], 'extension' => 'com_tjlms'));

			if (isset($item->query['courses_to_show']))
			{
				$vars['courses_to_show'] = $item->query['courses_to_show'];
			}

			if ($category_table->id || $segments[0] == 'all')
			{
				if ($segments[0] == 'all')
				{
					$vars['course_cat'] = -1;
				}
				else
				{
					$vars['course_cat'] = $category_table->id;
				}

				$vars['view'] = 'courses';
			}
			elseif ($course_table_id = $this->_getCourseRow($segments[0])->id)
			{
				$vars['view'] = 'course';
				$vars['id'] = $course_table_id;
			}
			elseif (in_array($segments[0], $this->views))
			{
				$vars['view'] = $segments[0];

				if ($segments[0] == 'coupon')
				{
					$vars['layout'] = 'edit';
				}
			}
			else
			{
				$vars['view'] = 'course';
				$vars['id'] = 0;
			}
		}
		else
		{
			$vars['view'] = $segments[0];

			switch ($segments[0])
			{
				case 'webhooks':
					unset($vars['view']);
					$vars['task'] = 'payment.notify';

					if (isset($segments[1]))
					{
						$vars['processor'] = $segments[1];
					}
					break;

				case 'orders':
					if (isset($segments[1]))
					{
						$vars['orderid'] = $segments[1];
					}
					break;

				case 'coupon':
				if (isset($segments[1]))
				{
					$vars['layout'] = 'edit';
					$vars['id'] = $segments[1];
				}
				break;

				case 'reports':
				$course_table = $this->_getCourseRow($segments[1]);
				$lesson_table = $this->_getLessonRow($segments[2]);

				$vars['course_id'] = $course_table->id;
				$vars['lesson_id'] = $lesson_table->id;
				break;

				case 'action':
				unset($vars['view']);
				$vars['task'] = $segments[1];
				break;

				default:
				if (in_array($segments[0], $this->views_needing_courseid))
				{
					$course_table = $this->_getCourseRow($segments[1]);

					$vars['course_id'] = $course_table->id;
				}
				else
				{
					$lesson_table = $this->_getLessonRow($segments[1]);

					$vars['view'] = 'lesson';
					$vars['lesson_id'] = $lesson_table->id;
				}
				break;
			}

			if (in_array($segments[0], $this->views_needing_tmpl))
			{
				$vars['tmpl'] = 'component';
			}
		}

		if(JVersion::MAJOR_VERSION >= 4) {
			$segments = array();
		}

		return $vars;
	}

	/**
	 * Get a course row based on alias or id
	 *
	 * @param   mixed   $course  The id or alias of the course to be loaded
	 * @param   string  $input   The field to match to load the course
	 *
	 * @return  object  The course JTable object
	 */
	private function _getCourseRow($course, $input = 'alias')
	{
		$db = Factory::getDbo();
		$table = Table::getInstance('Course', 'TjlmsTable', array('dbo', $db));
		$table->load(array($input => $course));

		return $table;
	}

	/**
	 * Get a lesson row based on alias or id
	 *
	 * @param   mixed   $lesson  The id or alias of the lesson to be loaded
	 * @param   string  $input   The field to match to load the lesson
	 *
	 * @return  object  The lesson JTable object
	 */
	private function _getLessonRow($lesson, $input = 'alias')
	{
		$db = Factory::getDbo();
		$table = Table::getInstance('Lesson', 'TjlmsTable', array('dbo', $db));
		$table->load(array($input => $lesson));

		return $table;
	}
}
