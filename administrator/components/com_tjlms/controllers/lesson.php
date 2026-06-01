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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
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
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->view_list = 'lessons';
		parent::__construct();
	}

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
		$ret = array();
		$result = 0;
		$msg = '';
		$input = Factory::getApplication()->input;

		// Get post and files
		$post = $input->post;

		// Check if edit..
		$LessonID = $input->get('id', '0', 'INT');

		if ( $LessonID != 0 )
		{
			// Set action as edit as further coding as refer to action many times.
			$input->set('action', 'edit', 'STRING');
		}

		$model = $this->getModel('lesson');

		$lessonData = $post->get('jform', '', 'ARRAY');

		// Validate the posted data.
		$form = $model->getForm($lessonData, false);

		if (!$form)
		{
			$result = 0;
			$msg = $model->getError();
		}
		else
		{
			$validData = $model->validate($form, $lessonData);

			// Check for validation errors.
			if ($validData === false)
			{
				$result = 0;
				$msg = '';

				// Get the validation messages.
				$errors = $model->getErrors();

				// Push up to three validation messages out to the user.
				for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
				{
					if ($errors[$i] instanceof \Exception)
					{
						$msg .= $errors[$i]->getMessage() . "<br />";
					}
					else
					{
						$msg .= $errors[$i] . "<br />";
					}
				}
			}
			else
			{
				// Save the module and the course ID along with that
				$saveLesson = $model->saveLesson($validData, $post);

				if ($saveLesson > 0)
				{
					$result = 1;
					$msg = $saveLesson;
				}
				elseif ( $saveLesson == -3)
				{
						$result = 0;
						$msg = Text::_('COM_TJLMS_LESSON_SAVING_FAILED');
				}
				elseif ( $saveLesson == -2)
				{
						$result = 0;
						$msg = Text::_('COM_TJLMS_LESSON_IMAGE_UPLOAD_FAILED');
				}
				elseif ( $saveLesson == -1)
				{
						$result = 0;
						$msg = Text::_('COM_TJLMS_LESSON_LESSON_UPDATE_IMGAENAME_FAILED');
				}
			}
		}

		$ret['OUTPUT'][0] = $result;
		$ret['OUTPUT'][1] = $msg;
		echo json_encode($ret);
		jexit();
		/*$this->setRedirect($link,$msg);*/
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
		echo json_encode($ret);
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

		// Get post
		$post = $input->post;

		$model = $this->getModel('lesson');

		// Save the module and the course ID along with that
		$saveLesson = $model->updateformat($post);

		$ret = array();

		if ($saveLesson)
		{
			$ret['OUTPUT']['result'] = 1;
			$ret['OUTPUT']['msg'] = Text::_('COM_TJLMS_LESSON_FORMAT_UPLOADED');

			if (isset($saveLesson['media_id']) && $saveLesson['media_id'] > 0)
			{
				$ret['OUTPUT']['media_id'] = $saveLesson['media_id'];
			}

			if (isset($saveLesson['test_id']) && $saveLesson['test_id'] > 0)
			{
				$ret['OUTPUT']['test_id'] = $saveLesson['test_id'];
			}
		}
		else
		{
			$ret['OUTPUT']['result'] = 0;
			$ret['OUTPUT']['msg'] = Text::_('COM_TJLMS_LESSON_FORMAT_UPLOADED');
		}

		echo json_encode($ret);
		jexit();
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
	 * Fuction to get download media file
	 *
	 * @return object
	 */
	public function downloadMedia()
	{
		$jinput = Factory::getApplication()->input;
		$mediaId = $jinput->get('mid', 0, 'INTEGER');

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjlms/models', 'TjlmsModel');
		$lessonModel = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');

		// Check if user is authorised to download the file
		$authRes             = $lessonModel->checkifUsercanAccessMedia($mediaId);
		$authRes['media_id'] = $mediaId;

		// Download will start
		$down_status = $lessonModel->downloadMedia($authRes);

		if (!$down_status)
		{
			if (count($errors = $this->get('Errors')))
			{
				Factory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');
			}
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
		$input = Factory::getApplication()->input;
		$post = $input->post;
		$model = $this->getModel('lesson');

		$assessmentData   = $post->get('jform', '', 'ARRAY');
		$subformat = $assessmentData['subformat'];
		$saveassessment = 0;

		if ($subformat == 'exercise' || $assessmentData['add_assessment'] == 1 || $assessmentData['set_id'])
		{
			$saveassessment = $model->saveAssessment($assessmentData);
		}

		if ($saveassessment)
		{
			$result = 1;
			$msg = Text::_('COM_TJLMS_ASSESMENT_SAVED');
		}
		else
		{
			$result = 0;
			$msg = Text::_('COM_TJLMS_ASSESMENT_ERROR');
		}

		if ((int) $saveassessment == -1)
		{
			$result = -1;
			$msg = Text::_('COM_TJLMS_ASSESSMENT_CANT_SAVE');
		}

		$ret['OUTPUT'][0] = $result;
		$ret['OUTPUT'][1] = $msg;
		$ret['OUTPUT'][2] = $saveassessment;
		echo json_encode($ret);
		die();
		jexit();
	}
}
