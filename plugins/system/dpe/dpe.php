<?php
/**
 * @package    dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2020. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Users\Administrator\Model\UserModel;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\User\UserHelper;
use TJQueue\Admin\TJQueueProduce;
use Joomla\CMS\Date\Date;
use Dompdf\Dompdf;
use Dompdf\Options;



require_once JPATH_SITE . '/libraries/techjoomla/dompdf/autoload.inc.php';




JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);
JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);
JLoader::import('components.com_multiagency.includes.multiagency', JPATH_SITE);
jimport('techjoomla.tjnotifications.tjnotifications');


$lang = Factory::getLanguage();
$lang->load('dpe', JPATH_ADMINISTRATOR);

JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);

include_once JPATH_SITE . '/components/com_multiagency/includes/multiagency.php';

if (ComponentHelper::getComponent('com_tjqueue', true)->enabled)
{
	jimport('tjqueueproduce', JPATH_ADMINISTRATOR . '/components/com_tjqueue/libraries');
}

JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

/**
 * Methods supporting a list of DPE action.
 *
 * @since  1.0.0/var/www/ttpllt48-php8.local/public/dpej4re/plugins/system/dpe/dpe.php
 */
class PlgSystemDpe extends CMSPlugin
{
	/**
	 * Function used to redirect on dashboard
	 *
	 * @param   Object  $options  Containing joomla response detail with User object
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onUserAfterLogin($options)
	{	
		if($options['user']->requireReset == 1)
			{	JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);

		$dpeUtility     = DPE::utilities();
		$dpeUtility->getLanguageConstant();
		$itemId         = $dpeUtility->getItemId('index.php?option=com_users&view=profile');
		$app = Factory::getApplication();
		$msg = Text::_('JGLOBAL_PASSWORD_RESET_REQUIRED');
		$app->enqueueMessage($msg, 'warning');
		$app->redirect(Uri::root().'index.php?option=com_users&view=profile&layout=edit&Itemid=' . $itemId, false);
		$app->close();
	}
		// Check the use reset password parameter unset then redirect to dashboard
	if (!$options['user']->requireReset
		&& (($options['user']->authorise('core.adduser', 'com_multiagency'))
			|| ($options['user']->authorise('core.view.own', 'com_tjdashboard'))))
	{
			// Get Multi-Agency
		$app                       = Factory::getApplication();
		$multiagencyParams         = ComponentHelper::getParams('com_multiagency');
		$params                    = ComponentHelper::getParams('com_dpe');
		$MultiagencyTrusteeGroupId = (INT) $multiagencyParams->get('multiagency_trustee_group');
		$courseItemId              = (INT) $params->get('staffDashboardLink');

			/*  Comment home menu check till we finalise implementation
			 *  Check default menu id exist in return URL then redirect to dashboard
			 */
			if ((strpos($options['return'], 'Itemid=' . $courseItemId) == false) && $app->isClient('site'))
			{
				if (!$options['user']->authorise('core.manageall', 'com_cluster'))
				{
					$activeCluster = DPE::model('Users', array('ignore_request' => true))->getUsersActiveLicenceClusters($options['user']->id);

					// Get for menu for member login

					// If admin role cluster found then redirect on member dashboard
					if (! empty($activeCluster['adminClusters']))
					{
						$menuId = (INT) $params->get('dashboardlink');
					}
					elseif (empty($activeCluster['adminClusters']) && ! empty($activeCluster['staffClusters']))
					{
						// If admin role cluster not found then redirect on staff dashboard if user is staff of org
						$menuId = (INT) $params->get('staffDashboardLink');
					}
				}

				// Used for DPE & Super User
				if ($options['user']->authorise('core.manageall', 'com_cluster'))
				{
					$menuId = (INT) $params->get('dashboardlinkDpe');
				}

				// Menu for Trustee login
				if (in_array($MultiagencyTrusteeGroupId, $options['user']->groups))
				{
					$menuId = (INT) $params->get('trusteeDashboardLink');
				}

				$app->redirect(Route::_('index.php?Itemid=' . $menuId, false));
			}
		}
	}

	/**
	 * Function onAfterRoute Redirect user from home page to dahsboard
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterRoute()
	{ 	
		$app  = Factory::getApplication();
		$menu = $app->getMenu();
		$user = Factory::getUser();
		$db   = Factory::getDbo();
		
		if (Factory::getUser()->get('requireReset', 0) ==1)
		{
			return false;
		}
		if ($app->isClient('site') && $user->id && $menu->getActive() == $menu->getDefault() && ($app->input->get('option') == $menu->getActive()->query['option']))
		{
			// Get current url and if it is available in redirect then return true
			$currentUrl = Uri::getInstance()->toString();
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_redirect/src/Table/');		
			$linkTable = new \Joomla\Component\Redirect\Administrator\Table\LinkTable($db);

			if ($linkTable->id)
			{
				return true;
			}

			if (!$user->requireReset)
			{
				if ((($user->authorise('core.adduser', 'com_multiagency'))
					|| ($user->authorise('core.view.own', 'com_tjdashboard'))))
				{
					$params = ComponentHelper::getParams('com_dpe');

					// Get users cluster and redirect on member dashboard if user is admin or redirect on staff dashboard if user is only have staff org
					if (!$user->authorise('core.manageall', 'com_cluster'))
					{
						$activeCluster = DPE::model('Users', array('ignore_request' => true))->getUsersActiveLicenceClusters($user->id);

						// Get for menu for member login

						// If admin role cluster found then redirect on member dashboard
						if (! empty($activeCluster['adminClusters']))
						{
							$menuId = (INT) $params->get('dashboardlink');
						}
						elseif (empty($activeCluster['adminClusters']) && ! empty($activeCluster['staffClusters']))
						{
							// If admin role cluster not found then redirect on staff dashboard if user is staff of org
							$menuId = (INT) $params->get('staffDashboardLink');
						}
					}

					// Get Multi-Agency
					$multiagencyParams         = ComponentHelper::getParams('com_multiagency');
					$MultiagencyTrusteeGroupId = (INT) $multiagencyParams->get('multiagency_trustee_group');

					// Used for DPE & Super User
					if ($user->authorise('core.manageall', 'com_cluster'))
					{
						$menuId = (INT) $params->get('dashboardlinkDpe');
					}

					// Menu for Trustee login
					if (in_array($MultiagencyTrusteeGroupId, $user->groups))
					{
						$menuId = (INT) $params->get('trusteeDashboardLink');
					}
					
					$uri = Uri::getInstance(); 
					$url = $uri->toString();
					$input = Factory::getApplication()->input->get('class');

					if((!strpos($url,'Plugin.EditorButton')) && (!strpos($url,'id')))
					{ 
						
						if(((Factory::getApplication()->input->get('Itemid') == $menu->getActive()->id)) && empty($input))
						{
							$app->redirect(Route::_('index.php?Itemid=' . $menuId, false));
						}

					} 

					
				}
				else
				{
					// Redirection for staff dashboard
					$params = ComponentHelper::getParams('com_dpe');
					$menuId = (INT) $params->get('staffDashboardLink');
					$app->redirect(Route::_('index.php?Itemid=' . $menuId, false));
				}
			}
		}
	}

	/**
	 * Function is triggered when agency is create
	 *
	 * @param   INT    $id    Agency Id
	 * @param   ARRAY  $data  Field data
	 *
	 * @return  null
	 *
	 * @since  1.0.0
	 */
	public function onAfterMultiagencySave($id, $data)
	{

		// Check if Lead Consultant changed
		$table = SlaFactory::table("slaclusterxrefs");
		$table->load(array('license_id' => $data['id']));

		// Get Lead consultant Role Id
		/** @scrutinizer ignore-call */
		$params                  = ComponentHelper::getParams('com_multiagency');
		$leadConsultantRoleId    = (int) $params->get('organization_lead_consultant_role_id', '0');
		$organizationAdminRoleId = (int) $params->get('school_admin_role_id', '0');

		if (!class_exists('MultiagencyFrontendHelpers'))
		{
			JLoader::register('MultiagencyFrontendHelpers', JPATH_SITE . '/components/com_multiagency/helpers/multiagency.php');
			JLoader::load('MultiagencyFrontendHelpers');
		}

		$leadConsultantsGroups   = Factory::getUser($data['lead_consultant_id'])->groups;
		$leadConsultantUser      = Factory::getUser($data['lead_consultant_id']);
		$groupOAId               = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
		$helperObject            = new MultiagencyFrontendHelpers;
		// Get LC User group as per config
		$leadConsultantGroupId   = (int) $params->get('multiagency_leadconsultant_group', '0');

		if ( in_array('all', (array) $data['dpeadmins']))
		{ 
			$data['dpeadmins'] = array('all');
		}

		// Get Old LC details if any
		$xrfId                        = $table->id;
		$currentLeadConsultantId      = $table->lead_consultant_id;
		$currentLeadConsultantsGroups = Factory::getUser($currentLeadConsultantId)->groups;

		// License creation
		$db       = Factory::getDbo();
		$nullDate = $db->getNullDate();

		// Get school cluster id by providing license id
		$clusterModel         = ClusterFactory::model('Cluster');
		$schoolClusterDetail  = $clusterModel::getClusterByClient('com_multiagency', $data['multiagency_id']);

		// Insert SLA-Cluster-License-LeadConsultant Xref + Table Object for Saving data into xref
		$slaClusterXrefsTable = SlaFactory::table("slaclusterxrefs");

		$isNew = 0;

		// Check entry is exist in xref
		if ($xrfId)
		{
			$slaClusterXrefsTable->id     = $xrfId;
		}
		else
		{
			$isNew = 1;
		}

		if ($isNew)
		{
			// Multi licence realted data in #__tj_sla_cluster_xref table
			if ($data['multiyearlicence'])
			{
				$multiLicenceData = new stdClass;
				$multiLicenceData->time_measure = $data['time_measure'];
				$multiLicenceData->duration = $data['duration'];
				$slaClusterXrefsTable->params  = json_encode($multiLicenceData);
			}
		}

		// Create new Sla
		$newSla = null;

		// Following code will execute for licence which is upcoming and new
		if ($data['parent_id'] && $data['state'] == 3 && $isNew)
		{
			$clusterXrefsTable = SlaFactory::table("slaclusterxrefs");
			$clusterXrefsTable->load(array('license_id' => $data['parent_id']));

			if ($clusterXrefsTable->sla_id)
			{
				// Set parent sla id to upcoming licence
				$newSla = $clusterXrefsTable->sla_id;
			}
		}

		// If upcoming licence is not there then newSla is null and in this case new sla will create
		if (!$newSla)
		{
			$newSla = $this->createNewSla($data, $isNew);
		}

		$data['sla_id'] = !$newSla ? $data['sla_id'] : $newSla;
		$slaClusterXrefsTable->sla_id             = $data['sla_id'];
		$slaClusterXrefsTable->cluster_id         = $schoolClusterDetail->id;
		$slaClusterXrefsTable->license_id         = $data['id'];
		$slaClusterXrefsTable->lead_consultant_id = (int) $data['lead_consultant_id'];
		$slaClusterXrefsTable->notify_dpe_admin   = $data['notify_dpe_admin'] ? 1 : 0;
		$slaClusterXrefsTable->params   = ($multiLicenceData)?json_encode($multiLicenceData):'null';

		if ($newSla && $xrfId)
		{
			// Update sla id in activities and services table
			$slaActivitiesModel = SlaFactory::model('SlaActivities', array('ignore_request' => true));
			$slaActivitiesModel->updateActivitiesSlaId($data['id'], $data['sla_id']);

			$slaServicesModel = SlaFactory::model('slaservices', array('ignore_request' => true));
			$slaServicesModel->updateServicesSlaId($data['id'], $data['sla_id']);
		}

		if ($data['notify_dpe_admin'] && empty($data['dpeadmins']))
		{
			$slaClusterXrefsTable->dpeadmins = json_encode(array('all'));
		}
		elseif ($data['notify_dpe_admin'])
		{
			$slaClusterXrefsTable->dpeadmins = json_encode($data['dpeadmins']);
		}
		else
		{
			$slaClusterXrefsTable->dpeadmins = '';
		}

		$slaClusterXrefsTable->store();

		$subUserRoles = array('com_multiagency' => $data['multiagency_id'], 'com_cluster' => $schoolClusterDetail->id);

		// Check if old user is LC and Remove its related db entries from the system
		if ((!$isNew) && (in_array($leadConsultantGroupId, $currentLeadConsultantsGroups)))
		{

			foreach ($subUserRoles as $content => $clientId)
			{
				// Get and delete TJSU Map ID for Lead consultanr and organizationAdmin Role
				$leadConsultantTjsuId = $helperObject->getIdsUserAgencyRoleMap($currentLeadConsultantId, $leadConsultantRoleId, $clientId, $content);

				$tableInstance = RBACL::table('user');

				if ($leadConsultantTjsuId[0])
				{
					$tableInstance->delete($leadConsultantTjsuId[0]);
				}

				$organizationAdminTjsuIds = $helperObject->getIdsUserAgencyRoleMap($currentLeadConsultantId, $organizationAdminRoleId, $clientId, $content);

				foreach ($organizationAdminTjsuIds as $organizationAdminTjsuId)
				{
					$tableInstance->delete($organizationAdminTjsuId);
				}

				// Remove from cluster
				if ($content == 'com_cluster')
				{
					$ClusterUserTabel = ClusterFactory::table('ClusterUsers');
					$ClusterUserTabel->load(array('user_id' => $currentLeadConsultantId, 'cluster_id' => $clientId));

					if (property_exists($ClusterUserTabel, 'id'))
					{
						$ClusterUserModel = ClusterFactory::model('ClusterUser');
						$ClusterUserModel->delete($ClusterUserTabel->id);
					}
				}
			}
		}

		if (in_array($leadConsultantGroupId, $leadConsultantsGroups))
		{
			// Add subusers entries for below client context
			$subUserRoles = array('com_multiagency','com_cluster');

			$params                 = ComponentHelper::getParams('com_dpe');
			$licenceAssignedRoleIds = $helperObject->getLicenceAssignedRoleIds($data['multiagency_id']);
			$dpeTools               = new Registry($params->get('dpe_role_ids'));
			$roleIdstoSaved         = array();

			if (count($licenceAssignedRoleIds))
			{
				// Add role as per tool allocated to licence
				$roleIdstoSaved = array_intersect($dpeTools->get($organizationAdminRoleId), $licenceAssignedRoleIds);
			}

			$additionalRole = new Registry($params->get('additional_role'));

			if (count($additionalRole[$organizationAdminRoleId]))
			{
				$roleIdstoSaved = array_merge($roleIdstoSaved, $additionalRole[$organizationAdminRoleId]);
			}

			// Add core role Id
			array_push($roleIdstoSaved, $organizationAdminRoleId);
			array_push($roleIdstoSaved, $leadConsultantRoleId);
			$userFormModel = Multiagency::model('userform', array('ignore_request' => true));

			foreach ($roleIdstoSaved as $roleId)
			{
				foreach ($subUserRoles as $content)
				{
					if ($content == 'com_multiagency')
					{
						$leadConsultantMapId     = $helperObject->getIdsUserAgencyRoleMap($leadConsultantUser->id, $roleId, $data['multiagency_id'], $content);
						$asssignUserData['id'] = $leadConsultantMapId[0];
					}
					else
					{
						$organizationAdminMapId  = $helperObject->getIdsUserAgencyRoleMap($leadConsultantUser->id, $roleId, $schoolClusterDetail->id, $content);
						$asssignUserData['id'] = $organizationAdminMapId[0];
					}

					$asssignUserData            = array();
					$asssignUserData['user_id'] = $leadConsultantUser->id;

					if ($content == 'com_multiagency')
					{
						$asssignUserData['client_id'] = $data['multiagency_id'];
					}
					else
					{
						$asssignUserData['client_id'] = $schoolClusterDetail->id;
					}

					$asssignUserData['content'] = $content;
					$asssignUserData['role_id'] = $roleId;

					// Assign user to role
					$userFormModel->assignRoleToUser($asssignUserData);
				}
			}

			// Add lead consutant to School Admin Group
			if (!in_array($groupOAId, $leadConsultantsGroups))
			{
				UserHelper::addUserToGroup($leadConsultantUser->id, $groupOAId);
			}
		}

		if (in_array($leadConsultantGroupId, $currentLeadConsultantsGroups))
		{
			// Check if user Lead consultant for any other organisation and Remove school admin group
			$groupAlreadyAssigned = $helperObject->getIdsUserAgencyRoleMap($currentLeadConsultantId, $leadConsultantRoleId);

			if (empty($groupAlreadyAssigned))
			{
				UserHelper::removeUserFromGroup($currentLeadConsultantId, $groupOAId);
			}
		}

		if (in_array($leadConsultantGroupId, $leadConsultantsGroups))
		{
			// Add DPE Lead Consultant in organization cluster
			
			PluginHelper::importPlugin('system');
			Factory::getApplication()->triggerEvent('onAfterAddUser', array($schoolClusterDetail->id, array($data['lead_consultant_id'])));
		}

		// Create activities only for active licence
		if (empty($xrfId) && $data['state'] != 3)
		{
			$data['cluster_id'] = $schoolClusterDetail->id;
			$activityLimit      = ComponentHelper::getParams('com_sla')->get('activityLimit');
			$slaActivitiesModel = SlaFactory::model('SlaActivities', array('ignore_request' => true));

			foreach ($data['activity'] as $activityType => $activityCount)
			{
				// Server side validation for count
				$activityCount = ($activityCount > $activityLimit) ? $activityLimit : $activityCount;

				if ($activityCount)
				{
					$slaActivitiesModel->createActivities($data, $activityType, $activityCount);
				}
			}
		}
		elseif ($data['state'] == 3)
		{
			if ($xrfId)
			{
				$slaActivitiesTable = SlaFactory::table("SlaActivities");
				$slaActivitiesTable->load(array('id' => $data['id'], 'state' => 1));

				// Update activities state if available
				if ($slaActivitiesTable->id)
				{
					/* Code is removed for archiving activites
					$slaModel = SlaFactory::model('SlaActivities', array('ignore_request' => true));
					$slaModel->updateActivitiesState($data['id'], 3); */
				}
			}
			else
			{ 
				// State 3 used for upcoming status
				$data['cluster_id'] = $schoolClusterDetail->id;

				// Add key into queue data
				$data['key'] = ComponentHelper::getParams('com_dpe')->get('private_key_storage_cron');
				$this->addActivityDataToQueue($data);
			}
		}

		if (count((array) $data['tools']))
		{
			// Save tools to licence xref table
			$this->saveSlaTool($data);

			// Add data in queue table to update the roles as per tools and only for active licence
			if ($data['state'] == 1)
			{
				$this->addToQueue($data);
			}
		}

		// Update upcoming licence tools only on active licence edit
		if ($xrfId && (count((array)$data['tools']) || $newSla))
		{
			JLoader::register('MultiagencyModelLicences', JPATH_ADMINISTRATOR . '/components/com_multiagency/models/licences.php');
			$multiagencyModelLicences = BaseDatabaseModel::getInstance('Licences', 'MultiagencyModel');

			// Get parent id
			$licenceTable = Multiagency::table('licence');
			$licenceTable->load(array('id' => $data['id'], 'state' => 1));
			$parentId = $licenceTable->parent_id ? $licenceTable->parent_id : $data['id'];

			// State 3 used for upcoming licence
			$multiagencyModelLicences->setState('filter.state', 3);
			$multiagencyModelLicences->setState('filter.parent_id', $parentId);
			$upcomingLicences = $multiagencyModelLicences->getItems();

			if (! empty($upcomingLicences))
			{
				$slaActivitiesModel = SlaFactory::model('SlaActivities', array('ignore_request' => true));

				foreach ($upcomingLicences as $upcomingLicence)
				{
					$data['id'] = $upcomingLicence->id;
					$this->saveSlaTool($data);

					if ($newSla)
					{
						$slaActivitiesModel->updateActivitiesSlaId($data['id'], $data['sla_id']);

						// Udpate sla id in cluster xref table
						$clusterxrefs = SlaFactory::table("slaclusterxrefs");
						$clusterxrefs->load(array('license_id' => $data['id']));
						$clusterxrefs->sla_id = $data['sla_id'];
						$clusterxrefs->store();
					}
				}

				$this->updateUpcomingLicenceDates($upcomingLicences, $data['end_date']);
			}
		}

		$this->onAfterOrgSlaUpdateLeadstaffMember($data['multiagency_id']);
	}

	/**
	 * Method to save SLA tool to licence xref
	 *
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function saveSlaTool($data)
	{
		$params       = ComponentHelper::getParams('com_dpe');
		$allTools     = new Registry($params->get('allTools'));
		$allToolsdata = $allTools->get('tools');

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');

		$savedToolClients = $this->getSavedToolsFromLicenceXref($data['id']);
		$toolClients      = array_keys($data['tools']);

		foreach ($toolClients as $toolClient)
		{
			$toolSupportingClients = $allToolsdata->$toolClient->supporting_clients;

			if ( count((array) $toolSupportingClients))
			{   
				$toolClients  = array_merge($toolClients, $toolSupportingClients);
			}
		}

		$oldClients = isset($savedToolClients) ? array_diff($savedToolClients, $toolClients):array();

		// Delete old tools from licence xref table
		foreach ($oldClients as $oldClient)
		{
			// Get the table of licence xref
			$licenceXrefTable = Multiagency::table("licencexref");

			$licenceXrefTable->load(array('licence_id' => $data['id'], 'client' => $oldClient));

			if ($licenceXrefTable->id)
			{
				$licenceXrefTable->delete($licenceXrefTable->id);
			}
		}

		foreach ($data['tools'] as $toolClient => $tool)
		{
			// Build array to store data into xref for tool client configured in SLA params
			$toolData                   = $allToolsdata->$toolClient;
			$licenceXref                = array();
			$licenceXref['licence_id']  = $data['id'];
			$licenceXref['tool_client'] = $toolData->tool_client;
			$licenceXref['type']        = 'all';
			$licenceXref['total_seats'] = ($data['total_seats'])?$data['total_seats']:'0';
			$licenceXref['used_seats']  = ($data['used_seats'])?$data['used_seats']:'0';
			$licenceXref['params']  	= 'null';

			$this->addEntryToLicenceXref($licenceXref);

			foreach ($toolData->supporting_clients as $client)
			{
				// Build array to store data into xref for supporting clients configured in SLA params
				$licenceXref                = array();
				$licenceXref['licence_id']  = $data['id'];
				$licenceXref['tool_client'] = $client;
				$licenceXref['type']        = 'all';
				$licenceXref['total_seats'] = ($data['total_seats'])?$data['total_seats']:'0';
				$licenceXref['used_seats']  = ($data['used_seats'])?$data['used_seats']:'0';
				$licenceXref['params']  	= 'null';

				$this->addEntryToLicenceXref($licenceXref);
			}
		}
	}

	/**
	 * Method to create new entry for SLA
	 *
	 * @param   mixed  $data   The associated data for the form.
	 * @param   int    $isNew  New SLA or old SLA
	 *
	 * @return  integer|boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function createNewSla($data, $isNew)
	{
		// Get tools from selected SLA
		$slaLibrary  = SlaSla::getInstance($data['sla_id']);
		$tools       = $slaLibrary->getSlaTools();
		$slaParams   = new Registry($slaLibrary->params);
		$slaParams->remove('tools');

		$jsonTools = json_encode($tools);
		$tools     = json_decode($jsonTools, true);

		$toolsClient = array_column((array) $tools, 'tool_client');
		$newClients  = array_keys((array) $data['tools']);

		// Sort the array client
		sort($newClients);
		sort($toolsClient);

		// If tool from params of SLA and tools selected by SLA are not same then create new SLA
		if ($newClients != $toolsClient)
		{
			$multiagencytable = Multiagency::table('multiagency');
			$multiagencytable->load($data['multiagency_id']);

			$params       = ComponentHelper::getParams('com_dpe');
			$allTools     = new Registry($params->get('allTools'));
			$allToolsdata = $allTools->get('tools');

			$params = new stdClass;
			$params->tools = new stdClass;
			foreach ($newClients as $newClient)
			{
				if ($slaParams->get('activityTypes'))
				{
					$params->activityTypes = $slaParams->get('activityTypes');
				}

				if ($slaParams->get('kbonly'))
				{
					$params->kbonly = $slaParams->get('kbonly');
				}

				if (!empty($data['show_in_sla_list']))
				{
					$params->show_in_sla_list = $data['show_in_sla_list'];
				}
				elseif ($slaParams->get('show_in_sla_list') && !$isNew)
				{
					$params->show_in_sla_list = $slaParams->get('show_in_sla_list');
				}

				$params->tools->$newClient = $allToolsdata->$newClient;
			}

			// Build Json to store into new created SLA
			$registery   = new Registry($params);
			$paramsTools = $registery->toString();

			$slaData = array();

			// If licence open in edit mode then update existing sla id
			if (!$isNew && !$slaParams->get('core'))
			{
				$slaData['id'] = $data['sla_id'];
			}

			// Build SLA name
			if ($isNew && $data['new_sla'] && !empty($data['show_in_sla_list']))
			{
				$slaData['title']  = $data['new_sla'];
			}
			else
			{
				if (!$slaData['id'])
				{
					$slaData['title'] = $multiagencytable->title . ' ' . $slaLibrary->title;
				}
			}

			$slaData['params'] = $paramsTools;

			$slaModel = SlaFactory::model('sla');
			$slaModel->save($slaData);

			// Return saved sla id
			return $slaModel->getState('sla.id');
		}

		return false;
	}

	/**
	 * Method to add entry into lesson xref
	 *
	 * @param   mixed  $licenceId  The associated data for the form.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getSavedToolsFromLicenceXref($licenceId)
	{
		if (!$licenceId)
		{
			return false;
		}

		// Query to get saved tools from licence xref table by licence id
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);

		$query->select('tlx.client');

		$query->from($db->quoteName('#__tjmultiagency_licences', 'tl'));
		$query->join(
			'LEFT', $db->quoteName('#__tjmultiagency_licences_xref', 'tlx') .
			' ON ' . $db->quoteName('tl.id') . '=' . $db->quoteName('tlx.licence_id')
		);
		$query->where($db->qn('tl.id') . ' = ' . (int) $licenceId);

		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Method to add entry into lesson xref
	 *
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function addEntryToLicenceXref($data)
	{
		// Get the table of licence xref
		$licenceXrefTable = Multiagency::table("licencexref");
		$licenceXrefTable->load(array('licence_id' => $data['licence_id'], 'client' => $data['tool_client']));

		// Insert licence-Tool Saving data into licence xref
		$licenceXref = Multiagency::table("licencexref");

		if ($licenceXrefTable->id)
		{
			$licenceXref->id = $licenceXrefTable->id;
		}

		$ucmTable = Table::getInstance('type', 'TjucmTable');
		$ucmTable->load(array('unique_identifier' => $data['tool_client']));

		// Build object to save record into xref table
		$licenceXref->licence_id  = $data['licence_id'];
		$licenceXref->client      = $data['tool_client'];
		$licenceXref->type        = 'all';
		$licenceXref->total_seats = ($data['total_seats'])?$data['total_seats']:'0';
		$licenceXref->used_seats = ($data['used_seats'])?$data['used_seats']:'0';
		$licenceXref->params  	= 'null';

		// If Ucm id present then add ucm id as tool id else it will 0
		if ($ucmTable->id)
		{
			$licenceXref->client_id = $ucmTable->id;
		}
		else
		{
			$licenceXref->client_id = 0;
		}

		// Save into licence xref
		$licenceXref->store();
	}

	/**
	 * Adds additional fields to the user editing form
	 *
	 * @param   Form  $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   3.9.0
	 */
	public function onContentPrepareForm($form, $data)
	{
		$app = Factory::getApplication();

		if (!$app->isClient('site'))
		{
			return;
		}

		if (!($form instanceof Form))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		// Check we are manipulating a valid form - we only display this on license form while editing.
		$name = $form->getName();

		$allowedForms = array ('com_multiagency.licence', 'com_sla.slaactivity', 'com_timelog.activityform');

		if (!in_array($name, $allowedForms))
		{
			return true;
		}

		$licenseId = $app->input->getInt('licence_id', 0);

		// We only display this if user has not consented before
		if (is_object($data) && ($name == 'com_multiagency.licence'))
		{
			$licenseId = isset($data->id) ? $data->id : 0;

			if (empty($licenseId))
			{
				$licenseId = $app->input->getInt('id', 0);
				$form->setFieldAttribute('multiple_count', 'required', 'true');
				$form->setFieldAttribute('duration', 'required', 'true');
			}

			if (empty($licenseId))
			{
				return true;
			}

			$table = SlaFactory::table("slaclusterxrefs");
			$table->load(array('license_id' => $licenseId));

			$registry         = new Registry($table->params);
			$multiLicenceData = $registry->toArray();

			$data->sla_id             = $table->sla_id;
			$data->lead_consultant_id = $table->lead_consultant_id;
			$data->notify_dpe_admin   = $table->notify_dpe_admin;
			$data->dpeadmins          = $table->dpeadmins;

			if (! empty($multiLicenceData))
			{
				$data->duration           = $multiLicenceData['duration'];
				$data->time_measure       = $multiLicenceData['time_measure'];
				$form->setFieldAttribute('end_date', 'readonly', 'true');
			}

			if (!empty($table->id))
			{
				$form->setFieldAttribute('sla_id', 'readonly', 'true');
			}
		}
		elseif (is_object($data) && $name == 'com_sla.slaactivity')
		{
			// Add Record
			if (!$data->id && $licenseId)
			{
				$table = SlaFactory::table("slaclusterxrefs");
				$table->load(array('license_id' => $licenseId));

				$data->lead_consultant_id = $table->lead_consultant_id;
				$data->license_id         = $licenseId;
			}
			// Edit Record
			elseif ($data->id)
			{
				JLoader::import('components.com_jlike.tables.todos', JPATH_ADMINISTRATOR);
				$todoTable = Table::getInstance('Todos', 'JlikeTable');
				$todoTable->load($data->todo_id);

				$data->lead_consultant_id = $todoTable->assigned_to;
				$data->activity_name      = $todoTable->title;
				$data->activity_desc      = $todoTable->sender_msg;
				$data->ideal_time         = $todoTable->ideal_time;
				$data->start_date         = $todoTable->start_date;
				$data->due_date           = $todoTable->due_date;
			}
		}
		elseif (is_object($data) && $name == 'com_timelog.activityform')
		{
			$slaActivity = $app->input->getInt('sla_activity', 0);

			if (!empty($licenseId))
			{
				$form->setFieldAttribute('license_id', 'readonly', 'true');
				$data->license_id = $licenseId;
			}

			if (!empty($slaActivity))
			{
				$data->client_id = $slaActivity;
			}
		}
	}

	/**
	 * Function is trigger for School manager to remove school admins from list
	 *
	 * @param   array  $userGroupIds  Logged in user group ids.
	 *
	 * @return  string
	 *
	 * @since   1.0.0
	 */
	public function onStaffLoad($userGroupIds)
	{
		$joomlaUserGroups = $this->params->get('joomlausergroups');

		if (!array_intersect((array )$joomlaUserGroups, $userGroupIds))
		{
			$db = Factory::getDbo();

			return $db->qn('r.id') . ' != ' . $this->params->get('subuserrole', 0);
		}
	}

	/**
	 * Function will trigger on After user has create update sla activity
	 *
	 * @param   Object   $data   SLA Activity Data
	 * @param   Boolean  $isNew  It's flag to tell us is Sla activity is new created or Already existed has updated
	 *
	 * @return  string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onAfterSlaActivitySave($data, $isNew)
	{
		if (!empty($data) && !empty($data->slaActivityData['cluster_user']))
		{
			JLoader::import('contentform', JPATH_SITE . '/components/com_jlike/models');

			$contentData = array();

			$contentData['element']    = 'com_sla.slaactivity';
			$contentData['url']        = 'index.php?option=com_sla&view=slaactivity&layout=edit&id=' . $data->id . '&licence_id=' . $data->license_id;
			$contentData['element_id'] = $data->id;
			$contentData['title']      = $data->slaActivityData['activity_name'];
			$contentId                 = JlikeModelContentForm::getContentID($contentData);

			JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);

			// Get SLA details
			$slaClusterXrefTable = SlaFactory::table("slaclusterxrefs");
			$slaClusterXrefTable->load(array('license_id' => $data->license_id));

			$todoData                = array();

			$db    = Factory::getDbo();
			$nullDate = $db->getNullDate();

			// This is call to check the user is added to todo or not
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jlike/tables');
			$jlikeTodoTable = Table::getInstance('Todos', 'JlikeTable');
			$jlikeTodoTable->load(array('content_id' => $contentId, 'assigned_to' => $data->slaActivityData['cluster_user']));

			// This is call to check the existing todo
			$todoTable = Table::getInstance('Todos', 'JlikeTable');
			$todoTable->load(array('content_id' => $contentId));

			$todoData['id']          = $todoTable->id;
			$todoData['assigned_to'] = $data->slaActivityData['cluster_user'];
			$todoData['start_date']  = (!empty($data->slaActivityData['start_date'])) ? $data->slaActivityData['start_date'] : $nullDate;
			$todoData['due_date']    = (!empty($data->slaActivityData['due_date'])) ? $data->slaActivityData['due_date'] : $nullDate;
			$todoData['title']       = $data->slaActivityData['activity_name'];
			$todoData['sender_msg']  = $data->slaActivityData['activity_desc'];
			$todoData['ideal_time']  = $data->slaActivityData['ideal_time'];
			$todoData['content_id']  = $contentId;
			$todoData['parent_id']   = $data->todo_id;
			$slaServiceObj           = SlaSlaService::getInstance();

			$jlikeTodoId             = $slaServiceObj->saveTodo($todoData);

			$dueDate = Factory::getDate($data->slaActivityData['due_date']);
			$prevDuedate = Factory::getDate($data->slaActivityData['prev_due_date']);

			if ($data->slaActivityData['due_date'] != $db->getNullDate()
				&& ($jlikeTodoTable->id
					&& $jlikeTodoTable->due_date != $nullDate
					&& $data->slaActivityData['due_date'] != $jlikeTodoTable->due_date)
				&& !empty($data->slaActivityData['cluster_user'])
				&& !empty($data->slaActivityData['due_date']))
			{
				$this->notifyRescheduleActivity($data->slaActivityData, $slaClusterXrefTable->cluster_id);
			}
			elseif (empty($jlikeTodoTable->id) && !empty($data->slaActivityData['due_date'])
				|| ($jlikeTodoTable->id
					&& $jlikeTodoTable->due_date == $nullDate
					&& $data->slaActivityData['due_date'] != $nullDate)
				&& !empty($data->slaActivityData['cluster_user'])
				&& !empty($data->slaActivityData['due_date']))
			{
				$this->notifyActivity($data->slaActivityData, $slaClusterXrefTable->cluster_id);
			}
		}
	}

	/**
	 * Function will trigger on After user has create update sla activity
	 *
	 * @param   Array  $data  Campaign Data
	 *
	 * @return  string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onAfterAddCampaign($data)
	{
		if (!empty($data))
		{
			$params                  = ComponentHelper::getParams('com_multiagency');
			$organizationAdminRoleId = (int) $params->get('school_admin_role_id');

			// Get users by roles
			$subusersModelUsers = RBACL::model('users', array('ignore_request' => true));
			$subusersModelUsers->setState('filter.client_id', $data['cluster_id']);
			$subusersModelUsers->setState('filter.role_id', $organizationAdminRoleId);
			$subusersModelUsers->setState('filter.client', 'com_cluster');
			$subusersModelUsers->setState('group_by', 'user_id');
			$subusersModelUsers->setState('filter.state', 0);
			$clusterUsers = $subusersModelUsers->getItems();

			// School manager/Admin users
			foreach ($clusterUsers as $clusterUser)
			{
				if ($clusterUser->user_id)
				{
					$this->sendAddCampaignEmails($data, (int) $clusterUser->user_id);
				}
			}
		}
	}

	/**
	 * Method to send Add Campaign Emails.
	 *
	 * @param   array  $data    The ids actions.
	 * @param   INT    $userId  user id to send email.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function sendAddCampaignEmails($data, $userId)
	{
		$app              = Factory::getApplication();
		$menu             = $app->getMenu();
		$campaignLink     = 'index.php?option=com_tjgophish&view=campaign&layout=edit';
		$campaignMenuItem = $menu->getItems('link', 'index.php?option=com_tjgophish&view=campaign&layout=edit', true);

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
		$clustersTable = Table::getInstance('Clusters', 'ClusterTable');
		$clustersTable->load(array('id' => $data['cluster_id']));

		$client = "com_dpe";
		$key    = "gophishAddCampaign";

		$recipients = array (
			// Add specific to, cc (optional), bcc (optional)
			'email' => array (
				'to' => array (Factory::getUser($userId)->email)
			)
		);

		$config       = Factory::getConfig();
		$mailfrom     = $config->get('mailfrom');
		$fromname     = $config->get('fromname');

		// Get user data
		$userInfo = Factory::getUser($userId);

		// Get content data
		$phishingSimulation               = new stdClass;
		$phishingSimulation->schoolname   = $clustersTable->name;
		$phishingSimulation->campaignLink = Uri::root() . substr(
			Route::_(
				$campaignLink . '&id=' . $data['campaignId'] . '&Itemid=' . $campaignMenuItem->id, false
			), strlen(Uri::base(true)) + 1
		);

		$replacements                     = new stdClass;
		$replacements->user               = $userInfo;
		$replacements->phishingSimulation = $phishingSimulation;

		$options = new Registry;
		$options->set('from', $mailfrom);
		$options->set('fromname', $fromname);

		Tjnotifications::send($client, $key, $recipients, $replacements, $options);
	}

	/**
	 * Function will trigger on sla activity status changed
	 *
	 * @param   Object  $data  SLA Activity Data
	 *
	 * @return  string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onAfterdpeOnTodoAfterSave($data)
	{
		if (!empty($data))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			// Fields to update.
			$fields     = array(
				$db->quoteName('status') . ' = ' . $db->quote($data['status']),
				$db->quoteName('modified_date') . ' = ' . $db->quote($data['modified_date'])
			);
			$conditions = array($db->quoteName('parent_id') . ' = ' . (int) $data['id'] );
			$query->update($db->quoteName('#__jlike_todos'))->set($fields)->where($conditions);

			$db->setQuery($query);
			$db->execute();

			// Get data for sending sal activity status via email notification to user
			$query = $db->getQuery(true);
			$query->select($db->qn(array('id', 'status', 'assigned_by', 'assigned_to','parent_id', 'due_date', 'title')))
			->from($db->qn('#__jlike_todos'))
			->where($db->qn('#__jlike_todos.parent_id') . ' = ' . (int) $data['id']);

			$db->setQuery($query);
			$updatedToData = $db->loadAssocList();

			$this->OnAfterSlaActivityStatusChange($updatedToData);
		}
	}

	/**
	 * Function will trigger onafter sla activity status changed
	 *
	 * @param   Object  $updatedToData  updatedToData
	 *
	 * @return  string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function OnAfterSlaActivityStatusChange($updatedToData)
	{
		$params = DPE::config();
		$dateTimeFormat = (String) $params->get('dateTimeFormat');
		$db    = Factory::getDbo();
		$nullDate = $db->getNullDate();

		if (!empty($updatedToData))
		{
			foreach ($updatedToData as $data)
			{
				if (empty($data['assigned_to']))
				{
					continue;
				}

				// Get SLA details
				$slaActivitiesTable = SlaFactory::table("SlaActivities");
				$slaActivitiesTable->load(array('todo_id' => $data['parent_id']));

				// If state is change for archived activity then don't send notification
				if ($slaActivitiesTable->state == 2)
				{
					return true;
				}

				$clusterTable = ClusterFactory::table("Clusters");
				$clusterTable->load(array('id' => $slaActivitiesTable->cluster_id));

				// Email Notification
				$client = "com_sla";
				$key    = "slaActivityUpdate";

				// Get user data
				$userInfo = Factory::getUser($data['assigned_to']);

				$recipients = array (
					// Add specific to, cc (optional), bcc (optional)
					'email' => array (
						'to' => array ($userInfo->email)
					)
				);

				$app          = Factory::getApplication();
				$config       = Factory::getConfig();
				$mailfrom     = $config->get('mailfrom');
				$fromname     = $config->get('fromname');

				// Get assigner data
				$assignerInfo = Factory::getUser($data['assigned_by']);

				switch ($data['status'])
				{
					case 'I':
					$status = Text::_('COM_SLA_ACTIVITY_STATUS_INCOMPLETE');
					break;
					case 'C':
					$status = Text::_('COM_SLA_ACTIVITY_STATUS_COMPLETE');
					break;
					case 'CN':
					$status = Text::_('COM_SLA_ACTIVITY_STATUS_CANCELLED');
					break;
					default:
					$status = Text::_('COM_SLA_ACTIVITY_STATUS_INCOMPLETE');
					break;
				}

				$contentData                 = new stdClass;
				$contentData->name   = $data['title'];
				$contentData->date   = Factory::getDate($data['due_date'])->format($dateTimeFormat);
				$contentData->status = $status;
				$contentData->schoolname = $clusterTable->name;
				$contentData->assigner = $assignerInfo->name;
				$contentData->dueDateText='';


				if ($data['due_date'] != '0000-00-00 00:00:00')
				{
					$contentData->dueDateText = Text::_('COM_NOTIFICATION_DUE_DATE_EMAIL') . ' ' . $contentData->date;
				}
				else
				{
					$contentData->dueDateText = "";
				}

				$replacements           = new stdClass;
				$replacements->user     = $userInfo;
				$replacements->assigner = $assignerInfo;
				$replacements->content  = $contentData;

				$options = new Registry;
				$options->set('subject', $contentData);
				$options->set('from', $mailfrom);
				$options->set('fromname', $fromname);

				Tjnotifications::send($client, $key, $recipients, $replacements, $options);
			}
		}
	}

	/**
	 * Function will trigger on After save new activity
	 *
	 * @param   Object  $data       SLA Activity Data
	 * @param   int     $clusterId  It's flag to tell us is Sla activity is new created or Already existed has updated
	 *
	 * @return  string
	 *
	 * @since   1.1.0
	 */
	public function notifyActivity($data, $clusterId)
	{
		$params = DPE::config();
		$dateTimeFormat = (String) $params->get('dateTimeFormat');

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
		$clustersTable = Table::getInstance('Clusters', 'ClusterTable');
		$clustersTable->load(array('id' => $clusterId));

		$client = "com_sla";
		$key    = "notifyActivity";

		// Get user data
		$userInfo = Factory::getUser($data['cluster_user']);

		$recipients = array (
			// Add specific to, cc (optional), bcc (optional)
			'email' => array (
				'to' => array ($userInfo->email)
			)
		);

		$app          = Factory::getApplication();
		$config       = Factory::getConfig();
		$mailfrom     = $config->get('mailfrom');
		$fromname     = $config->get('fromname');

		// Get assigner data
		$assignerInfo = Factory::getUser();

		// Get content data
		$activity = new stdClass;
		$activity->name = $data['activity_name'];
		$activity->description = $data['activity_desc'];
		$activity->date = Factory::getDate($data['due_date'])->format($dateTimeFormat);
		$activity->schoolname = $clustersTable->name;

		$replacements           = new stdClass;
		$replacements->user     = $userInfo;
		$replacements->assigner = $assignerInfo;
		$replacements->activity  = $activity;

		$options = new Registry;
		$options->set('subject', $activity);
		$options->set('from', $mailfrom);
		$options->set('fromname', $fromname);

		Tjnotifications::send($client, $key, $recipients, $replacements, $options);
	}

	/**
	 * Function will trigger on After reschedule activity
	 *
	 * @param   Object  $data       SLA Activity Data
	 * @param   int     $clusterId  It's flag to tell us is Sla activity is new created or Already existed has updated
	 *
	 * @return  string
	 *
	 * @since   1.1.0
	 */
	public function notifyRescheduleActivity($data, $clusterId)
	{
		$params = DPE::config();
		$dateTimeFormat = (String) $params->get('dateTimeFormat');

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
		$clustersTable = Table::getInstance('Clusters', 'ClusterTable');
		$clustersTable->load(array('id' => $clusterId));

		$client = "com_sla";
		$key    = "notifyRescheduleActivity";

		// Get user data
		$userInfo = Factory::getUser($data['cluster_user']);

		$recipients = array (
			// Add specific to, cc (optional), bcc (optional)
			'email' => array (
				'to' => array ($userInfo->email)
			)
		);

		$app          = Factory::getApplication();
		$config       = Factory::getConfig();
		$mailfrom     = $config->get('mailfrom');
		$fromname     = $config->get('fromname');

		// Get assigner data
		$assignerInfo = Factory::getUser();

		// Get content data
		$activity = new stdClass;
		$activity->name = $data['activity_name'];
		$activity->description = $data['activity_desc'];
		$activity->date = Factory::getDate($data['due_date'])->format($dateTimeFormat);
		$activity->schoolname = $clustersTable->name;

		$replacements           = new stdClass;
		$replacements->user     = $userInfo;
		$replacements->assigner = $assignerInfo;
		$replacements->activity  = $activity;

		$options = new Registry;
		$options->set('subject', $activity);
		$options->set('from', $mailfrom);
		$options->set('fromname', $fromname);

		Tjnotifications::send($client, $key, $recipients, $replacements, $options);
	}

	/**
	 * Function used as a trigger after user complete a lesson. While considering attempt grading
	 *
	 * @param   INT  $lessonId  Lesson ID
	 * @param   INT  $attempt   attempt number of the user
	 * @param   INT  $actorId   User who is attempting the lesson
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterLessonCompletion($lessonId, $attempt, $actorId)
	{
		if (!($lessonId && $actorId))
		{
			return;
		}

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jlike/tables');
		$contentTable = Table::getInstance('Content', 'JlikeTable');
		$contentTable->load(array('element_id' => $lessonId, 'element' => 'com_tjlms.lesson'));
		$contentId = 0;

		if (property_exists($contentTable, 'id'))
		{
			$contentId = $contentTable->id;
		}

		if (!$contentId)
		{
			return;
		}

		$todoTable = Table::getInstance('recommendation', 'JlikeTable');
		$todoTable->load(array('content_id' => $contentId, 'assigned_to' => $actorId));
		$todoid = 0;

		if (property_exists($todoTable, 'id'))
		{
			$todoid = $todoTable->id;
		}

		if (!$todoid)
		{
			return;
		}

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jlike/models');
		/** @var $JLikeTodoModel JlikeModelTodo */
		$JLikeTodoModel = BaseDatabaseModel::getInstance('Todo', 'JLikeModel');
		$todoContent = $JLikeTodoModel->getContent($todoid);

		if ($todoContent->status == 'C')
		{
			return;
		}

		$item = DPE::model('interaction')->getItem($todoid);
		$JLikeContentModel = BaseDatabaseModel::getInstance('Content', 'JLikeModel');
		$contentData = $JLikeContentModel->getData($todoContent->content_id);
		$savedInteractions = json_decode($contentData->params);

		$jlikeTjlmslessonPlugin = PluginHelper::getPlugin('content', 'jlike_tjlmslesson');
		$enabledInterations = json_decode($jlikeTjlmslessonPlugin->params);
		$updateParent = true;

		foreach ($savedInteractions as $key => $value)
		{
			if ($enabledInterations->$key == 1)
			{
				switch ($key)
				{
					case 'read_interaction':
					$item->read == 1 ? '' : $updateParent = false;
					break;
					case 'practice_interaction':
					$item->used == 1 ? '' : $updateParent = false;
					break;
					case 'consent_interaction':
					$item->consented == 1 ? '' : $updateParent = false;
					break;
				}
			}
		}

		if ($updateParent)
		{
			$saveData = array();
			$saveData['id'] = $todoid;
			$saveData['assigned_to'] = Factory::getUser()->id;
			$saveData['status'] = 'C';

			return $JLikeTodoModel->save($saveData);
		}
	}

	/**
	 * Function will trigger on After user has create update sla activity
	 *
	 * @param   Object  $data  Licenece Information
	 *
	 * @return  string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onLicenceFormAfterDelete($data)
	{
		$deletedLicenceId = $data['license_id'];
		$upcomingLicences = array();

		// Delete activities on after licence delete
		$slaModel = SlaFactory::model('SlaActivities', array('ignore_request' => true));
		$slaModel->deleteActivities($deletedLicenceId);

		// Delete tools on after licence delete
		$slaModel->deleteTools($deletedLicenceId);

		$slaClusterXrefTable = SlaFactory::table("slaclusterxrefs");
		$slaClusterXrefTable->load(array('license_id' => $deletedLicenceId));

		// Delete xref entries
		if (property_exists($slaClusterXrefTable, 'id'))
		{
			$slaClusterXrefTable->delete($slaClusterXrefTable->id);
		}

		/*
		JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);

		$slaClusterXrefTable = SlaFactory::table("slaclusterxrefs");
		$slaClusterXrefTable->load(array('license_id' => $data['license_id']));

		$leadConsultantUserID         = 0;
		$slaClusterXrefTableID        = 0;
		$slaClusterXrefTableClusterId = 0;

		if (property_exists($slaClusterXrefTable, 'lead_consultant_id'))
		{
			$leadConsultantUserID = $slaClusterXrefTable->lead_consultant_id;
		}

		if (property_exists($slaClusterXrefTable, 'id'))
		{
			$slaClusterXrefTableID = $slaClusterXrefTable->id;
		}

		if (property_exists($slaClusterXrefTable, 'cluster_id'))
		{
			$slaClusterXrefTableClusterId = $slaClusterXrefTable->cluster_id;
		}

		if (!$leadConsultantUserID)
		{
			return;
		}

		if ($slaClusterXrefTable->delete($slaClusterXrefTableID) !== true)
		{
			return;
		}

		if ((!$leadConsultantUserID) || !($slaClusterXrefTableClusterId) || (!$data['multiagency_id']))
		{
			return;
		}

		$subUserRoles = array('com_multiagency' => $data['multiagency_id'], 'com_cluster' => $slaClusterXrefTableClusterId);
		$leadConsultantUser      = Factory::getUser($leadConsultantUserID);
		$leadConsultantsGroups   = Factory::getUser($leadConsultantUserID)->groups;

		$params                  = Factory::getApplication()->getParams('com_multiagency');
		$helperObject            = new MultiagencyFrontendHelpers;

		$leadConsultantRoleId    = (int) $params->get('organization_lead_consultant_role_id', '0');
		$organizationAdminRoleId = (int) $params->get('school_admin_role_id', '0');
		$leadConsultantGroupId   = (int) $params->get('multiagency_leadconsultant_group', '0');

		if ((in_array($leadConsultantGroupId, $leadConsultantsGroups)))
		{
			if (! class_exists('MultiagencyFrontendHelpers'))
			{
				JLoader::register('MultiagencyFrontendHelpers', JPATH_COMPONENT_SITE . '/helpers/multiagency.php');
				JLoader::load('MultiagencyFrontendHelpers');
			}

			foreach ($subUserRoles as $content => $clientId)
			{
				$leadConsultantTjsuId = $helperObject->getIdsUserAgencyRoleMap($leadConsultantUserID, $leadConsultantRoleId, $clientId, $content);

				$tableInstance = RBACL::table('user');

				if ($leadConsultantTjsuId[0])
				{
					$tableInstance->delete($leadConsultantTjsuId[0]);
				}

				$organizationAdminTjsuId = $helperObject->getIdsUserAgencyRoleMap($leadConsultantUserID, $organizationAdminRoleId, $clientId, $content);

				if ($organizationAdminTjsuId[0])
				{
					$tableInstance->delete($organizationAdminTjsuId[0]);
				}

				if ($content == 'com_cluster')
				{
					$ClusterUserTabel = ClusterFactory::table('ClusterUsers');
					$ClusterUserTabel->load(array('user_id' => $leadConsultantUserID, 'cluster_id' => $clientId));

					if (property_exists($ClusterUserTabel, 'id'))
					{
						$ClusterUserModel = ClusterFactory::model('ClusterUser');
						$ClusterUserModel->delete($ClusterUserTabel->id);
					}
				}
			}

			$groupAlreadyAssigned = $helperObject->getIdsUserAgencyRoleMap($leadConsultantUserID, $leadConsultantRoleId);

			if (empty($groupAlreadyAssigned))
			{
				$groupOAId = (int) $params->get('multiagency_school_admin_group', '0', 'INT');

				if ($groupOAId)
				{
					UserHelper::removeUserFromGroup($leadConsultantUserID, $groupOAId);
				}
			}
		}
		*/
	}

	/**
	 * Function will trigger on After adding a external certificate
	 *
	 * @param   Boolean  $isNew  It's flag to tell us is certificate is new created or Already existed has updated
	 * @param   Object   $data   Certificate data
	 *
	 * @return  string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onTrainingRecordAfterAdded($isNew, $data)
	{
		JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
		$clusterUserModel        = ClusterFactory::model('ClusterUser', array('ignore_request' => true));

		$certificateOwner = Factory::getUser($data->getUserId());

		// If certificate owner is dpe admin then do not send email
		if ($certificateOwner->authorise('core.manageall', 'com_cluster'))
		{
			return false;
		}

		$clusters                = $clusterUserModel->getUsersClusters($data->getUserId());
		$clusters                = array_column($clusters, "cluster_id");
		$params                  = ComponentHelper::getParams('com_multiagency');
		$organizationAdminRoleId = (int) $params->get('school_admin_role_id');

		// Get users by roles
		$subusersModelUsers = RBACL::model('users', array('ignore_request' => true));
		$subusersModelUsers->setState('filter.client_id', $clusters);
		$subusersModelUsers->setState('filter.role_id', $organizationAdminRoleId);
		$subusersModelUsers->setState('filter.client', 'com_cluster');
		$subusersModelUsers->setState('group_by', 'user_id');
		$subusersModelUsers->setState('filter.state', 0);
		$clusterUsers = $subusersModelUsers->getItems();

		// Org Admin users
		foreach ($clusterUsers as $clusterUser)
		{
			if ($clusterUser->user_id)
			{
				$this->sendExternalCertificateEmails($data, (int) $clusterUser->user_id);
			}
		}
	}

	/**
	 * Method to send Add certificate Emails.
	 *
	 * @param   array  $recordDetails  Certificate data.
	 * @param   INT    $adminUserId    user id to send email.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function sendExternalCertificateEmails($recordDetails, $adminUserId)
	{
		$adminRecipients                  = array();
		$adminUser                        = Factory::getUser($adminUserId);
		$adminRecipients['email']['to'][] = $adminUser->email;
		$recordOwner                      = $recordDetails->getUserId();

		if ($recordOwner)
		{
			$user = Factory::getUser($recordOwner);
		}

		$client   = "com_dpe";
		$adminkey = "createRecordMailToOrgAdmin";

		$siteInfo           = new stdClass;
		$siteInfo->siteurl  = Uri::root();

		$replacements           = new stdClass;
		$replacements->info     = $siteInfo;
		$replacements->record   = $recordDetails;
		$replacements->user     = $user;
		$replacements->admin    = $adminUser;

		$options = new Registry;
		$options->set('record', $recordDetails);

		// Mail to site admin
		Tjnotifications::send($client, $adminkey, $adminRecipients, $replacements, $options);
	}

	/**
	 * Function will trigger on Before licece save
	 *
	 * @param   Array  $data  Licence Data
	 *
	 * @return  string|boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onBeforeLicenceSave($data)
	{
		// Server side validation to restrict the new licence if current is not archived
		if (!$data['id'] && $data['state'] != 3)
		{
			$licenceTable = Multiagency::table('licence');

			// Get active licence of agency
			$licenceTable->load(array('multiagency_id' => $data['multiagency_id'], 'state' => 1));

			if (property_exists($licenceTable, 'id'))
			{
				if ($licenceTable->id)
				{
					return Text::_('COM_MULTIAGENCY_ACTIVE_LICENCE_EXIST_MESSAGE');
				}
			}
		}
		else
		{
			// Server side validation to validate end date on edit licence
			$endDate     = Factory::getDate($data['end_date'])->toSql();
			$currentDate = Factory::getDate()->toSql();

			if ($endDate < $currentDate)
			{
				return Text::_('COM_MULTIAGENCY_LICENCES_END_CURRENT_DATE_BIGGER_WARNING');
			}
		}

		return true;
	}

	/**
	 * Method to push data in queue.
	 *
	 * @param   array  $data  record data
	 *
	 * @return  array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function addToQueue($data)
	{
		// Get users of organisation
		$subUsersModel = RBACL::model('users', array('ignore_request' => true));
		$subUsersModel->setState('filter.client_id', $data['multiagency_id']);
		$subUsersModel->setState('filter.client', 'com_multiagency');
		$subUsersModel->setState('filter.state', 0);
		$subUsersModel->setState('group_by', 'user_id');
		$orgUsers = $subUsersModel->getItems();

		if ($orgUsers)
		{
			$userObj            = new stdClass;
			$userObj->licenceId = $data['id'];
			$userObj->agencyId  = $data['multiagency_id'];

			$ClusterModel = ClusterFactory::model('Cluster',  array('ignore_request' => true));
			$addCluster   = $ClusterModel::getClusterByClient('com_multiagency', $userObj->agencyId);
			$userObj->clusterId  = $addCluster->id;

			$TJQueueProduce = new TJQueueProduce;

			foreach ($orgUsers as $orgUser)
			{
				$userObj->userId   = $orgUser->user_id;
				$userObj->roleId   = $orgUser->role_id;

				// Set message body
				$TJQueueProduce->message->setBody(json_encode($userObj));

				// @Params client, value
				$TJQueueProduce->message->setProperty('client', 'slatools.updateroles');
				$TJQueueProduce->produce();
			}
		}
	}

	/**
	 * Method to push activity data in queue.
	 *
	 * @param   array  $data  record data
	 *
	 * @return  array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function addActivityDataToQueue($data)
	{
		$return                           = array();
		$activityData                     = new stdClass;
		$activityData->id                 = $data['id'];
		$activityData->sla_id             = $data['sla_id'];
		$activityData->cluster_id         = $data['cluster_id'];
		$activityData->activity           = $data['activity'];
		$activityData->lead_consultant_id = $data['lead_consultant_id'];
		$activityData->key                = $data['key'];

		// State 3 used to create upcoming state activity
		$activityData->state              = 3;


		try
		{ 
			$TJQueueProduce = new TJQueueProduce;
			
			// Set message body
			$TJQueueProduce->message->setBody(json_encode($activityData));

			// @Params client, value
			$TJQueueProduce->message->setProperty('client', 'slaactivities.create');
			$TJQueueProduce->produce();
		}
		catch (Exception $e)
		{
			$return['success'] = 0;
			$return['message'] = $e->getMessage();

			return $return;
		}

		$return['success'] = 1;
		$return['message'] = '';

		return $return;
	}

	/**
	 * Method to update the dates of upcoming licences.
	 *
	 * @param   array   $upcomingLicences  record data
	 * @param   string  $endDate           end date
	 *
	 * @return  void
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function updateUpcomingLicenceDates($upcomingLicences, $endDate)
	{
		if (! empty($upcomingLicences))
		{
			$licenceTable = Multiagency::table('licence');

			// Udpate dates for upcoming licences
			foreach ($upcomingLicences as $upcomingLicence)
			{
				$clusterxrefs = SlaFactory::table("slaclusterxrefs");
				$clusterxrefs->load(array('license_id' => $upcomingLicence->id));

				// Get multilicence data which stored in licence xref
				$registry         = new Registry($clusterxrefs->params);
				$multiLicenceData = $registry->toArray();
				$duration         = null;

				if (! empty($multiLicenceData))
				{
					if ($multiLicenceData['time_measure'] === "week")
					{
						// Convert week into 7 days
						$duration = 'now +' . ($multiLicenceData['duration'] * 7) . ' day';
					}
					elseif($multiLicenceData['time_measure'] === "month")
					{
						// Convert into 30 days
						$duration = 'now +' . ($multiLicenceData['duration'] * 30) . ' day';
					}
					else
					{
						$duration = 'now +' . $multiLicenceData['duration'] . ' ' . $multiLicenceData['time_measure'];
					}
				}

				$updatedEndDate = HTMLHelper::date($endDate . $duration . '+1 day', Text::_('DATE_FORMAT_LC6'));
				$licenceTable->load(array('id' => $upcomingLicence->id));

				// If old date and updated date is same then break foreach loop
				if ($licenceTable->end_date == $updatedEndDate)
				{
					break;
				}

				$licenceTable->id = $upcomingLicence->id;

				if (property_exists($licenceTable, 'start_date') && property_exists($licenceTable, 'end_date'))
				{
					$licenceTable->start_date = HTMLHelper::date($endDate . '+1 day', Text::_('DATE_FORMAT_LC4'));
					$licenceTable->end_date   = $updatedEndDate;
				}

				$licenceTable->store();

				PluginHelper::importPlugin('system');
				Factory::getApplication()->triggerEvent('onUpdateUpcomingLicenceDate', array($licenceTable->getProperties()));

				// Get end date of saved licence and udpate $endDate variable
				$endDate = $licenceTable->end_date;
			}
		}
	}

	/**
	 * This trigger is used to extend ClusterUsers model getListQuery
	 * Here agency and licence table joined to user's load active licence org
	 *
	 * @param   String  $query  model query
	 *
	 * @return  String
	 *
	 * @since  1.0.0
	 */
	public function onClusterUsersModelGetListQuery($query)
	{
		$user = Factory::getUser();

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			$db = Factory::getDbo();
			$query->join('INNER', $db->quoteName('#__tjmultiagency_multiagency', 'tm') . ' ON (' . $db->qn('tm.id') . ' = ' . $db->qn('cl.client_id') . ')');
			$query->join('INNER', $db->qn('#__tjmultiagency_licences', 'ml') . ' ON (' . $db->qn('ml.multiagency_id') . ' = ' . $db->qn('tm.id') . ')');
			$query->where('ml.state = 1');
		}
	}

	/**
	 * This trigger is used to save reminder and todo relation in xref table
	 *
	 * @param   array  $data  remnder data
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function onAfterReminderSave($data)
	{
		if ($data['todo_id'])
		{
			$remindersTodoXref = DPE::table('RemindersTodoXref');

			$reminderData      = array();
			$reminderData['time_measure'] = $data['time_measure'];
			$reminderData['duration']     = $data['duration'];

			$obj             = new stdClass;
			$obj->todo_id    = $data['todo_id'];
			$obj->reminder_id = $data['id'];
			$obj->params      = json_encode($reminderData);

			$remindersTodoXref->save($obj);
		}
	}

	/**
	 * This trigger is used to get reminder data to show on edit view
	 *
	 * @param   Interger  $todoId  todo id
	 *
	 * @param   Object    $obj     current object of reminder model
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function onGetDataLoadReminderData($todoId, $obj)
	{
		if ($todoId)
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('rtxref.*');
			$query->from($db->quoteName('#__jlike_reminders_todo_xref', 'rtxref'));
			$query->join('INNER', $db->quoteName('#__jlike_reminders', 'jr')
				. ' ON ( ' . $db->quoteName('jr.id') . ' = ' . $db->quoteName('rtxref.reminder_id') . ')');
			$query->where($db->qn('todo_id') . ' = ' . (int) $todoId);
			$db->setQuery($query);
			$reminders = $db->loadAssocList();

			$allReminders = array();

			foreach ($reminders as $reminder)
			{
				$reminderData = new Registry($reminder['params']);
				$reminderFromData  = array();
				$reminderFromData['time_measure'] = $reminderData['time_measure'];
				$reminderFromData['duration'] = $reminderData['duration'];
				$reminderFromData['id'] = $reminder['reminder_id'];

				$allReminders[] = $reminderFromData;
			}

			return $obj->reminder = $allReminders;
		}
	}

	/**
	 * This trigger is used to delete data from #__jlike_reminders_todo_xref table
	 *
	 * @param   String  $context  context
	 *
	 * @param   Object  $data     reminder object
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function onAfterReminderDelete($context, $data)
	{
		$remindersTodoXref = DPE::table('RemindersTodoXref');
		$remindersTodoXref->delete($data->id);
	}

	/**
	 * Function is triggered to extend the recommendation getlistQuery
	 * This is used to get result with overdue status which is DPE specific 
	 *
	 * @param   Object  $query  Query Object
	 *
	 * @param   Object  $obj    Current object
	 *
	 * @return  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRecommendationsModelGetListQuery($query, $obj)
	{
		$input  = Factory::getApplication()->input;
		$view   = $input->get('view');

		if ($view != "recommendations" && $view != "staffdashboard")
		{
			return true;
		}

		$status = $obj->getstate('filter.status');
		$user   = Factory::getUser();
		$db     = Factory::getDbo();

		if ($status === "O")
		{
			$dateTo = new Date('now', 'UTC');
			$dateTo->setTime(0, 0, 0);

			$dueDateQuery = $db->quoteName('a.due_date') . ' < ' . $db->quote($dateTo);
			$dueDateFilter = ' AND ' . $dueDateQuery;
			$query->where($dueDateQuery);

			$overdueStatusQuery = $db->quoteName('a.status') . ' != ' . $db->quote('C');
			$overdueStatusFilter = ' AND ' . $overdueStatusQuery;
			$query->where($overdueStatusQuery);
		}

		// DPE Hack to load org users where the logged-in user is org admin

		if (ComponentHelper::isEnabled('com_multiagency') && ComponentHelper::getParams('com_jlike')->get('enable_multiagency'))
		{
			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
				$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
				$clusters = $clusterUserModel->getUsersClusters($user->id);
				$view     = $obj->getstate('view');

				foreach ($clusters as $cluster)
				{
					if (!$view)
					{
						if (RBACL::check($user->id, 'com_cluster', 'core.manageNotificationManager', 'com_jlike', $cluster->cluster_id))
						{
							$clusterIds[] = $cluster->cluster_id;
						}

						if (RBACL::check($user->id, 'com_cluster', 'core.own.manageNotifications', 'com_jlike', $cluster->cluster_id))
						{
							$staffClusterIds[] = $cluster->cluster_id;
						}
					}
					else
					{
						$clusterIds[] = $cluster->cluster_id;
					}
				}

				$agencyId  = $obj->getstate('filter.agency_id');

				// Filter by begin date.
				$dateFrom = $obj->getState('filter.date_from');

				if (!empty($dateFrom))
				{
					$dateFrom = new Date($dateFrom, 'UTC');
					$dateFrom->setTime(0, 0, 0);
					$query->where($db->quoteName('a.due_date') . ' >= ' . $db->quote($dateFrom));
				}

				// Filter by end date.
				$dateTo = $obj->getState('filter.date_to');

				if (!empty($dateTo))
				{
					$dateTo = new Date($dateTo, 'UTC');
					$dateTo->setTime(23, 59, 59);
					$query->where($db->quoteName('a.due_date') . ' <= ' . $db->quote($dateTo));
				}

				if ($status && $status != "O")
				{
					$statusfilter = ' AND a.status = ' . $db->quote($status);
					$query->where('a.status = ' . $db->quote($status));
				}

				// Filter by search
				$search = $obj->getState('filter.search');

				if (!empty($search))
				{
					if (stripos($search, 'id:') === 0)
					{
						$query->where('a.id = ' . (int) substr($search, 3));
					}
					else
					{
						$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
						$query->where('(a.title LIKE ' . $search . ' OR users.username LIKE ' . $search . ' OR users.email LIKE ' . $search . ')');
					}
				}

				$condition = '';

				// If user is staff and admin then show records from admin org and assigned record from staff org

				// Below code only for list view
				if (!$view)
				{
					if ($agencyId)
					{
						$condition = ' OR (clusters.id = ' . $agencyId . ' AND a.assigned_to = ' . $user->id . $statusfilter . $dueDateFilter
						. $overdueStatusFilter . ')';
					}
					else
					{
						if ($staffClusterIds)
						{
							$condition = ' OR (clusters.id  IN (' . implode(",", $staffClusterIds) . ') AND a.assigned_to = ' . $user->id . $statusfilter
							. $dueDateFilter . $overdueStatusFilter . ')';
						}
					}
				}

				// Get records only from admin role organisations
				if ($clusterIds)
				{
					$query->where("clusters.id  IN ('" . implode("','", $clusterIds) . "')" . $condition);
				}
			}
		}
	}

	/**
	 * Function to send notification after complete
	 *
	 * @param   string  $options  The context value
	 *
	 * @param   array   $data     The entity data
	 *
	 * @return  array
	 */
	public function getjlike_dpeTodoCompleteNotificationDetails($options, $data)
	{
		if ($data['notifyClient'] == 'com_sla')
		{
			return false;
		}

		// Get content data
		$notification        = new stdClass;
		$assignedUserName    = ucwords(Factory::getUser($data['assigned_to'])->name);
		$notification->user  = $assignedUserName;
		$notification->owner = Factory::getUser($data['assigned_by'])->name;

		$task           = new stdClass;
		$task->due_date = Factory::getDate($data['due_date'])->Format(Text::_('COM_DPE_DATE_ONLY_FORMAT'));
		$task->id       = $data['id'];
		$task->title    = ucwords($data['task_title']);
		$task->user     = $assignedUserName;

		$replacements               = new stdClass;
		$replacements->notification = $notification;
		$replacements->task         = $task;

		$optionsRegistryObj = new Registry;
		$optionsRegistryObj->set('task', $task);

		$notificationDetails                       = array();
		$notificationDetails['notifyClient']       = 'com_dpe';
		$notificationDetails['notifyKey']          = 'jlike.todo.complete.dpe';
		$notificationDetails['replacementsObj']    = $replacements;
		$notificationDetails['optionsRegistryObj'] = $optionsRegistryObj;

		return $notificationDetails;
	}

	/**
	 * Function to send notification after delete
	 *
	 * @param   array  $data  The entity data
	 *
	 * @return  array
	 */
	public function onAfterRecommendationDelete($data)
	{
		if (! empty($data))
		{
			$users = array();
			array_push($users, $data['assigned_by'], $data['assigned_to']);
			$data['title'];

			if ($data['cc_users'])
			{
				$data['cc_users'] = explode(',', $data['cc_users']);
				$users = array_merge($users, $data['cc_users']);
			}

			foreach ($users as $user)
			{
				$userdata = Factory::getUser($user);

				$recipients = array (
					// Add specific to, cc (optional), bcc (optional)
					'email' => array (
						'to' => array ($userdata->email)
					)
				);

				$todo = new stdClass;
				$todo->id = $data['id'];
				$todo->title = $data['title'];

				$replacements = new stdClass;
				$replacements->task = $todo;
				$replacements->user = $userdata;

				$options = new Registry;
				$options->set('task.id', $data['id']);

				Tjnotifications::send("com_dpe", 'jlike.todo.delete.dpe', $recipients, $replacements, $options);
			}
		}
	}

	/**
	 * Update the todo by Todo Id
	 * 
	 * 
 	 * @param   Int  $todoId  The Todo Id
	 *
	 * 
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAfterInteractionSave($data)
	{

		if (!$data) 
		{
			return false;
		}

			// if used checkbox is checked and form submitted then the todo is  updated as completed

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jlike/tables');
		$todosTable = Table::getInstance('Todos', 'JlikeTable');
		$todosTable->load(array('id' => $data['todo_id']));

		$contentId = $todosTable->content_id;
		$userId    = $todosTable->assigned_to;

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jlike/models');
		/** @var $JLikeTodoModel JlikeModelTodo */
		$JLikeTodoModel = BaseDatabaseModel::getInstance('Todo', 'JLikeModel');
		$todoContent = $JLikeTodoModel->getContent($data['todo_id']);
		$JLikeContentModel = BaseDatabaseModel::getInstance('Content', 'JLikeModel');

		$contentData = $JLikeContentModel->getData($contentId );
		$savedInteractions = json_decode($contentData->params);

		if (($savedInteractions->read_interaction && !$savedInteractions->practice_interaction) || (($savedInteractions->read_interaction && $savedInteractions->practice_interaction) && ($data['type']=='used')))
		{ 
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models');
			$tjfieldsModelFields  = BaseDatabaseModel::getInstance('Users', 'DpeModel', array('ignore_request' => true));
			$todoIds = array_column($tjfieldsModelFields->getTodoIdsBycontentId($contentId,$userId), 'id');
			$todoIds = implode(',',$todoIds);

			if ($todoIds)
			{
			   			// Update the todo with completed after interaction completed done		
				$db = Factory::getDbo();
				$query = $db->getQuery(true);
				$date = Factory::getDate();

						// Fields to update.
				$fields = array(
					$db->quoteName('status') . ' =  "C"',
					$db->quoteName('done_date') . ' = '. "'" .$date ."'" 

				);

				// Conditions for which records should be updated.
				$conditions = array(
					$db->quoteName('id') . ' IN (' . $todoIds .')'
				);

				$query->update($db->quoteName('#__jlike_todos'))->set($fields)->where($conditions);
				$db->setQuery($query);
				
				$db->execute();
			}

		}

		
	}

	/**
	 * Create course content for add todo 
	 * 
	 * @param   array  $data  Course Data
	 * 
	 * @since  __DEPLOY_VERSION__
	 */
	public function onbeforeTodoSave(&$data)
	{
		if ($data['element']   == 'com_tjlms.course')
		{		
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$table = Table::getInstance('course', 'TjlmsTable');			
			$table->load(array("id" => $data['course_id'] ));
			$courseTitle = $table->title;

				// Load contentform model to get content id
			JLoader::import('contentform', JPATH_SITE . '/components/com_jlike/models');
			$jlikeModelContentForm    = new JlikeModelContentForm;
			$contentData = array();
			$contentData['element']    = $data['element'];
			$contentData['url']        = 'index.php?option=com_tjlms&view=course&id=' . $data['course_id'];
			$contentData['element_id'] = $data['course_id'];
			$contentData['title'] 	  =  $courseTitle;        
			$contentData['params']     = (!empty($intParams)) ? json_encode($intParams) : '';
			$data['content_id'] = JlikeModelContentForm::getContentID($contentData);
		}
	}

	/**
	 * Deassign users from jlike_todos_cluster_xref 
	 * 
	 * @param   array  $userId  User Id
	 * 
	 * @param   array  $todoIds  Todo Ids
	 * 
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAfterDeassignUsers($todoIds)
	{
		
		if (!$todoIds)
		{
			return false;
		}

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jlike/tables');
		$ClusterTable = Table::getInstance('TodosClusterXref', 'JlikeTable');			
		
		foreach($todoIds as $todoId)
		{
			$ClusterTable->delete($todoId);
		}
	}

	/**
	 * Function used as a trigger after user complete a course.
	 *
	 * @param   INT  $actorId   User to completed the course
	 * @param   INT  $courseId  Course ID
	 * @param   INT  $lessonId  Lesson ID
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterCourseCompletion($actorId, $courseId, $lessonId = 0)
	{ 

		// Get the content ID by course id
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jlike/tables');
		$courseTable = Table::getInstance('Content', 'JlikeTable');			
		$courseTable->load(array("element_id" => $courseId, 'element'=>'com_tjlms.course' ));
		$contentId = $courseTable->id;


		if($contentId)
		{
			// Get  mulitple todo Ids from content id and user id.
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('id')->from('#__jlike_todos')->where($db->qn('assigned_to') . ' = '. $actorId .' AND ' . $db->qn('content_id') . ' = '. $contentId);

			$db->setQuery($query);
			$todoIds = $db->loadRowList();


		// call table object to update the todo data
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jlike/tables');	

			foreach($todoIds as $todoId)
			{   
				$todosTable = Table::getInstance('Todos', 'JlikeTable');
				$todosTable->save(array('id' => $todoId[0],'status' => 'C'));
			}
		}
	}


	/**
	 * Function used as a trigger after the ucm form save to create ticket
	 *
	 * @param   STRING $client   User to completed the course
	 * @param   ARRAY  $data  form data
	 * @param   ARRAY  $formData  modiefied formdata to save
	 *
	 */
	public function onAfterUcmLogSave($client, $data, $formData)
	{ 		

		if (!$client || empty($data) || empty($formData))
		{
			return false;
		}

		// Save ticket. in logs DPE Hack
		// Get the UCM type details

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
		$typeDetails = Table::getInstance('Type', 'TjucmTable');
		$typeDetails->load(array('unique_identifier' => $client));
		$ticketConditionData = json_decode(json_decode($typeDetails->params)->type_options);
		$ticketId ='';

		// Check Whether ticket create config is there or not.
		if ($ticketConditionData->isCreateTicket)
		{  
			$ucmFormUniqueName = str_replace('.','_',$client);
			
			$userModel = new UserModel();
			$userData= $userModel->getItem($data[$ticketConditionData->toUser]);

			$ticketData = array('cluster'=>$data[$ticketConditionData->clusterId], 'clusterusers'=> array('0' => $userData->email), 'subject'=>$data[$ticketConditionData->subject],'message'=>$data[$ticketConditionData->subject],'priority_id'=> 1,'consent'=>array('0'=>1),'department_id'=>$ticketConditionData->departmentIdOfRsticket);

			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
			$typeDetails = Table::getInstance('Type', 'TjucmTable');
			$typeDetails->load(array('unique_identifier' => $client));
			$ticketConditionData = json_decode(json_decode($typeDetails->params)->type_options);

						// Get the id of link text box check that field is present or not.
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
			$fieldTable = Table::getInstance('field', 'TjfieldsTable');
			$fieldTable->load(array('name'=>$ticketConditionData->linkField));
			
						// if link field is present then save the  link to the link field .
			if ($fieldTable->id)
			{
				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
				$fieldsValueTable = Table::getInstance('Fieldsvalue', 'TjfieldsTable');
				$fieldsValueTable->load(array('field_id' => $fieldTable->id, 'content_id'=> $formData['content_id']));

				if (!$fieldsValueTable->id)
					{ 	JLoader::import('components.com_rsticketspro.helpers.adapters.input', JPATH_ADMINISTRATOR);


				$session = Factory::getSession();
				$ticketId =  $session->get('ticketIdForUCMLog');

						 // Clear the session Data
				$session->clear('ticketIdForUCMLog','');

				if ($ticketId)
				{ 
						// remove whitespace if present and form the url.
					$ticektUrl = str_replace(' ','-',$data[$ticketConditionData->subject]); 
					$formData['fieldsvalue'][$ticketConditionData->linkField] = URI::root().'index.php?option=com_rsticketspro&view=ticket&id='.$ticketId.':'.$ticektUrl;


					// If data is valid then save the data into DB
					JLoader::import('components.com_tjucm.models.itemform', JPATH_SITE);
					$TjucmModel = BaseDatabaseModel::getInstance('ItemForm', 'TjucmModel');
					$result = $TjucmModel->saveFieldsData($formData);

					return $result = array('result'=> $result,'url' => $formData['fieldsvalue'][$ticketConditionData->linkField]);
				}

			}
		}
	}

}
	/**
	 * Function is triggered After save item to send email
	 *
	 * @param   integer  $validData  validData for email
	 *
	 * @param   string   $client    client
	 *
	 * @param   Array   $data    data
	 * 
	 * @param   Array   $formData   some part of  formData
	 *
	 * @return  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */

	public function onAfterUcmSave($validData, $client, $data, $formData)
	{


		if (!$validData || !$client || empty($data) || empty($formData) || $validData['draft'])
		{
			return false;
		}

		$ucmFieldIds = $this->getRadioAndListFieldsByClient($client);

		foreach($ucmFieldIds as $ucmFieldId)
		{

			$configDatas = $this->getConfigDataForUcmNotificationByFieldId($ucmFieldId);
			
			if(!empty($configDatas))
			{
				foreach($configDatas as $key => $configData)
				{
					Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
					$optionsTable = Table::getInstance('Option', 'TjfieldsTable');
					$optionsTable->load(array('id' => $configData->ucm_field_option_id)); 

					$formStatusValue = $formData['fieldsvalue'][$ucmFieldId->name];			

  				//Get the Old status data
					$oldStatus = array();

					foreach ($data['oldStatus'] as $innerArray) {
						$oldStatus += $innerArray;
					}


					$canSendEmail = 0; 

					if(($oldStatus[$configData->ucm_field_option_id] != $formStatusValue) || empty($oldStatus))
					{
						$canSendEmail++;
					}


					if (($optionsTable->value == $formStatusValue || (in_array($optionsTable->value,(array) $formStatusValue))) && ($canSendEmail))
					{
						$canSendEmail = 0;
						$configFieldValues = json_decode($configData->ucm_field_config);

						foreach($configFieldValues as $configValue)
						{
							switch ($configValue->sendNotificationto) {
								case 'userdefinedemail':
								$fieldIdOfUserDetails = $configValue->sendnotificationfield;

								JLoader::import('components.com_dpe.models.users', JPATH_SITE);
								$dpeUserModels = BaseDatabaseModel::getInstance('Users', 'DpeModel');
								$assignedUsersData = $dpeUserModels->getFieldValues($fieldIdOfUserDetails, $formData['content_id']);

								foreach ($assignedUsersData as $userId) {
									$validData['userIdToEmail'] = $userId['value'];
									$validData['notificationKey'] = $configValue->uniquekeytjnotification;
									$validData['status'] = $formStatusValue;
									$validData['url']  = Uri::root() . substr(
										Route::_(
											'index.php?option=com_tjucm&view=itemform&client=' . $validData['client'] . '&id=' . $validData['id'] . '&Itemid='
										),
										strlen(Uri::base(true)) + 1
									);

									unset(
										$validData['checked_out_time'],
										$validData['checked_out_time'],
										$validData['created_by'],
										$validData['created_date'],
										$validData['modified_by'],
										$validData['modified_date'],
										$validData['checked_out']
									);
									$this->addUcmNotificationToQueue($validData, 'ucmnotification.sendemail');
								}
								break;

								case 'specificemail':
								$validData['userIdToEmail'] = $configValue->sendnotificationemail;
								$validData['notificationKey'] = $configValue->uniquekeytjnotification;
								$validData['status'] = $formStatusValue;
								$validData['url']  = Uri::root() . substr(
									Route::_(
										'index.php?option=com_tjucm&view=itemform&client=' . $validData['client'] . '&id=' . $validData['id'] . '&Itemid='
									),
									strlen(Uri::base(true)) + 1
								);
								$this->addUcmNotificationToQueue($validData, 'ucmnotification.sendemail');
								break;

								default:
								break;
							}

						}

					}
				}
			}
		}				
	}

	/**
	 * Function is triggered before save item
	 *
	 * @param   integer  $recordId  item id
	 *
	 * @param   string   $client    client
	 *
	 *
	 * @return  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onTjUcmGetOldStatusData($recordId, $client, $draft)
	{
		
		if (($recordId && $client) && ($draft == 0))
		{ 
			$statusUpdatedFieldId = array();

			$ucmFieldIds = $this->getRadioAndListFieldsByClient($client);

			foreach($ucmFieldIds as $ucmFieldId)
			{
				$configDatas = $this->getConfigDataForUcmNotificationByFieldId($ucmFieldId);


				if(!empty($configDatas))
				{
					foreach($configDatas as $configData)
					{
						Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
						$optionsTable = Table::getInstance('Option', 'TjfieldsTable');
						$optionsTable->load(array('id' => $configData->ucm_field_option_id));  

						$fieldStatusTableValue = Table::getInstance('Fieldsvalue', 'TjfieldsTable');
						$fieldStatusTableValue->load(array('option_id' => $configData->ucm_field_option_id, 'content_id'=> $recordId));					

						$formStatusValue = $fieldStatusTableValue->value;

						if ($optionsTable->value == $formStatusValue )
						{
							$statusUpdatedFieldId[] = array($configData->ucm_field_option_id=>$formStatusValue);

						}

					}
				}

			}
			return $statusUpdatedFieldId;
		}
	}

	/**
	 * Function is triggered get only radio and list type of field data
	 *
	 * @param   string   $client    client
	 *
	 *
	 * @return  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getRadioAndListFieldsByClient($client)
	{
		if(!$client)
		{
			return false;
		}

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);

		$query->select('id,name');
		$query->from($db->quoteName('#__tjfields_fields'));
		$query->where($db->quoteName('client') . ' = "' . $client .'"');
		$query->where($db->quoteName('type') . ' IN ("radio","tjlist")');
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Function is triggered to get the configuration data for notification 
	 *
	 * @param   int   $ucmFieldId    client
	 *
	 *
	 * @return  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */

	public function getConfigDataForUcmNotificationByFieldId($ucmFieldId)
	{
		if(!$ucmFieldId)
		{
			return false;
		}
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('cb.*');
		$query->from($db->quoteName('#__dpe_ucm_field_notification_configurations_xref','cb'));
		$query->leftJoin($db->quoteName('#__dpe_ucmfieldsnotifiaction_config_xref', 'nb') . ' ON (' . $db->quoteName('nb.ucm_field_id') . ' = ' . $db->quoteName('cb.ucm_field_id') . ')');
		$query->where($db->qn('cb.ucm_field_id') . ' = "' . (int) $ucmFieldId->id .'"');
		$query->where($db->qn('nb.show_notification') . ' = 1');
		$db->setQuery($query);

		return $db->loadObjectList();

	}
	
	/**
	 * Method to push data in queue.
	 *
	 * @param   array  $data  record data
	 * 
	 * @param   string $tjQueueClient client for tjqueue to call the plugin
	 *
	 * @return  array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function addUcmNotificationToQueue($data,$tjQueueClient)
	{
		$return      = array();
		$messageBody = (object) $data;

		try
		{
			$TJQueueProduce = new TJQueueProduce;

			// Set message body
			$TJQueueProduce->message->setBody(json_encode($messageBody));

			// @Params client, value
			$TJQueueProduce->message->setProperty('client', $tjQueueClient);
			$TJQueueProduce->produce();
		}
		catch (Exception $e)
		{
			$return['success'] = 0;
			$return['message'] = $e->getMessage();

			return $return;
		}

		$return['success'] = 1;
		$return['message'] = '';

		return $return;
	}


	/**
	 * Method to update the leadstaff member in sla.
	 *
	 * @param   Int  $multiagencyId  mulriagency Id of Organisation
	 *  
	 * @param   Int $leadStaffMemberId  lead Staff Member Id
	 *
	 * @return  array
	 *
	 * @since __DEPLOY_VERSION__
	 * */
	public function onAfterOrgSlaUpdateLeadstaffMember($multiagencyId, $leadConsultantId = null)
	{
		if ($multiagencyId == '' || $multiagencyId == null)
		{
			return false;
		}

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
		$clusterTable = Table::getInstance('Clusters', 'ClusterTable');
		$clusterTable->load(array('client_id' => $multiagencyId));
		$clusterId = $clusterTable->id;


		if( $leadConsultantId == '')
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
			$multiagencyTable = Table::getInstance('multiagency', 'MultiagencyTable');
			$multiagencyTable->load(array('id' => $multiagencyId));
			$leadConsultantId = $multiagencyTable->lead_consultant_id;
		}

		if( $clusterId && $leadConsultantId)
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

		// Fields to update.
			$fields = array( $db->quoteName('lead_consultant_id') . ' = ' . $leadConsultantId );

		// Conditions for which records should be updated.
			$conditions = array( $db->quoteName('cluster_id') . ' = '.$clusterId);

			$query->update($db->quoteName('#__tj_sla_cluster_xref'))->set($fields)->where($conditions);

			$db->setQuery($query);
			$db->execute();
		}
	}

/**
	 * Method to push data in queue.
	 *
	 * @param   array  $data  record data
	 * 
	 * @param   array $onboarddata client for tjqueue to call the plugin

	 */
public function onUserOnboardNotificationToUsers($onboarddata)
{
	$return      = array();
	$messageBody = $onboarddata;
	$userAssigenment = json_decode($onboarddata)->specificuserdata;

	if($userAssigenment == 'nousers')
	{
		return false;
	}

	try
	{
		$TJQueueProduce = new TJQueueProduce;

	    // Set message body
		$TJQueueProduce->message->setBody(json_encode($messageBody));

	    // @Params client, value
		$TJQueueProduce->message->setProperty('client', 'dpe.onboard');
		$TJQueueProduce->produce();
	}
	catch (Exception $e)
	{
		$return['success'] = 0;
		$return['message'] = $e->getMessage();

		return $return;
	}

	$return['success'] = 1;
	$return['message'] = '';

	return $return;
}

	/**
	 * Method to push data in queue to send notifications and assign tasks to the new users.
	 *
	 * @param   array  $data  record data
	 * 
	 * @param   array $onboarddata client for tjqueue to call the plugin

	 */
	public function onUserOnboardAssignSet($usersJobtitleData, $userDetails)
	{

		if (!$usersJobtitleData || empty($userDetails))
		{
			return false;
		}

		$user = Factory::getUser();

		$client = 'dpe.onboardwithjobtitle';
		Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
		$onboarduserdata = Table::getInstance('OnboardXref', 'DpeTable');

		$ucmId = $usersJobtitleData['ucm_id'];
		$multiagencyId = $usersJobtitleData['clusterId'];

		if($usersJobtitleData['isNew'])
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
			$clusterInstance = Table::getInstance('Clusters', 'ClusterTable');
			$clusterInstance->load(array('client_id'=> $multiagencyId));
			$onboarduserdata->load(array('cluster_id' => $clusterInstance->id, 'set_as_main_default_set'=>'1'));
			$onbaordUsersSetData = json_decode($onboarduserdata->formdata);

		}else
		{
			$onboarduserdata->load(array('ucmid' => $ucmId, 'type_of_set'=>'jobtitleset'));
			$onbaordUsersSetData = json_decode($onboarduserdata->formdata);
			
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
			$clusterInstance = Table::getInstance('Clusters', 'ClusterTable');
			$clusterInstance->load(array('client_id'=> $multiagencyId));
		}

		if($onbaordUsersSetData->elearning_subform != NULL)
		{
			$courseAssignDetail['elearning_subform'] = $onbaordUsersSetData->elearning_subform;
			$courseAssignDetail['userid']     = $userDetails['id'];
			$courseAssignDetail['created_by']     = $user->id;
			$courseAssignDetail['clusterId']     = $clusterInstance->id;

			$this->insertOnbaordData($courseAssignDetail, $client);
		}
		if($onbaordUsersSetData->document_subform != NULL)
		{
			$documentAssignDetail['document_subform'] = $onbaordUsersSetData->document_subform;
			$documentAssignDetail['userid']           = $userDetails['id'];
			$documentAssignDetail['clusterId']        = $clusterInstance->id;
			$documentAssignDetail['created_by']       = $user->id;
			$this->insertOnbaordData($documentAssignDetail, $client);
		}
		if($onbaordUsersSetData->todo_subform != NULL)
		{
			$todoAssignData['todo_subform']	= $onbaordUsersSetData->todo_subform;
			$todoAssignData['userid']	    = $userDetails['id'];
			$todoAssignData['created_by']   = $user->id;
			$todoAssignData['clusterId']    = $clusterInstance->id;

			$this->insertOnbaordData($todoAssignData, $client);

		}
		
		return true;
	}

	protected function insertOnbaordData($data, $client)
	{
		$return      = array();
		$messageBody = $data;
		

		if(empty($messageBody))
		{
			return false;
		}
		
		try
		{
			$TJQueueProduce = new TJQueueProduce;

			// Set message body
			$TJQueueProduce->message->setBody(json_encode($messageBody));

			// @Params client, value
			$TJQueueProduce->message->setProperty('client', $client);
			$TJQueueProduce->produce();
		}
		catch (Exception $e)
		{
			$return['success'] = 0;
			$return['message'] = $e->getMessage();

			return $return;
		}

		$return['success'] = 1;
		$return['message'] = '';

		return $return;
	}

	/**
	 * Function is triggered After save item to Store cheklist note data
	 *
	 * @param   Array   $formData   some part of  formData
	 *
	 * @return  Boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */

	public function onAfterUcmChecklistSave($formData)
	{
		if (empty($formData))
		{
			return false;
		}
		JLoader::import('components.com_tjfields.helpers.tjfields', JPATH_SITE);

		$keysWithChecklistNote = [];
		$modifiedKeys = [];

		foreach ($formData as $key => $value) {
			if (strpos($key, '_checklistNote') !== false) {

				$modifiedKey = str_replace('_checklistNote', '', $key);
				// $modifiedKeys[$modifiedKey] = $value;
				$tjFieldHelper = new TjfieldsHelper;
				$fieldId = $tjFieldHelper->getFieldIdFromName($modifiedKey);
				
				// To get the checklist note id
				Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');

				$checklistNote = Table::getInstance('CheckListNoteExtend', 'DpeTable');
				$checklistNote->load(array('fieldId' => $fieldId, 'content_id' => $formData['id']));

				if (!$checklistNote->id)
				{
					if ($value)
					{
						$result = $checklistNote->save(array('content_id'=> (int)$formData['id'],'fieldId' => (int)$fieldId, 'fieldValue'=>$value));
					}
					

				}else
				{
					$result = $checklistNote->save(array('id'=>$checklistNote->id,'content_id'=> (int)$formData['id'],'fieldId' => (int)$fieldId, 'fieldValue'=>$value));
				}
			}
		}
		return true;
	}

	/**
	 * Function is triggered After save item to Store cheklist review date data
	 *
	 * @param   Array   $formData   some part of  formData
	 *
	 * @return  Boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAfterTodoStoreCheklistReviewDate($todoId, $checklistId)
	{
		
		if(!$checklistId || !$todoId)
		{
			return false;
		}

		Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
		$checklistReview = Table::getInstance('ChecklistNextReviewDate', 'DpeTable');
		$checklistReview->load(array('content_id' => $checklistId));

		if (!$checklistReview->id)
		{

			$result = $checklistReview->save(array('content_id'=> (int)$checklistId,'todo_id' => (int)$todoId)) ;
		}else
		{
			$result = $checklistReview->save(array('id'=>$checklistReview->id,'content_id'=> (int) $checklistId,'todo_id' => (int) $todoId ));
		}
	}
	/**
	 * Function is triggered After guest user is subscribed to jmail alert
	 *
	 * @param   Array   $data  is part of  users
	 * 
	 * @param   Boolean  $isNew   the user is new or not
	 *
	 * @return  Boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAfterJmaAlertSubscriptionSaveNotifyGuestUser($data, $isNew)
	{
		
		if( $data['user_id'] == 0)
		{
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jmailalerts/tables');
			$jmailalertsTablealert = Table::getInstance('alert', 'JmailalertsTable', array());
			$jmailalertsTablealert->load(array('id' => $data['alert_id']));

			$recipients = array (
							// Add specific to, cc (optional), bcc (optional)
				'email' => array (
					'to' => array ($data['email_id'])
				)
			);

			$replacements = new stdClass;
			$replacements->jmailalert = new stdClass;
			$replacements->jmailalert->username = $data['name'];
			$replacements->jmailalert->email = $data['email_id'];
			$replacements->jmailalert->alert_title = $jmailalertsTablealert->title;

			$options = new Registry;
			$options->set('jmailalert.guestEmails', $data['email_id']);
			$options->set('jmailalert.alert_title', $jmailalertsTablealert->title);
			$options->set('jmailalert.username', $data['name']);

			$notifi = Tjnotifications::send("com_dpe", 'jlike.jmailalert.guestusersubscribe.dpe', $recipients, $replacements, $options);

			$dpeParams = ComponentHelper::getParams('com_dpe');
			$crmEmail  = $dpeParams->get('crmemail');
			$crmEmail = array (
							// Add specific to, cc (optional), bcc (optional)
				'email' => array (
					'to' => array ($crmEmail),
					'bcc' => array($data['email_id'])
				)
			);

			if($notifi['success'])
			{
				$notifi = Tjnotifications::send("com_dpe", 'jmailalert.sendtocrm.dpe', $recipients, $replacements, $options);
				$crmData = ($data['fromevent'])?: Tjnotifications::send("com_dpe", 'com_dpe.crm.jmailalert', $crmEmail, $replacements, $options);
			}
		}

		if ($data['userOrganisation'])
		{
			
			// Get the Joomla database object
			$db = Factory::getDbo();

			// Prepare the data to insert
			$columns = array('jma_sub_id', 'org_name', 'sub_email');
			$values = array(
				$db->quote($data['subscriptionId']), 
				$db->quote($data['userOrganisation']), 
				$db->quote($data['email_id']) 
			);

			// Create the query
			$query = $db->getQuery(true)
			->insert($db->quoteName('#__jma_subscriber_org_xref'))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));

			// Execute the query
			$db->setQuery($query);

			try {
				$db->execute();
				echo 'Data inserted successfully.';
			} catch (RuntimeException $e) {
				echo 'Error inserting data: ' . $e->getMessage();
			}

		}
		
	}
	/**
	 * Function is triggered before show the fields check the conditions
	 *
	 * @param   Int   $fieldId  is part of  field Id
	 *
	 * @param   Int  $contentId   the user is new or not
	 *
	 * @return  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onBeforeViewLoadGetConditionData($fieldId, $contentId, $showField)
	{
		if (!$fieldId || !$contentId) {
			return false;
		}
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');

		$conditionSatisfied = 0;
		$getFieldConditionId = Table::getInstance('condition', 'TjfieldsTable');
		$getFieldConditionId->load(array('field_to_show' => $fieldId));
		$fieldConditonValues = json_decode($getFieldConditionId->condition);
		$fieldConditionMatch = ($getFieldConditionId->condition_match == 1) ? 'all' : 'any';

				// Flag to track unmatched condition (useful for 'all' match)
		$allConditionsSatisfied = true;
				// Flag for 'any' condition match
		$anyConditionSatisfied = false;
		foreach ($fieldConditonValues as $key => $fieldConditonValue) {
			$fieldOptions = json_decode($fieldConditonValue);
					// Load field value
			$fieldValueTables = Table::getInstance('Fieldsvalue', 'TjfieldsTable');
			$fieldValueTables->load(array('field_id' => $fieldOptions->field_on_show, 'content_id' => $contentId));
					// Check operator and condition satisfaction
			$conditionMet = false;

					if ($fieldOptions->operator == 1) { // Operator = 1 means condition must be satisfied
						$conditionMet = ($fieldValueTables->option_id == $fieldOptions->option);
					} else { // Operator = 0 means condition must NOT be satisfied
						$conditionMet = ($fieldValueTables->option_id != $fieldOptions->option);
					}
					if ($getFieldConditionId->condition_match == 1) { // Match 'all' conditions
					if (!$conditionMet) {
						$allConditionsSatisfied = false;
							break; // If any condition fails, no need to check further
						}
					} else { // Match 'any' condition
					if ($conditionMet) {
						$anyConditionSatisfied = true;
							break; // If any condition passes, no need to check further
						}
					}
				}
				// Final decision based on condition_match value
				if ($getFieldConditionId->condition_match == 1) { // 'all' conditions must be satisfied
				return $showField == 1 ? $allConditionsSatisfied : !$allConditionsSatisfied;
				} else{ // 'any' condition can be satisfied
				return $anyConditionSatisfied ? true : false;
			}
		}
	/**
	 * Function is triggered After save item to send email
	 *
	 * @param   integer  $contentId  contentId for form id
	 *
	 * @param   string   $client    client
	 *
	 * @return  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */

	public function onAfterUcmSaveRopToFlatTable($contentId, $client)
	{

// Get DB instance
		$db = Factory::getDbo();
		$contentId = (int) $contentId;

// Step 1: Fetch all original values
		$query = $db->getQuery(true)
		->select('*')
		->from($db->quoteName('#__tjfields_fields_value'))
		->where($db->quoteName('content_id') . ' = ' . $contentId);
		$db->setQuery($query);
		$originalData = $db->loadAssocList();

// Clients to skip
		$skipClients = ['com_tjucm.ropfiles', 'com_tjucm.roprisk', 'com_tjucm.roppeople', 'com_tjucm.ropdataflow'];

		foreach ($originalData as $row) {
    // Skip client check
			if (in_array($row['value'], $skipClients)) {
				continue;
			}

    // Check if ID exists in flat table
			$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__tjfields_fields_value_flat'))
			->where($db->quoteName('id') . ' = ' . (int) $row['id']);
			$db->setQuery($query);
			$existingRow = $db->loadAssoc();

			if ($existingRow) {
        // Compare value
				if ($existingRow['value'] !== $row['value']) {
            // Value changed => update it
					$update = $db->getQuery(true)
					->update($db->quoteName('#__tjfields_fields_value_flat'))
					->set($db->quoteName('value') . ' = ' . $db->quote($row['value']))
					->set($db->quoteName('option_id') . ' = ' . (isset($row['option_id']) ? (int) $row['option_id'] : 'NULL'))
					->set($db->quoteName('user_id') . ' = ' . (isset($row['user_id']) ? (int) $row['user_id'] : 'NULL'))
					->set($db->quoteName('email_id') . ' = ' . (isset($row['email_id']) ? $db->quote($row['email_id']) : 'NULL'))
					->set($db->quoteName('client') . ' = ' . $db->quote($row['client']))
					->where($db->quoteName('id') . ' = ' . (int) $row['id']);
					$db->setQuery($update);
					$db->execute();
				}
        // else: Do nothing if value is same
			} else {
        // Insert if not exists
				$insert = $db->getQuery(true);
				$columns = ['id', 'field_id', 'content_id', 'value', 'option_id', 'user_id', 'email_id', 'client'];
				$values = [
					(int) $row['id'],
					(int) $row['field_id'],
					(int) $row['content_id'],
					$db->quote($row['value']),
					isset($row['option_id']) ? (int) $row['option_id'] : 'NULL',
					isset($row['user_id']) ? (int) $row['user_id'] : 'NULL',
					isset($row['email_id']) ? $db->quote($row['email_id']) : 'NULL',
					$db->quote($row['client'])
				];

				$insert->insert($db->quoteName('#__tjfields_fields_value_flat'))
				->columns($columns)
				->values(implode(',', $values));
				$db->setQuery($insert);
				$db->execute();
			}
		}

	}

	/**
 * Event method to delete records from the flat table when a ROP record is removed.
 *
 * This method is triggered after a ROP content record is deleted.
 * It ensures data consistency by removing any associated flat values
 * from the `#__tjfields_fields_value_flat` table based on the `content_id`.
 *
 * @param   int  $recordId  The ID of the deleted ROP record (used as content_id).
 *
 * @return  void
 */
public function onAfterRopDeleteRemoveRecordFromFlatTable($recordId)
{
		    if (!$recordId) {
		        return;
		    }

		    try {
		        // Get the database object
		        $db = Factory::getDbo();

		        // Build the delete query
		        $query = $db->getQuery(true)
		            ->delete($db->quoteName('#__tjfields_fields_value_flat'))
		            ->where($db->quoteName('content_id') . ' = ' . (int) $recordId);

		        // Set and execute the query
		        $db->setQuery($query);
		        $db->execute();
		    } catch (\Exception $e) {
		        // Optional: log error or handle exception
		        //Factory::getApplication()->enqueueMessage('Failed to remove flat data: ' . $e->getMessage(), 'error');
		    }
		}

	/**
	 * Intercepts DOCman document requests to apply a Marketing Tag based access override.
	 *
	 * This method checks whether the requested document contains a specific tag
	 * configured in the plugin parameters. If the tag matches, the method removes
	 * access and category access restrictions from the query, allowing the document
	 * to bypass standard ACL checks.
	 *
	 * @param   object  $query  The DOCman request query object containing view, slug,
	 *                          and access related properties.
	 *
	 * @return  void
	 *
	 * @since   9.0.6
	 */
    public function onDocmanBeforeRequest($query)
    {
        // Check for Marketing tag override
        if (isset($query->view) && in_array($query->view, ['document', 'download', 'preview']) && isset($query->slug))
        {
            $manager = \KObjectManager::getInstance();

			$model = $manager->getObject(
				'com://site/docman.model.documents'
			);

			// Disable all filters for lookup
			$model->setState([
				'access' => null,
                'status' => null,
                'enabled' => null,
                'category' => null,
                'category_children' => null
			]);

			$overrideTagTitle = strtolower(trim($this->params->get('marketing_tags', '')));
			$document = $model
				->slug($query->slug)
   				->fetch();

            if (!$document->isNew()) {
                 // Check tag_list string - handle potential formatting differences
                 $tagList = array_map('trim', explode(',', $document->tag_list));

                 foreach($tagList as $tagTitle) {
                     if (strtolower($tagTitle) === $overrideTagTitle) {
                         $query->access = null;
                         $query->category_access = null;
                         $query->status = 'published';
                         $query->enabled = 1;
                         break;
                     }
                 }
            }
        }
    }

    /**
     * Intercept doclink permalinks before Permalink.php processes them.
     */
    public function onAfterInitialise()
    {
        $app = Factory::getApplication();
        if ($app->isClient('administrator')) {
            return;
        }

        $uri    = Uri::getInstance();
        $path   = $uri->getPath();

        // Check if this is a doclink URL
        if (strpos($path, '/doclink/') !== false)
        {
            $parts = explode('/', trim($path, '/'));
            $doclinkIndex = array_search('doclink', $parts);

            if ($doclinkIndex !== false && isset($parts[$doclinkIndex + 1]))
            {
                $slug = $parts[$doclinkIndex + 1];

                $user = Factory::getUser();
                if ($user->guest)
                {
                    $manager = \KObjectManager::getInstance();
                    $model = $manager->getObject('com://site/docman.model.documents');
                    
                    $model->setState([
                        'access' => null,
                        'status' => null,
                        'enabled' => null,
                        'category' => null
                    ]);

                    $document = $model->slug($slug)->fetch();
                    $overrideTagTitle = strtolower(trim($this->params->get('marketing_tags', '')));
                    $isMarketing = false;

                    if (!$document->isNew()) {
                        $tagList = array_map('trim', explode(',', $document->tag_list));
                        foreach($tagList as $tagTitle) {
                            if (strtolower($tagTitle) === $overrideTagTitle) {
                                $isMarketing = true;
                                break;
                            }
                        }
                    }

                    if (!$isMarketing) {
                        $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
                        $app->redirect(Route::_('index.php?option=com_users&view=login', false));
                        $app->close(); // stop further processing
                    }
                }
            }
        }
    }

}
