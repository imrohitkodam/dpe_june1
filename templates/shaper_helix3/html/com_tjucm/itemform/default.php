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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
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

// Define tjUcmItemForm global object (required by all Save buttons)
HTMLHelper::script('media/com_dpe/js/tjucmitemform.js');

$document = Factory::getDocument();
$document->addScript(Uri::root() . 'media/com_dpe/js/tjucmroplist.js');
$document->addScript(Uri::root() . 'media/system/js/showon-es5.js');
$document->addScript(Uri::root() . 'media/system/js/showon.js');
$document->addScript(Uri::root() . 'media/system/js/messages.min.js');

// Load admin language file
$lang = Factory::getLanguage();
$lang->load('com_tjucm', JPATH_SITE);

$jinput                    = Factory::getApplication();
$editRecordId              = $jinput->input->get("id", '', 'INT');
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

// Get Current url for notification manager widget
$extraParams = Uri::getInstance()->toString(array('query'));
$extraParams = str_replace('?', '&', $extraParams);
$input       = $jinput->input;

$currentUrl =  'index.php?option=' . $input->get('option') . '&view=' . $input->get('view') . $extraParams .'&Itemid=' . $input->get('Itemid');

$tmpl       = $input->getString('tmpl', '');

// Check template component set or not.
if (!empty($tmpl))
{
	$document->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
	$document->addStyleSheet('templates/shaper_helix3/css/legacy.css');
	$document->addStyleSheet('templates/shaper_helix3/css/template.css');
	$document->addStyleSheet('templates/shaper_helix3/css/custom.css');

	$document->addStyleSheet('templates/shaper_helix3/js/bootstrap.min.js');
	$document->addStyleSheet('templates/shaper_helix3/js/jquery.sticky.js');
	$document->addStyleSheet('templates/shaper_helix3/js/main.js');
	$document->addStyleSheet('templates/shaper_helix3/js/frontend-edit.js');
	$document->addStyleSheet('media/system/js/frontediting.js');
}
?>
<?php if (!empty($tmpl)): ?>
	<h3 class="rop-popup-header ml-20 mr-20"><?php echo Text::_('COM_TJUCM_CORE_DATA_ADD_NEW_RECORD_TITLE'); ?></h3>
<?php endif;?>
<form action="<?php echo Route::_('index.php');?>" method="post" enctype="multipart/form-data" name="adminForm" id="item-form" class="form-validate  ucm-form-styling default-layout">
	<?php
	if ($this->allow_auto_save == '1')
	{
	?>
	<div class="alert alert-info" style="display:none;" id="tjucm-auto-save-disabled-msg">
		<div class="msg">
			<div>
			<?php echo Text::_("COM_TJUCM_MSG_FOR_AUTOSAVE_FEATURE_DISABLED"); ?>
			</div>
		</div>
		<a class="close float-end " data-dismiss="alert" style='margin-top: -20px;'>×</a>
	</div>
	<?php
	}
	?>
	<div>
		<div class="row-fluid">
			<div class="span10 form-horizontal">
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
		echo $this->loadTemplate('extrafields');
		?>
		</div>
		<?php
	}

	if ($editRecordId)
	{
	?>
	<div class="clear-both alert alert-success" style="display: block;">
		
		<div class="msg">
			<?php echo Text::_("COM_TJUCM_NOTE_ON_FORM"); ?>
		</div>
		<a class="close float-end " data-dismiss="alert" style='margin-top: -20px;'>×</a>
	</div>
	<?php
	}

	// Show reverse relation in case of masterlist
	$params              = ComponentHelper::getParams('com_dpe');
	$codeDataFieldConfig = json_decode($params->get('coredatatitlefields'), true);
	$masterListClients   = array_keys($codeDataFieldConfig);

	// We may need config in future we wants to show reverse relation on other pages
	if ($this->item->cluster_id && in_array($this->client, array('com_tjucm.ropvendors')))
	{
		// Get Cluster name
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
		$clusterTable = Table::getInstance('clusters', 'ClusterTable');
		$clusterTable->load(array('id' => $this->item->cluster_id));

		$UcmTypes = array('com_tjucm.software','com_tjucm.ithardware');
		$tjUcmFrontendHelper = new TjucmHelpersTjucm;

		foreach ($UcmTypes as $UcmType)
		{
			// Get UCM Type name
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
			$ucmTable = Table::getInstance('type', 'TjucmTable');
			$ucmTable->load(array('unique_identifier' => $UcmType));

			// Get Process Addtion form Itemid
			$TypeItemId = $tjUcmFrontendHelper->getItemId('index.php?option=com_tjucm&view=items&client='.$UcmType);
			$reverseListLink = Route::_('index.php?option=com_tjucm&view=items&tmpl=component&Itemid=' . $TypeItemId.'&cluster_id=' . $this->item->cluster_id);
			$PopUpUrl = addslashes(Route::_($reverseListLink . '&reverselist=1'));
			?>
			<div class="ml-40 row-fluid">
				<a class=""
					href="javascript:void(0);"
					onclick="tjucm.itmes.openMasterlistPopups('<?php echo addslashes(Route::_($PopUpUrl));?>', this)"><?php echo Text::sprintf('COM_DPE_REVERSELIST_TITLE', $ucmTable->title, $clusterTable->name); ?>
				</a>
			</div>
		<?php
		}
		// Get UCM type details
		?>
	<?php
	}
	?>
	<div id="draft_msg" class="alert alert-success" style="display: none;">
		<a class="close" data-dismiss="alert">×</a>
		<?php echo Text::_("COM_TJUCM_MSG_ON_DRAFT_FORM"); ?>
	</div>

	<div class="form-actions buttons-mobile-view border-0 bg-none">
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

		if (isset($setnavigation) && $setnavigation == true)
		{
			?>
				<!-- <button type="button" class="btn btn-primary mt-20" id="previous_button" >
					<i class="icon-arrow-left-2"></i>
					<?php echo Text::_('COM_TJUCM_PREVIOUS_BUTTON'); ?>
				</button>
				<button type="button" class="btn btn-primary mt-20" id="next_button" >
					<?php echo Text::_('COM_TJUCM_NEXT_BUTTON'); ?>
					<i class="icon-arrow-right-2"></i>
				</button> -->
			<?php
		}

		if ($calledFrom == 'frontend')
		{
			?>
			<span class="pull-right row">
			<?php
			if (($this->allow_auto_save || $this->allow_draft_save) && $itemState)
			{
				?>
				<input type="button" class="btn btn-default px-25 mobile-space mr-5 col-xs-6" id="tjUcmSectionDraftSave"
				value="<?php echo Text::_("COM_TJUCM_SAVE_AS_DRAFT_ITEM"); ?>"
				onclick="tjUcmItemForm.saveUcmFormData();" />
				<?php
			}

			?>
			<input type="button" class="btn btn-primary mobile-space mr-5" value="<?php echo Text::_('COM_TJUCM_SAVE_ITEM'); ?>" id="tjUcmSectionFinalSave" onclick="tjUcmItemForm.saveUcmFormData();" />

			<?php if (empty($tmpl)) :?>

			<input type="button" class="btn btn-primary mobile-space mr-5" value="<?php echo Text::_('COM_TJUCM_SAVE_CLOSE_ITEM'); ?>" id="tjUcmSectionFinalSaveClose" onclick="tjUcmItemForm.saveUcmFormData();" />
			<input type="button" class="btn btn-warning mobile-space mr-5" value="<?php echo Text::_("COM_TJUCM_CANCEL_BUTTON"); ?>" onclick="Joomla.submitbutton('itemform.cancel');" />
			<?php endif; ?>

			</span>
			<div class="clearfix"></div>
			<?php
		}
		?>
	</div>
	<input type="hidden" name="layout" value="<?php echo $layout ?>"/>
	<input type="hidden" name="task" value="itemform.save"/>
	<input type="hidden" name="form_status" id="form_status" value=""/>
	<input type="hidden" name="tjucm-autosave" id="tjucm-autosave" value="<?php echo $this->allow_auto_save;?>"/>
	<input type="hidden" name="tjucm-bitrate" id="tjucm-bitrate" value="<?php echo $this->allow_bit_rate;?>"/>
	<input type="hidden" name="tjucm-bitrate_seconds" id="tjucm-bitrate_seconds" value="<?php echo $this->allow_bit_rate_seconds;?>"/>

	<input type="hidden" name="url" id="url" value="<?php echo $currentUrl;?>"/>
	<input type="hidden" name="element" id="element" value="<?php echo $this->client;?>"/>
	<input type="hidden" name="element_id" id="element_id" value="<?php echo $this->id;?>"/>
	<input type="hidden" name="cluster_id" id="cluster_id" value="<?php echo $this->item->cluster_id;?>"/>
	<input type="hidden" name="tmplPopUp" id="tmplPopUp" value="<?php echo !empty($tmpl) ? 1:0 ?>"/>

	<?php echo HTMLHelper::_('form.token'); ?>
</form>
<script>


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
});


</script>