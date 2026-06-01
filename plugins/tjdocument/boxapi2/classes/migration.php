<?php
/**
 * @package    Shika_Document_Viewer
 * @copyright  Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Log\Log;

/**
 * Box Documents migration
 *
 * @since  1.0.0
 */
class BoxApiMigration extends CMSObject
{
	public static $logger = false;

	public $token = '';

	public $document = null;

	public $boxObj = null;

	/**
	 * Plugin that supports uploading and tracking the PPTs PDFs documents of Box API
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		JLoader::import('boxapi', __DIR__);
		$this->boxObj = new BoxAPI;
	}

	/**
	 * Method to get API Token
	 *
	 * @return  string  API Token or empty string
	 *
	 * @since   1.0.0
	 */
	public function getBoxApi2Token()
	{
		if (!$this->token)
		{
			$this->token = $this->boxObj->createJWTToken();

			if (!$this->token)
			{
				$error = $this->boxObj->getError();

				if ($error)
				{
					$this->setError($error);
				}
				else
				{
					$this->setError(Text::_('PLG_TJDOCUMENT_BOXAPI2_COULD_NOT_CREATE_TOKEN'));
				}
			}
		}

		return $this->token;
	}

	/**
	 * Method to check if user can migrate docs
	 *
	 * @return  Boolean
	 *
	 * @since   1.0.0
	 */
	public function canMigrate()
	{
		// Run only for backend
		$isAdmin = Factory::getApplication()->isAdmin();

		if (!$isAdmin)
		{
			return false;
		}

		// User should have access
		$canMigrate = $this->canUserMigrate();

		if (!$canMigrate)
		{
			return false;
		}

		// Storage must be local
		$storage = ComponentHelper::getParams('com_tjlms')->get('lesson_files_stores', 'local');

		if ($storage != 'local')
		{
			return false;
		}

		// Must have documents to migrate
		$hasDoc = $this->getTotalDocsToMigrate();

		if (!$hasDoc)
		{
			return false;
		}

		// Should have setup new box migration
		$isApiSet = $this->getBoxApi2Token();

		if (!$isApiSet)
		{
			return false;
		}

		return true;
	}

	/**
	 * Method to check if user has Admin access
	 *
	 * @return  Boolean
	 *
	 * @since   1.0.0
	 */
	public function canUserMigrate()
	{
		$user = Factory::getUser();
		$isroot = $user->authorise('core.admin');

		return $isroot;
	}

	/**
	 * Get Media detail of document
	 *
	 * @param   INT  $lastId  Get Box Media of next to this Id
	 *
	 * @return  MIX  Media detail
	 *
	 * @since   1.0.0
	 */
	public function getDocumentDetail($lastId = 0)
	{
		if (!$this->document || ($lastId && $this->document->id != $lastId))
		{
			// Get Media Data
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from('#__tjlms_media as m');
			$query->where('m.sub_format="boxapi.upload"');
			$query->where('m.storage="local"');
			$query->order('m.id asc');

			// Get next document to process
			if ($lastId)
			{
				$query->where('id > ' . (int) $lastId);
			}

			$db->setQuery($query, 0, 1);
			$this->document = $db->loadObject();

			if ($this->document)
			{
				$this->document->params = json_decode($this->document->params, true);
			}
		}

		return $this->document;
	}

	/**
	 * Update media document detail after migration
	 *
	 * @param   INT     $id        Media Id
	 * @param   STRING  $newdocid  New document Id
	 *
	 * @return  MIX boolean false on failure or Media id
	 *
	 * @since   1.0.0
	 */
	public function updateDocMediaAfterMigrate($id, $newdocid)
	{
		$media = $this->getDocumentDetail($id);

		$object = new stdClass;
		$object->id = $id;
		$object->sub_format = 'boxapi2.upload';
		$object->params = $media->params;
		$object->params['old_document_id'] = $media->params['document_id'];
		$object->params['document_id'] = $newdocid;
		$object->params = json_encode($object->params);

		$result = Factory::getDbo()->updateObject('#__tjlms_media', $object, 'id');
		$result = $result ? $id : false;

		return $id;
	}

	/**
	 * Get total number of documents to migrate
	 *
	 * @return  INT Number of documents
	 *
	 * @since   1.0.0
	 */
	public function getTotalDocsToMigrate()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('COUNT(*)');
		$query->from('#__tjlms_media as m');
		$query->where('m.sub_format="boxapi.upload"');
		$query->where('m.storage="local"');

		$db->setQuery($query);
		$totalDocs = $db->loadResult();

		return $totalDocs;
	}

	/**
	 * Method to add migrate button in toolbar
	 *
	 * @return  Void
	 *
	 * @since   1.0.0
	 */
	public function displayMigrateButton()
	{
		$this->addMigrationStyleScript();

		$bar = JToolBar::getInstance('toolbar');
		$button = "<button id='box-migrate' class='btn'
			type='submit' onclick=\"tjBoxapi2.migrate.init(); return false;\">
				<img title='" . Text::_('PLG_TJDOCUMENT_BOXAPI2_MIGRATION_DESC') .
				"' class='migrate-icon' src='" . Uri::root() . "plugins/tjdocument/boxapi2/assets/images/icon-migrate.png'/>
				" . Text::_('PLG_TJDOCUMENT_BOXAPI2_MIGRATION_LABEL') . "
			</button>";
		$bar->appendButton('Custom', $button);
	}

	/**
	 * Method to add style and script for migration progress
	 *
	 * @return  Void
	 *
	 * @since   1.0.0
	 */
	public function addMigrationStyleScript()
	{
		$log = Factory::getConfig()->get('log_path');
		$document = Factory::getDocument();
		$document->addScript(JURI::root(true) . '/plugins/tjdocument/boxapi2/assets/js/migration.js?v=1.0');

		$script = 'var tjBoxapi2 = typeof tjBoxapi2 == "undefined" ? {} : tjBoxapi2;
		tjBoxapi2.migrate = typeof tjBoxapi2.migrate == "undefined" ? {} : tjBoxapi2.migrate;
		tjBoxapi2.migrate.url = "' . Juri::root() . 'administrator/index.php?option=com_ajax&group=tjdocument&plugin=boxapi2&format=json"
		tjBoxapi2.migrate.logpath = "' . $log . '"';
		$document->addScriptDeclaration($script);

		Text::script('PLG_TJDOCUMENT_BOXAPI2_CONFIRM_TO_ABORT');
		Text::script('PLG_TJDOCUMENT_BOXAPI2_MIGRATION_ABORTED');
		Text::script('PLG_TJDOCUMENT_BOXAPI2_ABORTED');
		Text::script('PLG_TJDOCUMENT_BOXAPI2_N_DOCS_MIGRATED_SUCCESSFULLY');
		Text::script('PLG_TJDOCUMENT_BOXAPI2_N_DOCS_MIGRATION_FAILED');
		Text::script('PLG_TJDOCUMENT_BOXAPI2_X_DOCS_OUT_OF_Y_PROCESSED');
		Text::script('PLG_TJDOCUMENT_BOXAPI2_NO_DOCUMENTS_TO_MIGRATE');
		Text::script('PLG_TJDOCUMENT_BOXAPI2_CONFIRM_TO_MIGRATE');
		Text::script('PLG_TJDOCUMENT_BOXAPI2_CHECK_LOG_FILE');
		Text::script('PLG_TJDOCUMENT_BOXAPI2_ERROR_GETTING_DOCUMENTS');
	}

	/**
	 * Method to migrate media based on passed id
	 *
	 * @param   INT  $id  Media Id to process
	 *
	 * @return  Boolean
	 *
	 * @since   1.0.0
	 */
	public function migrateMedia($id)
	{
		$result = false;
		$media = $this->getDocumentDetail($id);

		// If no data to migrate
		if (empty($media))
		{
			return $result;
		}

		$msg   = array();
		$msg[] = 'Start Processing - MediaId : ' . $media->id;
		$msg[] = 'FileName : ' . $media->org_filename;
		$msg[] = 'Source : ' . $media->source;
		$msg[] = 'BoxDocumentID : ' . $media->params['document_id'];

		$this->addLog(implode(', ', $msg));
		$errorType = 'error';
		$filepath = JPATH_SITE . '/media/com_tjlms/lessons/' . $media->source;

		if (file_exists($filepath))
		{
			$documentId = $this->uploadDocumentToBox($media->org_filename, $filepath);

			if ($documentId)
			{
				$errorType = 'info';
				$result  = $this->updateDocMediaAfterMigrate($media->id, $documentId);
				$message = 'Document migrated successfully. New document Id - ' . $documentId;
			}
			else
			{
				$message = $this->getError();
				$message = $message ? $message : 'Unknown error during document migration.';
			}
		}
		else
		{
			$message = 'File does not exists - ' . $filepath;
		}

		$this->addLog($message, $errorType);

		if (!$result)
		{
			$this->setError('Could not migrate document');
		}

		return $media->id;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @param   INT  $filename  File Name
	 * @param   INT  $filepath  File physical path
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.0.0
	 */
	public function uploadDocumentToBox($filename, $filepath)
	{
		ini_set('max_execution_time', 300);

		$this->boxObj->access_token  = $this->token;
		$upload_result      = $this->boxObj->sendFileToBox($filename, $filepath);

		if ($upload_result)
		{
			return $upload_result;
		}
		else
		{
			$this->setError(implode('<br>', $this->boxObj->getErrors()));
		}

		return false;
	}

	/**
	 * Method to add log
	 *
	 * @param   STRING  $message  Message to log
	 * @param   STRING  $type     Message type
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.0.0
	 */
	public function addLog($message, $type = 'info')
	{
		if (!self::$logger)
		{
			self::$logger = true;
			jimport('joomla.log.log');
			Log::addLogger(array('text_file' => 'boxapi.migration.php'), Log::ALL, array('boxapi'));
		}

		$logType = Log::INFO;

		if ($type == 'error')
		{
			$logType = Log::ERROR;
		}
		elseif ($type == 'warning')
		{
			$logType = Log::WARNING;
		}

		Log::add(Text::_($message), $logType, 'boxapi');
	}
}
