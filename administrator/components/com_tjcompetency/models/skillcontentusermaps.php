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
class TjCompetencyModelSkillContentUserMaps extends ListModel
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
				'id', 'a.id',
				'user_id', 'a.user_id',
				'framework_id', 'b.framework_id',
				'skill_id', 'a.skill_id',
				'scale_id', 'a.scale_id',
				'client', 'a.client',
				'client_id', 'a.client_id',
				'state', 'a.state',
				'created_by', 'a.created_by',
				'created_on', 'a.created_on',
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
	protected function populateState($ordering = 'a.id', $direction = 'desc')
	{
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.search', 'filter_search', '', 'string'));
		$this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string'));

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
		$query->select(array('a.*', 'IF(users.name IS NULL,"' . Text::_('COM_TJCOMPETENCY_BLOCKED_USER') . '",users.name) AS uname', 'IF(u.name IS NULL,"' . Text::_('COM_TJCOMPETENCY_BLOCKED_USER') . '",u.name) AS user_name', 'b.title as skill_title', 'c.title as scale_title'));
		$query->select(array('d.title as framework_title'));
		$query->from($db->quoteName('#__tjcompetency_skill_user_content_map', 'a'));
		$query->join('LEFT', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('a.created_by') . ' = ' . $db->quoteName('users.id') . ')');
		$query->join('LEFT', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('a.user_id') . ' = ' . $db->quoteName('u.id') . ')');
		$query->join('LEFT', $db->quoteName('#__tjcompetency_skills', 'b') . ' ON (' . $db->quoteName('a.skill_id') . ' = ' . $db->quoteName('b.id') . ')');
		$query->join('LEFT', $db->quoteName('#__tjcompetency_scales', 'c') . ' ON (' . $db->quoteName('a.scale_id') . ' = ' . $db->quoteName('c.id') . ')');
		$query->join('LEFT', $db->quoteName('#__tjcompetency_frameworks', 'd') . ' ON (' . $db->quoteName('b.framework_id') . ' = ' . $db->quoteName('d.id') . ')');

		// Filter by id
		$id = $this->getState('filter.id');

		if (!empty($id))
		{
			$query->where($db->quoteName('a.id') . ' = ' . (int) $id);
		}

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

		// Filter on the framework.
		$frameworkId = $this->getState('filter.framework_id');

		if (is_numeric($frameworkId))
		{
			$query->where('b.framework_id = ' . (int) $frameworkId);
		}
		elseif (is_array($frameworkId))
		{
			$frameworkId = ArrayHelper::toInteger($frameworkId);
			$frameworkId = implode(',', $frameworkId);
			$query->where('b.framework_id IN (' . $frameworkId . ')');
		}

		// Filter on the Client.
		$client = $this->getState('filter.client');

		if (!is_array($client) && !empty($client))
		{
			$query->where('a.client = ' . $db->quote($client));
		}
		elseif (is_array($client))
		{
			$client = array_map(array($db, 'quote'), $client);
			$client = implode(',', $client);
			$query->where('a.client IN (' . $client . ')');
		}

		// Filter on the client_id.
		$clientId = $this->getState('filter.client_id');

		if (is_numeric($clientId) && !empty($clientId))
		{
			$query->where('a.client_id = ' . (int) $clientId);
		}
		elseif (is_array($clientId))
		{
			$clientId = ArrayHelper::toInteger($clientId);
			$clientId = implode(',', $clientId);
			$query->where('a.client_id IN (' . $clientId . ')');
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

		// Filter on the scale.
		$scaleId = $this->getState('filter.scale_id');

		if (is_numeric($scaleId) && !empty($scaleId))
		{
			$query->where('a.scale_id = ' . (int) $skillId);
		}
		elseif (is_array($scaleId))
		{
			$scaleId = ArrayHelper::toInteger($scaleId);
			$scaleId = implode(',', $scaleId);
			$query->where('a.scale_id IN (' . $scaleId . ')');
		}

		// Filter by search in title.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where('(b.title LIKE ' . $search . ' OR c.title LIKE ' . $search . ' OR users.name LIKE ' . $search . ')');
			}
		}

		// Filter by created_by
		$created_by = $this->getState('filter.created_by');

		if (!empty($created_by))
		{
			$query->where($db->quoteName('a.created_by') . ' = ' . (int) $created_by);
		}

		// Filter by state
		$state = $this->getState('filter.state');

		if (is_numeric($state))
		{
			$query->where('a.state = ' . (int) $state);
		}
		elseif ($state === '')
		{
			$query->where('(a.state = 0 OR a.state = 1 OR a.state = 3)');
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

	/**
	 * Fetch Records
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		if (!empty($items))
		{
			foreach ($items as $key => &$item)
			{
				$item->contentName = TjCompetency::SkillContentMap()::getContentName($item->client, $item->client_id);
				$item->contentType = ucfirst($item->client);
			}
		}

		return $items;
	}
}
