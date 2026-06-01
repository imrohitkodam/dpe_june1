<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\String\StringHelper;

/**
 * Competency framework class. Handles all application interaction with a Competency Framework
 *
 * @since  1.0.0
 */
class TjCompetencyFramework extends CMSObject
{
	public $id = null;

	public $title = "";

	public $description = "";

	public $unique_code = null;

	public $state = 1;

	public $checked_out = null;

	public $checked_out_time = null;

	public $created_on = null;

	public $created_by = 0;

	public $modified_on = null;

	public $modified_by = 0;

	public $params = "";

	protected static $competencyFrameworkObj = array();

	/**
	 * Constructor activating the default information of the Competency framework
	 *
	 * @param   int  $id  The unique event key to load.
	 *
	 * @since   1.0.0
	 */
	public function __construct($id = 0)
	{
		if (!empty($id))
		{
			$this->load($id);
		}

		$db = Factory::getDbo();

		$this->checked_out_time = $this->modified_on = $db->getNullDate();
	}

	/**
	 * Returns the global competency framework object
	 *
	 * @param   integer  $id  The primary key of the competency framework id to load (optional).
	 *
	 * @return  Object  Competency framework object.
	 *
	 * @since   1.0.0
	 */
	public static function getInstance($id = 0)
	{
		if (!$id)
		{
			return new TjCompetencyFramework;
		}

		if (empty(self::$competencyFrameworkObj[$id]))
		{
			$competencyFramework = new TjCompetencyFramework($id);
			self::$competencyFrameworkObj[$id] = $competencyFramework;
		}

		return self::$competencyFrameworkObj[$id];
	}

	/**
	 * Method to load a competency framework object by competency framework id
	 *
	 * @param   int  $id  The competency framework id
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 */
	public function load($id)
	{
		$table = TjCompetency::table("frameworks");

		if (!$table->load($id))
		{
			return false;
		}

		$this->setProperties($table->getProperties());

		return true;
	}

	/**
	 * Method to save the Competency framework object to the database
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 * @throws  \RuntimeException
	 */
	public function save()
	{
		// Create the competency framework table object
		$table = TjCompetency::table("frameworks");
		$table->bind($this->getProperties());

		$currentDateTime = Factory::getDate()->toSql();

		$user = Factory::getUser();

		// Allow an exception to be thrown.
		try
		{
			// Check and store the object.
			if (!$table->check())
			{
				$this->setError($table->getError());

				return false;
			}

			// Check if new record
			$isNew = empty($this->id);

			if ($isNew)
			{
				$table->created_on = $currentDateTime;
				$table->created_by = $user->id;
			}
			else
			{
				$table->modified_on = $currentDateTime;
				$table->modified_by = $user->id;
			}

			// Store the user data in the database
			if (!($table->store()))
			{
				$this->setError($table->getError());

				return false;
			}

			$this->id = $table->id;

			// Fire the onTjCompetencyFrameworkAfterSave event.
			Factory::getApplication()->triggerEvent('onTjCompetencyFrameworkAfterSave', array($isNew, $this));
		}
		catch (\Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return true;
	}

	/**
	 * Method to bind an associative array of data to a competency framework object
	 *
	 * @param   array  &$array  The associative array to bind to the object
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 */
	public function bind(&$array)
	{
		if (empty ($array))
		{
			$this->setError(Text::_('COM_TJCOMPETENCY_EMPTY_DATA'));

			return false;
		}

		// Bind the array
		if (!$this->setProperties($array))
		{
			$this->setError(Text::_('COM_TJCOMPETENCY_BINDING_ERROR'));

			return false;
		}

		// Make sure its an integer
		$this->id = (int) $this->id;
		$this->title = StringHelper::trim($this->title);

		return true;
	}
}
