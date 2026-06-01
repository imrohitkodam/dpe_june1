<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Helper\MediaHelper;
jimport('joomla.filesystem.folder');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\FormController;

/**
 * File upload controller class.
 *
 * @since  1.0.0
 */
class TmtControllerFileUpload extends FormController
{
	/**
	 * The main function triggered to upload file off question
	 *
	 * @return object of result and message
	 *
	 * @since 1.0.0
	 * */

	public function processupload()
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');

		$oluser_id = Factory::getUser()->id;

		/* If user is not logged in*/
		if (!$oluser_id)
		{
			$ret['OUTPUT']['flag'] = 0;
			$ret['OUTPUT']['msg']  = Text::_('COM_TJLMS_MUST_LOGIN_TO_UPLOAD');
			echo json_encode($ret);
			jexit();
		}

		$input       = Factory::getApplication()->input;
		$tjlmsparams = ComponentHelper::getParams('com_tjlms');

		$files = $input->files;
		$post  = $input->post;

		/*Check if file is safe to upload*/
		$file_to_upload	= $files->get('FileInput', null, 'raw');

		$options  = array('fobidden_ext_in_content' => false);
		$isSafe   = InputFilter::isSafeFile($file_to_upload, $options);
		$filename = $files->name;

		if (!$isSafe)
		{
			$return = 0;
			$msg    =	Text::_("COM_TMT_ERROR_FILENOTSAFE_TO_UPLOAD", $filename);
			$print  = '';
		}
		else
		{
			/* Validate the uploaded file*/
			$validate_result = $this->validateupload($file_to_upload);

			if ($validate_result['res'] != 1)
			{
				$ret['OUTPUT']['flag'] = $validate_result['res'];
				$ret['OUTPUT']['msg']  = $validate_result['msg'];
				echo json_encode($ret);
				jexit();
			}

			$filepath = 'media/com_tmt/test/';
			$return   = 1;
			$msg      = '';

			$file_attached      = $file_to_upload['tmp_name'];
			$filename           = $file_to_upload['name'];
			$filepath_with_file = $filepath . $filename;
			$newfilename        = $filename;

			// Get file extention
			$file_ext = substr($filename, strrpos($filename, '.'));

			// Random number to be added to name.
			$random_number = rand(0, 9999999999);

			// New file name
			$newfilename        = $random_number . $file_ext;
			$filepath_with_file = $filepath . $newfilename;

			$uploads_dir = JPATH_SITE . '/' . $filepath_with_file;

			/*3rd param is to stream and 4is set to true to ask to upload unsafe file*/
			if (!File::upload($file_attached, $uploads_dir, false, true))
			{
				$return = 0;
				$msg    =	Text::sprintf("COM_TMT_ERROR_UPLOAD_ON_LOCAL", $filename);
				$print  = '';
			}
			else
			{
				$return = 1;
				$msg    =	$newfilename;
				$print  = Text::sprintf("COM_TMT_UPLOAD_SUCCESS");
			}
		}

		$ret['OUTPUT']['flag']  = $return;
		$ret['OUTPUT']['msg']   = $msg;
		$ret['OUTPUT']['print'] = $print;

		echo json_encode($ret);
		jexit();
	}

	/**
	 * The function to validate the uploaded format file
	 *
	 * @param   MIXED  $file_to_upload  file object
	 *
	 * @return  object of result and message
	 *
	 * @since 1.0.0
	 * */
	public function validateupload($file_to_upload)
	{
		$tjlmsparams = ComponentHelper::getParams('com_tjlms');

		$return = 1;
		$msg	= '';

		if ($file_to_upload["error"] == UPLOAD_ERR_OK)
		{
			// Total length of post back data in bytes.
			$contentLength = (int) $_SERVER['CONTENT_LENGTH'];

			// Instantiate the media helper
			$mediaHelper = new MediaHelper;

			// Maximum allowed size of post back data in MB.
			$postMaxSize = $mediaHelper->toBytes(ini_get('post_max_size'));

			// Maximum allowed size of script execution in MB.
			$memoryLimit = $mediaHelper->toBytes(ini_get('memory_limit'));

			// Maximum allowed size of script execution in MB.
			$uploadMaxFileSize = $mediaHelper->toBytes(ini_get('upload_max_filesize'));

			/* check if file size in within the uploading limit of site*/

			// Check for the total size of post back data.
			if ($contentLength > ($tjlmsparams->get('lesson_upload_size', 10) * 1024 * 1024)
				|| ($postMaxSize > 0 && $contentLength > $postMaxSize)
				|| ($uploadMaxFileSize > 0 && $contentLength > $uploadMaxFileSize)
				|| ($memoryLimit != -1 && $contentLength > $memoryLimit))
			{
				$return = 0;
				$msg    = Text::sprintf('COM_TMT_UPLOAD_SIZE_ERROR', $tjlmsparams->get('lesson_upload_size', 10, 'INT') . ' MB');
			}
		}
		else
		{
			$return = 0;
			$msg    = Text::_("COM_TMT_ERROR_UPLOADINGFILE", $filename);
		}

		$output['res'] = $return;
		$output['msg'] = $msg;

		return $output;
	}

	/**
	 * Triggered from ajax
	 * For scorm/tjscorm add entry in tjlms_scorm
	 * For others add entry in tjlms_media
	 *
	 * @return json encoded id of the row of related table
	 *
	 * @since 1.0.0
	 * */

	public function populate_tables()
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');
		$db    = Factory::getDBO();
		$input = Factory::getApplication()->input;
		$post  = $input->post;

		$filename    = $post->get('filename', '', 'STRING');
		$newfilename = $post->get('newfilename', '', 'STRING');

		$invite_id = $post->get('invite_id', '', 'INT');
		$qid       = $post->get('qid', '', 'INT');
		$test_id   = $post->get('test_id', '', 'INT');

		$format_id = $this->populate_related_tables($filename, $newfilename, $invite_id, $qid, $test_id);

		$ret['OUTPUT']['newfilename'] = $newfilename;
		$ret['OUTPUT']['format_id']   = $format_id;

		echo json_encode($ret);
		jexit();
	}

	/**
	 *Populate related the tables
	 *Add entry in tjlms_media
	 *
	 * @param   String  $filename     original file name
	 * @param   String  $newfilename  saved file name
	 * @param   INT     $invite_id    invite id of attempt
	 * @param   INT     $qid          question id
	 * @param   INT     $test_id      test id
	 *
	 * @return id of the respective table row
	 *
	 * @since 1.0.0
	 * */
	public function populate_related_tables($filename, $newfilename, $invite_id, $qid,$test_id)
	{
		$oluser_id = Factory::getUser()->id;
		$db        = Factory::getDBO();

		$format_data               = new stdClass;
		$format_data->created_by   = $oluser_id;
		$format_data->format       = 'test';
		$format_data->sub_format   = 'answer.upload';
		$format_data->storage      = 'local';
		$format_data->org_filename = $filename;
		$format_data->source       = $newfilename;

		$db->insertObject('#__tjlms_media', $format_data, 'id');

		$media_id = $db->insertid();

		$query = $db->getQuery(true);
		$query->select('id, question_id');
		$query->from(' #__tmt_tests_answers');
		$query->where($db->quoteName('user_id') . '=' . $db->quote($oluser_id));
		$query->where($db->quoteName('test_id') . '=' . $db->quote($test_id));
		$query->where($db->quoteName('question_id') . '=' . $db->quote($qid));
		$query->where($db->quoteName('invite_id') . '=' . $db->quote($invite_id));

		$db->setQuery($query);

		$old_test_answer_ids = $db->loadObject();
		$updateFlag          = 'insert';

		if (!empty($old_test_answer_ids))
		{
			$formatted_old_test_answer_ids[$old_test_answer_ids->question_id] = $old_test_answer_ids->id;

			if (array_key_exists($qid, $formatted_old_test_answer_ids))
			{
				$updateFlag = 'update';
				unset($formatted_old_test_answer_ids[$qid]);
			}
		}

		$answer_data              = new stdClass;
		$answer_data->question_id = $qid;
		$answer_data->user_id     = $oluser_id;
		$answer_data->test_id     = $test_id;
		$answer_data->invite_id   = $invite_id;
		$answer_data->answer      = $newfilename;

		switch ($updateFlag)
		{
			case 'insert':
				$db->insertObject('#__tmt_tests_answers', $answer_data, 'id');

			break;
			case 'update':
				$answer_data->id = $old_test_answer_ids->id;

				$db->updateObject('#__tmt_tests_answers', $answer_data, 'id');

			break;
		}

		return $media_id;
	}
}
