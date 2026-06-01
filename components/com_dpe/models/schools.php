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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

/**
 * Methods supporting a list of School Management.
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeModelSchools extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      __DEPLOY_VERSION__
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'ordering', 'a.ordering',
				'state', 'a.state',
				'created_by', 'a.created_by',
				'modified_by', 'a.modified_by',
				'title', 'a.title',
				'name', 'b.name',
				'phone_no', 'a.phone_no',
				'country_id', 'a.country_id',
				'state_id', 'a.state_id',
				'cluster_id', 'license_id', 'lead_consultant_id',
				'fv.value', 'sla.title', 'u.name',
				'l.end_date','agencyFilter'
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
	protected function populateState($ordering = 'a.title', $direction = 'ASC')
	{
		$this->setState('params', ComponentHelper::getParams('com_multiagency'));

		parent::populateState($ordering, $direction);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since    __DEPLOY_VERSION__
	 */
	protected function getListQuery()
	{
		$params      = $this->getState('params');
		$emailField  = (int) $params->get('schoolEmail', '0');
		$adminRole   = (int) $params->get('school_admin_role_id', '0');
		$app         = Factory::getApplication();
		$dpeParams   = ComponentHelper::getParams('com_dpe');
		$customField = $dpeParams->get('agencyFilterField', '0');

		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query
			->select(
				$this->getState(
					'list.select', 'DISTINCT a.id, a.title as school_name, sla.title as sla_name,
					u.name as lead_consultant, l.end_date as licence_end_date, fv.value as school_email, l.id as licence_id, l.state as licenceState, cl.id as cluster_id')
		);

		$query->from($db->qn('#__tjmultiagency_multiagency', 'a'));
		$query->join('LEFT', $db->qn('#__tjmultiagency_licences', 'l') . ' ON (' . $db->qn('l.multiagency_id') . ' = ' . $db->qn('a.id') . ')');

		$query->join('LEFT', $db->qn('#__fields_values', 'fv') . ' ON (' . $db->qn('fv.item_id') . ' = ' . $db->qn('a.id') .
		' AND ' . $db->qn('fv.field_id') . ' = ' . $db->q($emailField) . ')');

		if ($customField)
		{
			$query->join('LEFT', $db->qn('#__fields_values', 'fv1') . ' ON (' . $db->qn('fv1.item_id') . ' = ' . $db->qn('a.id') .
			' AND ' . $db->qn('fv1.field_id') . ' = ' . $db->q($customField) . ')');
		}

		$query->join('LEFT', $db->qn('#__tj_sla_cluster_xref', 'sxref') . ' ON (' . $db->qn('sxref.license_id') . ' = ' . $db->qn('l.id') . ')');
		$query->join('LEFT', $db->qn('#__tj_slas', 'sla') . ' ON (' . $db->qn('sla.id') . ' = ' . $db->qn('sxref.sla_id') . ')');

		$query->join(
    'LEFT',
    $db->qn('#__users', 'u') . ' ON (' . $db->qn('u.id') . ' = ' . $db->qn('sxref.lead_consultant_id') . ' AND ' . $db->qn('u.block') . ' = 0)'
);


		$query->join('LEFT', $db->qn('#__tj_clusters', 'cl') . ' ON (' . $db->qn('cl.client_id') . ' = ' . $db->qn('a.id') . ')');
		

		// To get user count
		$userCountQuery = $db->getQuery(true);
		$userCountQuery->select('count(DISTINCT(tjsu.user_id))');
		$userCountQuery->from($db->qn('#__tjsu_users', 'tjsu'));
		$userCountQuery->join('INNER', $db->qn('#__users', 'tju') . ' ON (' . $db->qn('tju.id') . ' = ' . $db->qn('tjsu.user_id') . ')');
		$userCountQuery->where($db->qn('tjsu.client') . ' = "com_multiagency"');
		$userCountQuery->where($db->qn('tju.block') . ' = 0');
		$userCountQuery->where($db->qn('tjsu.client_id') . ' = ' . $db->qn('a.id'));

		$query->select('(' . $userCountQuery . ') AS users_count');

		$query->where($db->qn('a.state') . '=1');

		// To get school admin details
		$subQueryUser = $db->getQuery(true);
		$subQueryUser->select("group_concat(uc.name separator ', ')");
		$subQueryUser->from($db->quoteName('#__tjsu_users', 'su'));
		$subQueryUser->join('INNER', $db->qn('#__users', 'uc') . ' ON (' . $db->qn('su.user_id') . ' = ' . $db->qn('uc.id') . ')');
		$subQueryUser->join('INNER', $db->qn('#__tjsu_roles', 'rl') . ' ON (' . $db->qn('rl.id') . ' = ' . $db->qn('su.role_id') . ')');
		$subQueryUser->where($db->qn('su.role_id') . ' = ' . $db->q($adminRole));
		$subQueryUser->where($db->qn('su.client') . " = 'com_multiagency'");
		$subQueryUser->where($db->qn('su.client_id') . ' = ' . $db->qn('a.id'));
		$subQueryUser->where($db->qn('uc.block') . ' = 0');

		$query->select('(' . $subQueryUser . ') AS schooladmin');

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->qn('a.id') . '=' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('(' . $db->qn('a.title') . ' LIKE ' . $search . ' OR
				' . $db->qn('u.name') . ' LIKE ' . $search . ')');
			}
		}

		// Filter by cluster_id
		$clusterId = (int) $this->getState('filter.cluster_id');

		if (!empty($clusterId))
		{
			$query->where($db->quoteName('cl.id') . " = " . $db->quote($clusterId));
		}

		// Filter by license_id
		$slaId = (int) $this->getState('filter.sla_id');

		if (!empty($slaId))
		{
			$query->where($db->quoteName('sla.id') . " = " . $db->quote($slaId));
		}

		// Filter by lead_consultant_id
		$leadConsultantId = (int) $this->getState('filter.lead_consultant_id');

		if (!empty($leadConsultantId))
		{
			$query->where($db->quoteName('sxref.lead_consultant_id') . " = " . $db->quote($leadConsultantId));
		}

		// Filter by licence state
		$licenceState = $this->getState('filter.licenceStatus');

		if (is_numeric($licenceState))
		{

			if ((int) $licenceState === 4)
			{
				$today = Factory::getDate()->format('Y-m-d');

				// Subquery to check if an upcoming (state=3) licence exists for the organisation
				$subQuery = $db->getQuery(true)
					->select('1')
					->from($db->quoteName('#__tjmultiagency_licences', 'lfs'))
					->where($db->quoteName('lfs.multiagency_id') . ' = ' . $db->quoteName('a.id'))
					->where($db->quoteName('lfs.state') . ' IN (1, 3)') // Include both Active and Upcoming licences
					->where($db->quoteName('lfs.end_date') . ' >= ' . $db->quote($today)); // for more security

				$maxDateSubQuery = $db->getQuery(true)
					->select('MAX(l2.end_date)')
					->from($db->quoteName('#__tjmultiagency_licences', 'l2'))
					->where($db->quoteName('l2.multiagency_id') . ' = ' . $db->quoteName('a.id'))
					->where($db->quoteName('l2.state') . ' = 2') // Archived
					->where($db->quoteName('l2.end_date') . ' < ' . $db->quote($today)); // Expired

				// Main query: organisation has archived licence which is expired and no upcoming licence + unassigned licence
					$query->where('(' .
					'(' .
						$db->quoteName('l.state') . ' = 2 AND ' .
						$db->quoteName('l.end_date') . ' = (' . $maxDateSubQuery . ') AND ' .
						'NOT EXISTS (' . $subQuery . ')' .
					') OR ' .
					$db->quoteName('l.state') . ' IS NULL' .
				')');
			}
			else
			{
				$query->where($db->quoteName('l.state') . ' = ' . (int) $licenceState);
			}
		}
		else
		{
			// Show active licence and unassigned licence organisations
			$query->where('(l.state = 1 OR l.state IS NULL)');
		}

		// Filter by tags
		$agencyTags = $this->getState('filter.tags','');
		$user = Factory::getUser();

		// checked dpe admin
		if (!empty($agencyTags) && $user->authorise('core.manageall', 'com_cluster'))
		{	
				$query->join('LEFT', $db->qn('#__contentitem_tag_map', 'tagsMap') . ' ON (' . $db->qn('tagsMap.content_item_id') . ' = ' . $db->qn('a.id') . ')');
				$query->where($db->quoteName('tagsMap.tag_id') . " IN (" . implode(",",$agencyTags) .')');	
				$query->where($db->quoteName('tagsMap.type_alias') . " = 'com_multiagency.multiagency' " );	
		}

		if ($customField)
		{
			// Filter by organisation custom field
			$agencyFilter = $this->getState('filter.agencyFilter');

			if ($agencyFilter != "all")
			{
				if ($agencyFilter == "none")
				{
					$query->where($db->quoteName('fv1.value') . "IS NULL");
				}
				elseif (!empty($agencyFilter))
				{
					$query->where($db->quoteName('fv1.value') . " = " . $db->quote($agencyFilter));
				}
			}
		}

		// Filter by user count
		$symbolFilter     = $this->getState('filter.symbolfilter');
		$usersCountFilter = $this->getState('filter.users_count');

		if ($symbolFilter && $usersCountFilter)
		{
			if ($symbolFilter === "lt")
			{
				$symbolFilter = "<";
			}

			$query->having($db->quoteName('users_count') . ' ' . $symbolFilter . ' ' . $usersCountFilter);
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
	 * function to get platform value of org
	 *
	 * @param   int     $agencyId        org id
	 * @param   int     $fieldId         field id
	 * @param   string  $platformValues  value and short form
	 *
	 * @return	String|Void
	 *
	 * @since	1.0.0
	 */
	public function getPlatformValue($agencyId, $fieldId, $platformValues)
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('fv.value');
		$query->from($db->quoteName('#__fields_values', 'fv'));
		$query->where($db->quoteName('fv.field_id') . ' = ' . (int) $fieldId);
		$query->where($db->quoteName('fv.item_id') . ' = ' . (int) $agencyId);
		$db->setQuery($query);

		$result = $db->loadResult();

		if ($result)
		{
			// Field value and short form maped here in json format
			$shortValues = new Registry($platformValues);

			if ($shortValues[$result])
			{
				$orgPlatformValue = $shortValues[$result];
			}
			else
			{
				$orgPlatformValue = $result;
			}

			return $orgPlatformValue;
		}

		return;
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

									// Add model path
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');

		// Load School model
		$schoolModel = BaseDatabaseModel::getInstance('School', 'DpeModel');

		foreach ($items as $item)
		{
			// Call model method if new organization created to fetch conlead_consultantID store result per item ID
			if (empty($item->lead_consultant))
			{
				$lead_consultantID = $this->getConsultantIdFromOrga($item->id);
				if($lead_consultantID > 0){

					// Get the user name
					$userInfo = Factory::getUser($lead_consultantID);

					$item->lead_consultant=$userInfo->name;
				}
			}
			$result = $this->getPlatformValue($item->id, $platformField, $platformValues);

			if ($result)
			{
				$item->platform = $result;
			}

			$item->licence_tools=[];

			$item->licence_tools = $schoolModel->getLicenceToolsList($item->licence_id );
		}

		return $items;
	}

	/**
	 * Get the lead consultant ID associated with a given organisation (multiagency) record.
	 *
	 * This method queries the #__tjmultiagency_multiagency table using the provided organisation ID
	 * and returns the corresponding lead_consultant_id.
	 *
	 * @param  integer  $id  The ID of the organisation record in the multiagency table.
	 *
	 * @return integer|null  The lead_consultant_id if found, otherwise null.
	 *
	 * @since   1.0.0
	 */
	public function getConsultantIdFromOrga($id)
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select($db->quoteName('lead_consultant_id'))
			->from($db->quoteName('#__tjmultiagency_multiagency'))
			->where($db->quoteName('id') . ' = ' . (int) $id);

		$db->setQuery($query);

			return $db->loadResult();

	}
}
