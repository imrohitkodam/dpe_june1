<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tc
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2016-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidator');
?>
<script type="text/javascript">
	js = jQuery.noConflict();
	js(document).ready(function () {

	});

	Joomla.submitbutton = function (task) {

		if (task == 'content.cancel') {
			Joomla.submitform(task, document.getElementById('content-form'));
		}
		else {

			if (task != 'content.cancel' && document.formvalidator.isValid(document.id('content-form'))) {
				Joomla.submitform(task, document.getElementById('content-form'));
			}
			else {
				alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
			}
		}
	}

	jQuery(document).ready(function(){

		if(jQuery('label[for="jform_global_id0"]').hasClass("active")==true){
			jQuery('#url_pattern_id').removeAttr('required');
		}

			// Check if T&C is in edit state then set version and client is readonly
			var tc_id = jQuery("#tc_id").val();

			if (tc_id)
			{
				jQuery('#jform_version').attr('readonly', true);
				jQuery('#jform_client_id').attr('readonly', true);

				return true;
			}

			// Version & client field validation
		   document.formvalidator.setHandler('version', function(value) {
				var regex = /^[0-9.]*$/;
				if(!regex.test(value))
				{
					return false;
				};

				if (value <= 0)
				{
					alert('<?php echo $this->escape(JText::_('COM_TC_VALIDATION_FORM_ZERO_VERSION_TC')); ?>');
					jQuery('#jform_version').val('');

					return false;
				}

				var isValid = function() {
					return checkVersionClient();
				}();
				return isValid;
			});

			document.formvalidator.setHandler('client', function(value) {
				var isValid = function() {
					return checkVersionClient();
				}();
				return isValid;
			});
		});

		function checkVersionClient()
		{
			var tc_id = jQuery("#tc_id").val();

			if (tc_id)
			{
				return true;
			}

			var tcVersion = jQuery("#jform_version").val();
			var tcClient = jQuery("#jform_client_id").val();
			var valid = false;

			if (tcVersion && tcClient)
			{
				 var promise = tjContentService.postData('index.php?option=com_tc&task=content.checkDuplicateAndLatestVersionTC', {
						tcVersion: tcVersion,
						tcClient: tcClient
					}
				);
				promise.fail(
					function(response) {
						//alert('ajax failed');
					}
				).done(
					function(data) {
						if (data === 0) {
							// this T&C has first version
							valid = true;
						}
						else if (tcVersion <= data) {
						alert('<?php echo JText::_('COM_TC_VALIDATION_FORM_LATEST_VERSION_TC'); ?>'+ tcClient +' - '+ data +
								'<?php echo JText::_('COM_TC_VALIDATION_FORM_GREATER_VERSION_TC'); ?>');
							jQuery('#jform_version').val('');

							valid = false;
						}
					}
				);
			}
			else
			{
				valid = true;
			}

			return valid;
		}

		 var tjContentService = {
			postData: function(url, formData, params) {
				if(!params){
					params = {};
				}

				params['url']		= url;
				params['data'] 		= formData;
				params['type'] 		= typeof params['type'] != "undefined" ? params['type'] : 'POST';
				params['async'] 	= typeof params['async'] != "undefined" ? params['async'] :false;
				params['dataType'] 	= typeof params['datatype'] != "undefined" ? params['datatype'] : 'json';

				var promise = jQuery.ajax(params);
				return promise;
			}
		}
</script>

<form
	action="<?php echo JRoute::_('index.php?option=com_tc&layout=edit&tc_id=' . (int) $this->item->tc_id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="content-form" class="form-validate">

	<div class="form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'general', JText::_('COM_TC_TITLE_CONTENT', true)); ?>
		<div class="row-fluid">
			<div class="span10 form-horizontal">
				<fieldset class="adminform">
				<input type="hidden" id="tc_id" name="jform[tc_id]" value="<?php echo $this->item->tc_id; ?>" />
				<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
				<input type="hidden" name="jform[state]" value="<?php echo $this->item->state; ?>" />
				<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
				<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />
				<?php
					echo $this->form->renderField('title');
					echo $this->form->renderField('client');
					echo $this->form->renderField('version');
					echo $this->form->renderField('start_date');
					echo $this->form->renderField('global');
					echo $this->form->renderField('url_pattern');
					echo $this->form->renderField('content');
					echo $this->form->renderField('email');
				?>
				</fieldset>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'Permissions', JText::_('COM_TC_TITLE_PERMISSIONS', true)); ?>
		<div class="row-fluid">
			<div class="span10 form-horizontal">
				<fieldset class="adminform">
				<?php
					echo $this->form->renderField('groups');
					echo $this->form->renderField('is_blacklist');
					// echo $this->form->renderField('enforce_delay_days');
				?>
				</fieldset>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php echo JHtml::_('bootstrap.endTabSet'); ?>

		<input type="hidden" name="task" value=""/>
		<?php echo JHtml::_('form.token'); ?>

	</div>
</form>
