<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;

/**
 * Tmt test attendees class
 *
 * @since  1.3.31
 */
class TmtTestAttendees extends CMSObject
{
	public $id = 0;

	public $invite_id = 0;

	public $test_id = 0;

	public $user_id = 0;

	/**
	 * Company Id
	 *
	 * @since       1.0.0
	 *
	 * @deprecated  1.4.0  This object will be removed and no replacement will be provided
	 */
	public $company_id = 0;

	public $result_status = null;

	public $score = 0;

	public $attempt_status = 0;

	public $review_status = 0;

	public $time_taken = 0;

	protected static $instances = array();

	/**
	 * Constructor activating the default information of the Tmt test attendees
	 *
	 * @param   int  $id  The unique test attendees key to load.
	 *
	 * @since   1.3.31
	 */
	public function __construct($id = 0)
	{
		if (!empty($id))
		{
			$this->load($id);
		}
	}

	/**
	 * Returns the global test attendees object
	 *
	 * @param   integer  $id  The primary key of the test attendees to load (optional).
	 *
	 * @return  TmtTestAttendees  The test attendees object.
	 *
	 * @since   1.3.31
	 */
	public static function getInstance($id = 0)
	{
		if (!$id)
		{
			return new TmtTestAttendees;
		}

		if (empty(self::$instances[$id]))
		{
			self::$instances[$id] = new TmtTestAttendees($id);
		}

		return self::$instances[$id];
	}

	/**
	 * Method to load a TestAttendees object by TestAttendees id
	 *
	 * @param   int  $id  The TestAttendees id
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.3.31
	 */
	public function load($id)
	{
		$table = TMT::table("TestAttendees");

		if (!$table->load($id))
		{
			return false;
		}

		$this->setProperties($table->getProperties());

		return true;
	}
}
