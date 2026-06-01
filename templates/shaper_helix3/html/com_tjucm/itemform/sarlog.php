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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;


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

JText::script('COM_TJFIELDS_FILE_ERROR_MAX_SIZE');

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
$user = Factory::getUser();
$params     			    = ComponentHelper::getParams('com_multiagency');
$orgAdmin           		= (int) $params->get('multiagency_school_admin_group', '0', 'INT');
$orgAdmin                   = in_array($orgAdmin, $user->groups);


// Js For comment section of Jlike
HTMLHelper::script('media/com_jlike/vendors/jquery-loading-overlay/loadingoverlay.min.js');
$tjStrapperPath = JPATH_SITE . '/media/techjoomla_strapper/tjstrapper.php';

if (File::exists($tjStrapperPath))
{
	require_once $tjStrapperPath;
	TjStrapper::loadTjAssets('com_jlike');
}
$document = Factory::getDocument();

$document->addScript(Uri::root(true) . '/components/com_jlike/assets/scripts/jlike.js');
$document->addScript(Uri::root(true) . '/components/com_jlike/assets/scripts/jlike_comments.js');
//End


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

	// DPE - Hack - Start - To add Jlike plugin
if (!empty($this->id) && ($this->client == 'com_tjucm.sarlog' || $this->client == 'com_tjucm.FOIlog'))
{

	PluginHelper::importPlugin('content', 'jlike_dpe');
	$this->event = new stdClass;
	$results = Factory::getApplication()->triggerEvent('onContentAfterDisplay', array('com_tjucm.itemform', &$this->item , &$this->params));
	$this->event->afterDisplayContent = trim(implode("\n", $results));
}
	// DPE - Hack - End

// Get Current url for notification manager widget
$extraParams = Uri::getInstance()->toString(array('query'));
$extraParams = str_replace('?', '&', $extraParams);
$input       = $jinput->input;

$currentUrl =  'index.php?option=' . $input->get('option') . '&view=' . $input->get('view') . $extraParams .'&Itemid=' . $input->get('Itemid');
?>
<form action="<?php echo Route::_('index.php');?>" method="post" enctype="multipart/form-data" name="adminForm" id="item-form" class="sar-breach-create-page form-validate ucm-form-styling">
	<?php


if (!$this->item->draft && $this->client == 'com_tjucm.dpialite')
{?>
	<div class="alert alert-info" id="tjucm-dpialite_assign-msg">
			<div class="msg float-start">
				<div>
					<?php echo Text::_("COM_TJUCM_MSG_FOR_DPIALITE_FEATURE"); ?>
				</div>
			</div>
			<a href="javascript:void();" class="close float-end" data-dismiss="alert">×</a>
			<div class="clearfix"></div>
		</div>
<?php }

	if ($this->allow_auto_save == '1')
	{
		?>
		<div class="alert alert-info" style="display:none;" id="tjucm-auto-save-disabled-msg">
			<div class="msg float-start">
				<div>
					<?php echo Text::_("COM_TJUCM_MSG_FOR_AUTOSAVE_FEATURE_DISABLED"); ?>
				</div>
			</div>
			<a href="javascript:void();" class="close float-end" data-dismiss="alert">×</a>
			<div class="clearfix"></div>
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
		//  DPE - Hack - Code to display the form
				echo $this->loadTemplate('extrafieldslog');
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
			if($user->authorise('core.manageall', 'com_cluster') && in_array($this->client, ['com_tjucm.breachlog', 'com_tjucm.FOIlog', 'com_tjucm.sarlog'])) {?>

				<div class="control-group">
				<div class="control-label">
					<label for="hours">Time</label>
				</div>
				<div class="controls">
					<input type="number" name="jform[hours]" id="hours" min="0" max="23" value="0" style="width:62px !important; display:inline-block;" />
					:
					<input type="number" name="jform[minutes]" id="minutes" min="0" max="59" step="5" value="5" style="width:62px !important; display:inline-block;" />
				</div>
			</div>
		<?php	}
			if ($calledFrom == 'frontend')
			{
				?>
				<div class="row m-sm-0">
					<div class="row m-0">
						<div class="col-sm-9 col-12 text-end pe-sm-3">
							<?php
							if (($this->allow_auto_save || $this->allow_draft_save) && $itemState)
							{

								?>

								<?php if(((!TjucmAccess::canView($ucmTypeId, $this->id, $user->id)) && (!$user->authorise('core.manageall', 'com_cluster')) && !$orgAdmin) && ($this->client == 'com_tjucm.breachlog')) {?>

								<?php }else{?>

									<input type="button" class="btn btn-default px-25 mobile-space" id="tjUcmSectionDraftSave"
								value="<?php echo Text::_("COM_TJUCM_SAVE_AS_DRAFT_ITEM"); ?>"
								onclick="tjUcmItemForm.saveUcmFormData();" />

									<?php
								}
							}

							?>
							<input type="button" class="btn btn-primary px-25 mobile-space" value="<?php echo Text::_('COM_TJUCM_SAVE_ITEM'); ?>" id="tjUcmSectionFinalSave" onclick="editorcontent();tjUcmItemForm.saveUcmFormData();" />
							
							<?php if(((!TjucmAccess::canView($ucmTypeId, $this->id, $user->id)) && (!$user->authorise('core.manageall', 'com_cluster')) && !$orgAdmin) && ($this->client == 'com_tjucm.breachlog')) {?>
								
								<input type="button" class="btn btn-warning mobile-space" value="<?php echo Text::_("COM_TJUCM_CANCEL_BUTTON"); ?>" onclick="toDashboard();" />

							<?php } 
							else
								{?>

									<input type="button" class="btn btn-primary mobile-space" value="<?php echo Text::_("COM_TJUCM_SAVE_CLOSE_ITEM"); ?>" id="tjUcmSectionFinalSaveClose" onclick="tjUcmItemForm.saveUcmFormData();" />

									<input type="button" class="btn btn-warning mobile-space" value="<?php echo Text::_("COM_TJUCM_CANCEL_BUTTON"); ?>" onclick="Joomla.submitbutton('itemform.cancel');" />

									
								<?php }
								?>


							</div>	
						</div>	
					</div>
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
			<input type="hidden" name="isDraft" id="isDraft" value="1"/>

			<?php echo HTMLHelper::_('form.token'); ?>

			<?php
	

	// DPE - Hack - Start - To show comments

			if (($this->client == 'com_tjucm.sarlog' || $this->client == 'com_tjucm.FOIlog'))
			{
				?>
				<div class="col-xs-12 logcomment">
					<?php echo ($this->event->afterDisplayContent)?$this->event->afterDisplayContent:'';?>
				</div>
				<?php
			}
			?>
			<input type="hidden" name="custForm" id="custForm" value="1"/>
			<!--  DPE - Hack - End  - Show comments-->
		</form>
		<script>
			jQuery(document).ready(function() {

				jQuery(document).on("mouseenter", ".jlike_position_relative", function(e) {
					jQuery(this).find(".jlike_edit_dropdown").show();
					jQuery(this).find(".jlike_edit_dropdown").css('margin-left', '-70px !important');

					e.stopPropagation(); 

				});

				jQuery(document).on("mouseleave", ".jlike_position_relative", function() {
					jQuery(".jlike_position_relative ul").hide();
				});

var currentUrl = window.parent.jQuery('#ticketUrl').val()

  // Check if the URL contains "view-ticket"
  if ((currentUrl != undefined) &&(currentUrl.indexOf("view-ticket") !== -1)) {


    // Code to execute after the DOM has fully loaded
    jQuery('#sp-top-bar').css('display', 'none');
    jQuery('#sp-header').css('display','none');
    jQuery('.ticketbtnclass').css('display','none');
    jQuery('#sp-bottom').css('display','none');
    jQuery('.timelogwidget').remove();
    jQuery('#tjUcmSectionFinalSaveClose').css('display','none');
    window.parent.jQuery('#sbox-btn-close').css('display','none');
    jQuery('.btn-warning').remove();
    jQuery('.breadcrumb').css('display','none'); 
       

     var cancleBtn = jQuery('<input type="button" id="canclebtn" class="btn btn-warning mobile-space" value="<?php echo Text::_("COM_TJUCM_CANCEL_BUTTON"); ?>" onclick="closePopup()" />');
         cancleBtn.insertAfter('#tjUcmSectionFinalSave');

    jQuery('#canclebtn').css('margin-left', '3px');

    setTimeout(function(){

    	jQuery('*').on('change',function(){	

    		if(jQuery('#recordId').val())
    		{
    			window.parent.$('#logid').val(jQuery('#recordId').val());	
    		}
    	})
    	jQuery('*').on('click',function(){	

    		if(jQuery('#recordId').val())
    		{
    			window.parent.$('#logid').val(jQuery('#recordId').val());	
    		}
    	})
    },1000);
 }

});
</script>
<script type="text/javascript">
	function toDashboard(){
		window.location.href = '<?php echo Uri::base(); ?>';
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
});

</script>

<?php if($user->authorise('core.manageall', 'com_cluster') && 
		in_array($this->client, ['com_tjucm.breachlog', 'com_tjucm.FOIlog', 'com_tjucm.sarlog'])) {?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const hoursField = document.getElementById("hours");
    const minutesField = document.getElementById("minutes");

    if (!hoursField || !minutesField) return;

    let startTime = Date.now();
    let lastElapsedMinutes = 0;

    setInterval(function () {
        let elapsedMs = Date.now() - startTime;
        let elapsedMinutes = Math.floor(elapsedMs / 60000);

        // Only start counting after 5 min
        if (elapsedMinutes >= 5 && elapsedMinutes > lastElapsedMinutes) {
            lastElapsedMinutes = elapsedMinutes;

            // Calculate how many minutes to add
            let extraMinutes = elapsedMinutes - 5; 

            let baseMinutes = 5; // your starting point
            let totalMinutes = baseMinutes + extraMinutes;

            let hours = Math.floor(totalMinutes / 60);
            let minutes = totalMinutes % 60;

            hoursField.value = hours;
            minutesField.value = minutes;
        }
    }, 1000); // check every second
});
</script>
<?php } ?>