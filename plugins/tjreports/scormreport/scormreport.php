<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJReport,scormreport
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

jimport('techjoomla.common');

/**
 * SCORM report plugin of TJReport
 *
 * @since  1.3.38
 */
class TjreportsModelScormreport extends TjreportsModelReports
{
	protected $default_order = 'elementid';

	protected $default_order_dir = 'DESC';

	public $showSearchResetButton = 1;

	protected $escape = 'htmlspecialchars';

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  1.3.38
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JModelLegacy
	 * @since 1.3.38
	 */
	public function __construct($config = array())
	{
		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

		$lang = Factory::getLanguage();

		$lang->load('com_tjlms', JPATH_SITE . '/administrator');

		$this->columns = array(
			'elementid' => array('table_column' => 'tsst.id', 'title' => 'PLG_TJREPORTS_SCORMREPORT_ELEMENT_ID', 'not_show_hide' => false),
			'username' => array('table_column' => 'u.username', 'title' => 'PLG_TJREPORTS_SCORMREPORT_USERNAME'),
			'email' => array('table_column' => 'u.email', 'title' => 'PLG_TJREPORTS_SCORMREPORT_EMAIL'),
			'attempt' => array('table_column' => 'tsst.attempt', 'title' => 'PLG_TJREPORTS_SCORMREPORT_ATTEMP'),
			'interactionid' => array('title' => 'PLG_TJREPORTS_SCORMREPORT_INTERACTION_ID', 'disable_sorting' => true),
			'userresponse' => array('title' => 'PLG_TJREPORTS_SCORMREPORT_USER_RESPONSE', 'disable_sorting' => true),
			'correctresponse' => array('title' => 'PLG_TJREPORTS_SCORMREPORT_CORRECT_RESPONSE', 'disable_sorting' => true),
			'correctincorrect' => array('title' => 'PLG_TJREPORTS_SCORMREPORT_CORRECT_INCORRECT', 'disable_sorting' => true),
			'interactionduration' => array('title' => 'PLG_TJREPORTS_SCORMREPORT_INTERACTION_DURATION', 'disable_sorting' => true),
			'interactionstart' => array('title' => 'PLG_TJREPORTS_SCORMREPORT_INTERACTION_START', 'disable_sorting' => true),
			'lesson_id' => array('table_column' => 'tl.id', 'title' => 'PLG_TJREPORTS_SCORMREPORT_LESSONID'),
			'lesson_title' => array('table_column' => 'tl.title', 'title' => 'PLG_TJREPORTS_SCORMREPORT_LESSONNAME'),
			'timeend' => array('table_column' => 'tlt.timeend', 'title' => 'PLG_TJREPORTS_SCORMREPORT_DATE','not_show_hide' => false )
		);

		$this->techjoomlacommon = new TechjoomlaCommon;

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return array Client
	 *
	 * @since 1.3.38
	 * */
	public function getPluginDetail()
	{
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_SCORMREPORT_TITLE'));

		return $detail;
	}

	/**
	 * Add stylesheets
	 *
	 * @return ARRAY Styles url
	 *
	 * @since 1.3.38
	 * */
	public function getStyles()
	{
		return array(
			Uri::root(true) . '/media/com_tjlms/css/tjlms_backend.css',
			Uri::root(true) . '/media/com_tjlms/font-awesome/css/font-awesome.min.css'
		);
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since 1.3.38
	 */
	public function getItems()
	{
		$input = Factory::getApplication()->input;
		$tpl = $input->get('tpl', 'default', 'string');
		$tpl = ($tpl == 'default' || $tpl == 'submit') ? null : $tpl;

		// Add additional columns which are not part of the query
		$items          = parent::getItems();
		$colToshow      = $this->getState('colToshow');
		$lmsparams      = ComponentHelper::getParams('com_tjlms');
		$dateFormatShow = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		foreach ($items as &$item)
		{
			if (in_array('timeend', $colToshow))
			{
				if ($item['timeend'] == '0000-00-00 00:00:00' || $item['timeend'] == '')
				{
					$item['timeend'] = '-';
				}
				else
				{
					$item['timeend'] = $this->techjoomlacommon->getDateInLocal($item['timeend'], 0, $dateFormatShow);
				}
			}

			$questionId = (int) filter_var($item['element'], FILTER_SANITIZE_NUMBER_INT);

			$elementsData = $this->getUserInteractions($item['userid'], $item['scorm_id'], $questionId, $item['attempt']);

			foreach ($elementsData as $element)
			{
				if ($element['element'] == "cmi.interactions_" . $questionId . ".id" || $element['element'] == "cmi.interactions." . $questionId . ".id")
				{
					$item['interactionid'] = $element['value'];
				}
				elseif ($element['element'] == "cmi.interactions_" . $questionId . ".student_response"
					|| $element['element'] == "cmi.interactions." . $questionId . ".student_response"
					|| $element['element'] == "cmi.interactions_" . $questionId . ".learner_response"
					|| $element['element'] == "cmi.interactions." . $questionId . ".learner_response")
				{
					$item['userresponse'] = $this->escape($element['value']);
				}
				elseif ($element['element'] == "cmi.interactions_" . $questionId . ".correct_responses_0.pattern"
					|| $element['element'] == "cmi.interactions." . $questionId . ".correct_responses.0.pattern")
				{
					$item['correctresponse'] = $this->escape($element['value']);
				}
				elseif ($element['element'] == "cmi.interactions_" . $questionId . ".result"
					|| $element['element'] == "cmi.interactions." . $questionId . ".result")
				{
					$item['correctincorrect'] = $element['value'];
				}
				elseif ($element['element'] == "cmi.interactions_" . $questionId . ".latency"
					|| $element['element'] == "cmi.interactions." . $questionId . ".latency")
				{
					$item['interactionduration'] = $element['value'];
				}
				elseif ($element['element'] == "cmi.interactions_" . $questionId . ".time"
					|| $element['element'] == "cmi.interactions." . $questionId . ".time")
				{
					$item['interactionstart'] = $element['value'];
				}
			}
		}

		$items = $this->sortCustomColumns($items);

		// Show the summary report only if plugin config is set to Yes & user clicks on Summary Report button
		if ($this->showSummaryReport === 'Yes' && $tpl != null)
		{
			$items = $this->showSummaryReport($items);
		}

		return $items;
	}

	/**
	 * Method to get a JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  JDatabaseQuery  A JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.3.38
	 */
	protected function getListQuery()
	{
		$db        = $this->_db;
		$query     = parent::getListQuery();
		$filters = $this->getState('filters');

		$query->select(array('tsst.userid','tsst.scorm_id','tsst.element','tsst.attempt'));

		if ($filters['attempt_state'] == '0')
		{
			$query->from('#__tjlms_scorm_scoes_track_archive AS tsst');
		}
		else
		{
			$query->from('#__tjlms_scorm_scoes_track AS tsst');
		}

		$query->join('LEFT', $db->quoteName('#__tjlms_scorm', 'ts') . ' ON ' . $db->quoteName('ts.id') . " = " . $db->quoteName('tsst.scorm_id'));

		$query->join('LEFT',
		$db->quoteName('#__tjlms_lessons', 'tl') . ' ON ' . $db->quoteName('tl.id') . " = " . $db->quoteName('ts.lesson_id')
		);

		$query->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('tsst.userid') . " = " . $db->quoteName('u.id'));

		$query->where($db->quoteName('element') . ' LIKE ' . $db->quote('cmi.interactions%.id'));

		if ($filters['attempt_state'] == '0')
		{
			$query->join(
			'INNER', $db->quoteName('#__tjlms_lesson_track_archive', 'tlt') . ' ON ' . $db->quoteName('tlt.user_id') . " = " . $db->quoteName('tsst.userid') .
			' AND ' . $db->quoteName('tlt.lesson_id') . " = " . $db->quoteName('tl.id') .
			' AND ' . $db->quoteName('tlt.attempt') . " = " . $db->quoteName('tsst.attempt')
			);
		}
		else
		{
			$query->join(
			'INNER', $db->quoteName('#__tjlms_lesson_track', 'tlt') . ' ON ' . $db->quoteName('tlt.user_id') . " = " . $db->quoteName('tsst.userid') .
			' AND ' . $db->quoteName('tlt.lesson_id') . " = " . $db->quoteName('tl.id') .
			' AND ' . $db->quoteName('tlt.attempt') . " = " . $db->quoteName('tsst.attempt')
			);
		}

		return $query;
	}

	/**
	 * Create an array of filters
	 *
	 * @return    void
	 *
	 * @since    1.3.38
	 */
	public function displayFilters()
	{
		JLoader::import('components.com_tjlms.models.reports', JPATH_ADMINISTRATOR);
		$TjlmsModelReports = new TjlmsModelReports;
		$userFilter        = $TjlmsModelReports->getUserFilter($myTeam);
		$lessonFilter      = $this->getLessonFilter();
		$courseFilter      = $this->getCourseFilter();

		$groups  = HTMLHelper::_('user.groups', true);
		array_unshift($groups, HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_ENROLLED_USER_ACCESS')));

		$attemptStateArray = array();
		$attemptStateArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_STATE'));
		$attemptStateArray[] = HTMLHelper::_('select.option', '1', Text::_('COM_TJLMS_FILTER_STATE_ACTIVE'));
		$attemptStateArray[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_FILTER_STATE_EXPIRED'));

		$filters = $this->getState('filters');

		$dispFilters = array(
			array(
				'username' => array(
					'search_type' => 'select', 'select_options' => $userFilter, 'type' => 'equal', 'searchin' => 'u.id'
				),
			),
			array(
				'lesson_id' => array(
						'search_type' => 'select', 'select_options' => $lessonFilter, 'type' => 'equal', 'searchin' => 'tl.id'
				),
				'course_id' => array(
						'search_type' => 'select', 'select_options' => $courseFilter, 'type' => 'equal', 'searchin' => 'tl.course_id'
				),
				'timeend' => array(
					'search_type' => 'date.range',
					'searchin' => 'timeend',
					'timeend_from' => array('attrib' => array('placeholder' => 'From YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')),
					'timeend_to' => array('attrib' => array('placeholder' => 'To YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'))
				),
				'attempt_state' => array(
					'search_type' => 'select', 'select_options' => $attemptStateArray, 'type' => 'equal'
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
	 * Function to get the lesson filter
	 *
	 * @return  array
	 *
	 * @since 1.3.38
	 */
	public function getLessonFilter()
	{
		JLoader::import('components.com_tjlms.models.lessons', JPATH_ADMINISTRATOR);
		$lessonsModel = BaseDatabaseModel::getInstance('lessons', 'TjlmsModel', array('ignore_request' => true));
		$lessonsModel->setState('filter.format', 'scorm');
		$lessons = $lessonsModel->getItems();

		$lessonFilter   = array();
		$lessonFilter[] = HTMLHelper::_('select.option', '', Text::_('PLG_TJREPORTS_SCORMREPORT_SELECT_SCORM'));

		if (!empty($lessons))
		{
			foreach ($lessons as $eachlesson)
			{
				$lessonFilter[] = HTMLHelper::_('select.option', $eachlesson->id, $eachlesson->title);
			}
		}

		return $lessonFilter;
	}

	/**
	 * Function to get the course filter
	 *
	 * @return  array
	 *
	 * @since 1.3.38
	 */
	public function getCourseFilter()
	{
		$query = $this->_db->getQuery(true);

		$query->select('DISTINCT(tc.id) as id, tc.title');
		$query->from($this->_db->qn('#__tjlms_courses', 'tc'));
		$query->join(
			'LEFT', $this->_db->qn('#__tjlms_lessons', 'tl') . ' ON ' . $this->_db->quoteName('tl.course_id') . " = " . $this->_db->quoteName('tc.id')
		);
		$query->where($this->_db->qn('tl.format') . ' = ' . $this->_db->quote('scorm'));

		$this->_db->setQuery($query);
		$courses = $this->_db->loadObjectList();

		$courseFilter   = array();
		$courseFilter[] = HTMLHelper::_('select.option', '', Text::_('PLG_TJREPORTS_SCORMREPORT_SELECT_COURSE'));

		if (!empty($courses))
		{
			foreach ($courses as $eachcourse)
			{
				$courseFilter[] = HTMLHelper::_('select.option', $eachcourse->id, $eachcourse->title);
			}
		}

		return $courseFilter;
	}

	/**
	 * Function to get the user interaction
	 *
	 * @param   INT  $userId      user id
	 *
	 * @param   INT  $scormId     scorm id
	 *
	 * @param   INT  $questionId  question id
	 *
	 * @param   INT  $attempt     attempt
	 *
	 * @return  void|array
	 *
	 * @since 1.3.38
	 */
	private function getUserInteractions($userId, $scormId, $questionId, $attempt)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		if ($userId && $scormId)
		{
			$query->select(array('tsst.element','tsst.value'));
			$query->from($db->quoteName('#__tjlms_scorm_scoes_track', 'tsst'));
			$query->where($db->quoteName('tsst.userid') . '=' . (int) $userId);
			$query->where($db->quoteName('tsst.scorm_id') . '=' . (int) $scormId);
			$query->where($db->quoteName('tsst.attempt') . '=' . (int) $attempt);
			$query->where($db->quoteName('tsst.element') . ' LIKE ' . $db->quote('cmi.interactions%' . $questionId . '%'));

			$db->setQuery($query);
			$elements = $db->loadAssocList();

			return $elements;
		}
	}

	/**
	 * Method to get chart data to show summary report
	 *
	 * @param   ARRAY  $items  report data
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since 1.3.38
	 */

	public function showSummaryReport($items)
	{
		// If the data is available
		if (!empty($items))
		{
			// If the data has qustion types
			if (!empty($items[0]['interactionid']))
			{
				$chartData = array();
				$interactions = array();

				foreach ($items as $item)
				{
					if (!in_array($item['interactionid'], $interactions, true))
					{
						array_push($interactions, $item['interactionid']);
					}
				}

				$n = 0;

				// Check the for each question
				foreach ($interactions as $interaction)
				{
					$chartData[$n]['fieldId'] = $interaction;
					$chartData[$n]['fieldLable'] = str_replace('_', ' ', $interaction);
					$chartData[$n]['fieldType'] = 'radio';

					$resultOfAnswers = $this->getTotalAnsCountByIntraction($interaction, $items);
					$correctAnswersPercentage = ($resultOfAnswers['correctAnswerscount'] / $resultOfAnswers['totalAnswerscount']) * 100;

					$wrongAnswersPercentage = 100 - $correctAnswersPercentage;

					$chartData[$n]['chartData']['labels'] = array();

					foreach ($items as $item)
					{
						if (trim($item['interactionid']) == trim($interaction))
						{
							$chartData[$n]['correctResponse'] = $item['correctresponse'];
						}
					}

					$chartData[$n]['chartData']['labels'] = array("correct","incorrect");
					$chartData[$n]['labels'] = "'correct','incorrect'";
					$chartData[$n]['data'] = $correctAnswersPercentage . ',' . $wrongAnswersPercentage;

					$n++;
				}

				return $chartData;
			}
		}

		return false;
	}

	/**
	 * Escapes a value for output in a view script.
	 *
	 * If escaping mechanism is either htmlspecialchars or htmlentities, uses
	 * {@link $_encoding} setting.
	 *
	 * @param   mixed  $var  The output to escape.
	 *
	 * @return  mixed  The escaped value.
	 */
	public function escape($var)
	{
		if (in_array($this->escape, array('htmlspecialchars', 'htmlentities')))
		{
			return call_user_func($this->escape, $var, ENT_COMPAT, $this->_charset);
		}

		return call_user_func($this->_escape, $var);
	}

	/**
	 * Function to get the total answers count by interaction
	 *
	 * @param   INT    $interaction  interaction
	 *
	 * @param   ARRAY  $items        items
	 *
	 * @return  void|array
	 *
	 * @since 1.3.38
	 */
	public function getTotalAnsCountByIntraction($interaction, $items)
	{
		$correctAnswers = array();
		$totalAnswers = array();
		$result = array();

		if ($interaction)
		{
			foreach ($items as $item)
			{
				if (trim($item['interactionid']) == trim($interaction))
				{
					if ($item['correctincorrect'] == 'correct')
					{
						array_push($correctAnswers, $item['correctincorrect']);
					}

					array_push($totalAnswers, $item['correctincorrect']);
				}
			}
		}

		$result['totalAnswerscount'] = count($totalAnswers);
		$result['correctAnswerscount'] = count($correctAnswers);

		return $result;
	}
}
