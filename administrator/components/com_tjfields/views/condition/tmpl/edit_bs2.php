<?php
/**
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2023 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die();
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
?>

<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'condition.cancel')
		{
			Joomla.submitform(task, document.getElementById('condition-form'));
		}
		else
		{
			if (task != 'condition.cancel' && document.formvalidator.isValid(document.getElementById('condition-form')))
			{
				Joomla.submitform(task, document.getElementById('condition-form'));
			}
			else
			{
				alert('<?php echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
			}
		}
	}
</script>

<div class="<?php echo TJFIELDS_WRAPPER_CLASS;?> tj-condition">
	<form action="<?php echo Route::_('index.php?option=com_tjfields&layout=edit&id=' . (int) $this->item->id . '&client=' . $this->input->get('client', '', 'STRING')); ?>"
		  method="post" enctype="multipart/form-data" name="adminForm" id="condition-form" class="form-validate">

		<div class="form-horizontal">
			<div class="row-fluid">
				<div class="span12 form-horizontal">
					<fieldset class="adminform">
						
						<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
						<input type="hidden" name="jform[state]" value="<?php echo $this->item->state; ?>" />
						
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('show'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('show'); ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('field_to_show'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('field_to_show'); ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('condition_match'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('condition_match'); ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('condition'); ?></div>
							<div class="controls condition"><?php echo $this->form->getInput('condition'); ?></div>
						</div>

					</fieldset>
				</div>
			</div>

			<input type="hidden" name="task" value="" />
			
			<?php echo HTMLHelper::_('form.token'); ?>

		</div>
	</form>
</div>
<script>
	jQuery(document).ready(function(){
		var conditionCount = jQuery('.subform-repeatable-group').length;

		for (i = 0; i < conditionCount; i++)
		{
			jQuery(document).on('change', '#jform_condition__condition'+i+'__field_on_show', function(){
				var fieldId = jQuery(this).val();
				
				jQuery.ajax({
					type:'POST',
					url: Joomla.getOptions('system.paths').base + '/index.php?option=com_tjfields&task=condition.getFieldsOptions',
					data:{fieldId:fieldId},
					success : function(resp){
						var response = JSON.parse(resp);						
						jQuery('#jform_condition__condition'+(i-1)+'__option').empty().append(jQuery("<option></option>").attr("value", '').text(Joomla.Text._('OSM_SELECT_TOWN')));
						jQuery.each(response.data, function(key, value) { 
							var op = '<option value=\"' + value.value + '\">' + value.text + '</option>';
							jQuery('#jform_condition__condition'+(i-1)+'__option').append(op);
						});

						jQuery('#jform_condition__condition'+(i-1)+'__option').trigger("liszt:updated"); 
						jQuery('#jform_condition__condition'+(i-1)+'__option').chosen();
					}
				});
			})
		}
	});

	jQuery(document).on('subform-row-add', function(event, row){
		var conditionCount = jQuery('.subform-repeatable-group').length;

		for (i = 0; i < conditionCount; i++)
		{
			jQuery(document).on('change', '#jform_condition__condition'+i+'__field_on_show', function(){
				var fieldId = jQuery(this).val();
				
				jQuery.ajax({
					type:'POST',
					url: Joomla.getOptions('system.paths').base + '/index.php?option=com_tjfields&task=condition.getFieldsOptions',
					data:{fieldId:fieldId},
					success : function(resp){
						var response = JSON.parse(resp);						
						jQuery('#jform_condition__condition'+(i-1)+'__option').empty().append(jQuery("<option></option>").attr("value", '').text(Joomla.Text._('OSM_SELECT_TOWN')));
						jQuery.each(response.data, function(key, value) { 
							var op = '<option value=\"' + value.value + '\">' + value.text + '</option>';
							jQuery('#jform_condition__condition'+(i-1)+'__option').append(op);
						});

						jQuery('#jform_condition__condition'+(i-1)+'__option').trigger("liszt:updated"); 
						jQuery('#jform_condition__condition'+(i-1)+'__option').chosen();
					}
				});
			})
		}
	});
</script>
