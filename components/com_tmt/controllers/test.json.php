<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Component\ComponentHelper;

require_once JPATH_COMPONENT . '/controller.php';
JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

/**
 * Test controller class.
 *
 * @since  1.0.0
 */
class TmtControllerTest extends TmtController
{
	/**
	 * Get test details
	 *
	 * @return  JSON
	 *
	 * @since  1.3.15
	 */
	public function getTestSectionsQuestions()
	{
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		try
		{
			$post          = Factory::getApplication()->input->post;
			$testId        = $post->get('id', 0, 'INT');
			$lessonTrackId = $post->get('invite_id', 0, 'INT');
			$pageNo        = $post->get('pageNo', 1, 'INT');

			$model = $this->getModel('Test', 'TmtModel');
			$model->setState('test.id', $testId);
			$model->setState('test.lessonTrackId', $lessonTrackId);

			// Get test data, sections
			$sectionsQuestions = $model->getuserTestSectionsQuestions($lessonTrackId, $testId, $pageNo);

			echo new JsonResponse($sectionsQuestions);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to get the tr html for question, when question to be associated with quiz
	 *
	 * @since    1.0
	 *
	 * @return void
	 *
	 * @deprecated  1.4.0  This function will be removed and no replacement will be provided
	 */
	public function getQuestionHtml()
	{
		$app      = Factory::getApplication();
		$postData = $app->input->post;
		$question = $postData->get('question', array(), "ARRAY");
		$test     = $postData->get('test', array(), "ARRAY");
		$qNo      = $postData->get('qNo', 0, "INT");

		try
		{
			$params   = ComponentHelper::getParams('com_tjlms');
			$mediaLib = TJMediaStorageLocal::getInstance();

			$data             = array();
			$data['question'] = (object) $question;
			$data['item']     = (object) $test;
			$data['params']   = $params;
			$data['mediaLib'] = $mediaLib;
			$data['qNo']      = $qNo;

			$layout = new FileLayout('question.question');
			$result = $layout->render($data);

			echo new JsonResponse($result);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}

		jexit();
	}

	/**
	 * function used to sync time
	 *
	 * @return  JSON
	 *
	 * @since  1.0.0
	 */
	public function updateTimeSpent()
	{
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		try
		{
			$post          = Factory::getApplication()->input->post;
			$lessonTrackId = $post->get('ltId', 0, 'INT');
			$testId        = $post->get('testId', 0, 'INT');
			$timeSpent     = $post->get('timeSpent', 0, 'INT');
			$model         = $this->getModel('Test', 'TmtModel');
			$ret           = $model->updateTimeSpent($lessonTrackId, $testId, $timeSpent);

			echo new JsonResponse($ret);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to save posted item data and redirect tests list
	 *
	 * @return  void
	 *
	 * @since 1.3
	 */
	public function removeFileuploadAnswer()
	{
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$input             = Factory::getApplication()->input;
		$post              = $input->post;
		$answertableId     = $post->get('answerId', 0, "INT");
		$userAnswerMediaId = $post->get('answerMediaId', 0, "INT");

		try
		{
			$testModel = $this->getModel('Test', 'TmtModel');
			$res       = $testModel->removeFileuploadAnswer($answertableId, $userAnswerMediaId);

			if ($res)
			{
				$msg = Text::_("COM_TMT_REMOVE_ANSWER_SUCCESS");
				echo new JsonResponse(1, $msg);
			}
			else
			{
				$msg = Text::_("COM_TMT_REMOVE_ANSWER_FAILED");
				echo new JsonResponse(0, $msg, true);
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to save the answer given by the user for a question
	 *
	 * @return  void
	 *
	 * @since 1.3
	 */
	public function saveQuestionAnswer()
	{
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$post = Factory::getApplication()->input->post;

		$ansData           = array();
		$ansData['qid']    = $post->get('questionId', 0, "INT");
		$ansData['testid'] = $post->get('testId', 0, "INT");
		$ansData['ltid']   = $post->get('ltId', 0, "INT");
		$ansData['answer'] = $post->get('answer', 0, "STRING");

		try
		{
			$testModel = $this->getModel('Test', 'TmtModel');
			$result    = $testModel->validateAnswer($ansData);

			if ($result)
			{
				$res = $testModel->saveTestQuestionAnswers($ansData);

				if ($res)
				{
					$msg = Text::_("COM_TMT_TEST_SAVE_ANSWER_SUCCESS");
					echo new JsonResponse(1, $msg);
				}
				else
				{
					$msg = Text::_("COM_TMT_TEST_SAVE_ANSWER_FAILED");
					echo new JsonResponse(0, $msg, true);
				}
			}
			else
			{
				$msg = $testModel->getError();
				echo new JsonResponse(0, $msg, true);
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to get Number of questions attempted by a user in an attempt
	 *
	 * @return  void
	 *
	 * @since 1.3
	 */
	public function getTotalAttemptedQuestion()
	{
		$post = Factory::getApplication()->input->post;

		$ansData = array();
		$testId  = $post->get('testId', 0, "INT");
		$ltId    = $post->get('ltId', 0, "INT");
		$userId  = Factory::getUser()->id;

		try
		{
			$testModel = $this->getModel('Test', 'TmtModel');
			$res       = $testModel->getAttemptedQuestions($testId, $userId, $ltId);

			// We are expecting count and it can also be zero (0)
			if ($res != "")
			{
				echo new JsonResponse($res);
			}
			else
			{
				echo new JsonResponse(0, '', true);
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to get Number of questions attempted by a user in an attempt
	 *
	 * @return  void
	 *
	 * @since 1.3
	 */
	public function submitTest()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$post = Factory::getApplication()->input->post;

		$attemptData           = array();
		$attemptData['testId'] = $post->get('testId', 0, "INT");
		$attemptData['ltId']   = $post->get('ltId', 0, "INT");
		$attemptData['Itemid'] = $post->get('Itemid', 0, "INT");
		$attemptData['userId'] = Factory::getUser()->id;
		$attemptData['live']   = $post->get('live', 1, "INT");

		try
		{
			$testModel = $this->getModel('Test', 'TmtModel');
			$res       = $testModel->submitTest($attemptData);

			if ($res)
			{
				echo new JsonResponse($res);
			}
			else
			{
				echo new JsonResponse(0, '', true);
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to save each page Questions when Next or FINISH is clicked
	 *
	 * @return  OBJECT
	 *
	 * @since 1.3
	 */
	public function saveEachPageQueAnswers()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app       = Factory::getApplication();
		$post      = $app->input->post;
		$test      = $post->get("test", array(), "ARRAY");
		$questions = $post->get("questions", array(), "ARRAY");

		$ansData           = array();
		$ansData['testid'] = $test['id'];
		$ansData['ltid']   = $ltId = $post->get("invite_id", 0, "INT");

		try
		{
			$testModel = $this->getModel('Test', 'TmtModel');

			if (!empty($questions['mcqs']))
			{
				foreach ($questions['mcqs'] as $qid => $ansArray)
				{
					$ansData['qid']    = $qid;
					$ansData['answer'] = implode(",", $ansArray);
					$res               = $testModel->saveTestQuestionAnswers($ansData);

					if (!$res)
					{
						echo new JsonResponse('', Text::_('COM_TMT_TEST_ERROR_SAVING_DATA_MSG'), true);
						$app->close();
					}
				}
			}

			if (!empty($questions['upload']))
			{
				foreach ($questions['upload'] as $qid => $ansArray)
				{
					$ansData['qid']    = $qid;
					$ansData['answer'] = implode(",", $ansArray);
					$res               = $testModel->saveTestQuestionAnswers($ansData);

					if (!$res)
					{
						echo new JsonResponse('', Text::_('COM_TMT_TEST_ERROR_SAVING_DATA_MSG'), true);
						$app->close();
					}
				}
			}

			if (!empty($questions['subjective']))
			{
				foreach ($questions['subjective'] as $qid => $ans)
				{
					$ansData['qid']    = $qid;
					$ansData['answer'] = $ans;
					$res               = $testModel->saveTestQuestionAnswers($ansData);

					if (!$res)
					{
						echo new JsonResponse('', Text::_('COM_TMT_TEST_ERROR_SAVING_DATA_MSG'), true);
						$app->close();
					}
				}
			}

			if (!empty($questions['rating']))
			{
				$flag = false;

				foreach ($questions['rating'] as $qid => $ans)
				{
					$ansData['qid']    = $qid;
					$ansData['answer'] = $ans;
					$result            = $testModel->validateAnswer($ansData);

					if (!$result)
					{
						$flag = true;
						continue;
					}

					$res = $testModel->saveTestQuestionAnswers($ansData);

					if (!$res)
					{
						echo new JsonResponse('', Text::_('COM_TMT_TEST_ERROR_SAVING_DATA_MSG'), true);
						$app->close();
					}
				}

				if ($flag)
				{
					echo new JsonResponse('', Text::_('COM_TMT_TEST_INVALID_ANSWER'), true);
					$app->close();
				}
			}

			$testModel->addTablePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
			$ltTable = Table::getInstance('Lessontrack', 'TjlmsTable');
			$ltTable->load($ltId);
			$ltTable->current_position = $test['ltCp'];
			$ltTable->lesson_status    = "incomplete";

			if (!$ltTable->store())
			{
				echo new JsonResponse('', Text::_('COM_TMT_TEST_ERROR_SAVING_DATA_MSG'), true);
				$app->close();
			}

			echo new JsonResponse(1);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to flag question
	 *
	 * @return  void
	 *
	 * @since  1.3.15
	 */
	public function flagQuestion()
	{
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$post = Factory::getApplication()->input->post;

		$ansData             = array();
		$ansData['qId']      = $post->get('qId', 0, "INT");
		$ansData['testId']   = $post->get('testId', 0, "INT");
		$ansData['inviteId'] = $post->get('invite_id', 0, "INT");

		try
		{
			$testModel = $this->getModel('Test', 'TmtModel');
			$res       = $testModel->flagQuestion($ansData);

			if ($res)
			{
				$msg = Text::_("COM_TMT_TEST_SAVE_ANSWER_SUCCESS");
				echo new JsonResponse(1, $msg);
			}
			else
			{
				$msg = Text::_("COM_TMT_TEST_SAVE_ANSWER_FAILED");
				echo new JsonResponse(0, '', true);
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}
}
