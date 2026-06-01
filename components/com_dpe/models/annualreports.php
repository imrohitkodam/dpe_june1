<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Form\Form;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Date\Date;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\ListModel;

JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

/**
 * Annual Report
 * 
 * @since  __DEPLOY_VERSION__
 */
class DpeModelAnnualReports extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.6
	 */
	public function __construct($config = array())
	{
		
		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since    1.6
	 */
	protected function populateState($ordering = "o.id", $direction = "DESC")
	{
		$app  = Factory::getApplication();
		$input = $app->input; 
		$filters = $input->get('filter', array(), 'ARRAY');
		$this->setState('list', $input->get('list', array(), 'ARRAY'));
		$this->setState('filters', $filters);
		
		parent::populateState($ordering, $direction);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		$db = $this->getDbo();
$query = $db->getQuery(true);
$query->select([
    'o.id AS id',
    'o.cluster_ids AS cluster_ids',
    'o.created_by',
    'o.start_date',
    'o.end_date',
    'o.created_date',
    'o.dpo_comment',
    'o.report_status',
    'u.name'
])
->from($db->quoteName('#__organisational_report', 'o'))
->leftJoin($db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('o.created_by') . ' = ' . $db->quoteName('u.id'));

$clusterId = $this->getState('filters')['cluster_id'];
$usersClusters = [];
    $user = Factory::getUser();

// If no filter provided, fallback to user's clusters
if (!$clusterId && !$user->authorise('core.manageall', 'com_cluster'))
{
    JFormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
    $cluster = JFormHelper::loadFieldType('cluster', false);
    $clusterList = $cluster->getOptionsExternally();

    if (!empty($clusterList))
    {
        JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
        foreach ($clusterList as $clusterItem)
        {
            if (!empty($clusterItem->value))
            {
                $usersClusters[] = $clusterItem->value;
            }
        }
    }
}
elseif ($clusterId)
{
    $usersClusters = explode(',', $clusterId);
}

// Apply WHERE condition if clusters are found
if (!empty($usersClusters))
{
    $clusterConditions = [];
    foreach ($usersClusters as $cluster)
    {
        $clusterConditions[] = "FIND_IN_SET(" . $db->quote($cluster) . ", o.cluster_ids) > 0";
    }

    $query->where('(' . implode(' OR ', $clusterConditions) . ')');
}
    $query->where('state=1');

		
		$fullorderCol = $this->getState('list')['fullordering'];

		// Apply ordering
		if (!empty($fullorderCol) && !str_contains($fullorderCol, 'null'))
		{
			$query->order($db->escape($fullorderCol));
		}
		else
		{
			$query->order('o.id DESC');
		}
		
		$fullorderCol = $this->getState('list')['fullordering'];
		$listarray = explode(' ',$fullorderCol);
		$this->setState('list.ordering', $listarray[0]);
		$this->setState('list.direction', $listarray[1]);
		return $query;
	}

	/**
	 * Get list items
	 *
	 * @return  ARRAY
	 *
	 * @since    1.6
	 */
	public function getItems()
	{
		$items = parent::getItems();
		foreach ($items as $key => $item) {

			if (str_contains($item->cluster_ids, ",")) {
			    $multiClusters = explode(',',$item->cluster_ids);
			    
			    foreach ($multiClusters as $clusterkey => $multiCluster) {
			    	$clusterName[$clusterkey] = $this->getClusterName($multiCluster);
			    }
			    $items[$key]->cluster_ids = implode(',',$clusterName);

			}
			else
			{
				$items[$key]->cluster_ids =  $this->getClusterName($item->cluster_ids);
				
			}
		}

		return $items;
	}
	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $clusteIds  The id of the clusters.
	 *
	 * @return  String name of the cluster
	 *
	 * @since   1.0.0
	 */
	public function getClusterName($clusteIds)
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
		$clusterInstance = Table::getInstance('Clusters', 'ClusterTable');
		$clusterInstance->load(array('id' => $clusteIds));
		return $clusterInstance->name;
	}

	
}