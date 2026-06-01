<?php
/**
 * @package     Multiagency
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');

HTMLHelper::_('behavior.core'); // <-- This loads showon.js

HTMLHelper::script('media/com_multiagency/js/user.min.js');
HTMLHelper::script('media/com_dpe/js/dpe.min.js');
Text::script('COM_USER_TITLE_MANAGEENROLLMENTS_IMPORT_ALREADY_ASSIGNED');
Text::script('COM_MULTIAGENCY_INTERACTION_AJAX_ERROR');
Text::script('COM_USER_TITLE_MANAGEENROLLMENTS_IMPORT_UPDATED');

$app   = Factory::getApplication();
$document = Factory::getDocument();
$document->addScript(Uri::root() . 'media/com_dpe/js/tjucm.js');

$wa = $this->document->getWebAssetManager();
$wa->useScript('showon');
// Get user groups as per name
$leadConsultantGroup = Table::getInstance('Usergroup', 'JTable');
$leadConsultantGroup->load(array('title' => 'External Lead Consultant'));
$user     = Factory::getUser();
$userGroups            = Factory::getUser($this->item->id)->groups;
$leadConsultantGroupId = 0;
$isLeadConsultant      = false;
$isViewOnly            = false;

if (property_exists($leadConsultantGroup, 'id'))
{
	$leadConsultantGroupId = $leadConsultantGroup->id;
}

if (in_array($leadConsultantGroupId, $userGroups))
{
	$isLeadConsultant = true;
}

if (!empty($this->item->id) && (($this->user->id == $this->item->id) || $isLeadConsultant))
{
	$isViewOnly = true;
}

?>

<style>
	.create-group.radio-red input[type="radio"]:checked + label.btn-outline-danger {
		background-color: #d94623;
		border: 1px solid #d94623;
	}
</style>
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

<form id="form-user" action="<?php echo Route::_('index.php?option=com_multiagency&task=userform.save'); ?>" method="post" class="form-validate form-horizontal ucm-form-styling" enctype="multipart/form-data">
	<div class="row users-edit">
		<div class="col-sm-7 col-md-5">
			<div class="control-group"><?php echo $this->form->renderField('name'); ?></div>
			<div class="control-group"><?php echo $this->form->renderField('email'); ?></div>

			
			<?php if (!$this->item->id && $user->authorise('core.manageall', 'com_cluster')){?>
				<div class="control-group"><?php echo $this->form->renderField('user_created'); ?></div>

				<div class="control-group user-tags-switcher"><?php echo $this->form->renderField('use_tags'); ?></div>
			<?php }?>
			<?php if ($this->item->id):?>
				<div class="control-group"><?php echo $this->form->renderField('reset_password'); ?></div>
				<div class="control-group"><?php echo $this->form->renderField('random_password'); ?></div>
				<div class="control-group"><?php echo $this->form->renderField('password'); ?></div>
				<div class="control-group"><?php echo $this->form->renderField('confirmPassword'); ?></div>
			<?php endif;?>
			<div class="control-group"><?php echo $this->form->renderField('client_id'); ?></div>
		</div>

		<div class="col-sm-12 dp-sub-form">
		<?php if (!$this->item->id && $user->authorise('core.manageall', 'com_cluster')){?>
			<div class="control-group tags-form">
				<div class="control-group"><?php echo $this->form->renderField('tags'); ?></div>
				<div class="control-group"><?php echo $this->form->renderField('jobtitle'); ?></div>
				<div class="control-group"><?php echo $this->form->renderField('rolelist'); ?></div>
				<div class="control-group"><?php echo $this->form->renderField('relatedrole'); ?></div>
				<div class="control-group"><?php echo $this->form->renderField('dpelead'); ?></div>
			</div>
		<?php }?>

			<div class="control-group cluster-form"><?php echo $this->form->renderField('agency_role_map'); ?></div>
			<div>
				<div class="control-group md-w-52">
					<div class="controls text-end">
						<?php if (!$isViewOnly) { ?>
							<button type="submit" onclick="return checkDuplicates();" class="validate btn btn-primary"><?php echo Text::_('JSUBMIT'); ?></button>
						<?php } ?>
						<a class="btn btn-default" href="<?php echo Route::_('index.php?option=com_multiagency&view=users'); ?>"title="<?php echo Text::_('JCANCEL'); ?>">
							<?php echo Text::_('JCANCEL'); ?>
						</a>
					</div>
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
			<input type="hidden" id="jform_cluster_ids" name="jform[cluster_ids]" value="" />
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
	jQuery(document).ready(function() {
		jQuery('.subform-repeatable-group').each(function() {
			jQuery(this).find('.chosen-container.chosen-container-single').eq(0).remove();
		});
	});

jQuery(document).on('click', '.group-add', function (event, row) {
    setTimeout(function(){
        jQuery(".radio-red").each(function() {
            jQuery(this).find("input[type=radio]:checked").each(function() {
                jQuery('#' + this.id + '-lbl').css({
                    'background-color': '#d94623',
                    'border': '1px solid #d94623'
                });
            });
        });
    }, 1000);
});

	jQuery(document).on('click', '.group-add', function (event, row) {
		setTimeout(function() {
			jQuery('.chosen-container').each(function() {
				jQuery(this).removeAttr('style');
				jQuery(this).css('width', '250px');
			});
		}, 500);
	});

	jQuery(document).ready(function() {
		jQuery('.chosen-container').removeAttr('style');
		jQuery('.chosen-container').css('width','250px');

	jQuery(document).on('click', 'input[type=radio]', function(){
    var id = jQuery(this).attr('id'); 
    var prvId = jQuery(this).attr('id');
     
    if (id.endsWith('0')) { 
        jQuery('#'+id+'-lbl').addClass('active btn-outline-success');
        var nextlbl = id.replace('dpelead0','dpelead1')
        jQuery('#'+nextlbl+'-lbl').removeClass('active btn-outline-danger');
        jQuery('#'+nextlbl+'-lbl').css({
            'background-color': '#fff',
            'border': '1px solid #adadad'
        });
    } else {
        jQuery('#'+prvId+'-lbl').addClass('active btn-outline-danger');
        var prvlbl = prvId.replace('dpelead1','dpelead0');
        jQuery('#'+prvlbl+'-lbl').removeClass('active btn-outline-success');
        jQuery('#'+prvId+'-lbl').css({
                    'background-color': '#d94623',
                    'border': '1px solid #d94623'
                });
    }
});

	jQuery(document).on('change', '#jform_user_created', function() {
		if (jQuery(this).is(':checked')) {
			// Use tags - Yes
			jQuery('input[name="jform[use_tags]"][value="0"]').prop('checked', true).trigger('change');
			jQuery('.tags-form').show();
			jQuery('.cluster-form').hide();
			jQuery('.user-tags-switcher').hide();
		} else {
			jQuery('input[name="jform[use_tags]"][value="1"]').prop('checked', true).trigger('change');

			jQuery('.cluster-form').show();
			jQuery('.user-tags-switcher').show();
			// Optionally hide tags-form if use_tags is 'No'
			if (jQuery('input[name="jform[use_tags]"]:checked').val() == '1') {
				jQuery('.tags-form').hide();
			}
		}
	});

	if (jQuery('#jform_user_created').is(':checked')) {
		jQuery('#jform_user_created').trigger('change');
	}
	});

</script>