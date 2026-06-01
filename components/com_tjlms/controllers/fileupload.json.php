<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Helper\MediaHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Controller\FormController;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * File upload controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerFileupload extends FormController
{
	/**
	 * The main function triggered to upload file off question
	 *
	 * @return object of result and message
	 *
	 * @since 1.0.0
	 * */

	public function validateAndUpload()
	{
		$app         = Factory::getApplication();
		$input       = $app->input;
		$tjlmsparams = ComponentHelper::getParams('com_tjlms');

		$return = $input->post->getArray();
		$return['fileToUpload'] = $input->files->get('FileInput', null, 'raw');

		// Validate the uploaded file
		$validate_result = $this->validateupload($return);

		if ($validate_result['res'] != 1)
		{
			echo new JsonResponse(0, $validate_result['msg'], true);
			$app->close();
		}

		$return['fileToUpload']['valid'] = 1;
		$res = $this->moveToCorrectFolder($return);

		if (!$res)
		{
			$fileName = $return['fileToUpload']['name'];
			echo new JsonResponse(0, Text::sprintf("COM_TJLMS_ERROR_UPLOADINGFILE", $fileName), true);
		}
		else
		{
			$return['fileToUpload']['source'] = $res;
			echo new JsonResponse($return, Text::sprintf("COM_TJLMS_UPLOAD_SUCCESS"));
		}

		$app->close();
	}

	/**
	 * The main function triggered to upload file off question
	 *
	 * @param   Array  $data  Array of data for fileupload
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 * */

	private function moveToCorrectFolder($data)
	{
		$src = $data['fileToUpload']['tmp_name'];

		// Make the filename safe
		$fileName = File::makeSafe($data['fileToUpload']['name']);

		$ext = File::getExt($fileName);

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$mediaModel = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');

		$filepath = $mediaModel->getuploadFolder($data);

		if (!$filepath)
		{
			echo new JsonResponse('', Text::sprintf("COM_TJLMS_NO_FOLDER_FOUND_AS_PER_FORMAT"), true);
		}
		else
		{
			// New file name
			$newfilename = rand(0, 999999999) . '.' . $ext;

			$PathWithName = $filepath . $newfilename;

			$destination = JPATH_SITE . $PathWithName;

			$options = array('fobidden_ext_in_content' => false);

			/*3rd param is to stream and 4is set to true to ask to upload unsafe file*/
			if (!File::upload($src, $destination, false, false, $options))
			{
				return false;
			}

			return $newfilename;
		}
	}

	/**
	 * The function to validate the uploaded format file
	 *
	 * @param   MIXED  $data  Post
	 *
	 * @return  object of result and message
	 *
	 * @since 1.0.0
	 * */
	private function validateupload($data)
	{
		$fileToUpload = $data['fileToUpload'];

		$tjlmsparams = ComponentHelper::getParams('com_tjlms');
		$mediaParams = ComponentHelper::getParams('com_media');
		$validExtensions = array_map('trim', explode(',', $mediaParams->get('doc_extensions')));
		$ext = File::getExt(File::makeSafe($fileToUpload['name']));

		$return = 1;
		$msg	= '';

		if (!in_array($ext, $validExtensions))
		{
			$return = 0;
			$msg = Text::_("COM_TJLMS_ERROR_FILE_EXTENSION_TO_UPLOAD");
		}
		elseif ($fileToUpload["error"] == UPLOAD_ERR_OK)
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
				$msg = Text::sprintf('COM_TMT_UPLOAD_SIZE_ERROR', $tjlmsparams->get('lesson_upload_size', 10, 'INT') . ' MB');
			}

			$zipFormats = array("scorm", "tincanlrs", "htmlzips");

			if (in_array($data['mediaformat'], $zipFormats) && strtolower($ext) != 'zip')
			{
				$msg    = Text::_("COM_TJLMS_VALID_ZIP_PACKAGE");
				$return = 0;
			}

			if ($return == 1)
			{
				switch ($data['mediaformat'])
				{
					case 'scorm':
							/*$valid_extensions_arr	=	array('application/zip','application/x-zip-compressed','application/x-rar-compressed' ,'application/octet-stream');*/

						if ($this->checkScormPackage($fileToUpload) != 1)
						{
							$return = 0;
							$msg    = Text::_("COM_TJLMS_VALID_SCORM_PACKAGE");
						}

						break;
					case 'tincanlrs':

						if ($this->checkTincanPackage($fileToUpload) != 1)
						{
							$return = 0;
							$msg    = Text::_("COM_TJLMS_VALID_TINCAN_PACKAGE");
						}

					break;
					case 'htmlzips':

						if ($this->checkHtmlzipPackage($fileToUpload) != 1)
						{
							$return = 0;
							$msg    = Text::_("COM_TJLMS_VALID_HTMLZIP_PACKAGE");
						}
					break;
				}
			}
		}
		else
		{
			$return = 0;
			$msg    = Text::_("COM_TJLMS_ERROR_UPLOADINGFILE", $fileToUpload['name']);
		}

		$format     = $data['mediaformat'];
		$subformat  = $data['subformat'];
		PluginHelper::importPlugin('tj' . $format);
		$check = Factory::getApplication()->triggerEvent('onValidateFilesFor' . $subformat, array($data));

		if (!empty($check[0]) && $check[0])
		{
			$checkRes = $check[0];

			if ($checkRes['res'] == 1 || $checkRes == 'true')
			{
				$return = 1;
				$msg    = $checkRes;
			}
			else
			{
				$return = 0;
				$msg    = $checkRes['msg'];
			}
		}

		$output        = array();
		$output['res'] = $return;
		$output['msg'] = $msg;

		return $output;
	}

	/**
	 * Triggered from ajax
	 *
	 * @return json encoded id of the row of related table
	 *
	 * @since 1.3
	 * */
	public function onAfterUpload()
	{
		$app    = Factory::getApplication();
		$input  = $app->input;
		$return = $input->post->getArray();

		$format    = $return['mediaformat'];
		$subformat = $return['subformat'];

		if (($format == 'quiz' && $subformat == 'answer') || ($format == 'associate' && $subformat == 'upload'))
		{
			$error = false;
			$msg = Text::_("COM_TJLMS_FILE_MOVED_TO_CORRECT_LOCATION");
		}
		else
		{
			$formatData  = $return['formatData'];
			$lessonId = $formatData[$format][$subformat]['lessonId'];
			$fileName = File::makeSafe($return['fileToUpload']['source']);

			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
			$mediaModel = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');

			$filepath = $mediaModel->getuploadFolder($return);

			$filepath = JPATH_ROOT . $filepath . $return['fileToUpload']['source'];

			PluginHelper::importPlugin('tj' . $format);
			$upload_status = Factory::getApplication()->triggerEvent('onUpload_filesOn' . $subformat, array($lessonId, $fileName, $filepath));

			if (!empty($upload_status[0]) && $upload_status[0])
			{
				$uploadRes = $upload_status[0];

				if (!empty($uploadRes['res']) && $uploadRes['res'] == 1 || $uploadRes == 'true' || $uploadRes == 1)
				{
					$error = false;
					$return['uploadRes'] = $uploadRes;
					$msg = Text::_("COM_TJLMS_FILE_MOVED_TO_CORRECT_LOCATION");
				}
				else
				{
					$error = true;
					$msg = $uploadRes['error'];
				}
			}
			else
			{
				$error = true;
				$msg = Text::sprintf("COM_TJLMS_FORMAT_UPLOAD_ERORR", $subformat);
			}
		}

		echo new JsonResponse($return, $msg, $error);
		$app->close();
	}

	/**
	 * Triggered from ajax
	 * Add entry in tjlms_media
	 *
	 * @return json encoded id of the row of related table
	 *
	 * @since 1.3
	 * */
	public function addTableEntries()
	{
		$app            = Factory::getApplication();
		$input          = $app->input;
		$post           = $input->post;
		$uploadPathData = $mediaData = [];
		$format         = $uploadPathData['mediaformat'] = $post->get('mediaformat', '', 'STRING');
		$subformat      = $uploadPathData['subformat'] = $post->get('subformat', '', 'STRING');
		$formatData     = $post->get('formatData', array(), 'ARRAY');
		$file           = $post->get('fileToUpload', '', 'Array');
		$uploadRes      = $post->get('uploadRes', array(), 'ARRAY');

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$mediaModel = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');

		if (!empty($file))
		{
			$mediaData['source']       = File::makeSafe($file['source']);
			$mediaData['org_filename'] = File::makeSafe($file['name']);
			$mediaData['format']       = $format;
			$mediaData['sub_format']   = $subformat . '.upload';
			$mediaData['params']       = json_encode($uploadRes);
			$mediaData['path']         = $mediaModel->getuploadFolder($uploadPathData);
			$mediaData['created_by']   = Factory::getUser()->id;
			$mediaData['saved_filename'] = '';
			
			$path = $mediaModel->getuploadFolder($uploadPathData);

			if (File::exists(JPATH_SITE . $path . $mediaData['source']))
			{
				$mediaModel->save($mediaData);
				$mediaId     = $mediaModel->getState($mediaModel->getName() . '.id');
				$mediaDetail = $mediaModel->getItem($mediaId);

				if (!empty($format))
				{
					$lessonFormats = array('scorm','htmlzips','tincanlrs','video',
					'document','textmedia','externaltool','event', 'survey','form');

					if (in_array($format, $lessonFormats))
					{
						Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
						$lessonTable = Table::getInstance('Lesson', 'TjlmsTable', array('dbo', Factory::getDbo()));
						$lessonId        = $formatData[$format][$subformat]['lessonId'];
						$mediaData['id'] = $lessonId;
						$lessonTable->load($lessonId);
						$lessonTable->media_id  = $mediaId;
						$lessonTable->store();
					}
					elseif ($format == 'quiz' && $subformat == 'answer')
					{
						$mediaData['path'] = '#';

						// Get media URL
						$mediaDownloadUrl = $mediaModel->getMediaUrl($mediaDetail);

						if (!empty($mediaDownloadUrl))
						{
							$mediaData['path'] = $mediaDownloadUrl;
						}

						Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tmt/tables');
						$testAnswersTable = Table::getInstance('testanswers', 'TmtTable', array('dbo', Factory::getDbo()));

						$test = $formatData['quiz'];

						$olUserId = Factory::getUser()->id;
						$testAnswersTable->load(
									array("question_id" => $test['answer']['qid'],
									"user_id" => $olUserId, "test_id" => $test['answer']['testid'],
									"invite_id" => $test['answer']['ltid']
									)
									);
						$mediaData['answer_id'] = $testAnswersTable->id;
						$mediaData['qid']       = $test['answer']['qid'];
					}
					elseif ($format == 'associate')
					{
						$db = Factory::getDbo();

						$fileData            = new stdClass;
						$fileData->id        = '';
						$fileData->lesson_id = $formatData['associate']['upload']['lessonId'];
						$fileData->media_id  = $mediaId;
						$db->insertObject('#__tjlms_associated_files', $fileData, 'id');
					}
				}

				$mediaData['media_id'] = $mediaId;

				$format = 'tj' . $format;
				PluginHelper::importPlugin($format);

				Factory::getApplication()->triggerEvent('onparse' . $subformat . 'Format', array($mediaData));
				$msg = Text::_("COM_TJLMS_FILE_TABLE_ENTRIES_ADDED");
				echo new JsonResponse($mediaData, $msg);
			}
			else
			{
				echo new JsonResponse(0, Text::_("COM_TJLMS_ERROR_FILENOTSAFE_TO_UPLOAD"), true);
			}
		}
		else
		{
			echo new JsonResponse(0, Text::_("COM_TJLMS_ERROR_FILENOTSAFE_TO_UPLOAD"), true);
		}

		$app->close();
	}

	/**
	 * Check if the uploaded zip id valid scorm package
	 *
	 * @param   String  $file  Uploaded zip
	 *
	 * @return  Boolean 1 if yes
	 *
	 * @since  1.3.30
	 * */
	public function checkScormPackage($file)
	{
		$return = 1;

		if (isset($file['tmp_name']) && $file['error'] == UPLOAD_ERR_OK)
		{
			$za = new ZipArchive;

			$za->open($file['tmp_name']);

			if (!$za->locateName('imsmanifest.xml', ZIPARCHIVE::FL_NODIR))
			{
				$return = 0;
			}
		}

		return $return;
	}

	/**
	 * Check if the uploaded zip id valid HTMLZIP package
	 *
	 * @param   String  $file  Uploaded zip
	 *
	 * @return  Boolean 1 if yes
	 *
	 * @since  1.3.30
	 * */
	public function checkHtmlzipPackage($file)
	{
		$return = 1;

		if (isset($file['tmp_name']) && $file['error'] == UPLOAD_ERR_OK)
		{
			$za = new ZipArchive;

			$za->open($file['tmp_name']);

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
	 * @return  Boolean 1 if yes
	 *
	 * @since  1.3.30
	 * */
	public function checkTincanPackage($file)
	{
		$return = 1;

		if (isset($file['tmp_name']) && $file['error'] == UPLOAD_ERR_OK)
		{
			$za = new ZipArchive;

			$za->open($file['tmp_name']);

			if (!$za->locateName('tincan.xml', ZIPARCHIVE::FL_NODIR))
			{
				$return = 0;
			}
		}

		return $return;
	}
}
