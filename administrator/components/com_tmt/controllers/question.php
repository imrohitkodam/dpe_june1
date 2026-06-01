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

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\String\StringHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\FormController;
/**
 * Question controller class.
 *
 * @since  1.0
 */
class TmtControllerQuestion extends FormController
{
	public $view_list = '';
	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->view_list = 'questions';
		parent::__construct();
	}

	/**
	 * Method to save posted item data and redirect to the edit form.
	 *
	 * @since    1.0
	 *
	 * @return boolean
	 */
	public function apply()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$app    = Factory::getApplication();
		$jinput = $app->input;
		$data   = $jinput->post;

		$res    = $this->saveQuestion();

		if (!$res)
		{
			// Save the data in the session.
			$app->setUserState('com_tmt.edit.question.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tmt.edit.question.id');
			$this->setRedirect(Route::_('index.php?option=com_tmt&view=question&layout=edit&id=' . $id, false));

			return false;
		}

		// Check in the profile.
		if ($return)
		{
			$model->checkin($return);
		}

		// Clear the profile id from the session.
		$app->setUserState('com_tmt.edit.question.id', null);

		// Redirect to the edit screen.
		$tmtFrontendHelper = new tmtFrontendHelper;
		$itemid            = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=question');
		$redirect          = Route::_('index.php?option=com_tmt&view=question&layout=edit&id=' . $res . '&Itemid=' . $itemid, false);
		$msg               = Text::_('COM_TMT_Q_FORM_MESSAGE_SAVE_QUESTION');
		$this->setRedirect($redirect, $msg);

		// Flush the data from the session.
		$app->setUserState('com_tmt.edit.question.data', null);
	}

	/**
	 * Ajax save used when question is saved from lesson
	 *
	 * @since    1.2
	 *
	 * @return int|bool question id
	 */
	private function saveQuestion()
	{
		// Initialise variables.
		$app    = Factory::getApplication();
		$jinput = $app->input;

		// Get all form data
		$data         = $jinput->post;
		$files        = $jinput->files;

		$mediaFiles   = $files->get('jform', array (), 'array');

		// Get all jform data
		$questionData = $jinput->get('jform', array(), 'array');

		// *Important - get non-jform data for all non-jform fields
		$questionData['answers_text']             = $data->get('answers_text', array (), 'array');
		$questionData['answers_comments']         = $data->get('answers_comments', array (), 'array');
		$questionData['answers_iscorrect_hidden'] = $data->get('answers_iscorrect_hidden', array (), 'array');
		$questionData['answers_iscorrect']        = $data->get('answers_iscorrect', array (), 'array');
		$questionData['answers_marks']            = $data->get('answers_marks', array (), 'array');
		$questionData['answer_id_hidden']         = $data->get('answer_id_hidden', array (), 'array');

		// Answer Media
		$questionData['answer_media_type']        = $data->get('answer_media_type', array (), 'array');
		$questionData['answer_media_video']       = $data->get('answer_media_video', array (), 'array');
		$questionData['answer_media_image']       = $files->get('answer_media_image', array (), 'array');
		$questionData['answer_media_audio']       = $files->get('answer_media_audio', array (), 'array');
		$questionData['answer_media_file']        = $files->get('answer_media_file', array (), 'array');

		// Question media files
		$questionData['mediaFiles'] = $mediaFiles;

		$questionData['title']      = StringHelper::trim($questionData['title']);

		try
		{
			$model = $this->getModel('Question', 'TmtModel');

			// Validate the posted data.
			$form = $model->getForm();
			$data = $model->validate($form, $questionData);

			// Check for errors.
			if ($data === false)
			{
				// Get the validation messages.
				$errors = $model->getErrors();

				// Push up to three validation messages out to the user.
				for ($i = 0, $n = count($errors);$i < $n && $i < 3;$i++)
				{
					if ($errors[$i] instanceof Exception)
					{
						$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
					}
					else
					{
						$app->enqueueMessage($errors[$i], 'warning');
					}
				}

				return false;
			}

			$questionData = array_merge($questionData, $data);

			// Attempt to save the data.
			$qid = $model->save($questionData);

			if (!$qid)
			{
				$app->enqueueMessage(Text::sprintf(Text::_('COM_TMT_FORM_QUESTION_SAVE_FAILED'), $model->getError()), 'warning');
			}

			return $qid;
		}
		catch (Exception $e)
		{
			$app->enqueueMessage(Text::sprintf(Text::_('COM_TMT_FORM_QUESTION_SAVE_FAILED'), $e->message), 'warning');
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to save posted item data and redirect questions list
	 *
	 * @param   MIXED  $key     key
	 * @param   MIXED  $urlVar  urlVar
	 *
	 * @since    1.0
	 *
	 * @return boolean
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$app          = Factory::getApplication();

		// Get all form data
		$data         = $app->input->post;
		$questionData = $data->get('jform', array(), 'array');
		$task         = $data->get('task', '', 'STRING');

		$res          = $this->saveQuestion();

		$errorLink    = $link = '';

		$tempLink     = 'index.php?option=com_tmt&view=question&layout=edit';

		if ($questionData['id'])
		{
			$errorLink .= $tempLink . '&id=' . $questionData['id'];
		}

		if ($task == 'question.apply')
		{
			$link = $tempLink . '&id=' . $res;
		}
		elseif ($task == 'question.save')
		{
			$link = 'index.php?option=com_tmt&view=questions';
		}
		elseif ($task == 'question.save2new')
		{
			$link = 'index.php?option=com_tmt&task=question.edit';
		}

		// Check for errors.
		if ($res === false)
		{
			if (!$questionData['id'])
			{
				// Save the data in the session.
				$app->setUserState('com_tmt.edit.question.data', $questionData);
				$errorLink = $tempLink;
			}

			$redirect = Route::_($errorLink, false);
		}
		else
		{
			// Clear the profile id from the session.
			$app->setUserState('com_tmt.edit.question.id', null);
			$app->setUserState('com_tmt.edit.question.data', null);
			$redirect = Route::_($link, false);

			$msg      = Text::_('COM_TMT_Q_FORM_MESSAGE_SAVE_QUESTION');
		}

		$this->setRedirect($redirect, $msg);
	}

	/**
	 * Method for redirecting to the list view
	 *
	 * @param   MIXED  $key  key
	 *
	 * @since    1.0
	 *
	 * @return void
	 */
	public function cancel($key = null)
	{
		$tmtFrontendHelper = new tmtFrontendHelper;
		$redirect          = Route::_('index.php?option=com_tmt&view=questions', false);
		$msg               = Text::_('COM_TMT_MESSAGE_CANCEL');
		$this->setRedirect($redirect, $msg);
	}

	/**
	 * Method to get the tr html for question, when question to be associated with quiz
	 *
	 * @since    1.0
	 *
	 * @return void
	 */
	public function getQuestionRowHtml()
	{
		$app      = Factory::getApplication();
		$jinput   = $app->input;
		$postData = $jinput->post;
		$qid      = $postData->get('question_id', '', 'INT');

		try
		{
			$model             = $this->getModel('Question', 'TmtModel');
			$model->getAnswers = 0;
			$question          = $model->getItem($qid);

			$layout = new FileLayout('questionrowhtml');
			echo $layout->render((array) $question);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}

		jexit();
	}
}
