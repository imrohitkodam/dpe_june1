<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Filesystem\File;
jimport('joomla.application.component.model');
jimport('techjoomla.common');

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/xref", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/tables/files", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/tables/xref", JPATH_LIBRARIES);

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Methods for lesson.
 *
 * @since  1.0.0
 */
class TjlmsModellesson extends BaseDatabaseModel
{
	protected $canAssess = 0;

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
		parent::__construct();
		$this->tjlmsdbhelper          = new tjlmsdbhelper;
		$this->comtjlmsHelper         = new comtjlmsHelper;
		$this->tjlmsLessonHelper      = new TjlmsLessonHelper;
		$this->techjoomlacommon       = new TechjoomlaCommon;
		$this->comtjlmstrackingHelper = new comtjlmstrackingHelper;
	}

	/**
	 * Get data for lesson.
	 *
	 * @param   INT  $lesson_id  of the lesson
	 *
	 * @return  object of   lesson
	 *
	 * @since 1.0.0
	 */
	public function getlessondata($lesson_id)
	{
		$oluser_id = Factory::getUser()->id;
		$lesson_data = $this->tjlmsLessonHelper->getLesson($lesson_id);

		if (!empty ($lesson_data->eligibility_criteria))
		{
			$lesson_data->eligibilty_lessons = $this->tjlmsLessonHelper->getLessonsName($lesson_data->eligibility_criteria);
		}

		if (!empty($lesson_data->start_date) || !empty($lesson_data->end_date))
		{
			$lesson_data->start_date = $this->techjoomlacommon->getDateInLocal($lesson_data->start_date);
			$lesson_data->end_date   = $this->techjoomlacommon->getDateInLocal($lesson_data->end_date);
		}

		return $lesson_data;
	}

	/**
	 * Get format data for lesson. For scorm lesson data is stored in #__tjlms_scorm table.
	 * For Video, ppt, associated file data is stored in #__tjlms_media table
	 *
	 * @param   INT  $lesson_id  id of the lesson
	 *
	 * @param   VAR  $format     format of the lesson
	 *
	 * @return  object from related tables
	 *
	 * @since 1.0.0
	 */
	public function getlesson_typedata($lesson_id, $format)
	{
		$db	= Factory::getDBO();
		require_once JPATH_ROOT . '/components/com_tjlms/libraries/storage.php';
		$tjStorage = new Tjstorage;

		// Get data for Video, ppt, document
		$media_id = $this->tjlmsdbhelper->get_records('media_id', 'tjlms_lessons', array('id' => $lesson_id), '', 'loadResult');
		$lessontype_data = $this->tjlmsdbhelper->get_records('*', 'tjlms_media', array('id' => $media_id), '', 'loadObject');

		// Get the proper path(according to storage) for the video OR ppt if subformt is upload
		if (!empty($lessontype_data->sub_format))
		{
			$sub_format = explode(".", $lessontype_data->sub_format);

			$lessontype_data->pluginToTrigger = $sub_format[0];
			$lessontype_data->sourcefilename = $lessontype_data->source;

			if ($sub_format[1] == 'upload')
			{
				if ($lessontype_data->storage != 'invalid')
				{
					$storage = $tjStorage->getStorage($lessontype_data->storage);
					$lessontype_data->source = $storage->getURI('media/com_tjlms/lessons/' . $lessontype_data->source);
				}
			}

			PluginHelper::importPlugin('tj' . $format, $lessontype_data->pluginToTrigger);
			$additionalData = Factory::getApplication()->triggerEvent('onGetAdditional' . $lessontype_data->pluginToTrigger . 'Data', array($lesson_id, $lessontype_data));

			if (!empty($additionalData))
			{
				$res = $additionalData[0];
				$lessontype_data->$format = $res;
			}
		}

		// If associate files are allowed for lesson, take them
		$params = ComponentHelper::getParams('com_tjlms');
		$allowAssocFiles = $params->get('allow_associate_files', '0', 'INT');

		if ($allowAssocFiles == 1)
		{
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('af.media_id', 'f.org_filename', 'f.source'), array('media_id', 'filename', 'source')));
			$query->select($db->quoteName('f.storage'));
			$query->from($db->quoteName('#__tjlms_media', 'f'));
			$query->join('INNER', $db->quoteName('#__tjlms_associated_files', 'af')
						. ' ON (' . $db->quoteName('af.media_id') . ' = ' . $db->quoteName('f.id') . ')');
			$query->where($db->quoteName('af.lesson_id') . "=" . $lesson_id);
			$query->where($db->quoteName('f.format') . " = 'associate'");
			$query->where($db->quoteName('f.format') . " != 'invalid'");

			$db->setQuery($query);
			$assolist = $db->loadObjectList();

			if (!empty ($assolist))
			{
				JLoader::import('components.com_tjlms.libraries.storage', JPATH_SITE);
				$tjStorage = new Tjstorage;

				foreach ($assolist as $key => $assocFile)
				{
					if ($assocFile->storage == 'invalid')
					{
						unset($assolist[$key]);

						continue;
					}

					$storage 	= $tjStorage->getStorage($assocFile->storage);
					$fileExists = $storage->exists('media/com_tjlms/lessons/' . $assocFile->source);

					if (!$fileExists)
					{
						unset($assolist[$key]);
					}
				}

				$lessontype_data->associateFiles = $assolist;
			}
		}

		return $lessontype_data;
	}

	/**
	 * Get tracking for documents. Mainly he current page
	 *
	 * @param   OBJECT  $lesson   lesson Object
	 *
	 * @param   INT     $user_id  id of the user
	 *
	 * @param   INT     $attempt  the attempt number for which tracking data is asked
	 *
	 * @return  object form #__tjlms_lesson_track
	 *
	 * @since 1.0.0
	 */
	public function gettrackingData($lesson, $user_id, $attempt)
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('t.*');
		$query->from('`#__tjlms_lesson_track` as t');
		$query->where('t.lesson_id=' . $lesson->id . ' AND t.user_id=' . $user_id . ' AND t.attempt=' . $attempt);
		$db->setQuery($query);
		$trackingData = $db->loadObject();

		$quizTypeArray = array("quiz", "exercise", "feedback");

		if (!empty($trackingData))
		{
			$trackingData->currentPositionFormat = $trackingData->current_position;
			$trackingData->totalContentFormat = $trackingData->total_content;

			if ($lesson->format != 'document' && !in_array($lesson->format, $quizTypeArray))
			{
				$trackingData->currentPositionFormat = $this->comtjlmsHelper->secToHours($trackingData->current_position);
				$trackingData->totalContentFormat = $this->comtjlmsHelper->secToHours($trackingData->total_content);
			}

			if (in_array($lesson->format, $quizTypeArray) && !$trackingData->current_position)
			{
				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tmt/models');
				$model = BaseDatabaseModel::getInstance('Test', 'TmtModel', array('ignore_request' => true));

				$testId                 = $model->getTestIdFromLessonTrack($trackingData->id);
				$userTraversedQuestions = $model->getTraversedQuestions($testId, $user_id, $trackingData->id, 1);
				$testPageData           = $model->getTestPageQuestions($testId);

				$questionsPerPage = $testPageData['questionsPerPage'];

				$temp = 0;
				$cp = 1;

				foreach ($questionsPerPage as $ind => $qc)
				{
					$temp += $qc;

					if ($userTraversedQuestions < $temp)
					{
						$cp = $ind;

						break;
					}
				}

				$lessonTrackTable = $this->getTable('lessonTrack', 'TjlmsTable');
				$lessonTrackTable->load($trackingData->id);

				$lessonTrackTable->current_position = $cp;
				$lessonTrackTable->total_content = count($questionsPerPage);
				$lessonTrackTable->store();

				$trackingData->current_position = $cp;
				$trackingData->total_content = count($questionsPerPage);
			}
		}

		return $trackingData;
	}

	/**
	 * Function to update media_id of lesson
	 *
	 * @param   INT     $id         primary key of the media table
	 * @param   INT     $lesson_id  id of the lesson
	 * @param   STRING  $format     format of the lesson eg. video
	 *
	 * @return BOOLEAN true
	 *
	 * @since 1.0.0
	 */
	public function saveMediaForlesson($id, $lesson_id, $format)
	{
		$db            = Factory::getDBO();
		$obj           = new stdclass;
		$obj->id       = $lesson_id;
		$obj->format   = $format;
		$obj->media_id = $id;
		$db->updateObject('#__tjlms_lessons', $obj, 'id');

		return 1;
	}

	/**
	 * This function remove unused lesson files from com_tjlms media folder
	 *
	 * @return message
	 *
	 * @since  1.1
	 *
	 */
	public function removeUnusedLessonFiles()
	{
		jimport('joomla.log.log');
		Log::addLogger(array('text_file' => 'com_tjlms.orphaned_lesson_deleted_file.php'), Log::ALL, array('com_tjlms'));

		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('m.org_filename','m.source','m.storage')));
		$query->from($db->quoteName('#__tjlms_media') . ' as m');
		$query->join('LEFT', $db->quoteName('#__tjlms_lessons') . ' as l ON l.media_id = m.id');
		$query->where($db->quoteName('m.format') . " != 'associate'");
		$query->where('l.media_id IS NULL');
		$db->setQuery($query);
		$lessonFiles = $db->loadObjectList();

		if (!empty($lessonFiles))
		{
			require_once JPATH_ROOT . '/components/com_tjlms/libraries/storage.php';
			$Tjstorage = new Tjstorage;

			foreach ($lessonFiles as $lessonFile)
			{
				if ($lessonFile->storage != 'invalid')
				{
					$storageId = 'media/com_tjlms/lessons/' . $lessonFile->source;
					$storage = $Tjstorage->getStorage($lessonFile->storage);

					if ($storage->delete($storageId))
					{
						// Create a log for deleted file
						Log::add(Text::sprintf('COM_TJLMS_DELETED_LESSONS_FILE', $storageId, $lessonFile->org_filename), Log::WARNING, 'com_tjlms');
					}
				}
			}
		}
	}

	/**
	 * This function is used to check if given user can access the media provided
	 *
	 * @param   INT  $mediaId   primary key of the media table
	 * @param   INT  $otpToken  if the media is tried accessing using google or microsoft url
	 * @param   INT  $userId    user id
	 *
	 * @return  array
	 *
	 * @since  1.1
	 *
	 */
	public function checkifUsercanAccessMedia($mediaId, $otpToken = null, $userId='')
	{
		$app           = Factory::getApplication();
		$return = $lessons = array();

		if (!$userId)
		{
			$userId = Factory::getUser()->id;

			if (!$userId)
			{
				$return['access'] = 0;
				$return['msg']    = Text::_('COM_TJLMS_MESSAGE_LOGIN_FIRST');

				return $return;
			}
		}

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('m.id', 'm.format', 'm.storage', 'm.sub_format','m.source',  'm.created_by', 'm.org_filename')));
		$query->from($db->quoteName('#__tjlms_media') . ' as m');
		$query->where($db->quoteName('m.id') . ' = ' . $mediaId);

		$db->setQuery($query);
		$mediaObj = $db->loadObject();

		$uploadFolderreq = array("mediaformat" => $mediaObj->format, "subformat" => $mediaObj->sub_format);

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$mediaModel = BaseDatabaseModel::getInstance('Media', 'TjlmsModel', array('ignore_request' => true));

		$uploadedFolder = $mediaModel->getuploadFolder($uploadFolderreq);

		if ($otpToken)
		{
			$return = array('access' => 1, 'mediaPath' => $uploadedFolder . $mediaObj->source, 'external' => 0,
			'org_filename' => $mediaObj->org_filename);
		}
		elseif ($mediaObj->id && $mediaObj->format)
		{
			switch ($mediaObj->format)
			{
				case "associate":
					$query = $db->getQuery(true);

					$fieldList    = $db->quoteName(array('af.lesson_id'));
					$fieldList[0] = 'distinct ' . $fieldList[0];

					$query->select($fieldList);
					$query->from($db->quoteName('#__tjlms_associated_files') . ' as af');
					$query->join('INNER', $db->quoteName('#__tjlms_lessons') . ' as l ON l.id = af.lesson_id');
					$query->where($db->quoteName('af.media_id') . "=" . $db->quote($mediaId));
					$db->setQuery($query);
					$lessons = $db->loadColumn();

				break;
				case "quiz":
						$return = array('access' => 0, 'mediaPath' => $uploadedFolder . $mediaObj->source, 'external' => 0,
						'org_filename' => $mediaObj->org_filename);

						if ($mediaObj->sub_format == "answer.upload")
						{
							$query  = $db->getQuery(true);
							$search = '[%"' . $db->escape($mediaId, true) . '"%]';

							$query->select('*');
							$query->from($db->quoteName('#__tmt_tests_answers', 'ta'));
							$query->where($db->quoteName('ta.answer') . ' LIKE ' . $db->quote($search, false));

							$db->setQuery($query);
							$testAnswer = $db->loadObject();

							if (!empty($testAnswer->test_id))
							{
								if ($testAnswer->user_id == $userId && $mediaObj->created_by == $userId)  // Check for creator
								{
									$return['access'] = 1;
								}
								else   // Check user have assessor access or not
								{
									$query = $db->getQuery(true);
									$query->select($db->quoteName(array('m.id','m.format')));
									$query->from($db->quoteName('#__tjlms_media', 'm'));
									$query->where($db->quoteName('m.source') . ' = ' . $db->quote($testAnswer->test_id));

									$db->setQuery($query);
									$mediaTestObj = $db->loadObject();

									if (!empty($mediaObj))
									{
										$query = $db->getQuery(true);
										$query->select($db->quoteName('course_id'));
										$query->from($db->quoteName('#__tjlms_lessons', 'l'));
										$query->where($db->quoteName('l.media_id') . ' = ' . $db->quote($mediaTestObj->id));
										$query->where($db->quoteName('l.format') . ' = ' . $db->quote($mediaTestObj->format));

										$db->setQuery($query);
										$lessonObj = $db->loadObject();

										if (!empty($lessonObj))
										{
											JLoader::register('TjlmsHelper', JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php');
											$this->canAssess = TjlmsHelper::canDoAssessment($lessonObj->course_id, $userId);

											if ($this->canAssess)
											{
												$return['access'] = 1;
											}
										}
									}
								}
							}
						}
				break;
				default:
					$query = $db->getQuery(true);

					$fieldList = $db->quoteName(array('l.id'), array('lesson_id'));
					$fieldList[0] = 'distinct ' . $fieldList[0];

					$query->select($fieldList);
					$query->from($db->quoteName('#__tjlms_lessons') . ' as l');
					$query->where($db->quoteName('l.media_id') . "=" . $db->quote($mediaId));
					$db->setQuery($query);
					$lessons = $db->loadColumn();
			}
		}

		if (empty($return))
		{
			$return = array('access' => 0, 'mediaPath' => $uploadedFolder . $mediaObj->source, 'external' => 0,
			'org_filename' => $mediaObj->org_filename);
		}

		if (!empty($lessons))
		{
			require_once JPATH_ROOT . '/components/com_tjlms/libraries/storage.php';
			$tjStorage = new Tjstorage;

			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$tjlmsLessonHelper = new TjlmsLessonHelper;

			$canAccess = 0;

			foreach ($lessons as $lessonId)
			{
				$lesson = Table::getInstance('Lesson', 'TjlmsTable', array('dbo', $db));
				$lesson->load($lessonId);

				$course = Table::getInstance('Course', 'TjlmsTable', array('dbo', $db));
				$course->load($lesson->course_id);

				// If user is creator he should be able to download the media
				if ($mediaObj->created_by == $userId || $lesson->created_by == $userId || $course->created_by == $userId)
				{
					$return['access'] = 1;
					break;
				}
				else
				{
					// Else check if user has access to the lesson
					$checkUserCanAccess = $tjlmsLessonHelper->usercanAccess($lesson, $course, $userId);

					if ($checkUserCanAccess['access'] == 1)
					{
						$return['access'] = 1;
						break;
					}
					else
					{
						$return['access'] = 0;
						$return['msg']    = $checkUserCanAccess['msg'];
					}
				}
			}

			if ($return['access'] == 1 && $mediaObj->storage == 's3')
			{
				$storage                = $tjStorage->getStorage($mediaObj->storage);
				$return['mediaPath']    = $storage->getURI($uploadedFolder . $mediaObj->source);
				$return['external']     = 1;
				$return['org_filename'] = $mediaObj->org_filename;
			}
		}

		return $return;
	}

	/**
	 * download the file
	 *
	 * @param   array  $mediaData  $file file path eg /var/www/j30/media/com_quick2cart/qtc_pack.zip, $orgFilename, $extern, $exitHere
	 *
	 * @return  html
	 */
	public function downloadMedia($mediaData)
	{
		$app = Factory::getApplication();

		clearstatcache();

		$file = $mediaData['mediaPath'];
		$orgFilename = $mediaData['org_filename'];
		$extern = $mediaData['external'];
		$exitHere = $mediaData['exitHere'];

		// Exists file - if not error
		if (!$extern)
		{
			// Add JPATH_SITE if url is not absolute url
			$file = JPATH_SITE . $file;

			if (!File::exists($file))
			{
				$this->setError(Text::_("COM_TJLMS_MEDIA_FILE_NOT_FOUND"));

				return false;
			}
			else
			{
				$len = filesize($file);
			}
		}
		else
		{
			$len = $this->urlfilesize($file);
		}

		try
		{
			$filename       = basename($file);

			$file_extension = strtolower(substr(strrchr($filename, "."), 1));

			// Check if we can use "mime_content_type($filename)" instead of our own function
			$ctype = mime_content_type($file);

			ob_end_clean();

			//  Needed for MS IE - otherwise content disposition is not used?
			if (ini_get('zlib.output_compression'))
			{
				ini_set('zlib.output_compression', 'Off');
			}

			// Things to be done on after file download.
			$this->onAfterAssociateDownload($mediaData);

			header("Cache-Control: public, must-revalidate");
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header("Expires: 0");
			header("Content-Description: File Transfer");
			header("Content-Type: " . $ctype);
			header("Content-Length: " . (string) $len);
			header('Content-Disposition: attachment; filename="' . $orgFilename . '"');

			//  set_time_limit doesn't work in safe mode
			if (!ini_get('safe_mode'))
			{
				@set_time_limit(0);
			}

			if (@readfile($file) === false)
			{
				return false;
			}

			if ($exitHere == 1)
			{
				exit;
			}
		}
		catch (RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return true;
	}

	/**
	 * Function to get the filesize of remote file
	 *
	 * @param   STRING  $url  URL of the file
	 *
	 * @return  filesize
	 */
	public function urlfilesize($url)
	{
		if (substr($url, 0, 4) == 'http' || substr($url, 0, 3) == 'ftp')
		{
			$size = array_change_key_case(get_headers($url, 1), CASE_LOWER);
			$size = $size['content-length'];

			if (is_array($size))
			{
				$size = $size[1];
			}
		}
		else
		{
			$size = @filesize($url);
		}

		$a = array("B", "KB", "MB", "GB", "TB", "PB");
		$pos = 0;

		while ($size >= 1024)
		{
			$size /= 1024;
			$pos++;
		}

		return round($size, 2) . " " . $a[$pos];
	}

	/**
	 * Check if the lesson which is being launched in published or started
	 *
	 * @param   ARRAY   $lesson   Lesson object
	 * @param   string  &$result  array of access and message
	 *
	 * @return  void
	 *
	 * @since 1.3.8
	 * */
	private function launchValidateDate($lesson, &$result)
	{
		$currentDate = Factory::getDate()->toSql();
		$lmsparams   = ComponentHelper::getParams('com_tjlms');
		$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		// Check if the lesson has not published or started
		if ($lesson->state != 1 || (strtotime($lesson->start_date) > strtotime($currentDate) && $lesson->start_date != Factory::getDbo()->getNullDate()))
		{
			$result['msg'] = Text::sprintf('COM_TJLMS_LESSONS_NOT_YET_STARTED',
					$this->techjoomlacommon->getDateInLocal($lesson->start_date, 0, $date_format_show)
				);
			$result['access'] = 0;
		}

		// Check if the lesson has expired
		if ((strtotime($lesson->end_date) < strtotime($currentDate)) && $lesson->end_date != Factory::getDbo()->getNullDate())
		{
			$result['msg'] = Text::_('COM_TJLMS_LESSON_EXPIRED');
			$result['access'] = 0;
		}
	}

	/**
	 * Check if the attempts of the lesson being lauched are exhausted
	 *
	 * @param   Object  $lesson        Lesson object
	 * @param   Object  $lastAttempt   Object
	 * @param   string  &$result       array of access and message
	 * @param   INT     $fromTestView  Additional check to let lauched from test
	 *
	 * @return  void
	 *
	 * @since 1.3.8
	 * */
	private function launchValidateAttempts($lesson, $lastAttempt, &$result, $fromTestView = 0)
	{
		if ($lastAttempt->lesson_status == 'AP')
		{
			$result['access'] = 0;
			$result['msg'] = Text::_('COM_TJLMS_LESSON_LAUNCH_IN_REVIEW');
		}

		// Attempts Exhausted
		if ($lesson->no_of_attempts > 0)
		{
			$completeStatus = array("completed", "passed", "failed");

			if ($lastAttempt->attempt >= $lesson->no_of_attempts && (in_array($lastAttempt->lesson_status, $completeStatus) || !$lesson->resume)
				&& !$fromTestView)
			{
				// Check if resume to lesson is allowed or not. If resume is not allowed, treat last attempt as completed
				$result['access'] = 0;

				if ($lastAttempt->lesson_status == "failed")
				{
					$result['msg'] = Text::_('COM_TJLMS_ATTEMPTS_EXHAUSTED_TOOLTIP');
				}
			}
		}
	}

	/**
	 * Used to check if tany of the courses of the lesson is being launched are published
	 *
	 * @param   ARRAY  $lesson   Lesson object
	 * @param   ARRAY  $user     User object
	 * @param   INT    &$result  The result is passed by reference
	 *
	 * @return  array of access and message
	 *
	 * @since 1.3.8
	 * */
	private function launchValidateCourseACL($lesson, $user, &$result)
	{
		JLoader::import('components.com_tjlms.models.course', JPATH_SITE);

		$courseModel = BaseDatabaseModel::getInstance('course', 'TjlmsModel', array('ignore_request' => true));

		$access = 0;

		foreach ($this->lessonMappedCourses as $courseId)
		{
			$course   = $courseModel->getItem($courseId);

			$categories = Categories::getInstance('Tjlms');
			$category   = $categories->get($course->catid);
			$canEnroll  = $user->authorise('core.enroll', 'com_tjlms.course.' . $courseId);

			// Check if lesson, course, Category is published.
			if (!$category->id || $category->published != 1)
			{
				$invalidCourses[] = $category->title;
			}
			elseif (!$course->state)
			{
				$invalidCourses[] = $course->title;
			}
			elseif (!$canEnroll && $course->type != 1)
			{
				$invalidCourses[] = $course->title;
			}
			else
			{
				$access = 1;
				break;
			}
		}

		$result['access'] = $access;

		if ($access == 0 && !empty($invalidCourses))
		{
			$result['msg'] = Text::_("COM_TJLMS_LESSON_LAUNCH_COURSEACL_ACCESS");
		}
	}

	/**
	 * Used to check if the user is enrolled to any of the courses of the lesson is being launched
	 *
	 * @param   ARRAY  $lesson   Lesson object
	 * @param   ARRAY  $user     User object
	 * @param   INT    &$result  The result is passed by reference
	 *
	 * @return  array of access and message
	 *
	 * @since 1.3.8
	 * */
	private function launchValidateEnrollment($lesson, $user, &$result)
	{
		JLoader::import('components.com_tjlms.models.course', JPATH_SITE);

		$params       = ComponentHelper::getParams('com_tjlms');
		$allowCreator = $params->get('allow_creator');
		$autoEnroll   = $params->get('auto_enroll');

		$invalidCourses = array();
		$access = 0;

		foreach ($this->lessonMappedCourses as $courseId)
		{
			$course  = TjLms::course($courseId);

			$enrolled = 0;

			if ($course->id && $user->id)
			{
				$enrolDetails = TjLms::Enrollment($user->id, $courseId);

				if ($enrolDetails->id && $enrolDetails->state)
				{
					$enrolled = 1;
				}

				if ($course->type == 1 && $enrolDetails->expired == 1)
				{
					$enrolled = 0;
				}
			}

			$lessonFormat = array('quiz', 'exercise', 'feedback');

			if (!$enrolled && ((!$user->id && in_array($lesson->format, $lessonFormat)) || ($course->type == 1
				&& $lesson->free_lesson == 0) || ($autoEnroll == 0 && $course->type == 0)))
			{
				$invalidCourses[] = $course->title;
			}
			else
			{
				$access = 1;
				break;
			}

			if ($allowCreator && $user->id == $course->created_by)
			{
				$access = 1;
			}
		}

		$result['access'] = $access;

		if ($access == 0 && !empty($invalidCourses))
		{
			$result['msg'] = Text::_("COM_TJLMS_LESSON_LAUNCH_ENROLL_ACCESS", implode(",", $invalidCourses));
		}
	}

	/**
	 * Used to check if the user has meet the any of the prerequisites of the lesson is being launched
	 *
	 * @param   ARRAY  $lesson   Lesson object
	 * @param   ARRAY  $user     User object
	 * @param   INT    &$result  the result is passed by reference
	 *
	 * @return  array of access and message
	 *
	 * @since 1.3.8
	 * */
	private function launchValidatePrerequisites($lesson, $user, &$result)
	{
		$eligibility_str = $lesson->eligibility_criteria;

		if (!empty($eligibility_str))
		{
			$prerequisites = explode(',', $eligibility_str);
			$prerequisites = array_filter($prerequisites);
			$completed_prerequisites = 0;

			foreach ($prerequisites as $index => $lId)
			{
				$eligibal_lesson = Table::getInstance('Lesson', 'TjlmsTable');
				$eligibal_lesson->load($lId);

				if (!$eligibal_lesson->id || $eligibal_lesson->state != 1)
				{
					unset($prerequisites[$index]);
				}

				$eligibility = $this->comtjlmstrackingHelper->getLessonattemptsGrading($eligibal_lesson, $user->id);

				if ($eligibility)
				{
					if ($eligibility->lesson_status == 'completed' || $eligibility->lesson_status == 'passed')
					{
						unset($prerequisites[$index]);
					}
				}
			}

			if (!empty($prerequisites))
			{
				$lessonObj          = Tjlms::lesson();
				$eligibilty_lessons = $lessonObj->getLessonTitles($prerequisites);
				$eligibilty_lessons = implode(',', $eligibilty_lessons);
				$result['access']   = 0;
				$result['msg']      = Text::sprintf('COM_TJLMS_NOT_COMPLETED_PREREQUISITES_TOOLTIP', Text::_("COM_TJLMS_TYPE_LESSON"), $eligibilty_lessons);
			}
		}
	}

	/**
	 * Used to update the tracking
	 *
	 * @param   INT  $lessonId      Lesson id
	 * @param   INT  $userId        Course id
	 * @param   INT  $fromTestView  Additional check to let lauched from test
	 *
	 * @return  true
	 *
	 * @since 1.0.0
	 * */
	public function canUserLaunch($lessonId, $userId, $fromTestView = 0)
	{
		$db = Factory::getDBO();
		$params = ComponentHelper::getParams('com_tjlms');

		if (!$userId)
		{
			$userId = Factory::getUser()->id;
		}

		$user = Factory::getUser($userId);

		$result['msg'] = '';
		$result['access'] = 1;
		$result['track'] = 1;

		// Load the lesson
		JLoader::import('lesson', JPATH_ADMINISTRATOR . '/components/com_tjlms/includes/');
		$lesson = new TjLmsLesson;
		$lesson->load($lessonId);
		$this->lessonMappedCourses = array($lesson->course_id);

		// Load the lessontrack for the last attempt
		$lessontrack = Tjlms::lessontrack($lessonId, $userId);

		// Custom Validations
		PluginHelper::importPlugin('tjlms');
		$response  = Factory::getApplication()->triggerEvent('onBeforeLessonLaunch', array($lesson, $this->lessonMappedCourses, $lessontrack));

		foreach ($response as $res)
		{
			if ($res['access'] == 0)
			{
				$result['access'] = 0;
				$result['msg'] = $res['msg'];

				return $result;
			}
		}

		$this->launchValidateDate($lesson, $result);

		if ($result['access'] == 0)
		{
			return $result;
		}

		if ($lessontrack->id)
		{
			$this->launchValidateAttempts($lesson, $lessontrack, $result, $fromTestView);

			if ($result['access'] == 0)
			{
				return $result;
			}
		}

		// If lesson is part of course check the course validation
		if ($lesson->in_lib == 1)
		{
			return $result;
		}
		elseif ($lesson->course_id)
		{
			$this->launchValidateCourseACL($lesson, $user, $result);

			if ($result['access'] == 0)
			{
				return $result;
			}

			PluginHelper::importPlugin('tjlms');
			$dispatcherResult = Factory::getApplication()->triggerEvent('onBeforeCourseEnrol', array($lesson->course_id, $userId));

			if (in_array(false, $dispatcherResult, true))
			{
				$result['access'] = 0;
				$result['msg']    = Text::_("COM_TJLMS_VIEW_COURSE_PREREQUISITE_RESTRICT_MESSAGE");

				return $result;
			}

			$this->launchValidateEnrollment($lesson, $user, $result);

			if ($result['access'] == 0)
			{
				return $result;
			}

			$this->launchValidatePrerequisites($lesson, $user, $result);

			if ($result['access'] == 0)
			{
				return $result;
			}
		}
		else
		{
			$result['access'] = 0;
			$result['msg'] = Text::_("COM_TJLMS_NOT_IN_LIBRARY_NOT_IN_COURSE");
		}

		return $result;
	}

	/**
	 * Used to update the tracking
	 *
	 * @param   object  $course  Course object
	 * @param   object  $lesson  Lesson object
	 * @param   int     $userId  User id
	 *
	 * @return  array
	 *
	 * @since 1.0.0
	 * */
	public function canUserLaunchFromCourse($course, $lesson, $userId)
	{
		$db = Factory::getDBO();
		$params = ComponentHelper::getParams('com_tjlms');

		if (!$userId)
		{
			$userId = Factory::getUser()->id;
		}

		$user = Factory::getUser($userId);

		$result['msg'] = '';
		$result['access'] = 1;
		$result['track'] = 1;

		$lessonId = $lesson->id;
		$this->lessonMappedCourses = array($course->id);

		// Load the lessontrack for the last attempt
		$lessontrack = Tjlms::lessontrack($lessonId, $userId);

		// Load the coursetrack for course status
		$coursetrack = Tjlms::coursetrack($course->id, $userId);

		// Custom Validations
		PluginHelper::importPlugin('tjlms');
		$response  = Factory::getApplication()->triggerEvent('onBeforeLessonLaunch', array($lesson, $this->lessonMappedCourses, $lessontrack));

		foreach ($response as $res)
		{
			if ($res['access'] == 0)
			{
				$result['access'] = 0;
				$result['msg'] = $res['msg'];

				return $result;
			}
		}

		$this->launchValidateDate($lesson, $result);

		if ($result['access'] == 0)
		{
			return $result;
		}

		if ($lessontrack->id)
		{
			$this->launchValidateAttempts($lesson, $lessontrack, $result);

			if ($result['access'] == 0)
			{
				return $result;
			}
		}

		// If lesson is part of course check the course validation
		if ($course->id)
		{
			$this->launchValidateCourseACL($lesson, $user, $result);

			if ($result['access'] == 0)
			{
				return $result;
			}

			PluginHelper::importPlugin('tjlms');
			$dispatcherResult = Factory::getApplication()->triggerEvent('onBeforeCourseEnrol', array($course->id, $userId));

			if (in_array(false, $dispatcherResult, true))
			{
				$result['access'] = 0;
				$result['msg']    = Text::_("COM_TJLMS_VIEW_COURSE_PREREQUISITE_RESTRICT_MESSAGE");

				return $result;
			}

			$this->launchValidateEnrollment($lesson, $user, $result);

			if ($result['access'] == 0)
			{
				return $result;
			}

			$this->launchValidatePrerequisites($lesson, $user, $result);

			if ($result['access'] == 0)
			{
				return $result;
			}

			$ComtjlmsHelper    = new comtjlmsHelper;

			$options                  = array();
			$options['IdOnly']        = 1;
			$options['state']         = 0;
			$options['getResultType'] = 'loadAssocList';
			$options['user_id']       = $userId;
			$enrolledUsers = $ComtjlmsHelper->getCourseEnrolledUsers($lesson->course_id, $options);

			if ($enrolledUsers)
			{
				$result['access'] = 0;
				$result['msg']    = Text::_("TJLMS_APPROVAL_REMAINING");

				return $result;
			}

			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjlms/models', 'TjlmsModel');

			$model           = BaseDatabaseModel::getInstance('course', 'TjlmsModel', array('ignore_request' => true));
			$certificateData = $model->checkCertificateIssued($course->id, $user->id);

			if (isset($certificateData[0]->id) && $coursetrack->status === "C")
			{
				JLoader::import('components.com_tjcertificate.includes.tjcertificate', JPATH_ADMINISTRATOR);

				$certificateObj  = TJCERT::Certificate()::validateCertificate($certificateData[0]->unique_certificate_id);

				if (!$certificateObj->id)
				{
					$result['access'] = 0;
					$result['msg']    = Text::_("COM_TJLMS_LESSON_RETAKE_BUTTON_TOOLTIP");
					$result['attemptLink'] = 0;

					return $result;
				}
			}
		}
		else
		{
			$result['access'] = 0;
			$result['msg'] = Text::_("COM_TJLMS_NOT_IN_LIBRARY_NOT_IN_COURSE");
		}

		return $result;
	}

	/**
	 * Function used to lesson image
	 *
	 * @param   Array  $lessonId   lessonId
	 * @param   Array  $mediaSize  size of the media from small, medium, large or original media
	 *
	 * @return  STRING  Image to use path
	 *
	 * @since  1.0.0
	 */
	public function getLessonImage($lessonId, $mediaSize = 'media')
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$lesson = Table::getInstance('Lesson', 'TjlmsTable');
		$lesson->load($lessonId);

		$lessonDefaultImg = Uri::root(true) . '/media/com_tjlms/images/default/lesson.png';

		$tjLmsParams = ComponentHelper::getParams('com_tjlms');

		// Get the image and links for lessons.
		if ($lesson->image)
		{
			try
			{
				$uploadPath = $tjLmsParams->get('lesson_image_upload_path', "/images/com_tjlms/lessons/");
				$mediaObj = TJMediaStorageLocal::getInstance(array("id" => $lesson->image ,"uploadPath" => $uploadPath));

				return $mediaObj->$mediaSize;
			}
			catch (\Exception $e)
			{
				return $lessonDefaultImg;
			}
		}

		return $lessonDefaultImg;
	}

	/**
	 * Function is triggered when associative files are downloaded of lesson.
	 *
	 * @param   Array  $mediaData  Media data about the downloading file.
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.4.0
	 */
	public function onAfterAssociateDownload($mediaData)
	{
		if (!empty($mediaData))
		{
			$dateTime                 = array();
			$dateTime['dateTime']     = Factory::getDate()->toSql();
			$downloadStats            = array();
			$downloadStats['file_id'] = $mediaData['media_id'];
			$downloadStats['user_id'] = Factory::getUser()->id;
			$downloadStats['downloads'] = json_encode($dateTime);

			// Get the table object required for data storing.
			$associatedFilesTable    = Tjlms::table('associatedfiles');
			$filesDownloadStatsTable = Tjlms::table('FileDownloadStats');

			// Get the lesson data related to media id.
			$associatedFilesTable->load(array('media_id' => $mediaData['media_id']));
			$associatedMediaData = $associatedFilesTable->getProperties();

			// Get the course details dependant on the lesson id.
			$lessonModel = Tjlms::model('lesson', array('ignore_request' => true));

			if (!empty($associatedMediaData['lesson_id']))
			{
				$lessonData                 = $lessonModel->getlessondata($associatedMediaData['lesson_id']);
				$downloadStats['course_id'] = $lessonData->course_id;
				$filesDownloadStatsTable->save($downloadStats);
			}
		}

		return true;
	}
}
