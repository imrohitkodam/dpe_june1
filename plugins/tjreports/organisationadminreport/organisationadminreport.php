<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Data\DataObject;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Date\Date;


JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Organisation admin report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelOrganisationAdminReport extends TjreportsModelReports
{
	protected $default_order       = 'id';

	protected $default_order_dir   = 'ASC';

	public $columns;

	public $dpeParams;

	public $defaultFilterValue;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     BaseDatabaseModel
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
			JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);
			
			Factory::getApplication()->input->set('report', 'organisationadminreport');
			$this->dpeParams          = ComponentHelper::getParams('com_dpe');
			$this->defaultFilterValue = $this->dpeParams->get('agencyFilterValue', '0');

			$this->columns = array(
			'username' => array('table_column' => '', 'title' => 'PLG_DPE_ORGANISATION_ADMIN_USERUSERNAME'),
			'job_title' => array('table_column' => '', 'title' => 'PLG_DPE_ORGANISATION_ADMIN_JOBTITLE'),
			'user_email' => array('table_column' => '', 'title' => 'PLG_DPE_ORGANISATION_ADMIN_EMAIL'),
			'lead_consultant' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_ORGANISATION_LEAD_CONSULTANT'),
			'title' => array('table_column' => 'a.title', 'title' => 'PLG_DPE_ORGANISATION_ADMIN_ORGANIZATION'),
			'licence_start_date' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_ORGANISATION_SLA_START_DATE'),
			'licence_end_date' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_ORGANISATION_SLA_END_DATE'),
		);

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return STRING Client
	 *
	 * @since   2.0
	 * 
	 */
	public function getPluginDetail()
	{
		return $detail = array('client' => 'com_dpe', 'title' => Text::_('PLG_DPE_ORGANISATIONADMIN_REPORT_TITLE'));
	}

	/**
	 * Create an array of filters
	 *
	 * @return    void
	 *
	 * @since    1.0
	 */
	public function displayFilters()
	{
		$user    = Factory::getUser();

		// To get Agency dropdown list
		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields');
		$clusterList = FormHelper::loadFieldType('Cluster', false);
		$clusterOptions = $clusterList->getOptionsExternally();

		$user          = Factory::getUser();

		// To add all value if the user has admin dpe admin acess and admin acess with multiple organisation.
		if ($user->authorise('core.manageall', 'com_cluster') && count($clusterOptions)>1)
		{
		  $clusterOptions[0]->value = "All" ;
		}

		JLoader::register("School", JPATH_SITE . '/components/com_dpe/controllers/school.php');
		JLoader::load("School");
		$schoolController = new DpeControllerSchool;
		$tags = $schoolController->getTagsList();

		$dpeUsersModel  = DPE::model('Users', array('ignore_request' => true));
		$leadConsultant = $dpeUsersModel->getLeadConsultant();

		FormHelper::addFieldPath(JPATH_SITE . '/components/com_dpe/models/fields');
		$agencyField = FormHelper::loadFieldType('AgencyFilter', false);
		$agencyFilterOption = $agencyField->getOptionsExternally();

		// To set the calendar field date format
		$filters = (array) $this->getState('filters');

		if (!$filters['cluster_id'])
		{
			$filters['cluster_id'] = $this->defaultFilterValue;
		}

		$filters['dateFormat'] = Text::_('PLG_TJREPORTS_DPE_DUE_DATE_FORMAT');
		$this->setState('filters', $filters); 
		
		$dispFilters = array(
			array(
			),
			array(
				'cluster_id' => array(
					'search_type' => 'select', 'select_options' => $clusterOptions, 'type' => 'equal', 'searchin' => 'cl.id'
				),
				'tags' => array(
					'search_type' => 'select', 'select_options' => $tags, 'type' => 'equal', 'multiple'=> 'multiple'
					),
				'l.end_date' => array(
					'search_type' => 'date.range',
					'searchin' => 'l.end_date',
					'l.end_date_from' => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_FORM'))),
					'l.end_date_to' => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_TO'))),
				),
			)
		);

		return $dispFilters;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function getItems()
	{	

		 $filters = (array) $this->getState('filters');
		 
		 $filters['dateFormat'] = Text::_('PLG_TJREPORTS_DPE_DUE_DATE_FORMAT');
		 $this->setState('filters', $filters); 
// get session value for the clsuers.
		 $session = Factory::getSession();

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

		// Add additional columns which are not part of the query
		$items 			= parent::getItems();
		$colToshow		= $this->getState('colToshow');
		$items 			= $this->sortCustomColumns($items);
		$params = DPE::config();
		$fieldName = array('school-address' , 'address-2', 'address-3', 'town-city', 'County', 'postcode', 'dpe-region', 'website', 'county');
	
		if (is_array($items))
		{
			JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

			foreach ($items as $key => &$item)
			{

				if ((!empty($item['licence_end_date']) && $item['licence_end_date'] != '0000-00-00 00:00:00') || (!empty($item['licence_start_date']) && $item['licence_start_date'] != '0000-00-00 00:00:00'))
				{
					$item['licence_end_date'] = HTMLHelper::_('date', $item['licence_end_date'], (String) $params->get('dateFormat'));

					$item['licence_start_date'] = HTMLHelper::_('date', $item['licence_start_date'], (String) $params->get('dateFormat'));
				}
				else
				{
					$item['licence_end_date'] = '';
				}

				$orgFields = FieldsHelper::getFields('com_multiagency.multiagency', $item, true);

				if (!empty($orgFields))
				{

					foreach ($orgFields as $field)
					{
				
						// Don't show media field and unused fields like calender , radio ,subform , tjlist, url etc.

						if (in_array($field->name, $fieldName))
						{
							$colToshow[$field->name] = $field->name;
							$this->columns[$field->name] = array('title' => ucwords($field->name));
							if (empty($field->value))
							{
								$field->value = "-";
							}

							$items[$key][$field->name] = $field->value;
						}
					}

					$this->setState('colToshow', $colToshow);
				}
			}
		}

		return $items;
	}

	/**
	 * Method to get a DataObjectbaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DataObjectbaseQuery  A DataObjectbaseQuery object to retrieve the data set.
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{

		$filters       = $this->getState('filters');

		$columnsToShow = (array) $this->getState('colToshow');
		$params        = ComponentHelper::getParams('com_dpe');
		$customField   = $params->get('agencyFilterField', '0');
		$user          = Factory::getUser();
		$db            = Factory::getDbo();
		$query         = $db->getQuery(true);
		
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_subusers/tables');
 		$roleTable = Table::getInstance('role', 'SubusersTable');
   		$roleTable->load(array('name' => 'Admin'));

   		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
   		$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');
   		$tjFieldFieldTable->load(array('name' => 'com_tjucm_role_name', 'state' => 1));

		$query->select(array('a.id', 'a.state', 'a.title','u.name as username', 'u.email as useremail'));
		$query->select('a.title as org_name');
		$query->select('u.name as lead_consultant');
		$query->select('u.name as username');
		$query->select('u.email as user_email');
		$query->select('fvs.value as job_title');
		$query->select('l.end_date as licence_end_date');
		$query->select('l.start_date as licence_start_date');
		$query->select('(CASE WHEN l.end_date IS NULL THEN 0 ELSE 1 END) as sla_assigned');

		$query->from($db->quoteName('#__users', 'u'));
		$query->join('INNER', $db->qn('#__tjsu_users', 'su') . ' ON (' . $db->qn('u.id') . ' = ' . $db->qn('su.user_id') . ') AND ' .  $db->qn('su.role_id') .' = ' .  $roleTable->id .' AND ' .  $db->qn('su.client') .'= "com_multiagency"  AND '. $db->qn('u.block') .' = 0');
		$query->join('LEFT', $db->qn('#__tj_clusters', 'cl') . ' ON (' . $db->qn('cl.client_id') . ' = ' . $db->qn('su.client_id') . ')' . ' AND ' .  $db->qn('cl.state') .'= 1' );


		$query->join('LEFT', $db->qn('#__tjmultiagency_multiagency', 'a') . ' ON (' . $db->qn('su.client_id') . ' = ' . $db->qn('a.id').')' );
		$query->join('INNER', $db->qn('#__tjmultiagency_licences', 'l') . ' ON (' . $db->qn('l.multiagency_id') . ' = ' . $db->qn('a.id') . ') AND ' .  $db->qn('l.state') .'= 1' );
		$query->join('LEFT', $db->qn('#__tj_sla_cluster_xref', 'sxref') . ' ON (' . $db->qn('sxref.license_id') . ' = ' . $db->qn('l.id') . ')');
		
		$query->join('LEFT', $db->qn('#__job_title_user_xref', 'jt') . ' ON (' . $db->qn('jt.user_id') . ' = ' . $db->qn('su.user_id') . ') AND ' .$db->qn('jt.cluster_id') .'=' .$db->qn('cl.id') );

		$query->join('LEFT', $db->qn('#__tjfields_fields_value', 'fvs') . ' ON (' . $db->qn('fvs.content_id') . ' = ' . $db->qn('jt.ucm_id') . ') AND'. $db->qn('fvs.field_id') .' = ' . $tjFieldFieldTable->id);

		if ($customField)
		{
			$query->join('LEFT', $db->qn('#__fields_values', 'fv') . ' ON (' . $db->qn('fv.item_id') . ' = ' . $db->qn('a.id') .
			' AND ' . $db->qn('fv.field_id') . ' = ' . $db->q($customField) . ')');
		}

			if (!empty($filters['l.end_date_from']))
			{
				$fromTime = $filters['l.end_date_from'] . ' 00:00:00';
				$fromTime = new Date($fromTime, 'UTC');
				$query->where($db->qn('l.end_date') . ' >= ' . $db->quote($fromTime));
			}

			if (!empty($filters['l.end_date_to']))
			{
				$toTime = $filters['l.end_date_to'] . ' 23:59:59';
				$toTime = new Date($toTime, 'UTC');
				$query->where($db->qn('l.end_date') . ' <= ' . $db->quote($toTime));
			}

			if (!empty($filters['uId']))
			{
				$query->where($db->qn('u.id') . ' = ' . (int) $filters['uId']);
			}

			

			// With following query we are showing the org which don't have any status
			if ($filters['cluster_id'] === 'none')
			{
				$query->where($db->quoteName('fv.value') . " IS NULL");
			}


			if (!empty($filters['cluster_id']))
			{
				$query->where($db->qn('cl.id') . ' = ' . (int) $filters['cluster_id']);
			}

			if ($filters['tags'])
			{
				$tags = implode(',',(array) $filters['tags']);

			// checked dpe admin 
			if ($tags && $user->authorise('core.manageall', 'com_cluster'))
			{
				$query->JOIN('INNER', $this->_db->qn('#__contentitem_tag_map', 'tag_map') . ' ON (' . $this->_db->qn('cl.client_id')
						. ' = ' . $this->_db->qn('tag_map.content_item_id') . ')');
			
				$query->where($this->_db->quoteName('tag_map.tag_id') . " IN ( " . $tags.')');
				$query->where($this->_db->quoteName('tag_map.type_alias') . " LIKE 'com_multiagency.multiagency'");
			}
		}

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{	

			FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields');
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

		 $limit      = $this->getState('list.limit');
		 $limitStart = $this->getState('list.start');
		
		if (!empty($limit))
		{
			 $query->setlimit($limit, $limitStart);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.id');
		$orderDirn = $this->state->get('list.direction', 'DESC');
		
		if ($orderCol && $orderDirn)
		{
			$query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}


}
