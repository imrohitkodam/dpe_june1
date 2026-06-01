<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
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
class SlaModelSlas extends ListModel
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
				'id', 's.id',
				'title', 's.title',
				'ordering', 's.ordering',
				'state', 's.state',
				'created_by', 's.created_by',
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
		$query->select(array('s.*','users.name as uname'));
		$query->from($db->quoteName('#__tj_slas', 's'));
		$query->join('LEFT', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('s.created_by') . ' = ' . $db->quoteName('users.id') . ')');

		// Filter by Id
		$id = $this->getState('filter.id');

		if (!empty($id))
		{
			$query->where($db->quoteName('s.id') . ' = ' . (int) $id);
		}

		// Filter by search in title.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('s.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where('(s.title LIKE ' . $search . ' OR s.description LIKE ' . $search . ' )');
			}
		}

		// Filter by created_by
		$created_by = $this->getState('filter.created_by');

		if (!empty($created_by))
		{
			$query->where($db->quoteName('s.created_by') . ' = ' . (int) $created_by);
		}

		// Filter by state
		$state = $this->getState('filter.state');

		if (is_numeric($state))
		{
			$query->where('s.state = ' . (int) $state);
		}
		elseif ($state === '')
		{
			$query->where('(s.state = 0 OR s.state = 1)');
		}

		// Filter by state
		$core = $this->getState('filter.core');

		if (is_numeric($core))
		{
				if ($core)
				{
					//orWhere
					$query->where('JSON_EXTRACT(s.params, "$.core") >= 1');
					$query->orWhere('JSON_EXTRACT(s.params, "$.show_in_sla_list") >= 1');
				}
				else
				{
					$query->where('JSON_EXTRACT(s.params, "$.show_in_sla_list") is null');
					$query->where('JSON_EXTRACT(s.params, "$.core") is null');
				}
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 's.id');
		$orderDirn = $this->state->get('list.direction', 'desc');

		if (!in_array(strtoupper($orderDirn), array('ASC', 'DESC', '')))
		{
			$orderDirn = 'DESC';
		}

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}
}
