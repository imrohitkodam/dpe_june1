<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\Database\DatabaseQuery;

class EventbookingModelMitems extends RADModelList
{
	/**
	 * Instantiate the model.
	 *
	 * @param   array  $config  configuration data for the model
	 */
	public function __construct($config = [])
	{
		$config['search_fields'] = ['tbl.name', 'tbl.title', 'tbl.title_en'];

		parent::__construct($config);

		$this->state->insert('filter_group', 'string', 0);
	}

	/**
	 * Builds a WHERE clause for the query
	 *
	 * @param   DatabaseQuery  $query
	 *
	 * @return RADModelList
	 */
	protected function buildQueryWhere(DatabaseQuery $query)
	{
		$db = $this->getDbo();

		if ($this->state->filter_group)
		{
			$query->where('tbl.group = ' . $db->quote($this->state->filter_group));
		}

		return parent::buildQueryWhere($query);
	}

	/**
	 * Apply search filter
	 *
	 * @param   DatabaseQuery  $query
	 */
	protected function applySearchFilter(DatabaseQuery $query)
	{
		$state = $this->state;

		if (stripos($state->filter_search, 'id:') === 0)
		{
			$query->where('tbl.id = ' . (int) substr($state->filter_search, 3));
		}
		else
		{
			$db     = $this->getDbo();
			$search = $db->quote('%' . $db->escape($state->filter_search, true) . '%', false);

			if (is_array($this->searchFields))
			{
				$whereOr = [];

				foreach ($this->searchFields as $searchField)
				{
					$whereOr[] = "LOWER($searchField) LIKE " . $search;
				}

				$whereOr[] = 'tbl.name IN (SELECT message_key FROM #__eb_messages WHERE message LIKE ' . $search . ')';

				$query->where('(' . implode(' OR ', $whereOr) . ') ');
			}
		}
	}

	/**
	 * Override buildQueryOrder method to have featured items displayed first
	 *
	 * @param   DatabaseQuery  $query
	 *
	 * @return RADModelList
	 */
	protected function buildQueryOrder(DatabaseQuery $query)
	{
		$query->order('tbl.featured DESC');

		return parent::buildQueryOrder($query);
	}
}
