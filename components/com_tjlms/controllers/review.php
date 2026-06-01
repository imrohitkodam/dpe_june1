<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

jimport('joomla.application.component.controller');

/**
 * Lesson controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerReview extends TjlmsController
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
	 * Method to set module filters
	 *
	 * @access public
	 *
	 * @return void
	 *
	 * @since  1.0
	 */
	public function submitform()
	{
		$mainframe = Factory::getApplication('site');
		$input = $mainframe->input;
		$jform = $input->get('post');

		$lesson_id = $input->get('lesson_id', '', 'INT');

		$model = $this->getModel('Assignment');
		$status = $model->submitform();

		switch ($status)
		{
			case 'draft':
				$mainframe->enqueueMessage(Text::_('COM_TJLMS_SUBMISSION_DRAFT_UPDATED_MSG'), 'message');
			break;

			case 'review':
				$mainframe->enqueueMessage(Text::_('COM_TJLMS_SUBMISSION_REVIEW_UPDATED_MSG'), 'message');
			break;

			case 'final':
				$mainframe->enqueueMessage(Text::_('COM_TJLMS_SUBMISSION_FINAL_UPDATED_MSG'), 'message');
			break;
		}

		$itemid = comtjlmsHelper::getItemid('index.php?option=com_tjlms&view=lesson');
		$link = Route::_('index.php?option=com_tjlms&view=lesson&lesson_id=' . $lesson_id . '&tmpl=component&Itemid=' . $itemid, false);

		$mainframe->redirect($link);
	}

	/**
	 * The main function triggered after on format upload
	 *
	 * @return object of result and message
	 *
	 * @since 1.0
	 * */
	public function processupload()
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');

		$oluser_id = Factory::getUser()->id;

		/* If user is not logged in*/
		if (!$oluser_id)
		{
			$ret['OUTPUT']['flag']	=	0;
			$ret['OUTPUT']['msg']	=	Text::_('COM_IDEAS_MUST_LOGIN_TO_UPLOAD');
			$ret['OUTPUT']['filename'] = '<input type="hidden" id="jform_attachment" name="jform[attachment]" value="">';
			$ret['OUTPUT']['ShowFiles'] = '';

			echo json_encode($ret);
			jexit();
		}

		$input = Factory::getApplication()->input;
		$lesson_id = $input->get('lesson_id', '', 'INT');

		$inputId = "jform_attachment" . $lesson_id;
		$inputName = "jform[attachment]";

		$files = $input->files;

		$post = $input->post;

		$file_to_upload	=	$files->get('FileInput', '', 'ARRAY');

		/* Validate the uploaded file*/
		$validate_result = $this->validateupload($file_to_upload);

		/* $rs1       = @mkdir(JPATH_SITE . '/media/com_tjlms/lessons/'.$lesson_id .'/submission', 0777); */
		$rs0 = @mkdir(JPATH_SITE . '/media/com_tjlms/lessons/' . $lesson_id, 0777);
		$rs  = @mkdir(JPATH_SITE . '/media/com_tjlms/lessons/' . $lesson_id . '/submission', 0777);
		$rs1 = @mkdir(JPATH_SITE . '/media/com_tjlms/lessons/' . $lesson_id . '/submission/' . $oluser_id, 0777);

		/* $rs1       = @mkdir(JPATH_SITE . '/plugins/tjassignment/submission/uploads/tmp', 0777); */

		// Start file heandling functionality *
		$filename = $file_to_upload['name'];
		$filetype = $file_to_upload['type'];
		$file_attached	= $file_to_upload['tmp_name'];

		// $uploads_dir = JPATH_SITE . '/plugins/tjassignment/submission/uploads/tmp/' . $filename;
		$uploads_dir = JPATH_SITE . '/media/com_tjlms/lessons/' . $lesson_id . '/submission/' . $oluser_id . '/' . $filename;

		// Check file already exists
		move_uploaded_file($file_attached, $uploads_dir);

		$db = Factory::getDBO();

		$media = new stdClass;
		$media->format = 'assignment';
		$media->sub_format = 'assignment.upload';
		$media->org_filename = $filename;
		$media->source = $filename;
		$media->created_by = $oluser_id;
		$db->insertObject('#__tjlms_media', $media, 'id');

		$return = 1;
		$msg = Text::sprintf('File Saved Successfully', $filename);

		$ret['OUTPUT']['flag'] = $return;
		$ret['OUTPUT']['msg'] = $msg;
		$ret['OUTPUT']['media_id'] = $media->id;
		$ret['OUTPUT']['filename'] = '<input type="hidden" name="' . $inputName . '[]" value="' . $filename . '">';
		$ret['OUTPUT']['filename'] .= '<input type="hidden" name="jform[media_id][]" value="' . $media->id . '">';

		/*'<a target="_blank" href="' . JUri::root() . 'plugins/tjassignment/submission/uploads/tmp/' . $filename . '">' . $filename . '</a>';*/

		$url = Uri::root() . 'media/com_tjlms/lessons/' . $lesson_id . '/submission/' . $oluser_id . '/' . $filename;

		$ret['OUTPUT']['ShowFiles'] = '<a target="_blank" href="' . $url . '">' . $filename . '</a>';

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
		$return = 1;
		$msg	= '';

		if ($file_to_upload["error"] == UPLOAD_ERR_OK)
		{
		}
		else
		{
			$return = 0;
			$msg = Text::_("COM_TJLMS_ERROR_UPLOADINGFILE", $filename);
		}

		$output['res'] = $return;
		$output['msg'] = $msg;

		return $output;
	}

	/**
	 * Function used to remove submitted files
	 *
	 * @return  JSON
	 *
	 * @since  1.0
	 */
	public function removeSubFiles()
	{
		$input = Factory::getApplication()->input;
		$mediaId = $input->get('media_id', '0', 'INT');
		$model = $this->getModel('Assignment');
		$removeAssocFiles = $model->removeSubFiles($mediaId, $lessonId);
		echo json_encode($removeAssocFiles);
		jexit();
	}

	/**
	 * Function used to remove submitted files
	 *
	 * @return  JSON
	 *
	 * @since  1.0
	 */
	public function AddReview()
	{
		$mainframe = Factory::getApplication('site');
		$input = $mainframe->input;
		$lesson_id = $input->get('lesson_id', '', 'INT');
		$model = $this->getModel('Review');
		$model->AddReview();

		$post = $input->get('jform', '', 'array');

		switch ($post['review_status'])
		{
			case 'draft':
				$mainframe->enqueueMessage(Text::_('COM_TJLMS_REVIEW_DRAFT_UPDATED_MSG'), 'message');
				$url = 'index.php?option=com_tjlms&view=lesson&tmpl=component&lesson_id=' . $lesson_id . '&submission_id=' . $post['submission_id'];
			break;

			case 'final':
				$mainframe->enqueueMessage(Text::_('COM_TJLMS_REVIEW_FINAL_UPDATED_MSG'), 'message');
				$url = 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $lesson_id . '&tmpl=component';
			break;
		}

		$itemid = comtjlmsHelper::getItemid('index.php?option=com_tjlms&view=course');
		$link = Route::_($url . '&Itemid=' . $itemid, false);
		$mainframe->redirect($link);
	}
}
