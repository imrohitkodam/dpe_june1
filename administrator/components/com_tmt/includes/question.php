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
 * Tmt question class
 *
 * @since  1.3.31
 */
class TmtQuestion extends CMSObject
{
	public $id = 0;

	public $ordering = 0;

	public $checked_out = null;

	public $checked_out_time = null;

	public $created_on = null;

	public $title = null;

	public $alias = null;

	public $description = null;

	public $type = null;

	public $level = null;

	public $marks = 0;

	public $state = 1;

	public $ideal_time = 0;

	public $gradingtype = null;

	public $category_id = 0;

	public $created_by = 0;

	public $params = "";

	protected static $instances = array();

	/**
	 * Constructor activating the default information of the Tmt question
	 *
	 * @param   int  $id  The unique question key to load.
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
	 * Returns the global question object
	 *
	 * @param   integer  $id  The primary key of the question to load (optional).
	 *
	 * @return  TmtQuestion  The question object.
	 *
	 * @since   1.3.31
	 */
	public static function getInstance($id = 0)
	{
		if (!$id)
		{
			return new TmtQuestion;
		}

		if (empty(self::$instances[$id]))
		{
			self::$instances[$id] = new TmtQuestion($id);
		}

		return self::$instances[$id];
	}

	/**
	 * Method to load a Tmt question object by question id
	 *
	 * @param   int  $id  The question id
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.3.31
	 */
	public function load($id)
	{
		$table = TMT::table("Question");

		if (!$table->load($id))
		{
			return false;
		}

		$this->setProperties($table->getProperties());

		return true;
	}

	/**
	 * Check if question is beign used.
	 *
	 * @return  boolean  True/False.
	 *
	 * @since   1.3.31
	 */
	public function isUsed()
	{
		$model = TMT::model('Question');

		return $model->isUsed($this->id);
	}
}
