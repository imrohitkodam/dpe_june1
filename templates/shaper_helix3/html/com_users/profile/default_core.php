<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Table\Table;

JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
JLoader::import('components.com_dpe.includes.dpe', JPATH_SITE);
$dpeUsers = DPE::model('Users');
$schoolInfo = $dpeUsers->getUserSchoolRole('com_multiagency', $this->data->id);

$subusersModelRole = RBACL::model('roles', array('ignore_request' => true));
$subusersModelRole->setState('filter.client', 'com_multiagency');
$subusersModelRole->setState('filter.state', 1);
$coreRoles = $subusersModelRole->getItems();

$params           = ComponentHelper::getParams('com_multiagency');
$orgAdminRoleId   = (int) $params->get('school_admin_role_id');
// $orgmanagerRoleId = (int) $params->get('manager_role_id');
$coreRoles        = array_column($coreRoles, 'id');
$dpeParams        = ComponentHelper::getParams('com_dpe');
$relatedRoles     = new Registry($dpeParams->get('related_roles'));
$relatedRoles     = $relatedRoles->get('related_roles');

JModelLegacy::addIncludePath(JPATH_ROOT . '/components/com_dpe/models');
$schoolModel = BaseDatabaseModel::getInstance('School', 'DpeModel');

JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
$clusterModel = ClusterFactory::model('Cluster');

$userJobtitleData = isset($this->data->id)?$schoolModel->getJobTitlesByUserId($this->data->id):null;
$organisationName = array_values(array_unique(array_column($schoolInfo, 'title')));

Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
$clusterInstance = Table::getInstance('Clusters', 'ClusterTable');

foreach ($schoolInfo as $key => $info)
{
	if (!in_array($info->role_id, $coreRoles))
	{
		// Get subusers actions mapp
		$coreRoleId = RBACL::getCoreRoleByUser($this->data->id, 'com_multiagency', $info->client_id);

		// if (!empty(array_intersect($coreRoleId, array($orgAdminRoleId, $orgmanagerRoleId))))
		if (!empty(array_intersect($coreRoleId, array($orgAdminRoleId))))
		{
			unset($schoolInfo[$key]);
		}
	}
}

$schoolInfo = array_values($schoolInfo);
$schoolUniqueData = [];

foreach ($schoolInfo as $schoolInfoData) {
    $client_id = $schoolInfoData->client_id;
    
    // Check if the client_id is not already in $uniqueData
    if (!isset($schoolUniqueData[$client_id])) {
        $schoolUniqueData[$client_id] = $schoolInfoData;
    }
}

$schoolUniqueData = array_values($schoolUniqueData);

foreach($userJobtitleData as $key => $jobTitleData)
{
	// Get cluster Id
	$clusterInstance->load(array('id' => $jobTitleData->cluster_id));
	$userJobtitleData[$key]->client_id = $clusterInstance->client_id;
}

for ($index=0; $index < sizeof($schoolUniqueData); $index++)
{   
	foreach($userJobtitleData as $key => $userJobData)
	{ 
		if ($userJobData->client_id == $schoolUniqueData[$index]->client_id)
		{	
			$jobTitle[$index]  = array('title'=>isset($this->data->id)?$schoolModel->getJobTitleValueById($userJobtitleData[$key]->ucm_id):null,'client_id'=>$userJobtitleData->cluster_id);
	
			$dpeLead[$index] = $userJobtitleData[$key]->dpelead;	
		}	
	}	
}
?>
<fieldset id="users-profile-core">
	<legend>
		<?php echo Text::_('COM_USERS_PROFILE_CORE_LEGEND'); ?>
	</legend>
	<dl class="dl-horizontal custom-profile">
		<dt>
			<?php echo Text::_('COM_USERS_PROFILE_NAME_LABEL'); ?>
		</dt>
		<dd>
			<?php echo $this->escape($this->data->name); ?>
		</dd>
		<dt>
			<?php echo Text::_('COM_USERS_PROFILE_USERNAME_LABEL'); ?>
		</dt>
		<dd>
			<?php echo $this->escape($this->data->username); ?>
		</dd>
		<dt>
			<?php echo Text::_('COM_USERS_PROFILE_REGISTERED_DATE_LABEL'); ?>
		</dt>
		<dd>
			<?php echo HTMLHelper::_('date', $this->data->registerDate, Text::_('DATE_FORMAT_LC1')); ?>
		</dd>

		<?php
		if (!empty($schoolInfo))
		{
		?>
		<dt>
			<?php echo Text::sprintf('COM_USERS_SCHOOL_ROLE_LABEL', Text::_('COM_MULTIAGENCY_ORGANISATION')); ?>
		</dt>

		<dd>
			<?php
				
				foreach ($schoolInfo as $info)
				{ 	
					
					echo '<div class="mb-10 roleclass">' . $this->escape(ucfirst($info->title)) .
					Text::_('COM_USERS_SCHOOL_ROLE_SEPERATOR') . $this->escape(ucfirst($info->rolename)) .' </div>';
				}
			?>
		</dd>
		<dt>
			<?php if ($jobTitle)
				  {
				   echo  Text::_('COM_USERS_SCHOOL_JOBTITLE_LABEL');
				  } ?>
		</dt>

		<dd>
			<?php 
					foreach ($organisationName as $key => $title)
					{ 
						
							$jobTitlevalue = $jobTitle[$key]['title'];
							echo $title.' - <b>'.$jobTitlevalue.'</b><br>';
					}
			
			?>
		</dd>
		<dt>
			<?php if ($jobTitle)
				  {
				   echo  Text::_('COM_MULTIAGENCY_FORM_DPELEAD_LIST');
				  } 
				  ?>
		</dt>
		<dd>
			<?php 
					foreach ($organisationName as $key => $title)
					{ 	

						if ($dpeLead[$key] == 1 ){ 

							$dpeLead[$key] = '<i class="fa fa-check fa-lg dpelead"></i>';
							echo $title.' - <b>'.$dpeLead[$key].'</b> <br>';
						}
						else
						{
							echo $title.' - <b></b> <br>';
						}
						
					}
			
			?>
		</dd>
		<?php
		}
		?>
	</dl>
</fieldset>
