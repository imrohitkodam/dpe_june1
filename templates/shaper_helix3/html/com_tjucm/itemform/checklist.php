<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
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
// Call js file to update the link to ticket 
HTMLHelper::script('media/com_dpe/js/logsticket.js');

// Call to utilize the tab structure in URL
HTMLHelper::script('media/com_dpe/js/dpe_ucm_tab.js');

$fieldsets_counter = 0;
// Load admin language file
$lang = Factory::getLanguage();
$lang->load('com_tjucm', JPATH_SITE);
$jinput                    = Factory::getApplication();
$app  = Factory::getApplication();
$menu = $app->getMenu();
$defaultMenuId = $menu->getActive()->query['id'];
$detailMenu = $menu->getItems('link', 'index.php?option=com_dpe&view=dashboard', true);

$editRecordId              = ($jinput->input->get("id", '', 'INT') != $defaultMenuId)?$jinput->input->get("id", '', 'INT'):'';
$baseUrl                   = $jinput->input->server->get('REQUEST_URI', '', 'STRING');
$calledFrom                = (strpos($baseUrl, 'administrator')) ? 'backend' : 'frontend';
$layout                    = ($calledFrom == 'frontend') ? 'default' : 'edit';
$fieldsets_counter_deafult = 0;
$setnavigation             = false;

if ($this->item->id)
{
	$itemState = ($this->item->draft && ($this->allow_auto_save || $this->allow_draft_save)) ? 1 : 0;
}
else
{
	$itemState = ($this->allow_auto_save || $this->allow_draft_save) ? 1 : 0;
}

// DPE - Hack - Start - Add new confirmation mesage
Text::script('COM_TJUCM_ITEMFORM_SUBMIT_DPE_CONFIRMATION');
Text::script('COM_TJUCM_SAVE_PLEASE_WAIT', true);

$doc = Factory::getDocument();
$doctype = $doc->getType();
$clusterId = $jinput->input->get("cluster_id", '', 'INT');
$cluster = ClusterCluster::getInstance($clusterId);

// DPE - Hack - End

Factory::getDocument()->addScriptDeclaration('
	jQuery(function() {
		jQuery("#item-form").areYouSure();
		});

		jQuery(document).ready(function ()

		{
			jQuery("#item-form .nav-tabs li a").first().click();
			});

			Joomla.submitbutton = function (task)
			{
				if (task == "itemform.cancel")
				{
					Joomla.submitform(task, document.getElementById("item-form"));
				}
				else
				{
					if (task != "itemform.cancel" && document.formvalidator.isValid(document.id("item-form")))
					{
						Joomla.submitform(task, document.getElementById("item-form"));
					}
					else
					{
						alert("' . $this->escape(Text::_("JGLOBAL_VALIDATION_FORM_FAILED")) . '");
					}
				}
			};
			');

// DPE - Hack - Start - To display Cluster title with school name
if ($doctype != 'json')
{
	?>
	<div class="row checklist_outer pt-10 print-view">
		<div class="col-xs-6 col-sm-9 checklist-title font-bold mb-10">
			<?php echo Text::_('COM_DPE_CHECKLIST_TITLE'); ?>
		</div>
		<div class="col-xs-6 col-sm-3">
			<div class="btn-group pull-right">
				<?php
				echo $cluster->name;
				?>
			</div>
		</div>
	</div>
	<?php
}?>
<div class="today-date visible-print print-view-date">
	<?php echo Text::_("COM_DPE_PRINT_DATE"); ?>:
	<span class="font-bold">
		<?php echo HTMLHelper::date('now', Text::_('COM_DPE_DATE_ONLY_FORMAT'), false);?>
	</span>
</div>

<?php if ($doctype == 'json')
{
	?>
	<button type="button" class="btn btn-primary pull-right print-hide-btn print-ucm-checklist"><i class="fa fa-print"></i> <?php echo Text::_("COM_DPE_PRINT"); ?></button>
	<?php
}
else
{
	?>
	<button type="button" class="btn btn-primary pull-right print-hide-btn " onclick="print_profile()"><i class="fa fa-print"></i> <?php echo Text::_("COM_DPE_PRINT"); ?></button>
	<?php
}
$tjUcmModelType = BaseDatabaseModel::getInstance('Type', 'TjucmModel');
$typeId = $tjUcmModelType->getTypeId($this->client);
$TypeData = $tjUcmModelType->getItem($typeId);
?>

<h3 class="font-bold mt-10 mb-20 print-m-0">
	<?php
	if ($doctype != 'json')
	{
		?>
		<a class="checklistBack" href="<?php echo Route::_('index.php?option=com_dpe&view=dashboard&Itemid='.$detailMenu->id, false);?>"><i class="fa">&#xf104;</i> &nbsp;</a><?php echo $TypeData->title; ?>
		<?php
	}
	else
	{
		echo $TypeData->title;
	}
	?>
</h3>
<!-- DPE - Hack - End -->

<form action="<?php echo Route::_('index.php');?>" method="post" enctype="multipart/form-data" name="adminForm" id="item-form" class="form-validate print-view-tab ucm-form-styling">
	<!-- DPE - Hack - Removed extra messages and added design ( Div's) -->

	<div>
		<div class="span10 form-horizontal">

			<div class="row-fluid">

				<fieldset>
					<input type="hidden" name="jform[id]" id="recordId" value="<?php echo $editRecordId; ?>" />
					<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
					<input type="hidden" name="jform[state]" value="<?php echo $this->item->state;?>" />
					<input type="hidden" id="ucm-client" name="jform[client]" value="<?php echo $this->client;?>" />
					<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
					<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />
					<input type="hidden" name="itemState" id="itemState" value="<?php echo $itemState; ?>"/>
					<?php echo $this->form->renderField('created_by'); ?>
					<?php echo $this->form->renderField('created_date'); ?>
					<?php echo $this->form->renderField('modified_by'); ?>
					<?php echo $this->form->renderField('modified_date'); ?>
				</fieldset>
			</div>
		</div>
		<?php
		if ($this->form_extra)
		{
			?>
			<div class="form-horizontal">
				<?php
		// Code to display the form
				echo $this->loadTemplate('extrafieldschecklist');
				?>
			</div>
			<?php
		}
		?>
		<!-- DPE - Hack - Removed extra messages and added design ( Div's) -->

		<div id="draft_msg" class="alert alert-success" style="display: none;">
			<a class="close" data-dismiss="alert">×</a>
			<?php echo Text::_("COM_TJUCM_MSG_ON_DRAFT_FORM"); ?>
		</div>
		<div class="clearfix"></div>
		<div class="hidden-print mt-20 float-end">
			<?php
		// Show next previous buttons only when there are mulitple tabs/groups present under that field type
			$fieldArray = $this->form_extra;

			foreach ($fieldArray->getFieldsets() as $fieldName => $fieldset)
			{
				if (count($fieldArray->getFieldsets()) > 1)
				{
					$setnavigation = true;
				}
			}

			if (isset($setnavigation) && $setnavigation)
			{
				?>
				<span class="pull-left mb-5">
				<!-- <button type="button" class="btn btn-primary" id="previous_button" >
					<i class="icon-arrow-left-2"></i>
					<?php echo Text::_('COM_TJUCM_PREVIOUS_BUTTON'); ?>
				</button>
				<button type="button" class="btn btn-primary" id="next_button" >
					<?php echo Text::_('COM_TJUCM_NEXT_BUTTON'); ?>
					<i class="icon-arrow-right-2"></i>
				</button> -->
			</span>
		<?php } ?>
		<span class="text-right d-block">
			<?php
			if (($this->allow_auto_save || $this->allow_draft_save) && $itemState)
			{
				?>
				<input type="button" class="btn btn-default px-25" id="tjUcmSectionDraftSave"
				value="<?php echo Text::_("COM_TJUCM_SAVE_AS_DRAFT_ITEM"); ?>"
				onclick="tjUcmItemForm.saveUcmFormData();" />
				<?php
			}

			?>
			<input type="button" class="btn btn-primary mb-5" value="<?php echo Text::_('COM_TJUCM_SAVE_ITEM'); ?>" id="tjUcmSectionFinalSave" onclick="tjUcmItemForm.saveUcmFormData();" />

			<input type="button" class="btn btn-primary mb-5" value="<?php echo Text::_('COM_TJUCM_SAVE_CLOSE_ITEM'); ?>" id="tjUcmSectionFinalSaveClose" onclick="tjUcmItemForm.saveUcmFormData();" />

			<!--  DPE - Hack - Start - To redirect on checklist view -->
			<a class="btn btn-warning ml-10 mb-5" href="<?php echo Route::_('index.php?option=com_dpe&view=dashboard?cluster_id='.$clusterId, false);?>">
				<?php echo Text::_("COM_TJUCM_CANCEL_BUTTON"); ?></a>
				<!--  DPE - Hack - End-->
			</span>
		</div>
		<?php
		if ($calledFrom == 'frontend')
		{
			?>
			<div class='clear-both'>
				<div class="alert alert-info checklist-help hide mt-10 alert-dismissible" role="alert">
					<span class="content fs-12"></span>
					<button type="button" class="checklist-close-alert close" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
			</div>
		<?php } ?>
		<input type="hidden" name="layout" value="<?php echo $layout ?>"/>
		<input type="hidden" name="task" value="itemform.save"/>
		<input type="hidden" name="form_status" id="form_status" value=""/>
		<input type="hidden" name="tjucm-autosave" id="tjucm-autosave" value="<?php echo $this->allow_auto_save;?>"/>
		<input type="hidden" name="tjucm-bitrate" id="tjucm-bitrate" value="<?php echo $this->allow_bit_rate;?>"/>
		<input type="hidden" name="tjucm-bitrate_seconds" id="tjucm-bitrate_seconds" value="<?php echo $this->allow_bit_rate_seconds;?>"/>
		<input type="hidden" id="documentType" name="documentType" value="<?php echo  $doctype;?>"/>

		<?php echo HTMLHelper::_('form.token'); ?>

		<!--  DPE - Hack - Start - To show custom confirmation message -->
		<input type="hidden" name="custForm" id="custForm" value="1"/>
		<!--  DPE - Hack - End-->
	</form>

	<script>

		function print_profile()
		{
			jQuery('.checklisttextarea').css('border','none');
			jQuery('#checklistTodoDate').css('border','none');
			jQuery('.checklisttodo').css('display','none');
			jQuery('.fa-calendar').css('display','none');
			jQuery('.todocommon').css('display','none');
			jQuery('.notificationwidget').css('display','none !impotant');
			jQuery('.notificationlistwidget').css('display','none !impotant');
			jQuery('.timelogwidget').css('display','none !impotant');
			jQuery('.fa-history').css('display','none !impotant');
			jQuery('.fa-edit').css('display','none !impotant');
			jQuery('.fa-list').css('display','none !impotant');
			jQuery('.checklisttextarea').each(function() {
				var element = jQuery(this);
				element.css('overflow', 'hidden'); 
        element.height(0); // Reset height
        element.height(element[0].scrollHeight); // Set to content's scroll height
    });

			window.print();
		}

		jQuery(document).ready(function($) {
    // Function to open the calendar
			$('input.form-control.calendar-textfield-class').on('click', function(event) {
        event.stopPropagation(); // Prevent the click event from propagating to the document

        // Close all other open calendars
        $('.js-calendar').removeClass('open').attr('hidden', 'hidden');
        
        // Find the parent field-calendar container
        var fieldCalendar = $(this).closest('.field-calendar');
        
        // Show the calendar container within this field-calendar
        var calendarContainer = fieldCalendar.find('.js-calendar');
        calendarContainer.addClass('open').removeAttr('hidden');
    });

    // Function to close the calendar when clicking outside
			$(document).on('click', function(event) {
        // Check if the click is outside the input fields and the calendars
				if (!$(event.target).closest('.field-calendar').length) {
					$('.js-calendar').removeClass('open').attr('hidden', 'hidden');
				}
			});

    // Prevent closing the calendar when clicking inside it
			$(document).on('click', '.js-calendar', function(event) {
        event.stopPropagation(); // Prevent the click event from propagating to the document
    });
			var currentUrl = window.parent.location.href;
			if(currentUrl.includes("best-practice-library/best-practice/") || currentUrl.includes('com_sppagebuilder'))
			{
				jQuery('.print-ucm-checklist').hide();
			}
		});


	</script>
