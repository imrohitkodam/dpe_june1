<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\ListModel;

/**
 * Methods supporting a list of records.
 *
 * @since  1.0.0
 */
class TmtModelTestAttendees extends ListModel
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
				'id', 'ta.id',
				'invite_id', 'ta.invite_id',
				'test_id', 'ta.test_id',
				'user_id', 'ta.user_id',
			);
		}

		parent::__construct($config);
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
		$query->select(array('ta.*', 'users.name as uname'));
		$query->from($db->quoteName('#__tmt_tests_attendees', 'ta'));
		$query->join('LEFT', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('ta.user_id') . ' = ' . $db->quoteName('users.id') . ')');

		// Filter by dashboard_id
		$id = $this->getState('filter.id');

		if (!empty($id))
		{
			$query->where($db->quoteName('ta.id') . ' = ' . (int) $id);
		}

		// Filter by invite_id
		$inviteId = $this->getState('filter.invite_id');

		if (!empty($inviteId))
		{
			$query->where($db->quoteName('ta.invite_id') . ' = ' . (int) $inviteId);
		}

		// Filter by test_id
		$testId = $this->getState('filter.test_id');

		if (!empty($testId))
		{
			$query->where($db->quoteName('ta.test_id') . ' = ' . (int) $testId);
		}

		// Filter by user_id
		$userId = $this->getState('filter.user_id');

		if (!empty($userId))
		{
			$query->where($db->quoteName('ta.user_id') . ' = ' . (int) $userId);
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
