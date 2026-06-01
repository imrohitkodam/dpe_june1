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
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * Methods supporting a list of records.
 *
 * @since  1.0.0
 */
class TjCompetencyModelSkillUserMaps extends ListModel
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
				'a.max_sequence_number', 'a.max_sequence_number',
				'user_id', 'a.user_id',
				'skill_title', 'b.skill_title',
				'skill_id', 'a.skill_id',
				'scale_set_id', 'a.scale_set_id',
				'scale_set_title', 'a.scale_set_title',
				'c.title'
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
	protected function populateState($ordering = 'a.user_id', $direction = 'desc')
	{
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.search', 'filter_search', '', 'string'));

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
		$query->select(array('a.*', 'IF(u.name IS NULL,"' . Text::_('COM_TJCOMPETENCY_BLOCKED_USER') . '",u.name) AS user_name', 'c.title as scale_title'));
		$query->from($db->quoteName('#__tjcompetency_skill_user_map', 'a'));
		$query->join('INNER', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('a.user_id') . ' = ' . $db->quoteName('u.id') . ')');
		$query->join('INNER', $db->quoteName('#__tjcompetency_scales', 'c') . ' ON (' . $db->quoteName('a.scale_set_id') . ' = ' . $db->quoteName('c.scale_set_id') . ' AND ' . $db->quoteName('a.max_sequence_number') . ' = ' . $db->quoteName('c.sequence_number') . '  )' );

		// Filter by user
		$userId = $this->getState('filter.user_id');

		if (is_numeric($userId))
		{
			$query->where('a.user_id = ' . $type . (int) $userId);
		}
		elseif (is_array($userId))
		{
			$userId = ArrayHelper::toInteger($userId);
			$userId = implode(',', $userId);
			$query->where('a.user_id IN (' . $userId . ')');
		}

		// Filter on the Skill.
		$skillId = $this->getState('filter.skill_id');

		if (is_numeric($skillId) && !empty($skillId))
		{
			$query->where('a.skill_id = ' . (int) $skillId);
		}
		elseif (is_array($skillId))
		{
			$skillId = ArrayHelper::toInteger($skillId);
			$skillId = implode(',', $skillId);
			$query->where('a.skill_id IN (' . $skillId . ')');
		}

		// Filter by search in title.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
			$query->where('(a.skill_title LIKE ' . $search . ' OR u.name LIKE ' . $search . ')');
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
