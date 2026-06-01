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
class TjCompetencyModelSkills extends ListModel
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
				'framework_id', 'a.framework_id',
				'title', 'a.title',
				'alias', 'a.alias',
				'state', 'a.state',
				'checked_out', 'a.checked_out',
				'checked_out_time', 'a.checked_out_time',
				'created_on', 'a.created_on',
				'created_by', 'a.created_by',
				'lft', 'a.lft',
				'rgt', 'a.rgt',
				'level', 'a.level',
				'path', 'a.path',
				'parent_id', 'a.parent_id',
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
	protected function populateState($ordering = 'a.lft', $direction = 'asc')
	{
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.search', 'filter_search', '', 'string'));
		$this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'int'));
		$this->setState('filter.level', $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level', '', 'int'));
		$this->setState('filter.path', $this->getUserStateFromRequest($this->context . '.filter.path', 'filter_path', '', 'string'));
		// $this->setState('filter.framework_id', $this->getUserStateFromRequest($this->context . '.filter.framework_id', 'filter_framework_id', '', 'int'));

		$app = Factory::getApplication();
		$app->getUserStateFromRequest($this->context . '.filter.framework_id', 'framework_id');
		/*$frameworkId = */

		// if ($frameworkId)
		// {
		// 	// $this->setState('filter.framework_id', $frameworkId);
		// }

		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since   1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.level');

		return parent::getStoreId($id);
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
		$query->select(array('a.*', 'IF(users.name IS NULL,"' . Text::_('COM_TJCOMPETENCY_BLOCKED_USER') . '",users.name) AS uname', 'fr.title as framework_title', 'COUNT(IF(b.state=1, 1, null)) as published_contents', 'COUNT(IF(b.state <> 1, 1, null)) as unpublished_contents'));
		$query->from($db->quoteName('#__tjcompetency_skills', 'a'));
		$query->join('LEFT', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('a.created_by') . ' = ' . $db->quoteName('users.id') . ')');
		$query->join('LEFT', $db->quoteName('#__tjcompetency_skill_content_map', 'b') . ' ON (' . $db->quoteName('b.skill_id') . ' = ' . $db->quoteName('a.id') . ')');
		$query->join('INNER', $db->quoteName('#__tjcompetency_frameworks', 'fr') . ' ON (' . $db->quoteName('a.framework_id') . ' = ' . $db->quoteName('fr.id') . ')');

		$query->where($db->quoteName('a.id') . ' <> 1');

		// Filter by id
		$id = $this->getState('filter.id');

		if (!empty($id))
		{
			$query->where($db->quoteName('a.id') . ' = ' . (int) $id);
		}

		// Filter on the Parent id.
		if ($parentId = $this->getState('filter.parent_id'))
		{
			$query->where('a.parent_id = ' . (int) $parentId);
		}

		// Filter on the Path.
		if ($path = $this->getState('filter.path'))
		{
			$query->where($db->qn('a.path') . ' = ' . $db->quote($path));
		}

		// Filter on the level.
		if ($level = $this->getState('filter.level'))
		{
			$query->where('a.level <= ' . (int) $level);
		}

		// Filter on the framework.
		$frameworkId = $this->getState('filter.framework_id');

		if (is_numeric($frameworkId))
		{
			$query->where('a.framework_id = ' . (int) $frameworkId);
		}
		elseif (is_array($frameworkId))
		{
			$frameworkId = ArrayHelper::toInteger($frameworkId);
			$frameworkId = implode(',', $frameworkId);
			$query->where('a.framework_id IN (' . $frameworkId . ')');
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
				$query->where('(a.title LIKE ' . $search . ' )');
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
			$query->where('(a.state = 0 OR a.state = 1)');
		}

		// Add the list ordering clause.
		$orderCol = $this->getState('list.ordering', 'a.lft');
		$orderDirn = $db->escape($this->getState('list.direction', 'ASC'));

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		$query->group('a.id,
				a.title'
		);

		return $query;
	}

	/**
	 * Method to get a list of skills.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		if (!empty($items))
		{
			foreach ($items as $item)
			{
				$item->userCount = $this->getUserCount($item->id);
			}
		}

		return $items;
	}

	/**
	 * Function for get total user for skill.
	 *
	 * @param   STRING  $name    model name
	 *
	 * @return  object  The model.
	 *
	 * @since  1.0.0
	 */
	public function getUserCount($skillId)
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);

		$query->select('distinct a.user_id');
		$query->from($db->quoteName('#__tjcompetency_skill_user_content_map', 'a'));
		$query->join('LEFT', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('a.user_id') . ' = ' . $db->quoteName('u.id') . ')');

		$query->where($db->qn('a.skill_id') . ' = ' . $db->quote($skillId));

		$db->setQuery($query);
		$db->execute();

		return $db->getNumRows();
	}
}
