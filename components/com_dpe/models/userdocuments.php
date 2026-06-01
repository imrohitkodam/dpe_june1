<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\Data\DataObject;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;

use Joomla\CMS\User\User;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * Methods supporting a list of rsTickets.
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeModelUserDocuments extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JModels
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
			'b.name',
			'agencies', 'documents',
			'ticketStatus', 'assigncount'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   rsticket order
	 * @param   string  $direction  rsticket order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function populateState($ordering = 'a.id', $direction = 'DESC')
	{
		$filters = Factory::getApplication()->getUserStateFromRequest($this->context . '.filter', 'filter', array(), 'array');

		if (!empty($filters))
		{
			foreach ($filters as $name => $value)
			{
				$this->setState('filter.' . $name, $value);
			}
		}

		parent::populateState($ordering, $direction);
	}

	/**
	 * Get the query for retrieving a list of coupons to the model state.
	 *
	 * @return  \DataObjectbaseQuery
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getListQuery()
	{
		$jinput = Factory::getApplication()->input;
		$documentId = $jinput->get('document_id', '', 'INT');
		$user = Factory::getUser();
		$canManageMaterial = $user->authorise('core.manage.material', 'com_tjlms');
		$create = $user->authorise('core.create', 'com_dpe');

		// Check if current user is super user
		$currentUserSuperUser = $user->authorise('core.admin');

		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$subQuery = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'DISTINCT a.*'));
		$query->from($db->qn('#__jlike_todos', 'a'));

		$query->select('b.name');
		$query->join('INNER', $db->qn('#__users', 'b') . ' ON (' . $db->qn('b.id') . ' = ' . $db->qn('a.assigned_to') . ')');

		$query->join('INNER', $db->qn('#__tjsu_users', 'c') . ' ON (' . $db->qn('b.id') . ' = ' . $db->qn('c.user_id') . ')');

		$query->select("GROUP_CONCAT(distinct(d.title) SEPARATOR ', ') AS 'schools'");
		$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'd') . ' ON (' . $db->qn('c.client_id') . ' = ' . $db->qn('d.id')
		. ' AND ' . $db->qn('c.client') . " = 'com_multiagency' )");

		// Get document assigned count
		$subQuery->select('COUNT(f.assigned_to)');
		$subQuery->from($db->quoteName('#__jlike_todos', 'f'));
		$subQuery->where($db->quoteName('f.assigned_to') . ' = ' . $db->qn('b.id'));

		if (!$currentUserSuperUser && !$canManageMaterial)
		{
			$subQuery->where($db->qn('f.assigned_by') . '=' . (int) $user->id);
		}

		$query->select('(' . $subQuery . ') AS assigncount');

		$query->select('e.id');
		$query->join('INNER', $db->qn('#__jlike_content', 'e') . ' ON (' . $db->qn('a.content_id') . ' = ' . $db->qn('e.id') . ')');
		$query->where($db->qn('b.block') . ' = 0');

		if (!$currentUserSuperUser && !$canManageMaterial)
		{
			$query->where($db->qn('a.assigned_by') . '=' . (int) $user->id);
		}

		// Filter by search in organisation name
		$mainframe   	= Factory::getApplication();
		$search = $this->getState('filter.search');

		// Populate Agency and document
		$agencies = $this->getState('filter.agencies');
		$document = $this->getState('filter.documents');

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$MultiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel', array('ignore_request' => true));
		$params = ComponentHelper::getParams('com_multiagency');
		$memberRole = $params->get('member_role_id', '0', 'INT');

		// Get assigned agencies
		$agenciesList = $MultiagencyModel->getAllocatedAgencies($user->id);

		if (count($agenciesList) > 0)
		{
			$i = 1;

			$allocatedAgency = array();

			foreach ($agenciesList as $agency)
			{
				$allocatedAgency[] = $agency->id;
			}
		}

		if (!empty($agencies) && in_array($agencies, $allocatedAgency))
		{
			$query->where('d.id = ' . (int) $agencies);
		}

		if (!empty($document))
		{
			$query->where('e.id = ' . (int) $document);
		}
		else
		{
			$query->where('e.id = ' . (int) $documentId);
		}

		if (!empty($search))
		{
			// Escape the search token.
			$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));

			// Compile the different search clauses.
			$searches   = array();
			$searches[] = 'b.name LIKE ' . $search;

			// Add the clauses to the query.
			$query->where('(' . implode(' OR ', $searches) . ')');
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		$query->group('a.id');

		return $query;
	}

	/**
	 * Method to get an array of data items
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();

		return $items;
	}
}
