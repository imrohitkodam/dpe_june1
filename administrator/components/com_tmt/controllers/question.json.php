<?php
/**
 * @package    Com_Tmt
 * @copyright  Copyright (C) 2009 -2015 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

/**
 * Question controller class.
 *
 * @since  1.0
 */
class TmtControllerQuestion extends FormController
{
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

		// Initialise variables.
		$app = Factory::getApplication();
		$jinput = $app->input;

		// Get all form data
		$data = $jinput->post;

		$files = $jinput->files;

		$mediaFiles = $files->get('jform', array (), 'array');

		// Get all jform data
		$questionData = $jinput->get('jform', array(), 'array');

		// *Important - get non-jform data for all non-jform fields
		$questionData['answers_text'] 				= $data->get('answers_text', array (), 'array');
		$questionData['answers_comments']			= $data->get('answers_comments', array (), 'array');
		$questionData['answers_iscorrect_hidden'] 	= $data->get('answers_iscorrect_hidden', array (), 'array');
		$questionData['answers_iscorrect']			= $data->get('answers_iscorrect', array (), 'array');
		$questionData['answers_marks']				= $data->get('answers_marks', array (), 'array');
		$questionData['answer_id_hidden']			= $data->get('answer_id_hidden', array (), 'array');

		// Answer Media
		$questionData['answer_media_type']			= $data->get('answer_media_type', array (), 'array');
		$questionData['answer_media_video']			= $data->get('answer_media_video', array (), 'array');
		$questionData['answer_media_image']			= $files->get('answer_media_image', array (), 'array');
		$questionData['answer_media_audio']			= $files->get('answer_media_audio', array (), 'array');
		$questionData['answer_media_file']			= $files->get('answer_media_file', array (), 'array');

		// Question media files
		$questionData['mediaFiles'] = $mediaFiles;

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
						echo new JsonResponse(0, Text::sprintf(Text::_('COM_TMT_FORM_QUESTION_SAVE_FAILED'), $errors[$i]->getMessage()), true);
					}
					else
					{
						echo new JsonResponse(0, Text::sprintf(Text::_('COM_TMT_FORM_QUESTION_SAVE_FAILED'), $errors[$i]), true);
					}
				}

				return false;
			}

			$questionData = array_merge($questionData, $data);

			// Attempt to save the data.
			$qid = $model->save($questionData);

			/*$model->getAnswers = 0;
			$return = $model->getItem($qid);*/

			if (empty($qid))
			{
				echo new JsonResponse(0, Text::sprintf(Text::_('COM_TMT_FORM_QUESTION_SAVE_FAILED'), $model->getError()), true);
			}
			else
			{
				echo new JsonResponse($qid);
			}
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
	 */
	public function getQuestionRowHtml()
	{
		$app = Factory::getApplication();
		$jinput = $app->input;
		$postData = $jinput->post;
		$qid = $postData->get('question_id', '', 'INT');
		$testId = $postData->get('test_id', '', 'INT');
		$sectionId = $postData->get('section_id', '', 'INT');

		try
		{
			$model = $this->getModel('Question', 'TmtModel');
			$model->getAnswers = 0;
			$question = $model->getItem($qid);

			$question->canDeleteQ  = 1;
			$question->section_id = $sectionId;

			if ($testId)
			{
				$model = $this->getModel('test', 'TmtModel');
				$question->canDeleteQ = ($model->isTestAttempted($testId)) ? 0 : 1;
			}

			$layout = new FileLayout('questionrowhtml');
			$result = $layout->render((array) $question);

			echo new JsonResponse($result);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}

		jexit();
	}

	/**
	 * Delete media file
	 *
	 * @return JSON
	 *
	 * @since   2.0
	 */
	public function deleteMedia()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$tjlmsParams = ComponentHelper::getParams('com_tjlms');
		$app = Factory::getApplication();
		$jinput = $app->input;
		$model = $this->getModel('Question', 'TmtModel');

		$user = Factory::getUser();

		if (!$user->id)
		{
			echo new JsonResponse(1, Text::_('JERROR_ALERTNOAUTHOR'), true);
			$app->close();
		}

		$mediaId  = $jinput->get('media_id', 0, 'INT');
		$clientId = $jinput->get('client_id', 0, 'INT');
		$client = $jinput->get('client', '', 'STRING');

		$authorise = ($user->authorise("core.delete", 'com_tmt') == 1 ? true : false);

		// If I don't have access or if I am not admin
		if (!$authorise || !$user->authorise('core.admin'))
		{
			echo new JsonResponse(1, Text::_('JERROR_ALERTNOAUTHOR'), true);
			$app->close();
		}

		if (empty($mediaId) || empty($clientId) || empty($client))
		{
			echo new JsonResponse(1, Text::_('JERROR_ALERTNOAUTHOR'), true);
			$app->close();
		}

		// Check media exists
		$checkMediaXrefExistence = $model->checkMediaXrefExistence($clientId, $client);

		// While inserting in media xref table,
		// we have ensured that only entry will be allowed against one client & clientId
		if ($checkMediaXrefExistence->media_id != $mediaId)
		{
			echo new JsonResponse(1, Text::_('JERROR_ALERTNOAUTHOR'), true);
			$app->close();
		}

		$mediaLib = TJMediaStorageLocal::getInstance();
		$storagePath = $mediaLib->mediaUploadPath;
		$returnData  = $model->deleteMedia($mediaId, $storagePath, $client, $clientId);

		if (!$returnData)
		{
			echo new JsonResponse(1, $model->getError(), true);

			$app->close();
		}
		else
		{
			echo new JsonResponse(1, Text::_('COM_TMT_QUESTION_MEDIA_DELETE_SUCCESS'));

			$app->close();
		}
	}
}
