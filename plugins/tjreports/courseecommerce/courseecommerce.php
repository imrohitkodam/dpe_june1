<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJReport,coursecommerce
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');
jimport('joomla.application.application');

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Courseecommerce tjreports
 *
 * @since  1.3.14
 */

class TjreportsModelCourseecommerce extends TjreportsModelReports
{
	protected $default_order = 'id';

	protected $default_order_dir = 'ASC';

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  1.3.10
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.3.14
	 */
	public function __construct($config = array())
	{
		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);

		$this->columns = array(
			'course_id' => array('title' => 'COM_TJLMS_COURSE_ID'),
			'title' => array('table_column' => 'c.title', 'title' => 'COM_TJLMS_COURSE_NAME'),
			'enrolled_users_for_free_sample' => array('title' => 'PLG_TJREPORTS_COURSEECOMMERCE_ENROLLED_USERS_FOR_FREE_SAMPLE'),
			'paid_users' => array('title' => 'PLG_TJREPORTS_COURSEECOMMERCE_PAID_USERS'),
			'total_revenue' => array('title' => 'PLG_TJREPORTS_COURSEECOMMERCE_TOTAL_REVENUE'),
			'conversion_ratio' => array('title' => 'PLG_TJREPORTS_COURSEECOMMERCE_CONVERSION_RATIO')
		);

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return array
	 *
	 * @since   1.3.14
	 * */
	public function getPluginDetail()
	{
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_COURSEECOMMERCE'));

		return $detail;
	}

	/**
	 * Get style for left sidebar menu
	 *
	 * @return ARRAY Keys of data
	 *
	 * @since   1.3.14
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
	 * @return    mixed
	 *
	 * @since   1.3.14
	 */
	public function displayFilters()
	{
		$reportOptions  = TjlmsHelper::getReportFilterValues($this, $selected, $created_by, $myTeam);

		JLoader::import('components.com_tjlms.models.reports', JPATH_ADMINISTRATOR);
		$TjlmsModelReports 	= new TjlmsModelReports;
		$courseFilter 		= $TjlmsModelReports->getCourseFilter($created_by);

		$dispFilters = array(
			array(
				'course_id' => array(
					'search_type' => 'text', 'type' => 'equal', 'searchin' => 'c.id'
				),
				'title' => array(
					'search_type' => 'select', 'select_options' => $courseFilter, 'type' => 'equal', 'searchin' => 'c.id'
				)
			)
		);

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
	 * @since   1.3.14
	 */
	protected function getListQuery()
	{
		$db        = $this->_db;
		$query     = parent::getListQuery();
		$colToshow = $this->getState('colToshow');
		$filters = $this->getState('filters');
		$user     = Factory::getUser();
		$userId   = $user->id;

		$query->select(
		array('c.id as course_id', 'c.title as title', 'SUM(o.amount) as total_revenue',
		'count("eu.id") enrolled_users_for_free_sample', "sum(case when o.status = 'C' then 1 else 0 end) paid_users")
		);
		$query->from($db->quoteName('#__tjlms_courses') . 'AS c');
		$query->join('Left',
		$db->quoteName('#__tjlms_enrolled_users', 'eu') . ' ON (' . $db->quoteName('eu.course_id') . ' = ' . $db->quoteName('c.id') . ')'
		);
		$query->join('Left', $db->quoteName('#__tjlms_orders', 'o') . ' ON (' . $db->quoteName('o.course_id') . ' = ' . $db->quoteName('c.id') .
			'and' . $db->quoteName('o.user_id') . ' = ' . $db->quoteName('eu.user_id') .
			'and' . $db->quoteName('o.status') . ' = ' . $db->quote('C') . ')');
		$query->join('INNER', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('u.id') . ' = ' . $db->quoteName('eu.user_id') . ')');
		$query->where('c.type= 1');
		$query->where($db->quoteName('c.state') . ' = ' . (int) 1);
		$query->group('c.id');
		$db->setQuery($query);

		$createdByClause = $myTeamClause = false;
		$reportId = $this->getDefaultReport($this->name);
		$viewAll = $this->checkpermissions($reportId);

		if ((int) $filters['report_filter'] === 1)
		{
			$createdByClause = true;
		}
		elseif ((int) $filters['report_filter'] === -1)
		{
			$hasUsers = TjlmsHelper::getSubusers();
			$myTeamClause = true;
		}

		if ($createdByClause)
		{
			$query->where('c.created_by = ' . (int) $userId);
		}
		elseif ($myTeamClause && $hasUsers)
		{
			$query->where('eu.user_id IN(' . implode(',', $hasUsers) . ')');
		}
		elseif (!$viewAll)
		{
			$query->where('eu.user_id=0');
		}

		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.3.14
	 */
	public function getItems()
	{
		// Add additional columns which are not part of the query
		$items = parent::getItems();

		jimport('techjoomla.common');
		JLoader::import('components.com_tjlms.helpers.tracking', JPATH_SITE);

		$db        = $this->_db;
		$colToshow = $this->getState('colToshow');

		foreach ($items as $key => $item)
		{
			// Calculate conversion ratio
			if (in_array('conversion_ratio', $colToshow))
			{
				$conversion_ratio = "0.000";

				if ($item['enrolled_users_for_free_sample'] != 0)
				{
					$total = $item['paid_users'] / $item['enrolled_users_for_free_sample'] * 100;
					$conversion_ratio = sprintf("%.2f", $total);
				}

				$item['conversion_ratio'] = $conversion_ratio . " %";
			}			$items[$key] = $item;
		}

		$items = $this->sortCustomColumns($items);

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
			array('name' => 'course_id', 'label' => Text::_('COM_TJLMS_COURSE_ID'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'title', 'label' => Text::_('COM_TJLMS_COURSE_NAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'enrolled_users_for_free_sample', 'label' => Text::_('PLG_TJREPORTS_COURSEECOMMERCE_ENROLLED_USERS_FOR_FREE_SAMPLE'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'paid_users', 'label' => Text::_('PLG_TJREPORTS_COURSEECOMMERCE_PAID_USERS'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'total_revenue', 'label' => Text::_('PLG_TJREPORTS_COURSEECOMMERCE_TOTAL_REVENUE'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'conversion_ratio', 'label' => Text::_('PLG_TJREPORTS_COURSEECOMMERCE_CONVERSION_RATIO'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
		);
	}
}
