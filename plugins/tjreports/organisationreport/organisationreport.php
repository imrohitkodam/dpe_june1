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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Date\Date;

// Include TJReport Model
JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Organisation report plugin of TJReport
 *
 * @since  _DEPLOY_VERSION_
 */
class TjreportsModelOrganisationReport extends TjreportsModelReports
{
	protected $default_order = 'a.title';

	protected $default_order_dir = 'ASC';

	public $columns;

	public $dpeParams;

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
		Factory::getApplication()->input->set('report', 'organisationreport');
		$this->dpeParams          = ComponentHelper::getParams('com_dpe');
		$this->defaultFilterValue = $this->dpeParams->get('agencyFilterValue', '0');

		$this->columns = array(
			'org_name' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_ORGANISATION_NAME'),
			'lead_consultant' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_ORGANISATION_LEAD_CONSULTANT'),
			'licence_end_date' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_ORGANISATION_SLA_END_DATE'),
			'sla_assigned' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_ORGANISATION_SLA_ASSIGNED')
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
		return $detail = array('client' => 'com_multiagency', 'title' => Text::_('PLG_TJREPORTS_ORGANISATIONREPORT'));
	}

	/**
	 * Create an array of filters
	 *
	 * @return    ARRAY Filters used in reports
	 *
	 * @since    __DEPLOY_VERSION__
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

		if (!$filters['agencyfilter'])
		{
			$filters['agencyfilter'] = $this->defaultFilterValue;
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
				'l.end_date' => array(
					'search_type' => 'date.range',
					'searchin' => 'l.end_date',
					'l.end_date_from' => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_FORM'))),
					'l.end_date_to' => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_TO'))),
				),
				'agencyfilter' => array(
					'search_type' => 'select', 'select_options' => $agencyFilterOption, 'type' => 'equal', 'searchin' => 'fv.value'
				),
				'tags' => array(
					'search_type' => 'select', 'select_options' => $tags, 'type' => 'equal', 'multiple'=> 'multiple'
					),
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
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getListQuery()
	{
		$db          = $this->_db;
		$user        = Factory::getUser();
		$app         = Factory::getApplication();
		$params      = ComponentHelper::getParams('com_dpe');
		$customField = $params->get('agencyFilterField', '0');
		$query       = $db->getQuery(true);
		$query       = parent::getListQuery();
		$filters     = $this->getState('filters');

		if($filters['cluster_id'] == 'All')
		{
			$filters['cluster_id'] = '';
			$query->clear('where');
		}
		$query->select(array('a.id, a.state, a.title'));
		$query->select('a.title as org_name');
		$query->select('u.name as lead_consultant');
		$query->select('l.end_date as licence_end_date');
		$query->select('(CASE WHEN l.end_date IS NULL THEN 0 ELSE 1 END) as sla_assigned');
		$query->from($db->quoteName('#__tjmultiagency_multiagency', 'a'));
		$query->join('LEFT', $db->qn('#__tj_clusters', 'cl') . ' ON (' . $db->qn('cl.client_id') . ' = ' . $db->qn('a.id') . ')');
		$query->join('LEFT', $db->qn('#__tjmultiagency_licences', 'l') . ' ON (' . $db->qn('l.multiagency_id') . ' = ' . $db->qn('a.id') . ')');
		$query->join('LEFT', $db->qn('#__tj_sla_cluster_xref', 'sxref') . ' ON (' . $db->qn('sxref.license_id') . ' = ' . $db->qn('l.id') . ')');
		$query->join('LEFT', $db->qn('#__users', 'u') . ' ON (' . $db->qn('u.id') . ' = ' . $db->qn('sxref.lead_consultant_id') . ')');

		if ($customField)
		{
			$query->join('LEFT', $db->qn('#__fields_values', 'fv') . ' ON (' . $db->qn('fv.item_id') . ' = ' . $db->qn('a.id') .
			' AND ' . $db->qn('fv.field_id') . ' = ' . $db->q($customField) . ')');
		}

		/* Clear all where clause and rebuild when filter value is "none" or "all"
		because we don't have value with none and all value
		and dynamic filter will attempt search with filter values
		*/

		if ($filters['agencyfilter'] === 'none' || $filters['agencyfilter'] === 'all')
		{
			$query->clear('where');

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

			if (!empty($filters['cluster_id']))
			{
				$query->where($db->qn('cl.id') . ' = ' . (int) $filters['cluster_id']);
			}

			// With following query we are showing the org which don't have any status
			if ($filters['agencyfilter'] === 'none')
			{
				$query->where($db->quoteName('fv.value') . " IS NULL");
			}
		}
		elseif ($filters['agencyfilter'] === $this->defaultFilterValue)
		{
			$query->where($db->qn('fv.value') . ' = ' . $db->quote($filters['agencyfilter']));
		}

		// Tags Filter
		if (is_array($filters['tags']))
		{ 
			$tags = implode(',',$filters['tags']);
		}
		
		// checked dpe admin 
		if ($tags && $user->authorise('core.manageall', 'com_cluster'))
		{
			$query->JOIN('INNER', $this->_db->qn('#__contentitem_tag_map', 'tag_map') . ' ON (' . $this->_db->qn('cl.client_id')
					. ' = ' . $this->_db->qn('tag_map.content_item_id') . ')');
		
			$query->where($this->_db->quoteName('tag_map.tag_id') . " IN ( " . $tags.')');
			$query->where($this->_db->quoteName('tag_map.type_alias') . " LIKE 'com_multiagency.multiagency'");
		}

		if (!$user->authorise('core.manageall', 'com_cluster'))
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

		$query->where($db->qn('a.state') . '=1');

		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getItems()
	{

		
		$session = Factory::getSession();

		$filters = (array) $this->getState('filters');if (!empty($filters['cluster_id']) && $filters['cluster_id'] != 'All') {
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
		$colToshow = $this->getState('colToshow');

		$params = DPE::config();

		if (is_array($items))
		{
			JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

			foreach ($items as $key => &$item)
			{
				if ($item['sla_assigned'] == 1)
				{
					$item['sla_assigned'] = "Yes";
				}
				else
				{
					$item['sla_assigned'] = "No";
				}

				if (!empty($item['licence_end_date']) && $item['licence_end_date'] != '0000-00-00 00:00:00')
				{
					$item['licence_end_date'] = HTMLHelper::_('date', $item['licence_end_date'], (String) $params->get('dateFormat'));
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
						// Don't show media field
						if ($field->type != "media")
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
}
