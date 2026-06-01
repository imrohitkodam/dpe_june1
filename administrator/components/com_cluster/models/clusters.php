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

use Joomla\CMS\MVC\Model\ListModel;

/**
 * Methods supporting a list of records.
 *
 * @since  1.0.0
 */
class ClusterModelClusters extends ListModel
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
				'id', 'cl.id',
				'name', 'cl.name',
				'client', 'cl.client',
				'ordering', 'cl.ordering',
				'state', 'cl.state',
				'created_by', 'cl.created_by',
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

		// Create the base select statement.
		$query->select(array('cl.*','users.name as uname','cl.id as cluster_id'));
		$query->from($db->quoteName('#__tj_clusters', 'cl'));
		$query->join('LEFT', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('cl.created_by') . ' = ' . $db->quoteName('users.id') . ')');

		// Filter by dashboard_id
		$id = $this->getState('filter.id');

		if (!empty($id))
		{
			$query->where($db->quoteName('cl.id') . ' = ' . (int) $id);
		}

		// Filter by search in title.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('cl.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where('(cl.name LIKE ' . $search . ' OR cl.description LIKE ' . $search . ')');
			}
		}

		// Filter by created_by
		$created_by = $this->getState('filter.created_by');

		if (!empty($created_by))
		{
			$query->where($db->quoteName('cl.created_by') . ' = ' . (int) $created_by);
		}

		// Filter by state
		$state = $this->getState('filter.state');

		if (is_numeric($state))
		{
			$query->where('cl.state = ' . (int) $state);
		}
		elseif ($state === '')
		{
			$query->where('(cl.state = 0 OR cl.state = 1)');
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}
}
