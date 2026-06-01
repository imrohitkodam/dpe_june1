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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\MVC\Model\ListModel;

JLoader::register('RSTicketsProHelper', JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/rsticketspro.php');

/**
 * Methods supporting a list of rsTickets.
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeModelRsticketspro extends ListModel
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
			$rsTicketShowUserInfo = RSTicketsProHelper::getConfig('show_user_info');

			$config['filter_fields'] = array(
			'id', 'a.id',
			'date', 'a.date',
			'last_reply', 'a.last_reply',
			'status_id', 'a.status_id',
			'priority_id', 'a.priority_id',
			'subject', 'a.subject',
			'customer_id', 'a.customer_id',
			'staff_id', 'a.staff_id',
			'time_spent', 'a.time_spent',
			'agencyId', 'rsxref.agency_id',
			'agencies', 'ticketPriority', 'cluster.name',
			'c.' . $rsTicketShowUserInfo , 's.' . $rsTicketShowUserInfo ,
			'ticketStatus', 'st.name', 'pr.name'
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
			$session = Factory::getSession();

		if (!empty($filters))
		{
			foreach ($filters as $name => $value)
			{
				$this->setState('filter.' . $name, $value);
			}
		}

		$myTickets = $this->state->get('filter.myTickets');
		$input = Factory::getApplication()->input;

		if ($myTickets || ($session->set('myTickets',  $myTickets) =='on') || $input->get('filter')['myticket'])
		{

			$this->setState('filter.myTickets', $myTickets);
			$session->set('myTickets',  $myTickets);

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
		$user = Factory::getUser();
		$isSuperUser = $user->authorise('core.admin');
		$params = ComponentHelper::getParams('com_multiagency');
		$dpeParams = ComponentHelper::getParams('com_dpe');
		$ticketClosedStatusId = $dpeParams->get('rsticketstatus');

		// Is dpeadmin and superuser
		JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
		$adminRole = $params->get('multyagency_admin_role_id', '0', 'INT');
		$dpeAdmin = RBACL::getRoleByUser($user->id, 'com_multiagency', 0);

		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$agencies = $this->state->get('filter.agencies', strtolower(Text::_('COM_MULTIAGENCY_SELECT_ALL_AGENCY')));
		
		$input = Factory::getApplication()->input;
		$myTickets = ($this->state->get('filter.myTickets') || $input->get('filter')['myticket'])?'on':'';

		$rsTicketShowUserInfo = RSTicketsProHelper::getConfig('show_user_info');

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'DISTINCT a.*'));
		$query->from('`#__rsticketspro_tickets` AS a');

		// Join over the Joomla user table for getting customer info
		$query->select($db->qn('c.' . $rsTicketShowUserInfo, 'customer'));
		$query->join('LEFT', '#__users AS c ON a.customer_id = c.id');

		// Join over the Joomla user table for getting staff info
		$query->select($db->qn('s.' . $rsTicketShowUserInfo, 'staff'));
		$query->join('LEFT', '#__users AS s ON a.staff_id = s.id');

		// Join over the rsticketspro_statuses table
		$query->select('st.name AS status');
		$query->join('LEFT', '#__rsticketspro_statuses AS st ON a.status_id = st.id');

		if (($ticketClosedStatusId) && !$this->state->get('filter.ticketStatus'))
		{
			$query->where('st.id != ' . (int) $ticketClosedStatusId);	
		}

		// Join over the rsticketspro_priorities table
		$query->select('pr.name AS priority');
		$query->join('LEFT', '#__rsticketspro_priorities AS pr ON a.priority_id = pr.id');

		// Join over the rsticket_integration_xref table
		$query->select('rsxnote.id as note, rsxref.agency_id AS agencyId');
		$query->select('rsxref.emails');
		$query->join('LEFT', '#__rsticket_integration_xref AS rsxref ON a.id = rsxref.ticket_id');

		// Join over the tjmultiagency_multiagency table
		$query->select('cluster.name AS agencyTitle');
		$query->join('LEFT', '#__tj_clusters AS cluster ON rsxref.agency_id = cluster.id');

		$query->join('LEFT', '#__rsticketspro_ticket_notes AS rsxnote ON rsxnote.ticket_id = rsxref.ticket_id');

		// Search Filter
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where(
				'( a.subject LIKE ' . $search .
				' OR a.code LIKE ' . $search .
				' OR cluster.name LIKE ' . $search .
				' OR pr.name LIKE ' . $search .
				' OR st.name LIKE ' . $search .
				' OR c.' . $rsTicketShowUserInfo . ' LIKE ' . $search .
				' OR s.' . $rsTicketShowUserInfo . ' LIKE ' . $search .
				' )'
				);
			}
		}

		if (is_array($this->state->get('filter.ticketStatus')))
		{
			$ticketstatusValue = implode(',', $this->state->get('filter.ticketStatus'));
		}


		// Filtering by Ticket Status
		if ($this->state->get('filter.ticketStatus') != '')
		{
			$query->where('a.status_id IN  (' . $ticketstatusValue .')');
		}

		// Filtering by Ticket Priority
		if ($this->state->get('filter.ticketPriority') != '')
		{
			$query->where('a.priority_id = ' . (int) $this->state->get('filter.ticketPriority'));
			
		}

			// Filter by tags
			$agencyTags = $this->getState('filter.tags');

			if (is_array($agencyTags))
			{
				foreach($agencyTags as $key => $agencyTag)
				{
					if (!is_int($agencyTag))
					{
						$agencyTags[$key] = (int) $agencyTag;
					}
				}
			 }
		
		$params     = ComponentHelper::getParams('com_multiagency');
		$multiagencyTrusteeRoleId = (int) $params->get('multiagency_trustee_group');
		$isTrustee = in_array($multiagencyTrusteeRoleId, $user->groups);
		$orgAdminRoleId           = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
		$isOrgAdmin 			  = in_array($orgAdminRoleId, $user->groups);
		
		// DPE Hack for tag filter
		if (!empty($agencyTags) && ($user->authorise('core.manageall', 'com_cluster') || $isTrustee || $isOrgAdmin))
		{	
			$query->join('LEFT', $db->qn('#__contentitem_tag_map', 'tagsMap') . ' ON (' . $db->qn('tagsMap.content_item_id') . ' = ' . $db->qn('cluster.client_id') . ')');
			
			$query->where($db->quoteName('tagsMap.tag_id') . " IN ( " . implode(',', $agencyTags) .')');
			$query->where($db->quoteName('tagsMap.type_alias') . " = 'com_multiagency.multiagency' " );

		}

		
		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.id');
		$orderDirn = $this->state->get('list.direction', 'DESC');

		// Code execute for those users(Orphan) who doesn't having any agency
		if ($isSuperUser || in_array($adminRole, $dpeAdmin))
		{
			if (!empty($agencies) && $agencies == strtolower(Text::_('COM_MULTIAGENCY_SELECT_NONE')))
			{
				$query->where($db->quoteName('a.agent') . ' = ' . $db->quote('RSTickets! Pro Cron'));
				$query->where($db->quoteName('rsxref.agency_id') . ' IS NULL');

				if ($myTickets)
				{
					$query->where($db->qn('a.customer_id') . '=' . $db->q($user->id));
				}

				$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

				return $query;
			}
		}

		// Filtering records by Agency
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');

		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusters         = $clusterUserModel->getUsersClusters($user->id);

		$allocatedAgency = array();
		$staffAgency = array();

		if (count($clusters) > 0)
		{
			foreach ($clusters as $cluster)
			{
				if (RBACL::authorise($user->id, 'com_cluster', 'core.view.all',  'com_multiagency', $cluster->cluster_id))
				{
					$staffAgency[] = $cluster->cluster_id;
				}
				else
				{
					$allocatedAgency[] = $cluster->cluster_id;
				}
			}
		}

		if (!empty($agencies) && in_array($agencies, $allocatedAgency))
		{
			$agencyCondition = 'rsxref.agency_id = ' . (int) $agencies;
		}
		elseif (!empty($agencies) && in_array($agencies, $staffAgency))
		{
			$staffAgencyCondition = 'rsxref.agency_id = ' . (int) $agencies;
		}
		elseif (!empty($agencies) && $agencies == strtolower(Text::_('COM_MULTIAGENCY_SELECT_ALL_AGENCY')))
		{
			if (!$isSuperUser && !in_array($adminRole, $dpeAdmin))
			{
				if (!empty($staffAgency))
				{
					$staffAgencyCondition = 'rsxref.agency_id  IN ( ' . implode(',', $staffAgency) . ')';
				}

				if (!empty($allocatedAgency))
				{
					$agencyCondition = 'rsxref.agency_id  IN ( ' . implode(',', $allocatedAgency) . ')';
				}
			}
		}

		// Below query causes when clear the agency filter, its set to 0

		/*
		else
		{
			$query->where($db->qn('rsxref.agency_id') . ' = ' . (int) $agencies . ' AND ' . $db->qn('c.id') . ' = ' . $user->id);
		}
		*/

		$isStaff = RSTicketsProHelper::isStaff();

		if ($isStaff)
		{
			/* In DPE site  super user will be a staff of a department who can support to the ticket.
			So in that case if logddin user is super usper + staff member then should able to see all tickets which are present on*/
			if ($isSuperUser || in_array($adminRole, $dpeAdmin))
			{
				JLoader::register('RsticketsproModelDepartments', JPATH_ADMINISTRATOR . '/components/com_rsticketspro/models/departments.php');
				$rsticketsproModelDepartments = BaseDatabaseModel::getInstance('Departments', 'RsticketsproModel', array('ignore_request' => true));
				$departments = $rsticketsproModelDepartments->getItems();
				$departmentsListArr = array();

				if (!empty($departments))
				{
					foreach ($departments as $department)
					{
						$departmentsListArr[] = $department->id;
					}
				}

				$departmentsList = '"' . implode('","', $departmentsListArr) . '"';
			}
			else
			{
				$departmentsList = '"' . implode('","', RSTicketsProHelper::getCurrentDepartments()) . '"';
			}

			// This is DPE specific condition because here super user is staff who has assist the tickets.
			if ($myTickets)
			{
				$query->where($db->qn('a.department_id') . ' IN (' . $departmentsList . ')');
				$query->where($db->qn('a.customer_id') . ' = ' . $db->q($user->id));
			}

			if ($agencyCondition)
			{
				$query->where($agencyCondition);
			}
		}
		else
		{
			if ($myTickets)
			{
				$query->where($db->qn('a.customer_id') . '=' . $db->q($user->id));

					if (!empty($staffAgencyCondition) && !empty($agencyCondition))
					{
						$query->where('(' . $staffAgencyCondition . ' OR ' . $agencyCondition . ')');
					}
					elseif ($agencyCondition)
					{
						$query->where($agencyCondition);
					}
					elseif ($staffAgencyCondition)
					{
						$query->where($staffAgencyCondition);
					}
			}
			else
			{
				// Check logged in user is staff
				$queryString = '(';

				// If staff then show only ticket created by him
				if (!empty($staffAgencyCondition))
				{
					$queryString .= '(' . $staffAgencyCondition . ' AND ' . $db->qn('a.customer_id') . '=' . $db->q($user->id) . ')';
				}

				if ($agencies != strtolower(Text::_('COM_MULTIAGENCY_SELECT_ALL_AGENCY')))
				{
					if (!empty($staffAgencyCondition) && !empty($agencyCondition))
					{
						$queryString .= ' OR ' . $agencyCondition;
					}
					elseif (empty($staffAgencyCondition) && !empty($agencyCondition))
					{
						$queryString .= $agencyCondition;
					}
					else
					{
						$queryString .= ' OR (' . $db->qn('rsxref.emails') . ' LIKE ' . $db->q('%' . $user->email . '%') .
							' AND ' . $db->qn('rsxref.agency_id') . ' = ' . (int) $agencies . ')';
					}
				}
				else
				{
					if (!empty($staffAgencyCondition) && !empty($agencyCondition))
					{
						 $queryString .= ' OR ' . $agencyCondition;
					}
					elseif (!empty($agencyCondition))
					{
						$queryString .= $agencyCondition;
					}

					$queryString .= ' OR (' . $db->qn('rsxref.emails') . ' LIKE ' . $db->q('%' . $user->email . '%') . ')';
				}

				$queryString .= ')';

				$query->where($queryString);
			}
		}

		// Assigned to filter
		$assignedTo = $this->getState('filter.assigned_to');

		if ($assignedTo != '')
		{
			$query->where($db->qn('a.staff_id') . '=' . (int) $assignedTo);
		}

		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));
		$query->group('a.id'); // Prevent duplicate tickets due to multiple notes

		return $query;
	}

	/**
	 * Method to get School information by Ticket Id.
	 *
	 * @param   Integer  $ticketId  rsticket ticket ID
	 *
	 * @return Array
	 *
	 * @throws Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getTicketXrefData($ticketId)
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select($db->qn(array('rsXref.agency_id', 'rsXref.emails','cluster.name','rsXref.dpe_cc_emails', 'rsXref.user_cc_emails','rsXref.dpe_allow_admin')));
		$query->from($db->qn('#__rsticketspro_tickets', 'rst'));
		$query->join('LEFT', $db->qn('#__rsticket_integration_xref', 'rsXref') . ' ON (' . $db->qn('rst.id') . ' = ' . $db->qn('rsXref.ticket_id') . ')');
		$query->join('LEFT', $db->qn('#__tj_clusters', 'cluster') .
		' ON (' . $db->qn('rsXref.agency_id') . ' = ' . $db->qn('cluster.id') . ')');
		$query->where($db->qn('rst.id') . ' = ' . (int) $ticketId);
		$db->setQuery($query);

		return $db->loadAssoc();
	}

	/**
	 * Method to check is loggdin user has delete access or not.
	 *
	 * @return Object
	 *
	 * @throws Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getPermissions()
	{
		JLoader::register('RsticketsproModelRsticketspro', JPATH_SITE . '/components/com_rsticketspro/models/rsticketspro.php');
		$rsticketsproModelRsticketspro = BaseDatabaseModel::getInstance('Rsticketspro', 'RsticketsproModel', array('ignore_request' => true));
		$permissionData = $rsticketsproModelRsticketspro->getPermissions();

		return $permissionData;
	}

	/**
	 * Method to get activated licesce school(s)
	 *
	 * @param   Integer  $userId  user id
	 * 
	 * @return Object
	 *
	 * @throws Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getActiveSchools($userId)
	{
		if ($userId)
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			// Query to get activated licesce school(s) of logged in user
			$query->select('DISTINCT c.id, c.title');
			$query->from($db->qn('#__users', 'a'));
			$query->join('INNER', $db->qn('#__tjsu_users', 'b') .
			' ON (' . $db->qn('a.id') . ' = ' . $db->qn('b.user_id') . ' AND ' . $db->qn('b.client') . ' = "com_multiagency" )');
			$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'c') . ' ON (' . $db->qn('b.client_id') . ' = ' . $db->qn('c.id') . ')');
			$query->join('INNER', $db->qn('#__tjmultiagency_licences', 'd') . ' ON (' . $db->qn('d.multiagency_id') . ' = ' . $db->qn('c.id') . ' )');
			$query->where($db->quoteName('d.state') . ' = 1');
			$query->where($db->quoteName('a.id') . ' = ' . $db->quote((int) $userId));
			$db->setQuery($query);

			return $db->loadObjectList();
		}
	}

	/**
	 * Get Items functions
	 *
	 * @return	Object
	 *
	 * @since	1.0.0
	 */
	public function getItems()
	{
		$items          = parent::getItems();
		$dpeParams      = ComponentHelper::getParams('com_dpe');
		$platformField  = $dpeParams->get('platformField', 0);
		$platformValues = $dpeParams->get('platformShortValues');

		JLoader::import("/components/com_sla/includes/cluster", JPATH_ADMINISTRATOR);
		JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);

		foreach ($items as $item)
		{
			if ($item->agencyId)
			{
				$clusterTable = ClusterFactory::table("Clusters");
				$clusterTable->load(array('id' => $item->agencyId));

				$dpeModel = DPE::model('schools', array('ignore_request' => true));
				$result   = $dpeModel->getPlatformValue($clusterTable->client_id, $platformField, $platformValues);

				if ($result)
				{
					$item->platform = $result;
				}
			}
		}

		return $items;
	}

	/**
	 * Get Tags of trustee
	 *
	 * @return	array of tags
	 *
	 */
	public function getTrusteeTags()
	{
		JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
		$dpeModel = DPE::model('school', array('ignore_request' => true));
		$params = ComponentHelper::getParams('com_multiagency');
		$multiagency_trustee_group = (int) $params->get('multiagency_trustee_group');
		
		return $dpeModel->getAgencyTags($multiagency_trustee_group); 
	}
}
