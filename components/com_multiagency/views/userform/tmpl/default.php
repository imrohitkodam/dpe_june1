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

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');

HTMLHelper::script('media/com_multiagency/js/user.min.js');
HTMLHelper::script('media/com_dpe/js/dpe.min.js');
Text::script('COM_USER_TITLE_MANAGEENROLLMENTS_IMPORT_ALREADY_ASSIGNED');
Text::script('COM_MULTIAGENCY_INTERACTION_AJAX_ERROR');
Text::script('COM_USER_TITLE_MANAGEENROLLMENTS_IMPORT_UPDATED');

// Get user groups as per name
$leadConsultantGroup = Table::getInstance('Usergroup', 'JTable');
$leadConsultantGroup->load(array('title' => 'External Lead Consultant'));
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
			<?php if ($this->item->id):?>
			<div class="control-group"><?php echo $this->form->renderField('reset_password'); ?></div>
			<div class="control-group"><?php echo $this->form->renderField('random_password'); ?></div>
			<div class="control-group"><?php echo $this->form->renderField('password'); ?></div>
			<div class="control-group"><?php echo $this->form->renderField('confirmPassword'); ?></div>
			<?php endif;?>
			<div class="control-group"><?php echo $this->form->renderField('client_id'); ?></div>
		</div>
		<div class="col-sm-12 dp-sub-form">
			<div class="control-group"><?php echo $this->form->renderField('agency_role_map'); ?></div>
			<div>
				<div class="control-group md-w-52">
					<div class="controls text-right">
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
</script>