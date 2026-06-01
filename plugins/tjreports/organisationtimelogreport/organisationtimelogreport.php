<?php
/**
 * @package    DPE
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2020. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Data\DataObject;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Date\Date;

// Include TJReport Model
JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Organisation report plugin of TJReport
 *
 * @since  __DEPLOY_VERSION__
 */
class TjreportsModelOrganisationTimelogReport extends TjreportsModelReports
{
	protected $default_order = 'latest_date';

	protected $default_order_dir = 'DESC';

	public $columns;

	protected $app;

	public $defaultFilterValue;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     BaseDatabaseModel
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct($config = array())
	{
		$this->app = Factory::getApplication();
		$this->app->input->set('report', 'organisationreport');
		$this->dpeParams          = ComponentHelper::getParams('com_dpe');
		$this->defaultFilterValue = $this->dpeParams->get('agencyFilterValue');

		JLoader::import("components/com_multiagency/includes/multiagency", JPATH_SITE);
		JLoader::import("components/com_timelog/includes/timelog", JPATH_SITE);
		JLoader::import("components/com_dpe/includes/dpe", JPATH_SITE);

		$this->columns = array(
			'org_name' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_ORGANISATION_NAME'),
			'lead_consultant' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_ORGANISATION_LEAD_CONSULTANT'),
			'spentTime' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_SPENT_TIME'),
			'start_date' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_ORGANISATION_TIMELOG_START_DATE'),
			'latest_date' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_ORGANISATION_TIMELOG_LATEST_DATE'),
			'licence_end_date' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_ORGANISATION_SLA_END_DATE'),
		);

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return array<string,mixed|string> Plugin Details
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getPluginDetail()
	{
		return array('client' => 'com_multiagency', 'title' => Text::_('PLG_TJREPORTS_ORGANISATION_TIMELOG_REPORT'));
	}

	/**
	 * Create an array of filters
	 *
	 * @return    ARRAY Filters used in reports
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function displayFilters()
	{
		// To get Agency dropdown list
		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields');
		$clusterList    = FormHelper::loadFieldType('Cluster', false);
		$clusterOptions = $clusterList->getOptionsExternally();
		

		$user           = Factory::getUser();
		$dpeUsersModel  = DPE::model('Users', array('ignore_request' => true));
		$leadConsultant = $dpeUsersModel->getLeadConsultant();
		$filters        = (array) $this->getState('filters');
		$user           = Factory::getUser();

		JLoader::register("School", JPATH_SITE . '/components/com_dpe/controllers/school.php');
		JLoader::load("School");
		$schoolController = new DpeControllerSchool;
		$tags = $schoolController->getTagsList();

		// Add custom field filter
		FormHelper::addFieldPath(JPATH_SITE . '/components/com_dpe/models/fields');
		$agencyField = FormHelper::loadFieldType('AgencyFilter', false);
		$agencyFilterOption = $agencyField->getOptionsExternally();

		if (!$filters['agencyfilter'])
		{
			$filters['agencyfilter'] = $this->defaultFilterValue;
		}

		// To set the calendar field date format
		$filters['dateFormat'] = Text::_('PLG_TJREPORTS_DPE_DUE_DATE_FORMAT');
		$this->setState('filters', $filters);

		$params     			   = ComponentHelper::getParams('com_multiagency');
	   $orgAdminRoleId            = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
	   $orgAdminRoleId 		   = in_array($orgAdminRoleId, $user->groups);
	   $isDpeAdmin = $user->authorise('core.manageall', 'com_cluster');

				if($orgAdminRoleId && !$isDpeAdmin)
				{
					JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
				$dpeModel = DPE::model('school', array('ignore_request' => true));
				$tags = $dpeModel->getAgencyTags($orgAdminRoleId);
				}
		
		if ($isDpeAdmin || $orgAdminRoleId)
		{
			$tagFilter =  array(
					'search_type' => 'select', 'select_options' => $tags, 'type' => 'equal', 'multiple'=> 'multiple'
					);
		}

		// To Add the All instead of emppty value for the organisations to shwo the all value for multiple organisations.
		

		if(($isDpeAdmin || $orgAdminRoleId) && (count($clusterOptions)>1))
		{
		  $clusterOptions[0]->value = "All" ;
		}

		$dispFilters = array(
			array(
			),
			array(
				'org_trust' => array(
					'search_type' => 'text','placeholder' => Text::_('PLG_TJREPORTS_SEARCH_BY_ORGANISATION'), 'showRemoveButton' => false
				),
				'cluster_id' => array(
					'search_type' => 'select', 'select_options' => $clusterOptions, 'type' => 'equal'
				),
				'tactivities.created_date' => array(
						'search_type' => 'date.range',
						'searchin' => 'tactivities.created_date',
						'tactivities.created_date_from' => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_FORM'))),
						'tactivities.created_date_to' => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_TO'))),
				),
				'agencyfilter' => array( 'search_type' => 'select', 'select_options' => $agencyFilterOption, 'type' => 'equal'
				),
				'tags' => $tagFilter,
			)
		);

		if (!empty($leadConsultant))
		{
			$dispFilters[1]['uId'] = array(
				'search_type' => 'select', 'select_options' => $leadConsultant,
				'type' => 'equal', 'searchin' => 'u.id'
			);
		}

		return $dispFilters;
	}

	/**
	 * Method to get a DataObjectbaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DataObjectbaseQuery  A DataObjectbaseQuery object to retrieve the data set.
	 *
	 * @since   _DEPLOY_VERSION_
	 */
	protected function getListQuery()
	{
		$db         = $this->_db;
		$user       = Factory::getUser();
		$query      = $db->getQuery(true);
		$query      = parent::getListQuery();
		$filters    = $this->getState('filters');
		$isDpeAdmin = $user->authorise('core.manageall', 'com_cluster');

		if($filters['cluster_id'] == 'All')
		{
			$filters['cluster_id'] = '';
			$query->clear('where');
		}
		// Custom field filter
		$params      = ComponentHelper::getParams('com_dpe');
		$customField = $params->get('agencyFilterField', '0');

		if (!$filters['agencyfilter'])
		{
			$filters['agencyfilter'] = $this->defaultFilterValue;
		}

		$query->select(array('a.id AS agency_id, a.state, a.title'));
		$query->select('a.title as org_name');
		$query->select('cl.id as cluster_id');
		$query->select('u.id as lead_consultant_id');
		$query->select('fv.value as customFieldValue');
		$query->select('l.end_date as licence_end_date, l.id as licence_id');
		$query->select('TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(tactivities.timelog))), "' . Text::_('COM_DPE_TIME_FORMAT_DB_HRMIN') . '" ) as spentTime');
		$query->select('MIN(tactivities.created_date) as start_date');
		$query->select('max(tactivities.created_date) as latest_date');
		$query->select('u.name as lead_consultant');

		$query->from($db->quoteName('#__tjmultiagency_multiagency', 'a'));

		$query->join('LEFT', $db->qn('#__tjmultiagency_licences', 'l') . ' ON ('
. $db->qn('l.multiagency_id') . ' = ' . $db->qn('a.id') . ' AND ' . $db->qn('l.state') . ' = ' . (int) 1 . ')');

		$query->join('LEFT', $db->qn('#__tj_sla_cluster_xref', 'sxref') . ' ON (' . $db->qn('sxref.license_id') . ' = ' . $db->qn('l.id') . ')');

		$clusterWhere = '';

		if ($filters['cluster_id'])
		{
			$clusterWhere .= ' AND ' . $db->qn('cl.id') . ' = ' . (int) $filters['cluster_id'];
		}

		$query->join('INNER', $db->qn('#__tj_clusters', 'cl') . ' ON (' . $db->qn('cl.client_id') . ' = ' . $db->qn('a.id') . $clusterWhere . ' )');

		if ($filters['uId'])
		{
			$query->join('INNER', $db->qn('#__users', 'u')
. ' ON (' . $db->qn('u.id') . ' = ' . $db->qn('sxref.lead_consultant_id') . ')'
. ' AND ' . $db->qn('u.id') . ' = ' . (int) $filters['uId']
);
		}
		else
		{
			$query->join('INNER', $db->qn('#__users', 'u') . ' ON (' . $db->qn('u.id') . ' = ' . $db->qn('sxref.lead_consultant_id') . ')');
		}

		$query->join('LEFT', $db->qn('#__tj_sla_activities', 'activities') . ' ON (' . $db->qn('activities.cluster_id') . ' = ' . $db->qn('cl.id') . ')');

		$query->join('LEFT', $db->qn('#__timelog_activities', 'tactivities')
. ' ON (' . $db->qn('tactivities.client_id') . ' = ' . $db->qn('activities.id') . ')');

		$query->join('LEFT', $db->qn('#__fields_values', 'fv')
. ' ON (' . $db->qn('fv.item_id') . ' = ' . $db->qn('a.id')
. ' AND ' . $db->qn('fv.field_id') . ' = ' . $db->q($customField) . ')'
);

		if (!$isDpeAdmin)
		{
			$cluster = FormHelper::loadFieldType('cluster', false);
			$clusterList = $cluster->getOptionsExternally();
			$usersClusters = array();

			if (!empty($clusterList))
			{
				foreach ($clusterList as $clusterList)
				{
					if (!empty($clusterList->value))
					{
						$usersClusters[] = $clusterList->value;
					}
				}
			}

			$query->where($db->qn('cl.id') . " IN ('" . implode("','", $usersClusters) . "')");
		}

		/* Show 0 timelog orgs only to dpe admin,
		   if allowing user other than dpe admin then need to work on query
		   beacause following orwhere skipping above where condition for non-dpe admin users when we set the date filters,
		   if org filter is selected then don't show 0 timelog orgs
		*/

		if ($filters['agencyfilter'] === 'none' || $filters['agencyfilter'] === 'all')
		{
			$likeStr = '';

			if ($isDpeAdmin && ($filters['tactivities.created_date_from'] || $filters['tactivities.created_date_to']))
			{
				if (!empty(trim($filters['org_trust'])) && !$filters['cluster_id'])
				{
					$likeStr .= ' AND ' . $db->qn('a.title') . ' LIKE ( ' . $db->quote('%' . $filters['org_trust'] . '%') . ')';
				}

				$filterStr = '';

				if ($filters['agencyfilter'] === 'none')
				{
					$likeStr .= ' AND ' . $db->qn('fv.value') . ' IS NULL';
				}

				$query->orwhere($db->qn('tactivities.timelog') . ' IS NULL');
				$query->andwhere($db->qn('a.state') . '= 1 ' . $likeStr);
			}
			else
			{
				if (!empty(trim($filters['org_trust'])) && !$filters['cluster_id'])
				{
					$likeStr .= ' AND ' . $db->qn('a.title') . ' LIKE ( ' . $db->quote('%' . $filters['org_trust'] . '%') . ')';
				}

				if ($filters['agencyfilter'] === 'none')
				{
					$query->where($db->quoteName('fv.value') . " IS NULL");
				}
				elseif (!empty($filters['agencyfilter']) && $filters['agencyfilter'] !== 'all')
				{
					$likeStr .= ' AND ' . $db->qn('fv.value') . ' = ' . $db->quote($filters['agencyfilter']);
				}

				$query->where($db->qn('a.state') . '= 1 ' . $likeStr);
			}
		}
		else
		{
			if ($isDpeAdmin && ($filters['tactivities.created_date_from'] || $filters['tactivities.created_date_to']))
			{
				if (!empty(trim($filters['org_trust'])) && !$filters['cluster_id'])
				{
					$likeStr .= ' AND ' . $db->qn('a.title') . ' LIKE ( ' . $db->quote('%' . $filters['org_trust'] . '%') . ')';
				}

				if (!empty($filters['agencyfilter']))
				{
					$likeStr .= ' AND ' . $db->qn('fv.value') . ' = ' . $db->quote($filters['agencyfilter']);
				}

				$query->orwhere($db->qn('tactivities.timelog') . ' IS NULL');
				$query->andwhere($db->qn('a.state') . '= 1 ' . $likeStr);
			}
			else
			{
				if (!empty(trim($filters['org_trust'])) && !$filters['cluster_id'])
				{
					$likeStr .= ' AND ' . $db->qn('a.title') . ' LIKE ( ' . $db->quote('%' . $filters['org_trust'] . '%') . ')';
				}

				if (!empty($filters['agencyfilter']))
				{
					$likeStr .= ' AND ' . $db->qn('fv.value') . ' = ' . $db->quote($filters['agencyfilter']);
				}

				$query->where($db->qn('a.state') . '= 1 ' . $likeStr);
			}
		}

		// Tags Filter
		if (is_array($filters['tags']))
		{ 
			$tags = empty($filters['tags']) ? '' : implode(',',$filters['tags']);
		}

		

		// checked dpe admin 
		if ($tags && $user->authorise('core.manageall', 'com_cluster'))
		{
			$query->JOIN('INNER', $this->_db->qn('#__contentitem_tag_map', 'tag_map') . ' ON (' . $this->_db->qn('cl.client_id')
					. ' = ' . $this->_db->qn('tag_map.content_item_id') . ')');
		
			$query->where($this->_db->quoteName('tag_map.tag_id') . " IN ( " . $tags.')');
			$query->where($this->_db->quoteName('tag_map.type_alias') . " LIKE 'com_multiagency.multiagency'");
		}

		$query->group('a.id');

		return $query;	
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   _DEPLOY_VERSION_
	 */
	public function getItems()
	{	

		$filters = (array) $this->getState('filters');

		$session  = Factory::getSession();

		if (!empty($filters['cluster_id']) && $filters['cluster_id'] != 'All') {
	    	// Add session when filter has some value
			$session->set('reportCluster', $filters['cluster_id']);

		}elseif($filters['cluster_id'] == 'All')
		{	
			$session->clear('reportCluster');
			$filters['cluster_id'] = '';
			$this->setState('filters', $filters);
		} else {
			$filters['cluster_id'] = $session->get('reportCluster');
			$this->setState('filters', $filters);
		}

		$items = parent::getItems();
		$params = DPE::config();
		$user   = Factory::getUser();
		$isDpeAdmin = $user->authorise('core.manageall', 'com_cluster');


		foreach ($items as &$item)
		{
			// Add licnese edit link on org name for dpe admin because other user don't have access for edit sla
			if ($isDpeAdmin && $item['org_name'])
			{
				$slaEditLink        = 'index.php?option=com_multiagency&view=licenceform&layout=edit';
				/** @scrutinizer ignore-call */
				$multiagencyUtility = Multiagency::utilities();
				$itemId             = $multiagencyUtility->getItemId($slaEditLink);
				$slaLink            = Route::_($slaEditLink . '&id=' . $item['licence_id'] . '&Itemid=' . $itemId, false);
				$item['org_name']   = '<a href=' . $slaLink . ' target="_blank">' . $item['org_name'] . '</a>';
			}




			// Add timelog list view link on spent-time
			if ($item['spentTime'])
			{
				$filters = $this->app->input->get('filters', '', 'Array');
				
				$fromDateFilter = isset($filters['tactivities.created_date_from'])?$filters['tactivities.created_date_from']:'';

				$toDateFilter   = isset($filters['tactivities.created_date_to'])?$filters['tactivities.created_date_to']:'';
				$fromFilter     = '';
				$toFilter       = '';

				if ($fromDateFilter)
				{
					$fromFilter = '&filter[date_from]=' . $fromDateFilter;
				}

				if ($toDateFilter)
				{
					$toFilter = '&filter[date_to]=' . $toDateFilter;
				}

				$slaActivtyLink     = 'index.php?option=com_timelog&view=activities';
				/** @scrutinizer ignore-call */
				$multiagencyUtility = Timelog::utilities();
				$itemId             = $multiagencyUtility->getItemId($slaActivtyLink);
				$timelogLink        = Route::_(
				$slaActivtyLink . '&layout=activities' . $fromFilter . $toFilter . '&licence_id=' . $item['licence_id'] . '&tmpl=component&state=1&Itemid=' . $itemId, false
				);

				$timelogpopupLink = addslashes($timelogLink);

				$item['spentTime']  = '<a lass="d-inline-block mr-15"
				onclick="timeLog.openTimeLogPopup('."'".$timelogpopupLink."'".')"
				id="assign-modal-link"
				target="_blank">' . $item['spentTime'] . '</a>';
			}

			if (!empty($item['licence_end_date']) && $item['licence_end_date'] != '0000-00-00 00:00:00')
			{
				$item['licence_end_date'] = HTMLHelper::_('date', $item['licence_end_date'], (string) $params->get('dateFormat'));
			}
			else
			{
				$item['licence_end_date'] = "-";
			}

			if (!empty($item['start_date']) && $item['start_date'] != '0000-00-00 00:00:00')
			{
				$item['start_date'] = HTMLHelper::_('date', $item['start_date'], (string) $params->get('dateFormat'));
			}
			else
			{
				$item['start_date'] = "-";
			}

			if (!empty($item['latest_date']) && $item['latest_date'] != '0000-00-00 00:00:00')
			{
				$item['latest_date'] = HTMLHelper::_('date', $item['latest_date'], (string) $params->get('dateFormat'));
			}
			else
			{
				$item['latest_date'] = "-";
			}

			if (empty($item['spentTime']))
			{
				$item['spentTime'] = "-";
			}
		}

		return $items;
	}
}
