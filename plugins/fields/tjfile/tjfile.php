<?php
/**
 * @package     Joomla.Plugins
 * @subpackage  PlgFieldsTjFile
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// @namespace Techjoomla\Plugin\Fields;

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\Log\Logger\FormattedtextLogger;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

JLoader::import('components.com_fields.libraries.fieldsplugin', JPATH_ADMINISTRATOR);
jimport('joomla.form.formfield');
jimport('joomla.filesystem.file');

/**
 * Fields File Plugin
 *
 * @since  1.0.0
 */
class PlgFieldsTjfile extends FieldsPlugin
{
	/**
	 * Transforms the field into a DOM XML element and appends it as a child on the given parent.
	 *
	 * @param   stdClass    $field   The field.
	 * @param   DOMElement  $parent  The field node parent.
	 * @param   JForm       $form    The form.
	 *
	 * @return  DOMElement
	 *
	 * @since   1.0.0
	 */
	public function onCustomFieldsPrepareDom($field, \DOMElement $parent, Form $form)
	{
		$fieldNode = parent::onCustomFieldsPrepareDom($field, $parent, $form);

		if (!$fieldNode)
		{
			return $fieldNode;
		}

		Form::addFieldPath(JPATH_PLUGINS . '/fields/tjfile/fields');
		$fieldNode->setAttribute('type', 'tjfile');

		return $fieldNode;
	}

	/**
	 * The function to validate the uploaded format file
	 *
	 * @return  object of result
	 *
	 * @since 1.0.0
	 * */
	public function onAjaxValidateAndUpload()
	 {
		$app       = Factory::getApplication();
		$user      = Factory::getUser();
		$input     = $app->input;
		$contentId = $input->get('id', 0, 'INT');

		$return                 = $input->post->getArray();
		$return['fileToUpload'] = $input->files->get('file', null, 'raw');

		// Validate the uploaded file
		$validate_result = $this->validateupload($return);

		if ($validate_result['res'] != 1)
		{
			echo new JsonResponse(0, $validate_result['msg'], true);
			$app->close();
		}

		$return['fileToUpload']['valid'] = 1;

		$res      = $this->uploadFile($return);
		$fileName =  $return['fileToUpload']['name'];

		if (!$res)
		{
			echo new JsonResponse(0, Text::sprintf("PLG_FIELDS_TJFILE_UPLOADING_FILE", $fileName), true);
		}
		else
		{
			$config = array(
				'text_file' => 'fileUploadlogs.log'
			);

			$logger  = new FormattedtextLogger($config);
			$logText = $user->id . ' ' . Text::sprintf("PLG_FIELDS_TJFILE_UPLOADING_FILE_LOG_MSG", $fileName, $contentId);

			$entry = new LogEntry($logText, Log::INFO);

			$logger->addEntry($entry);


				$app = Factory::getApplication();  
				echo new JResponseJson($res, false, false);
		}

		$app->close();
	}

	/**
	 * The function to validate the uploaded format file
	 *
	 * @param   MIXED  $data  Post
	 *
	 * @return  Array of result and message
	 *
	 * @since 1.0.0
	 * */
	public function validateupload($data)
	{
		$app          = Factory::getApplication()->input;
		$fileToUpload = $data['fileToUpload'];

		$session    = Factory::getSession();
		$configData = $session->get('fileupload');

		$return = 1;
		$msg	= '';

		if ($fileToUpload["error"] == UPLOAD_ERR_OK)
		{
			if ( $app->server->getString('CONTENT_LENGTH', '') > ($configData['filesize'] * 1024 * 1024)
				|| $app->server->getString('CONTENT_LENGTH', '') > (int) (ini_get('upload_max_filesize')) * 1024 * 1024
				|| $app->server->getString('CONTENT_LENGTH', '') > (int) (ini_get('post_max_size')) * 1024 * 1024
				|| (($app->server->getString('CONTENT_LENGTH', '') > (int) (ini_get('memory_limit')) * 1024 * 1024) && ((int) (ini_get('memory_limit')) != -1)))
			{
				$return = 0;
				$msg = Text::sprintf('PLG_FIELDS_TJFILE_ERROR_UPLOAD_SIZE', $configData['filesize'] . ' KB');
			}
			elseif ($return == 1)
			{
				$fileName = File::makeSafe($data['fileToUpload']['name']);
				$fileName = preg_replace('/\s/', '_', $fileName);

				$ext = File::getExt($fileName);

				// Check file type extension with respect to configs
				$valid_extensions_arr = explode(",", trim($configData['acceptedformats']));

				if (!in_array($ext, $valid_extensions_arr))
				{
					$msg = Text::_('PLG_FIELDS_TJFILE_ERROR_INVALID_FILE');
					$return = 0;
				}
			}
		}
		else
		{
			$return = 0;
			$msg = Text::_("PLG_FIELDS_TJFILE_ERROR_UPLOADING", $fileToUpload['name']);
		}

		$output['res'] = $return;
		$output['msg'] = $msg;

		return $output;
	}

	/**
	 * The uploadFile function triggered to upload file
	 *
	 * @param   Array  $data  Array of data for fileupload
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 * */
	private function uploadFile($data)
	{
		$src = $data['fileToUpload']['tmp_name'];

		// Make the filename safe
		$fileName = File::makeSafe($data['fileToUpload']['name']);
		$fileName = preg_replace('/\s/', '_', $fileName);

		$ext      = File::getExt($fileName);
		$fileName = basename($fileName, $ext);

		$session    = Factory::getSession();
		$configData = $session->get('fileupload');
		$filepath   = JPATH_SITE . '/' . $configData['destination'] . '/';

		if (!$filepath)
		{
			echo new JsonResponse('', Text::sprintf("PLG_FIELDS_TJFILE_ERROR_UPLOAD_FOLDER_NOT_FOUND"), true);
		}
		else
		{
			// New file name
			$newfilename  = $fileName . '_' . rand(0, 999999999) . '.' . $ext;
			$PathWithName = $filepath . $newfilename;

			$options = array('fobidden_ext_in_content' => false);

			/*3rd param is to stream and 4is set to true to ask to upload unsafe file*/
			if (!File::upload($src, $PathWithName, false, false, $options))
			{
				return false;
			}

			return $newfilename;
		}
	}

	/**
	 * The DeleteFile function delete file from folder
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 * */
	public function onAjaxDeleteFile()
	{
		$app = Factory::getApplication();

		$fileName  = $app->input->get('button', null, 'raw');
		$contentId = $app->input->get('id', 0, 'INT');

		$user          = Factory::getUser();
		$canDeleteFile = $user->authorise('core.delete', 'com_content');

		if (!$canDeleteFile)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect(Route::_(Uri::base()));

			return false;
		}

		$session    = Factory::getSession();
		$configData = $session->get('fileupload');
		$filepath   = JPATH_SITE . '/' . $configData['destination'] . '/' . $fileName;

		if (File::exists($filepath))
		{
			unlink($filepath);

			$config = array(
				'text_file' => 'fileUploadlogs.log'
			);

			$logger  = new FormattedtextLogger($config);
			$logText = $user->id . ' ' . Text::sprintf("PLG_FIELDS_TJFILE_DELETE_FILE_LOG_MSG", $fileName, $contentId);

			$entry = new LogEntry($logText, Log::INFO);

			$logger->addEntry($entry);

			echo new JsonResponse(1, Text::sprintf("PLG_FIELDS_TJFILE_DELETED_SUCCESSFULLY", $fileName), true);
		}

		$app->close();
	}
}
