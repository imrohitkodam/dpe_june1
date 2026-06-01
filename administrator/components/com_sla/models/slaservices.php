<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\Data\DataObject;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;

JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);

/**
 * Methods supporting a list of records.
 *
 * @since  1.0.0
 */
class SlaModelSlaServices extends ListModel
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
				'id', 'sr.id',
				'sla_id', 'sr.sla_id',
				'sla_activity_type_id', 'sr.sla_activity_type_id',
				'title', 'sr.title',
				'ideal_time', 'sr.ideal_time',
				'ordering', 'sr.ordering',
				'state', 'sr.state',
				'created_by', 'sr.created_by',
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
		$query->select(array('sr.*', 'sl.title as sla_title', 'sat.title as sla_activity_type_title', 'users.name as uname'));
		$query->from($db->quoteName('#__tj_sla_services', 'sr'));
		$query->join('INNER', $db->quoteName('#__tj_slas', 'sl') . ' ON (' . $db->quoteName('sl.id') . ' = ' . $db->quoteName('sr.sla_id') . ')');
		$query->join('LEFT', $db->quoteName('#__tj_sla_activity_types', 'sat')
			. ' ON (' . $db->quoteName('sr.sla_activity_type_id') . ' = ' . $db->quoteName('sat.id') . ')');
		$query->join('LEFT', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('sr.created_by') . ' = ' . $db->quoteName('users.id') . ')');

		// Filter by dashboard_id
		$id = $this->getState('filter.id');

		if (!empty($id))
		{
			$query->where($db->quoteName('sr.id') . ' = ' . (int) $id);
		}

		// Filter by search in title.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('sr.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where('(sl.title LIKE ' . $search . ' OR sr.title LIKE ' . $search . ' OR sr.description LIKE ' . $search . ')');
			}
		}

		// Filter by sla_activity_type_id
		$slaActivityType = $this->getState('filter.sla_activity_type_id');

		if (!empty($slaActivityType))
		{
			$query->where($db->quoteName('sr.sla_activity_type_id') . ' = ' . (int) $slaActivityType);
		}

		// Filter by created_by
		$created_by = $this->getState('filter.created_by');

		if (!empty($created_by))
		{
			$query->where($db->quoteName('sr.created_by') . ' = ' . (int) $created_by);
		}

		// Filter by state
		$state = $this->getState('filter.state');

		if (is_numeric($state))
		{
			$query->where('sr.state = ' . (int) $state);
		}
		elseif ($state === '')
		{
			$query->where('(sr.state = 0 OR sr.state = 1)');
		}

		// Filter by sla
		$sla = $this->getState('filter.sla_id');

		if (is_numeric($sla))
		{
			$query->where('sr.sla_id = ' . (int) $sla);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

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

	/**
	 * Function to update the sla id of services
	 *
	 * @param   integer  $licenceId  Licence Id
	 *
	 * @param   integer  $slaId      Sla Id
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function updateServicesSlaId($licenceId, $slaId)
	{
		if ($licenceId && $slaId)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('DISTINCT sla_service_id');
			$query->from($db->qn('#__tj_sla_activities'));
			$query->where($db->qn('license_id') . ' = ' . (int) $licenceId);
			$db->setQuery($query);
			$slaServiceIds = $db->loadObjectList();

			if ($slaServiceIds)
			{
				$slaServicesTable = SlaFactory::table('slaservices');

				foreach ($slaServiceIds as $slaServiceId)
				{
					$slaServicesTable->load($slaServiceId->sla_service_id);
					$slaServicesTable->sla_id = $slaId;
					$slaServicesTable->store();
				}
			}
		}
	}
}
