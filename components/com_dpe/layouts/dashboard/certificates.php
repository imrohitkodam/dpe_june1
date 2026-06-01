<?php
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Plugin\PluginHelper;

JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);
JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);

$app               = Factory::getApplication();
$menu              = $app->getMenu();
$menuItem          = $menu->getItems('link', 'index.php?option=com_tjcertificate&view=certificates&layout=my', true);
$myCertificates    = $displayData['myCertificates'];
$user              = Factory::getUser();
$certificateCreate = $user->authorise('certificate.external.create', 'com_tjcertificate');

PluginHelper::importPlugin('content');

$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
$clusters         = $clusterUserModel->getUsersClusters($user->id);

// Check user org having elearning access

if (!$user->authorise('core.manageall', 'com_cluster'))
{
	$elearingAccess = array();

	foreach ($clusters as $cluster)
	{
		$elearingAccess[] = RBACL::check($user->id, 'com_cluster', 'core.viewShika', 'com_tjlms', $cluster->cluster_id);
	}
}

$elearingAccess = array_filter($elearingAccess);
?>
<div class="row">
	<div class="col-xs-9 pb-10">
		<h4><strong><?php echo Text::_('COM_DPE_ASSIGN_CERTIFICATES_DASHBOARD_MY_CERTIFICATES_HEAD'); ?></strong></h4>
	</div>

	<?php 
	if (!empty($myCertificates) || !empty($elearingAccess)) 
	{
	?>
		<div class="col-xs-3">
			<div class="pull-right">
				<a class="hasTooltip" target="_blank"
				href="<?php echo Route::_($menuItem->link . '&Itemid=' . $menuItem->id); ?>" target="_blank">
					<?php echo Text::_('COM_DPE_DASHBOARD_VIEW_ALL_CERTIFICATES'); ?>
				</a>
			</div>
		</div>
	<?php
	}
	?>
</div>
<?php
	if ($certificateCreate && !empty($elearingAccess))
	{
		$trainingrecordMenuItem = $menu->getItems('link', 'index.php?option=com_tjcertificate&view=trainingrecord&layout=edit', true);
		$recordFormLink         = 'index.php?option=com_tjcertificate&view=trainingrecord&layout=edit';
		$addRecordLink          = Route::_($recordFormLink . '&Itemid=' . $trainingrecordMenuItem->id);
?>
	<div class="mb-10">
		<a class="btn btn-primary btn-small" href="<?php echo $addRecordLink;?>" target="_blank">
			<i class="fa fa-plus me-2"></i><?php echo Text::_('COM_TJCERTIFICATE_ADD_EXTERNAL_CERTIFICATE'); ?>
		</a>
	</div>
<?php
	}
?>
<div class="clearfix"></div>
<?php
if (empty($elearingAccess))
{?>
	<div class="alert alert-warning">
		<?php echo Text::_('COM_DPE_ELEARNING_DASHBOARD_NO_TOOL_ACCESS');?>
	</div>
<?php
}?>

<?php
if (empty($myCertificates) && !empty($elearingAccess)) 
{
?>
	<div class="alert alert-warning">
		<?php echo Text::_('COM_DPE_NO_CERTIFICATES_MESSAGE')?>
	</div>
<?php 
} 
else 
{
	?>
	<table class="table table-hover">
		<thead>
		</thead>
		<tbody>
			<?php 
			foreach ($myCertificates as $myCertificate) 
			{ 
				$certificateObj = TJCERT::Certificate($myCertificate->id);
				$data = Factory::getApplication()->triggerEvent('onGetCertificateClientData', array($myCertificate->client_id, $myCertificate->client));
			?>
			<tr>
				<td>
					<?php
						if ($myCertificate->is_external)
						{
							echo $myCertificate->name;
						}
						else
						{
							echo ($data[0]->title ? $data[0]->title : "-");
						}
					?>

					<?php echo $myCertificate->is_external ? Text::_('COM_DPE_CERTIFCATE_DASHBOARD_EXTERNAL_CERTIFICATE') : Text::_('COM_DPE_CERTIFCATE_DASHBOARD_DPE_CERTIFICATE');?>
				</td>
				<td>
					<?php
						if (!$myCertificate->is_external) 
						{
							$certificateUrl = $certificateObj->getUrl('',false);
						}
						elseif($myCertificate->is_external)
						{
							$certificateUrl = $certificateObj->getUrl('',false, true);
						}
					?>
					<a class="hasTooltip" target="_blank"
					href="<?php echo $certificateUrl; ?>"
					<?php echo $target;?>>
						<span class="pull-right"><?php echo Text::_('COM_DPE_CERTIFCATE_DASHBOARD_VIEW_LABLE')?></span>
					</a>
				</td>
			</tr>
			<?php } ?>
		</tbody>
	</table>

<?php } ?>
