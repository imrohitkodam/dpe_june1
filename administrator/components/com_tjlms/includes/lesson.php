<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\PluginHelper;
JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

/**
 * Dhanik Lot Class
 *
 * @since  0.0.1
 */
class TjLmsLesson extends CMSObject
{
	/**
	 * Auto increment lesson id
	 *
	 * @var    int
	 * @since  0.0.1
	 */
	public $id = 0;

	/**
	 * The lesson title
	 *
	 * @var    string
	 * @since  0.0.1
	 */
	public $title = null;

	/**
	 * The lesson alias
	 *
	 * @var    string
	 * @since  0.0.1
	 */
	public $alias = null;

	/**
	 * The category id for the lesson
	 *
	 * @var    int
	 * @since  0.0.1
	 */
	public $catid = 0;

	/**
	 * The short desc for the lesson
	 *
	 * @var    string
	 * @since  0.0.1
	 */
	public $short_desc = 0;

	/**
	 * The desc for the lesson
	 *
	 * @var    string
	 * @since  0.0.1
	 */
	public $description = 0;

	/**
	 * creator id
	 *
	 * @var    string
	 * @since  0.0.1
	 */
	public $image = null;

	/**
	 * lesson start date
	 *
	 * @var    datetime
	 * @since  0.0.1
	 */
	public $start_date = null;

	/**
	 * lesson end date
	 *
	 * @var    datetime
	 * @since  0.0.1
	 */
	public $end_date = null;

	/**
	 * Number of attempts available for the lesson
	 *
	 * @var    int
	 * @since  0.0.1
	 */
	public $no_of_attempts   = 0;

	/**
	 * Attempts grading
	 *
	 * @var    int
	 * @since  0.0.1
	 */
	public $attempts_grade   = '';

	/**
	 * Lesson format
	 *
	 * @var    int
	 * @since  0.0.1
	 */
	public $format = '';

	/**
	 * Lesson media_id
	 *
	 * @var    int
	 * @since  0.0.1
	 */
	public $media_id  = 0;

	/**
	 * Lesson Ideal time
	 *
	 * @var    int
	 * @since  0.0.1
	 */
	public $ideal_time   = 0;

	/**
	 * If lesson is allowed to resume
	 *
	 * @var    int
	 * @since  0.0.1
	 */
	public $resume    = 0;

	/**
	 * Total marks for the lesson
	 *
	 * @var    int
	 * @since  0.0.1
	 */
	public $total_marks     = 0;

	/**
	 * Passing marks for the lesson
	 *
	 * @var    int
	 * @since  0.0.1
	 */
	public $passing_marks     = 0;

	/**
	 * If lesson is set to be shown in lesson list
	 *
	 * @var    int
	 * @since  0.0.1
	 */
	public $in_lib     = 0;

	/**
	 * @var    array  Lesson instances container.
	 * @since  1.3.30
	 */
	protected static $instances = array();

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
	 * Load a lesson by its id
	 *
	 * @param   integer  $id  lesson ID
	 *
	 * @return  void
	 *
	 * @since  1.0
	 */
	public function load($id)
	{
		JLoader::import("/components/com_tjlms/tables", JPATH_ADMINISTRATOR);
		$table = Table::getInstance('Lesson', 'TjlmsTable');

		// Load the object based on the id or throw a warning.
		if (!$table->load($id))
		{
			$this->setError(Text::_("COM_TJLMS_NO_LESSON_WITH_ID"));

			return false;
		}

		// Get lesson image
		if ($table->image)
		{
			$tjLmsParams = ComponentHelper::getParams('com_tjlms');
			$uploadPath = $tjLmsParams->get('lesson_image_upload_path', "/images/com_tjlms/lessons/");
			$mediaObj = TJMediaStorageLocal::getInstance(array("id" => $table->image, "uploadPath" => $uploadPath));
			$table->imageMedia = $mediaObj;
		}

		$this->setProperties($table->getProperties());
	}

	/**
	 * Returns the global course object
	 *
	 * @param   integer  $id  The primary key of the lesson to load (optional).
	 *
	 * @return  TjLmsLesson  The lesson object.
	 *
	 * @since   1.3.30
	 */
	public static function getInstance($id = 0)
	{
		if (!$id)
		{
			return new TjLmsLesson;
		}

		if (empty(self::$instances[$id]))
		{
			self::$instances[$id] = new TjLmsLesson($id);
		}

		return self::$instances[$id];
	}

	/**
	 * This function checks the lesson is passable or not
	 *
	 * @param   string  $lessonFormat  lesson format
	 *
	 * @return  boolean
	 *
	 * @since   1.3.39
	 */
	public function checkLessonIsPassable($lessonFormat)
	{
		if ($lessonFormat)
		{
			static $passableLessonTypes = array();

			$hash = md5($lessonFormat);

			if (isset($passableLessonTypes[$hash]))
			{
				return $passableLessonTypes[$hash];
			}

			PluginHelper::importPlugin('tj' . $lessonFormat);

			$result = Factory::getApplication()->triggerEvent('onisPassable_tj' . $lessonFormat);

			if (!empty ($result))
			{
				return $passableLessonTypes[$hash] = true;
			}
		}

		return false;
	}

	/**
	 * Fetch list of all lessons used in any of the courses
	 *
	 * @param   ARRAY  $lessonIds  Array of the lesson Ids
	 *
	 * @return  ARRAY
	 *
	 * @since   1.4.0
	 *
	 */
	public function getLessonTitles($lessonIds)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->qn(array('l.title')));
		$query->from($db->qn('#__tjlms_lessons', 'l'));
		$query->where($db->qn('l.id') . ' IN(' . implode(',', $lessonIds) . ')');
		$db->setQuery($query);

		return $db->loadColumn();
	}
}
