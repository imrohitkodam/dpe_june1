<?php
/**
 * @package    Com_Cluster
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

/**
 * Get Cluster Users controller class.
 *
 * @since  1.0.0
 */
class ClusterControllerClusterUsers extends BaseController
{
	/**
	 * Method to get user list depending on the client chosen.
	 *
	 * @return   null
	 *
	 * @since    1.0.0
	 */
	public function getUsersByClientId()
	{
		$app                       = Factory::getApplication();

		// DPE hack to dont show Trustee users from cluster
		$params                    = ComponentHelper::getParams('com_multiagency');
		$groupMultiagecnyTrusteeId = (INT) $params->get('multiagency_trustee_group');
 
		// Check for request forgeries.  DPE hack session is disable for now
		// if (!Session::checkToken())
		// {
		// 	echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
		// 	$app->close();
		// }

		$clusterIds = $app->input->getInt('cluster_id', 0);
		$userOptions = $allUsers = array();

		// Initialize array to store dropdown options

		// DPE Hack start to remove default option if field is multi selected
		if (!$app->input->getInt('multiple'))
		{
			$userOptions[] = HTMLHelper::_('select.option', "", Text::_('COM_CLUSTER_OWNERSHIP_USER'));
		}
		// DPE Hack end

		// Check cluster selected or not
		if ($clusterIds)
		{
			$clusterObj = ClusterFactory::model('ClusterUsers', array('ignore_request' => true));
			$clusterObj->setState('filter.block', 0);
			$clusterObj->setState('filter.cluster_id', $clusterIds);
			$clusterObj->setState('list.group_by_user_id', 1);
			$allUsers = $clusterObj->getItems();
		}

		// DPE Hack start to check the user  has permission to view the users or not 
		
		$user    = Factory::getUser();
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusters = $clusterUserModel->getUsersClusters($user->id);
		// Get UCM type ID
		foreach ($clusters as $key => $indCluster) {

				$clusters[$key] = $indCluster->cluster_id;
			}
		
		$dpeAdmin      = $user->authorise('core.manageall', 'com_cluster');

		if(array_diff((array)$clusterIds, $clusters) && !$dpeAdmin)
		{
			echo new JsonResponse($userOptions);
			$app->close();
		}
		//Dpe Hack End

		if (!empty($allUsers))
		{
			foreach ($allUsers as $user)
			{
				$userOptions[] = HTMLHelper::_('select.option', $user->user_id, trim($user->uname . ' (' . $user->uemail . ')'));
			}
		}

		echo new JsonResponse($userOptions);
		$app->close();
	}
}
