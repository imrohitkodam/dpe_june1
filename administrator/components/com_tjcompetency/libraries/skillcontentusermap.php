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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Competency skillcontentusermap class. Handles all application interaction with a Competency SkillContentUserMap
 *
 * @since  1.0.0
 */
class TjCompetencySkillContentUserMap extends CMSObject
{
	public $id = null;

	public $user_id = null;

	public $skill_id = null;

	public $scale_id = null;

	public $client = null;

	public $client_id = null;

	public $reviewer_id = null;

	public $note = null;

	public $state = 1;

	public $checked_out = null;

	public $checked_out_time = null;

	public $created_on = null;

	public $created_by = 0;

	public $modified_on = null;

	public $modified_by = 0;

	public $notify = 1;

	protected static $competencySkillContentUserMapObj = array();

	/**
	 * Constructor activating the default information of the Competency skillcontentusermap
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
	 * Returns the global competency skillcontentusermap object
	 *
	 * @param   integer  $id  The primary key of the competency skillcontentusermap id to load (optional).
	 *
	 * @return  Object  Competency skillcontentusermap object.
	 *
	 * @since   1.0.0
	 */
	public static function getInstance($id = 0)
	{
		if (!$id)
		{
			return new TjCompetencySkillContentUserMap;
		}

		if (empty(self::$competencySkillContentUserMapObj[$id]))
		{
			$competencySkillContentUserMap = new TjCompetencySkillContentUserMap($id);
			self::$competencySkillContentUserMapObj[$id] = $competencySkillContentUserMap;
		}

		return self::$competencySkillContentUserMapObj[$id];
	}

	/**
	 * Method to load a competency skillcontentusermap object by competency skillcontentusermap id
	 *
	 * @param   int  $id  The competency skillcontentusermap id
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 */
	public function load($id)
	{
		$table = TjCompetency::table("skillcontentusermaps");

		if (!$table->load($id))
		{
			return false;
		}

		$this->setProperties($table->getProperties());

		return true;
	}

	/**
	 * Method to save the Competency skillcontentusermap object to the database
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 * @throws  \RuntimeException
	 */
	public function save()
	{
		// Create the competency skillcontentusermap table object
		$table = TjCompetency::table("skillcontentusermaps");
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
				if (empty($table->created_on))
				{
					$table->created_on = $currentDateTime;
				}

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

			if ($isNew && $this->notify)
			{
				if ($table->state == 1)
				{
					TjCompetency::Mails()->onSkillAfterAwarded($this);
				}
				elseif ($table->state == 3)
				{
					TjCompetency::Mails()->onSkillAfterPendingApproval($this);
				}
			}

			// Fire the onTjCompetencySkillContentUserMapAfterSave event.
			Factory::getApplication()->triggerEvent('onTjCompetencySkillContentUserMapAfterSave', array($isNew, $this));
		}
		catch (\Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return true;
	}

	/**
	 * Method to bind an associative array of data to a competency skillcontentusermap object
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

		return true;
	}
}
