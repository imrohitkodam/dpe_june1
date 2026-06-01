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
use Joomla\CMS\Language\Text;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;

/**
 * TjGoPhish - CampaignReport Model
 *
 * @since  1.0.0
 */
class TjGoPhishModelCampaignReport extends ItemModel
{
	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $type    The table name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table  A Table object
	 *
	 * @since   1.0.0
	 */
	public function getTable($type = 'Campaign', $prefix = 'TjGoPhishTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get campaign report data
	 *
	 * @param   integer  $pk  An optional ID
	 *
	 * @return  object
	 *
	 * @since   1.0.0
	 */
	public function getItem($pk = null)
	{
		$table = $this->getTable();
		$table->load($pk);
		$this->item = (object) $table->getProperties();
		$user = Factory::getUser();

		/*
		Validate if a user can access the campaign report
		JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusters = $clusterUserModel->getUsersClusters($user->id);

		if (!empty($clusters))
		{
			$clusterIds = array();

			foreach ($clusters as $cluster)
			{
				$clusterIds[] = $cluster->cluster_id;
			}

			if (!in_array($this->item->cluster_id, $clusterIds))
			{
				$this->setError(Text::_("JERROR_ALERTNOAUTHOR"));

				return false;
			}
		}
		*/

		// Get com_cluster component status
		if (ComponentHelper::getComponent('com_cluster', true)->enabled)
		{
			// Get com_subusers component status
			$subUserExist = ComponentHelper::getComponent('com_subusers', true)->enabled;

			// Check user have permission to edit record of assigned cluster
			if ($subUserExist && !empty($this->item->cluster_id) && !$user->authorise('core.manageall', 'com_cluster'))
			{
				JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

				/*
				 *  @Todo migration for client 'com_cluster' in dpe
				 *  Com_dpe - Hack - start
				 *  Original Code : RBACL::check($user->id, 'com_cluster', 'core.addItem', 'com_tjucm', $clusterId)
				 */

				// Check user has permission for mentioned cluster
				if (!RBACL::check($user->id, 'com_cluster', 'core.downloadReport', 'com_tjgophish', $this->item->cluster_id))
				{
					$this->setError(Text::_("JERROR_ALERTNOAUTHOR"));

					return false;
				}
			}
		}

		if ($this->item->id)
		{
			$params = ComponentHelper::getParams('com_tjgophish');
			$goPhishApiEnd = $params->get('api_base_url');
			$goPhishApiKey = $params->get('api_key');

			$Http = new Http;
			$url = $goPhishApiEnd . 'api/campaigns/' . $this->item->gophish_campaign_id . '/results?api_key=' . $goPhishApiKey;
			$response = $Http->get($url);
			$response = json_decode($response->body);

			$this->item->report = $response;

			$url = $goPhishApiEnd . 'api/campaigns/' . $this->item->gophish_campaign_id . '/summary?api_key=' . $goPhishApiKey;
			$response = $Http->get($url);
			$response = json_decode($response->body);

			$this->item->summary = $response;
		}

		return $this->item;
	}
}
