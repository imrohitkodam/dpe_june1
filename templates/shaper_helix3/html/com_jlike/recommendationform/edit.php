<?php
/**
 * @package     Jlike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Table\Table;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('bootstrap.renderModal');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('jquery.token');
HTMLHelper::_('formbehavior.chosen', 'select');
Text::script('COM_JLIKE_SELECT_USER');
$options['relative'] = true;
HTMLHelper::_('script', 'media/system/js/messages.min.js');
HTMLHelper::_('script', 'com_jlike/jlikeService.js', $options);
HTMLHelper::_('script', 'com_jlike/jlike.js', $options);
HTMLHelper::_('script', 'media/com_jlike/vendors/loader/js/loadingoverlay.min.js');
HTMLHelper::_('script', 'media/com_dpe/js/dpepreloader.js');
$doc = Factory::getDocument();
$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjreports/tables');

$reportTable = Table::getInstance('tjreport', 'TjreportsTable');
$reportTable->load(array('plugin' => 'dpeenrolmentreport'));

$app = Factory::getApplication();

JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
$clusters         = $clusterUserModel->getUsersClusters($this->user->id);

// Check for Can view recommendatoin form
if (ComponentHelper::getComponent('com_subusers', true)->enabled)
{
	JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

	if (!$this->user->authorise('core.manageall', 'com_cluster'))
	{
		$canAccessTodoForm   = null;
		$staffAccessTodoForm = null;
		$adminOrgs           = array();
		$staffOrgs           = array();

		foreach ($clusters as $cluster)
		{
			if (!$canAccessTodoForm)
			{
				$canAccessTodoForm   = RBACL::check($this->user->id, 'com_cluster', 'core.manageNotificationManager', 'com_jlike', $cluster->cluster_id);
				$staffAccessTodoForm = RBACL::check($this->user->id, 'com_cluster', 'core.own.manageNotifications', 'com_jlike', $cluster->cluster_id);

				if ($canAccessTodoForm)
				{
					$adminOrgs[]  = $cluster->cluster_id;
				}

				if ($staffAccessTodoForm)
				{
					$staffOrgs[] = $cluster->cluster_id;
				}
			}
		}

		if (!$canAccessTodoForm && !$staffOrgs)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->setHeader('status', 403, true);

			return;
		}
	}
}

$tmpl       = $app->input->getString('tmpl', '');
$popupClass = '';
$columnClass = '';

// Check template component set or not.
if (!empty($tmpl))
{
	$doc = Factory::getDocument();
	$doc->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
	$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
	$popupClass = 'notification-add-form';
	$columnClass = 'col-sm-12';
	$cancelButton = "";
}
else
{
	$columnClass = 'col-sm-6';
	$cancelButton = "Joomla.submitbutton('recommendation.cancel')";
}

$urlData  = new Registry($this->item->params);
$pageLink = $urlData['current_page_link'];
?>
<div id="system-message-container"></div>
<div class="clearfix"></div>
<form action="" class="form-validate form-horizontal add-todos ucm-form-styling recommendation-form <?php echo $popupClass;?>"
	method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
	<div class="timelog-add-form activity-edit front-end-edit jlike-timelog <?php if (!empty($popupClass)){echo 'modal-header';}?>">
		
		<h3 class="activity-header fs-20">
			<?php

			echo (empty($this->item->id)) ? Text::_('COM_JLIKE_ADD_RECOMMENDATION') : Text::_('COM_JLIKE_EDIT_RECOMMENDATION');
			?>
		</h3>
		<?php if (!empty($popupClass))
		{
			?>
			<button type="button" data-refresh="" class="close closepopup">&times;</button>
			<?php
		}
		?>

	</div>
	<div class="container-fluid p-3 add-to-do-form">
		<div class="row">
			<div class="form-group" style="display:none;">
				<label class="col-sm-6 col-12"><?php echo $this->form->getLabel('id'); ?></label>
				<div class="col-sm-6 col-12"><?php echo $this->form->getInput('id'); ?></div>
			</div>
			
			<div class="col-sm-6">
				<div class="col-12">
					<?php
						// If multiagency component enable and agency support config is true then render clutser field
					if ($this->isAgencyEnabled)
					{
						echo $this->form->renderField('clusters');
					}
					?>
				</div>
				<div class="col-12">
					<?php
					echo $this->form->renderField('title');
					?>
				</div>
				<div class="col-12">
					<?php
					echo $this->form->renderField('sender_msg');

					?>
				</div>
				<div class="col-12">
					<?php
					if (!$pageLink)
					{
						echo $this->form->renderField('currentLink');
					}
					else
					{
						$this->form->setFieldAttribute('currentLink', 'disabled', 'true');
						echo $this->form->renderField('currentLink', null, true);?>
						<?php

					}


					?>
				</div>
				<div class="col-12">
					<div class="control-group ">

						<div class="controls">
							<input type="text" name="jform[current_page_link]" id="current_page_link" class="inputbox" size="40" aria-invalid="false" value="<?php	echo htmlspecialchars($pageLink, ENT_QUOTES, 'UTF-8'); ?>">
						</div>
					</div>
				</div>
				<br>
				
				<div class="col-12">
					<?php
					if ($this->item->id && ($this->item->assigned_to != $this->user->id))
					{
						echo $this->form->renderField('assigned_to');
					}
					?>
					<div id = 'read_understood'hidden="hidden">
						<?php if (!$this->item->id)
						{

							if ($adminOrgs || $this->user->authorise('core.manageall', 'com_cluster'))
							{
								echo $this->form->renderField('read_and_understood');
							}
						}
						?>
					</div>
					<div id = 'usedinpractice'hidden="hidden">
						<?php if (!$this->item->id)
						{

							if ($adminOrgs || $this->user->authorise('core.manageall', 'com_cluster'))
							{
								echo $this->form->renderField('used_in_practice');
							}
						}
						?>
					</div>



					<div id="course_title" hidden="hidden">
						<label><?php echo Text::_('PLG_CONTENT_JLIKE_MULTIAGENCY_FIELD_COURSE_NAME').' : '."<span  id='courseName' style='color:Black;'></span>"; ?> </label>

						<?php if (!$this->item->id)
						{
							if ($adminOrgs || $this->user->authorise('core.manageall', 'com_cluster'))
							{
								echo $this->form->renderField('course_status');
							}
						}
						?>
					</div>

					<?php if (!$this->item->id)
					{
						if ($adminOrgs || $this->user->authorise('core.manageall', 'com_cluster'))
						{
							?>
							<div id = 'cluster_user'>
								<?php echo $this->form->renderField('all_cluster_users');
								echo $this->form->renderField('assigned_to_users');
								?>
							</div>
							<?php
						}
					}
					?>
				</div>
				<div class="col-12">
					<?php
					if ($adminOrgs || $this->user->authorise('core.manageall', 'com_cluster'))
					{
							//echo $this->form->renderField('cc_users');
					}
					?>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="col-12">
					<?php


					JLoader::import('components.com_jlike.tables.todos', JPATH_ADMINISTRATOR);
					$todosTable = Table::getInstance('Todos', 'JlikeTable');
					$todosTable->load($this->item->id);

					JLoader::import('components.com_jlike.tables.content', JPATH_ADMINISTRATOR);
					$contentTable = Table::getInstance('Content', 'JlikeTable');
					$contentTable->load($todosTable->content_id);

					if ($this->item->id && (($contentTable->element != 'com_tjlms.lesson') && ($contentTable->element != 'com_tjlms.course')))
					{
						echo $this->form->renderField('status');
					}
					?>
				</div>
				<div class="col-12">
					<?php
					echo $this->form->renderField('due_date');

					?>
				</div>
				<div class="col-12">
					<?php
					echo $this->form->renderField('reminder', null, null, ['class' => 'reminder-field']);
					?>
				</div>
			</div>
			<br>
			<?php if(empty($this->item->id)) { 
				?>
				<div class="preloader-wrap" style= "display: none;">
					<div class="percentage" id="precent" ></div>
					<div class="newloader">
						<div class="trackbar">
							<div class="loadbar"></div>
						</div>
						<div class="glow"></div>
					</div><br><br>
					<div class="getdata" id="getdata"><?php echo Text::_('COM_DPE_LOADER_SAVING_DATA'); ?></div>
				</div>
			<?php }?>
			<div class="control-group popup-action-btn mr-20">
				<div class="controls pull-right">
					<?php
					if ($this->item->id || ($staffOrgs && empty($adminOrgs)))
					{
						?>
						<button onclick="Joomla.submitbutton('recommendationform.save');"
						type="button" class="btn btn-primary"><?php echo Text::_('JSUBMIT'); ?></button>
						<?php
					}
					else
					{
						// Ajax saving while add todos
						?>
						<button type="button" class="btn btn-primary addTodoForm"><?php echo Text::_('JSUBMIT'); ?></button>
						<?php
					}
					?>
					<button type="button " style="<?php if (!empty($tmpl)){echo'border: 1px solid;padding: 1px;font-size: 17px;';}?>" class="btn btn-default btn <?php if(!empty($tmpl)){echo"closepopup ";}?>"  onclick="<?php echo htmlspecialchars($cancelButton, ENT_QUOTES, 'UTF-8');?>">
						<span><?php echo Text::_('JCANCEL'); ?></span>
					</button>
				</div>
			</div>
		</div>
	</div>
	
	<input type="hidden" name="jform[todoeditForm]" id="todoeditForm" value="1"/>					
	<input type="hidden" id="assigned_user_id" value="<?php echo htmlspecialchars($this->item->assigned_to, ENT_QUOTES, 'UTF-8'); ?>" />
	<input type="hidden" name="jform[id]" id="id" value="<?php echo htmlspecialchars($this->item->id, ENT_QUOTES, 'UTF-8'); ?>" />
	<input type="hidden" name="cc_users" id="cc_users" value="<?php echo htmlspecialchars($this->item->cc_users, ENT_QUOTES, 'UTF-8'); ?>" />
	<input type="hidden" name="option" value="com_jlike"/>
	<input type="hidden" name="task" value="recommendationform.save"/>
	<input type="hidden" name="jform[element]" id="element" value="com_jlike.generic_todo"/>
	<input type="hidden" name="jform[element_id]" id="element_id" value="1"/>
	<input type="hidden" name="jform[url]" id="url" value="<?php echo htmlspecialchars(Uri::getInstance()->toString(), ENT_QUOTES, 'UTF-8');?>"/>
	<input type="hidden" name="jform[content_id]" id="content_id" value="<?php echo htmlspecialchars($this->item->content_id, ENT_QUOTES, 'UTF-8'); ?>"/>
	<!-- <input type="hidden" name="jform[current_page_link]" id="current_page_link" value=""/> -->
	<input type='hidden'name="jform[course_id]", id="course_id" value=""/>
	<input type='hidden'name="jform[contentId]", id="contentId" value=""/>
	<input type='hidden'name="jform[is_todo_specific]", id="is_todo_specific" value="1"/>
	<input type='hidden'name="jform[chceklist_id]", id="chceklist_id" value=""/>
	
	

	<?php echo HTMLHelper::_('form.token'); ?>
</form>
<script type="text/javascript">

	var todoId      = <?php echo json_encode($this->item->id); ?>;
	var currentLink = (<?php echo json_encode($pageLink); ?>)?<?php echo json_encode($pageLink); ?>:jQuery(window.parent.document.location).attr('href');

	jQuery('#current_page_link').val(currentLink);

	jQuery('#jform_currentLink').click(function(event)
	{

		if (jQuery('#jform_currentLink').attr('checked'))
		{
			jQuery('#current_page_link').val(currentLink);
			jQuery('#current_page_link').show();
		}
		else
		{
			jQuery('#current_page_link').val();
			jQuery('#current_page_link').hide();

		}
	})

	if (!todoId)
	{
		jQuery('#jform_currentLink').prop("checked", true);
	}

	var tmpl = <?php echo json_encode($tmpl);?>;

	if (tmpl)
	{
		jlike.init();
	}

	if (jQuery('#system-message div').hasClass('alert alert-success'))
	{
		jQuery("#system-message-container").fadeTo(4000, 500, function(){
			window.parent.document.location.reload(true);
			window.parent.SqueezeBox.close();
		});
	}

	if (currentLink)
	{
		var after = currentLink.substring(currentLink.indexOf('all-lessons'));

		if(after.includes("all-lessons"))
		{
			jQuery("#read_understood").removeAttr("hidden");
			jQuery("#jform_all_cluster_users-lbl").hide();
			jQuery("#cluster_user").hide();
			var contentId = window.parent.jQuery("input[name='filter[contentId]']").val();
			jQuery("#contentId").val(contentId);
			var cluster_id = window.parent.jQuery("input[name='cluster_id']").val();

			jQuery("#jform_clusters").val(cluster_id);
			jQuery('label[for="jform_read_and_understood2"]').remove();
			jQuery('label[for="jform_used_in_practice2"]').remove();

			var used_in_practice = window.parent.jQuery("input[name='used_in_practice']").val();
			if(used_in_practice == 'used')
			{
				jQuery("#usedinpractice").removeAttr("hidden");
			}

			setTimeout(function ()
			{
				jQuery('#jform_clusters_chosen').css('pointer-events','none');
			}, 1000);
		}

		jQuery.urlParam = function(reportId)
		{
			var results = new RegExp('[\?&]' + reportId + '=([^&#]*)').exec(currentLink);
			return results[1] || 0;
		}

		var report_id = <?php echo json_encode($reportTable->id); ?>;
		var actualRepId = window.parent.jQuery("#report_id").val();

		if(report_id == actualRepId)
		{
			jQuery("#course_title").removeAttr("hidden");

			var courseId = window.parent.jQuery("#filterscourse_id").val();
			jQuery("#course_id").val(courseId);

			var courseName = window.parent.jQuery("#filterscourse_id option:selected").text();

			jQuery("#courseName").text(courseName);
			jQuery("#jform_all_cluster_users-lbl").hide();
			jQuery("#cluster_user").hide();

			var organisationId = window.parent.jQuery("#filterscluster_id").val();

			if(organisationId)
			{
				jQuery("#jform_clusters").val(organisationId);
			}
		}

	// Set cluster in case of RS ticket
	var cluster_id_rsticket = window.parent.jQuery("input[name='cluster_id_rsticket']").val();

	if (typeof cluster_id_rsticket !== 'undefined')
	{
		jQuery("#jform_clusters").val(cluster_id_rsticket);
	}
}

jQuery(document).ready(function()
{

	// Remove selected users from target field when "Add all users to the group?" is Yes
	jQuery("#jform_all_cluster_users").change(function() {

		// Check field option is Yes
		if (jQuery("input[name='jform[all_cluster_users]']:checked").val() == 1)
		{
			// Remove selected options from target dropdown
			jQuery("#jform_assigned_to_users option:selected").removeAttr("selected");

			// Update the chosen dropdown after removing selected options
			jQuery("#jform_assigned_to_users").trigger("chosen:updated");
		}
	});

/*here if url contains courses then during add todo  it will fetch the parent element and element id and store in the hidden fields.
 
 this code will be used in future , currently not in use so its commented.
var url = window.parent.location.href;
if (url.indexOf('courses') != -1)
{
	var courseId = window.parent.jQuery("input[name='element_id']").val();
	
	var courseElement = window.parent.jQuery("input[name='element']").val();
	jQuery('#element').val(courseElement);
	jQuery('#course_id').val(courseId);
}*/

jQuery('.addTodoForm').click(function(event){
	event.stopPropagation();
	jQuery('.addTodoForm').prop('disabled',true);
	jlike.addTodos(); 

})

if (jQuery('#jform_currentLink').attr('checked'))
{
	jQuery('#current_page_link').show();
}
else
{
	jQuery('#current_page_link').hide();
}

});

if(window.parent.jQuery('#checklistTodoDate').length)
{
	var currentUrl = window.parent.location.href;
	var url = new URL(currentUrl);
	var checklistId = url.searchParams.get('id');
	var cluster = url.searchParams.get('cluster_id');

	if(currentUrl.includes("best-practice-library/best-practice/") || currentUrl.includes('com_sppagebuilder'))
	{
		checklistId = window.parent.jQuery("#recordId").val();
		jQuery('#chceklist_id').val(checklistId);
		jQuery("#jform_clusters").val(window.parent.jQuery("#filtercluster_id").val());

	}else if(checklistId)
	{
		jQuery('#chceklist_id').val(checklistId);
	}

	if((cluster) && (!currentUrl.includes("best-practice-library/best-practice/") || !currentUrl.includes('com_sppagebuilder')))
	{
		jQuery("#jform_clusters").val(cluster);
		
	}
}


</script>
