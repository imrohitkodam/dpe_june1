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
use Joomla\CMS\MVC\Model\ListModel;

/**
 * Methods supporting a list of records.
 *
 * @since  1.0.0
 */
class TjCompetencyModelFrameworks extends ListModel
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
				'id', 'cf.id',
				'title', 'cf.title',
				'state', 'cf.state',
				'created_by', 'cf.created_by',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Ordering
	 * @param   string  $direction  Ordering dir
	 *
	 * @since    1.6
	 *
	 * @return  void
	 */
	protected function populateState($ordering = 'cf.id', $direction = 'desc')
	{
		parent::populateState($ordering, $direction);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   JDatabaseQuery
	 *
	 * @since    1.0.0
	 */
	protected function getListQuery()
	{
		// Initialize variables.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Create the base select statement.
		$query->select(array('cf.*', 'IF(users.name IS NULL,"' . Text::_('COM_TJCOMPETENCY_BLOCKED_USER') . '",users.name) AS uname', 'COUNT(cs.id) as skill_count'));
		$query->from($db->quoteName('#__tjcompetency_frameworks', 'cf'));
		$query->join('LEFT', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('cf.created_by') . ' = ' . $db->quoteName('users.id') . ')');
		$query->join('LEFT', $db->quoteName('#__tjcompetency_skills', 'cs') . ' ON (' . $db->quoteName('cf.id') . ' = ' . $db->quoteName('cs.framework_id') . ')');

		// Filter by id
		$id = $this->getState('filter.id');

		if (!empty($id))
		{
			$query->where($db->quoteName('cf.id') . ' = ' . (int) $id);
		}

		// Filter by search in title.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('cf.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where('(cf.title LIKE ' . $search . ' )');
			}
		}

		// Filter by created_by
		$created_by = $this->getState('filter.created_by');

		if (!empty($created_by))
		{
			$query->where($db->quoteName('cf.created_by') . ' = ' . (int) $created_by);
		}

		// Filter by state
		$state = $this->getState('filter.state');

		if (is_numeric($state))
		{
			$query->where('cf.state = ' . (int) $state);
		}
		elseif ($state === '')
		{
			$query->where('(cf.state = 0 OR cf.state = 1)');
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		// Group by on Skills for JOIN with component tables to count items
		$query->group('cf.id,
				cf.title'
		);

		return $query;
	}
}
