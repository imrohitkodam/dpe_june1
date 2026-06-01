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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Attempt report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelActivityreport extends TjreportsModelReports
{
	public $columns = array();

	protected $default_order = 'name';

	protected $default_order_dir = 'ASC';

	protected $showNameOrUsername = '';
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JModelLegacy
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		// Joomla fields integration
		// Define custom fields table, alias, and table.column to join on
		$this->customFieldsTable       = '#__tjreports_com_users_user';
		$this->customFieldsTableAlias  = 'tjrcuu';
		$this->customFieldsQueryJoinOn = 'a.actor_id';

		if (method_exists($this, 'tableExists'))
		{
			$this->customFieldsTableExists = $this->tableExists();
		}

		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);

		$lmsparams = ComponentHelper::getParams('com_tjlms');
		$this->showNameOrUsername = $lmsparams->get('show_user_or_username', 'name');

		if ($this->showNameOrUsername == 'name')
		{
			$th = 'COM_TJLMS_ENROLMENT_USER_NAME';
		}
		else
		{
			$th = 'COM_TJLMS_REPORT_USERUSERNAME';
		}

		$this->columns = array(
			'activity' => array('title' => 'COM_TJLMS_ACTIVITYREPORT_ACTIVITY', 'table_column' => '', 'disable_sorting' => true),
			'user_id' => array('title' => 'COM_TJLMS_ENROLMENT_USERID', 'table_column' => 'u.id'),
			$this->showNameOrUsername => array('table_column' => 'u.' . $this->showNameOrUsername, 'title' => $th),
			'user_email' => array('title' => 'COM_TJLMS_ENROLMENT_USER_EMAIL_ADDRESS', 'table_column' => 'u.email'),
			'added_time' => array('table_column' => 'a.added_time','title' => 'COM_TJLMS_ACTIVITYREPORT_ADDED_TIME'),
			'context' => array('table_column' => 'a.element','title' => 'COM_TJLMS_ACTIVITYREPORT_CONTEXT'),
			'action' => array('table_column' => 'a.action', 'title' => 'COM_TJLMS_ACTIVITYREPORT_ACTION')
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
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_ACTIVITYREPORT_TITLE'));

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
		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);

		$reportOptions  = TjlmsHelper::getReportFilterValues($this, $selected, $created_by, $myTeam);

		JLoader::import('components.com_tjlms.models.reports', JPATH_ADMINISTRATOR);
		$TjlmsModelReports 	= new TjlmsModelReports;
		$lmsparams = ComponentHelper::getParams('com_tjlms');
		$showNameOrUsername = $lmsparams->get('show_user_or_username', 'name');

		if ($showNameOrUsername == 'name')
		{
			$nameUserNameFilter 		= $TjlmsModelReports->getNameFilter($myTeam);
		}
		else
		{
			$nameUserNameFilter 		= $TjlmsModelReports->getUserFilter($myTeam);
		}

		$actionArray = array();
		$actionArray[] = JHTML::_('select.option', '', Text::_('COM_TJLMS_SELONE_ACTION'));
		$actionArray[] = JHTML::_('select.option', 'LOGIN', Text::_('COM_TJLMS_ACTION_LOGIN'));
		$actionArray[] = JHTML::_('select.option', 'LOGOUT', Text::_('COM_TJLMS_ACTION_LOGOUT'));
		$actionArray[] = JHTML::_('select.option', 'ENROLL', Text::_('COM_TJLMS_ACTION_ENROLL'));
		$actionArray[] = JHTML::_('select.option', 'ATTEMPT', Text::_('COM_TJLMS_ACTION_ATTEMPT'));
		$actionArray[] = JHTML::_('select.option', 'ATTEMPT_END', Text::_('COM_TJLMS_ACTION_ATTEMPT_END'));
		$actionArray[] = JHTML::_('select.option', 'COURSE_CREATED', Text::_('COM_TJLMS_ACTION_COURSE_CREATED'));
		$actionArray[] = JHTML::_('select.option', 'COURSE_RECOMMENDED', Text::_('COM_TJLMS_ACTION_COURSE_RECOMMENDED'));
		$actionArray[] = JHTML::_('select.option', 'COURSE_COMPLETED', Text::_('COM_TJLMS_ACTION_COURSE_COURSE_COMPLETED'));
		$actionArray[] = JHTML::_('select.option', 'CERTIFICATE_EXPIRED', Text::_('COM_TJLMS_ACTION_COURSE_CERTIFICATE_EXPIRED'));

		$dispFilters = array(
			array(
				'user_id' => array(
					'search_type' => 'text', 'type' => 'equal', 'searchin' => 'u.id'
				),
				$showNameOrUsername => array(
					'search_type' => 'select', 'select_options' => $nameUserNameFilter, 'type' => 'equal', 'searchin' => 'u.id'
				),
				'user_email' => array(
					'search_type' => 'text', 'type' => 'equal', 'searchin' => 'u.email'
				),
				'action' => array(
					'search_type' => 'select', 'select_options' => $actionArray, 'type' => 'equal', 'searchin' => 'a.action'
				),
				'context' => array(
					'search_type' => 'text','type' => 'equal', 'searchin' => 'a.element'
				)
			),
			array(
				'added_time' => array(
					'search_type' => 'date.range',
					'searchin' => 'added_time',
					'added_time_from' => array('attrib' => array('placeholder' => 'FROM (YYYY-MM-DD)')),
					'added_time_to' => array('attrib' => array('placeholder' => 'TO (YYYY-MM-DD)')),
				)
			)
		);

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

		$colToshow = (array) $this->getState('colToshow');
		$filters = $this->getState('filters');
		$user     = Factory::getUser();
		$userId   = $user->id;

		$query->select('c.title, a.params,a.element_url,a.parent_id');
		$query->select('u.' . $this->showNameOrUsername, 'title');
		$query->select('a.action', 'action');

		// Select the required fields from the table.
		$query->from('`#__tjlms_activities` AS a');
		$query->join('LEFT', $db->quoteName('#__tjlms_courses', 'c') . ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.parent_id') . ')');

		$query->join('INNER', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('a.actor_id') . ' = ' . $db->quoteName('u.id') . ')');
		$query->where($db->quoteName('a.actor_id') . ' <> ' . $db->quote(0));
		$query->where($db->quoteName('a.action') . '<>' . $db->quote("RECOMMENDED"));

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
		// Add additional columns which are not part of the query
		$items = parent::getItems();

		if (empty($items))
		{
			return;
		}

		$lmsparams = ComponentHelper::getParams('com_tjlms');
		$dateFormatShow = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');
		$showNameOrUsername = $lmsparams->get('show_user_or_username', 'name');

		if (!class_exists('ComtjlmstrackingHelper'))
		{
			JLoader::register('ComtjlmstrackingHelper', JPATH_SITE . '/components/com_tjlms/helpers/tracking.php');
			JLoader::load('ComtjlmstrackingHelper');
		}

		$trackingHrlper = new ComtjlmstrackingHelper;
		$colToshow = (array) $this->getState('colToshow');

		jimport('techjoomla.common');
		$tjCommon = new TechjoomlaCommon;

		foreach ($items as &$item)
		{
			if (empty($item['context']))
			{
				$item['context'] = ' - ';
			}

			switch ($item['action'])
			{
					case "ENROLL":
						$course_link  = "<a href='" . Uri::root() . $item['element_url'] . "' target='_blank'>" . $item['context'] . "</a>";
						$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ENROLL', $item[$showNameOrUsername], $course_link);
						$item['action'] = Text::_('COM_TJLMS_ACTION_ENROLL');
						break;

					case "ATTEMPT":
						$lesson_link = "<em><b>" . $item['context'] . "</b></em>";
						$course_url  = Uri::root() . 'index.php?option=com_tjlms&view=course&id=' . $item['parent_id'];
						$course_link = "<a href='" . $course_url . "' target='_blank'>" . $item['title'] . "</a>";
						$params       = json_decode($item['params']);
						$attempt      = $params->attempt;
						$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ATTEMPT', $item[$showNameOrUsername], $attempt, $lesson_link, $course_link);
						$item['action'] = Text::_('COM_TJLMS_ACTION_ATTEMPT');
						break;

					case "ATTEMPT_END":
						$lesson_link = "<em><b>" . $item['context'] . "</b></em>";
						$course_url  = Uri::root() . 'index.php?option=com_tjlms&view=course&id=' . $item['parent_id'];
						$course_link = "<a href='" . $course_url . "' target='_blank'>" . $item['title'] . "</a>";
						$params       = json_decode($item['params']);
						$attempt      = $params->attempt;
						$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ATTEMPT_END', $item[$showNameOrUsername], $attempt, $lesson_link, $course_link);
						$item['action'] = Text::_('COM_TJLMS_ACTION_ATTEMPT_END');
						break;

					case "COURSE_CREATED":
						$course_link  = "<a href='" . Uri::root() . $item['element_url'] . "' target='_blank'>" . $item['context'] . "</a>";
						$text_to_show = Text::sprintf('COM_TJLMS_COURSE_CREATED_STREAM', $item[$showNameOrUsername], $course_link);
						$item['action'] = Text::_('COM_TJLMS_ACTION_COURSE_CREATED');
						break;

					case "COURSE_COMPLETED":
							$course_link  = "<a href='" . Uri::root() . $item['element_url'] . "' target='_blank'>" . $item['context'] . "</a>";
							$text_to_show = Text::sprintf('COM_TJLMS_COURSE_COMPLETED_STREAM', $item[$showNameOrUsername], $course_link);
							$item['action'] = Text::_('COM_TJLMS_ACTION_COURSE_COURSE_COMPLETED');
						break;

					case "COURSE_RECOMMENDED":
							$params = json_decode($item['params']);
							$text_to_show = '';
							$user_name = (isset($item[$showNameOrUsername]) ? $item[$showNameOrUsername] : '');
							$targetUserName = Text::_('COM_TJLMS_BLOCKED_USER');

							if (User::getTable()->load($params->target_id))
							{
								$targetUser = Factory::getUser($params->target_id);

								if ($targetUser->block == 0 )
								{
									$targetUserName = Factory::getUser($params->target_id)->name;
								}
							}

							if (isset($params->target_id))
							{
								$course_link  = "<a href='" . Uri::root() . $item['element_url'] . "' target='_blank'>" . $item['context'] . "</a>";
								$text_to_show = Text::sprintf('COM_TJLMS_ON_RECOMMEND_COURSE_AS_LMS', $user_name, $course_link, $targetUserName);
								$item['action'] = Text::_('COM_TJLMS_ACTION_COURSE_RECOMMENDED');
							}
						break;

					case "LOGIN":
							$user_name = (isset($item[$showNameOrUsername]) ? $item[$showNameOrUsername] : '');
							$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_LOGGEDIN', $user_name);
							$item['action'] = Text::_('COM_TJLMS_ACTION_LOGIN');
						break;

					case "LOGOUT":
							$user_name = (isset($item[$showNameOrUsername]) ? $item[$showNameOrUsername] : '');
							$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_LOGGEDOUT', $user_name);
							$item['action'] = Text::_('COM_TJLMS_ACTION_LOGOUT');
						break;

					case "CERTIFICATE_EXPIRED":
						$course_link  = "<a href='" . Uri::root() . $item['element_url'] . "' target='_blank'>" . $item['context'] . "</a>";
						$text_to_show = Text::sprintf('COM_TJLMS_COURSE_CERTIFICATE_EXPIRED', $item[$showNameOrUsername], $course_link);
						$item['action'] = Text::_('COM_TJLMS_ACTION_COURSE_CERTIFICATE_EXPIRED');
					break;

					default:
						$text_to_show = '';
						break;
			}

			$time = ' <small><em>' . $trackingHrlper->time_elapsed_string($item['added_time'], true) . '</em></small>';

			if (empty($item['added_time']) || $item['added_time'] == '0000-00-00 00:00:00')
			{
				$item['added_time'] = ' - ';
			}
			else
			{
				$item['added_time'] = $tjCommon->getDateInLocal($item['added_time'], 0, $dateFormatShow);
			}

			$item['activity'] = $text_to_show . $time;
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
	 * @since   1.3.30
	 */
	public function getGDSFields()
	{
		$return = array(
			array('name' => 'activity', 'label' => Text::_('COM_TJLMS_ACTIVITYREPORT_ACTIVITY'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'user_id', 'label' => Text::_('COM_TJLMS_ENROLMENT_USERID'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'user_email', 'label' => Text::_('COM_TJLMS_ENROLMENT_USER_EMAIL_ADDRESS'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'added_time', 'label' => Text::_('COM_TJLMS_ACTIVITYREPORT_ADDED_TIME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'context', 'label' => Text::_('COM_TJLMS_ACTIVITYREPORT_CONTEXT'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'action', 'label' => Text::_('COM_TJLMS_ACTIVITYREPORT_ACTION'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
		);

		return $return;
	}
}
