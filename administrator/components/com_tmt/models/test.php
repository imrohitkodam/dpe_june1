<?php
/**
 * @package     TMT
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

jimport('techjoomla.common');
JLoader::import("/techjoomla/media/xref", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/tables/xref", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/tables/files", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Tmt model.
 *
 * @since  1.0
 */
class TmtModeltest extends AdminModel
{
	/**
	 * @var  string  The prefix to use with controller messages.
	 * @since  1.0
	 */
	protected $text_prefix    = 'COM_TMT';

	protected $item;

	public $lessonImageClient = 'tjlms.lesson';

	public $defaultMimeTypes  = array(
		'image/jpeg',
		'image/gif',
		'image/png',
	);
	/**
	 * Constructor
	 *
	 * @since 1.0
	 */

	public function __construct()
	{
		parent::__construct();
		$this->user             = Factory::getUser();
		$this->app              = Factory::getApplication();
		$this->TechjoomlaCommon = new TechjoomlaCommon;
		$this->ComtjlmsHelper   = new ComtjlmsHelper;
		$this->techjoomlacommon = new TechjoomlaCommon;
		$this->tjLmsParams      = ComponentHelper::getParams('com_tjlms');
	}

	/**
	 * Method to get an ojbect.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return  mixed   Object on success, false on failure.
	 *
	 * @since 1.0
	 */
	public function getData($id = null)
	{
		if ($this->item === null)
		{
			$this->item = false;

			if (empty($id))
			{
				$id = $this->getState('test.id');
			}

			if (!$this->item)
			{
				// Get a level row instance.
				$table = $this->getTable();

				// Attempt to load the row.
				if ($table->load($id))
				{
					// Get current record id - this is important
					$id = $table->id;

					// Convert the Table to a clean JObject.
					$properties = $table->getProperties(1);
					$this->item = ArrayHelper::toObject($properties, 'JObject');

					// Get test questions & other data
					if ($id)
					{
						$this->item->sections  = $this->getTestSections($id);
						$this->item->questions = $this->getTestQuestions($id, $this->item->sections);

						$db        = $this->getDbo();
						$lesson_id = $this->app->input->get('unique', '0', 'INT');

						$query = $db->getQuery(true);
						$query->select('max(attempt) as total,l.no_of_attempts');
						$query->from('#__tjlms_lesson_track as lt');
						$query->leftjoin('#__tjlms_lessons as l ON l.id = lt.lesson_id');
						$query->where('lesson_id  = ' . $lesson_id);
						$db->setQuery($query);
						$result                  = $db->loadobject();
						$this->item->max_attempt = $result->total;
						$this->item->qid         = $lesson_id;

						// Get test reviewers
						$query = $db->getQuery(true);
						$query->select('tr.id, tr.user_id');
						$query->from('#__tmt_tests_reviewers AS tr');
						$query->where('tr.test_id =' . (int) $id);
						$db->setQuery($query);
						$rData    = $db->loadObjectList();
						$rDataNew = array();

						foreach ($rData as $r)
						{
							$rDataNew[$r->id] = $r->user_id;
						}

						$this->item->reviewers = $rDataNew;

						// Get eligibilty criteria
						$qur = $db->getQuery(true);
						$qur->select('l.eligibility_criteria,l.no_of_attempts,l.attempts_grade,l.free_lesson,l.consider_marks, l.catid');
						$qur->from('#__tjlms_lessons as l');
						$qur->join('INNER', '#__tjlms_tmtquiz as t ON t.lesson_id=l.id');
						$qur->where('t.test_id =' . (int) $id . ' AND l.id=' . $lesson_id);
						$db->setQuery($qur);
						$lessonData = $db->loadObject();

						if ($lessonData)
						{
							$this->item->eligibility_criteria = $lessonData->eligibility_criteria;
							$this->item->no_of_attempts       = $lessonData->no_of_attempts;
							$this->item->attempts_grade       = $lessonData->attempts_grade;
							$this->item->free_lesson          = $lessonData->free_lesson;
							$this->item->consider_marks       = $lessonData->consider_marks;
							$this->item->catid                = $lessonData->catid;
						}

						// Get Rules data if quiz type is set added by raviraj

						if ($this->item->type != 'plain')
						{
							$qurre = $db->getQuery(true);
							$qurre->select('id, questions_count, pull_questions_count, marks, category, difficulty_level, question_type');
							$qurre->from('#__tmt_quiz_rules');
							$qurre->where('quiz_id=' . "$id");
							$db->setQuery($qurre);
							$rulesData         = $db->loadObjectList();
							$this->item->rules = $rulesData;
						}
					}
				}
				elseif ($error = $table->getError())
				{
					$this->setError($error);
				}
			}
		}

		return $this->item;
	}

	/**
	 * Returns the questions associated with test
	 *
	 * @param   int  $testId  Test id
	 *
	 * @return  Questions associated with test
	 *
	 * @since 1.0
	 */
	public function getTestSections($testId)
	{
		$query = $this->_db->getQuery(true);
		$query->select($this->_db->qn(['s.id','s.title','s.ordering','s.state','s.description']));

		$mquery = $this->_db->getQuery(true);
		$mquery->select("SUM(q.marks)")
				->from($this->_db->qn("#__tmt_questions", "q"))
				->join("left", $this->_db->qn("#__tmt_tests_questions", "tq") . " ON tq.question_id = q.id")
				->where("tq.section_id = s.id")
				->where("tq.test_id = " . (int) $testId);
		$query->select("(" . $mquery . ") as marks");

		$query->from('#__tmt_tests_sections AS s');
		$query->where('s.test_id =' . (int) $testId);
		$query->join("left", $this->_db->qn("#__tmt_tests", "t") . " ON t.id = s.test_id");
		$query->where((int) $testId . " > 0");
		$query->order('s.ordering ASC');

		$this->_db->setQuery($query);

		return $this->_db->loadObjectList();
	}

	/**
	 * Returns the sections associated with test
	 *
	 * @param   OBJECT  &$item  Test object
	 *
	 * @return  Sections associated with test
	 *
	 * @since 1.0
	 */
	private function getTestQuestionsBySections(&$item)
	{
		$db       = $this->getDbo();
		$sections = $this->getTestSections($item->id);

		$item->sections = array();

		foreach ($sections as $section)
		{
			$item->sections[$section->id] = $section;

			$query = $db->getQuery(true);

			// Get test questions
			$query->select('tq.section_id, q.id, q.title, q.type, q.category_id, q.level, q.marks, tq.is_compulsory');
			$query->from('#__tmt_tests_questions AS tq');

			$query->join('LEFT', '#__tmt_questions AS q ON q.id = tq.question_id ');

			/*$isApptemptedQ = $db->getQuery(true);
			$isApptemptedQ->select('COUNT(ta.id)');
			$isApptemptedQ->from($this->_db->quoteName("#__tmt_tests_answers", "ta"));
			$isApptemptedQ->where('q.id = ta.question_id');

			$query->select("(" . $isApptemptedQ . ")" . " as isQuestionAttempted");*/

			// Join over the foreign key 'category_id'
			$query->select('c.title AS cat_title ');
			$query->join('LEFT', '#__categories AS c ON c.id = q.category_id ');

			$query->select('s.title AS section_title, s.state AS section_state ');
			$query->join('LEFT', '#__tmt_tests_sections AS s ON s.id = tq.section_id ');

			$query->where('tq.test_id =' . (int) $item->id);
			$query->where('tq.section_id =' . (int) $section->id);

			// Ordering
			$query->order('tq.order ASC');
			$db->setQuery($query);
			$q_data = $db->loadObjectList();

			$item->sections[$section->id]->questions = $q_data;
			$item->sections[$section->id]->qcnt = count($q_data);

			// Get Rules data
			if ($item->type == 'set')
			{
				$qurre = $this->_db->getQuery(true);
				$qurre->select('id, questions_count, pull_questions_count, marks, category, difficulty_level, question_type');
				$qurre->from('#__tmt_quiz_rules');
				$qurre->where('quiz_id=' . $this->_db->q((int) $item->id));
				$qurre->where('section_id=' . $this->_db->q((int) $section->id));
				$this->_db->setQuery($qurre);
				$rulesData = $this->_db->loadObjectList();
				$item->sections[$section->id]->rules = $rulesData;
			}
		}
	}

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   type    $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table A database object
	 *
	 * @since 1.0
	 */
	public function getTable($type = 'Test', $prefix = 'TmtTable', $config = array())
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/' . 'components' . '/' . 'com_tmt' . '/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to check in an item.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function checkin($id = null)
	{
		// Get the id.
		$id = (!empty($id)) ? $id : (int) $this->getState('test.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Attempt to check the row in.
			if (method_exists($table, 'checkin'))
			{
				if (!$table->checkin($id))
				{
					$this->setError($table->getError());

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to check out an item for editing.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return  boolean     True on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function checkout($id = null)
	{
		// Get the user id.
		$id = (!empty($id)) ? $id : (int) $this->getState('test.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Attempt to check the row out.
			if (method_exists($table, 'checkout'))
			{
				if (!$table->checkout($this->user->get('id'), $id))
				{
					$this->setError($table->getError());

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm  A JForm object on success, false on failure
	 *
	 * @since  1.0
	 */
	public function getForm($data = array() , $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_tmt.test', 'test', array( 'control' => 'jform', 'load_data' => $loadData ));

		if (empty($form))
		{
			return false;
		}

		/*if ($data)
		{
			if ($data[0] == 'exercise')
			{
				$form->removeField('dynamic_question');
				$form->removeField('answer_sheet');
			}

			if ($data[0] == 'feedback')
			{
				$form->removeField('dynamic_question');
				$form->removeField('total_marks');
				$form->removeField('passing_marks');
			}
		}*/

		return $form;
	}

	/**
	 * Allows preprocessing of the JForm object.
	 *
	 * @param   JForm   $form   The form object
	 * @param   array   $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		$form->loadFile(JPATH_ADMINISTRATOR . '/components/com_tjlms/models/forms/lesson.xml', true);
		$form->removeField('ideal_time');

		$gradingType = $this->app->input->getString('gradingtype', 'quiz', 'WORD');

		if (!empty($data->id))
		{
			$gradingType = $data->gradingtype;
		}

		if ($gradingType == 'feedback')
		{
			$form->setFieldAttribute('no_of_attempts', 'readonly', 'true');
			$form->setFieldAttribute('attempts_grade', 'readonly', 'true');
			$form->removeField('total_marks');
			$form->removeField('passing_marks');
		}
		elseif ($gradingType == 'quiz')
		{
			$form->setFieldAttribute('total_marks', 'readonly', 'true');
		}
		elseif ($gradingType == 'exercise')
		{
			$form->setFieldAttribute('total_marks', 'readonly', 'true');
			$form->removeField('answer_sheet');
		}

		if ($gradingType != 'quiz')
		{
			$form->removeField('type');
		}

		$plugin = PluginHelper::getPlugin('tj' . $gradingType, $gradingType);

		$assessment = 0;

		if (!empty($plugin->params))
		{
			$params     = new Registry($plugin->params);
			$assessment = (int) $params->get('assessment', '0');
		}

		if ($assessment)
		{
			$form->loadFile(JPATH_ADMINISTRATOR . '/components/com_tjlms/models/forms/assessment.xml', true);

			if ($gradingType == 'exercise')
			{
				$form->setValue('add_assessment', null, '1');
				$form->setFieldAttribute('add_assessment', 'readonly', 'true');
			}
		}

		parent::preprocessForm($form, $data, $group);
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return mixed  The data for the form.
	 *
	 * @since 1.0
	 */
	protected function loadFormData()
	{
		$app = Factory::getApplication();
		$data = $app->getUserState('com_tmt.edit.test.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		// Prime some default values.
			if ($this->getState('test.id') == 0)
			{
				$data->set('gradingtype', $app->input->get('gradingtype', $app->getUserState('com_tmt.tests.filter.gradingtype'), 'string'));
			}


		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  mixed  The test id on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function save($data)
	{
		$null = Factory::getDbo()->getNullDate();

		if (!$data['id'])
		{
			$data['created_by'] = $this->user->id;
			$data['parent_id'] = !empty($data['parent_id']) ? $data['parent_id'] : 0;
			$data['type'] = !empty($data['type']) ? $data['type'] : 'plain';
			$data['ordering'] = !empty($data['ordering']) ? $data['ordering'] : 1;
			$data['checked_out'] = !empty($data['checked_out']) ? $data['checked_out'] : 0;
			$data['checked_out_time'] = !empty($data['checked_out_time']) ? $data['checked_out_time'] : $null;
			$data['reviewers'] = !empty($data['reviewers']) ? $data['reviewers'] : '';
			$data['show_time'] = !empty($data['show_time']) ? $data['show_time'] : 0;
			$data['time_duration'] = !empty($data['time_duration']) ? $data['time_duration'] : 0;
			$data['show_time_finished'] = !empty($data['show_time_finished']) ? $data['show_time_finished'] : 0;
			$data['time_finished_duration'] = !empty($data['time_finished_duration']) ? $data['time_finished_duration'] : 0;
			$data['total_marks'] = !empty($data['total_marks']) ? $data['total_marks'] : 0;
			$data['passing_marks'] = !empty($data['passing_marks']) ? $data['passing_marks'] : 0;
			$data['isObjective'] = !empty($data['isObjective']) ? $data['isObjective'] : 0;
			$data['created_on'] = !empty($data['created_on']) ? $data['created_on'] : Factory::getDate()->toSql();
			$data['modified_on'] = !empty($data['modified_on']) ? $data['modified_on'] : $null;
			$data['start_date'] = !empty($data['start_date']) ? $data['start_date'] : $null;
			$data['end_date'] = !empty($data['end_date']) ? $data['end_date'] : $null;
			$data['show_quiz_marks'] = !empty($data['show_quiz_marks']) ? $data['show_quiz_marks'] : 0;
			$data['image'] = !empty($data['image']['name']) ? $data['image'] : '';	
		}

		$data['start_date'] = !empty($data['start_date']) ? $data['start_date'] : $null;
		$data['end_date'] = !empty($data['end_date']) ? $data['end_date'] : $null;

		if (!empty($data['image']) && (!$data['image']['error'] || $data['image']['error'] == UPLOAD_ERR_OK))
		{
			$imageDetails = pathinfo($data['image']['name']);

			// Get legal image extensions
			$legalImageExtensions = $this->tjLmsParams->get('tjlms_image_extension', '');

			if (!empty($legalImageExtensions))
			{
				$legalImageExtensions = array_map('trim', explode(',', $legalImageExtensions));
			}
			else
			{
				$legalImageExtensions = $this->defaultImageExtensions;
			}

			$isImage = false;

			// Check if file is image
			if (in_array($imageDetails['extension'], $legalImageExtensions))
			{
				$isImage = true;
			}

			// Check for allowed mime types - start
			$imageMimeType = $this->tjLmsParams->get('tjlms_image_mime_type', '');

			if (!empty($imageMimeType))
			{
				$imageMimeType = array_map('trim', explode(',', $imageMimeType));
			}
			else
			{
				$imageMimeType = $this->defaultMimeTypes;
			}

			$mediaLibObj = TJMediaStorageLocal::getInstance();

			$getMimeType = $mediaLibObj->getMimeType($data['image']['tmp_name'], $isImage);

			if (!in_array($getMimeType, $imageMimeType))
			{
				$this->setError(Text::_("COM_TMT_QUESTION_MEDIA_INVALID_FILE_TYPE_ERROR"));

				return false;
			}
			// Check for allowed mime types - end
		}

		// If user is not allowed to view the answer sheet then show_correct_answer should be disabled
		if (isset($data['answer_sheet']) && empty($data['answer_sheet']))
		{
			$data['show_correct_answer'] = 0;
		}

		if (parent::save($data))
		{
			$result = (!empty($data['id'])) ? $data['id'] : $this->getState($this->getName() . '.id');

			if (empty($this->getTestSections($result)))
			{
				BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/models/section.php');
				$sectionModel = BaseDatabaseModel::getInstance('Section', 'TmtModel');

				$sectionData['title']    = "Section 1";
				$sectionData['test_id']  = $result;
				$sectionData['state']    = 1;
				$sectionData['ordering'] = 1;
				$sectionData['description'] = '';
				$sectionData['min_questions'] = 0;
				$sectionData['max_questions'] = 0;
				$sectionModel->save($sectionData);

				$sectionId = $sectionModel->getState($sectionModel->getName() . '.id');

				$this->setState($this->getName() . '.section_id', $sectionId);
			}

			/*require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/lesson.php';
			require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/media.php';
			$lessonModel = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');
			$mediaModel = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');

			if (isset($data['course_id']) && isset($data['mod_id']))
			{
				$data['id'] = (!empty($data['lesson_id'])) ? $data['lesson_id'] : 0;
				$lessonModel->save($data);

				$lessonId = (!empty($data['id'])) ? $data['id'] : (int) $lessonModel->getState($lessonModel->getName() . '.id');

				$this->setState($this->getName() . '.lesson_id', $lessonId);
			}*/
			$id = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');

			if (!empty($data['image']) && (!$data['image']['error'] || $data['image']['error'] == UPLOAD_ERR_OK))
			{
				$imageFile[]   = $data['image'];
				$uploadFolder  = JPATH_SITE . $this->tjLmsParams->get('lesson_image_upload_path', "/images/com_tjlms/lessons/");
				$maxSize       = $this->tjLmsParams->get('lesson_upload_size');
				$config        = array("client_id" => $id, "client" => $this->lessonImageClient, "uploadPath" => $uploadFolder, "size" => $maxSize);
				BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
				$mediaModel    = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');
				$uploadedMedia = $mediaModel->uploadImage($imageFile, null, $config);

				if (!$uploadedMedia)
				{
					$this->setError(Text::_($mediaModel->getError()));

					return false;
				}

				$data['id']    = $id;
				$data['image'] = $uploadedMedia['id'];
				parent::save($data);
			}
		}

		return true;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  mixed  The test id on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function saveSection($data)
	{
		$date = Factory::getDate();

		if (!$data['id'])
		{
			// Existing item
			$data['created_on'] = $date->toSql();
			$data['created_by'] = $this->user->id;
		}

		if (parent::save($data))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/lesson.php';
			require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/media.php';
			$lessonModel = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');
			$mediaModel  = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');
			$result      = $this->getState($this->getName() . '.id');

			if (isset($data['course_id']) && isset($data['mod_id']))
			{
				if (isset($data['gradingtype']))
				{
					$mediaData['format']     = $data['gradingtype'];
					$mediaData['sub_format'] = $data['gradingtype'] . '.test';
					$mediaData['source']     = $result;

					$data['media_id']        = $mediaModel->getMediaIdByData($mediaData);

					if (!$data['media_id'])
					{
						$mediaModel->save($mediaData);
						$data['media_id'] = $mediaModel->getState($mediaModel->getName() . '.id');
					}
				}

				$data['id'] = $data['lesson_id'];
				$lessonModel->save($data);

				$lessonId   = (!empty($data['id'])) ? $data['id'] : (int) $lessonModel->getState($lessonModel->getName() . '.id');

				$this->setState($this->getName() . '.lesson_id', $lessonId);
			}
		}

		return true;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  mixed  The test id on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function savedsa($data)
	{
		$db    = Factory::getDBO();
		$id    = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('test.id');
		$state = (!empty($data['state'])) ? 1 : 0;

		// For new record.
		if (!$id)
		{
			$data['created_on'] = $this->techjoomlacommon->getDateInUtc(Factory::getDate());
		}

		if (!empty($data['start_date']))
		{
			$data['start_date'] = $this->TechjoomlaCommon->getDateInUtc($data['start_date']);
		}

		if (!empty($data['end_date']))
		{
			$data['end_date'] = $this->TechjoomlaCommon->getDateInUtc($data['end_date']);
		}

		$data['type']        = (isset($data['quiz_type']) and $data['quiz_type'] == 1) ? 'set' : 'plain';
		$data['isObjective'] = 1;
		$table               = $this->getTable();

		// Save test data
		if ($table->save($data) === true)
		{
			$id             = $table->id;

			$subjective_tqs = $this->getSubjectiveQuestions($id);

			if (count($subjective_tqs) && $data['qztype'] == 'quiz')
			{
				$assessmentData['add_assessment']    = 1;
				$assessmentData['aseessment_params'] = '';
				$assessmentData['total_marks']       = $data['total_marks'];
				$assessmentData['attempts']          = 1;
				$assessmentData['can_view']          = $data['answer_sheet'];
				$assessmentData['passing_marks']     = $data['passing_marks'];
				$assessmentData['attempts_grade']    = 0;
				$assessmentData['allow_attachments'] = 0;
				$assessmentData['subformat']         = $data['qztype'];
				$assessmentData['lesson_id']         = $data['lesson_id'];
				$assessmentData['set_id']            = isset($data['set_id']) ? $data['set_id'] : 0;

				BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
				$lessonModel                = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');
				$set_id                     = $lessonModel->saveAssessment($assessmentData);
				$ret['OUTPUT']['set_id']    = $set_id;

				$lessontable                = Table::getInstance('lesson', 'TjlmsTable', array('dbo', $db));
				$lessontable->load($assessmentData['lesson_id']);

				$lessontable->total_marks   = $data['total_marks'];
				$lessontable->passing_marks = $data['passing_marks'];
				$lessontable->store();
			}

			if (count($subjective_tqs))
			{
				$test_data              = new stdClass;
				$test_data->id          = (int) $id;
				$test_data->isObjective = 0;

				if (!$this->_db->updateObject('#__tmt_tests', $test_data, 'id'))
				{
					echo $this->_db->stderr();

					return false;
				}
			}

			if (!empty($data['lesson_id']))
			{
				$lesson_id = $data['lesson_id'];
			}

			if ($data['qztype'] != 'feedback')
			{
				$db                    = Factory::getDBO();
				Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
				$ltable                = Table::getInstance('Lesson', 'TjlmsTable', array('dbo', $db));
				$ltable->load($lesson_id);
				$ltable->total_marks   = $data['total_marks'];
				$ltable->passing_marks = $data['passing_marks'];
				$ltable->store();
			}

			// Check if the Quiz id added from LMS and not from Quizzes view
			if (!empty($data['addquiz']))
			{
				$data['eligibility_criteria'] = '';

				if (!empty($data['eligibility_criteria']))
				{
					$data['eligibility_criteria'] = ',' . implode(',', $data['eligibility_criteria']) . ',';
				}

				$lesson_data           = $data;
				$lesson_data['name']   = $table->title;
				$lesson_data['alias']  = $table->alias;
				$lesson_data['format'] = 'tmtQuiz';

				/**If we do not have test id, that means we are adding new quiz against a lesson hence we need to add entry
				 in LMS table**/
				$test_id = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('test.id');

				if (!$test_id)
				{
					$lesson_data['id']         = '';
					$lesson_data['created_by'] = $this->user->id;
				}
				else
				{
					if (!$data['unique'])
					{
						$query = $db->getQuery(true);
						$query->select('tq.lesson_id')->from(' #__tjlms_tmtquiz AS tq')->where('tq.test_id =' . (int) $test_ID);
						$db->setQuery($query);
						$lesson_id = $db->loadResult();
					}
					else
					{
						$lesson_id = $data['unique'];
					}

					$lesson_data['id'] = $lesson_id;
				}

				$lesson_id = $this->saveLesson($lesson_data, $id);
			}

			if ($lesson_id)
			{
				$db    = Factory::getDBO();
				$query = $db->getQuery(true);
				$query->select('id')
					->from('#__tjlms_tmtquiz')
					->where("lesson_id = " . $lesson_id);
				$db->setQuery($query);
				$tmtquizid = $db->loadResult();

				$object            = new stdClass;
				$object->lesson_id = $lesson_id;
				$object->test_id   = $id;

				if ($tmtquizid)
				{
					$result = $db->updateObject('#__tjlms_tmtquiz', $object, 'lesson_id');
				}
				else
				{
					$object            = new stdClass;
					$object->lesson_id = $lesson_id;
					$object->test_id   = $id;
					$result            = $db->insertObject('#__tjlms_tmtquiz', $object);
				}
			}

			// Return test id
			return $id;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to save the Questions against testid
	 *
	 * @param   array  $lesson_data  Array of the question ids
	 * @param   array  $test_id      Id of the test against which questions are to be stored
	 *
	 * @return  mixed  The test id on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function saveLesson($lesson_data, $test_id)
	{
		$db    = Factory::getDBO();
		$query = "SHOW COLUMNS FROM #__tjlms_lessons";
		$db->setQuery($query);
		$res   = $db->loadColumn();

		$ldata = array();

		foreach ($res as $col)
		{
			if (isset($lesson_data[$col]))
			{
				$ldata[$col] = $lesson_data[$col];
			}
		}

		// Get the largest ordering value for a given where clause.
		$query = $this->_db->getQuery(true)->select('MAX(ordering)')->from('#__tjlms_lessons');
		$this->_db->setQuery($query);
		$max_ordering      = (int) $this->_db->loadResult();

		$ldata['ordering'] = ($max_ordering + 1);

		$db     = Factory::getDBO();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$ltable = Table::getInstance('Lesson', 'TjlmsTable', array('dbo', $db));

		if (!($ltable->save($ldata) === true))
		{
			echo $db->stderr();

			return false;
		}

		return $ltable->id;
	}

	/**
	 * function is used to save sorting of modules.
	 *
	 * @param   int  $testId  Course id
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 **/
	public function getSectionsOrderList($testId)
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('id,ordering FROM #__tmt_tests_sections');
		$query->where('test_id=' . (int) $testId);

		$db->setQuery($query);
		$sectionOrder = $db->loadobjectlist();

		if (!empty($sectionOrder) && count($sectionOrder) > 0)
		{
			$list = array();

			foreach ($sectionOrder as $key => $s_order)
			{
				$list[ $s_order->id ] = $s_order->ordering;
			}

			return $list;
		}
		else
		{
			return false;
		}
	}

	/**
	 *	Save ordering for modules whose ordering has been change
	 *
	 * @param   int  $key      module_id
	 *
	 * @param   int  $newRank  new ordering
	 *
	 * @param   int  $testId   Test id
	 *
	 * @return boolean
	 *
	 * @since	1.0
	 **/
	public function switchOrder($key, $newRank, $testId)
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->update('#__tmt_tests_sections');
		$query->set('ordering=' . (int) $newRank);
		$query->where('id=' . (int) $key);
		$query->where('test_id=' . (int) $testId);
		$db->setQuery($query);
		$db->execute();

		return true;
	}

	/**
	 * Method to save the Questions against testid
	 *
	 * @param   array  $question_ids  Array of the question ids
	 * @param   array  $section_ids   Array of the section ids
	 * @param   array  $test_id       Id of the test against which questions are to be stored
	 *
	 * @return  mixed  The test id on success, false on failure.
	 *
	 * @since   1.0
	 */
	/*public function saveTestQuestions($question_ids, $section_ids, $test_id)
	{
		if (!empty($question_ids))
		{
			$db = Factory::getDBO();

			foreach ($question_ids as $ind => $qid)
			{
				if ($qid)
				{
					$order = $this->getMaxOrder($section_ids[$ind]);
					$tq = new stdClass;
					$tq->test_id = $test_id;
					$tq->question_id = $qid;
					$tq->order = $order;
					$tq->section_id = $section_ids[$ind];
					$this->saveTestQuestion($tq);
				}
			}
		}

		return true;
	}*/

	/**
	 * Method to max order of question in a section
	 *
	 * @param   INT  $section_id  id of section
	 *
	 * @return  INT  count of question entries in a section
	 *
	 * @since   1.0
	 */
	public function getMaxOrder($section_id)
	{
		$count = 0;
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('COUNT(`order`)');
		$query->from($db->quoteName('#__tmt_tests_questions'));
		$query->where($db->quoteName('section_id') . " = " . $db->quote($section_id));

		// Reset the query using our newly populated query object.
		$db->setQuery($query);
		$count = $db->loadResult();

		if ($count)
		{
			return $count;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Method to save the single Question against test_id
	 *
	 * @param   object  $data  object of test's question
	 *
	 * @return  boolean  true on successful save false on failure .
	 *
	 * @since   1.0
	 */
	public function addQuestionToSection($data)
	{
		if (!empty($data['question_id']) && !empty($data['test_id']) && !empty($data['section_id']))
		{
			$data['order'] = $this->getMaxOrder($data['section_id']);

			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tmt/tables');
			$table = Table::getInstance('Testquestions', 'TmtTable', array('dbo', $this->_db));

			// Check if question already added to the section and test. This is added if we are editing a que from a quiz and hit save and insert
			$table->load(array('question_id' => $data['question_id'], 'test_id' => $data['test_id'], 'section_id' => $data['section_id']));

			if (!empty($table->id))
			{
				return $table;
			}

			if (!($table->save($data) === true))
			{
				echo $this->_db->stderr();

				return false;
			}

			$table->load($table->id);

			return $table;
		}

		return false;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  mixed  The test id on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function saveForm($data)
	{
		$id    = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('test.id');
		$state = (!empty($data['state'])) ? 1 : 0;

		if (!empty($data['eligibility_criteria']))
		{
			$data['eligibility_criteria'] = ',' . implode(',', $data['eligibility_criteria']) . ',';
		}
		else
		{
			$data['eligibility_criteria'] = '';
		}

		if (!empty($data['start_date']))
		{
			$data['start_date'] = $this->TechjoomlaCommon->getDateInUtc($data['start_date']);
		}

		if (!empty($data['end_date']))
		{
			$data['end_date'] = $this->TechjoomlaCommon->getDateInUtc($data['end_date']);
		}

		if (isset($data['quiz_type']) and $data['quiz_type'] == 1)
		{
			$data['type'] = 'set';
		}
		else
		{
			$data['type'] = 'plain';
		}

		if (isset($data['total_marks']))
		{
			$data['total_marks'] = $data['total_marks'];
		}

		if (isset($data['passing_marks']))
		{
			$data['passing_marks'] = $data['passing_marks'];
		}

		if (isset($data['time_duration']))
		{
			$data['time_duration'] = $data['time_duration'];
		}

		if (isset($data['show_time']))
		{
			$data['show_time'] = $data['show_time'];
		}

		if (isset($data['show_time_finished']))
		{
			$data['show_time_finished'] = $data['show_time_finished'];
		}

		if (isset($data['time_finished_duration']))
		{
			$data['time_finished_duration'] = $data['time_finished_duration'];
		}

		$table = $this->getTable();

		// For new record.
		if (!$id)
		{
			// Add created time.
			// $data['created_on'] = date('Y-m-d H:i:s');
			$data['created_on'] = $this->techjoomlacommon->getDateInUtc(Factory::getDate());

			// Publish new record.
			// $data['state'] = 1;
		}

		// Bind data
		if (!$table->bind($data))
		{
			$this->setError($table->getError());

			return false;
		}

		if (isset($data['unique']))
		{
			$table->lesson_id = $data['unique'];
		}

		// Save test data
		if ($table->save($data) === true)
		{
			$db = Factory::getDbo();
			$id = $table->id;

			if (isset($data['format_id']))
			{
				$quiz_data         = new stdClass;
				$quiz_data->id     = $data['format_id'];
				$quiz_data->source = $id;
				$db->updateObject('#__tjlms_media', $quiz_data, 'id');
			}

			// Save reviewers data first
			// Get all existing reviewer ids posted from form
			if (!empty($data['reviewers_hidden']))
			{
				$form_reviewers = $data['reviewers_hidden'];
				$form_reviewers = implode(',', $form_reviewers);
			}

			// Get all existing reviewer ids from db
			$db = Factory::getDbo();
			$existing_reviewers_ids = array();

			if (!empty($form_reviewers))
			{
				$query = $db->getQuery(true);
				$query->select('id, user_id')->from(' #__tmt_tests_reviewers')->where(' test_id =' . $id)->where(' id IN(' . $form_reviewers . ')');
				$db->setQuery($query);
				$existing_reviewers = $db->loadObjectList();

				// Convert it to '#_tmt_tests_reviewers => #_tmt_tests_reviewers' format
				foreach ($existing_reviewers as $er)
				{
					$existing_reviewers_ids[$er->id] = $er->user_id;
				}
			}

			// First, delete existing reviewers which are not selected by user
			$db               = $this->getDbo();
			$query            = $db->getQuery(true);
			$newFormReviewers = $data['reviewers'];
			$newFormReviewers = implode(',', $newFormReviewers);
			$query = 'DELETE FROM #__tmt_tests_reviewers
			WHERE test_id =' . (int) $id . '
			AND user_id NOT IN(' . $newFormReviewers . ')';
			$db->setQuery($query);
			$db->execute();
			$count = count($data['reviewers']);

			// Now, save all reviewers options
			for ($i = 0;$i < $count;$i++)
			{
				$updateFlag = 'insert';

				// Let's represent data as needed by reviewers table
				$newdata          = new stdClass;
				$newdata->id      = '';
				$newdata->test_id = $table->id;
				$newdata->user_id = $data['reviewers'][$i];

				// Check if the current record is already present in db
				if (in_array($data['reviewers'][$i], $existing_reviewers_ids))
				{
					$newdata->id = $data['reviewers'][$i];
					$updateFlag  = 'update';
					unset($data['reviewers'][$i]);
				}

				switch ($updateFlag)
				{
				case 'insert':

					if (!$this->_db->insertObject('#__tmt_tests_reviewers', $newdata, 'id'))
					{
						echo $this->_db->stderr();

						return false;
					}
				break;
				case 'update':

					if (!$this->_db->updateObject('#__tmt_tests_reviewers', $newdata, 'id'))
					{
						echo $this->_db->stderr();

						return false;
					}
				break;
				}
			}

			// Now, let's save questions against this test.

			// Delete old questions.
			// @TODO use update, instead of delete
			$query = $this->_db->getQuery(true);
			$query = 'DELETE FROM #__tmt_tests_questions
			WHERE test_id =' . (int) $id;
			$this->_db->setQuery($query);
			$this->_db->execute();
			$count = count($data['cid']);

			if ($count > 0)
			{
				for ($i = 0;$i < $count;$i++)
				{
					if (isset($data['cid'][$i]))
					{
						$obj              = new stdClass;
						$obj->test_id     = $id;
						$obj->question_id = $data['cid'][$i];
						$obj->order       = $i;

						// Insert object
						if (!$this->_db->insertObject('#__tmt_tests_questions', $obj, 'id'))
						{
							echo $this->_db->stderr();

							return false;
						}
					}
				}
			}

			// Check if a test is an objective test.
			$query = $db->getQuery(true);
			$query->select('tq.id')->from(' #__tmt_tests_questions AS tq')
					->join('LEFT', '#__tmt_questions AS q ON q.id = tq.question_id ')
					->where(' ( q.type = "text" OR q.type = "textarea" )')->where('tq.test_id =' . (int) $id);
			$db->setQuery($query);
			$subjective_tqs = $db->loadObjectList();
			$test_data      = new stdClass;
			$test_data->id  = (int) $id;

			if (count($subjective_tqs))
			{
				$test_data->isObjective = 0;
			}
			else
			{
				$test_data->isObjective = 1;
			}

			if (!$this->_db->updateObject('#__tmt_tests', $test_data, 'id'))
			{
				echo $this->_db->stderr();

				return false;
			}

			$lesson             = new stdClass;
			$lesson->id         = $data['lesson_id'];
			$lesson->state      = $data['state'];
			$lesson->name       = $data['title'];
			$lesson->alias      = $data['alias'];
			$lesson->created_by = $data['created_by'];

			// @TODO no_of_attempts hardcoded for now
			$lesson->no_of_attempts = $data['no_of_attempts'];
			$lesson->attempts_grade = $data['attempts_grade'];
			$lesson->consider_marks = $data['consider_marks'];

			if (isset($data['free_lesson']))
			{
				$lesson->free_lesson = $data['free_lesson'];
			}

			$lesson->eligibility_criteria = $data['eligibility_criteria'];
			$lesson->format = $data['format'];

			if (!$this->_db->updateObject('#__tjlms_lessons', $lesson, 'id'))
			{
				echo $this->_db->stderr();

				return false;
			}

			if (!empty($data['addquiz']))
			{
				// Checking if called from LMS
				$test_ID = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('test.id');

				if (!$test_ID)
				{
					// Add lesson entry in LMS

					// Get the largest ordering value for a given where clause.
					$query = $this->_db->getQuery(true)->select('MAX(ordering)')->from('#__tjlms_lessons');
					$this->_db->setQuery($query);
					$max_ordering       = (int) $this->_db->loadResult();
					$lesson             = new stdClass;
					$lesson->id         = '';
					$lesson->state      = $data['state'];
					$lesson->name       = $data['title'];
					$lesson->alias      = $table->alias;
					$lesson->created_by = $this->user->id;
					$lesson->course_id  = $data['course_id'];
					$lesson->mod_id     = $data['mod_id'];

					$lesson->start_date = $data['start_date'];
					$lesson->end_date   = $data['end_date'];
					$lesson->ordering   = ($max_ordering + 1);

					// @TODO no_of_attempts hardcoded for now
					$lesson->no_of_attempts = $data['no_of_attempts'];
					$lesson->attempts_grade = $data['attempts_grade'];
					$lesson->consider_marks = $data['consider_marks'];
					$lesson->ideal_time     = $data['ideal_time'];

					if (isset($data['free_lesson']))
					{
						$lesson->free_lesson = $data['free_lesson'];
					}

					$lesson->eligibility_criteria = $data['eligibility_criteria'];
					$lesson->format               = $data['format'];

					if (!$this->_db->insertObject('#__tjlms_lessons', $lesson, 'id'))
					{
						echo $this->_db->stderr();

						return false;
					}

					$lesson_id = $this->_db->insertid();

					// Add test integration entry
					$inte            = new stdClass;
					$inte->id        = '';
					$inte->lesson_id = $lesson_id;
					$inte->test_id   = $id;

					if (!$this->_db->insertObject('#__tjlms_tmtquiz', $inte, 'id'))
					{
						echo $this->_db->stderr();

						return false;
					}
				}
				else
				{
					if (!$data['unique'])
					{
						$query = $db->getQuery(true);
						$query->select('tq.lesson_id')->from(' #__tjlms_tmtquiz AS tq')->where('tq.test_id =' . (int) $test_ID);
						$db->setQuery($query);
						$lesson_id = $db->loadResult();
					}
					else
					{
						$lesson_id = $data['unique'];
					}

					$lesson             = new stdClass;
					$lesson->id         = $lesson_id;
					$lesson->state      = $data['state'];
					$lesson->name       = $data['title'];
					$lesson->alias      = $table->alias;
					$lesson->created_by = $this->user->id;

					// $lesson->course_id = $data['course_id'];
					$lesson->mod_id     = $data['mod_id'];
					$lesson->start_date = $data['start_date'];
					$lesson->end_date   = $data['end_date'];

					// @TODO no_of_attempts hardcoded for now

					if (isset($data['no_of_attempts']))
					{
						$lesson->no_of_attempts = $data['no_of_attempts'];
					}

					$lesson->attempts_grade = $data['attempts_grade'];
					$lesson->consider_marks = $data['consider_marks'];

					if (isset($data['free_lesson']))
					{
						$lesson->free_lesson = $data['free_lesson'];
					}

					$lesson->eligibility_criteria = $data['eligibility_criteria'];
					$lesson->ideal_time           = $data['ideal_time'];

					if (!$this->_db->updateObject('#__tjlms_lessons', $lesson, 'id'))
					{
						echo $this->_db->stderr();

						return false;
					}
				}
			}

			// Update child values
			$query = $db->getQuery(true);
			$query->select('id')->from('#__tmt_tests')->where('parent_id =' . (int) $id);
			$db->setQuery($query);
			$childTests                   = $db->loadColumn();
			$childData                    = new stdClass;
			$childData->resume            = $data['resume'];
			$childData->termscondi        = $data['termscondi'];
			$childData->answer_sheet      = $data['answer_sheet'];
			$childData->questions_shuffle = $data['questions_shuffle'];
			$childData->answers_shuffle   = $data['answers_shuffle'];

			if (!empty($childTests))
			{
				foreach ($childTests as $childTestId)
				{
					$testTableData     = $childData;
					$testTableData->id = $childTestId;

					if (!$this->_db->updateObject('#__tmt_tests', $testTableData, 'id'))
					{
						echo $this->_db->stderr();

						return false;
					}
				}
			}
			// End update child values

			// Return test id
			return $id;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to assign a test as a lesson to the course
	 *
	 * @param   array  $data  test data.
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function addTestTocourse($data)
	{
		$test = $this->getTable("test");
		$test->load($data['id']);

		// Check if test is already added in course
		$query = $this->_db->getQuery(true);
		$query->select('l.id');
		$query->from($this->_db->qn('#__tjlms_lessons', 'l'));
		$query->join('LEFT', $this->_db->qn('#__tjlms_media', 'tm') . ' ON (' . $this->_db->qn('l.media_id') . ' = ' . $this->_db->qn('tm.id') . ')');
		$query->where('tm.source = ' . $data['id']);
		$query->where('l.course_id = ' . $data['course_id']);
		$this->_db->setQuery($query);

		if ($this->_db->loadResult())
		{
			$this->setError(Text::_("COM_TJLMS_COURSE_TEST_ADD_ERROR_ALREADY_EXISTS"));

			return false;
		}

		$data['id']                 = '';
		$data['gradingtype']        = $test->gradingtype;
		$data['state']              = $test->state;
		$data['format']             = $test->gradingtype;
		$data['title']              = $test->title;
		$data['total_marks']        = $test->total_marks;
		$data['passing_marks']      = $test->passing_marks;
		$data['free_lesson']        = 0;
		$data['no_of_attempts']     = 0;
		$data['attempts_grade']     = 0;
		$data['consider_marks']     = 1;
		$data['resume']             = 0;
		$data['show_all_questions'] = 0;
		$data['in_lib']             = 0;

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$mediaModel              = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');
		$lessonModel             = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');

		$mediaData               = array();
		$mediaData['format']     = $data['gradingtype'];
		$mediaData['sub_format'] = $data['gradingtype'] . '.test';
		$mediaData['source']     = $test->id;

		$mediaId                 = $mediaModel->getMediaIdByData($mediaData);

		$setId                   = 0;
		$ifSubjective            = $this->checkifSubjective($test->id);

		if (empty($mediaId))
		{
			$mediaModel->save($mediaData);
			$data['media_id'] = $mediaModel->getState($mediaModel->getName() . '.id');
		}
		else
		{
			$data['media_id'] = $mediaId;
		}

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$lessonTable = Table::getInstance('Lesson', 'TjlmsTable');
		$lessonTable->load(array('media_id' => $data['media_id']));

		if (!empty($lessonTable->id))
		{
			$data['catid'] = $lessonTable->catid;
		}

		if ($ifSubjective)
		{
			if (!empty($lessonTable->id))
			{
				// Get the set id from xref
				$assessxreftable = Table::getInstance('Assessmentxref', 'TjlmsTable');
				$assessxreftable->load(array('lesson_id' => $lessonTable->id));

				if ($assessxreftable->id)
				{
					$setId = $assessxreftable->set_id;
				}
			}
		}

		$lessonModel->save($data);
		$lessonId = $lessonModel->getState($lessonModel->getName() . '.id');

		if ($ifSubjective && $setId)
		{
			/*This is to add assessment for the subjective quiz. for that we need to check if media is already added
			and for that media some lesson in any course is created with assessment. If yes, we will reuse assessment
			else we will add new assessment*/

			$assessxreftable            = Table::getInstance('Assessmentxref', 'TjlmsTable');
			$assessxreftable->set_id    = $setId;
			$assessxreftable->lesson_id = $lessonId;
			$assessxreftable->store();
		}
		elseif ($ifSubjective && !$setId)
		{
			$assessmentData                              = array();
			$assessmentData['add_assessment']            = 1;
			$assessmentData['assessment_params']         = array();
			$assessmentData['assessment_attempts']       = 1;
			$assessmentData['assessment_answersheet']    = 1;
			$assessmentData['answersheet_options']       = array('assessments' => 0, 'param_marks' => 0, 'param_comments' => 0, 'feedback' => 0);
			$assessmentData['assessment_attempts_grade'] = 0;
			$assessmentData['allow_attachments']         = 0;
			$assessmentData['lesson_id']                 = $lessonId;
			$assessmentData['assessment_student_name']   = 1;

			// Get Assessment added against lesson
			require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/assessment.php';
			$assessModel = BaseDatabaseModel::getInstance('Assessment', 'TjlmsModel');
			$assessModel->save($assessmentData);
		}

		return $lessonId;
	}

	/**
	 * Method to format the rules in an array
	 * Input Array
	 * (
	 *	[questions_count] => Array
	 *		(
	 *			[0] => 2
	 *			[1] => 1
	 *		)
	 *	[questions_marks] => Array
	 *		(
	 *			[0] => 2
	 *			[1] => 2
	 *		)
	 *)
	 * Output [0] => Array
	 *  (
	 * [questions_count] => 2
	 * [questions_marks] => 2
	 * )
	 *
	 * @param   object  $rulesdata  post of the form.
	 *
	 * @return  mixed  array of Object of questions
	 *
	 * @since 1.0
	 */
	public function formatRulesData($rulesdata)
	{
		$rule                       = array();
		$rule['questions_count']    = $rule['pull_questions_count'] = $rule['questions_marks'] = 0;
		$rule['questions_category'] = $rule['questions_level'] = $rule['questions_type'] = '';

		foreach ($rulesdata['questions_count'] as $ind => $rule_cnt)
		{
			$rule['questions_count']      = $rule_cnt;
			$rule['pull_questions_count'] = isset($rulesdata['pull_questions_count'][$ind])?$rulesdata['pull_questions_count'][$ind]:0;

			if (!empty($rulesdata['questions_marks'][$ind]))
			{
				$rule['questions_marks'] = $rulesdata['questions_marks'][$ind];
			}

			if (!empty($rulesdata['questions_category'][$ind]))
			{
				$rule['questions_category'] = $rulesdata['questions_category'][$ind];
			}

			if (!empty($rulesdata['questions_level'][$ind]))
			{
				$rule['questions_level'] = $rulesdata['questions_level'][$ind];
			}

			if (!empty($rulesdata['questions_type'][$ind]))
			{
				$rule['questions_type'] = $rulesdata['questions_type'][$ind];
			}
		}

		return $rule;
	}

	/**
	 * Method to get questions based on rules posted using ajax
	 *
	 * @param   object  $rule  post of the form.
	 *
	 * @return  mixed  array of Object of questions
	 *
	 * @since 1.0
	 */
	public function fetchQuestions($rule)
	{
		$rulesData = $questions = array();

		if (!empty($rule['testId']))
		{
			$temp    = $this->getTestQuestions($rule['testId']);
			$qidsStr = implode(",", $temp);
		}

		$questionMarks = 0;

		if (isset($rule['marks']))
		{
			$questionMarks = $rule['marks'];
		}

		$questionCount = $rule['pull_questions_count'];

			// To Fetch 2x questions than questions specified by rules

			/*if ($quiz_type)
			{
				$questionCount = $rule['pull_questions_count'];
			}*/

		if ($questionCount && $questionCount > 0)
		{
			$query = $this->_db->getQuery(true);

			// Select the required fields from the table.
			$query->select('q.*');
			$query->from($this->_db->qn('#__tmt_questions', 'q'));

			// Manage own will only fetch own created question
			JLoader::import('administrator.components.com_tmt.helpers.tmt', JPATH_SITE);
			$canManage = TmtHelper::canManageQuestions();

			if ($canManage == -1)
			{
				$query->where($this->_db->qn('q.created_by') . ' = ' . (int) $this->user->get('id'));
			}

			// Join over the foreign key 'category_id'
			$query->select($this->_db->qn('c.title', 'category'));
			$query->join('LEFT', $this->_db->qn('#__categories', 'c') . ' ON (' . $this->_db->qn('c.id') . ' = ' . $this->_db->qn('q.category_id') . ')');
			$query->where($this->_db->qn('q.gradingtype') . ' = ' . $this->_db->quote($rule['gradingtype']));

			// Get questions from published categories only.
			$query->where($this->_db->qn('c.published') . ' = 1');

			// Apply posted data filters
			// Validate not empty, greater than zero, integer
			if (!empty($questionMarks) && ((int) $questionMarks > 0))
			{
				$query->where($this->_db->qn('q.marks') . ' = ' . (int) $questionMarks);
			}

			if (!empty($rule['category']))
			{
				$query->where($this->_db->qn('q.category_id') . ' = ' . (int) $rule['category']);
			}

			if (!empty($rule['difficulty_level']))
			{
				$query->where($this->_db->qn('q.level') . ' = ' . $this->_db->quote($rule['difficulty_level']));
			}

			if (!empty($rule['question_type']))
			{
				$query->where($this->_db->qn('q.type') . ' = ' . $this->_db->quote($rule['question_type']));
			}

			// Get only published questions for test - add questions
			$query->where($this->_db->qn('q.state') . ' = 1');

			// To Get non-duplicated questions
			if (!empty($qidsStr))
			{
				$query->where($this->_db->qn('q.id') . 'not in(' . $qidsStr . ')');
			}

			// To get only non duplicate questions used in other rules
			if (!empty(array_filter($rule["otherRulesQuestions"])))
			{
				$rule["otherRulesQuestions"] = Joomla\Utilities\ArrayHelper::toInteger($rule["otherRulesQuestions"]);

				$query->where($this->_db->qn('q.id') . ' not in(' . implode(",", $rule["otherRulesQuestions"]) . ')');
			}

			$query->order('RAND()');

			// Validate not empty, greater than zero, integer
			if ($questionCount)
			{
				$this->_db->setQuery($query, 0, (int) $questionCount);
			}
			else
			{
				$this->_db->setQuery($query);
			}

			$currQuestions = $this->_db->loadObjectList();

			foreach ($currQuestions as $qindex => $q)
			{
				// This is important
				$q->title = htmlentities($q->title);

				switch ($q->type)
				{
					case "radio":
						$q->type = Text::_('COM_TMT_QTYPE_MCQ_SINGLE_SHORT');
					break;
					case "checkbox":
						$q->type = Text::_('COM_TMT_QTYPE_MCQ_MULTIPLE_SHORT');
					break;
					case "text":
						$q->type = Text::_('COM_TMT_QTYPE_SUB_TEXT');
					break;
					case "textarea":
						$q->type = Text::_('COM_TMT_QTYPE_SUB_TEXTAREA');
					break;
					default:
						$q->type = $q->type;
				}

				$questions[$qindex] = $q;
			}

			$rulesData['questions'] = $questions;

			// To fetch total count and remaining count
			$totalCount                 = count($rulesData['questions']);
			$rulesData['que_available'] = $totalCount;

			$remaining_questions_count  = $questionCount - $totalCount;

			if ($remaining_questions_count < 0)
			{
				$remaining_questions_count = 0;
			}

			$rulesData['que_need']      = (int) $questionCount;
			$rulesData['que_remaining'] = $remaining_questions_count;

			if ($rule['forDynamic'] == 1)
			{
				/*Save rule and save Questions fetched only if the rule satisfies the minimun question count condition*/
				if ($totalCount >= $rule["questions_count"])
				{
					$rule['quiz_id']    = $rule['testId'];
					$rule['section_id'] = $rule['sectionId'];

					if ($this->storeRule($rule))
					{
						foreach ($rulesData['questions'] as $question)
						{
							$qData['test_id']     = $rule['testId'];
							$qData['section_id']  = $rule['sectionId'];
							$qData['question_id'] = $question->id;
							$this->addQuestionToSection($qData);
						}
					}
				}
			}
		}

		return $rulesData;
	}

	/**
	 * Function to store a rule against a test and section
	 *
	 * @param   ARRAY  $rule  rule array consisting questions_marks, questions_count, pull_questions_count
	 *
	 * @return  boolean
	 */
	public function storeRule($rule)
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/models', 'quizrules');
		$model = BaseDatabaseModel::getInstance('QuizRules', 'TmtModel');

		if ($rule['marks'] && $rule['marks'] > 0 && $rule['questions_count'] && $rule['questions_count'] > 0)
		{
			try
			{
				// Save or update the rules
				$model->save($rule);
			}
			catch (Exception $e)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $questionCount  The count of the questions.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since 1.0
	 */
	public function getMultiplicationFactor($questionCount = '')
	{
		return 2;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since 1.0
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			$id = $item->id;

			$this->getTestQuestionsBySections($item);

			$lessonId = $this->app->input->get('lid', '0', 'INT');

			if ($lessonId)
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/lesson.php';
				$lessonModel = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');

				$lesson                     = $lessonModel->getItem($lessonId);
				$item->max_attempt          = $lesson->max_attempt;
				$item->eligibility_criteria = $lesson->eligibility_criteria;
				$item->no_of_attempts       = $lesson->no_of_attempts;
				$item->attempts_grade       = $lesson->attempts_grade;
				$item->free_lesson          = $lesson->free_lesson;
				$item->consider_marks       = $lesson->consider_marks;
				$item->resume               = $lesson->resume;
				$item->catid                = $lesson->catid;
				$item->in_lib               = $lesson->in_lib;
				$item->resume               = $lesson->resume;

				if ($item->gradingtype == 'exercise')
				{
					$item->livetrackReviews          = $lesson->livetrackReviews;
					$item->set_id                    = $lesson->set_id;
					$item->assessment_title          = $lesson->assessment_title;
					$item->assessment_attempts       = $lesson->assessment_attempts;
					$item->assessment_attempts_grade = $lesson->assessment_attempts_grade;
					$item->assessment_answersheet    = $lesson->assessment_answersheet;
					$item->assessment_student_name   = $lesson->assessment_student_name;
					$item->answersheet_options       = $lesson->answersheet_options;
					$item->assessment_params         = $lesson->assessment_params;
				}
			}

			if ($item->image)
			{
				try
				{
					$uploadPath  = $this->tjLmsParams->get('lesson_image_upload_path', "/images/com_tjlms/lessons/");
					$mediaObj    = TJMediaStorageLocal::getInstance(array("id" => $item->image ,"uploadPath" => $uploadPath));
					$item->image = $mediaObj->media;
				}
				catch (\Exception $e)
				{
					// If we have any kind of error here => false;
					$this->setError($e->getMessage());

					return false;
				}
			}
		}

		return $item;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   object  $table  of test table
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	public function prepareTable($table)
	{
		jimport('joomla.filter.output');

		if (empty($table->id))
		{
			// Set ordering to the last item if not set
			if (@$table->ordering === '')
			{
				$db              = Factory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM #__tmt_tests');
				$max             = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * To store the rules data into rules table
	 *
	 * @param   int    $test_id  This is quiz id/ test_id
	 *
	 * @param   array  $rules    This contains all the rules data
	 *
	 * @return void
	 *
	 * @since 1.0
	 */

	public function storeRules ($test_id, $rules)
	{
		$db = Factory::getDbo();

		// Format the posted data
		$rulesdata = array();

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/models', 'quizrules');
		$model                = BaseDatabaseModel::getInstance('QuizRules', 'TmtModel');
		$getStoredRulesId     = $model->getStoredRulesData($test_id);

		$rulesdata['rule_id'] = $rules->get('rule_id', array(), 'array');

		// Check if any rule is deleted by taking difference between two array.
		if (!empty($rulesdata['rule_id']))
		{
			$deleteRules = array_diff($getStoredRulesId, $rulesdata['rule_id']);

			if ($deleteRules)
			{
				$model->delete($deleteRules);
			}
		}

		$rulesdata['quiz_id']              = $test_id;
		$rulesdata['questions_count']      = $rules->get('questions_count', '', 'array');
		$rulesdata['pull_questions_count'] = $rules->get('pull_questions_count', '', 'array');
		$rulesdata['questions_marks']      = $rules->get('questions_marks', '', 'array');
		$rulesdata['questions_category']   = $rules->get('questions_category', '', 'array');
		$rulesdata['questions_level']      = $rules->get('questions_level', '', 'array');
		$rulesdata['questions_type']       = $rules->get('questions_type', '', 'array');

		$count = count($rulesdata['questions_count']);

		for ($i = 0;$i < $count;$i++)
		{
			$data['id']                   = 0;
			$data['marks']                = $rulesdata['questions_marks'][$i];
			$data['questions_count']      = $rulesdata['questions_count'][$i];
			$data['pull_questions_count'] = $rulesdata['pull_questions_count'][$i];

			if (!empty($rulesdata['rule_id'][$i]))
			{
				$data['id'] = $rulesdata['rule_id'][$i];
			}

			$data['quiz_id'] = $rulesdata['quiz_id'];

			if ($data['marks'] && $data['marks'] > 0 && $data['questions_count'] && $data['questions_count'] > 0)
			{
				$data['category']         = isset($rulesdata['questions_category'][$i]) ? $rulesdata['questions_category'][$i] : '';
				$data['difficulty_level'] = isset ($rulesdata['questions_level'][$i]) ? $rulesdata['questions_level'][$i] : '';
				$data['question_type']    = isset($rulesdata['questions_type'][$i]) ? $rulesdata['questions_type'][$i] : '';

				$quizRulesModel           = BaseDatabaseModel::getInstance('QuizRules', 'TmtModel');

				// Save or update the rules
				$quizRulesModel->save($data);
			}
		}
	}

	/**
	 * To retrive the question ids for particular test.
	 *
	 * @param   int  $testId  This is quiz id/ test_id
	 *
	 * @return string
	 *
	 * @since 1.0
	 */

	public function getTestQuestions($testId)
	{
		$db    = $this->_db;
		$query = $db->getQuery(true);

		$query->select('q.question_id');
		$query->from('`#__tmt_tests_questions` AS q ');
		$query->where('q.test_id =' . $testId);
		$db->setQuery($query);
		$questions = $db->loadColumn();

		return $questions;
	}

	/**
	 * Method to get information of is quiz quiz attempted already
	 *
	 * @param   array  $data  This is quiz id/ test_id
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */

	public function isInUse($data)
	{
		$test_id = $data->get('test_id', '', 'STRING');

		$db      = $this->_db;
		$query   = $db->getQuery(true);

		$query->select('count(q.id)');
		$query->from('`#__tmt_tests` AS q ');
		$query->where('q.parent_id =' . $test_id);
		$db->setQuery($query);
		$attempted_count = $db->loadResult();

		if ($attempted_count > 0)
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Method to get count of how many questions are in question bank.
	 *
	 * @param   array  $data  Contains rules and post data
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */

	public function getCounts($data)
	{
		$db = $this->getDbo();

		// Format the posted data
		$newdata                       = array();
		$newdata['questions_marks']    = $data->get('questions_marks', '', 'array');
		$newdata['questions_category'] = $data->get('questions_category', '', 'array');
		$newdata['questions_level']    = $data->get('questions_level', '', 'array');
		$newdata['questions_type']     = $data->get('questions_type', '', 'array');

		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('count(*)');
		$query->from('`#__tmt_questions` AS q ');

		// Join over the foreign key 'category_id'
		$query->select('c.title AS category ');
		$query->join('LEFT', '#__categories AS c ON c.id = q.category_id ');

		// Apply posted data filters
		// Validate not empty, greater than zero, integer
		if (!empty($newdata['questions_marks'][0]) && ((int) $newdata['questions_marks'][0] > 0))
		{
			$query->where(' q.marks=' . (int) $newdata['questions_marks'][0]);
		}

		if (!empty($newdata['questions_category'][$i]))
		{
			$query->where(' q.category_id=' . (int) $newdata['questions_category'][0]);
		}

		if (!empty($newdata['questions_level'][0]))
		{
			$query->where(" q.level='" . $newdata['questions_level'][0] . "'");
		}

		if (!empty($newdata['questions_type'][0]))
		{
			$query->where(" q.type='" . $newdata['questions_type'][0] . "'");
		}

		// Get only published questions for test - add questions
		$query->where('q.state=1');

		$db->setQuery($query);
		$currQuestions = $db->loadResult();

		return $currQuestions;
	}

	/**
	 * Make or undo the question compulsory
	 *
	 * @param   int  $testId      test id
	 * @param   int  $sectionId   Section id
	 * @param   int  $questionId  question id
	 * @param   int  $compulsory  0/1
	 *
	 * @return  boolean
	 *
	 * @since  1.3
	 */
	public function changeCompulsoryState($testId, $sectionId, $questionId, $compulsory)
	{
		$testQue = $this->getTable("testquestions");
		$testQue->load(array("test_id" => $testId, "section_id" => $sectionId, "question_id" => $questionId));

		try
		{
			if ($testQue->id)
			{
				$testQue->is_compulsory = $compulsory;

				$testQue->store();
			}
			else
			{
				throw new Exception(Text::_('COM_TMT_TEST_CHANGE_DATA_NOT_FOUND'), 400);

				return false;
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Method to get all the subjective Questions in quiz
	 *
	 * @param   int  $testId  Id of the test against which questions are to be stored
	 *
	 * @return  array  The test id on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function checkifSubjective($testId)
	{
		$db    = Factory::getDbo();

		$query = $db->getQuery(true);

		// Check if a test is an objective test.
		$query->select("count('tq.id')");
		$query->from("#__tmt_tests_questions AS tq");
		$query->join("LEFT", "#__tmt_questions AS q ON q.id = tq.question_id");
		$query->where("(q.type = 'text' OR q.type = 'textarea' OR q.type = 'file_upload' OR q.type = 'rating')");
		$query->where("tq.test_id =" . (int) $testId);
		$db->setQuery($query);

		return ($db->loadResult()) ? 1 : 0;
	}

	/**
	 * Delete question from a quiz
	 *
	 * @param   int  $questionId  question id
	 *
	 * @param   int  $testId      test id
	 *
	 * @return  boolean
	 *
	 * @since  1.3
	 */
	public function deleteTestQuestion($questionId, $testId)
	{
		$testQue = $this->getTable("testquestions");
		$testQue->load(array("test_id" => $testId, "question_id" => $questionId));

		if ($testQue->id)
		{
			$sectionId = $testQue->section_id;
			$query     = $this->_db->getQuery(true);

			// Delete question.
			$conditions = array(
				$this->_db->quoteName('question_id') . ' = ' . $this->_db->quote($questionId),
				$this->_db->quoteName('test_id') . ' = ' . $this->_db->quote($testId),
			);

			$query->delete($this->_db->quoteName('#__tmt_tests_questions'));
			$query->where($conditions);

			$this->_db->setQuery($query);

			if ($this->_db->execute())
			{
				$query = $this->_db->getQuery(true);

				// Fields to update.
				$fields = array(
					$this->_db->quoteName('order') . ' = ' . $this->_db->quoteName('order') . ' - ' . 1
				);

				$conditions = array(
					$this->_db->quoteName('section_id') . ' = ' . $this->_db->quote($sectionId),
					$this->_db->quoteName('order') . ' > ' . $this->_db->quote($testQue->order)
				);

				$query->update($this->_db->quoteName('#__tmt_tests_questions'))->set($fields)->where($conditions);
				$this->_db->setQuery($query);
				$result = $this->_db->execute();

				return true;
			}
		}

		return false;
	}

	/**
	 * Method to delete all the questions associated with section - This is most probably be called from the
	 * SET, when FETCH Questions is done
	 *
	 * @param   int  $testId     test id
	 * @param   int  $sectionId  Section id
	 *
	 * @return  JSON response
	 *
	 * @since 1.3
	 */
	public function deleteSectionRules($testId, $sectionId)
	{
		// Delete the questions added againt the section
		$query = $this->_db->getQuery(true);
		$query->delete($this->_db->quoteName('#__tmt_quiz_rules'))
				->where($this->_db->qn('section_id') . ' = ' . $this->_db->q((int) $sectionId))
				->where($this->_db->qn('quiz_id') . ' = ' . $this->_db->q((int) $testId));
		$this->_db->setQuery($query);

		if (!$this->_db->execute())
		{
			$this->setError("Error while removing the section-rule association");

			return false;
		}

		return true;
	}

	/**
	 * Method to delete all the questions associated with section - This is most probably be called from the
	 * SET, when FETCH Questions is done
	 *
	 * @param   int  $testId     test id
	 * @param   int  $sectionId  Section id
	 *
	 * @return  JSON response
	 *
	 * @since 1.3
	 */
	public function deleteSectionQuestions($testId, $sectionId)
	{
		// Delete the questions added againt the section
		$query = $this->_db->getQuery(true);
		$query->delete($this->_db->quoteName('#__tmt_tests_questions'))
				->where($this->_db->qn('section_id') . ' = ' . $this->_db->q((int) $sectionId))
				->where($this->_db->qn('test_id') . ' = ' . $this->_db->q((int) $testId));
		$this->_db->setQuery($query);

		if (!$this->_db->execute())
		{
			$this->setError("Error while removing the question-section association");

			return false;
		}

		return true;
	}

	/**
	 * Method to delete all the questions associated with test - This is most probably be called from the
	 * SET, when FETCH Questions is done
	 *
	 * @param   int  $testId  test id
	 *
	 * @return  JSON response
	 *
	 * @since 1.3
	 */
	public function deleteTestQuestions($testId)
	{
		// Delete the questions added againt the section
		$query = $this->_db->getQuery(true);
		$query->delete($this->_db->quoteName('#__tmt_tests_questions'))
				->where($this->_db->qn('test_id') . ' = ' . $this->_db->q((int) $testId));
		$this->_db->setQuery($query);

		if (!$this->_db->execute())
		{
			$this->setError("Error while removing the question-test association");

			return false;
		}

		return true;
	}

	/**
	 * Method to delete all rules associated with test - This is most probably be called from the
	 * SET, when FETCH Questions is done
	 *
	 * @param   int  $testId  test id
	 *
	 * @return  JSON response
	 *
	 * @since 1.3
	 */
	public function deleteTestRules($testId)
	{
		// Delete the questions added againt the section
		$query = $this->_db->getQuery(true);
		$query->delete($this->_db->quoteName('#__tmt_quiz_rules'))
				->where($this->_db->qn('quiz_id') . ' = ' . $this->_db->q((int) $testId));
		$this->_db->setQuery($query);

		if (!$this->_db->execute())
		{
			$this->setError("Error while removing the test-rule association");

			return false;
		}

		return true;
	}

	/**
	 * Delete section from a test
	 *
	 * @param   int  $sectionId  section Id
	 *
	 * @return  void
	 *
	 * @since  1.3
	 */
	public function deleteSection($sectionId)
	{
		$testSec = $this->getTable("section");
		$testSec->load($sectionId);

		try
		{
			// Get the order of the section which has to be deleted
			$currentOrder = $testSec->ordering;

			// Update the order for rest of the section
			$query = $this->_db->getQuery(true);
			$query->update($this->_db->qn('#__tmt_tests_sections'))
					->set($this->_db->qn('ordering') . ' = `ordering`-1')
					->where($this->_db->qn('ordering') . ' > ' . $this->_db->q((int) $currentOrder));
			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
				$this->setError("Error while setting the ordering for sections");

				return false;
			}

			// Delete the questions added againt the section
			$query = $this->_db->getQuery(true);
			$query->delete($this->_db->quoteName('#__tmt_tests_questions'))
					->where($this->_db->qn('section_id') . ' = ' . $this->_db->q((int) $sectionId));
			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
				$this->setError("Error while removing the question-section association");

				return false;
			}

			// Delete the section
			$query = $this->_db->getQuery(true);
			$query->delete($this->_db->quoteName('#__tmt_tests_sections'))
					->where($this->_db->qn('id') . ' = ' . $this->_db->q((int) $sectionId));
			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
				$this->setError("Error while deleting the section");

				return false;
			}

			return true;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Update the test marks from the questions if set then by rules added
	 *
	 * @param   int  $testId    test Id
	 * @param   int  $testType  plain / set
	 *
	 * @return  void
	 *
	 * @since  1.3
	 */
	public function setTestMarksbyQuestions($testId, $testType)
	{
		$marks = 0;
		$table = $this->getTable();
		$table->load($testId);

		if ($table->id)
		{
			if ($testType == "plain")
			{
				$query = $this->_db->getQuery(true);
				$query->select("SUM(marks)");
				$query->from('#__tmt_tests_questions AS tq');
				$query->join('INNER', '#__tmt_questions AS q ON q.id = tq.question_id');
				$query->join('LEFT', '#__tmt_tests_sections AS s ON tq.section_id = s.id');
				$query->where('tq.test_id =' . (int) $testId);
				$query->where('s.state =1');
				$this->_db->setQuery($query);
				$marks = $this->_db->loadResult();
			}
			else
			{
				$query = $this->_db->getQuery(true);
				$query->select(array("tr.questions_count", "tr.marks"));
				$query->from(' #__tmt_quiz_rules AS tr');
				$query->join('LEFT', '#__tmt_tests_sections AS s ON tr.section_id = s.id');
				$query->where('tr.quiz_id =' . (int) $testId);
				$query->where('s.state =1');
				$this->_db->setQuery($query);
				$rules = $this->_db->loadObjectList();

				foreach ($rules as $rule)
				{
					$marks += $rule->questions_count * $rule->marks;
				}
			}

			$table->type        = $testType;
			$table->total_marks = $marks;
			$table->store();
		}

		return $marks;
	}

	/**
	 * Method to check whether a test is attempted by any user.
	 *
	 * @param   integer  $testId  testId
	 *
	 * @return  boolean  True if test is attempted.
	 *
	 * @since   1.3.5
	 */
	public function isTestAttempted($testId)
	{
		$query = $this->_db->getQuery(true);
		$query->select($this->_db->quoteName(array('ta.id')));
		$query->from($this->_db->quoteName('#__tmt_tests_attendees', 'ta'));
		$query->where($this->_db->quoteName('ta.test_id') . ' = ' . (int) $testId);
		$query->setLimit('1');
		$this->_db->setQuery($query);

		if (!empty($this->_db->loadResult()))
		{
			return true;
		}

		return false;
	}

	/**
	 * Method to get lesson data from test id
	 *
	 * @param   int  $lessonId  lesson id
	 *
	 * @return  string  lesson object
	 *
	 * @since  1.0
	 */
	public function getTestFromLesson($lessonId)
	{
		if ($lessonId)
		{
			$query = $this->_db->getQuery(true);
			$query->select('tt.*');
			$query->from('`#__tmt_tests` AS tt');
			$query->leftjoin('`#__tjlms_media` AS m ON m.source = tt.id');
			$query->leftjoin('`#__tjlms_lessons` AS l ON l.media_id = m.id');
			$query->where('l.id = ' . (int) ($lessonId));
			$db->setQuery($query);

			$test = $db->loadObject();

			return $test;
		}
	}
}
