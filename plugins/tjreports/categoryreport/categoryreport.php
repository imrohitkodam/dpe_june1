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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Categories\Categories;
use Joomla\Data\DataObject;
use Joomla\CMS\Router\Route;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Attempt report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelCategoryreport extends TjreportsModelReports
{
	protected $default_order = 'cat_title';

	protected $default_order_dir = 'ASC';

	public $showSearchResetButton = false;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     BaseDatabaseModel
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);

		$this->columns = array(
			'cat_title' => array('title' => 'COM_TJLMS_COURSE_CAT', 'table_column' => 'cat.title'),
			'name' => array('title' => 'COM_TJLMS_ENROLMENT_USER_NAME', 'table_column' => 'u.name'),
			'username' => array('title' => 'COM_TJLMS_REPORT_USERUSERNAME', 'table_column' => 'u.username'),
			'usergroup' => array('title' => 'COM_TJLMS_REPORT_USERGROUP', 'disable_sorting' => true),
			'total_courses' => array('title' => 'COM_TJLMS_TOTAL_COURSES', 'table_column' => ''),
			'enrolled_courses' => array('title' => 'COM_TJLMS_ENROLMENT_TOTAL_COURSES_ENROLLED', 'table_column' => ''),
			'completed_courses' => array('title' => 'COM_TJLMS_ENROLMENT_TOTAL_COURSES_COMPLETED', 'table_column' => ''),
			'completion' => array('title' => 'COM_TJLMS_COMPLETION', 'table_column' => '', 'disable_sorting' => true),
		);

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return STRING Client
	 *
	 * @since   2.0
	 * */
	public function getPluginDetail()
	{
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_CATEGORYREPORT_REPORT_TITLE'));

		return $detail;
	}

	/**
	 * Get style for left sidebar menu
	 *
	 * @return ARRAY Keys of data
	 *
	 * @since   2.0
	 * */
	public function getStyles()
	{
		return array(
			Uri::root(true) . '/media/com_tjlms/css/tjlms_backend.css',
			Uri::root(true) . '/media/com_tjlms/font-awesome/css/font-awesome.min.css'
		);
	}

	/**
	 * Create an array of filters
	 *
	 * @return    void
	 *
	 * @since    1.0
	 */
	public function displayFilters()
	{
		$reportOptions  = TjlmsHelper::getReportFilterValues($this, $selected, $created_by, $myTeam);

		JLoader::import('components.com_tjlms.models.reports', JPATH_ADMINISTRATOR);
		$TjlmsModelReports 	= new TjlmsModelReports;
		$catFilter 	= $TjlmsModelReports->getCatFilter();
		$userFilter = $TjlmsModelReports->getUserFilter($myTeam);
		$nameFilter = $TjlmsModelReports->getNameFilter($myTeam);

		$dispFilters = array(
			array(
				'cat_title' => array(
					'search_type' => 'select', 'select_options' => $catFilter, 'type' => 'custom'
				),
				'usergroup' => array(
					'search_type' => 'select', 'select_options' => $this->getUserGroupFilter()
				)
			),
		);

		$filters = $this->getState('filters');

		if (isset($filters['cat_title']) && !empty($filters['cat_title']))
		{
			$catid = (int) $filters['cat_title'];

			jimport('joomla.application.categories');
			$categories = Categories::getInstance('tjlms');
			$cat = $categories->get((int) $filters['cat_title']);

			$childCat   = array();
			$childCat[] = $filters['cat_title'];

			if ($cat)
			{
				$children = $cat->getChildren();

				foreach ($children as $child)
				{
					$childCat[] = $child->id;
				}
			}

			$dispFilters[0]['cat_title']['searchin'] = 'cat.id IN (' . implode(",", $childCat) . ')';
		}

		if (count($reportOptions) > 1)
		{
			$dispFilters[1] = array();
			$dispFilters[1]['report_filter'] = array(
					'search_type' => 'select', 'select_options' => $reportOptions
				);
		}

		return $dispFilters;
	}

	/**
	 * Method to get a DataObjectbaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DataObjectbaseQuery  A DataObjectbaseQuery object to retrieve the data set.
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		$db        = $this->_db;
		$query     = parent::getListQuery();
		$colToshow = (array) $this->getState('colToshow');
		$filters = $this->getState('filters');
		$user     = Factory::getUser();
		$userId   = $user->id;

		// Must have columns to get details of non linked data like completion
		$query->select('u.id as user_id, cat.id');
		$query->from('`#__users` AS u');
		$query->join('INNER', '`#__tjlms_enrolled_users` AS eu ON eu.user_id = u.id');
		$query->join('INNER', '`#__tjlms_courses` AS c ON eu.course_id = c.id');
		$query->join('INNER', '`#__categories` AS cat ON c.cat_id = cat.id');
		$query->where('eu.state=1');
		$query->where('u.block=0');

		if (array_intersect(array('completion', 'total_courses'), $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(courses.id)')
					->from($db->quoteName('#__tjlms_courses', 'courses'))
					->where('courses.cat_id = cat.id');
			$query->select('(' . $subQuery->__toString() . ' ) total_courses');
		}

		if (array_intersect(array('completion', 'enrolled_courses'), $colToshow))
		{
			$query->select('COUNT(eu.id) as enrolled_courses');
		}

		if (array_intersect(array('completion', 'completed_courses'), $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(cct.id)')
				->from($db->quoteName('#__tjlms_course_track', 'cct'))
				->join('INNER', '`#__tjlms_courses` AS cc ON cc.id=cct.course_id')
				->where('cct.user_id = eu.user_id')
				->where('cc.cat_id = cat.id')
				->where('cct.status = "C"');
			$query->select('(' . $subQuery->__toString() . ' ) completed_courses');
		}

		if (in_array('usergroup', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('ugm.group_id');
			$subQuery->from($db->quoteName('#__user_usergroup_map') . ' as ugm');
			$subQuery->where($db->quoteName('ugm.user_id') . ' = ' . $db->quoteName('u.id'));
			$query->select('(SELECT GROUP_CONCAT(ug.title SEPARATOR ", ") from  #__usergroups ug where ug.id IN(' . $subQuery . ')) as usergroup');

			if (isset($filters['usergroup']) && !empty($filters['usergroup']))
			{
				$subQuery = $db->getQuery(true);
				$subQuery->select('ugm.user_id');
				$subQuery->from($db->quoteName('#__user_usergroup_map') . ' as ugm');
				$subQuery->where($db->quoteName('ugm.group_id') . ' = ' . (int) $filters['usergroup']);
				$query->where('u.id IN(' . $subQuery . ')');
			}
		}

		if ((int) $filters['report_filter'] === 1)
		{
			$query->where('c.created_by = ' . (int) $userId);
		}
		elseif ((int) $filters['report_filter'] === -1)
		{
			$hasUsers = TjlmsHelper::getSubusers();

			if ($hasUsers)
			{
				$query->where('eu.user_id IN(' . implode(',', $hasUsers) . ')');
			}
			else
			{
				$query->where('eu.user_id=0');
			}
		}

		$query->group($db->quoteName('u.id'));
		$query->group($db->quoteName('cat.id'));

		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function getItems()
	{
		$items = parent::getItems();

		foreach ($items as $ind => &$item)
		{
			$item['completion'] = '0 %';

			if (abs($item['total_courses']))
			{
				$item['completion'] = floor(($item['completed_courses'] / $item['total_courses']) * 100) . ' %';
			}

			if ($item['total_courses'])
			{
				$filters = array('cat_title' => $item['id']);
				$link = $this->getReportLink('coursereport', $filters);
				$item['total_courses'] = '<a href="' . Route::_($link) . '">' . $item['total_courses'] . '</a>';
			}

			if ($item['enrolled_courses'])
			{
				$filters = array('username' => $item['user_id'], 'cat_title' => $item['id']);
				$link = $this->getReportLink('studentcoursereport', $filters);
				$item['enrolled_courses'] = '<a href="' . Route::_($link) . '">' . $item['enrolled_courses'] . '</a>';
			}

			if ($item['completed_courses'])
			{
				$filters = array('username' => $item['user_id'], 'cat_title' => $item['id'], 'status' => 'C');
				$link = $this->getReportLink('studentcoursereport', $filters);
				$item['completed_courses'] = '<a href="' . Route::_($link) . '">' . $item['completed_courses'] . '</a>';
			}
		}

		return $items;
	}
}
