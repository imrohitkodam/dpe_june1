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
 * Methods supporting a list of recordsat.
 *
 * @since  1.0.0
 */
class SlaModelSlaActivityTypes extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settingsat.
	 *
	 * @see        JController
	 * @since      1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'sat.id',
				'title', 'sat.title',
				'ordering', 'sat.ordering',
				'state', 'sat.state',
				'created_by', 'sat.created_by',
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
		// Initialize variablesat.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Create the base select statement.
		$query->select(array('sat.*','users.name as uname'));
		$query->from($db->quoteName('#__tj_sla_activity_types', 'sat'));
		$query->join('LEFT', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('sat.created_by') . ' = ' . $db->quoteName('users.id') . ')');

		// Filter by Id
		$id = $this->getState('filter.id');

		if (!empty($id))
		{
			if (is_array($id))
			{
				$query->where($db->quoteName('sat.id') . 'IN (' . implode(',', $db->q($id)) . ')');
			}
			else
			{
				$query->where($db->quoteName('sat.id') . ' = ' . (int) $id);
			}
		}

		// Filter by search in title.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('sat.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where('(sat.title LIKE ' . $search . ' OR sat.description LIKE ' . $search . ' )');
			}
		}

		// Filter by created_by
		$created_by = $this->getState('filter.created_by');

		if (!empty($created_by))
		{
			$query->where($db->quoteName('sat.created_by') . ' = ' . (int) $created_by);
		}

		// Filter by state
		$state = $this->getState('filter.state');

		if (is_numeric($state))
		{
			$query->where('sat.state = ' . (int) $state);
		}
		elseif ($state === '')
		{
			$query->where('(sat.state = 0 OR sat.state = 1)');
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'sat.id');
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
