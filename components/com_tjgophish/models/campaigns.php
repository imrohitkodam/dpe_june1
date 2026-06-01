<?php
/**
 * @package     TjGoPhish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Factory;

/**
 * TjGoPhish campaigns Model
 *
 * @since  1.0.0
 */
class TjGoPhishModelCampaigns extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id',
				'gophish_campaign_id',
				'gophish_campaign_title',
				'gophish_campaign_status',
				'cluster_id',
				'created_by'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return  string  An SQL query
	 */
	protected function getListQuery()
	{
		// Initialize variables.
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		// Create the base select statement.
		$query->select('a.*');
		$query->from($db->quoteName('#__tjgophish_campaign_ref', 'a'));

		// Filter: like / search
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->quote('%' . $search . '%');
			$query->where($db->quoteName('a.gophish_campaign_title') . ' LIKE ' . $search);
		}

		$user = Factory::getUser();

		// Show campaigns belonging to users cluster if com_cluster is installed and enabled
		$clusterExist = ComponentHelper::getComponent('com_cluster', true)->enabled;

		// Filter by cluster Id
		$clusterFilter = (INT) $this->getState('filter.cluster_id');

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

		
		// DPE Hack  checked for dpe admin
			if ($agencyTags && $user->authorise('core.manageall', 'com_cluster'))
			{	
				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
				$dashBoardModel = BaseDatabaseModel::getInstance('Dashboard', 'DpeModel');
				$clusterFilter  = $dashBoardModel->getClusterIdsByTags($agencyTags);
			}
			
		// Dpe Hack end	

		if ($clusterExist)
		{
			$query->select($db->qn('cl.name', 'clustertitle'));

			// Join over cluster table
			$query->join(
				"LEFT", $db->qn('#__tj_clusters', 'cl') . ' ON (' . $db->qn('a.cluster_id') . ' = ' . $db->qn('cl.id') . ')'
			);

			JLoader::import("components.com_cluster.includes.cluster", JPATH_ADMINISTRATOR);
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters = $clusterUserModel->getUsersClusters($user->id);

			$createCampaign = true;

			if (!empty($clusters))
			{
				$clusterIds = array();

				foreach ($clusters as $cluster)
				{
					// Dpe - hack start to exclude staff organization campaigns
					// If user is staff of organization then do not show the campaigns from that organization
					if (!$user->authorise('core.manageall', 'com_cluster'))
					{
						$createCampaign = RBACL::check($user->id, 'com_cluster', 'core.createCampaign', 'com_tjgophish', $cluster->cluster_id);
					}

					if ($createCampaign)
					{
						$clusterIds[] = $cluster->cluster_id;
					}
					// Dpe - hack end to exclude staff organization campaigns
				}

				//  $clusterIdAvailable is checked whether cluster id is available or not in tag getting array and in cluster getting int data so use array_intersect for tag and in_array for cluster.

				 $clusterIdAvailable = (is_array($clusterFilter)) ? array_intersect($clusterFilter, $clusterIds) : in_array($clusterFilter, $clusterIds)  ;


				if (!empty($clusterFilter) &&  $clusterIdAvailable)
				{	
					$clusterFilter = is_array($clusterFilter) ? implode(',', $clusterFilter) : $clusterFilter;
					$query->where($db->qn('a.cluster_id') . " IN (" . $clusterFilter .')');
				}
				else
				{
					$query->where($db->qn('a.cluster_id') . " IN ('" . implode("','", $clusterIds) . "')");
				}
			}
		}

		// Filter by campaign status
		$campaignStatus = $this->getState('filter.campaign_status');

		if ($campaignStatus != '')
		{
			$query->where($db->qn('a.gophish_campaign_status') . " = " . $db->quote($campaignStatus));
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering', $db->quoteName('a.id'));
		$orderDirn = $this->state->get('list.direction', 'desc');

		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

		return $query;
	}

	/**
	 * Method to get a list of campaigns.
	 *
	 * Overridden to inject convert the attribs field into a JParameter object.
	 *
	 * @return  mixed  An array of objects on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function getItems()
	{
		$items  = parent::getItems();

		// If no reference entry found then return
		if (empty($items))
		{
			return $items;
		}

		$params = ComponentHelper::getParams('com_tjgophish');
		$goPhishApiEnd = $params->get('api_base_url');
		$goPhishApiKey = $params->get('api_key');

		$Http = new Http;
		$response = $Http->get($params->get('api_base_url') . 'api/campaigns/' . '?api_key=' . $goPhishApiKey);
		$goPhishCampaigns = json_decode($response->body);

		$goPhishCampaignsTmp = array();

		foreach ($goPhishCampaigns as $goPhishGroup)
		{
			$goPhishCampaignsTmp[$goPhishGroup->id] = $goPhishGroup;
		}

		$goPhishCampaigns = $goPhishCampaignsTmp;

		foreach ($items as &$item)
		{
			if ($item->gophish_campaign_status == $goPhishCampaigns[$item->gophish_campaign_id]->status)
			{
				$item->gophish_campaign_status = $goPhishCampaigns[$item->gophish_campaign_id]->status;
			}

				$item->campaign_name = $goPhishCampaigns[$item->gophish_campaign_id]->name;
				$item->targets = $goPhishCampaigns[$item->gophish_campaign_id]->targets;
		}

		return $items;
	}
}
