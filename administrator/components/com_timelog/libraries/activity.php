<?php
/**
 * @package    Com_Timelog
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die('Unauthorized Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;

/**
 * TimelogActivity class - Handles all application interaction with a Timelog
 *
 * @since  1.0.0
 */
class TimelogActivity extends CMSObject
{
	/**
	 * The auto incremental primary key of timelog activity
	 *
	 * @var    integer
	 * @since  1.0.0
	 */
	public $id = 0;

	/**
	 * The activity_type_id is a type of timelog activities [It's an Id of Doccumentation type of activity]
	 *
	 * @var    integer
	 * @since  1.0.0
	 */
	public $activity_type_id = 0;

	/**
	 * The client means to store activity against one entity like com_shika, com_jlike
	 *
	 * @var    String
	 * @since  1.0.0
	 */
	public $client = "";

	/**
	 * The client_id to store reference entity Id
	 *
	 * @var    integer
	 * @since  1.0.0
	 */
	public $client_id = 0;

	/**
	 * The client means to store activity against one entity like com_shika, com_jlike
	 *
	 * @var    String
	 * @since  1.0.0
	 */
	public $activity_note = "";

	/**
	 * The activity created date and time
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	public $created_date;

	/**
	 * The spent_time against activity in hours
	 *
	 * @var    integer
	 * @since  1.0.0
	 */
	public $spent_time = 0;

	/**
	 * The state of activity [ state value 1 : Publish, 0 : Unpublish, 2 : Archive, -2 : Trash ]
	 *
	 * @var    integer
	 * @since  1.0.0
	 */
	public $state = 1;

	/**
	 * The attachment to store the files/ document against timelog activity
	 *
	 * @var    String
	 * @since  1.0.0
	 */
	public $attachment = "";

	/**
	 * joomla user id of the creator
	 *
	 * @var    integer
	 * @since  1.0.0
	 */
	public $created_by = 0;

	/**
	 * The activity modified date and time
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	public $modified_date = '';

	/**
	 * joomla user id of the modifier
	 *
	 * @var    integer
	 * @since  1.0.0
	 */
	public $modified_by = 0;

	/**
	 * holds the already loaded instances of the activity
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected static $timelogActivityObj = array();

	/**
	 * Constructor activating the default information of the Timelog
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
	}

	/**
	 * Returns the global Timelog object
	 *
	 * @param   integer  $id  The primary key of the timelog to load (optional).
	 *
	 * @return  Object  The Timelog object.
	 *
	 * @since   1.0.0
	 */
	public static function getInstance($id = 0)
	{
		if (!$id)
		{
			return new TimelogActivity;
		}

		if (empty(self::$timelogActivityObj[$id]))
		{
			$timelog = new TimelogActivity($id);
			self::$timelogActivityObj[$id] = $timelog;
		}

		return self::$timelogActivityObj[$id];
	}

	/**
	 * Method to load a timelog object by activity id
	 *
	 * @param   int  $id  The timelog id
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 */
	public function load($id)
	{
		/*
		 * @var $table TimelogTableactivity
		 */
		$table = TimelogFactory::table("activity");

		if (!$table->load($id))
		{
			return false;
		}

		$this->setProperties($table->getProperties());

		return true;
	}

	/**
	 * Method to save the Timelog object to the database
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0.0
	 * @throws  \RuntimeException
	 */
	public function save()
	{
		/* Create the table object
		 *
		 * @var $table TimelogTableactivity
		 */
		$table = TimelogFactory::table("activity");
		$table->bind($this->getProperties());

		// Check and store the object.
		if (!$table->check())
		{
			$this->setError($table->getError());

			return false;
		}

		// Store the user data in the database
		if (!($table->store()))
		{
			$this->setError($table->getError());

			return false;
		}

		$this->id = (int) $table->id;

		return true;
	}

	/**
	 * Method to bind an associative array of data to a timelog object
	 *
	 * @param   array  $array  The associative array to bind to the object
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 */
	public function bind($array)
	{
		if (empty($array))
		{
			$this->setError(Text::_('COM_TIMELOG_EMPTY_DATA'));

			return false;
		}

		// Bind the array
		if (!$this->setProperties($array))
		{
			$this->setError(Text::_('COM_TIMELOG_BINDING_ERROR'));

			return false;
		}

		// Make sure its an integer
		$this->id = (int) $this->id;

		return true;
	}

	/**
	 * Method to delete data
	 *
	 * @param   int  &$pk  Item primary key
	 *
	 * @return  int|boolean  The id of the deleted item
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function delete(&$pk)
	{
		$user = Factory::getUser();

		// DPE Hack - Check if you have access timelog for a activity
		$slaActivityId   = 0;

		if (!$this->client_id)
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if ($this->client_id)
		{
			$slaActivityId = $this->client_id;
		}

		$slaSlaActivity = SlaSlaActivity::getInstance($slaActivityId);

		if (property_exists($slaSlaActivity, 'cluster_id'))
		{
			$clusterId = $slaSlaActivity->cluster_id;
		}

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			if (!$clusterId)
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}

			// DPE hack to check permission
			$canDeleteTimelog = RBACL::check($user->id, 'com_cluster', 'core.delete.logs', 'com_timelog', $clusterId);

			if (!$canDeleteTimelog)
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}
		}

		/*
		if ($user->authorise('core.delete', 'com_timelog') !== true)
		{
			$this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}
		*/

		// Create the widget table object
		$table = TimelogFactory::table("activity");

		if ($table->delete($pk) !== true)
		{
			$this->setError(Text::_('JERROR_FAILED'));

			return false;
		}

		return true;
	}
}
