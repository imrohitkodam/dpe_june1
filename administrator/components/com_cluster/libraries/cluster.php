<?php
/**
 * @package    Cluster
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Unauthorized Access');
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\Object\CMSObject;

/**
 * Cluster class.  Handles all application interaction with a Cluster
 *
 * @since  1.0.0
 */
class ClusterCluster extends CMSObject
{
	public $id = null;

	public $name = "";

	public $description = "";

	public $params = "";

	public $client = "";

	public $client_id = 0;

	public $ordering = 0;

	public $state = 1;

	public $checked_out = null;

	public $checked_out_time = null;

	public $created_on = null;

	public $created_by = 0;

	public $modified_on = null;

	public $modified_by = 0;

	protected static $clusterObj = array();

	/**
	 * Constructor activating the default information of the Cluster
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

		$this->checked_out_time = $this->created_on = $this->modified_on = $db->getNullDate();
	}

	/**
	 * Returns the global Cluster object
	 *
	 * @param   integer  $id  The primary key of the cluster to load (optional).
	 *
	 * @return  ClusterCluster  The Cluster object.
	 *
	 * @since   1.0.0
	 */
	public static function getInstance($id = 0)
	{
		if (!$id)
		{
			return new ClusterCluster;
		}

		if (empty(self::$clusterObj[$id]))
		{
			$cluster = new ClusterCluster($id);
			self::$clusterObj[$id] = $cluster;
		}

		return self::$clusterObj[$id];
	}

	/**
	 * Method to load a cluster object by cluster id
	 *
	 * @param   int  $id  The cluster id
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 */
	public function load($id)
	{
		$table = ClusterFactory::table("clusters");

		if (!$table->load($id))
		{
			return false;
		}

		$this->setProperties($table->getProperties());

		return true;
	}

	/**
	 * Method to save the Cluster object to the database
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0.0
	 * @throws  \RuntimeException
	 */
	public function save()
	{
		// Create the widget table object
		$table = ClusterFactory::table("clusters");
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
		}
		catch (\Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		$this->id = $table->id;

		return true;
	}

	/**
	 * Method to bind an associative array of data to a cluster object
	 *
	 * @param   array  &$array  The associative array to bind to the object
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 */
	public function bind(&$array)
	{
		if (empty($array))
		{
			$this->setError(Text::_('COM_CLUSTER_EMPTY_DATA'));

			return false;
		}

		// Bind the array
		if (!$this->setProperties($array))
		{
			$this->setError(\Text::_('COM_CLUSTER_BINDING_ERROR'));

			return false;
		}

		// Make sure its an integer
		$this->id = (int) $this->id;

		return true;
	}

	/**
	 * Determines if the provided user id is the owner of this cluster.
	 *
	 * @param   array  $userId  user id
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 */
	public function isOwner($userId = null)
	{
		$userId = Factory::getuser($userId)->id;

		if ($this->created_by == $userId)
		{
			return true;
		}

		return false;
	}

	/**
	 * Function isMember to check user associated with passed cluster_id
	 *
	 * @param   INT  $userId  User Id
	 *
	 * @return  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function isMember($userId = null)
	{
		$userId = Factory::getuser($userId)->id;

		if (empty($userId))
		{
			return false;
		}

		$ClusterModel = ClusterFactory::model('ClusterUsers', array('ignore_request' => true));
		$ClusterModel->setState('filter.published', 1);
		$ClusterModel->setState('filter.cluster_id', (int) $this->id);
		$ClusterModel->setState('filter.user_id', $userId);

		// Check user associated with passed cluster_id
		$clusters = $ClusterModel->getItems();

		if (!empty($clusters))
		{
			return true;
		}

		return false;
	}
}
