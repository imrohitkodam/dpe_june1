<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();
use Joomla\CMS\Factory;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * TjLms course class
 *
 * @since  1.3.30
 */
class TjLmsCourse extends CMSObject
{
	/**
	 * Id
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $id = 0;

	/**
	 * Title
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $title = null;

	/**
	 * Alias
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $alias = null;

	/**
	 * Category id
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $catid = 0;

	/**
	 * Shot description
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $short_desc = null;

	/**
	 * Description
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $description = null;

	/**
	 * Image
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $image = null;

	/**
	 * Start date
	 *
	 * @var    datetime
	 * @since  1.3.30
	 */
	public $start_date = null;

	/**
	 * End date
	 *
	 * @var    datetime
	 * @since  1.3.30
	 */
	public $end_date = null;

	/**
	 * Access level
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $access = 0;

	/**
	 * Free or Paid
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $type = 0;

	/**
	 * Condition to get the certificate
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $certificate_term = 0;

	/**
	 * Certificate table id
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $certificate_id = 0;

	/**
	 * @var    array  Course instances container.
	 * @since  1.3.30
	 */
	protected static $instances = array();

	/**
	 * @var    object  tjlms helper object.
	 * @since  1.4.0
	 */
	private $comtjlmsHelperObj = null;

	/**
	 * Constructor
	 *
	 * @param   int  $id  Unique key to load.
	 *
	 * @since   1.3.30
	 */
	public function __construct($id = 0)
	{
		if (!empty($id))
		{
			$this->load($id);
		}
	}

	/**
	 * Load a course by its id
	 *
	 * @param   integer  $id  course ID
	 *
	 * @return  void
	 *
	 * @since  1.3.30
	 */
	public function load($id)
	{
		$table = TjLms::table("Course");

		// Load the object based on the id or throw a warning.
		if (!$table->load($id))
		{
			return false;
		}

		$this->setProperties($table->getProperties());

		return true;
	}

	/**
	 * Returns the global course object
	 *
	 * @param   integer  $id  The primary key of the course to load (optional).
	 *
	 * @return  TjLmsCourse  The course object.
	 *
	 * @since   1.3.30
	 */
	public static function getInstance($id = 0)
	{
		if (!$id)
		{
			return new TjLmsCourse;
		}

		if (empty(self::$instances[$id]))
		{
			self::$instances[$id] = new TjLmsCourse($id);
		}

		return self::$instances[$id];
	}

	/**
	 * This function provides the passable lessons of course
	 *
	 * @return  array|void  array of lesson ids
	 *
	 * @since   1.3.39
	 */
	public function getPassableLessons()
	{
		if (!empty($this->id))
		{
			// Get lessons by setting the course id
   			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjlms/models', 'TjlmsModel');
			$model = BaseDatabaseModel::getInstance('Lessons', 'TjlmsModel', array('ignore_request' => true));
			$model->setState('filter.in_lib', 0);
			$model->setState('filter.course_id', $this->id);
			$model->setState('filter.consider_marks', 1);
			$lessons = $model->getItems();

			$passableLesson = array();

			$lessonObj = Tjlms::lesson();

			foreach ($lessons as $lesson)
			{
				$result = $lessonObj->checkLessonIsPassable($lesson->format);

				if ($result)
				{
					$passableLesson[] = $lesson;
				}
			}

			return $passableLesson;
		}
	}

	/**
	 * Function to expire the certificates & archive the corresponding lesson attempts.
	 *
	 * @param   integer  $userId  user id.
	 *
	 * @return  bool True on success, false otherwise
	 *
	 * @since   1.4.0
	 */
	public function expireCertificate($userId = 0)
	{
		$tjlmsParams             = ComponentHelper::getParams('com_tjlms');
		$notifyCertificateExpiry = $tjlmsParams->get('notify_certificate_expiry', '0');

		// Add TJLMS activites
		$this->comtjlmsHelperObj = new comtjlmsHelper;

		JLoader::import('components.com_tjlms.helpers.mailcontent', JPATH_SITE);
		$tjlmsMailcontentHelper = new TjlmsMailcontentHelper;

		if (empty($this->id))
		{
			return;
		}

		JLoader::import('components.com_tjcertificate.models.certificates', JPATH_ADMINISTRATOR);
		$certificatesModel = BaseDatabaseModel::getInstance('certificates', 'TjCertificateModel', array('ignore_request' => true));
		$certificatesModel->setState('filter.client', 'com_tjlms.course');
		$certificatesModel->setState('filter.client_id', $this->id);

		// In case of retake course set this filter
		if ($userId)
		{
			$certificatesModel->setState('filter.user_id', $userId);
		}

		$certificatesModel->setState('filter.expired', 1);
		$certificatesData = $certificatesModel->getItems();

		if (empty($certificatesData))
		{
			return;
		}

		JLoader::import('components.com_tjlms.models.lessons', JPATH_SITE);
		$lessonsModel = BaseDatabaseModel::getInstance('lessons', 'TjlmsModel', array('ignore_request' => true));
		$lessonsModel->setState('filter.course_id', $this->id);
		$lessonsModel->setState('filter.in_lib', 0);
		$lessonsData = $lessonsModel->getItems();
		$lessonsId = array_column($lessonsData, 'id');

		$usersId = array_column($certificatesData, 'user_id');

		if (!empty($lessonsId) && !empty($usersId))
		{
			JLoader::import('components.com_tjlms.models.lessontrack', JPATH_SITE);
			$lessonTrackmodel = BaseDatabaseModel::getInstance('lessonTrack', 'tjlmsModel', array('ignore_request' => true));
			$lessonTrackmodel->archiveBulkAttempts($lessonsId, $usersId);

			$this->resetBulkStatus($this->id, $usersId);
		}
		
		foreach ($certificatesData as $certificate)
		{
			// Action performed
			$action    = 'CERTIFICATE_EXPIRED';
			$params    = '';
			$courseUrl = 'index.php?option=com_tjlms&view=course&id=' . $this->id;

			$this->comtjlmsHelperObj->addActivity($certificate->user_id, $action, $this->id, $this->title, $this->id, $courseUrl, $params);

			// Notify user to certificate is expired.
			if ($notifyCertificateExpiry)
			{
				$tjlmsMailcontentHelper->onAfterCertificateExpired($certificate->user_id, $this->id);
			}
		}

		return true;
	}

	/**
	 * Function to archive the corresponding lesson attempts.
	 *
	 * @param   integer  $courseId  course id
	 * @param   array    $usersId   user id array
	 *
	 * @since   1.4.0
	 *
	 * @return  array|void
	 */
	public function resetBulkStatus($courseId, $usersId)
	{
		if (!empty($courseId) && !empty($usersId))
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->update($db->quoteName('#__tjlms_course_track'))
				->set('status = ' . $db->quote('I'))
				->set('completed_lessons = 0')
				->where('course_id = ' . (int) $courseId)
				->where('user_id IN (' . implode(',', $usersId) . ')');
			$db->setQuery($query);

			$db->execute();
		}
	}

	/**
	 * Function to check if course is Paid
	 *
	 * @return  boolean  True on success
	 *
	 * @since   4.0.0
	 */
	public function isPaid()
	{
		return $this->type == 1 ? true : false;
	}
}
