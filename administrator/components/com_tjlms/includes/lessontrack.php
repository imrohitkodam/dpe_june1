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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

/**
 * TjLms lesson track class
 *
 * @since  1.3.30
 */
class TjLmsLessonTrack extends CMSObject
{
	/**
	 * Id
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $id = 0;

	/**
	 * Lesson id
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $lesson_id = null;

	/**
	 * User id
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $user_id = null;

	/**
	 * Attempt
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $attempt = 0;

	/**
	 * Start time
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $timestart = null;

	/**
	 * End time
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $timeend = null;

	/**
	 * Score
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $score = 0;

	/**
	 * Lesson status
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $lesson_status = null;

	/**
	 * Last accessed on
	 *
	 * @var    datetime
	 * @since  1.3.30
	 */
	public $last_accessed_on = null;

	/**
	 * Modified date
	 *
	 * @var    datetime
	 * @since  1.3.30
	 */
	public $modified_date = null;

	/**
	 * Total content
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $total_content = 0;

	/**
	 * Current position
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $current_position = 0;

	/**
	 * Time spent
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $time_spent = null;

	/**
	 * Live
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $live = 0;

	/**
	 * Modified by
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $modified_by = 0;

	/**
	 * Array to hold the object instances
	 *
	 * @var    object
	 * @since  1.3.30
	 */
	public static $instances = array();

	/**
	 * Constructor
	 *
	 * @param   integer  $lessonId  lesson id
	 * @param   integer  $userId    user id
	 * @param   integer  $attempt   attempt number
	 *
	 * @since   _DEPLOY_VERSION_
	 */
	public function __construct($lessonId, $userId, $attempt = 0)
	{
		$this->load($lessonId, $userId, $attempt);
	}

	/**
	 * Load a lessontrack by its id
	 *
	 * @param   integer  $lessonId  lesson id
	 * @param   integer  $userId    user id
	 * @param   integer  $attempt   attempt number
	 *
	 * @return  void
	 *
	 * @since  1.3.30
	 */
	public function load($lessonId, $userId, $attempt = 0)
	{
		if (!$attempt)
		{
			JLoader::import('components.com_tjlms.models.lessontrack', JPATH_SITE);
			$lessonTrackmodel = BaseDatabaseModel::getInstance('lessonTrack', 'tjlmsModel', array('ignore_request' => true));
			$lastAttemptTrack = $lessonTrackmodel->getLastAttemptonLesson($lessonId, $userId);

			if (isset($lastAttemptTrack->attempt))
			{
				$attempt = $lastAttemptTrack->attempt;
			}
		}

		if (!$attempt)
		{
			return;
		}

		$hash = md5($lessonId . $userId . $attempt);

		if (isset(self::$instances[$hash]))
		{
			return self::$instances[$hash];
		}

		$table = TjLms::table("Lessontrack");

		// Load the object based on the id or throw a warning.
		if (!$table->load(array("lesson_id" => $lessonId, "user_id" => $userId, "attempt" => $attempt)))
		{
			$this->setError(Text::_("COM_TJLMS_NO_LESSONTRACK_WITH_ID"));

			return false;
		}

		$this->setProperties($table->getProperties());

		self::$instances[$hash] = $this;

		return self::$instances[$hash];
	}

	/**
	 * Returns the global object
	 *
	 * @param   integer  $lessonId  lesson id
	 * @param   integer  $userId    user id
	 * @param   integer  $attempt   attempt number
	 *
	 * @return  TjLmsLessonTrack  Object.
	 *
	 * @since   _DEPLOY_VERSION_
	 */
	public static function getInstance($lessonId, $userId, $attempt = 0)
	{
		if (!$lessonId || !$userId)
		{
			return new TjLmsLessonTrack($lessonId, $userId, $attempt);
		}

		$hash = md5($lessonId . $userId . $attempt);

		if (empty(self::$instances[$hash]))
		{
			self::$instances[$hash] = new TjLmsLessonTrack($lessonId, $userId, $attempt);
		}

		return self::$instances[$hash];
	}
}
