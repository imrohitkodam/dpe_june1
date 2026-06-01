<?php
/**
 * @package     TJ-Gophish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Object\CMSObject;

/**
 * TjGoPhish group class.
 *
 * @since  1.0.0
 */
class TjGoPhishGroup extends CMSObject
{
	/**
	 * The auto incremental primary key of the group
	 *
	 * @var    integer
	 * @since  1.0.0
	 */
	public $id = 0;

	/**
	 * Group Name
	 *
	 * @var    String
	 * @since  1.0.0
	 */
	public $name = '';

	/**
	 * GoPhish User id
	 *
	 * @var    Int
	 * @since  1.0.0
	 */
	public $user_id = '';

	/**
	 * Date time when the group was last modified
	 *
	 * @var    datetime
	 * @since  1.0.0
	 */
	public $modified_date = '';

	/**
	 * Array of objects of targets for the group
	 *
	 * @var    Array
	 * @since  1.0.0
	 */
	public $targets = '';

	/**
	 * holds the already loaded instances of the Group
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected static $groupObj = array();

	/**
	 * Constructor activating the default information of the group
	 *
	 * @param   int  $id  The unique group key to load.
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
	 * Returns the group object
	 *
	 * @param   integer  $id  The primary key of the group to load (optional).
	 *
	 * @return  TjGoPhishGroup  The group object.
	 *
	 * @since   1.0.0
	 */
	public static function getInstance($id = 0)
	{
		if (!$id)
		{
			return new TjGoPhishGroup;
		}

		if (empty(self::$groupObj[$id]))
		{
			self::$groupObj[$id] = new TjGoPhishGroup($id);
		}

		return self::$groupObj[$id];
	}

	/**
	 * Method to load a group properties
	 *
	 * @param   int  $id  The group id
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0.0
	 */
	public function load($id)
	{
		$table = TjGoPhish::table("group");

		if ($table->load($id))
		{
			$this->setProperties($table->getProperties());

			return true;
		}

		return false;
	}

	/**
	 * Method to save the Group object to the database
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0.0
	 */
	public function save()
	{
		$isNew = $this->isNew();

		// Create the group table object
		$table = TjGoPhish::table('group');

		// Allow an exception to be thrown.
		try
		{
			$table->bind(get_object_vars($this));

			// Check and store the object.
			if (!$table->check())
			{
				$this->setError($table->getError());

				return false;
			}

			// Store the group data in the database
			$result = $table->store();

			// Set the id for the group object in case we created a new group.
			if ($result && $isNew)
			{
				$this->load($table->get('id'));
				$group = TjGoPhish::model('group');
				$this->group_id = $group->generateGroupID($this->id);

				return $this->save();
			}
			elseif ($result && !$isNew)
			{
				return $this->load($this->id);
			}
		}
		catch (\Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return $result;
	}

	/**
	 * Method to check is group new or not
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0.0
	 */
	private function isNew()
	{
		return $this->id < 1;
	}

	/**
	 * Returns a property of the object or the default value if the property is not set.
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $default   The default value.
	 *
	 * @return  mixed    The value of the property.
	 *
	 * @since   1.0.0
	 */
	public function get($property, $default = null)
	{
		if (isset($this->$property))
		{
			return $this->$property;
		}

		return $default;
	}
}
