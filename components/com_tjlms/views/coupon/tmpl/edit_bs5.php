<?php
/**
 * @version     1.0.0
 * @package     com_tjlms
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
 */
// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('bootstrap.tooltip');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.multiselect');
?>
<script type="text/javascript">
	var oldCouponCode = '<?php echo str_replace("'", "\'", $this->form->getValue('code')); ?>';
	Joomla.submitbutton = function(task)
	{
		if (task == 'coupon.cancel') {
			Joomla.submitform(task, document.getElementById('coupon-form'));
		}
		else
		{
			if(jQuery.trim(jQuery('#jform_value').val()) == '0')
			{
				jQuery('#jform_value').val('');
			}

			if (task != 'coupon.cancel' && document.formvalidator.isValid(document.getElementById('coupon-form')))
			{
				var isValid = true;
				var from_date = jQuery('#jform_from_date').val();
				var exp_date = jQuery('#jform_exp_date').val();

				if (from_date !== '' &&  exp_date !== '')
				{
					from_date = new Date(from_date);
					exp_date = new Date(exp_date);

					if(from_date > exp_date)
					{
						var dateValidationmsg = Joomla.Text._('COM_TJLMS_COUPON_DATE_VALIDATION');
						dispEnqueueMessage(dateValidationmsg, "coupon_date");
						isValid = false;
					}
				}
				if(isValid){
					dispEnqueueMessage('', "coupon_date");
				}
				var max_use = jQuery('#jform_max_use').val();
				var max_per_user = jQuery('#jform_max_per_user').val();

				if ((!max_use && max_per_user) || parseInt(max_use, 10) < parseInt(max_per_user, 10))
				{
					var maxvalidate = Joomla.Text._('COM_TJLMS_MAX_USER_VALIDATION');
					dispEnqueueMessage(maxvalidate,'max_per_user');
					isValid = false;
				}

				if (isValid)
				{
					dispEnqueueMessage('', "max_per_user");
				}
				if(!isValid)
				{
					return false;
				}
				var couponElem = document.getElementById('jform_code');
				validatecodes(couponElem,task);
			}
		}
	}

	function validatecodes(obj,task)
	{
		dispEnqueueMessage('','samecoupon');
		var course_id = jQuery('#jform_course_id').val();
		var codes = obj.value;
		var code = jQuery.trim(codes);
		obj.value = code;
		// If code value is not empety check for validation
		if (code && code != oldCouponCode)
		{
			jQuery.ajax
			({
				url:"<?php echo Uri::root(true); ?>/index.php?option=com_tjlms&task=coupon.validatecode",
				type: "POST",
				data:{couponcode:code, course_id:course_id},
				success: function(data)
				{
					if (data == 1)
					{
						dispEnqueueMessage('<?php echo $this->escape(Text::_('COM_TJLMS_COUPON_CODE_VALIDATION')); ?>','samecoupon');
						jQuery('#jform_code').val('');
						jQuery( "#jform_code" ).focus();
					}else if(task){
						Joomla.submitform(task, document.getElementById('coupon-form'));
					}
				}
			});
		}else if(task){
			Joomla.submitform(task, document.getElementById('coupon-form'));
		}
	}

	tjlms.coupon.init();
</script>


<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">

	<div class="tjlms_head_title">
		<?php
			$app = Factory::getApplication();
			$input = $app->input;
			$cop_id = $input->get('id', '', 'INT');
			?>
		<h2><?php echo (!empty($cop_id))? Text::_("COM_TJLMS_EDIT_COUPON"): Text::_("COM_TJLMS_ADD_COUPON"); ?></h2>
	</div>

	<form action="<?php echo Route::_('index.php?option=com_tjlms&view=coupon'. ($cop_id ? ('&id='. $cop_id ) : ''))?>" method="post" enctype="multipart/form-data" name="adminForm" id="coupon-form" class="form-validate form-horizontal ">
		<div class="tjlms-filters tjlms-coupon-view row">
			<fieldset class="adminform">
				<div class="col-sm-6">
					<div class="form-group" style="display:none">
					<?php echo $this->form->renderField('id'); ?>
					</div>
					<div class="form-group">
						<div class="col-sm-3">
							<?php echo $this->form->getLabel('name'); ?>
						</div>
						<div class="col-sm-9">
							<?php echo $this->form->getInput('name'); ?>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-3">
							<?php echo $this->form->getLabel('state'); ?>
						</div>
						<div class="col-sm-9">
							<?php echo $this->form->getInput('state'); ?>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-3">
							<?php echo $this->form->getLabel('code'); ?>
						</div>
						<div class="col-sm-9">
							<?php echo $this->form->getInput('code'); ?>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-3">
							<?php echo $this->form->getLabel('value'); ?>
						</div>
						<div class="col-sm-9">
							<?php echo $this->form->getInput('value'); ?>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-3">
							<?php echo $this->form->getLabel('created_by'); ?>
						</div>
						<div class="col-sm-9">
							<?php echo $this->form->getInput('created_by'); ?>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-3">
							<?php echo $this->form->getLabel('description'); ?>
						</div>
						<div class="col-sm-9">
							<?php echo $this->form->getInput('description'); ?>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-3">
							<?php echo $this->form->getLabel('couponParams'); ?>
						</div>
						<div class="col-sm-9">
							<?php echo $this->form->getInput('couponParams'); ?>
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<div class="col-sm-3">
							<?php echo $this->form->getLabel('course_id'); ?>
						</div>
						<div class="col-sm-9">
							<?php echo $this->form->getInput('course_id'); ?>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-3">
							<?php echo $this->form->getLabel('subscription_id'); ?>
						</div>
						<div class="col-sm-9">
							<?php echo $this->form->getInput('subscription_id'); ?>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-3">
							<?php echo $this->form->getLabel('val_type'); ?>
						</div>
						<div class="col-sm-9">
							<?php echo $this->form->getInput('val_type'); ?>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-3">
							<?php echo $this->form->getLabel('from_date'); ?>
						</div>
						<div class="col-sm-9">
							<?php echo $this->form->getInput('from_date'); ?>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-3">
							<?php echo $this->form->getLabel('max_use'); ?>
						</div>
						<div class="col-sm-9">
							<?php echo $this->form->getInput('max_use'); ?>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-3">
							<?php echo $this->form->getLabel('max_per_user'); ?>
						</div>
						<div class="col-sm-9">
							<?php echo $this->form->getInput('max_per_user'); ?>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-3">
							<?php echo $this->form->getLabel('privacy'); ?>
						</div>
						<div class="col-sm-9">
							<?php echo $this->form->getInput('privacy'); ?>
						</div>
					</div>
				</div>
			</fieldset>

			<div class="col-sm-3">
				<button type="button" class="btn btn-default  btn-primary com_jticketing_margin validate"  onclick="Joomla.submitbutton('coupon.save')">
					<span><?php echo Text::_('JSUBMIT'); ?></span>
				</button>
				<button type="button" class="btn btn-default"  onclick="Joomla.submitbutton('coupon.cancel')">
					<span><?php echo Text::_('JCANCEL'); ?></span>
				</button>
			</div>

			<input type="hidden" name="task" value="" />
			<?php echo HTMLHelper::_('form.token'); ?>

		</div>
	</form>
</div>

<script>
	tjlms.coupon.init();
</script>
