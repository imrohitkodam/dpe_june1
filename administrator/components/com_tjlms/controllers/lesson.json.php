<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;


/**
 * Lesson controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerLesson extends FormController
{
	/**
	 * Function to save user enrollment
	 * Call from backend enrollment view and from frontend while enrolling
	 *
	 * @param   int  $key     admin approval 1 or 0
	 * @param   int  $urlVar  id of user who has enrolle the user
	 *
	 * @return  boolean  true or false
	 *
	 * @since 1.0.0
	 */
	public function save($key = null, $urlVar = null)
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$input = $app->input;
		$post = $input->post;
		$data = $post->get('jform', '', 'ARRAY');
		$files = $input->files->get('jform', array (), 'array');

		$data['image'] = $files['image'];
		$model = $this->getModel('lesson');

		try
		{
			$form = $model->getForm();
			$data = $model->validate($form, $data);

			if ($data == false)
			{
				$errors = $model->getErrors();
				$this->processErrors($errors);

				echo new JResponseJson('', Text::_('COM_TJLMS_FORM_VALIDATATION_FAILED'), true);
			}

			if (isset($data['eligibility_criteria']) && !empty($data['eligibility_criteria']))
			{
				$data['eligibility_criteria'] = ',' . implode(',', $data['eligibility_criteria']) . ',';
			}
			else
			{
				$data['eligibility_criteria'] = '';
			}

			$lessonId = $model->save($data);

			if ($lessonId)
			{
				$result['id'] = $lessonId;

				echo new JResponseJson($result, Text::_('COM_TJLMS_FORM_SAVE_SUCCESS'));
			}
			else
			{
				$errors = $model->getErrors();
				$this->processErrors($errors);
				echo new JResponseJson('', Text::_('COM_TJLMS_FORM_SAVE_FAIL'), true);
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Function to update user enrollment
	 * Call from backend enrollment view and from frontend while enrolling
	 *
	 * @return  boolean  true or false
	 *
	 * @since 1.0.0
	 */
	public function updateassocfiles()
	{
		$ret = array();
		$input = Factory::getApplication()->input;

		// Get post
		$post = $input->post;

		$model = $this->getModel('lesson');

		// Save the files for respected course.
		$saveassocfiles = $model->updateassocfiles($post);

		if ($saveassocfiles)
		{
			$result = 1;
			$msg = Text::_('COM_TJLMS_LESSON_FILES_UPLOADED');
		}
		else
		{
			$result = 0;
			$msg = Text::_('COM_TJLMS_LESSON_FILES_UPLOADED_ERROR');
		}

		$ret['OUTPUT'][0] = $result;
		$ret['OUTPUT'][1] = $msg;
		echo new JsonResponse($ret);
		jexit();
	}

	/**
	 * Function to update user enrollment
	 * Call from backend enrollment view and from frontend while enrolling
	 *
	 * @return  boolean  true or false
	 *
	 * @since 1.0.0
	 */
	public function updateformat()
	{
		$input = Factory::getApplication()->input;
		$post = $input->post;
		$format_data = $post->get('lesson_format', '', 'ARRAY');
		$model = $this->getModel('lesson');

		try
		{
			// Save the module and the course ID along with that
			$mediaId = $model->updateformat($format_data);

			$result = array();

			if ($mediaId)
			{
				$result['id'] = $format_data['id'];
				$result['media_id'] = $mediaId;
				echo new JResponseJson($result, Text::_('COM_TJLMS_LESSON_FORMAT_UPLOADED'));
			}
			else
			{
				$errors = $model->getErrors();
				$this->processErrors($errors);
				echo new JResponseJson('', Text::_('COM_TJLMS_FORM_VALIDATATION_FAILED'), true);
			}
		}
		catch (Exception $e)
		{
			echo new JResponseJson($result, Text::_('COM_TJLMS_LESSON_FORMAT_UPLOADED'), true);
		}
	}

	/**
	 * Function to update user enrollment
	 * Call from backend enrollment view and from frontend while enrolling
	 *
	 * @param   int  $key  admin approval 1 or 0
	 *
	 * @return  boolean  true or false
	 *
	 * @since 1.0.0
	 */
	public function cancel($key = null)
	{
		$input = Factory::getApplication()->input;
		$post = $input->post;
		$data = $post->get('jform', '', 'ARRAY');
		$courseId = $data['course_id'];
		$modId = $data['mod_id'];
		$link = Route::_('index.php?option=com_tjlms&view=modules&course_id=' . $courseId . '&mod_id=' . $modId, false);
		$this->setRedirect($link);
	}

	/**
	 * Function to update user enrollment
	 * Call from backend enrollment view and from frontend while enrolling
	 *
	 * @return  boolean  true or false
	 *
	 * @since 1.0.0
	 */
	public function upload_files_store()
	{
		$input = Factory::getApplication()->input;
		$post = $input->post;
		$files = $input->files;
		$course_id = $input->get('course_id', 0, 'INT');
		$mod_id = $input->get('mod_id', 0, 'INT');
		$lesson_id = $input->get('lesson_id', 0, 'INT');
		$lesson_id_srting = '';

		if ($lesson_id)
		{
			$lesson_id_srting = ' &lesson_id=' . $lesson_id;
		}

		$TjlmsHelper = new TjlmsHelper;
		$store_files = $TjlmsHelper->upload_files_store($post, $files);
		$store_files = json_encode($store_files);
		$url = 'index.php?option=com_tjlms&view=lesson&layout=add_files&course_id=' . $course_id . '&mod_id=' . $mod_id;
		$this->setRedirect($url . '&tmpl=component' . $lesson_id_srting . '&store_files=' . $store_files);
	}

	/**
	 * Triggered from list of associated files when inserted to lesson
	 *
	 * @return object
	 */
	public function addMediaAsAssociatetoLesson()
	{
		$input    = Factory::getApplication()->input;
		$mediaId = $input->post->get('mediaId', 0, 'INT');
		$lessonId = $input->get('lessonId', 0, 'INT');

		try
		{
			$db = Factory::getDbo();
			$fileData            = new stdClass;
			$fileData->id        = '';
			$fileData->lesson_id = $lessonId;
			$fileData->media_id  = $mediaId;
			$db->insertObject('#__tjlms_associated_files', $fileData, 'id');

			echo new JsonResponse(1);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Triggered to de-associate file from lesson
	 *
	 * @return object
	 */
	public function removeAssociatedFileFromLesson()
	{
		$input = Factory::getApplication()->input;
		$mediaId = $input->get('mediaId', '0', 'INT');
		$lessonId = $input->get('lessonId', '0', 'INT');

		try
		{
			$model = $this->getModel('lesson');
			$ret = $model->removeAssociatedFile($lessonId, $mediaId);
			echo new JResponseJson($ret, Text::_("COM_TJLMS_LESSON_ASSOCIATED_FILE_REMOVED"));
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Fuction to get download media file
	 *
	 * @return object
	 */
	public function downloadMedia()
	{
		$jinput = Factory::getApplication()->input;
		$mediaId = $jinput->get('mid', 0, 'INTEGER');
		$userId = Factory::getUser()->id;

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjlms/models', 'TjlmsModel');
		$lessonModel = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');

		// Check if user is authorised to download the file
		$authorized = $lessonModel->checkifUsercanAccessMedia($mediaId, $userId);

		if ($authorized['access'])
		{
			$filepath      = $authorized['mediaPath'];

			// Download will start
			$down_status = $lessonModel->downloadMedia($filepath, $authorized['external']);

			if (!$down_status)
			{
				if (count($errors = $this->get('Errors')))
				{
					Factory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');
				}
			}
		}
		else
		{
			Factory::getApplication()->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
		}

		Factory::getApplication()->redirect(Route::_('index.php?option=com_users'));

		jexit();
	}

	/**
	 * Function to save html content
	 *
	 * @since 1.0.0
	 */
	/*
	public function saveHtmlContent()
	{
		header('Content-type: application/json');
		$input = Factory::getApplication()->input;
		$post = $input->post;
		$db = Factory::getDBO();

		$media_id = $post->get('media_id','','INT');
		$lesson_id = $post->get('lesson_id','','INT');

		 Save Html content in media object
		$obj = new stdclass;
		$obj->source    = $post->get('htmlcontent','','RAW');
		$obj->created_by = $post->get('user_id','','STRING');
		$obj->format = 'textmedia';
		$obj->storage = 'local';

		save if no media ID is present. Hence consider as new data
		if ($media_id == 0)
		{
			$obj->id = '';

			if (!$db->insertObject( '#__tjlms_media', $obj, 'id'))
			{
				echo $db->stderr();
			}
			$id = $db->insertid();
		}
		else // Update if media ID present
		{
			$obj->id = $media_id;

			if (!$db->updateObject( '#__tjlms_media', $obj, 'id'))
			{
				echo $db->stderr();
			}

			$id = $media_id;
		}
		$this->saveMediaForlesson($id , $lesson_id, 'textmedia');


		$media_id = json_encode($id);
		echo $media_id;
		jexit();
	}*/
	/**
	 * Function to update media_id of lesson
	 * $id is media id
	 * @since 1.0.0
	 */
	/*function saveMediaForlesson($id, $lesson_id, $format)
	{
		$db = Factory::getDBO();
		$obj = new stdclass;
		$obj->id    = $lesson_id;
		$obj->format    = $format;
		$obj->media_id = $id;
		$db->updateObject( '#__tjlms_lessons', $obj, 'id');
		return 1;

	}*/
	/**
	 * Function to save images for html content
	 *
	 * @since 1.0.0
	 */
	/*public function saveHtmlImages()
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Access-Control-Allow-Origin: *');
		$dir = JPATH_SITE.'/media/com_tjlms/lessons/';

		STEP 2: Specify url path
		$path = 'media/com_tjlms/lessons/';

		$input = Factory::getApplication()->input;
		$post = $input->post;

		$count = $input->get('count','1','INT');
		$b64str = $post->get('hidimg-' . $count,'1','STRING');
		$imgname = $post->get('hidname-' . $count,'1','STRING');
		$imgtype = $post->get('hidtype-' . $count,'1','STRING');

		Generate random file name here
		if($imgtype == 'png')
		{
			$image = $imgname . '-' . base_convert(rand(),10,36) . '.png';
		}
		else
		{
			$image = $imgname . '-' . base_convert(rand(),10,36) . '.jpg';
		}

		Save image
		$success = file_put_contents($dir . $image, base64_decode($b64str));

		if ($success === FALSE)
		{

			if (!file_exists($dir))
			{
				echo "<html><body onload=\"alert('Saving image to folder failed. Folder ".$dir." not exists.')\"></body></html>";
			}
			else
			{
				echo "<html><body onload=\"alert('Saving image to folder failed. Please check write permission on " .$dir. "')\"></body></html>";
			}
		}
		else
		{
			Replace image src with the new saved file
			echo "<html><body onload=\"parent.document.getElementById('img-" .
			* $count . "').setAttribute('src','" . $path . $image . "');
			*  parent.document.getElementById('img-" . $count . "').removeAttribute('id') \"></body></html>";
		}
	}*/

	/**
	 * Function to save assessment parameters
	 *
	 * @return  boolean  true or false
	 *
	 * @since 1.0.0
	 */
	public function saveassessment()
	{
		$app = Factory::getApplication();
		$input = Factory::getApplication()->input;
		$data = $input->get('jform', array(), 'array');

		require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/lesson.php';
		$lessonModel = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');

		require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/assessment.php';
		$assessmentModel = new TjlmsModelAssessment;

		$lessonData = array();
		$lessonData['id'] = $data['id'];
		$lessonData['total_marks'] = $data['total_marks'];
		$lessonData['passing_marks'] = $data['passing_marks'];

		$result = array();

		try
		{
			$data['lesson_id'] = $data['id'];
			$assessmentModel->save($data);

			$lessonModel->save($lessonData);

			$result['id'] = $data['id'];
			echo new JResponseJson($result, Text::_('COM_TJLMS_FORM_SAVE_SUCCESS'));
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
}
