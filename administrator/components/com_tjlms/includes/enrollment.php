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
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Object\CMSObject;

/**
 * Tjlms enrollment class
 *
 * @since  1.3.30
 */
class TjLmsEnrollment extends CMSObject
{
	/**
	 * Id
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $id = 0;

	/**
	 * Course id
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $course_id = 0;

	/**
	 * User id
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $user_id = 0;

	/**
	 * Enrollment time
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $enrolled_on_time = null;

	/**
	 * End time
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $end_time = null;

	/**
	 * Enrolled by
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $enrolled_by = 0;

	/**
	 * Modified time
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $modified_time = null;

	/**
	 * State
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $state = null;

	/**
	 * Unlimited plan
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $unlimited_plan = 0;

	/**
	 * Before expiry mail
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $before_expiry_mail = 0;

	/**
	 * After expiry mail
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $after_expiry_mail = 0;

	/**
	 * Params
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $params = '';

	/**
	 * Array to hold the object instances
	 *
	 * @var    object
	 * @since  1.3.30
	 */
	public static $instances = array();

	/**
	 * Expired (Not in mysql table)
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $expired = 0;

	/**
	 * Constructor
	 *
	 * @param   int  $userId    User id
	 *
	 * @param   int  $courseId  Course id
	 *
	 * @since   1.3.30
	 */
	public function __construct($userId, $courseId)
	{
		if (!empty($userId) && !empty($courseId))
		{
			$this->load($userId, $courseId);
		}
	}

	/**
	 * Load enrollment
	 *
	 * @param   integer  $userId    User id
	 * @param   integer  $courseId  Course id
	 *
	 * @return  void
	 *
	 * @since  1.3.30
	 */
	public function load($userId, $courseId)
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$table = Table::getInstance('Enrolledusers', 'TjlmsTable');

		$table->load(array('user_id' => (int) $userId, 'course_id' => (int) $courseId));

		if (!empty($table->id))
		{
			$table->expired = 0;

			$course = TjLms::course($table->course_id);

			if ($course->type == 1)
			{
				$today   = Factory::getDate();
				$curdate = strtotime($today);

				if ($curdate > strtotime($table->end_time) && $table->unlimited_plan != 1)
				{
					$table->expired = 1;
				}
			}
		}

		$this->setProperties($table->getProperties());
	}

	/**
	 * Returns the global object
	 *
	 * @param   integer  $userId    User id
	 * @param   integer  $courseId  Course id
	 *
	 * @return  TjLmsEnrollment  Object.
	 *
	 * @since   1.3.30
	 */
	public static function getInstance($userId, $courseId)
	{
		if (!$userId || !$courseId)
		{
			return new TjLmsEnrollment($userId, $courseId);
		}

		$hash = md5($userId . $courseId);

		if (empty(self::$instances[$hash]))
		{
			self::$instances[$hash] = new TjLmsEnrollment($userId, $courseId);
		}

		return self::$instances[$hash];
	}
}
