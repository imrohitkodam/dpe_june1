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
class DpeModelUcmbulkdownload extends ListModel
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
	protected function populateState($ordering = "id", $direction = "DESC")
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
    // Get a db connection
    $db = Factory::getDbo();

    // Create a new query object
    $query = $db->getQuery(true);
    $user = Factory::getUser();

    // Select all fields from the table
    $query->select('*')
        ->from($db->quoteName('#__ucm_reports_download_detail'))
	      ->where($db->quoteName('state') . ' = 1');
        //->where($db->quoteName('user_id') . ' = '.$user->id);
    // Get cluster filter and current user
    $clusterId = $this->getState('filters')['cluster_id'] ?? null;
    $usersClusters = [];

    // If no filter and no manageall access, fetch user's clusters
    if (!$clusterId && !$user->authorise('core.manageall', 'com_cluster'))
    {
        FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
        $cluster = FormHelper::loadFieldType('cluster', false);

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

    if (!empty($usersClusters))
    {
        $clusterConditions = [];

        foreach ($usersClusters as $cluster)
        {
            $cluster = trim($cluster);
            if ($cluster !== '') {
                $clusterConditions[] = 'FIND_IN_SET(' . $db->quote($cluster) . ', ' . $db->quoteName('cluster_id') . ')';
            }
        }

        if (!empty($clusterConditions)) {
            $query->where('(' . implode(' OR ', $clusterConditions) . ')');
        }
    }
		$fullorderCol = $this->getState('list')['fullordering'];

		// Apply ordering
		if (!empty($fullorderCol) && !str_contains($fullorderCol, 'null'))
		{
			$query->order($db->escape($fullorderCol));
		}
		else
		{
			$query->order('id DESC');
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
		JLoader::import('clusters', JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
    
		foreach ($items as $key => &$item)
{
    // Split cluster_ids
    $clusterIds = explode(',', $item->cluster_id);
    $clusterNames = [];

    // Instantiate cluster table once
    $clustersTable = Table::getInstance('clusters', 'ClusterTable', array());

    foreach ($clusterIds as $clusterId)
    {
        // Load each cluster by individual ID
        if ($clustersTable->load(['id' => (int) $clusterId]))
        {
            $clusterNames[] = $clustersTable->name;
        }
    }

    // Join cluster names with comma
    $item->cluster_names = implode(', ', array_unique($clusterNames));

    // Format date fields
    $item->expiry = date('d-m-Y H:i:s', strtotime($item->created_at . ' +7 days'));
    $item->created_at = date('d-m-Y H:i:s', strtotime($item->created_at));

    // Get username
    $item->user_id = Factory::getUser($item->user_id)->username;
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


    /**
	 * Method to assign the default set as per start date.
	 *
	 * @param String $start_date
	 * 
	 * @return  bool
	 */
    public function deleteUcmBulkFile($date)
    {
    	$db   = Factory::getDBO();
    	$suceessCount=0;
    	try
    	{
    		$query = $db->getQuery(true);
    		$query->select(array("*"));

    		$query->from($db->qn('#__ucm_reports_download_detail'));
    		$query->where('DATE(' . $db->qn('created_at') . ') <= ' . $db->q($date));

    		$query->where($db->qn('state') . ' = 1');
    		$db->setQuery($query);
    		$result = $db->loadAssocList();

    		foreach($result as $key => $fileDetails)
    		{
    			 $tmpPosition = strpos($fileDetails['download_url'], '/tmp/');

    			 if ($tmpPosition !== false) {
				    $folderPath = substr($fileDetails['download_url'], 0, $tmpPosition + 5);
				    $fileName = substr($fileDetails['download_url'], $tmpPosition + 5);
				}
					 $filePath = JPATH_SITE . "/tmp/" . '/' . $fileName;

				    // Check if the file exists
				    if (file_exists($filePath)) { 

				        if (unlink($filePath)) {

				        	$newState = '0'; // Example of a value you want to update
				        $db   = Factory::getDBO();
						$query = $db->getQuery(true);

						// Fields to update
						$fields = array(
						    $db->quoteName('state') . ' = ' . $db->quote($newState)
						);

						// Conditions
						$conditions = array(
						    $db->quoteName('id') . ' = ' . (int) $fileDetails['id'],
						);

						// Build the query
						$query->update($db->quoteName('#__ucm_reports_download_detail'))
						      ->set($fields)
						      ->where($conditions); 
						$query = $query->__toString() . ' LIMIT 1';

						// Set and execute
						$db->setQuery($query);

							if($db->execute())
							{
								$suceessCount++;
								//return "File '$fileName' deleted successfully.";
							}
				        	}else {
				              // echo "Failed to delete file '$fileName'.";
				            }
				    } 
    		}
    		return $suceessCount;

    	}catch (Exception $e)
    	{
    		return false;
    	}

    }
}
