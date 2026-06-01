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
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Helper\MediaHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Plugin\PluginHelper;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * File upload controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerFileUpload extends FormController
{
	/**
	 * The main function triggered after on format upload
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
			$ret['OUTPUT']['flag']	=	0;
			$ret['OUTPUT']['msg']	=	Text::_('COM_TJLMS_MUST_LOGIN_TO_UPLOAD');
			echo json_encode($ret);
			jexit();
		}

		$input = Factory::getApplication()->input;
		$tjlmsparams = ComponentHelper::getParams('com_tjlms');

		$files = $input->files;
		$post = $input->post;

		/*Check if file is safe to upload*/
		$file_to_upload	=	$files->get('FileInput', null, 'raw');

		$options = array('fobidden_ext_in_content' => false);
		$isSafe = InputFilter::isSafeFile($file_to_upload, $options);

		if (!$isSafe)
		{
			$return = 0;
			$msg =	Text::_("COM_TJLMS_ERROR_FILENOTSAFE_TO_UPLOAD", $filename = '');
		}
		else
		{
			$format	=	$post->get('Format', '', 'STRING');
			$subformat	=	$post->get('Subformat', '', 'STRING');
			$lesson_id	=	$post->get('Lessonid', '', 'INT');

			/* Validate the uploaded file*/
			$validate_result = $this->validateupload($file_to_upload, $format, $subformat, $lesson_id);

			if ($validate_result['res'] != 1)
			{
				$ret['OUTPUT']['flag']	=	$validate_result['res'];
				$ret['OUTPUT']['msg']	=	$validate_result['msg'];
				echo json_encode($ret);
				jexit();
			}

			$filepath = 'media/com_tjlms/lessons/';

			$return = 1;
			$msg = '';

			$file_attached	= $file_to_upload['tmp_name'];
			$filename = File::makeSafe($file_to_upload['name']);

			// Rename the file

			// Get file extention
			$file_ext = substr($filename, strrpos($filename, '.'));

			// Random number to be added to name.
			$random_number      = rand(0, (int) getrandmax());

			// New file name
			$newfilename = $random_number . $file_ext;
			$filepath_with_file = $filepath . $newfilename;

			$uploads_dir = JPATH_SITE . '/' . $filepath_with_file;

			/*3rd param is to stream and 4is set to true to ask to upload unsafe file*/
			if (!File::upload($file_attached, $uploads_dir, false, true))
			{
				$return = 0;
				$msg =	Text::sprintf("COM_TJLMS_ERROR_UPLOAD_ON_LOCAL", $filename);
				$uploadErrors = Factory::getApplication()->getMessageQueue();

				if (!empty($uploadErrors))
				{
					foreach ($uploadErrors as $uploadError)
					{
						$msg .= '<br>' . $uploadError['message'];
					}
				}
			}
			else
			{
				$return = 1;
				$msg =	$newfilename;
			}
		}

		$ret['OUTPUT']['flag'] = $return;
		$ret['OUTPUT']['msg'] = $msg;
		echo json_encode($ret);
		jexit();
	}

	/**
	 * The function to validate the uploaded format file
	 *
	 * @param   MIXED   $file_to_upload  file object
	 * @param   STRING  $format          format selected
	 * @param   STRING  $subformat       subformat selected
	 * @param   STRING  $lesson_id       lesson id
	 *
	 * @return  object of result and message
	 *
	 * @since 1.0.0
	 * */
	public function validateupload($file_to_upload, $format, $subformat, $lesson_id)
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
			if (($tjlmsparams->get('lesson_upload_size', 10) * 1024 * 1024) != 0)
			{
				// Check for the total size of post back data.
				if ($contentLength > ($tjlmsparams->get('lesson_upload_size', 10) * 1024 * 1024)
					|| ($postMaxSize > 0 && $contentLength > $postMaxSize)
					|| ($uploadMaxFileSize > 0 && $contentLength > $uploadMaxFileSize))
				{
					$return = 0;
					$msg = Text::sprintf('COM_TJLMS_UPLOAD_SIZE_ERROR', $tjlmsparams->get('lesson_upload_size', 10, 'INT') . ' MB');
				}
			}

			/* Check for the type/extensiom of the file*/
			if ($return == 1)
			{
				$filename = $file_to_upload['name'];
				$fileext = File::getExt($filename);

				switch ($format)
				{
					case 'scorm':
							/*$valid_extensions_arr	=	array('application/zip','application/x-zip-compressed','application/x-rar-compressed' ,'application/octet-stream');*/
							$valid_extensions_arr = array('zip');

							if (in_array($fileext, $valid_extensions_arr))
							{
								$res = $this->check_scorm_package($file_to_upload);

								if ($res != 1)
								{
									$return = 0;
									$msg = Text::_("COM_TJLMS_VALID_SCORM_PACKAGE");
								}
							}
							else
							{
								$msg = Text::_("COM_TJLMS_VALID_ZIP_PACKAGE");
								$return = 0;
							}
						break;
					case 'tincanlrs':
							$valid_extensions_arr = array('zip');

							if (in_array($fileext, $valid_extensions_arr))
							{
								$res = $this->check_tincan_package($file_to_upload);

								if ($res != 1)
								{
									$return = 0;
									$msg = Text::_("COM_TJLMS_VALID_TINCAN_PACKAGE");
								}
							}
							else
							{
								$msg = Text::_("COM_TJLMS_VALID_ZIP_PACKAGE");
								$return = 0;
							}

						break;
					case 'htmlzips':
							$valid_extensions_arr = array('zip');

							if (in_array($fileext, $valid_extensions_arr))
							{
								$res = $this->check_htmlzip_package($file_to_upload);

								if ($res != 1)
								{
									$return = 0;
									$msg = Text::_("COM_TJLMS_VALID_HTMLZIP_PACKAGE");
								}
							}
							else
							{
								$return = 0;
								$msg = Text::_("COM_TJLMS_VALID_ZIP_PACKAGE");
							}
						break;
					case 'document':
							/*$valid_extensions_arr = array(
														'application/vnd.ms-powerpoint', 'application/pdf',
														'application/msword','application/vnd.openxmlformats-officedocument.presentationml.presentation',
														'application/vnd.openxmlformats-officedocument.wordprocessingml.document');*/
							$valid_extensions_arr = array('pdf', 'doc', 'docx', 'ppt', 'pptx', 'xlsx');

							if ($subformat == 'pdfviewer')
							{
								$valid_extensions_arr = array('pdf');
								$msg = Text::_("COM_TJLMS_UPLOAD_EXTENSION_ERROR");
							}

							if (!in_array($fileext, $valid_extensions_arr))
							{
								$return = 0;
								$msg = (!empty($msg)? $msg : Text::_("COM_TJLMS_VALID_DOCUMENT_UPLOAD"));
							}
						break;

					case 'video':
						/*$valid_extensions_arr	=	array("video/x-msvideo", "video/msvideo", "video/avi", "video/mp4", "video/x-flv", "audio/mp3", "audio/mpeg");*/
						$valid_extensions_arr = array('flv', 'mp4', 'mp3');

						if (!in_array($fileext, $valid_extensions_arr))
						{
								$msg = Text::_("COM_TJLMS_VALID_VIDEO_UPLOAD");
								$return = 0;
						}

						break;
				}
			}
		}
		else
		{
			$return = 0;
			$msg  = Text::sprintf("COM_TJLMS_ERROR_UPLOADINGFILE", $file_to_upload['name']);

			if (!empty($file_to_upload["error"]))
			{
				$msg .= '<br>' . $this->uploadcodeToMessage($file_to_upload["error"]);
			}
		}

		$output['res'] = $return;
		$output['msg'] = $msg;

		return $output;
	}

	/**
	 * Check if the uploaded zip id valid scorm package
	 *
	 * @param   String  $file  Uploaded zip
	 *
	 * @return 1 if yes
	 *
	 * @since 1.0.0
	 * */
	public function check_scorm_package($file)
	{
		$return = 1;

		if (isset($file['tmp_name']) && $file['error'] == UPLOAD_ERR_OK)
		{
			$za = new ZipArchive;

			$za->open($file['tmp_name']);

			/*for ($i = 0; $i < $za->numFiles; $i++)
			{
				$stat = $za->statIndex($i);
				$zipped_files[] = $stat['name'];
			}

			if (!in_array('imsmanifest.xml', $zipped_files))*/
			if (!$za->locateName('imsmanifest.xml', ZIPARCHIVE::FL_NODIR))
			{
				$return = 0;
			}
		}

		return $return;
	}

	/**
	 * Check if the uploaded zip id valid scorm package
	 *
	 * @param   String  $file  Uploaded zip
	 *
	 * @return 1 if yes
	 *
	 * @since 1.0.0
	 * */
	public function check_htmlzip_package($file)
	{
		$return = 1;

		if (isset($file['tmp_name']) && $file['error'] == UPLOAD_ERR_OK)
		{
			$za = new ZipArchive;

			$za->open($file['tmp_name']);

			/*for ($i = 0; $i < $za->numFiles; $i++)
			{
				$stat = $za->statIndex($i);
				$zipped_files[] = $stat['name'];
			}*/

			if (!$za->locateName('index.html'))
			{
				$return = 0;
			}
		}

		return $return;
	}

	/**
	 * Check if the uploaded zip id valid tincan package
	 *
	 * @param   String  $file  Uploaded zip
	 *
	 * @return 1 if yes
	 *
	 * @since 1.0.0
	 * */
	public function check_tincan_package($file)
	{
		$return = 1;

		if (isset($file['tmp_name']) && $file['error'] == UPLOAD_ERR_OK)
		{
			$za = new ZipArchive;

			$za->open($file['tmp_name']);

			/*for ($i = 0; $i < $za->numFiles; $i++)
			{
				$stat = $za->statIndex($i);
				$zipped_files[] = $stat['name'];
			}*/

			/*if (!in_array('tincan.xml', $zipped_files))*/
			if (!$za->locateName('tincan.xml', ZIPARCHIVE::FL_NODIR))
			{
				$return = 0;
			}
		}

		return $return;
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
		$db	=	Factory::getDBO();
		$input = Factory::getApplication()->input;
		$post = $input->post;
		$ret = [];

		$filename = $post->get('filename', '', 'STRING');
		$newfilename = $post->get('newfilename', '', 'STRING');
		$lesson_id = $post->get('lesson_id', '', 'INT');
		$format = $post->get('lessonformat', '', 'STRING');
		$subformat = $post->get('lessonsubformat', '', 'STRING');
		$upload_response = $post->get('upload_response', '', 'STRING');

		$format_id	=	$this->populate_related_tables($filename, $newfilename, $format, $subformat, $lesson_id, $upload_response);

		/* If uploaded file format is not in allowed formats of tjlms */
		$tjlmsnotallowedformats = array('associate');

		if (!in_array($format, $tjlmsnotallowedformats) && !empty($format_id))
		{
			// Check if enrollment is published
			require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/lesson.php';
			$model     = BaseDatabaseModel::getInstance('lesson', 'TjlmsModel');
			$model->removeLastUsedMedia($lesson_id);

			// Update lesson table for format
			$lesson_data = new stdClass;
			$lesson_data->id = $lesson_id;
			$lesson_data->format = $format;
			$lesson_data->media_id = $format_id;
			$db->updateObject('#__tjlms_lessons', $lesson_data, 'id');

			$ret['OUTPUT']['data'] = $format_id;
			$ret['OUTPUT']['msg'] = Text::_("COM_TJLMS_FORM_SAVE_SUCCESS");
		}
		else
		{
			$ret['OUTPUT']['data'] = $format_id;
			$ret['OUTPUT']['msg'] = Text::_("COM_TJLMS_ERROR_FILENOTSAFE_TO_UPLOAD");
		}

		echo json_encode($ret);
		jexit();
	}

	/**
	 *Populate related the tables
	 *For scorm/tjscorm add entry in tjlms_scorm
	 *For others add entry in tjlms_media
	 *
	 * @param   String  $filename         original file name
	 * @param   String  $newfilename      saved file name
	 * @param   String  $format           type of lesson format
	 * @param   String  $subformat        plugin type used for uploading format
	 * @param   INT     $lesson_id        Lesson id
	 * @param   JSON    $upload_response  The response sent from the sever when file is stored
	 *
	 * @return id of the respective table row
	 *
	 * @since 1.0.0
	 * */
	public function populate_related_tables($filename, $newfilename, $format, $subformat, $lesson_id,$upload_response)
	{
		$oluser_id = Factory::getUser()->id;
		$db	=	Factory::getDBO();
		$filename = File::makeSafe($filename);
		$newfilename = File::makeSafe($newfilename);
		$path = JPATH_ROOT . '/media/com_tjlms/lessons/';
		$media_id = 0;

		if (File::exists($path . $newfilename))
		{
				$format_data = new stdClass;
				$format_data->created_by = $oluser_id;
				$format_data->format = $format;

				if (!empty($subformat))
				{
					$format_data->sub_format = $subformat . '.upload';
				}

				$format_data->storage = 'local';
				$format_data->params = $upload_response;
				$format_data->org_filename = $filename;
				$format_data->source = $newfilename;

				$db->insertObject('#__tjlms_media', $format_data, 'id');

				$media_id = $db->insertid();

			/* If uploaded file format is associate */
			if ($format == 'associate')
			{
				$fileData            = new stdClass;
				$fileData->id        = '';
				$fileData->lesson_id = $lesson_id;
				$fileData->media_id  = $media_id;
				$db->insertObject('#__tjlms_associated_files', $fileData, 'id');
			}
		}

		/*if ($format == 'scorm' || $format == 'tjscorm')
		{
			$format_data = new stdClass;
			$format_data->lesson_id = $lesson_id;
			$format_data->storage = 'local';
			$format_data->scormtype	= 'native';

			if ($format == 'tjscorm')
			{
				$format_data->scormtype = 'local';
			}

			$format_data->package = $filename;
			$idofScormLesson = $this->getscormidforLesson($lesson_id);

			if (!empty($idofScormLesson))
			{
				$format_data->id = $idofScormLesson;
				$db->updateObject('#__tjlms_scorm', $format_data, 'id');
				/*$media_id	=	$format_data->id;*//*
			}
			else
			{
				$db->insertObject('#__tjlms_scorm', $format_data);
				/*$media_id	=	$db->insertid();*//*
			}
		}*/

		return $media_id;
	}

	/**
	 *Used to upload the format on respective servers..
	 *For scorm/tjscorm we need to unzip the file on local
	 *For box api we need to upload file on box server
	 *For tincan we need to uploaded file on scorm cloud
	 *
	 * @return object of result and message
	 *
	 * @since 1.0.0
	 * */
	public function uploadFormatsonResServer()
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');

		$input = Factory::getApplication()->input;
		$db = Factory::getDBO();
		$post = $input->post;

		$filename = $post->get('filename', '', 'STRING');
		$lesson_id = $post->get('lesson_id', '', 'INT');
		$format = $post->get('lessonformat', '', 'STRING');
		$subformat = $post->get('subformat', '', 'STRING');

		$result = 1;
		$msg = Text::sprintf("COM_TJLMS_FORMAT_UPLOAD_SUCCESS_ON", $subformat);
		$warning = $upload = '';

		/*if ($format == 'scorm' || $format == 'tjscorm')
		{
			$extract_dir = JPATH_SITE . '/media/com_tjlms/lessons/' . $lesson_id . '/scorm';

			if (Folder::exists($extract_dir))
			{
				Folder::delete($extract_dir);
			}

			$scormfile	=	JPATH_SITE . '/media/com_tjlms/lessons/' . $filename;

			if (!$this->extractCourse($extract_dir, $scormfile))
			{
				$result = 0;
				$msg = Text::sprintf("COM_TJLMS_FORMAT_EXTRACT_ERROR");
			}
		}
		else*/
		if (!empty($subformat))
		{
			$dest = JPATH_ROOT . '/media/com_tjlms/lessons/' . $filename;
			PluginHelper::importPlugin('tj' . $format);
			$upload_status = Factory::getApplication()->triggerEvent('onUpload_filesOn' . $subformat, array($lesson_id, $filename, $dest));

			if (!empty($upload_status[0]) && $upload_status[0])
			{
				$upload_res = $upload_status[0];

				if ($upload_res['res'] == 1 || $upload_res == 'true')
				{
					$result = 1;
					$upload = $upload_res;

					$errorMsg = $this->check_create_htaccess($lesson_id);

					if (!empty($errorMsg))
					{
						$warning = implode('<Br />', $errorMsg);
					}
				}
				else
				{
					$result = 0;
					$msg = $upload_res['error'];
				}
				/*foreach($resUpload as $res)
				{

				}*/
			}
			else
			{
				$result = 0;
				$msg = Text::sprintf("COM_TJLMS_FORMAT_UPLOAD_ERORR", $subformat);
			}

			/*if (!empty($upload_status[0]) && $upload_status[0])
			{
				$res = $upload_status[0];

				$params = array();
				$paramdata = new stdClass;
				$paramdata->id = $format_id;
				$params[$res['param']] = $res['value'];
				$paramdata->params = json_encode($params);
				$db->updateObject('#__tjlms_media', $paramdata, 'id');

				if ($error = $db->getErrorMsg())
				{
					$this->setError($error);
					$result = 0;
					$msg = Text::sprintf("COM_TJLMS_FORMATPARAM_STORE_ERORR", $res['column']);
				}
			}
			else
			{
				$result = 0;
				$resUpload = Text::sprintf("COM_TJLMS_FORMAT_UPLOAD_ERORR", $subformat);
			}*/
		}

		$ret['OUTPUT']['flag'] = $result;
		$ret['OUTPUT']['msg'] = $msg;
		$ret['OUTPUT']['upload'] = $upload;
		$ret['OUTPUT']['warning'] = $warning;

		echo json_encode($ret);
		jexit();
	}

	/**
	 * Function parse the scorm packge
	 * Parse the manifest file and store data in belonging tables
	 *
	 * @return  true if successful
	 *
	 * @since 1.0.0
	 */
	public function parse_format()
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');

		$input = Factory::getApplication()->input;
		$post = $input->post;

		$lesson_id = $post->get('lesson_id', '', 'INT');
		$format_id = $post->get('format_id', '', 'INT');

		$lessonFormatData['id'] = $post->get('lesson_id', '', 'INT');
		$lessonFormatData['media_id'] = $post->get('format_id', '', 'STRING');
		$lessonFormatData['uploded_lesson_file'] = $post->get('uploded_lesson_file', '', 'STRING');

		$lessonformat = $post->get('lessonformat', '', 'STRING');
		$subfomatopt = $post->get('lessonsubformat', '', 'STRING');

		$format = 'tj' . $lessonformat;
		PluginHelper::importPlugin($format);

		$results = Factory::getApplication()->triggerEvent('onparse' . $subfomatopt . 'Format', array($lessonFormatData));

		echo json_encode(1);
		jexit();
	}

	/**
	 * Function parse the scorm packge
	 * Parse the manifest file and store data in belonging tables
	 *
	 * @param   String  $filename    zip name
	 * @param   String  $format      Format scorm or Tjscorm
	 * @param   MIXED   $scorm_data  Object to be stored in tjlms_scorm
	 *
	 * @return  true if successful
	 *
	 * @since 1.0.0
	 */
	/*public function parse_scorm($filename, $format, $scorm_data)
	{
			$db = Factory::getDBO();
			$lesson_id = $scorm_data->lesson_id;

			/*$format_data = new stdClass;
			$format_data->lesson_id = $lesson_id;
			$format_data->storage = 'local';
			$format_data->scormtype	= 'native';

			if($format	== 'tjscorm')
			{
				$format_data->scormtype	= 'local';
			}
			$format_data->package = $filename;


			$db->insertObject( '#__tjlms_scorm', $format_data, 'id' );

			$scorm_table_id	=	$db->insertid();

			return $scorm_table_id;*/
			/*extract the uploaded scorm package*/
/*			$extract_dir = JPATH_SITE . '/media/com_tjlms/lessons/' . $lesson_id . '/scorm';

			if (Folder::exists($extract_dir))
			{
				Folder::delete($extract_dir);
			}

			$scormfile	=	JPATH_SITE . '/media/com_tjlms/lessons/' . $filename;

			if (!$this->extractCourse($extract_dir, $scormfile))
			{
				return false;
			}

			/* Parse the manifest file and store data in belonging tables*/
			/*if ($format == 'scorm' )
			{
				$scorm_data->id = $scorm_table_id;
				$ret = $this->tjlmsscormlib->scorm_parse($scorm_data);
			}

			return $scorm_table_id;
	}*/

	/**
	 * Function to get the id of the scorm table
	 *
	 * @param   INT  $lessonid  lessonid
	 *
	 * @return  id of tjlms_scorm
	 *
	 * @since 1.0.0
	 */
	public function getscormidforLesson($lessonid)
	{
		$db = Factory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('id')));
		$query->from($db->quoteName('#__tjlms_scorm'));
		$query->where($db->quoteName('lesson_id') . ' = ' . $lessonid);
		$query->order('id DESC');
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Function to get upload error
	 *
	 * @param   STRING  $code  Upload error code
	 *
	 * @return  STRING error code description
	 *
	 * @since 1.0.0
	 */
	private function uploadcodeToMessage($code)
	{
		switch ($code)
		{
			case UPLOAD_ERR_INI_SIZE:
				$message = Text::_('COM_TJLMS_ERROR_UPLOAD_ERR_INI_SIZE');
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$message = Text::_('COM_TJLMS_ERROR_UPLOAD_ERR_FORM_SIZE');
				break;
			case UPLOAD_ERR_PARTIAL:
				$message = Text::_('COM_TJLMS_ERROR_UPLOAD_ERR_PARTIAL');
				break;
			case UPLOAD_ERR_NO_FILE:
				$message = Text::_('COM_TJLMS_ERROR_UPLOAD_ERR_NO_FILE');
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$message = Text::_('COM_TJLMS_ERROR_UPLOAD_ERR_NO_TMP_DIR');
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$message = Text::_('COM_TJLMS_ERROR_UPLOAD_ERR_CANT_WRITE');
				break;
			case UPLOAD_ERR_EXTENSION:
				$message = Text::_('COM_TJLMS_ERROR_UPLOAD_ERR_INI_SIZE');
				break;

			default:
				$message = Text::_('COM_TJLMS_ERROR_UNKNOWN_ERROR');
				break;
		}

		return $message;
	}

	/**
	 * Function to create htaccess file for allowing access to a directory
	 *
	 * @param   STRING  $lesson_id  Lesson id for folder name
	 *
	 * @return  Boolean
	 *
	 * @since 1.0.0
	 */
	protected function check_create_htaccess($lesson_id)
	{
		jimport('joomla.filesystem.file');
		$lesson_dir		=	JPATH_SITE . '/media/com_tjlms/lessons/' . $lesson_id;
		$htaccessFile	=	$lesson_dir . '/.htaccess';

		$errorMsg = Array();

		// Only create if lesson has extracted file and htaccess is not already created
		if (Folder::exists($lesson_dir) && !File::exists($htaccessFile))
		{
			if (!is_writable($lesson_dir))
			{
				$errorMsg[] = Text::sprintf("COM_TJLMS_ERROR_FOLDER_NOT_WRITABLE", $lesson_dir);
			}

			$content = "allow from all";
			$result  = File::write($htaccessFile, $content);

			if (!$result)
			{
				$errorMsg[] = Text::sprintf("COM_TJLMS_ERROR_FILE_NOT_WRITABLE", $htaccessFile);
			}
		}

		return $errorMsg;
	}
}
