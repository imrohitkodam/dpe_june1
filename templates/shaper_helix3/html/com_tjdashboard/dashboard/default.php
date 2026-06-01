<?php
/**
 * @package     TJDashboard
 * @subpackage  com_tjdashboard
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('bootstrap.renderModal');
HTMLHelper::_('formbehavior.chosen', 'select');

JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);
JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);

$document = Factory::getDocument();
$user     = Factory::getUser();

$document->addStylesheet('components/com_tjdashboard/assets/css/dashboard.css');
$document->addStylesheet('media/techjoomla_strapper/css/bootstrap.j3.min.css');
$document->addScript('components/com_tjdashboard/assets/js/tjDashboardService.min.js');
$document->addScript('components/com_tjdashboard/assets/js/tjDashboardUI.min.js');
$document->addStylesheet('media/com_tjdashboard/css/tjdashboard-sb-admin.css');
HTMLHelper::script('plugins/tjdashboardrenderer/piechart/assets/js/chartjs.js');
HTMLHelper::script('plugins/tjdashboardrenderer/piechart/assets/js/chartjs-plugin-datalabels.js');
$document->addScript('media/com_dpe/js/dpepreloader.js');

$staffRole  = ComponentHelper::getParams('com_multiagency')->get('member_role_id');
$coreRoleId = RBACL::getCoreRoleByUser($user->id, 'com_multiagency');

$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
$clusters         = $clusterUserModel->getUsersClusters($user->id);
$app              = Factory::getApplication();
$menu             = $app->getMenu();

// Check user having active staff org to show the staff link
if (!$user->authorise('core.manageall', 'com_cluster'))
{
	$staffClusters = array();

	// Get clusters where is user staff by checking a core role
	foreach ($clusters as $cluster)
	{
		$coreRoleIds = RBACL::getCoreRoleByUser($user->id, 'com_cluster', $cluster->cluster_id);

		if (in_array($staffRole, $coreRoleIds))
		{
			$staffClusters[] = $cluster->cluster_id;
		}
		else
		{
			$adminClusters[] = $cluster->cluster_id;
		}
	}
}
?>

<script>
jQuery(document).ready(function() {
		TJDashboardUI.initDashboard(<?php echo ($this->item->state == 1? $this->item->dashboard_id : 0); ?>);
	});
</script>


<div class="preloader-wrap">
  <div class="percentage" id="precent"></div>
  <div class="newloader">
    <div class="trackbar">
      <div class="loadbar"></div>
    </div>
    <div class="glow"></div>
  </div><br><br>
  <div class="getdata" id="getdata"> <?php echo ' ' . TEXT::_('COM_DPE_LOADER_GETTING_DATA');?> </div>
</div>


<div class="<?php echo COM_TJDASHBOARD_WRAPPER_DIV;?> dashboard-view">
	<div>
		<div class="row">
			<!-- Hack added to add staff dashboard link for admin and staff users -->
			<?php
			if (! empty($adminClusters) || ! empty($staffClusters))
			{
				$staffMenu          = $menu->getItems('link', 'index.php?option=com_dpe&view=staffdashboard', true);
				$staffDashboardLink = Route::_($staffMenu->link . '&Itemid=' . $staffMenu->id);
			?>
				<div class="pr-20 pull-right">
					<div><strong><?php echo Text::sprintf('COM_DPE_STAFF_DASHBOARD_LINK', $staffDashboardLink); ?></strong></div>
				</div>
			<?php
			}
			?>
			<!-- Hack end -->
			<div class="col-12 tjdashboard">
					<h3 class="d-none" data-dashboard-id="<?php echo $this->item->dashboard_id;?>"><?php echo htmlspecialchars($this->item->title);?></h3>
			</div>
		</div>
	</div>
</div>
<input id="datadashboardId" type="hidden" value="<?php echo $this->item->dashboard_id;?>" />
<input id="dashboardClusterId" type="hidden" value=""/>
<input id="dashboardTagId" name=dashboardTagId[] type="hidden" value=""/>
<?php   
				$params     							= ComponentHelper::getParams('com_multiagency');
				$multiagencyTrusteeRoleId = (int) $params->get('multiagency_trustee_group');
				$orgAdminRoleId           = (int) $params->get('multiagency_school_admin_group', '0', 'INT');

				
	// Hide the Tags filter for non-Super Admin users
	if ($user->authorise('core.manageall', 'com_cluster'))
	{ ?>
		<input type="hidden" id ="dpeadminuser" name="dpeadminuser" value="dpeadmin"/>
	<?php }else if (in_array($multiagencyTrusteeRoleId, $user->groups)){?>

	<input type="hidden" id ="dpeadminuser" name="dpeadminuser" value="<?php echo $multiagencyTrusteeRoleId; ?>"/>
	<?php }
	else if (in_array($orgAdminRoleId, $user->groups) && ($user->authorise('core.manageall', 'com_cluster') || $user->authorise('core.admin'))){?>

	<input type="hidden" id ="dpeadminuser" name="dpeadminuser" value="<?php echo $orgAdminRoleId; ?>"/>
	<?php }  ?>
