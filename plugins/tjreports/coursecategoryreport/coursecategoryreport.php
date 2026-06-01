<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJReport,course category
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Router\Route;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Attempt report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelCoursecategoryreport extends TjreportsModelReports
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
		if (method_exists($this, 'tableExists'))
		{
			$this->customFieldsTableExists = $this->tableExists();
		}

		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);

		$this->columns = array(
			'cat_title' => array('title' => 'PLG_TJREPORTS_COURSECATEGORYREPORT_REPORT_CATEGORY', 'table_column' => 'cat.title'),
			'total_courses' => array('title' => 'PLG_TJREPORTS_COURSECATEGORYREPORT_REPORT_TOTAL_COURSES', 'table_column' => ''),
			'total_enrolled' => array('title' => 'PLG_TJREPORTS_COURSECATEGORYREPORT_REPORT_TOTAL_ENROLLED', 'table_column' => ''),
			'total_completed' => array('title' => 'PLG_TJREPORTS_COURSECATEGORYREPORT_REPORT_TOTAL_COMPLETED', 'table_column' => ''),
			'catid' => array('title' => 'PLG_TJREPORTS_COURSECATEGORYREPORT_REPORT_CAT_ID','table_column' => '')
		);

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return ARRAY Client
	 *
	 * @since   2.0
	 * */
	public function getPluginDetail()
	{
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_COURSECATEGORYREPORT_REPORT_TITLE'));

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
	 * @return    ARRAY
	 *
	 * @since    1.0
	 */
	public function displayFilters()
	{
		$reportOptions  = TjlmsHelper::getReportFilterValues($this, $selected, $created_by, $myTeam);

		JLoader::import('components.com_tjlms.models.reports', JPATH_ADMINISTRATOR);
		$TjlmsModelReports 	= new TjlmsModelReports;
		$catFilter 	= $TjlmsModelReports->getCatFilter();

		$dispFilters = array(
			array(
				'cat_title' => array(
					'search_type' => 'select', 'select_options' => $catFilter, 'type' => 'custom'
				)
			),
		);

		$filters = $this->getState('filters');

		if (isset($filters['cat_title']) && !empty($filters['cat_title']))
		{
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
	 * Method to get a JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  JDatabaseQuery  A JDatabaseQuery object to retrieve the data set.
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
		$query->select('c.catid');
		$query->from('`#__tjlms_courses` AS c');
		$query->join('INNER', '`#__categories` AS cat ON c.catid = cat.id');
		$query->join('LEFT', '`#__users` AS u ON c.created_by = u.id');
		$query->where('cat.published=1');
		$query->where('c.state=1');

		if (in_array('total_courses', $colToshow))
		{
			$query->select('count(distinct(c.id)) as total_courses');
		}

		if (in_array('total_enrolled', $colToshow) || in_array('total_completed', $colToshow))
		{
			$query->join("LEFT", '#__tjlms_enrolled_users as eu ON eu.course_id = c.id');
		}

		if (in_array('total_enrolled', $colToshow))
		{
			$query->select('COUNT(distinct(eu.id)) as total_enrolled');
		}

		if (in_array('total_completed', $colToshow))
		{
			$query->select('COUNT(distinct(ct.id)) as total_completed')
			->join("LEFT", '#__tjlms_course_track as ct ON c.id = ct.course_id AND ct.status = "C"');
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
				$query->where('u.id IN(' . implode(',', $hasUsers) . ')');
			}
			else
			{
				$query->where('u.id=0');
			}
		}

		$query->group($db->quoteName('c.catid'));

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

		if (empty($items))
		{
			return;
		}

		foreach ($items as $ind => &$item)
		{
			if ($item['total_courses'])
			{
				$filters = array('cat_title' => $item['catid']);
				$link = $this->getReportLink('coursereport', $filters);
				$item['total_courses'] = '<a href="' . Route::_($link) . '">' . $item['total_courses'] . '</a>';
			}

			if ($item['total_enrolled'])
			{
				$filters = array('cat_title' => $item['catid']);
				$link = $this->getReportLink('studentcoursereport', $filters);
				$item['total_enrolled'] = '<a href="' . Route::_($link) . '">' . $item['total_enrolled'] . '</a>';
			}

			if ($item['total_completed'])
			{
				$filters = array('cat_title' => $item['catid'], 'status' => 'C');
				$link = $this->getReportLink('studentcoursereport', $filters);
				$item['total_completed'] = '<a href="' . Route::_($link) . '">' . $item['total_completed'] . '</a>';
			}
		}

		return $items;
	}

	/**
	 * Create an array of fields in the form of Google data studio requires
	 * Array(
	 *   array(
	 *		'name' => internal name of the field
	 * 		'label' => Name to be displayed on the report
	 *      'dataType' => 'NUMBER' OR 'STRING' OR 'BOOLEAN'
	 * 		'semantics' => array('conceptType' => 'DIMENSION' OR 'METRIC')
	 * 	  ),
	 * )
	 *
	 * More information about fields https://developers.google.com/datastudio/connector/reference#data_types
	 *
	 * @return  ARRAY
	 *
	 * @since   1.3.31
	 */
	public function getGDSFields()
	{
		return array(
			array('name' => 'cat_title', 'label' => Text::_('PLG_TJREPORTS_COURSECATEGORYREPORT_REPORT_CATEGORY'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'total_courses', 'label' => Text::_('PLG_TJREPORTS_COURSECATEGORYREPORT_REPORT_TOTAL_COURSES'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'total_enrolled', 'label' => Text::_('PLG_TJREPORTS_COURSECATEGORYREPORT_REPORT_TOTAL_ENROLLED'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'total_completed', 'label' => Text::_('PLG_TJREPORTS_COURSECATEGORYREPORT_REPORT_TOTAL_COMPLETED'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'catid', 'label' => Text::_('PLG_TJREPORTS_COURSECATEGORYREPORT_REPORT_CAT_ID'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
		);
	}
}
