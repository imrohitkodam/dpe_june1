<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Tmt test class
 *
 * @since  1.3.31
 */
class TmtTest extends CMSObject
{
	public $id = 0;

	public $parent_id = 0;

	public $type = 0;

	public $ordering = 0;

	public $state = 0;

	public $checked_out = null;

	public $checked_out_time = null;

	public $created_by = 0;

	public $title = null;

	public $alias = null;

	public $description = 0;

	/**
	 * Reviewer's Id
	 *
	 * @since       1.0.0
	 *
	 * @deprecated  1.4.0  This object will be removed and no replacement will be provided
	 */
	public $reviewers = null;

	public $show_time = 0;

	public $time_duration = 0;

	public $show_time_finished = 0;

	public $time_finished_duration = 0;

	public $total_marks = 0;

	public $passing_marks = 0;

	public $isObjective = 0;

	public $created_on = null;

	public $modified_on = null;

	public $start_date = null;

	public $end_date = null;

	public $termscondi = 0;

	public $answer_sheet = 0;

	public $questions_shuffle = 0;

	public $answers_shuffle = 0;

	public $gradingtype = '';

	public $show_thankyou_page = 0;

	public $show_all_questions = 0;

	public $pagination_limit = 0;

	public $show_questions_overview = 0;

	public $image = 0;

	protected static $instances = array();

	/**
	 * Constructor activating the default information of the Tmt test
	 *
	 * @param   int  $id  The unique test key to load.
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
	 * Returns the global test object
	 *
	 * @param   integer  $id  The primary key of the test to load (optional).
	 *
	 * @return  TmtTest  The test object.
	 *
	 * @since   1.3.31
	 */
	public static function getInstance($id = 0)
	{
		if (!$id)
		{
			return new TmtTest;
		}

		if (empty(self::$instances[$id]))
		{
			self::$instances[$id] = new TmtTest($id);
		}

		return self::$instances[$id];
	}

	/**
	 * Method to load a Tmt test object by certificate template id
	 *
	 * @param   int  $id  The certificate template id
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 */
	public function load($id)
	{
		$table = TMT::table("Test");

		if (!$table->load($id))
		{
			return false;
		}

		$this->setProperties($table->getProperties());

		return true;
	}
}
