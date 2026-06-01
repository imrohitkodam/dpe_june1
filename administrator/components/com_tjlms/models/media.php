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
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;

jimport('joomla.application.component.modeladmin');
jimport('joomla.filesystem.folder');
jimport('techjoomla.common');
jimport('joomla.log.log');

require_once JPATH_SITE . '/media/com_tjlms/vendors/otphp/lib/otphp.php';

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/xref", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/tables/files", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/tables/xref", JPATH_LIBRARIES);

/**
 * Tjlms model.
 *
 * @since  1.0.0
 */
class TjlmsModelMedia extends AdminModel
{
	// Set to 3 minutes
	protected $timelyUrlInterval = 180;

	public $defaultMimeTypes = array(
		'image/jpeg',
		'image/gif',
		'image/png',
	);

	public $defaultImageExtensions = array ('gif', 'jpg', 'png', 'jpeg');

	/**
	 * Constructor.
	 *
	 * @see     JControllerLegacy
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function __construct()
	{
		$this->tjLmsParams = ComponentHelper::getParams('com_tjlms');
		parent::__construct();
	}


// DPE HACK TO SET PRIMARY KEY AS IN JOOMLA 4 ITS TAKING THE DEFAULT MENU ID AS PK ALWAYS
	/**
     * Stock method to auto-populate the model state.
     *
     * @return  void
     *
     * @since   1.6
     */
    protected function populateState()
    {
        $table = $this->getTable();
        $key   = $table->getKeyName();

        // Get the pk of the record from the request.
        $pk = Factory::getApplication()->getInput()->getInt($key);
        $pk = (Factory::getApplication()->getMenu()->getDefault()->query['id'] == $pk)?'':$pk;
        $this->setState($this->getName() . '.id', $pk);

        // Load the parameters.
        $value = ComponentHelper::getParams($this->option);
        $this->setState('params', $value);
    }

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   type    $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return    JTable    A database object
	 *
	 * @since    1.6
	 */
	public function getTable($type = 'Media', $prefix = 'TjlmsTable', $config = array())
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the media id by the format subformat and source passed.
	 *
	 * @param   array  $mediaData  An  array of data for the form to interogate.
	 *
	 * @return  INT
	 *
	 * @since  1.0
	 */
	public function getMediaIdByData($mediaData)
	{
		$mediaTable = $this->getTable();
		$mediaTable->load($mediaData);

		return $mediaTable->id;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm  A JForm object on success, false on failure
	 *
	 * @since  1.0
	 */
	public function getForm($data = array() , $loadData = true)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tjlms.media', 'media', array( 'control' => 'jform', 'load_data' => $loadData ));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return mixed  The data for the form.
	 *
	 * @since 1.0
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_tmt.edit.test.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Function to check if Media is reused
	 *
	 * @param   INT  $media_id  media_id
	 *
	 * @return  Boolean
	 *
	 * @since 1.1.4
	 */
	private function isMediaUsed($media_id)
	{
		$media_id = (int) $media_id;

		try
		{
			if ($media_id)
			{
				$query = $this->_db->getQuery(true);
				$query->select('count(*)');
				$query->from($this->_db->qn('#__tjlms_lessons', 'l'));
				$query->where($this->_db->qn('l.media_id') . ' = ' . (int) $media_id);
				$this->_db->setQuery($query);
				$used_count = $this->_db->loadResult();

				return $used_count;
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function to remove Media
	 *
	 * @param   INT  &$id  Media id
	 *
	 * @return  Boolean
	 *
	 * @since 1.1.4
	 */
	public function delete(&$id)
	{
		$item = $this->getItem($id);
		$table = $this->getTable();

		if ($table->load($id))
		{
			if ($table->delete($id))
			{
				// Delete media entries
				$this->deleteMediaFiles($item);

				return true;
			}
		}

		return false;
	}

	/**
	 * Function to remove Media
	 *
	 * @param   MIX  $mediaObj  Media object
	 *
	 * @return  Boolean
	 *
	 * @since 1.1.4
	 */
	private function deleteMediaFiles($mediaObj)
	{
		try
		{
			if (!empty($mediaObj))
			{
				if ($mediaObj->storage != 'invalid')
				{
					require_once JPATH_ROOT . '/components/com_tjlms/libraries/storage.php';
					$Tjstorage = new Tjstorage;
					$storageId = $mediaObj->path . $mediaObj->source;
					$storage = $Tjstorage->getStorage($mediaObj->storage);
					$storage->delete($storageId);
				}
			}
		}
		catch (Exception $e)
		{
				$this->setError($e->getMessage());

				return false;
		}

		return true;
	}

	/**
	 * Method to get url of Question/Answer's media.
	 *
	 * @param   object   $mediaObj      Media obj.
	 *
	 * @param   boolean  $getTimelyUrl  Get timely URL based on OTP.
	 *
	 * @return	string|void	URL.
	 */
	public function getMediaUrl($mediaObj, $getTimelyUrl = false)
	{
		if (!$mediaObj->id || !$mediaObj->source)
		{
			return;
		}

		$timelyUrl = Route::_('index.php?option=com_tjlms&task=lesson.downloadMedia&mid=' . $mediaObj->id);

		if ($getTimelyUrl)
		{
			$totp = $this->generateTotp($mediaObj);

			$timelyUrl = 'index.php?option=com_tjlms&task=lesson.downloadMedia&mid=' . $mediaObj->id . '&otpToken=' . $totp->now();
			$timelyUrl = Uri::root() . substr(Route::_($timelyUrl), strlen(Uri::base(true)) + 1);
		}

		return $timelyUrl;
	}

	/**
	 * Method to generate TOTP.
	 *
	 * @param   object  $mediaObj  Media obj.
	 *
	 * @return	mixed Totp
	 */
	public function generateTotp($mediaObj)
	{
		$totp = new \OTPHP\TOTP(\Base32::encode($mediaObj->id . '+' . $mediaObj->org_filename), array ('interval' => $this->timelyUrlInterval));

		return $totp;
	}

	/**
	 * Method to verify TOTP.
	 *
	 * @param   object   $mediaObj  Media obj.
	 * @param   integer  $token     Generated token to verify
	 *
	 * @return	boolean
	 */
	public function verifyTotp($mediaObj, $token)
	{
		$totp = $this->generateTotp($mediaObj);

		if ($totp->verify($token))
		{
			return true;
		}

		return false;
	}

	/**
	 * The main function triggered to upload file off question
	 *
	 * @param   Array  $data  Array of data for fileupload
	 *
	 * @return object of result and message
	 *
	 * @since 1.0.0
	 * */
	public function getuploadFolder($data)
	{
		$folder = '';

		if (!empty($data['mediaformat']))
		{
			if ($data['mediaformat'] == 'quiz' && ($data['subformat'] == 'answer.upload' || $data['subformat'] == 'answer'))
			{
				$folder = '/media/com_tmt/test/';
			}
			else
			{
				$folder = '/media/com_tjlms/lessons/';
			}
		}

		return $folder;
	}

	/**
	 * Method to upload the file (image/PDF/Audio)
	 *
	 * @param   ARRAY|Object  $files   fileData
	 * @param   Integer       $access  access for uploading
	 * @param   ARRAY|Object  $config  client and client id
	 *
	 * @return	array
	 *
	 * @since   2.0
	 */
	public function uploadImage($files, $access = null, $config = array())
	{
		if (!isset($config['size']))
		{
			$config['size']                = $this->tjLmsParams->get('tjlms_image_size', '2');
		}

		if (!isset($config['type']))
		{
			$temp           = $this->tjLmsParams->get('tjlms_image_mime_type', implode(",", $this->defaultMimeTypes));
			$mediaMimeType = array_map('trim', explode(',', $temp));
			$config['type'] = $mediaMimeType;
		}

		if (!isset($config['allowedExtension']))
		{
			$temp = $this->tjLmsParams->get('tjlms_image_extension', implode(",", $this->defaultImageExtensions));
			$mediaAllowedExtension = array_map('trim', explode(',', $temp));
			$config['allowedExtension'] = array_map('strtolower', $mediaAllowedExtension);
		}

		$config['saveData']                                   = 1;
		$config['state']                                      = '0';
		$config['auth']                                       = 1;
		$config['imageResizeSize']                            = array();
		$config['imageResizeSize']['small']['small_width']    = $this->tjLmsParams->get('small_width', '128');
		$config['imageResizeSize']['small']['small_height']   = $this->tjLmsParams->get('small_height', '128');
		$config['imageResizeSize']['medium']['medium_width']  = $this->tjLmsParams->get('medium_width', '240');
		$config['imageResizeSize']['medium']['medium_height'] = $this->tjLmsParams->get('medium_height', '240');
		$config['imageResizeSize']['large']['large_width']    = $this->tjLmsParams->get('large_width', '400');
		$config['imageResizeSize']['large']['large_height']   = $this->tjLmsParams->get('large_height', '400');

		$mediaLib = TJMediaStorageLocal::getInstance($config);

		$uploadRes = $mediaLib->upload($files);

		if (!empty($uploadRes[0]))
		{
			if (!$this->saveMediaXref($uploadRes[0]['id'], $config['client_id'], $config['client']))
			{
				return false;
			}
		}
		else
		{
			$this->setError($mediaLib->getError());

			return false;
		}

		return $uploadRes[0];
	}

	/**
	 * Method to check media xref existence
	 *
	 * @param   INT     $clientId  clientId
	 *
	 * @param   STRING  $client    client
	 *
	 * @return	array
	 *
	 * @since   2.0
	 */
	public function checkMediaXrefExistence($clientId, $client)
	{
		$tjmediaXrefTable = Table::getInstance('Xref', 'TJMediaTable');

		$tjmediaXrefTable->load(array('client_id' => (int) $clientId, 'client' => $client));

		return $tjmediaXrefTable;
	}

	/**
	 * Method to save media xref
	 *
	 * @param   INT     $mediaId    mediaId
	 *
	 * @param   INT     $clientId   clientId
	 *
	 * @param   STRING  $client     client
	 *
	 * @param   INT     $isGallery  isGallery
	 *
	 * @return	object
	 *
	 * @since   2.0
	 */
	public function saveMediaXref($mediaId, $clientId, $client, $isGallery = 0)
	{
		$tjmediaXrefTable = $this->checkMediaXrefExistence($clientId, $client);

		if (!empty($tjmediaXrefTable->id))
		{
			// Delete existing xref
			$oldMediaId = $tjmediaXrefTable->media_id;

			$tjmediaXrefTable->delete();

			// Check if any other xref media not present then delete media file also
			$oldTjmediaXrefTable = Table::getInstance('Xref', 'TJMediaTable');
			$oldTjmediaXrefTable->load(array('media_id' => $oldMediaId));

			if (!$oldTjmediaXrefTable->id)
			{
				$storagePath = TJMediaStorageLocal::getInstance();

				$filetable = Table::getInstance('Files', 'TJMediaTable');

				// Load the object based on the id or throw a warning.
				$filetable->load($oldMediaId);

				$mediaConfig = array('id' => $oldMediaId, 'uploadPath' => $storagePath->mediaUploadPath);

				$mediaLib = TJMediaStorageLocal::getInstance($mediaConfig);

				if ($mediaLib->id)
				{
					if (!$mediaLib->delete())
					{
						$this->setError(Text::_($mediaLib->getError()));

						return false;
					}

					$dateTime = str_replace(array(' ', '-', ':'), '_', Factory::getDate());
					$logFileName = 'com_tjlms.image_delete.log';
					Log::addLogger(array('text_file' => $logFileName), Log::ALL, array('com_tjlms'));
					Log::add(Text::sprintf("COM_TJLMS_LESSON_MEDIA_DELETE_LOG_CSV_START", $dateTime), Log::INFO, 'com_tjlms');
					Log::add(Text::sprintf("COM_TJLMS_LESSON_MEDIA_DELETE_LOG_MEDIA_DELETE", $mediaLib->id, $clientId, Factory::getUser()->id), Log::INFO, 'com_tjlms');
				}
				else
				{
					$this->setError(Text::_($mediaLib->getError()));

					return false;
				}
			}
		}

		$mediaXref = array();
		$mediaXref['id'] = '';
		$mediaXref['media_id']   = $mediaId;
		$mediaXref['client_id']  = $clientId;
		$mediaXref['client']     = $client;
		$mediaXref['is_gallery'] = $isGallery;

		$mediaModelXref          = TJMediaXref::getInstance();
		$mediaModelXref->bind($mediaXref);
		$mediaModelXref->save();

		return true;
	}
}
