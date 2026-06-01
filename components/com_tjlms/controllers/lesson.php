<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.application.component.controller');

/**
 * Lesson controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerLesson extends TjlmsController
{
	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * */
	public function __construct()
	{
		$this->tjlmsdbhelperObj       = new tjlmsdbhelper;
		$this->comtjlmstrackingHelper = new comtjlmstrackingHelper;
		parent::__construct();
	}

	/**
	 * function is called from resume window
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 * */
	public function askforaction()
	{
		header('Content-type: application/json');

		$app       = Factory::getApplication();
		$input     = $app->input;
		$oluser_id = Factory::getUser()->id;

		// Get params set in user request while launching the lesson

		/*$lesson_id = $app->getUserStateFromRequest('com_tjlms' . 'lesson.id', 'lesson.id', -1, 'INTEGER');
		$attempt = $app->getUserStateFromRequest('com_tjlms' . 'lesson.attempt', 'lesson.attempt', -1, 'INTEGER');
		$format = $app->getUserStateFromRequest('com_tjlms' . 'lesson.format', 'lesson.format', '', 'STRING');*/

		$lesson_id = $input->get('lesson_id', '0', 'INT');
		$attempt   = $input->get('attempt', '-1', 'INT');
		$format    = $input->get('lessonformat', '', 'STRING');

		$app->setUserState('com_tjlms' . 'lesson.resetProgress', 0);

		if ($input->get('action', '0', 'STRING') == 'start')
		{
			if ($lesson_id > 0 && $oluser_id > 0)
			{
				$trackObj                   = new stdClass;
				$trackObj->attempt          = $attempt;
				$trackObj->score            = 0;
				$trackObj->last_accessed_on = Factory::getDate('now', 'UTC');
				$trackObj->current_position = 0;
				$this->comtjlmstrackingHelper->update_lesson_track($lesson_id, $oluser_id, $trackObj);

				// TODO: in case of scorm delete form scorm_scoes_track

				/*if ($format == 'scorm')
				{
					$db = Factory::getDbo();
					$aquery = $db->getQuery(true);
					$aquery->select($db->quoteName('id'));
					$aquery->from($db->quoteName('#__tjlms_scorm'));
					$aquery->where($db->quoteName('lesson_id') . ' = ' . $lesson_id);
					$aquery->order('id DESC');

					$db->setQuery($aquery);
					$scorm_id = $db->loadResult();

					$query = $db->getQuery(true);

					Delete all custom keys for user 1001.
					$conditions = array(
						$db->quoteName('userid') . ' = ' . $oluser_id,
						$db->quoteName('scorm_id') . ' = ' . $scorm_id,
						$db->quoteName('attempt') . ' = ' . $attempt
					);

					$query->delete($db->quoteName('#__tjlms_scorm_scoes_track'));
					$query->where($conditions);

					$db->setQuery($query);
					$result = $db->execute();
				}*/

				$app->setUserState('com_tjlms' . 'lesson.resetProgress', 1);
			}
		}

		echo 1;
		jexit();
	}

	/**
	 * function used to save time spent for html content
	 *
	 * @return id of tjlms_lesson_track
	 *
	 * @since 1.0.0
	 * */
	public function htmlupdateData()
	{
		header('Content-type: application/json');
		$input     = Factory::getApplication()->input;
		$post      = $input->post;
		$lesson_id = $post->get('lesson_id', '', 'INT');

		$trackObj                = new stdClass;
		$trackObj->attempt       = $post->get('attempt', '', 'INT');
		$trackObj->lesson_status = 'incomplete';
		$trackObj->score         = 0;

		$trackObj->current_position = $post->get('current_position', '', 'INT');
		$trackObj->total_content    = $post->get('total_content', '', 'INT');
		$trackObj->time_spent       = $post->get('total_time', '', 'FLOAT');

		if ($trackObj->current_position == $trackObj->total_content)
		{
			$trackObj->lesson_status = 'completed';
		}

		$trackingid = $this->comtjlmstrackingHelper->update_lesson_track($lesson_id, $user_id, $trackObj);

		/*$trackingid = $comtjlmstrackingHelper->update_lesson_track(
		 * $lesson_id,$attempt,$score,$lesson_status,$user_id,$total_content,$current_position,$time_spent);*/

		$trackingid = json_encode($trackingid);

		echo $trackingid;
		jexit();
	}

	/**
	 * Fuction to get download media file
	 *
	 * @return object
	 */
	public function downloadMedia()
	{
		$app           = Factory::getApplication();
		$jinput        = $app->input;
		$mediaId       = $jinput->get('mid', 0, 'INTEGER');
		$olUser        = Factory::getUser();

		// Check if OTP token is set for timely URL
		$getTimelyUrlToken = $jinput->get('otpToken', 0, 'STRING');

		$lessonModel = $this->getModel('lesson');

		// Check if user is authorised to download the file
		$authRes             = $lessonModel->checkifUsercanAccessMedia($mediaId, $getTimelyUrlToken);
		$authRes['media_id'] = $mediaId;

		if (!empty($getTimelyUrlToken))
		{
			// Get media details
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
			$mediaModel = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');

			$mediaDetail     = (object) $authRes;
			$mediaDetail->id = $mediaId;

			// Validate Timely URL token
			if ($mediaModel->verifyTotp($mediaDetail, $getTimelyUrlToken))
			{
				// Download will start
				$down_status = $lessonModel->downloadMedia($authRes);

				if (!$down_status)
				{
					if (count($errors = $lessonModel->getErrors()))
					{
						$app->enqueueMessage(implode("\n", $errors), 'error');
					}
				}
			}
			else
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			}
		}
		elseif ($authRes['access'])
		{

			$filepath      = $authorized['mediaPath'];

			// Download will start
			$down_status = $lessonModel->downloadMedia($authRes);

			if (!$down_status)
			{
				if (count($errors = $lessonModel->getErrors()))
				{
					$app->enqueueMessage(implode("\n", $errors), 'error');
				}
			}
		}
		else
		{
			if (!empty($authRes['msg']))
			{
				$app->enqueueMessage($authRes['msg']);
			}
			else
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			}

			$app->redirect(Route::_('index.php?option=com_users'));
		}

		jexit();
	}
}
