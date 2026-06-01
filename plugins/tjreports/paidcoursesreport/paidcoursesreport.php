<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJReport, paid course
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;

jimport('techjoomla.common');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Paid Course Report
 *
 * @since  1.3.14
 */

class TjreportsModelPaidcoursesreport extends TjreportsModelReports
{
	protected $default_order = 'id';

	protected $default_order_dir = 'ASC';

	public $showSearchResetButton = false;

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
		// Joomla fields integration
		// Define custom fields table, alias, and table.column to join on
		$this->customFieldsTable       = '#__tjreports_com_users_user';
		$this->customFieldsTableAlias  = 'tjrcuu';
		$this->customFieldsQueryJoinOn = 'o.user_id';

		if (method_exists($this, 'tableExists'))
		{
			$this->customFieldsTableExists = $this->tableExists();
		}

		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);

		$this->columns = array(
			'course_id' => array('title' => 'COM_TJLMS_COURSE_ID'),
			'title' => array('table_column' => 'c.title','title' => 'COM_TJLMS_COURSE_NAME'),
			'name' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_NAME'),
			'created_by' => array('table_column' => 'c.created_by', 'title' => 'JGLOBAL_FIELD_CREATED_BY_LABEL'),
			'enrolled_on_time' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_ENROLLED_ON_TIME'),
			'end_time' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_END_TIME'),
			'user_email' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_EMAIL'),
			'cdate' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_PAID_ON'),
			'order_value' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_ORDER_VALUE'),
			'coupon_code' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_COUPON_CODE'),
			'coupon_discount' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_COUPON_DISCOUNT'),
			'payment_processor' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_PAYMENT_PROCESSOR'),
			'course_status' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_COURSE_STATUS'),
			'payment_status' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_PAYMENT_STATUS'),
			'free_lesson_attempted' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_FREE_LESSON_ATTEMPTED', 'not_show_hide' => false),
			'free_lesson_completed' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_FREE_LESSON_COMPLETED', 'not_show_hide' => false),
			'order_tax' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_ORDER_TAX'),
			'country_code' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_COUNTRY_NAME'),
			'state_code' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_STATE_NAME'),
			'city' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_CITY_NAME'),
			'order_id' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_ORDER_ID'),
			'phone' => array('title' => 'PLG_TJREPORTS_PAIDCOURSESREPORT_PHONE')
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
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_PAIDCOURSE_TITLE'));

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
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.3.14
	 */
	public function displayFilters()
	{
		$reportOptions  = TjlmsHelper::getReportFilterValues($this, $selected, $created_by, $myTeam);

		JLoader::import('components.com_tjlms.models.reports', JPATH_ADMINISTRATOR);
		$TjlmsModelReports  = new TjlmsModelReports;
		$catFilter          = $TjlmsModelReports->getCatFilter();
		$userFilter         = $TjlmsModelReports->getUserFilter($myTeam);
		$courseFilter       = $TjlmsModelReports->getCourseFilter($created_by);
		$paymentProcessorFilter = $TjlmsModelReports->getPaymentProcessorFilter();

		JLoader::register('TjlmsModelOrders', JPATH_ADMINISTRATOR . '/components/com_tjlms/models/orders.php');
		$ordersModel = new TjlmsModelOrders;
		$paymentStatusArray = array();
		$paymentStatusArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_STATUS'));
		$statusData = $ordersModel->getPaymentStatusFilter();
		$paymentStatusArray = array_merge($paymentStatusArray, $statusData);

		$statusArray = array();
		$statusArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_STATUS'));
		$statusArray[] = HTMLHelper::_('select.option', 'I', Text::_('COM_TJLMS_LESSONSTATUS_INCOMPLETE'));
		$statusArray[] = HTMLHelper::_('select.option', 'C', Text::_('COM_TJLMS_FILTER_STATUS_COMPLETED'));

		if (!class_exists('TjlmsModelcourses'))
		{
			$path = JPATH_SITE . '/components/com_tjlms/models/courses.php';
			JLoader::register('TjlmsModelcourses', $path);
		}

		$tjlmsModelcourses = new TjlmsModelcourses;

		$nameUserNameFilter = array();
		$nameUserNameFilter[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_USER'));

		$courseCreators = $tjlmsModelcourses->getCourseCreators();

		if (!empty($courseCreators))
		{
			$nameUserNameFilter = array_merge($nameUserNameFilter, $courseCreators);
		}

		$dispFilters = array(
			array(
				'course_id' => array(
					'search_type' => 'text', 'type' => 'equal', 'searchin' => 'c.id'
				),
				'user_email' => array(
					'search_type' => 'text', 'type' => 'equal', 'searchin' => 'u.email'
				),
				'title' => array(
					'search_type' => 'select', 'select_options' => $courseFilter, 'type' => 'equal', 'searchin' => 'c.id'
				),
				'enrolled_on_time' => array(
					'search_type' => 'calendar',
					'searchin' => 'eu.enrolled_on_time',
					'attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')
				),
				'end_time' => array(
					'search_type' => 'calendar',
					'searchin' => 'eu.end_time',
					'attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')
				),
				'cdate' => array(
					'search_type' => 'date.range',
					'searchin' => 'cdate',
					'cdate_from' => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')),
					'cdate_to' => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'))
				),
				'name' => array(
					'search_type' => 'select', 'select_options' => $userFilter, 'type' => 'equal', 'searchin' => 'o.user_id'
				),
				'course_status' => array(
					'search_type' => 'select', 'select_options' => $statusArray, 'type' => 'equal', 'searchin' => 'ct.status'
				),
				'payment_status' => array(
					'search_type' => 'select', 'select_options' => $paymentStatusArray, 'type' => 'equal', 'searchin' => 'o.status'
				),
				'payment_processor' => array(
					'search_type' => 'select', 'select_options' => $paymentProcessorFilter, 'type' => 'equal', 'searchin' => 'o.processor'
				),
				'created_by' => array(
					'search_type' => 'select', 'select_options' => $nameUserNameFilter, 'type' => 'equal', 'searchin' => 'c.created_by'
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

		// Joomla fields integration
		// Call parent function to set filters for custom fields
		if (method_exists(get_parent_class($this), 'setCustomFieldsDisplayFilters'))
		{
			parent::setCustomFieldsDisplayFilters($dispFilters);
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
		$filters   = $this->getState('filters');
		$colToshow = $this->getState('colToshow');
		$user     = Factory::getUser();
		$userId   = $user->id;

		$lmsparams = ComponentHelper::getParams('com_tjlms');
		$showNameOrUsername = $lmsparams->get('show_user_or_username', 'name');

		$query->select(
		array('c.id as course_id,c.title as title, eu.user_id, u.name as name, eu.enrolled_on_time, eu.end_time, u.email as user_email,
		o.cdate, o.amount as order_value, o.coupon_code, o.coupon_discount, o.processor as payment_processor,
		ct.status as courseStatus, o.order_tax,tju.country_code, tju.state_code, tju.city, o.order_id, tju.phone,
		(
		CASE
		 WHEN ct.status = "C" or ct.status = "c" THEN "Complete"
		 WHEN ct.status = "I" or ct.status = "i" THEN "Incomplete"
		ELSE "-"
		END
		) as course_status,
		o.status as order_statue,
		(
		CASE
		 WHEN o.status = "C" THEN "Complete"
		 WHEN o.status = "RF" THEN "Refunded"
		 WHEN o.status = "P" THEN "Pending"
		 WHEN o.status = "D" THEN "Declined"
		 WHEN o.status = "E" THEN "Failed"
		 WHEN o.status = "UR" THEN "Under Review"
		 WHEN o.status = "CRV" THEN "Cancel Reversed"
		 WHEN o.status = "RV" THEN "Reversed"
		 WHEN o.status = "I" THEN "Initiated"
		ELSE "-"
		END) as payment_status')
		);
		$query->from($db->quoteName('#__tjlms_orders') . 'AS o');
		$query->join('INNER', $db->quoteName('#__tjlms_courses', 'c') . ' ON (' . $db->quoteName('o.course_id') . ' = ' . $db->quoteName('c.id') . ')');
		$query->join('LEFT', $db->quoteName('#__tjlms_enrolled_users', 'eu') . ' ON (' . $db->quoteName('eu.id') . ' = '
			. $db->quoteName('o.enrollment_id') . ')');
		$query->join('LEFT', $db->quoteName('#__tjlms_course_track', 'ct') .
			' ON (' . $db->quoteName('o.user_id') . ' = ' . $db->quoteName('ct.user_id') .
			'AND' . $db->quoteName('o.course_id') . ' = ' . $db->quoteName('ct.course_id') . ')');
		$query->join('INNER', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('u.id') . ' = ' . $db->quoteName('o.user_id') . ')');
		$query->join('INNER', $db->quoteName('#__tjlms_users', 'tju') . ' ON (' . $db->quoteName('tju.order_id') . ' = ' . $db->quoteName('o.id') . ')');
		$query->where($db->quoteName('c.type') . ' = ' . (int) 1);
		$query->where($db->quoteName('c.state') . ' = ' . (int) 1);
		$query->where($db->quoteName('u.block') . ' = ' . (int) 0);

		if (in_array('cat_title', $colToshow))
		{
			$query->join('INNER', $db->quoteName('#__categories', 'cat') . ' ON (' . $db->quoteName('c.catid') . ' = ' . $db->quoteName('cat.id') . ')');
		}

		if (in_array('created_by', $colToshow))
		{
			$query->select($db->quoteName('us.block'));
			$query->select($db->quoteName('us.' . $showNameOrUsername, 'uname'));
			$query->join('LEFT', $db->quoteName('#__users', 'us') . ' ON (' . $db->quoteName('c.created_by') . ' = ' . $db->quoteName('us.id') . ')');
		}

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
			$query->where('o.user_id IN(' . implode(',', $hasUsers) . ')');
		}
		elseif (!$viewAll)
		{
			$query->where('o.user_id=0');
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

		$db        = $this->_db;
		$colToshow = $this->getState('colToshow');

		$lmsparams = ComponentHelper::getParams('com_tjlms');
		$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');
		$this->techjoomlacommon = new TechjoomlaCommon;
		$comtjlmsHelper = new comtjlmsHelper;

		$path = JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';

		if (!class_exists('ComtjlmstrackingHelper'))
		{
			JLoader::register('ComtjlmstrackingHelper', $path);
			JLoader::load('ComtjlmstrackingHelper');
		}

		foreach ($items as $key => $item)
		{
			$ComtjlmstrackingHelper = new ComtjlmstrackingHelper;

			if (in_array('enrolled_on_time', $colToshow))
			{
				if ($item['enrolled_on_time'] == '0000-00-00 00:00:00' || $item['enrolled_on_time'] == '')
				{
					$item['enrolled_on_time'] = '-';
				}
				else
				{
					$item['enrolled_on_time'] = $this->techjoomlacommon->getDateInLocal($item['enrolled_on_time'], 0, $date_format_show);
				}
			}

			if (in_array('cdate', $colToshow))
			{
				if ($item['cdate'] == '0000-00-00 00:00:00' || $item['cdate'] == '')
				{
					$item['cdate'] = '-';
				}
				else
				{
					$item['cdate'] = $this->techjoomlacommon->getDateInLocal($item['cdate'], 0, $date_format_show);
				}
			}

			if (in_array('end_time', $colToshow))
			{
				if ($item['end_time'] == '0000-00-00 00:00:00' || $item['end_time'] == '')
				{
					$item['end_time'] = '-';
				}
				else
				{
					$item['end_time'] = $this->techjoomlacommon->getDateInLocal($item['end_time'], 0, $date_format_show);
				}
			}

			if (in_array('order_value', $colToshow))
			{
				if ($item['order_value'] == '')
				{
					$item['order_value'] = '-';
				}
			}

			if (in_array('coupon_code', $colToshow))
			{
				if ($item['coupon_code'] == '')
				{
					$item['coupon_code'] = '-';
				}
			}

			if (in_array('coupon_discount', $colToshow))
			{
				if ($item['coupon_discount'] == '')
				{
					$item['coupon_discount'] = '-';
				}
			}

			if (in_array('payment_processor', $colToshow))
			{
				if ($item['payment_processor'] == '')
				{
					$item['payment_processor'] = '-';
				}
			}

			if (in_array('completion', $colToshow))
			{
				// Get %completion
				$progress = $ComtjlmstrackingHelper->getCourseTrackEntry($item['id'], $item['user_id']);
				$item['completion'] = floor($progress['complitionPercent']);
			}

			if (in_array('free_lesson_attempted', $colToshow) || in_array('free_lesson_completed', $colToshow))
			{
				// Get course status
				$query = $db->getQuery(true);
				$query->select('l.id as lesson_id,l.attempts_grade, lt.*');
				$query->from($db->quoteName('#__tjlms_lessons', 'l'));
				$query->join('INNER',
				$db->quoteName('#__tjlms_lesson_track', 'lt') . ' ON (' . $db->quoteName('lt.lesson_id') . ' = ' . $db->quoteName('l.id') . ')');
				$query->where($db->quoteName('lt.user_id') . ' = ' . (int) $item['user_id']);
				$query->where($db->quoteName('l.course_id') . ' = ' . (int) $item['course_id']);
				$query->where($db->quoteName('l.free_lesson') . ' = ' . (int) 1);
				$query->group($db->quoteName('l.id'));
				$db->setQuery($query);
				$free_lesson_data = $db->loadObjectList();

				$item['free_lesson_attempted'] = '0';
				$item['free_lesson_completed'] = '0';

				$lesson_complete_count = 0;

				foreach ($free_lesson_data as $lesson)
				{
					$lessonData = new stdclass;
					$lessonData->id = $lesson->lesson_id;
					$lessonData->attempts_grade = $lesson->attempts_grade;
					$lessonTrackdata = $ComtjlmstrackingHelper->getLessonattemptsGrading($lessonData, $lesson->user_id);

					if (!empty($lessonTrackdata))
					{
						if ($lessonTrackdata->lesson_status == 'completed' || $lessonTrackdata->lesson_status == 'passed')
						{
							$lesson_complete_count++;
						}
					}
				}

				if (!empty($free_lesson_data))
				{
					$item['free_lesson_attempted'] = count($free_lesson_data);
					$item['free_lesson_completed'] = $lesson_complete_count;
				}
			}

			if (in_array('country_code', $colToshow))
			{
				if ($item['country_code'])
				{
					$item['country_code'] = $comtjlmsHelper->getCountryById($item['country_code']);
				}
				else
				{
					$item['country_code'] = '-';
				}
			}

			if (in_array('state_code', $colToshow))
			{
				if ($item['state_code'])
				{
					$item['state_code'] = $comtjlmsHelper->getRegionById($item['state_code']);
				}
				else
				{
					$item['state_code'] = '-';
				}
			}

			if (in_array('created_by', $colToshow))
			{
				$item['created_by'] = $item['uname'];

				if (empty($item['uname']) || ($item['block'] == 1))
				{
					$item['created_by'] = Text::_('COM_TJLMS_BLOCKED_USER');
				}
			}

			$items[$key] = $item;
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
			array('name' => 'name', 'label' => Text::_('PLG_TJREPORTS_PAIDCOURSESREPORT_NAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'created_by', 'label' => Text::_('JGLOBAL_FIELD_CREATED_BY_LABEL'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'enrolled_on_time', 'label' => Text::_('PLG_TJREPORTS_PAIDCOURSESREPORT_ENROLLED_ON_TIME'),
				'dataType' => 'STRING',
				'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'end_time', 'label' => Text::_('PLG_TJREPORTS_PAIDCOURSESREPORT_END_TIME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'user_email', 'label' => Text::_('PLG_TJREPORTS_PAIDCOURSESREPORT_EMAIL'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'cdate', 'label' => Text::_('PLG_TJREPORTS_PAIDCOURSESREPORT_PAID_ON'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'order_value', 'label' => Text::_('PLG_TJREPORTS_PAIDCOURSESREPORT_ORDER_VALUE'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'coupon_code', 'label' => Text::_('PLG_TJREPORTS_PAIDCOURSESREPORT_COUPON_CODE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'coupon_discount', 'label' => Text::_('PLG_TJREPORTS_PAIDCOURSESREPORT_COUPON_DISCOUNT'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'payment_processor', 'label' => Text::_('PLG_TJREPORTS_PAIDCOURSESREPORT_PAYMENT_PROCESSOR'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'course_status', 'label' => Text::_('PLG_TJREPORTS_PAIDCOURSESREPORT_COURSE_STATUS'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'payment_status', 'label' => Text::_('PLG_TJREPORTS_PAIDCOURSESREPORT_PAYMENT_STATUS'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'free_lesson_attempted', 'label' => Text::_('PLG_TJREPORTS_PAIDCOURSESREPORT_FREE_LESSON_ATTEMPTED'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'order_tax', 'label' => Text::_('PLG_TJREPORTS_PAIDCOURSESREPORT_ORDER_TAX'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'country_code', 'label' => Text::_('PLG_TJREPORTS_PAIDCOURSESREPORT_COUNTRY_NAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'state_code', 'label' => Text::_('PLG_TJREPORTS_PAIDCOURSESREPORT_STATE_NAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
		);
	}
}
