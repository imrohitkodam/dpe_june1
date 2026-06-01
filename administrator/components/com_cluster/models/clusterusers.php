<?php
/**
 * @package    Cluster
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\Data\DataObject;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Methods supporting a list of records.
 *
 * @since  1.0.0
 */
class ClusterModelClusterUsers extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'cu.id',
				'cluster_id', 'cu.cluster_id',
				'state', 'cu.state','cl.name','cl.client_id'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since    1.0.0
	 */
	protected function getListQuery()
	{
		// Initialize variables.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select(
			array('cu.*','cl.name', 'users.name as uname', 'users.email as uemail', 'users.username','cl.name as title', 'cl.client_id as client_id')
		);
		$query->from($db->quoteName('#__tj_cluster_nodes', 'cu'));
		$query->join('INNER', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('cu.user_id') . ' = '
		. $db->quoteName('users.id') . ')');
		$query->join('INNER', $db->quoteName('#__tj_clusters', 'cl') . ' ON (' . $db->quoteName('cl.id') . ' = ' . $db->quoteName('cu.cluster_id') . ')');

		// DPE Hack start to show only active licence org (query extended using plugin trigger)

		Factory::getApplication()->triggerEvent('onClusterUsersModelGetListQuery', array($query));

		// DPE Hack end

		// Get com_subusers component status

		$subUserExist = ComponentHelper::getComponent('com_subusers', true)->enabled;

		if ($subUserExist)
		{
			$roleId = $this->getState('filter.role_id');

			if (!empty($roleId))
			{
				$query->join('INNER', $db->qn('#__tjsu_users', 'su') .
			' ON (' . $db->qn('users.id') . ' = ' . $db->qn('su.user_id') . ' AND ' . $db->qn('su.client') . ' = "com_multiagency" )');
				$query->join('INNER', $db->qn('#__tjsu_roles', 'r') .
			' ON (' . $db->qn('r.id') . ' = ' . $db->qn('su.role_id') . ' AND ' . $db->qn('r.state') . ' = 1 )');

				if (is_numeric($roleId))
				{
					$query->where('su.role_id = ' . (int) $roleId);
				}
				elseif (is_array($roleId))
				{
					$query->where("su.role_id IN ('" . implode("','", $roleId) . "')");
				}
			}
		}

		// Filter by search in title.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('cu.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where('(users.name LIKE' . $search . ' OR cl.name LIKE ' . $search . ')');
			}
		}

		$createdBy = $this->getState('filter.created_by');

		if (!empty($createdBy))
		{
			$query->where($db->quoteName('cu.created_by') . ' = ' . (int) $createdBy);
		}

		// Filter by state
		$state = $this->getState('filter.state');

		if (is_numeric($state))
		{
			$query->where('cu.state = ' . (int) $state);
		}
		elseif ($state === '')
		{
			$query->where('(cu.state = 0 OR cu.state = 1)');
		}

		// Filter by cluster
		$cluster = $this->getState('filter.cluster_id');

		if (is_numeric($cluster))
		{
			$query->where('cu.cluster_id = ' . (int) $cluster);
		}

		// Filter by user
		$clusterUser = $this->getState('filter.user_id');

		if (is_numeric($clusterUser))
		{
			$query->where('cu.user_id = ' . (int) $clusterUser);
		}

		// Filter by client_id
		$clusterClientId = $this->getState('filter.client_id');

		if (is_numeric($clusterClientId))
		{
			$query->where('cl.client_id = ' . (int) $clusterClientId);
		}
		elseif (is_array($clusterClientId))
		{
			$query->where("cl.client_id IN ('" . implode("','", $clusterClientId) . "')");
		}

		// Filter by cluster table state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where('cl.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(cl.state = 0 OR cl.state = 1)');
		}

		// Filter users by block
		$blockUser = $this->getState('filter.block');

		if (is_numeric($blockUser))
		{
			$query->where($db->quoteName('users.block') . ' = ' . (int) $blockUser);
		}

		// Group by cluster
		$clientID = $this->getState('list.group_by_client_id');

		if (is_numeric($clientID))
		{
			$query->group('cl.client_id');
		}

		// Group by user
		$userID = $this->getState('list.group_by_user_id');

		if (is_numeric($userID))
		{
			$query->group('users.id');
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'users.name');
		$orderDirn = $this->state->get('list.direction', 'asc');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}
}
