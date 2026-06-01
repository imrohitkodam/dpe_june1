<?php
/**
 * @package     JLike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;

/**
 * JLike Model extendedTodos
 *
 * @since  __DEPLOY_VERSION__
 */
class JLikeModelExtendedTodos extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'title','a.title',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to get a \JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  \JDatabaseQuery  A \JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getListQuery()
	{
		$db      = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('a.*,b.*,c.name as userName');
		$query->from($db->qn('#__jlike_todos', 'a'));
		$query->leftJoin($db->quoteName('#__jlike_todos_extended', 'b') . ' ON (' . $db->quoteName('a.id') . ' = ' . $db->quoteName('b.todo_id') . ')');
		$query->innerJoin($db->quoteName('#__users', 'c') . ' ON (' . $db->quoteName('a.assigned_to') . ' = ' . $db->quoteName('c.id') . ')');
		$query->where($db->qn('a.type') . ' = "assign"');
		$query->where($db->qn('c.block') . ' = 0');

		$contentId = $this->getState('filter.contentId');

		if (is_numeric($contentId))
		{
			$query->where($db->qn('a.content_id') . ' = ' . (int) $contentId);
		}

		$used = $this->getState('filter.used');

		if (is_numeric($used))
		{
			if ($used)
			{
				$query->where($db->qn('b.used') . ' = ' . (int) $used);
			}
			else
			{
				$query->where("(" . $db->qn('b.used') . ' = ' . (int) $used . " OR b.todo_id IS NULL)");
			}
		}

		$read = $this->getState('filter.read');

		if (is_numeric($read))
		{
			if ($read)
			{
				$query->where($db->qn('b.read') . ' = ' . (int) $read);
			}
			else
			{
				$query->where("(" . $db->qn('b.read') . ' = ' . (int) $read . " OR b.todo_id IS NULL)");
				
				// DPE Hack to show the only data of compliance manager which are completed and read is 0
				if ($read == '0')
				{
					$query->where($db->qn('a.status') . ' = "I"' );
				}
			}
		}

		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
			$query->where('c.name LIKE ' . $search);
		}

		// Dpe Hack to get  the  user details with group by 
		$query->group($db->quoteName('a.assigned_to'));
		$query->order($db->quoteName('a.id') . ' DESC');

		return $query;
	}
}
