<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJReport,testreport
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
JLoader::import('components.com_tmt.tables.question', JPATH_ADMINISTRATOR);

$lang = Factory::getLanguage();
$lang->load('com_tmt.quiz', JPATH_SITE, null, true, true);

/**
 * Test report plugin of TJReport
 *
 * @since  1.3.36
 */
class TjreportsModelTestreport extends TjreportsModelReports
{
	protected $default_order = 'name';

	protected $default_order_dir = 'ASC';

	public $showSearchResetButton = -1;

	protected $escape = 'htmlspecialchars';

	protected $charset = 'UTF-8';

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  1.3.36
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JModelLegacy
	 * @since 1.3.36
	 */
	public function __construct($config = array())
	{
		// Joomla fields integration
		// Define custom fields table, alias, and table.column to join on
		$this->customFieldsTable       = '#__tjreports_com_users_user';
		$this->customFieldsTableAlias  = 'tjrcuu';
		$this->customFieldsQueryJoinOn = 'tta.user_id';

		if (method_exists($this, 'tableExists'))
		{
			$this->customFieldsTableExists = $this->tableExists();
		}

		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

		$lang = Factory::getLanguage();

		$lang->load('com_tjlms', JPATH_SITE . '/administrator');

		$this->columns = array(
			'test_id' => array('table_column' => 'tta.test_id', 'not_show_hide' => false,'title' => 'PLG_TJREPORTS_TMTTESTREPORT_ID'),
			'username' => array('table_column' => 'u.username', 'not_show_hide' => false,
			'title' => 'PLG_TJREPORTS_TMTTESTREPORT_USERUSERNAME', 'isPiiColumn' => true),
			'user_id' => array('table_column' => 'tta.user_id', 'title' => 'PLG_TJREPORTS_TMTTESTREPORT_USERID'),
			'name' => array('table_column' => 'u.name', 'title' => 'COM_TJLMS_ENROLMENT_USER_NAME'),
			'email' => array('table_column' => 'u.email', 'title' => 'COM_TJLMS_ENROLMENT_USER_EMAIL', 'emailColumn' => true),
			'invite_id' => array('table_column' => 'tta.invite_id', 'not_show_hide' => false, 'title' => 'PLG_TJREPORTS_TMTTESTREPORT_INVITEID'),
			'lesson_id' => array('table_column' => 'tlm.lesson_id', 'not_show_hide' => false, 'title' => 'PLG_TJREPORTS_TMTTESTREPORT_LESSONID'),
			'testName' => array('table_column' => 'tt.title', 'title' => 'PLG_TJREPORTS_TMTTESTREPORT_TESTNAME'),
		);

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return array Client
	 *
	 * @since 1.3.36
	 * */
	public function getPluginDetail()
	{
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_TMTTESTREPORT_TITLE'));

		return $detail;
	}

	/**
	 * Add stylesheets
	 *
	 * @return ARRAY Styles url
	 *
	 * @since 1.3.36
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
	 * @since 1.3.36
	 */
	public function getItems()
	{
		$input = Factory::getApplication()->input;
		$tpl = $input->get('tpl', 'default', 'string');
		$tpl = ($tpl == 'default' || $tpl == 'submit') ? null : $tpl;

		$filters = (array) $this->getState('filters');

		if (empty($filters['test_id']))
		{
			$this->setTJRMessages(Text::_('PLG_TJREPORTS_TMTTESTREPORT_SELECT_COURSE_MESSAGE'));

			return array();
		}
		else
		{
			$this->getAdditionalColNames($filters['test_id']);
		}

		// Add additional columns which are not part of the query
		$items = parent::getItems();

		foreach ($items as &$item)
		{
			$answers = $this->getUserAns($item['test_id'], $item['user_id'], $item['invite_id']);

			foreach ($answers as $k => $v)
			{
				$item[$k] = $v;
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
	 * @since   1.3.36
	 */
	protected function getListQuery()
	{
		$db        = $this->_db;
		$query     = parent::getListQuery();
		$colToshow = $this->getState('colToshow');

		$query->select(array('tta.user_id','tlm.lesson_id', 'tta.invite_id'));
		$query->from('#__tmt_tests_answers AS tta');
		$query->join('LEFT',
		$db->quoteName('#__tmt_tests', 'tt') . ' ON ' . $db->quoteName('tt.id') . " = " . $db->quoteName('tta.test_id')
		);
		$query->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . " = " . $db->quoteName('tta.user_id'));
		$query->join('LEFT', $db->quoteName('#__tjlms_lesson_track', 'tlm') . ' ON ' . $db->quoteName('tlm.id') . " = " . $db->quoteName('tta.invite_id'));

		$filters = (array) $this->getState('filters');

		if (empty($filters['test_id']))
		{
			$query->where('tta.test_id=0');
		}

		if ((int) $filters['report_filter'] === -1)
		{
			$hasUsers = TjlmsHelper::getSubusers();

			if ($hasUsers)
			{
				$query->where('tta.user_id IN(' . implode(',', $hasUsers) . ')');
			}
			else
			{
				$query->where('tta.user_id=0');
			}
		}

		$query->group('tta.user_id, tta.invite_id, tta.test_id');

		return $query;
	}

	/**
	 * Create an array of filters
	 *
	 * @return    void
	 *
	 * @since    1.3.36
	 */
	public function displayFilters()
	{
		$reportOptions  = TjlmsHelper::getReportFilterValues($this, $selected, $created_by, $myTeam);

		JLoader::import('components.com_tjlms.models.reports', JPATH_ADMINISTRATOR);
		$TjlmsModelReports = new TjlmsModelReports;
		$userFilter        = $TjlmsModelReports->getUserFilter($myTeam);
		$nameFilter        = $TjlmsModelReports->getNameFilter($myTeam);
		$lessonFilter      = $this->getTestFilter($created_by);

		$groups  = HTMLHelper::_('user.groups', true);
		array_unshift($groups, HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_ENROLLED_USER_ACCESS')));

		$filters = $this->getState('filters');

		$dispFilters = array(
			array(
				'username' => array(
					'search_type' => 'select', 'select_options' => $userFilter, 'type' => 'equal', 'searchin' => 'u.id'
				),
				'name' => array(
					'search_type' => 'select', 'select_options' => $nameFilter, 'type' => 'equal', 'searchin' => 'u.id'
				),
				'email' => array(
					'search_type' => 'text', 'searchin' => 'u.email'
				),
			),
			array(
				'test_id' => array(
						'search_type' => 'select', 'select_options' => $lessonFilter, 'type' => 'equal', 'searchin' => 'tt.id'
				)
			)
		);

		if (count($reportOptions) > 1)
		{
			$filterHtml = HTMLHelper::_('select.genericlist', $reportOptions, 'filters[report_filter]',
					'class="filter-input input-medium" size="1" ' .
					'onchange="document.getElementById(\'filterstest_id\').selectedIndex=0;tjrContentUI.report.submitTJRData();"',
					'value', 'text', $filters['report_filter']
				);
			$dispFilters[1] = array('report_filter' => array( 'search_type' => 'html', 'html' => $filterHtml)) + $dispFilters[1];
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
	 * Create Extra columns
	 *
	 * @param   INT  $testId  Test ID
	 *
	 * @return    void
	 *
	 * @since    1.3.36
	 */
	private function getAdditionalColNames($testId)
	{
		$db    = $this->_db;
		$query = $db->getQuery(true);

		$query->select(array('ttq.question_id','tq.title'));
		$query->from($db->quoteName('#__tmt_tests_questions', 'ttq'));
		$query->join('INNER', $db->quoteName('#__tmt_questions', 'tq') . ' ON ' . $db->quoteName('tq.id') . " = " . $db->quoteName('ttq.question_id'));
		$query->where($db->quoteName('ttq.test_id') . ' = ' . (int) $testId);

		$db->setQuery($query);

		$questions = $db->loadObjectList();

		$colToshow = $this->getState('colToshow', Array());

		if (!empty($questions))
		{
			$this->headerLevel = 1;

			foreach ($questions as $question)
			{
				$question->title = $this->escape(Text::_($question->title));
				$colToshow[$question->title] = $question->title;
				$this->columns[$question->title] = array('title' => $question->title);
			}
		}

		$this->setState('colToshow', $colToshow);
	}

	/**
	 * Function to get the test filter
	 *
	 * @param   INT  $created_by  Fetch creators tests
	 *
	 * @return  object
	 *
	 * @since 1.3.36
	 */
	public function getTestFilter($created_by = 0)
	{
		$query = $this->_db->getQuery(true);

		$query->select('DISTINCT(id) as id,title');
		$query->from($this->_db->qn('#__tmt_tests'));
		$query->where($this->_db->qn('parent_id') . ' = ' . (int) 0);

		if ($created_by)
		{
			$query->where($this->_db->qn('created_by') . ' = ' . (int) $created_by);
		}

		$this->_db->setQuery($query);
		$lessons = $this->_db->loadObjectList();

		$lessonFilter   = array();
		$lessonFilter[] = HTMLHelper::_('select.option', '', Text::_('PLG_TJREPORTS_TMTTESTREPORT_SELECT_TEST'));

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
	 * Function to get answers
	 *
	 * @param   Array  $answers  Fetch creators courses
	 *
	 * @return  string
	 *
	 * @since 1.3.36
	 */
	private function getAnswers($answers = array())
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		if (!empty($answers))
		{
			$query->select(array('tta.*'));
			$query->from($db->quoteName('#__tmt_answers', 'tta'));
			$query->where($db->quoteName('tta.id') . ' in (' . implode(',', $db->quote($answers)) . ')');

			$db->setQuery($query);
			$result = $db->loadAssocList();

			$answer = '';

			if (!empty($result))
			{
				foreach ($result as $value)
				{
					$answer .= $this->escape(Text::_($value['answer'])) . '</br>';
				}

				rtrim($answer, "</br>");
			}

			return $answer;
		}
	}

	/**
	 * Function to get answers of rating type
	 *
	 * @param   INT  $queId  Question id
	 *
	 * @param   INT  $ans    Answer number
	 *
	 * @return  string
	 *
	 * @since   1.3.41
	 */
	private function getRatingAns($queId, $ans)
	{
		$ansIndex = $ans - 1;

		if (!empty($queId))
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/models');
			$questionModel = BaseDatabaseModel::getInstance('Question', 'TmtModel');
			$res = $questionModel->getItem($queId);

			if (!empty($res->params['rating_label']))
			{
				$ratingLables = explode(',', $res->params['rating_label']);
				$answer       = $ratingLables[$ansIndex];
			}
			else
			{
				$answer = $ans;
			}

			return $answer;
		}
	}

	/**
	 * Convert a multi-dimensional array into a single-dimensional array.
	 *
	 * @param   array  $multidimessionalArray  The multi-dimensional array.
	 *
	 * @return  array
	 *
	 * @since 1.3.36
	 */

	public function array_values_recursive($multidimessionalArray)
	{
		$singleDimenssionalArray = array();

		foreach (array_keys($multidimessionalArray) as $array )
		{
			$arrayElement = $multidimessionalArray[$array];

			if (is_scalar($arrayElement))
			{
				$singleDimenssionalArray[] = $arrayElement;
			}
			elseif (is_array($arrayElement))
			{
				$singleDimenssionalArray = array_merge($singleDimenssionalArray, $this->array_values_recursive($arrayElement));
			}
		}

		return $singleDimenssionalArray;
	}

	/**
	 * Function to get the answers given by users
	 *
	 * @param   INT  $testId    Test id
	 *
	 * @param   INT  $userId    User id
	 *
	 * @param   INT  $inviteId  Invite id
	 *
	 * @return  void|array
	 *
	 * @since 1.3.36
	 */
	private function getUserAns($testId, $userId, $inviteId)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		if ($testId && $userId && $inviteId)
		{
			$query->select(array('tta.*', 'tq.title as ques', 'tq.type as questype'));
			$query->from($db->quoteName('#__tmt_tests_answers', 'tta'));
			$query->join('INNER', $db->quoteName('#__tmt_questions', 'tq') . ' ON ' . $db->quoteName('tq.id') . " = " . $db->quoteName('tta.question_id'));
			$query->where($db->quoteName('tta.test_id') . '=' . (int) $testId);
			$query->where($db->quoteName('tta.user_id') . '=' . (int) $userId);
			$query->where($db->quoteName('tta.invite_id') . '=' . (int) $inviteId);

			$db->setQuery($query);
			$result = $db->loadAssocList();

			$i = 0;

			$answer  = array();
			$answers = array();

			foreach ($result as $value)
			{
				$value['ques'] = $this->escape(Text::_($value['ques']));

				if (!in_array($value['questype'], array('text','textarea','file_upload','rating', 'objtext')))
				{
					$answers = explode(',', str_replace(array('[',']','"'), '', $value['answer']));
					$answer[$value['ques']] = $this->getAnswers($answers);
				}
				elseif (in_array($value['questype'], array('file_upload')))
				{
					$answers = explode(',', str_replace(array('[',']','"'), '', $value['answer']));
					BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
					$mediaModel = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');
					$res = $mediaModel->getItem($answers[0]);

					$answer[$value['ques']] = $res->org_filename;
				}
				elseif (in_array($value['questype'], array('rating')))
				{
					$answer[$value['ques']] = $this->getRatingAns($value['question_id'], $value['answer']);
				}
				else
				{
					$answer[$value['ques']] = $value['answer'];
				}

				if (empty($answer[$value['ques']]))
				{
					$answer[$value['ques']] = Text::_('PLG_TJREPORTS_TMTTESTREPORT_NOT_ANSWERED');
				}

				// Return the question related information as this will use in summary report
				$answer['questype'][$i]['question_id'] = $value['question_id'];
				$answer['questype'][$i]['question_lbl'] = $value['ques'];
				$answer['questype'][$i]['question_type'] = $value['questype'];
				$answer['questype'][$i]['test_id'] = $value['test_id'];

				$i++;
			}

			return $answer;
		}
	}

	/**
	 * Method to get chart data to show summary report
	 *
	 * @param   ARRAY  $items  report data
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since 1.3.36
	 */

	public function showSummaryReport($items)
	{
		// If the data is available
		if (!empty($items))
		{
			$db = Factory::getDBO();
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tmt/tables');

			// If the data has qustion types
			if (!empty($items[0]['questype']))
			{
				$n = 0;
				$chartData = array();

				// Check the for each question
				foreach ($items[0]['questype'] as $value)
				{
					// Condition to check the question type is supported for summary report or not
					if (in_array($value['question_type'], $this->supportedFieldTypesForSummaryReport))
					{
						$chartData[$n]['fieldId'] = $value['question_id'];
						$chartData[$n]['fieldLable'] = $value['question_lbl'];
						$chartData[$n]['fieldType'] = $value['question_type'];

						// Total submitted answers with respect to question
						$totalSubmittedAnswerQuery = $db->getQuery(true);
						$totalSubmittedAnswerQuery->select(array('tta.id','tta.answer','q.params'));
						$totalSubmittedAnswerQuery->from('#__tmt_tests_answers AS tta');
						$totalSubmittedAnswerQuery->join('INNER', '#__tmt_questions AS q ON q.id = tta.question_id');
						$totalSubmittedAnswerQuery->where($db->qn('tta.test_id') . ' = ' . $value['test_id']);
						$totalSubmittedAnswerQuery->where($db->qn('tta.question_id') . ' = ' . $value['question_id']);
						$totalSubmittedAnswerQuery->where($db->qn('tta.answer') . ' != " "');

						$db->setQuery($totalSubmittedAnswerQuery);
						$submittedAnswers = $db->loadAssocList();

						$submittedAnswersParams = json_decode($submittedAnswers['0']['params'], true);

						// Fetch data only related to answer
						$submittedAnswers = array_column($submittedAnswers, "answer");

						$k = 0;

						// JSON decode the received answers
						foreach ($submittedAnswers as $ans)
						{
							$submittedAnswers['jsonParsedData'][$k] = json_decode($ans);
							$k++;
						}

						/* Convert the multidimensional array into single dimension using array_flatten function & get the count of each submitted answer
						 e.g.
						 Input array :
						 Array([0] => Array ([0] => 7) [1] => Array ([0] => 8 [1] => 10)  [2] => Array([0] => 7 [1] => 8 [2] => 9 [3] => 10 [4] => 11))
						 Output array :
						 Array([0] => 7 [5] => 8 [6] => 10 [7] => 7 [8] => 8 [9] => 9 [10] => 10 [11] => 11)
						*/

						$flattenArray          = array_count_values($this->array_values_recursive($submittedAnswers['jsonParsedData']));
						$totalAnswerCountArray = count($this->array_values_recursive($submittedAnswers['jsonParsedData']));

						// Create the chart labels & data to show stats with respect to chart options (labels)
						$m = 0;
						$chartData[$n]['chartData']['labels'] = array();

						foreach ($flattenArray as $answerId => $particularAnswerCount)
						{
							$submittedAnswers['chartDataArray'][$m] = ($particularAnswerCount / $totalAnswerCountArray) * 100;

							if ($value['question_type'] == 'rating')
							{
								$answerIndex                 = $answerId - 1;

								if (!empty($submittedAnswersParams['rating_label']))
								{
									$submittedAnswersParamsArray = explode(',', $submittedAnswersParams['rating_label']);

									$answer = $submittedAnswersParamsArray[$answerIndex];
								}
								else
								{
									$answer = $answerId;
								}
							}
							else
							{
								$table = Table::getInstance('Answers', 'TmtTable', array('dbo', $this->_db));
								$table->load(array('id' => $answerId));
								$answer = $table->answer;
							}

							if (!empty($answer))
							{
								array_push($chartData[$n]['chartData']['labels'], Text::_(trim($answer)));
							}

							$m++;
						}

						$chartData[$n]['labels'] = "`" . implode("`,`", $chartData[$n]['chartData']['labels']) . "`";
						$chartData[$n]['data']   = implode(",", $submittedAnswers['chartDataArray']);

						// Unset the element in case if the label is not present for the question
						if (count($chartData[$n]['chartData']['labels']) <= 0)
						{
							unset($chartData[$n]);
						}

						$n++;
					}
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
			return call_user_func($this->escape, $var, ENT_COMPAT, $this->charset);
		}

		return call_user_func($this->_escape, $var);
	}
}
