<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 * @author      Techjoomla extensions@techjoomla.com
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

if (JVERSION >= '3.0')
{
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('behavior.formvalidator');
	HTMLHelper::_('formbehavior.chosen', 'select');
	HTMLHelper::_('behavior.multiselect');
}
else
{
	HTMLHelper::_('behavior.tooltip');
	HTMLHelper::_('behavior.formvalidator');
	HTMLHelper::_('behavior.keepalive');
}


// Import CSS
HTMLHelper::_('script', '/media/com_tjlms/js/tjlms.js');
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

				var from_date = jQuery('#jform_from_date').val();
				var exp_date = jQuery('#jform_exp_date').val();

				if (from_date !== '' &&  exp_date !== '')
				{
					from_date = new Date(from_date);
					exp_date = new Date(exp_date);

					if(from_date > exp_date)
					{
						var dateValidationmsg = Joomla.Text._('COM_TJLMS_DATE_RANGE_VALIDATION');
						enqueueSystemMessage(dateValidationmsg, ".admin.com_tjlms.view-coupon");
						return false;
					}
				}

				var max_use = jQuery('#jform_max_use').val();
				var max_per_user = jQuery('#jform_max_per_user').val();

				if ((max_use && max_per_user) && parseInt(max_use, 10) < parseInt(max_per_user, 10))
				{
						var maxvalidate = Joomla.Text._('COM_TJLMS_MAX_USER_VALIDATION');
						enqueueSystemMessage(maxvalidate, ".admin.com_tjlms.view-coupon");
						return false;
				}

				var couponElem = document.getElementById('jform_code');
				validatecode(couponElem,task);
			}
		}
	}

	function validatecode(obj,task)
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
				url:"index.php?option=com_tjlms&task=coupon.validatecode",
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

</script>


<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">


<form action="<?php echo Route::_('index.php?option=com_tjlms&layout=edit&id=' . (int) $this->item->id); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="coupon-form" class="form-validate">

	<div class="form-horizontal">

		<div class="row-fluid">
			<div class="span10 form-horizontal">
				<fieldset class="adminform">

					<div class="control-group" style="display:none">
						<div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('id'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('name'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('name'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('state'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('state'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('course_id'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('course_id'); ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('subscription_id'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('subscription_id'); ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('created_by'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('created_by'); ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('code'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('code'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('value'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('value'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('val_type'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('val_type'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('from_date'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('from_date'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('exp_date'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('exp_date'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('max_use'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('max_use'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('max_per_user'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('max_per_user'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('description'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('description'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('couponParams'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('couponParams'); ?></div>
					</div>
					<div class="controls"><?php echo $this->form->getInput('privacy'); ?></div>
				</fieldset>
			</div>
		</div>

		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>

	</div>
</form>

</div>

<script>
	tjlms.coupon.init();
</script>
