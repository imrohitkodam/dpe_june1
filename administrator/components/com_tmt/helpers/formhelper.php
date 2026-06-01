<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Form\Form;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

if (JVERSION < '3.0')
{
	HTMLHelper::_('behavior.formvalidation');
}

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

JLoader::import('components.com_tmt.includes.tmt', JPATH_ADMINISTRATOR);

$document = Factory::getDocument();

$document->addScript(Uri::root(true) . '/administrator/components/com_tjlms/assets/js/tjlmsvalidator.js');
$document->addScript(Uri::root(true) . '/administrator/components/com_tmt/assets/js/tjform.js');
$document->addStylesheet(Uri::root(true) . '/administrator/components/com_tmt/assets/css/tjform.css');

Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tmt/');

/**
 * Content builder plugin for Joomla Content
 *
 * @since  1.0.0
 */
class FormHelper extends CMSPlugin
{
	/**
	 * Plugin that supports uploading and tracking the joomla content
	 *
	 * @param   string   &$subject  The context of the content being passed to the plugin.
	 * @param   integer  $config    Optional page number. Unused. Defaults to zero.
	 *
	 * @since 1.0.0
	 */
	public function __construct(&$subject, $config)
	{
		TMT::Language()->adminLanguageConstant();

		parent::__construct($subject, $config);

		$document = Factory::getDocument();
		$lang = Factory::getLanguage();
		$lang->load('plg_' . $this->_type . '_' . $this->_name, JPATH_ADMINISTRATOR);
		$lang->load('com_tmt', JPATH_ADMINISTRATOR);

		$js = '/plugins/' . $this->_type . '/' . $this->_name . '/assets/js/' . $this->_type . '.js';
		$document->addScript(Uri::root(true) . $js);
	}

	/**
	 * Function to get Sub Format options when creating / editing lesson format
	 * the name of function should follow standard getSubFormat_<plugin_type>ContentInfo
	 *
	 * @param   ARRAY  $config  config specifying allowed plugins
	 *
	 * @return  object.
	 *
	 * @since 1.0.0
	 */
	public function getSubFormat_ContentInfo($config = array('quiz'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj               = array();
		$obj['name']       = $this->params->get('plugin_name', 'quiz');
		$obj['id']         = $this->_name;
		$obj['assessment'] = 0;

		return $obj;
	}

	/**
	 * Function to get Sub Format HTML when creating / editing lesson format
	 * the name of function should follow standard getSubFormat_<plugin_name>ContentHTML
	 *
	 * @param   INT    $mod_id       id of the module to which lesson belongs
	 * @param   INT    $lesson_id    id of the lesson
	 * @param   MIXED  $lesson       Object of lesson
	 * @param   ARRAY  $comp_params  Params of component
	 * @param   int    $form_id      id of form
	 *
	 * @return  html
	 *
	 * @since 1.0.0
	 */
	public function getSubFormat_ContentHTML($mod_id , $lesson_id, $lesson, $comp_params, $form_id)
	{
		/* Check if there in any quiz or Exercise or Feedback is not associated with any lesson of the course*/
		/* non zero value = show the Add existing or create new */

		$allow_to_add_existing = $this->checkAlreadyAdded($lesson->course_id, $this->_name);

		$plugin_name = $this->_name;
		$params      = '';
		$questions   = array();
		$subformat   = $lesson->sub_format;
		$test_data   = array();
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjlms/models');
		$assessmentsModel = BaseDatabaseModel::getInstance('Assessments', 'TjlmsModel');
		$assessmentSet    = $assessmentsModel->getLessonAssessSet($lesson_id);

		if (!empty($subformat) && !empty($lesson->source))
		{
			$subformat_source_options = explode('.', $subformat);
			$source_plugin            = $subformat_source_options[0];
			$source_option            = $subformat_source_options[1];

			if (!empty($source_option) && $source_plugin == $this->_name)
			{
				$test_id = $lesson->source;

				require_once JPATH_ADMINISTRATOR . '/components/com_tmt/models/test.php';
				$model     = BaseDatabaseModel::getInstance('Test', 'TmtModel');
				$sections  = $model->getTestSections($test_id);
				$questions = $model->getTestQuestions($test_id, $sections);

				$db    = Factory::getDBO();
				$query = $db->getQuery(true);
				$query->select('*')->from('#__tmt_tests')->where("id=" . $test_id);
				$db->setQuery($query);
				$test_data = $db->loadAssoc();

				foreach ($test_data as $key => $test_c)
				{
					$params['jform[' . $key . ']'] = $test_c;
				}
			}
		}

		ob_start();
		$element      = $this->_name;
		$jformElement = $this->buildForm($test_data);
		$layout       = PluginHelper::getLayoutPath($this->_type, $this->_name, 'creator');
		include $layout;
		$html         = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Function to check if there are any tests present which are not associated with the given course
	 *
	 * @param   int  $course_id  id of course
	 * @param   int  $qztype     quiz type
	 *
	 * @return   result
	 */
	public function checkAlreadyAdded($course_id, $qztype = 'quiz')
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_tmt/models/tests.php';
		$model      = BaseDatabaseModel::getInstance('Tests', 'TmtModel');
		$used_tests = $model->alreadyaddedtest($course_id, $qztype);

		$count = 0;

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('COUNT(a.id)');
		$query->from('`#__tmt_tests` AS a');

		if (!empty($used_tests))
		{
			$query->where("a.id NOT IN(" . implode(",", $used_tests) . ")");
		}

		$query->where("qztype = '" . $qztype . "'");

		$db->setQuery($query);

		$count = $db->loadResult();

		return $count;
	}

	/**
	 * Function to get needed data for this API
	 *
	 * @param   MIXED  $data  array
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	public function getData($data)
	{
		return true;
	}

	/**
	 * Function to render the document
	 *
	 * @param   ARRAY  $config  Data to display
	 *
	 * @return  complete html along with script is return.
	 *
	 * @since 1.0.0
	 */
	public function quizrenderPluginHTML($config)
	{
		$input             = Factory::getApplication()->input;
		$mode              = $input->get('mode', '', 'STRING');
		$config['plgtask'] = 'quiz_updatedata';
		$config['plgtype'] = $this->_type;
		$config['plgname'] = $this->_name;

		// Load the layout & push variables
		ob_start();
		$layout = $this->buildLayoutPath('default');
		include $layout;
		$html   = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Internal use functions
	 *
	 * @param   STRING  $layout  layout
	 *
	 * @return  file
	 *
	 * @since 1.0.0
	 */
	public function buildLayoutPath($layout)
	{
		$app = Factory::getApplication();
		$core_file = dirname(__FILE__) . '/' . $this->_name . '/tmpl/' . $layout . '.php';
		$override  = JPATH_BASE . '/templates/' . $app->getTemplate() . '/html/plugins/' . $this->_type . '/' . $this->_name . '/' . $layout . '.php';

		if (File::exists($override))
		{
			return $override;
		}
		else
		{
			return $core_file;
		}
	}

	/**
	 * Builds the layout to be shown, along with hidden fields.
	 *
	 * @param   ARRAY   $vars    vars to be used
	 * @param   STRING  $layout  layout
	 *
	 * @return  html
	 *
	 * @since 1.0.0
	 */
	public function buildLayout($vars, $layout = 'default' )
	{
		// Load the layout & push variables
		ob_start();
		$layout = $this->buildLayoutPath($layout);
		include $layout;
		$html   = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Constructor
	 *
	 * @param   ARRAY  $params  params
	 *
	 * @return true
	 *
	 * @since 1.0.0
	 *
	 */
	public function buildForm($params)
	{
		$form_path  = JPATH_ADMINISTRATOR . '/components/com_tmt/models/forms/';
		Form::addFormPath($form_path);
		require_once JPATH_ADMINISTRATOR . '/components/com_tmt/models/test.php';
		$model      = BaseDatabaseModel::getInstance('Test', 'TmtModel');
		$data       = array($this->_name);
		$form       = $model->getForm($data);
		$form->bind($params);
		$field_sets = $form->getFieldsets();

		$html = array();

		foreach ($field_sets as $field_set)
		{
			$html[]   = "<div class='quizfieldset quizfieldset_" . $field_set->name . " span6'>";
			$fieldset = $form->renderFieldset($field_set->name);
			$html[]   = str_replace('jform[', 'lesson_format[' . $this->_name . '][', $fieldset);
			$html[]   = "</div>";
			$html[]   = "<style>";
			$html[]   = ".quizfieldset_" . $field_set->name . ":after{content:'" . Text::_($field_set->label);
			$html[]   = "'}</style>";
		}

		return $html = implode('', $html);
	}

	/**
	 * Function to upload a file on server
	 * This is blank as we do not upload file on jwplayer
	 *
	 * @param   STRING  $lessonFormatData  file name
	 *
	 * @return  true
	 *
	 * @since 1.0.0
	 */
	public function OnAfterFormatUploaded($lessonFormatData)
	{
		require_once JPATH_SITE . "/administrator/components/com_tmt/models/test.php";
		require_once JPATH_SITE . "/administrator/components/com_tjlms/models/lesson.php";

		$db                          = Factory::getDBO();
		$query                       = "SELECT * from #__tjlms_lessons WHERE id=" . $lessonFormatData['id'];
		$db->setQuery($query);
		$lessonDetails               = $db->loadAssoc();
		$lessonFormat                = $lessonFormatData[$this->_name];
		$lessonFormat['id']          = $lessonFormat['test_id'];
		$lessonFormat['from_plugin'] = '1';
		$lessonFormat['title']       = $lessonDetails['name'];
		$lessonFormat['alias']       = $lessonDetails['alias'];
		$lessonFormat['description'] = $lessonDetails['short_desc'];
		$lessonFormat['start_date']  = $lessonDetails['start_date'];
		$lessonFormat['end_date']    = $lessonDetails['end_date'];
		$lessonFormat['lesson_id']   = $lessonFormatData['id'];

		$objTmtModelTest = new TmtModelTest;
		$test_id         = $objTmtModelTest->save($lessonFormat);

		// Update source of the media id
		$object             = new stdClass;
		$object->id         = $lessonFormatData['format_id'];
		$object->sub_format = $this->_name . '.test';
		$object->source     = $test_id;

		$result = $db->updateObject('#__tjlms_media', $object, 'id');

		return $test_id;
	}

	/**
	 * Function to get the id of the scorm table
	 *
	 * @param   INT  $lessonId   lessonid
	 * @param   INT  $mediaData  Media Object
	 *
	 * @return  id of tjlms_scorm
	 *
	 * @since 1.0.0
	 */
	public function getAdditionalformdata($lessonId, $mediaData)
	{
		$testId = $mediaData->source;
		$result = new stdClass;

		if ($testId)
		{
			$db    = Factory::getDBO();
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tmt/tables');
			$table = Table::getInstance('Test', 'TmtTable', array('dbo', $db));
			$table->load($testId);

			// @if quiz is of set type If attempted previously get the test id from test attendees table

			if ($table->type == "set")
			{
				$userId = Factory::getUser()->id;

				$ltquery = $db->getQuery(true);
				$ltquery->select('t.id');
				$ltquery->from('#__tjlms_lesson_track AS t');
				$ltquery->join('INNER', '#__tjlms_lessons AS l ON l.id = t.lesson_id');
				$ltquery->where('l.resume = 1');
				$ltquery->where('t.lesson_id ="' . $lessonId . '"' . ' and t.user_id ="' . $userId . '"');
				$ltquery->where('t.lesson_status <>' . $db->q('completed'));
				$ltquery->where('t.lesson_status <>' . $db->q('passed'));
				$ltquery->where('t.lesson_status <>' . $db->q('failed'));
				$ltquery->order('t.id desc');
				$ltquery->setLimit('1');

				$query = $db->getQuery(true);
				$query->select(' ta.test_id');
				$query->from(' #__tmt_tests_attendees AS ta');
				$query->where(' ta.invite_id =(' . $ltquery . ')');
				$db->setQuery($query);
				$temp = $db->loadResult();

				if (!empty($temp))
				{
					$testId = $temp;
				}
			}

			$result->id            = $testId;
			$result->title         = $table->title;
			$result->description   = $table->description;
			$result->time_duration = $table->time_duration;
			$result->termscondi    = $table->termscondi;
			$result->gradingtype   = $table->gradingtype;

			if ($table->type == "plain")
			{
				$query = $db->getQuery(true);
				$query->select("COUNT(tq.id)");
				$query->from('#__tmt_tests_questions AS tq');
				$query->join('INNER', '#__tmt_questions AS q ON q.id = tq.question_id');
				$query->join('LEFT', '#__tmt_tests_sections AS s ON tq.section_id = s.id');
				$query->where('tq.test_id =' . (int) $table->id);
				$query->where('s.state =1');
				$db->setQuery($query);
				$questions = $db->loadResult();
			}
			else
			{
				$query = $db->getQuery(true);
				$query->select("SUM(tr.questions_count)");
				$query->from(' #__tmt_quiz_rules AS tr');
				$query->join('LEFT', '#__tmt_tests_sections AS s ON tr.section_id = s.id');
				$query->where('tr.quiz_id =' . (int) $table->id);
				$query->where('s.state = 1');
				$db->setQuery($query);
				$questions = $db->loadResult();
			}

			$result->test_questions = $questions;
		}

		return $result;
	}

	/**
	 * Function to get the id of the scorm table
	 *
	 * @param   INT  $lessonId  lessonid
	 * @param   INT  $mediaObj  Media Object
	 *
	 * @return  id of tjlms_scorm
	 *
	 * @since 1.0.0
	 */
	public function getAdditionalFormatCheck($lessonId, $mediaObj)
	{
		/*Check if questions added against a quiz*/
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select("COUNT(tq.id)");
		$query->from('#__tmt_tests_questions AS tq');
		$query->join('INNER', '#__tmt_questions AS q ON q.id = tq.question_id');
		$query->join('LEFT', '#__tmt_tests_sections AS s ON tq.section_id = s.id');
		$query->where('tq.test_id =' . (int) $mediaObj->source);
		$query->where('s.state =1');
		$db->setQuery($query);
		$questions = $db->loadResult();

		if ($questions > 0)
		{
			return $mediaObj;
		}

		return false;
	}

	/**
	 * This is added for fileupload question. Extra params validation
	 *
	 * @param   INT  $data  array of format, subformat and file uploaded
	 *
	 * @return  array of validation
	 *
	 * @since 1.3.6
	 */
	public function validateFilesForAnswer($data)
	{
		$formatData   = $data['formatData'];
		$questionId   = $formatData['quiz']['answer']['qid'];
		$testId       = $formatData['quiz']['answer']['testid'];
		$inviteId     = $formatData['quiz']['answer']['ltid'];

		$fileToUpload = $data['fileToUpload'];

		$return = 1;
		$msg = '';

		if ($questionId  && $testId && $inviteId && !empty($fileToUpload))
		{
			JLoader::import('questionform',  JPATH_SITE . '/components/com_tmt/models');
			$questionModel              = BaseDatabaseModel::getInstance('questionform', 'TmtModel');
			$questionModel->withAnswers = 0;
			$questionInfo               = $questionModel->getItem($questionId);

			if (!empty($questionInfo->params))
			{
				$questionParams = json_decode($questionInfo->params);

				if (!empty($questionParams->file_format))
				{
					$validExtensions = array_map('trim', explode(',', str_replace('.', '', $questionParams->file_format)));
					$validExtensions = array_map('strtolower', $validExtensions);

					$ext = strtolower(File::getExt(File::makeSafe($fileToUpload['name'])));

					if (!empty($validExtensions) && !in_array($ext, $validExtensions))
					{
						$return = 0;
						$msg = Text::sprintf("COM_TJLMS_ALLOWED_FILE_EXTENSION_ERROR_MSG", $questionParams->file_format, $ext);
					}
				}

				if (!empty($questionParams->file_size))
				{
					if ($fileToUpload['size'] > ($questionParams->file_size * 1024 * 1024))
					{
						$return = 0;
						$msg = Text::sprintf(
							'COM_TJLMS_ALLOWED_FILE_SIZE_ERROR_MSG',
							$questionParams->file_size,
							number_format($fileToUpload['size'] / (1024 * 1024), 2)
						);
					}
				}

				if (!empty($questionParams->file_count))
				{
					// Check if user has added filess against the fileupload question
					$noOfFilesAlreadyUploaded = $questionModel->getNoOfFilesuploadedforAns($inviteId, $questionId);

					if ($noOfFilesAlreadyUploaded >= $questionParams->file_count)
					{
						$return = 0;
						$msg = Text::sprintf("COM_TJLMS_MAX_NUMBER_OF_FILE_UPLOAD_ERROR_MSG", $questionParams->file_count);
					}
				}
			}
		}

		return array('res' => $return, 'msg' => $msg);
	}
}
