<?php

/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
jimport('joomla.filesystem.file');

/**
 * Methods supporting a list of Tjlms cloud storage cron.
 *
 * @since  1.0.0
 */
class TjCron
{
	private $message = array();

	/**
	 * Consturctor method of tjcron class.
	 */
	public function __construct ()
	{
		require_once JPATH_ROOT . '/components/com_tjlms/libraries/storage.php';
		$this->Tjstorage = new Tjstorage;

		// Load main contoller to call api sendReminders
		$path = JPATH_SITE . '/components/com_tjlms/controller.php';
		$this->TjlmsController = new TjlmsController;

		if (File::exists($path))
		{
			if (!class_exists('TjlmsController'))
			{
				JLoader::register('TjlmsController', $path);
				JLoader::load('TjlmsController');
			}

			$this->TjlmsController = new TjlmsController;
		}

		$this->tjlmsparams = ComponentHelper::getParams('com_tjlms');
		$this->comtjlmsHelper = new comtjlmsHelper;
	}

	/**
	 *  The main function called when cron is executed
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */

	public function execute()
	{
		/* complete process all tasks */
		$this->_processCourseImageStorage();
		$this->_processLessonImageStorage();
		$this->_processLessonStorage();
		$this->_processAssociatedfilesStorage();

		$jlikeparams         = ComponentHelper::getParams('com_jlike');
		$reminder_batch_size = $jlikeparams->get('reminder_batch_size', 10);

		if ($reminder_batch_size)
		{
			$reminder_count = $this->TjlmsController->sendReminders($reminder_batch_size);

			if ($reminder_count)
			{
				$this->_message[] = Text::sprintf('COM_TJLMS_REMINDERS_SENT_SUCCESSFULLY', $reminder_count);
			}
		}

		// Display cron messages if neessary
		header('Content-type: text/html');

		// Seperated to assist syntax highliter
		echo '<html>';
		echo '<body>';

		foreach ($this->_message as $msg)
		{
			echo '<div>';
			echo $msg;
			echo '</div>';
		}

		echo '</body>';
		echo '</html>';

		exit;
	}

	/**
	 *  Function is used to transfer the course images according to storage specified
	 *
	 * @param   INT  $updateNum  An optional LIMIT field.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function _processCourseImageStorage($updateNum = 5)
	{
		$db = Factory::getDbo();

		// Get the storage method for course images from options
		$storageMethod = $this->tjlmsparams->get('course_images_storage');

		// Get the object of the class of the storage method for course images from options
		// e.g if storage method is set to 's3' then this will return instance of S3_TjStorage class
		$storage = $this->Tjstorage->getStorage($storageMethod);

		$query = $db->getQuery(true);

		// Get the course whose image has to be transfered
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_courses'));
		$query->where($db->quoteName('storage') . ' != ' . $db->quote($storageMethod));
		$query->where($db->quoteName('storage') . " != 'invalid'");
		$query->where($db->quoteName('image') . " != ' ' ");
		$query->order('ordering ASC');

		if (JVERSION < '3.0')
		{
			$db->setQuery($query, 0, $updateNum);
		}
		else
		{
			$query->setLimit($updateNum);
			$db->setQuery($query);
		}

		$rows = $db->loadObjectList();

		if (empty($rows))
		{
			$this->_message[] = Text::_('COM_TJLMS_NO_COURSE_IMAGE_FOUND_TO_TRANSFER');

			return;
		}

		// Get the course image path from config
		$localCourseImgPath = $this->tjlmsparams->get('course_image_upload_path');
		$totalMoved = 0;
		$totalInvalid = 0;

		foreach ($rows as $row)
		{
			// Get the object of the class of the storage method for course images
			$current = $this->Tjstorage->getStorage($row->storage);

			$imagePathwithName = $localCourseImgPath . $row->image;

			if ($current->exists($imagePathwithName) )
			{
				// Move locally if file exists on remote storage OR vice a versa.
				if ($this->_moveToStorage($storageMethod, $current, $storage, $imagePathwithName, $row->id, '#__tjlms_courses') == true)
				{
					// Move course's resized images if present
					$largeimagePathwithName = $localCourseImgPath . 'L_' . $row->image;
					$this->_moveToStorage($storageMethod, $current, $storage, $largeimagePathwithName);

					$medimagePathwithName = $localCourseImgPath . 'M_' . $row->image;
					$this->_moveToStorage($storageMethod, $current, $storage, $medimagePathwithName);

					$smallimagePathwithName = $localCourseImgPath . 'S_' . $row->image;
					$this->_moveToStorage($storageMethod, $current, $storage, $smallimagePathwithName);

					$totalMoved++;
				}
			}
			else
			{
				if ($this->markInvalid($row->id, '#__tjlms_courses'))
				{
					$totalInvalid ++;
				}
			}
		}

		$this->_message[] = Text::sprintf('COM_TJLMS_NUMBER_OF_COURSE_IMAGES_TRANSFERED', $totalMoved);

		if ($totalInvalid)
		{
			$this->_message[] = Text::sprintf('COM_TJLMS_NUMBER_OF_COURSE_INVALID', $totalMoved);
		}
	}

	/**
	 *  Function is used to transfer the lesson images according to storage specified
	 *
	 * @param   INT  $updateNum  An optional LIMIT field.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */

	private function _processLessonImageStorage($updateNum = 5)
	{
		$db = Factory::getDbo();

		// Get the storage method for course images from options
		$storageMethod = $this->tjlmsparams->get('lesson_image_storage');

		// Get the object of the class of the storage method for course images from options
		// e.g if storage method is set to 's3' then this will return instance of S3_TjStorage class
		$storage = $this->Tjstorage->getStorage($storageMethod);

		$query = $db->getQuery(true);

		// Get the course whose image has to be transfered
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_lessons'));
		$query->where($db->quoteName('image') . " != ' '");
		$query->where($db->quoteName('storage') . " != 'invalid'");
		$query->where($db->quoteName('storage') . ' != ' . $db->quote($storageMethod));
		$query->order('ordering ASC');

		if (JVERSION < '3.0')
		{
			$db->setQuery($query, 0, $updateNum);
		}
		else
		{
			$query->setLimit($updateNum);
			$db->setQuery($query);
		}

		$rows = $db->loadObjectList();

		if (empty($rows))
		{
			$this->_message[] = Text::_('COM_TJLMS_NO_LESSON_IMAGE_FOUND_TO_TRANSFER');

			return;
		}

		// Get the course image path from config
		$local_lesson_img_path	  = $this->tjlmsparams->get('lesson_image_upload_path');

		$totalMoved = $totalInvalid = 0;

		foreach ($rows as $row)
		{
			// Get the object of the class of the storage method for course images
			$current = $this->Tjstorage->getStorage($row->storage);

			$imagePathwithName = $local_lesson_img_path . $row->image;

			if ($current->exists($imagePathwithName))
			{
				// Move locally if file exists on remote storage.
				if ($this->_moveToStorage($storageMethod, $current, $storage, $imagePathwithName, $row->id, '#__tjlms_lessons') == true)
				{
					// Move course's resized images if present
					$largeimagePathwithName = $local_lesson_img_path . 'L_' . $row->image;
					$this->_moveToStorage($storageMethod, $current, $storage, $largeimagePathwithName);

					$medimagePathwithName = $local_lesson_img_path . 'M_' . $row->image;
					$this->_moveToStorage($storageMethod, $current, $storage, $medimagePathwithName);

					$smallimagePathwithName = $local_lesson_img_path . 'S_' . $row->image;
					$this->_moveToStorage($storageMethod, $current, $storage, $smallimagePathwithName);

					$totalMoved++;
				}
			}
			else
			{
				if ($this->markInvalid($row->id, '#__tjlms_lessons'))
				{
					$totalInvalid ++;
				}
			}
		}

		$this->_message[] = Text::sprintf('COM_TJLMS_NUMBER_OF_LESSON_IMAGES_TRANSFERED', $totalMoved);

		if ($totalInvalid)
		{
			$this->_message[] = Text::sprintf('COM_TJLMS_NUMBER_OF_LESSON_IMAGES_INVALID', $totalInvalid);
		}
	}

	/**
	 * Function is used to transfer the all types of lessons according to storage specified
	 *
	 * @param   INT  $updateNum  An optional LIMIT field.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */

	private function _processLessonStorage($updateNum = 5)
	{
		// The path where lessons are stored locally
		$local_lesson_path  = 'media/com_tjlms/lessons';

		// Get the storage method of specified in condif for lesson format files
		$storageMethod = $this->tjlmsparams->get('lesson_files_stores');

		// Get the object of the class of the storage method for course images from options
		// E.g if storage method is set to 's3' then this will return instance of S3_TjStorage class
		$storage = $this->Tjstorage->getStorage($storageMethod);

		// Transfer the lesson formats which are not scorm
		$this->_processLessoMediaStorage($local_lesson_path, $storageMethod, $storage);

		// Transfer the lesson formats which scorm
		// $this->_processLessoScormStorage($local_lesson_path, $storageMethod, $storage);
	}

	/**
	 * Function is used to transfer format files of lesson which are stored in thlms_media table
	 *
	 * @param   VAR  $local_lesson_path  Path in which lessons are stored
	 *
	 * @param   VAR  $storageMethod      The storage method for lesson format files from config
	 *
	 * @param   VAR  $storage            The storage class according to method for lesson format files from config
	 *
	 * @param   INT  $updateNum          An optional LIMIT field.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function _processLessoMediaStorage($local_lesson_path, $storageMethod, $storage, $updateNum = 5)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Get the medias whose formats has to be transfered.. Exclude text and media and videos with URL
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_media'));

		$where = $db->quoteName('format') . " != 'textmedia' ";
		$where .= "AND " . $db->quoteName('format') . " !=  'associate' ";
		$where .= "AND (" . $db->quoteName('sub_format') . " = ' '  OR " . $db->quoteName('sub_format') . " LIKE '%upload%' )";

		$query->where($where);
		$query->where($db->quoteName('storage') . ' != ' . $db->quote($storageMethod));
		$query->where($db->quoteName('storage') . " != 'invalid'");

		$query->order('id ASC');

		if (JVERSION < '3.0')
		{
			$db->setQuery($query, 0, $updateNum);
		}
		else
		{
			$query->setLimit($updateNum);
			$db->setQuery($query);
		}

		$rows = $db->loadObjectList();

		if (empty($rows))
		{
			$this->_message[] = Text::sprintf('COM_TJLMS_NO_LESSON_FORMATS_FOUND_TO_TRANSFER');

			return;
		}

		$totalMoved = $totalInvalid = 0;

		foreach ($rows as $row)
		{
			// Get the object of the class of the storage method for media
			$current = $this->Tjstorage->getStorage($row->storage);

			$itemPathWithname = $local_lesson_path . '/' . $row->source;

			$newStorage = '';

			if ($current->exists($itemPathWithname))
			{
				// Move locally if file exists on remote storage.
				if ($this->_moveToStorage($storageMethod, $current, $storage, $itemPathWithname, $row->id, '#__tjlms_media') == true)
				{
					$newStorage = $storageMethod;
					$totalMoved++;
				}
			}
			else
			{
				if ($this->markInvalid($row->id, '#__tjlms_media'))
				{
					$newStorage = 'invalid';
					$totalInvalid ++;
				}
			}

			if ($row->format == 'scorm')
			{
				$this->_updateScormEntry($row->source, $newStorage);
			}
		}

		$this->_message[] = Text::sprintf('COM_TJLMS_NUMBER_OF_LESSON_FORMATS_TRANSFERED', $totalMoved);

		if ($totalInvalid)
		{
			$this->_message[] = Text::sprintf('COM_TJLMS_NUMBER_OF_LESSON_FORMATS_INVALID', $totalInvalid);
		}
	}

	/**
	 * Function is used to transfer format files of lesson which are stored in tjlms_scorm table
	 *
	 * @param   VAR  $local_lesson_path  Path in which lessons are stored
	 *
	 * @param   VAR  $storageMethod      The storage method for lesson format files from config
	 *
	 * @param   VAR  $storage            The storage class according to method for lesson format files from config
	 *
	 * @param   INT  $updateNum          An optional LIMIT field.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function _processLessoScormStorage($local_lesson_path, $storageMethod, $storage, $updateNum = 5)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Get the Scorm whose formats has to be transfered.
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_scorm'));
		$query->where($db->quoteName('lesson_id') . " != ' '");
		$query->where($db->quoteName('storage') . ' != ' . $db->quote($storageMethod));
		$query->where($db->quoteName('storage') . " != 'invalid'");
		$query->order('id ASC');

		if (JVERSION < '3.0')
		{
			$db->setQuery($query, 0, $updateNum);
		}
		else
		{
			$query->setLimit($updateNum);
			$db->setQuery($query);
		}

		$rows = $db->loadObjectList();

		if (empty($rows))
		{
			$this->_message[] = Text::sprintf('COM_TJLMS_NO_SCORM_FORMATS_FOUND_TO_TRANSFER');

			return;
		}

		$totalMoved = $totalInvalid = 0;

		foreach ($rows as $row)
		{
			// Get the object of the class of the storage method for media
			$current = $this->Tjstorage->getStorage($row->storage);

			$itemPathWithname = $local_lesson_path . '/' . $row->package;

			if ($current->exists($itemPathWithname))
			{
				// Move locally if file exists on remote storage.
				if ($this->_moveToStorage($storageMethod, $current, $storage, $itemPathWithname, $row->id, '#__tjlms_scorm') == true)
				{
					$totalMoved++;
				}
			}
			else
			{
				if ($this->markInvalid($row->id, '#__tjlms_scorm'))
				{
					$totalInvalid ++;
				}
			}
		}

		$this->_message[] = Text::sprintf('COM_TJLMS_NUMBER_OF_LESSON_SCORM_FORMATS_TRANSFERED', $totalMoved);

		if ($totalInvalid)
		{
			$this->_message[] = Text::sprintf('COM_TJLMS_NUMBER_OF_LESSON_SCORM_FORMATS_INVALID', $totalInvalid);
		}
	}

	/**
	 * Function is used to transfer the all associate of lessons according to storage specified
	 *
	 * @param   INT  $updateNum  An optional LIMIT field.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function _processAssociatedfilesStorage($updateNum = 5)
	{
		// Get the storage method for course images from options
		$storageMethod = $this->tjlmsparams->get('lesson_ass_files_storage');

		// Get the object of the class of the storage method for course images from options
		// e.g if storage method is set to 's3' then this will return instance of S3_TjStorage class
		$storage = $this->Tjstorage->getStorage($storageMethod);

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		/*get the course whose image has to be transfered*/
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_media'));
		$query->where($db->quoteName('format') . " = 'associate' ");
		$query->where($db->quoteName('storage') . ' != ' . $db->quote($storageMethod));
		$query->where($db->quoteName('storage') . " != 'invalid'");

		$query->order('id ASC');

		if (JVERSION < '3.0')
		{
			$db->setQuery($query, 0, $updateNum);
		}
		else
		{
			$query->setLimit($updateNum);
			$db->setQuery($query);
		}

		$rows = $db->loadObjectList();

		if (empty($rows))
		{
			$this->_message[] = Text::_('COM_TJLMS_NO_ASSOFILES_FOUND_TO_TRANSFER');

			return;
		}

		$local_file_path = 'media/com_tjlms/lessons';

		$totalMoved = $totalInvalid = 0;

		foreach ($rows as $row)
		{
			// Get the object of the class of the storage method for course images
			$current = $this->Tjstorage->getStorage($row->storage);

			$itemPathWithname = $local_file_path . '/' . $row->source;

			if ($current->exists($itemPathWithname))
			{
				// Move locally if file exists on remote storage.
				if ($this->_moveToStorage($storageMethod, $current, $storage, $itemPathWithname, $row->id, '#__tjlms_media') == true)
				{
					$totalMoved++;
				}
			}
			else
			{
				if ($this->markInvalid($row->id, '#__tjlms_media'))
				{
					$totalInvalid ++;
				}
			}
		}

		$this->_message[] = Text::sprintf('COM_TJLMS_ASSOFILES_TRANSFER_SUCCESS', $totalMoved);

		if ($totalInvalid)
		{
			$this->_message[] = Text::sprintf('COM_TJLMS_ASSOFILES_INVALID', $totalInvalid);
		}
	}

	/**
	 *  Function is used to transfer the file provided to the storage specified
	 *
	 * @param   STRING  $storageMethod  the storage method specified in config
	 *
	 * @param   STRING  $current        the value of the storage column
	 *
	 * @param   STRING  $storage        where we want to move file
	 *
	 * @param   STRING  $fileToMove     file to be moved
	 *
	 * @param   STRING  $rowId          row id of the table to be updated
	 *
	 * @param   STRING  $table          table to be updated
	 *
	 * @return   boolean
	 *
	 * @since   1.0.0
	 */

	private function _moveToStorage($storageMethod, $current, $storage, $fileToMove, $rowId='', $table='')
	{
		$app = Factory::getApplication();
		$db = Factory::getDbo();

		// Move locally if file exists on remote storage.
		$tmpImageFileName = $app->getCfg('tmp_path') . '/' . md5($fileToMove);

		$current->get($fileToMove, $tmpImageFileName);

		// Check again if prepare transfer files exists
		if (File::exists($tmpImageFileName))
		{
			if ($storage->put($fileToMove, $tmpImageFileName))
			{
				if ($rowId != '' && $rowId > 0)
				{
					// Update the storage column of the course
					$course_data = new stdClass;
					$course_data->id = $rowId;
					$course_data->storage = $storageMethod;
					$result = $db->updateObject($table, $course_data, 'id');
				}
				// Delete existing storage's Image.
				$current->delete($fileToMove);

				// Remove temporary generated Image.
				File::delete($tmpImageFileName);

				return true;
			}
		}

		return false;
	}

	/**
	 *  Function is used to mark the secific entry as invalid if its not exists on current storage
	 *
	 * @param   STRING  $rowId  row id of the table to be updated
	 *
	 * @param   STRING  $table  table to be updated
	 *
	 * @return   boolean
	 *
	 * @since   1.0.0
	 */
	private function markInvalid($rowId='', $table='')
	{
		$result = '';
		$db = Factory::getDbo();

		if ($rowId != '' && $rowId > 0)
		{
			// Update the storage column of the course
			$course_data = new stdClass;
			$course_data->id = $rowId;
			$course_data->storage = 'invalid';
			$result = $db->updateObject($table, $course_data, 'id');
		}

		return $result;
	}

	/**
	 *  Function is used to update scrom table if entry is updated in media table
	 *
	 * @param   STRING  $package     Package that is already move by Media table
	 * @param   STRING  $newStorage  New storage where media is moved
	 *
	 * @return   boolean
	 *
	 * @since   1.0.0
	 */
	private function _updateScormEntry($package, $newStorage)
	{
		$result = false;
		$db = Factory::getDbo();

		if (!empty($package) && !empty($newStorage))
		{
			// Update the storage column of the course
			$scorm_data = new stdClass;
			$scorm_data->package = $package;
			$scorm_data->storage = $newStorage;
			$result = $db->updateObject('#__tjlms_scorm', $scorm_data, 'package');
		}

		return $result;
	}
}
