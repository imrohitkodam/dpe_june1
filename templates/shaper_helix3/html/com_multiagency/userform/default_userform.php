<?php
/**
 * @package     Multiagency
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');

HTMLHelper::script('media/com_multiagency/js/user.min.js');
HTMLHelper::script('media/com_dpe/js/dpe.min.js');
Text::script('COM_USER_TITLE_MANAGEENROLLMENTS_IMPORT_ALREADY_ASSIGNED');
Text::script('COM_MULTIAGENCY_INTERACTION_AJAX_ERROR');
Text::script('COM_USER_TITLE_MANAGEENROLLMENTS_IMPORT_UPDATED');

JLoader::register('MultiagencyFrontendHelpers', JPATH_SITE . '/components/com_multiagency/helpers/multiagency.php');
$multiAgencyHelper = new MultiagencyFrontendHelpers;

// Create Model Object
JModelLegacy::addIncludePath(JPATH_ROOT . '/components/com_dpe/models');
$schoolModel = BaseDatabaseModel::getInstance('School', 'DpeModel');
$input = Factory::getApplication()->input;

// Get component prams
$params                   = ComponentHelper::getParams('com_multiagency');
$leadConsultantGroupId    = (int) $params->get('multiagency_leadconsultant_group', 'INT');

$userGroups            = Factory::getUser($this->item->id)->groups;
$isViewOnly            = false;

$isLeadConsultant = in_array($leadConsultantGroupId, $userGroups) ? true : false;

if (!empty($this->item->id) && (($this->user->id == $this->item->id) || $isLeadConsultant))
{
	$isViewOnly = true;
}

$app    = Factory::getApplication();
$itemId = $app->input->getInt('Itemid', 0);
?>
<div class="row-fluid">
	<div class="page-header">
		<h2>
			<?php if ($isViewOnly): ?>
				<?php echo Text::_('COM_MULTIAGENCY_VIEW_USER'); ?>
			<?php elseif (!empty($this->item->id)): ?>
				<?php echo Text::_('COM_MULTIAGENCY_EDIT_USER'); ?>
			<?php else: ?>
				<?php echo Text::_('COM_MULTIAGENCY_ADD_MULTIAGENCY_USER'); ?>
			<?php endif; ?>
		</h2>
	</div>
</div>

<form id="form-user" action="<?php echo Route::_('index.php?option=com_multiagency&task=userform.save&Itemid='. $itemId); ?>" method="post" class="form-validate form-horizontal ucm-form-styling" enctype="multipart/form-data">
	<div class="row users-edit">
		<div class="col-sm-7 col-md-5">
			<div class="control-group"><?php echo $this->form->renderField('name'); ?></div>
			<div class="control-group"><?php echo $this->form->renderField('email'); ?></div>
			<?php if ($this->item->id): ?>
			<div class="control-group"><?php echo $this->form->renderField('reset_password'); ?></div>
			<div class="control-group"><?php echo $this->form->renderField('random_password'); ?></div>
			<div class="control-group"><?php echo $this->form->renderField('password'); ?></div>
			<div class="control-group"><?php echo $this->form->renderField('confirmPassword'); ?></div>
			<?php endif; ?>
			<div class="control-group"><?php echo $this->form->renderField('client_id'); ?></div>
		</div>
		<div class="col-sm-12 dp-sub-form">
				<div class="">
				<?php
					
					BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
					$userFormModel = $this->getModel('UserForm', 'MultiagencyModel');

					// DPE Hack start
					JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
					$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
					$clusters = $clusterUserModel->getUsersClusters($this->user->id);
					$orgAdmin = ComponentHelper::getParams('com_multiagency')->get('school_admin_role_id');

					// This block foreach loop added to get the agency id where user have add user action and set 1st agency as active tab
					foreach ($clusters as $cluster)
					{
						if (RBACL::authorise($this->user->id, 'com_cluster', 'core.adduser', 'com_multiagency',$cluster->cluster_id))
						{
							$clusterIds[] = $cluster->client_id;
						}
					}
					// DPE Hack end

					echo HTMLHelper::_('bootstrap.startTabSet', 'schoolTab', array('active' => 'display-' . $clusterIds[0]));

					// For DPE Instead of "$this->agencyList" used $clusters to load active licence cluster
					// In foreach block used "$cluster->client_id" instead of "$agency->value"
					foreach ($clusters as $key => $cluster)
					{
						// Action checked to show organisations where user having add user access
						if (RBACL::authorise($this->user->id, 'com_cluster', 'core.adduser', 'com_multiagency',$cluster->cluster_id))
						{	
							// Job title data 
							$userId = $input->get('id', 0, 'int');

							Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
					  		$clustertableInstance  = Table::getInstance('Clusters', 'ClusterTable');
					  		$clustertableInstance->load(array('client_id' => $cluster->client_id));
					   		$clusterId = $clustertableInstance->id;
							$userJobTitle = $schoolModel->getJobTitlebyUserData($clusterId,$userId);
							
							// Job title data end
							
							$data = array();

							if (empty($cluster->client_id))
							{
								continue;
							}

							$data = $this->item->agency_role_map['agency_role_map' . $cluster->client_id];
							$showTab = true;
							if ($this->item->id && ($this->item->id == $this->user->id) && $data['client_id'] != $cluster->client_id)
							{
								$showTab = false;
							}

							$class = '';
							if ($data['rolelist'] != $this->staffRoleId && $data['rolelist'] != $this->trusteeRoleId)
							{
								$class="hide";
							}

							if ($showTab) {
							echo HTMLHelper::_('bootstrap.addTab', 'schoolTab', 'display-' . $cluster->client_id, $cluster->title); ?>
								<div>
									<input type="hidden" name="jform[agency_role_map][agency_role_map<?php echo $key;  ?>][client_id]" value="<?php echo $cluster->client_id; ?>">
								</div>
								<div class="control-group">
									<div class="control-label">
										<label><?php echo Text::_('COM_MULTIAGENCY_FORM_DESC_ROLE_LIST'); ?></label>
									</div>
									<div class="controls">
										<?php
										$allowRoles = array();
										$allowRoles = $userFormModel->getUserAgencyAllowedRolesOptions($cluster->client_id);
										?>
										<?php echo HTMLHelper::_('select.genericlist', $allowRoles, 'jform[agency_role_map][agency_role_map'.$key.'][rolelist]','class="adminuser" onchange="userform.showRelatedField(this)"', 'value', 'text', $data['rolelist'], 'jform_agency_role_map__agency_role_map'.$key.'__rolelist' ); ?>
									</div>
								</div>

								<div class="control-group showRelatedField <?php echo $class; ?>">
									<div class="control-label">
										<label><?php echo Text::_('COM_MULTIAGENCY_FORM_RELATED_ROLE'); ?></label>
									</div>
									<div class="controls">
										<?php
											$relatedroleList = array();
											$relatedroleList = $userFormModel->getUserAgencyRelatedRolesOptions($cluster->client_id);
										?>
										<?php echo HTMLHelper::_('select.genericlist', $relatedroleList, 'jform[agency_role_map][agency_role_map'.$key.'][relatedrole][]',' multiple="true" class="" onchange=""', 'value', 'text', $data['relatedrole'], 'jform_agency_role_map__agency_role_map'.$key.'__relatedrole' ); ?>
									</div>
								</div>
							<!--Job title start-->
									<div class="control-group">
									<div class="control-label">
										<label><?php echo Text::_('COM_MULTIAGENCY_FORM_DESC_TITLE_LIST'); ?></label>
									</div>
									<div class="controls">
										<?php
										$jobTitles = array();
										BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_cluster/models');
										$clusterModel = BaseDatabaseModel::getInstance('Cluster', 'ClusterModel');
										$cluster      = $clusterModel->getClusterByClient($client=null, $cluster->client_id);
										$jobTitles    = $schoolModel->getJobTitlesByClusterId($cluster->id);
										
										for ($index=0; $index < count($jobTitles); $index++)
										{
												 $jobTitleValue[$index]= array('value'=>$jobTitles[$index]->id,'text'=>$jobTitles[$index]->value);
										}	

										if (is_array($jobTitleValue))
											{
												$jobTitleValue = array_values($jobTitleValue);
												array_unshift($jobTitleValue, array('value'=>'','text'=> Text::_('COM_MULTIAGENCY_SELECT_TITLE_OPTION')));
											}
										?>
										<?php echo HTMLHelper::_('select.genericlist',  $jobTitleValue, 'jform[agency_role_map][agency_role_map'.$key.'][jobtitle]','class="adminuser2" onchange="userform.showRelatedField(this)"', 'value', 'text', $userJobTitle, 'jform_agency_role_map__agency_role_map'.$key.'__jobtitle' ); ?>
									</div>
								</div>
								<!--Job title end-->
						<?php echo HTMLHelper::_('bootstrap.endTab');  ?>
						<?php } ?>
					<?php } ?>
				<?php } ?>
				<?php echo HTMLHelper::_('bootstrap.endTabSet');?>
				</div>
			<div>
			<div class="control-group md-w-52">
				<div class="controls text-end">
					<?php if (!$isViewOnly) { ?>
					<button type="submit" onclick="return checkDuplicates();" class="validate btn btn-primary"><?php echo Text::_('JSUBMIT'); ?></button>
					<?php } ?>
					<a class="btn btn-default" href="<?php echo Route::_('index.php?option=com_multiagency&view=users&Itemid=' . $itemId); ?>"title="<?php echo Text::_('JCANCEL'); ?>">
						<?php echo Text::_('JCANCEL'); ?>
					</a>
				</div>
			</div>


			<input type="hidden" name="jform[id]" id="itemId" value="<?php echo $this->item->id; ?>" />
			<?php if(empty($this->item->created_by)): ?>
				<input type="hidden" name="jform[created_by]" value="<?php echo $this->user->id; ?>" />
			<?php else: ?>
				<input type="hidden" name="jform[created_by]" value="<?php echo $this->item->created_by; ?>" />
			<?php endif; ?>
			<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
			<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />
			<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
			<input type="hidden" name="option" value="com_multiagency"/>
			<input type="hidden" name="task" value="userform.save"/>
			<?php echo HTMLHelper::_('form.token'); ?>
			<?php echo HTMLHelper::_( 'jquery.token'); ?>
		</div>
	</div>
</form>
<script type="text/javascript">
	var userform = {
		showRelatedField: function(obj){
			var role           = jQuery(obj).val();
			var id             = jQuery(obj).attr('id');
			var id             = jQuery(obj).attr('id').split('__rolelist');
			var relatedFieldid = id[0];
			relatedFieldid     = relatedFieldid + '__relatedrole';
			
			var staffRole   = '<?php echo $this->params->get("member_role_id", "0", "INT"); ?>';
			var trusteeRole = '<?php echo $this->params->get("organization_trustee_role_id", "0", "INT"); ?>';

			if (role == staffRole || role == trusteeRole)
			{
				jQuery('#'+relatedFieldid).parent().parent().removeClass('hide');
			}
			else
			{
				jQuery('#'+relatedFieldid).parent().parent().addClass('hide');
			}
		}
	}

	jQuery(document).ready(function(){

		jQuery('.adminuser2').css('width','50%');
	})
</script>
