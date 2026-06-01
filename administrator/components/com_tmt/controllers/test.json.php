<?php
/**
 * @package     TMT
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Object\CMSObject;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Controller\FormController;

/**
 * Test controller class.
 *
 * @since  1.0
 */
class TmtControllerTest extends FormController
{
	/**
	 * Method to save posted item data and redirect tests list
	 *
	 * @param   integer  $key     key
	 * @param   integer  $urlVar  url var
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function saveBasic($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app   = Factory::getApplication();
		$input = $app->input;

		// Get all jform data
		$data  = $input->post->get('jform', '', 'ARRAY');
		$files = $input->files->get('jform', '', 'image');

		$data['image'] = $files['image'];
		$model         = $this->getModel('Test', 'TmtModel');

		try
		{
			$form = $model->getForm();
			$data = $model->validate($form, $data);

			if ($data == false)
			{
				$errors = $model->getErrors();
				$this->processErrors($errors);

				echo new JsonResponse('', Text::_('COM_TMT_FORM_VALIDATATION_FAILED'), true);
			}

			/*$data['lesson_id'] = $lesson_id;
			$data['format'] = $data['gradingtype'];

			if (isset($data['eligibility_criteria']) && !empty($data['eligibility_criteria']))
			{
				$data['eligibility_criteria'] = ',' . implode(',', $data['eligibility_criteria']) . ',';
			}
			else
			{
				$data['eligibility_criteria'] = '';
			}*/

			if ($model->save($data))
			{
				$result       = array();
				$result['id'] = ($data['id']) ? $data['id'] : $model->getState($model->getName() . '.id');

				/*if ($data['course_id'] && $data['mod_id'])
				{
					$result['lesson_id'] = ($data['lesson_id']) ? $data['lesson_id'] : $model->getState($model->getName() . '.lesson_id');
				}*/

				$result['section_id'] = $model->getState($model->getName() . '.section_id');

				echo new JsonResponse($result, Text::_('COM_TMT_FORM_SAVE_SUCCESS'));
			}
			else
			{
				$errors = $model->getErrors();
				$this->processErrors($errors);
				echo new JsonResponse('', Text::_('COM_TMT_FORM_VALIDATATION_FAILED'), true);
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to save posted item data and redirect tests list
	 *
	 * @param   integer  $key     key
	 * @param   integer  $urlVar  url var
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function saveTimeDuration($key = null, $urlVar = null)
	{
		// Initialise variables.
		$app   = Factory::getApplication();
		$model = $this->getModel('Test', 'TmtModel');

		// Get all jform data
		$data = $app->input->get('jform', array(), 'array');

		try
		{
			$data['ideal_time'] = $data['time_duration'];
			$data['time_duration'] = ($data['time_duration'])?$data['time_duration']:0;
			$data['time_finished_duration'] = ($data['time_finished_duration'])?$data['time_finished_duration']:0;

			$model->save($data);

			$result['id'] = (!empty($data['id'])) ? $data['id'] : (int) $model->getState($model->getName() . '.id');

			echo new JsonResponse($result, Text::_('COM_TMT_FORM_SAVE_SUCCESS'));
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to save posted item data and redirect tests list
	 *
	 * @param   integer  $key     key
	 * @param   integer  $urlVar  url var
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function saveMarks($key = null, $urlVar = null)
	{
		// Initialise variables.
		$app   = Factory::getApplication();
		$model = $this->getModel('Test', 'TmtModel');

		// Get all jform data
		$data = $app->input->get('jform', array(), 'array');

		try
		{
			$model->save($data);

			$result['id'] = (!empty($data['id'])) ? $data['id'] : (int) $model->getState($model->getName() . '.id');

			echo new JsonResponse($result, Text::_('COM_TMT_FORM_SAVE_SUCCESS'));
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to save posted item data and redirect tests list
	 *
	 * @return  JSON
	 *
	 * @since 1.0
	 */
	public function saveAssessment()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$data  = $input->get('jform', array(), 'array');

		foreach ($data["assessment_params"] as $key => $criteria)
		{
			$data["assessment_params"][$key]['title'] = StringHelper::trim($criteria['title']);

			if ($data["assessment_params"][$key]['title'] == "")
			{
				$errorMsg = Text::_('COM_TJLMS_INVALID_ASSESSMENT_TITLE');
				echo new JsonResponse(0, $errorMsg, true);
				$app->close();
			}
		}

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$mediaModel      = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');
		$lessonModel     = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');
		$assessmentModel = BaseDatabaseModel::getInstance('Assessment', 'TjlmsModel');
		$testModel       = $this->getModel('Test', 'TmtModel');

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tmt/tables');
		$testTable  = Table::getInstance('Test', 'TmtTable');
		$testTable->load($data['id']);
		$properties = $testTable->getProperties(1);
		$testTable  = ArrayHelper::toObject($properties, CMSObject::class);

		$lessonData                  = array();
		$lessonData['id']            = $data['lesson_id'];
		$lessonData['course_id']     = $data['course_id'];
		$lessonData['mod_id']        = $data['mod_id'];
		$lessonData['title']         = $testTable->title;
		$lessonData['state']         = $testTable->state;
		$lessonData['created_by']    = $testTable->created_by;
		$lessonData['description']   = $testTable->description;
		$lessonData['ideal_time']    = $testTable->time_duration;
		$lessonData['total_marks']   = $testTable->total_marks;
		$lessonData['passing_marks'] = $testTable->passing_marks;
		$lessonData['format']        = $testTable->gradingtype;

		$gradingtype = $data['gradingtype'];
		$lessonId    = $data['lesson_id'];
		$testId      = $data['id'];

		$result = array();

		try
		{
			if (!empty($data['course_id']) && !empty($data['mod_id']))
			{
				if ($gradingtype)
				{
					$mediaData['format']     = $gradingtype;
					$mediaData['sub_format'] = $gradingtype . '.test';
					$mediaData['source']     = $testId;
					$mediaData['org_filename'] = '';
					$mediaData['saved_filename'] = ''; 
					$mediaData['created_by'] = !empty($testTable->created_by) ? $testTable->created_by : Factory::getUser()->id; 
					$mediaData['path'] = ''; 
					$mediaData['storage'] = ''; 
					$mediaData['params'] = '';

					$mediaId = $mediaModel->getMediaIdByData($mediaData);

					if (empty($mediaId))
					{
						$mediaModel->save($mediaData);
						$lessonData['media_id'] = $mediaModel->getState($mediaModel->getName() . '.id');
					}
				}
				
				$id = $lessonModel->save($lessonData);
				
				if (!empty($id))
				{
					$data['lesson_id'] = !empty($lessonId) ? $lessonId : $id;
					$assessmentModel->save($data);

					$data['answer_sheet'] = $data['assessment_answersheet'];
					$testModel->save($data);

					$result['id'] = (!empty($data['id'])) ? $data['id'] : (int) $testModel->getState($testModel->getName() . '.id');
					$result['lesson_id'] = $data['lesson_id'];
				}
			}

			echo new JsonResponse($result, Text::_('COM_TMT_FORM_SAVE_SUCCESS'));
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to save posted item data and redirect tests list
	 *
	 * @return  JSON
	 *
	 * @since 1.0
	 */
	public function saveSection()
	{
		// Initialise variables.
		$app   = Factory::getApplication();
		$model = $this->getModel('Section', 'TmtModel');

		// Get all jform data
		$data = $app->input->get('section', array(), 'array');
		$data['description'] = !empty($data['description']) ? $data['description'] : '';
		$data['ordering'] = !empty($data['ordering']) ? $data['ordering'] : 0;
		$data['state'] = !empty($data['state']) ? $data['state'] : 1;
		$data['min_questions'] = !empty($data['min_questions']) ? $data['min_questions'] : 0;
		$data['max_questions'] = !empty($data['max_questions']) ? $data['max_questions'] : 0;

		try
		{
			$form = $model->getForm();
			$data = $model->validate($form, $data);

			if ($data)
			{
				$model->save($data);
			}

			$errors = $model->getErrors();

			if (!empty($errors))
			{
				$msg = array();

				// Push up to three validation messages out to the user.
				for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
				{
					if ($errors[$i] instanceof Exception)
					{
						$msg[] = $errors[$i]->getMessage();
					}
					else
					{
						$msg[] = $errors[$i];
					}
				}

				$errormsg = Text::_('COM_TMT_SECTION_SAVE_ERROR') . " : " . implode("\n", $msg);
				echo new JsonResponse(0, $errormsg, true);
			}
			else
			{
				$id = (!empty($data['id'])) ? $data['id'] : (int) $model->getState($model->getName() . '.id');

				$result = $model->getItem($id);

				echo new JsonResponse($result, Text::_('COM_TMT_FORM_SAVE_SUCCESS'));
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to save posted item data and redirect tests list
	 *
	 * @param   integer  $key     key
	 * @param   integer  $urlVar  url var
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function addQuestionToSection($key = null, $urlVar = null)
	{
		// Initialise variables.
		$app    = Factory::getApplication();
		$jinput = $app->input;

		$questionData = $jinput->getArray();

		try
		{
			$model = $this->getModel('Test', 'TmtModel');
			$ret   = $model->addQuestionToSection($questionData);

			$result['question_id'] = $ret->question_id;
			$result['test_id']     = $ret->test_id;
			$result['section_id']  = $ret->section_id;

			$smodel            = $this->getModel('Section', 'TmtModel');
			$result['section'] = $smodel->getItem($result['section_id']);

			echo new JsonResponse($result);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to procees errors
	 *
	 * @param   ARRAY  $errors  ERRORS
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function processErrors($errors)
	{
		$app = Factory::getApplication();

		if (!empty($errors))
		{
			$code = 500;
			$msg  = array();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					$code  = $errors[$i]->getCode();
					$msg[] = $errors[$i]->getMessage();
				}
				else
				{
					$msg[] = $errors[$i];
				}
			}

			$app->enqueueMessage(implode("\n", $msg), 'error');
		}
	}

	/**
	 * Function used to delete the question of a particular section
	 *
	 * @return true/false
	 *
	 * @since  1.0.0
	 **/
	public function deleteTestQuestion()
	{
		$input      = Factory::getApplication()->input;
		$sectionId  = $input->get('sectionId', 0, 'INT');
		$questionId = $input->get('questionId', 0, 'INT');
		$testId     = $input->get('testId', 0, 'INT');

		try
		{
			$model  = $this->getModel('Test', 'TmtModel');
			$result = $model->deleteTestQuestion($questionId, $testId);

			/*$smodel = $this->getModel('Section', 'TmtModel');
			$result['section'] = $smodel->getItem($sectionId);*/
			echo new JsonResponse($result, Text::_("COM_TMT_TEST_QUESTION_DELETE_SUCCESS"));
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Function used to get the section
	 *
	 * @return true/false
	 *
	 * @since  1.0.0
	 **/
	public function getSection()
	{
		$input      = Factory::getApplication()->input;
		$sectionId  = $input->get('id', 0, 'INT');
		$questionId = $input->get('questionId', 0, 'INT');
		$testId     = $input->get('testId', 0, 'INT');

		try
		{
			$smodel = $this->getModel('Section', 'TmtModel');
			$result = $smodel->getItem($sectionId);

			echo new JsonResponse($result);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Function to add test entry in media and update lesson with it
	 *
	 * @return void
	 *
	 * @since  1.3.0
	 **/
	public function addTestMedia()
	{
		$input = Factory::getApplication()->input;
		$post  = $input->post;

		$lessonId    = $input->get('lessonId', 0, 'INT');
		$testId      = $input->get('testId', 0, 'INT');
		$courseId    = $input->get('courseId', 0, 'INT');
		$modId       = $input->get('modId', 0, 'INT');
		$gradingtype = $input->get('gradingtype', '', 'string');

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$mediaModel  = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');
		$lessonModel = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');
		$testModel   = $this->getModel('Test', 'TmtModel');

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tmt/tables');
		$testTable = Table::getInstance('Test', 'TmtTable');
		$testTable->load($testId);
		$properties = $testTable->getProperties(1);
		$testTable  = ArrayHelper::toObject($properties, CMSObject::class);

		$data                   = array();
		$data['id']             = $lessonId;
		$data['course_id']      = $courseId;
		$data['mod_id']         = $modId;
		$data['no_of_attempts'] = $input->get('no_of_attempts', 0, 'INT');
		$data['attempts_grade'] = $input->get('attempts_grade', 0, 'INT');
		$data['free_lesson']    = $input->get('free_lesson', 0, 'INT');
		$data['consider_marks'] = $input->get('consider_marks', 0, 'INT');
		$data['resume']         = $input->get('resume', 0, 'INT');
		$data['title']          = $testTable->title;
		$data['state']          = $testTable->state;
		$data['created_by']     = $testTable->created_by;
		$data['description']    = $testTable->description;
		$data['ideal_time']     = $testTable->time_duration;
		$data['total_marks']    = $testTable->total_marks;
		$data['passing_marks']  = $testTable->passing_marks;
		$data['format']         = $testTable->gradingtype;
		$data['start_date']     = $testTable->start_date;
		$data['end_date']       = $testTable->end_date;
		$data['image']          = $testTable->image;
		$data['in_lib']         = $input->get('in_lib', 0, 'INT');
		$data['resume']         = $input->get('resume', 0, 'INT');
		$data['catid']          = $input->get('catid', 0, 'INT');

		$data['eligibility_criteria'] = '';

		if (!empty($input->get('eligibility_criteria', array(), 'ARRAY')))
		{
			$data['eligibility_criteria'] = ',' . implode(',', $input->get('eligibility_criteria', array(), 'ARRAY')) . ',';
		}

		try
		{
			if (!empty($courseId) && !empty($modId))
			{
				if ($gradingtype)
				{
					$mediaData['format']     = $gradingtype;
					$mediaData['sub_format'] = $gradingtype . '.test';
					$mediaData['source']     = $testId;
					$mediaData['org_filename'] = '';
					$mediaData['saved_filename'] = ''; 
					$mediaData['created_by'] = !empty($testTable->created_by) ? $testTable->created_by : Factory::getUser()->id; 
					$mediaData['path'] = ''; 
					$mediaData['storage'] = ''; 
					$mediaData['params'] = '';

					$mediaId = $mediaModel->getMediaIdByData($mediaData);

					if (empty($mediaId))
					{
						$mediaModel->save($mediaData);
						$data['media_id'] = $mediaModel->getState($mediaModel->getName() . '.id');
					}
				}

				$lessonId = $lessonModel->save($data);

				if ($lessonId)
				{
					/*Save Assessment if test/ exercise has subjective questions and no assessment added*/
					$ifSubjective = $testModel->checkifSubjective($testId);

					if ($ifSubjective && $gradingtype == 'quiz')
					{
						$assessmentData                              = array();
						$assessmentData['add_assessment']            = 1;
						$assessmentData['assessment_params']         = array();
						$assessmentData['assessment_attempts']       = 1;
						$assessmentData['assessment_answersheet']    = 1;
						$assessmentData['answersheet_options']       = array('assessments' => 0, 'param_marks' => 0, 'param_comments' => 0, 'feedback' => 0);
						$assessmentData['assessment_attempts_grade'] = 0;
						$assessmentData['allow_attachments']         = 0;
						$assessmentData['lesson_id']                 = ($data['id']) ? $data['id'] : $lessonModel->getState($lessonModel->getName() . '.id');
						$assessmentData['assessment_student_name']   = 1;

						// Get Assessment added against lesson
						require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/assessment.php';
						$assessModel = BaseDatabaseModel::getInstance('Assessment', 'TjlmsModel');
						$assessModel->save($assessmentData);
					}

					echo new JsonResponse($testId);
				}
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Function used to chage the state of the module
	 *
	 * @return true/false
	 *
	 * @since  1.0.0
	 **/
	public function changeSectionState()
	{
		$input       = Factory::getApplication()->input;
		$sectionId   = $input->get('id', 0, 'INT');
		$state       = $input->get('state', 0, 'INT');
		$statesArray = array('publish' => 1, 'unpublish' => 0);
		$state       = in_array($state, $statesArray) ? $state : 0;
		$text        = ($state) ? "PUBLISH" : "UNPUBLISH";

		$model    = $this->getModel('Section', 'TmtModel');

		try
		{
			$cids = array($sectionId);
			$model->publish($cids, $state);
			$errors = $model->getErrors();

			if ($errors)
			{
				$this->processErrors($errors);
				echo new JsonResponse('', Text::_('COM_TMT_TEST_SECTION_FAILED_' . $text), true);
			}
			else
			{
				$result = $model->getItem($sectionId);
				echo new JsonResponse($result, Text::_('COM_TMT_TEST_SECTION_SUCCESS_' . $text));
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Function used to chage the state of the module
	 *
	 * @return true/false
	 *
	 * @since  1.0.0
	 **/
	public function deleteSection()
	{
		$input     = Factory::getApplication()->input;
		$sectionId = $input->get('id', 0, 'INT');
		$model     = $this->getModel('Section', 'TmtModel');

		try
		{
			$result = $model->delete($sectionId);
			$errors = $model->getErrors();

			if ($errors)
			{
				$this->processErrors($errors);
				echo new JsonResponse('', Text::_('COM_TMT_TEST_SECTION_FAILED_DELETE'), true);
			}
			else
			{
				echo new JsonResponse($result, Text::_('COM_TMT_TEST_SECTION_SUCCESS_DELETE'));
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Function used to sort the questions when moved form one section to another
	 *
	 * @return true/false
	 *
	 * @since  1.0.0
	 **/
	public function sortSectionQuestions()
	{
		$db    = Factory::getDbo();
		$input = Factory::getApplication()->input;
		$post  = $input->post;

		// Get course ID
		$questionData['section_id'] = $sectionId = $post->get('sectionId', 0, 'INT');
		$questionData['test_id']    = $testId = $post->get('testId', 0, 'INT');
		$questions                  = $post->get('lessons', array(), "ARRAY");

		$model = $this->getModel('Test', 'TmtModel');

		try
		{
			$query = $db->getQuery(true);
			$query->select('question_id');
			$query->from('#__tmt_tests_questions AS tq');
			$query->where('tq.test_id =' . (int) $testId);
			$query->where('tq.section_id =' . (int) $sectionId);
			$db->setQuery($query);
			$sectionQuestion = $db->loadColumn();

			foreach ($sectionQuestion as $sq)
			{
				if (!in_array($sq, $questions))
				{
					$temp                = array();
					$temp['section_id']  = $sectionId;
					$temp['test_id']     = $testId;
					$temp['question_id'] = $sq;
					$temptable = $model->getTable("Testquestions");
					$temptable->load($temp);
					$temptable->delete($temptable->id);
				}
			}

			foreach ($questions as $ind => $qid)
			{
				if ($qid)
				{
					$questionData['question_id']     = $qid;
					$testQuestionsTable  = $model->getTable("Testquestions");
					$testQuestionsTable->load($questionData);

					$testQuestionsTable->question_id = $qid;
					$testQuestionsTable->section_id  = $sectionId;
					$testQuestionsTable->test_id     = $testId;
					$testQuestionsTable->order       = $ind;
					$testQuestionsTable->store();
				}
			}

			echo new JsonResponse(1);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to get questions based on rules posted using ajax
	 *
	 * @return  JSON response
	 *
	 * @since 1.3
	 */
	public function fetchQuestionsforRules()
	{
		$data    = Factory::getApplication()->input->post;
		$newdata = array();

		$newdata['id']                   = $data->get('id', 0, 'INT');
		$newdata['questions_count']      = $data->get('questions_count', 0, 'INT');
		$newdata['pull_questions_count'] = $data->get('pull_questions_count', 0, 'INT');
		$newdata['marks']                = $data->get('marks', 0, 'INT');
		$newdata['category']             = $data->get('category', 0, 'INT');
		$newdata['difficulty_level']     = $data->get('difficulty_level', '', 'string');
		$newdata['question_type']        = $data->get('question_type', '', 'string');
		$newdata['gradingtype']          = $data->get('gradingType', '', 'string');
		$newdata['otherRulesQuestions']  = $data->get('cid', array(), 'array');
		$newdata['testId']               = $data->get('testId', 0, 'INT');
		$newdata['sectionId']            = $data->get('sectionId', 0, 'INT');
		$newdata['forDynamic']           = $data->get('forDynamic', 0, 'INT');

		try
		{
			$model     = $this->getModel('Test', 'TmtModel');
			$questions = $model->fetchQuestions($newdata);
			echo new JsonResponse($questions);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to delete all the questions associated with section - This is most probably be called from the
	 * SET, when FETCH Questions is done
	 *
	 * @return  JSON response
	 *
	 * @since 1.3
	 */
	public function deleteSectionRulesQuestions()
	{
		$data      = Factory::getApplication()->input->post;
		$testId    = $data->get('testId', '', 'INT');
		$sectionId = $data->get('sectionId', 0, 'INT');

		try
		{
			$model = $this->getModel('Test', 'TmtModel');
			$ret   = $model->deleteSectionRules($testId, $sectionId);

			if ($ret)
			{
				$ret = $model->deleteSectionQuestions($testId, $sectionId);
			}

			echo new JsonResponse($ret);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to delete all the questions associated with a tset - This is most probably be called from the
	 * SET, when Refresh rules is done
	 *
	 * @return  JSON response
	 *
	 * @since 1.3
	 */
	public function deleteTestRulesQuestions()
	{
		$data   = Factory::getApplication()->input->post;
		$testId = $data->get('testId', '', 'INT');

		try
		{
			$model = $this->getModel('Test', 'TmtModel');
			$ret   = $model->deleteTestRules($testId);

			if ($ret)
			{
				$ret = $model->deleteTestQuestions($testId);
			}

			echo new JsonResponse($ret);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to delete all the questions associated with section - This is most probably be called from the
	 * SET, when FETCH Questions is done
	 *
	 * @return  JSON response
	 *
	 * @since 1.3
	 */
	public function deleteTestRule()
	{
		$data   = Factory::getApplication()->input->post;
		$ruleId = $data->get('ruleId', 0, 'INT');

		try
		{
			$model = $this->getModel('QuizRules', 'TmtModel');
			$ret   = $model->delete($ruleId);
			echo new JsonResponse($ret, Text::_('COM_TMT_TEST_MSG_DELETE_RULE_SUCCESS'));
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to delete all the questions associated with section - This is most probably be called from the
	 * SET, when FETCH Questions is done
	 *
	 * @return  JSON response
	 *
	 * @since 1.3
	 */
	public function setTestMarksbyQuestions()
	{
		$data     = Factory::getApplication()->input->post;
		$testId   = $data->get('testId', 0, 'INT');
		$testType = $data->get('testType', 'plain', 'string');

		try
		{
			$model = $this->getModel('test', 'TmtModel');
			$ret   = $model->setTestMarksbyQuestions($testId, $testType);
			echo new JsonResponse($ret);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Save ordering. Use when sorting is done using drap and drop
	 *
	 * @return  void
	 *
	 * @since  1.3
	 */
	public function sortSections()
	{
		try
		{
			$input    = Factory::getApplication()->input;
			$testId   = $input->get('testId', 0, 'INT');
			$sections = $input->get('sections', array(), 'ARRAY');
			$model    = $this->getModel('test', 'TmtModel');

			// Get the order of all the section present in that course.
			$data = $model->getSectionsOrderList($testId);

			foreach ($sections as $index => $sectionId)
			{
				if (!empty($sectionId))
				{
					// If the order are not same then change the order according to new orders
					if ($data[$sectionId] != ($index + 1))
					{
						$model->switchOrder($sectionId, ($index + 1), $testId);
					}
				}
			}

			echo new JsonResponse(1);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Add existing test as a lesson to the course
	 *
	 * @return  void
	 *
	 * @since  1.3
	 */
	public function addTestTocourse()
	{
		// Initialise variables.
		$app  = Factory::getApplication();
		$post = $app->input->post;

		$data = array();
		$data['course_id'] = $post->get('courseId', '', 'INT');
		$data['mod_id']    = $post->get('modId', '', 'INT');
		$data['id']        = $post->get('testId', '', 'INT');

		try
		{
			$model    = $this->getModel('test', 'TmtModel');
			$lessonid = $model->addTestTocourse($data);

			$errors = $model->getErrors();

			if (!empty($errors))
			{
				$msg = array();

				// Push up to three validation messages out to the user.
				for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
				{
					if ($errors[$i] instanceof Exception)
					{
						$msg[] = $errors[$i]->getMessage();
					}
					else
					{
						$msg[] = $errors[$i];
					}
				}

				$errormsg = implode("\n", $msg);
				echo new JsonResponse(0, $errormsg, true);
			}
			else
			{
				$result = array();
				$result['lesson_id'] = $lessonid;
				$result['redirect_url'] = Route::_('index.php?option=com_tmt&view=test&layout=edit&id='
					. $data['id'] . '&lid=' . $lessonid . '&cid='
					. $data['course_id'] . '&mid=' . $data['mod_id'], false
				);

				echo new JsonResponse($result);
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Make / undo the compulsory questions in a test
	 *
	 * @return true/false
	 *
	 * @since  1.0.0
	 **/
	public function changeCompulsoryState()
	{
		$input      = Factory::getApplication()->input;
		$sectionId  = $input->get('sectionId', 0, 'INT');
		$questionId = $input->get('questionId', 0, 'INT');
		$testId     = $input->get('testId', 0, 'INT');
		$compulsory = $input->get('compulsory', 0, 'INT');

		try
		{
			$model  = $this->getModel('Test', 'TmtModel');
			$result = $model->changeCompulsoryState($testId, $sectionId, $questionId, $compulsory);

			$msg = ($compulsory) ? Text::_("COM_TMT_TEST_QUESTION_COMPULSORY_SUCCESS") : Text::_("COM_TMT_TEST_QUESTION_UNDO_COMPULSORY_SUCCESS");
			echo new JsonResponse($result, $msg);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}
}
