<?php
/**
 * @package     DPE
 * @subpackage  mod_ucm_checklist
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('jquery.token');

/*
 * Script to show alert box if form changes are made and user is closing/refreshing/navigating the tab
 * without saving the content
 */
HTMLHelper::script('media/com_tjucm/js/vendor/jquery/jquery.are-you-sure.js');

/*
 * Script to show alert box if form changes are made and user is closing/refreshing/navigating the tab
 * without saving the content on iphone|ipad|ipod|opera
 */
HTMLHelper::script('media/com_tjucm/js/vendor/shim/ays-beforeunload-shim.js');
HTMLHelper::script('administrator/components/com_tjfields/assets/js/tjfields.js');
HTMLHelper::script('modules/mod_ucm_checklist/asset/js/ucm-checklist.min.js');
HTMLHelper::_('script', 'media/system/js/fields/calendar-locales/date/gregorian/date-helper.min.js');
HTMLHelper::script('media/com_tjucm/js/com_tjucm.min.js');
HTMLHelper::script('media/com_tjucm/js/core/class.min.js');
HTMLHelper::script('media/com_tjucm/js/core/base.min.js');
HTMLHelper::script('media/com_tjucm/js/services/item.min.js');
HTMLHelper::script('media/com_tjucm/js/vendor/jquery/jquery.form.js');
HTMLHelper::script('media/com_tjucm/js/ui/itemform.min.js');
HTMLHelper::StyleSheet('media/com_tjucm/css/tjucm.css');
HTMLHelper::script('media/com_dpe/js/tjreportaddtodo.js');
Text::script('COM_TJUCM_ITEMFORM_SUBMIT_DPE_CONFIRMATION');

// Load admin language file
$lang = Factory::getLanguage();
$lang->load('com_tjucm', JPATH_SITE);



if (!empty($usersClusters))
{
	?>
	<div class="checklist_outer p-5">
		<div class="btn-group pull-right hidden-xs">
			<?php
			echo HTMLHelper::_('select.genericlist', $usersClusters, "filter[cluster_id]", 'class="input-medium ucm-checklist-cluster" size="1"', "value", "text");
			?>
		</div>
	</div>
	<div class="checklisttododiv">
		<label style="flex: 4;color:red;">
			<?php echo Text::_('COM_DPE_DATE_OF_NEXT_REVIEW') . ' ' ;?> </label>
			<?php

			echo HTMLHelper::_('calendar', ($dueDate)?$dueDate:'', 'checklistTodoDate', 'checklistTodoDate', '%d-%m-%Y %H:%M', ['style' => 'width:100%;']); ?>

			<a href='#' onclick='if (checklistTodo()) { openPopup(Joomla.getOptions("system.paths").rootFull+ "index.php?option=com_jlike&tmpl=component&task=recommendationform.edit&layout=edit&source=checklist"); } return false;' style="margin-left: 10px;">

				<i class="fa fa-calendar checklisttodo" title="<?php echo Text::_('PLG_SYSTEM_ADDTODO_BTN')?>"></i>
			</a>

		</div>
	<?php }?>
	<div class="ucm-module-checklist-container <?php echo $moduleclassSfx; ?>" data-ucm-type="<?php echo $params->get('ucm_type');?>">
	</div>
	<script type="text/javascript">
		jQuery(document).ready(function () {
			jQuery('#checklistTodoDate_btn').css('background','none');
			jQuery('.checklisttododiv').css('width', '35%');
			setTimeout(function(){
				getDueDate();
				
			},3000);
			jQuery('#filtercluster_id').on('change', function () {
				jQuery('#checklistTodoDate').val('');
				setTimeout(function(){
					getDueDate();
				},3000)
			});
		});

		function getDueDate()
		{
			var recordId = jQuery('#recordId').val();
			
			if(recordId == '')
			{
				return false;
			}

			jQuery.ajax({type:"POST",
				url:Joomla.getOptions("system.paths").rootFull+ "index.php?option=com_dpe&task=tjucm.getChecklistContentId&format=json",

				data:{recordId:recordId},
				dataType:"json",
				success:function(t){

					let dateTime = t.data.duedate;
					let dateOnly = dateTime.split(' ')[0];
					jQuery('#checklistTodoDate').val(dateOnly);

				}
			})
		}

		function checklistTodo(){

			var currentUrl = window.parent.location.href;
			var recordId = jQuery('#recordId').val();


			if(!recordId)
			{
				alert("Please submit the form first");
				return false;
			}else
			{
				return true;
			}
		}

	</script>