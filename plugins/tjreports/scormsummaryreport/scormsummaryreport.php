<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJReport,scormsummaryreport
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

/**
 * SCORM summary report plugin of TJReport
 *
 * @since  1.3.38
 */
class TjreportsModelScormsummaryreport extends TjreportsModelReports
{
	protected $default_order = 'lesson_id';

	protected $default_order_dir = 'DESC';

	public $showSearchResetButton = -2;

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
			'interactionid'          => array('title' => 'PLG_TJREPORTS_SCORMSUMMARYREPORT_INTERACTION_ID', 'disable_sorting' => true),
			'correctresponse'          => array('title' => 'PLG_TJREPORTS_SCORMSUMMARYREPORT_CORRECT_RESPONSE', 'disable_sorting' => true),
			'correct'          => array('title' => 'PLG_TJREPORTS_SCORMSUMMARYREPORT_CORRECT', 'disable_sorting' => true),
			'incorrect'          => array('title' => 'PLG_TJREPORTS_SCORMSUMMARYREPORT_INCORRECT', 'disable_sorting' => true),
			'avginteractionduration'          => array('title' => 'PLG_TJREPORTS_SCORMSUMMARYREPORT_AVG_INTERACTION_DURATION', 'disable_sorting' => true),
			'lesson_id' => array('table_column' => 'tl.id', 'title' => 'PLG_TJREPORTS_SCORMSUMMARYREPORT_LESSONID'),
			'lesson_title' => array('table_column' => 'tl.title', 'title' => 'PLG_TJREPORTS_SCORMSUMMARYREPORT_LESSONNAME')
		);

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
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_SCORMSUMMARYREPORT_TITLE'));

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
		// Add additional columns which are not part of the query
		$items = parent::getItems();
		$usersInteractionData = $this->getInteractionsData();

		foreach ($items as &$item)
		{
			$resultOfAnswers = $this->getTotalAnsCountByIntraction($item, $usersInteractionData);

			$correctAnswersPercentage = ($resultOfAnswers['correctAnswerscount'] / $resultOfAnswers['totalAnswerscount']) * 100;
			$wrongAnswersPercentage = 100 - $correctAnswersPercentage;

			$item['correct'] = number_format($correctAnswersPercentage, 2);
			$item['incorrect'] = number_format($wrongAnswersPercentage, 2);

			$totalTime  = array();

			foreach ($usersInteractionData as $interactionData)
			{
				if ($item['interactionid'] == $interactionData['interactionid'] && $item['scorm_id'] == $interactionData['scorm_id'])
				{
					$item['correctresponse'] = $interactionData['correctresponse'];
					array_push($totalTime, $interactionData['interactionduration']);
				}
			}

			$totalAvgTime = $this->calculateAvgTime($totalTime);
			$item['avginteractionduration'] = $totalAvgTime;
		}

		$items = $this->sortCustomColumns($items);

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

		$query->select(array('tsst.value AS interactionid','tsst.scorm_id'));
		$query->from('#__tjlms_scorm_scoes_track AS tsst');

		$query->join('LEFT', $db->quoteName('#__tjlms_scorm', 'ts') . ' ON ' . $db->quoteName('ts.id') . " = " . $db->quoteName('tsst.scorm_id'));

		$query->join('LEFT',
		$db->quoteName('#__tjlms_lessons', 'tl') . ' ON ' . $db->quoteName('tl.id') . " = " . $db->quoteName('ts.lesson_id')
		);

		$query->where($db->quoteName('element') . ' LIKE ' . $db->quote('cmi.interactions%.id'));
		$query->group(array('interactionid', 'tl.id'));

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
		$lessonFilter[] = HTMLHelper::_('select.option', '', Text::_('PLG_TJREPORTS_SCORMSUMMARYREPORT_SELECT_SCORM'));

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
		$courseFilter[] = HTMLHelper::_('select.option', '', Text::_('PLG_TJREPORTS_SCORMSUMMARYREPORT_SELECT_COURSE'));

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
	 * Get all Interactions Data
	 *
	 * @return  void|array
	 *
	 * @since 1.3.38
	 */
	private function getInteractionsData()
	{
		$db        = $this->_db;
		$query = $db->getQuery(true);

		$query->select(array('tsst.userid','tsst.scorm_id','tsst.element'));
		$query->from('#__tjlms_scorm_scoes_track AS tsst');

		$query->join('LEFT', $db->quoteName('#__tjlms_scorm', 'ts') . ' ON ' . $db->quoteName('ts.id') . " = " . $db->quoteName('tsst.scorm_id'));

		$query->join('LEFT',
		$db->quoteName('#__tjlms_lessons', 'tl') . ' ON ' . $db->quoteName('tl.id') . " = " . $db->quoteName('ts.lesson_id')
		);

		$query->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('tsst.userid') . " = " . $db->quoteName('u.id'));

		$query->where($db->quoteName('element') . ' LIKE ' . $db->quote('cmi.interactions%.id'));

		$db->setQuery($query);

		$data = $db->loadAssocList();

		foreach ($data as &$item)
		{
			$questionId = (int) filter_var($item['element'], FILTER_SANITIZE_NUMBER_INT);

			$elementsData = $this->getUserInteractions($item['userid'], $item['scorm_id'], $questionId);

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
			}
		}

		return $data;
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
	 * @return  void|array
	 *
	 * @since 1.3.38
	 */
	private function getUserInteractions($userId, $scormId, $questionId)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		if ($userId && $scormId)
		{
			$query->select(array('tsst.element','tsst.value'));
			$query->from($db->quoteName('#__tjlms_scorm_scoes_track', 'tsst'));
			$query->where($db->quoteName('tsst.userid') . '=' . (int) $userId);
			$query->where($db->quoteName('tsst.scorm_id') . '=' . (int) $scormId);
			$query->where($db->quoteName('tsst.element') . ' LIKE ' . $db->quote('cmi.interactions%' . $questionId . '%'));

			$db->setQuery($query);
			$elements = $db->loadAssocList();

			return $elements;
		}
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
	 * @param   INT    $interactions  interactions
	 *
	 * @param   ARRAY  $items         items
	 *
	 * @return  void|array
	 *
	 * @since 1.3.38
	 */
	public function getTotalAnsCountByIntraction($interactions, $items)
	{
		$correctAnswers = array();
		$totalAnswers = array();
		$result = array();

		if (!empty($interactions) && !empty($items))
		{
			foreach ($items as $item)
			{
				if (trim($item['interactionid']) == trim($interactions['interactionid']) && $item['scorm_id'] == $interactions['scorm_id'])
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

	/**
	 * Function to calculate average time
	 *
	 * @param   INT  $totalTime  total Time
	 *
	 * @return  string
	 *
	 * @since 1.3.38
	 */
	public function calculateAvgTime($totalTime)
	{
		$total = 0;

		if (!empty($totalTime))
		{
			foreach ($totalTime as $time)
			{
				list($hours, $minutes, $seconds) = explode(':', $time);
				list($seconds, $milliseconds) = explode('.', $seconds);

				$total += ($hours * 3600) + ($minutes * 60) + $seconds + ($milliseconds / 1000);
			}
		}

		$totalAvgTime = $total / count($totalTime);

		return number_format($totalAvgTime);
	}
}
