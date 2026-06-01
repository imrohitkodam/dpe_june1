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
use Joomla\CMS\Layout\FileLayout;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

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
// For a frontend view:
$wa = \Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('webcomponent.field-subform');

JHtml::_('formbehavior.chosen', 'select');
$wa = \Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('chosen');

$wa->registerAndUseScript('field.calendar', 'media/system/js/fields/calendar.min.js', [], [], ['core']);
$wa->registerAndUseStyle('field.calendar', 'media/system/css/fields/calendar.min.css');
/*
* Script to show alert box if form changes are made and user is closing/refreshing/navigating the tab
* without saving the content on iphone|ipad|ipod|opera
*/
HTMLHelper::script('media/com_tjucm/js/vendor/shim/ays-beforeunload-shim.js');

HTMLHelper::script('administrator/components/com_tjfields/assets/js/tjfields.js');

// Load DPE sepcific JS
HTMLHelper::script('media/com_dpe/js/dperopform.js');
HTMLHelper::script('media/com_dpe/js/dpefeedbacksubform.js');

// Call to utilize the tab structure in URL
HTMLHelper::script('media/com_dpe/js/dpe_ucm_tab.js');

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

// DPE - Hack - Start - Code check cluster Id of URL with saved cluster_id both are equal in edit mode
if ($this->id && !$this->copyRecId && $this->client == 'com_tjucm.rop')
{
	$clusterId = $jinput->input->getInt("cluster_id", 0);

	if ($clusterId != $this->item->cluster_id)
	{
		$jinput->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
		$jinput->setHeader('status', 403, true);

		return;
	}
}
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
?>
<style>
.dataflow-success {
	color: #3c763d;
    background-color: #dff0d8;
    border-color: #d6e9c6;
}
</style>
<form action="<?php echo Route::_('index.php');?>" method="post" enctype="multipart/form-data" name="adminForm" id="item-form" class="form-validate custom-step-wizard ucm-form-styling rop-form-design">
	<?php

	if ($this->allow_auto_save == '1' && $this->item->draft == 1 && $this->item->state == 0)
	{
	?>
	<div class="alert alert-info" style="display:none;" id="tjucm-auto-save-disabled-msg">
		<a class="close" data-dismiss="alert">×</a>
		<div class="msg">
			<div>
			<?php echo Text::_("COM_TJUCM_MSG_FOR_AUTOSAVE_FEATURE_DISABLED"); ?>
			</div>
		</div>
	</div>
	<?php
	}
	?>
	<div>
		<div class="row-fluid">
			<div class="form-horizontal">
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
		$xmlFileName = explode(".", $this->form_extra->getName());
		$this->formXml = simplexml_load_file(JPATH_SITE . "/administrator/components/com_tjucm/models/forms/" . $xmlFileName[1] . ".xml");

		$ropdataflowdragdroplayout = new FileLayout('detail.ropdataflowdragdrop', JPATH_ROOT . '/components/com_tjucm');

		$count = 0;
		$xmlFieldSets = array();

		foreach ($this->formXml as $k => $xmlFieldSet)
		{
			$xmlFieldSets[$count] = $xmlFieldSet;
			$count++;
		}

		?>
		<div class="form-horizontal mt-30">
			<div id="data-flow-relation" class="data-flow-relation">

			<?php

			try
			{
				$html = $ropdataflowdragdroplayout->render(array('xmlFormObject' => $xmlFieldSets, 'formObject' => $this->form_extra, 'itemData' => $this->item));
			}
			catch (Exception $e)
			{
				echo '<div class="row-fluid">' . Text::_('COM_TJUCM_DATA_FLOW_INVALID_TREE_MESSAGE') . '</div>';
			}


			If (empty($html))
			{
				echo Text::_('COM_TJUCM_DATA_FLOW_EMPTY_TREE');
			}
			else
			{
				?>
				<div class="row-fluid">
					<h4 class="font-600"><?php echo Text::_('COM_TJUCM_DATA_FLOW_TREE_TITLE'); ?></h4>
				</div>
				<div id="DragDropHTMLCover">
					<?php echo $html; ?>
				</div>
				<?php
			}
			 ?>
				<div id="SuccessMsg" class="dataflow-success hide">
					 <?php echo Text::_('COM_TJUCM_DATA_FLOW_RELATION_SUCCESS_MSG'); ?>
				</div>

			</div>
		<?php
		// Code to display the form
		 echo $this->loadTemplate('extrafieldsrop');
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

	<input type="hidden" name="layout" value="<?php echo $layout ?>"/>
	<input type="hidden" name="task" value="itemform.save"/>
	<input type="hidden" name="form_status" id="form_status" value=""/>
	<input type="hidden" name="tjucm-autosave" id="tjucm-autosave" value="<?php echo $this->allow_auto_save;?>"/>
	<input type="hidden" name="tjucm-bitrate" id="tjucm-bitrate" value="<?php echo $this->allow_bit_rate;?>"/>
	<input type="hidden" name="tjucm-bitrate_seconds" id="tjucm-bitrate_seconds" value="<?php echo $this->allow_bit_rate_seconds;?>"/>
	<input type="hidden" name="isROP" id="isROPForm" value="1"/>

	<?php echo HTMLHelper::_('form.token'); ?>
</form>
<script>


function heightbeforescroll(){
	var windowheight = jQuery(window).height();

	var headertop = jQuery("#sp-top-bar").outerHeight();
	var header = jQuery("#sp-header").outerHeight();
	var breadcrumb= jQuery("#sp-page-title").outerHeight();
	var topheight = headertop + header + breadcrumb;
	jQuery(".data-flow-relation").css("top", topheight);
	jQuery(".data-flow-relation").css("margin-top","30px");
	jQuery("#tjucm_myTabTabs.nav.nav-tabs").css("top", topheight);
	jQuery("#tjucm_myTabTabs.nav.nav-tabs").css("margin-top","30px");

	var licounts= document.getElementById("tjucm_myTabTabs").childElementCount;
	var lielementhight = jQuery("#tjucm_myTabTabs li").outerHeight();
	var lihight= licounts * lielementhight;
	var buttons_height = topheight + lihight;
	jQuery(".buttons").css("top", buttons_height);
	jQuery(".buttons").css("margin-top","30px");

	var footerheight = jQuery("#sp-footer").outerHeight();
	var spbottom =  jQuery("#sp-bottom").outerHeight();
	jQuery(".header-changes #sp-bottom").css("bottom", footerheight);

	var bottomheight = footerheight + spbottom;
	var tabwidth= jQuery(".tab-pane").width();
	jQuery(".form-actions.action-btns").css("bottom", bottomheight);
	jQuery(".form-actions.action-btns").css("width", tabwidth);

}
function heightafterscroll(){
	var windowheight = jQuery(window).height();

	var headertop = jQuery("#sp-top-bar").outerHeight();
	var header = jQuery("#sp-header").outerHeight();
	var breadcrumb= jQuery("#sp-page-title").outerHeight();
	var topheight = breadcrumb- (headertop + header);
	jQuery(".data-flow-relation").css("top", header);
	jQuery(".data-flow-relation").css("margin-top","30px");
	jQuery("#tjucm_myTabTabs.nav.nav-tabs").css("top", header);
	jQuery("#tjucm_myTabTabs.nav.nav-tabs").css("margin-top","30px");

}

jQuery(document).ready(function(){

	var htmlToInsert = '<ul class="nav" id="tjucm_myTabTabs1" style="display:none;"><li><a href="#legal-Lawfulbasis" class="nav-link">Lawful Basis</a></li><li><a href="#legal-data-subjects" class="nav-link">Data Subjects</a></li><li><a href="#legal-accuracy" class="nav-link">Accuracy</a></li><li><a href="#legal-destination" class="nav-link">Destination</a></li><li><a href="#legal-retention" class="nav-link">Retention</a></li><li><a href="#legal-impact-assessment" class="nav-link">Impact Assessment</a></li><li><a href="#legal-status-attachment" class="nav-link">Status and Attachment</a></li></ul>';
	  jQuery('a[href="#legal-summary"]').after(htmlToInsert);


	var licount= document.getElementById("tjucm_myTabTabs").childElementCount;

	if (window.matchMedia("(max-width: 700px)").matches) {
		var width= document.getElementById("tjucm_myTabTabs").childElementCount;
		var liwidth= (100/width);
		jQuery("ul#tjucm_myTabTabs > li").css("width",liwidth+'%');

		var width1= document.getElementById("tjucm_myTabTabs1").childElementCount;
		var liwidth= (100/width1);
		jQuery("ul#tjucm_myTabTabs1 > li").css("width",liwidth+'%');
  	}
		//jQuery("#tjucm_myTabTabs li").css("width",liwidth+'%');
 	jQuery("#tjucm_myTabTabs li").click(function(){
 		var hrefValue = $('.nav-link.active').attr('href');
		 // if(this.id=="rop-data-flow"){
		 	 if(hrefValue=="#data-flow"){
			jQuery(".rop-form-design").addClass("rop-repeated-fild-view")
			jQuery(".data-flow-relation").css("display", "block");

			var dragdropwidth= jQuery(".data-flow-relation").width();
            //var tabcontentheight=jQuery(".tab-content").height();
			var tabwidth= jQuery(".tab-pane").width();
			jQuery(".form-actions.action-btns").css("width", tabwidth);

			jQuery(".tab-content").css("margin-left",dragdropwidth);
		}
		else{
			jQuery(".rop-form-design").removeClass("rop-repeated-fild-view")
			jQuery(".data-flow-relation").css("display", "none");
			//alert("hi");
			var tabwidth= jQuery(".tab-pane").width();
			jQuery(".form-actions.action-btns").css("width", tabwidth);
			jQuery(".tab-content").css("margin-left", "0px");
		}

  });
  jQuery('a[href="#legal-Lawfulbasis"]').click(function (e) {
    e.preventDefault();
    window.location.hash = '#legal-Lawfulbasis';

	});
	jQuery('a[href="#legal-data-subjects"]').click(function (e) {
		e.preventDefault();
		window.location.hash = '#legal-data-subjects';

	});
	jQuery('a[href="#legal-accuracy"]').click(function (e) {
		e.preventDefault();
		window.location.hash = '#legal-accuracy';

	});
	jQuery('a[href="#legal-destination"]').click(function (e) {
		e.preventDefault();
		window.location.hash = '#legal-destination';

	});
	jQuery('a[href="#legal-retention"]').click(function (e) {
		e.preventDefault();
		window.location.hash = '#legal-retention';

	});
	jQuery('a[href="#legal-impact-assessment"]').click(function (e) {
		e.preventDefault();
		window.location.hash = '#legal-impact-assessment';

	});
	jQuery('a[href="#legal-status-attachment"]').click(function (e) {
		e.preventDefault();
		window.location.hash = '#legal-status-attachment';

	});
	jQuery('a[href="#legal-file-upload"]').click(function (e) {
		e.preventDefault();
		window.location.hash = '#legal-file-upload';
	});

jQuery("#rop-legal-summary li").click(function(){
			var sptopbar = jQuery("#sp-top-bar").outerHeight();
			var spheader = jQuery("#sp-header").outerHeight();
			var spbreadcrumb = jQuery("#sp-page-title").outerHeight();
			var scrollheight = sptopbar + spheader + spbreadcrumb;
			setTimeout(function(){
			window.scrollBy(0,0-scrollheight);
			}, 500);


	});
	jQuery("#tjucm_myTabTabs li").click(function(){
		var legalValue = $('.nav-link.active').attr('href');
		 if(legalValue=="#legal-summary"){
	
	    jQuery('#tjucm_myTabTabs1').show();

		// if(this.id=="rop-legal-summary"){
			if (window.matchMedia("(max-width: 700px)").matches) {
				jQuery("ul#tjucm_myTabTabs1").css("display", "inline-block");
			}
			else{

				jQuery("ul#tjucm_myTabTabs1").css("display", "block");
				var spbottom =  jQuery("#sp-bottom").outerHeight();
				jQuery("ul#tjucm_myTabTabs1").css("margin-bottom", spbottom);

				if (!jQuery(window).scrollTop()){
					normalwindowClick();

				}

			}
		}
		else{
				jQuery("ul#tjucm_myTabTabs1").css("display", "none");
		}

  });
  heightbeforescroll();
var action_btn_form;
function actionbtn(){
	action_btn_form = jQuery(".form-actions").outerHeight();
	formHeight(action_btn_form);
}

function formHeight(action_btn_form){
	var windowheight1 = jQuery(window).height();
	var headertop_for_form1 = jQuery("#sp-top-bar").outerHeight();
	var header_for_form1 = jQuery("#sp-header").outerHeight();
	var breadcrumb_for_form1= jQuery("#sp-page-title").outerHeight();
	var footerheight_for_form1 = jQuery("#sp-footer").outerHeight();
	var spbottom_for_form1 =  jQuery("#sp-bottom").outerHeight();
	//var action_btn_form1 = jQuery(".form-actions").outerHeight();
	var totalheight_form1 = windowheight1 - (headertop_for_form1 + header_for_form1 + breadcrumb_for_form1 + footerheight_for_form1 + spbottom_for_form1 + action_btn_form);
	jQuery(".tab-content .tab-pane").css("height", totalheight_form1-30);
	jQuery(".tab-content").css("top", "0");
}
function formHeightafterscroll(action_btn_form){
	var windowheight = jQuery(window).height();
	var headertop_for_form = jQuery("#sp-top-bar").outerHeight();
	var header_for_form = jQuery("#sp-header").outerHeight();
	//var breadcrumb_for_form= jQuery("#sp-page-title").outerHeight();
	var footerheight_for_form = jQuery("#sp-footer").outerHeight();
	var spbottom_for_form =  jQuery("#sp-bottom").outerHeight();
	//var action_btn_form = jQuery(".form-actions").outerHeight();
	var totalheight_form =windowheight-(headertop_for_form + header_for_form + footerheight_for_form + spbottom_for_form + action_btn_form);
	jQuery(".tab-content .tab-pane").css("height", totalheight_form-30);
	jQuery(".tab-content").css("top", header_for_form);
}
// function parentulHeight(){


// 	var scrollheader = jQuery("#sp-header").outerHeight();
// 	var breadcrumb_height= jQuery("#sp-page-title").outerHeight();
// 	var parentulafterscroll = scrollheader + breadcrumb_height;
// 	jQuery("#tjucm_myTabTabs.nav.nav-tabs").css("top", parentulafterscroll);
// 	var licount1= document.getElementById("tjucm_myTabTabs").childElementCount;
// 	var liheight1 = jQuery("#tjucm_myTabTabs li").outerHeight();
// 	var parentUlheight1 = licount1 * liheight1;
// 	var childUltopheight1 =  parentulafterscroll + parentUlheight1;
// 	jQuery("#tjucm_myTabTabs1.nav").css("top", childUltopheight1);
// 	jQuery("#tjucm_myTabTabs1.nav").css("margin-top","15px");

// }
function ulHeight() {
					var liheight = jQuery("#tjucm_myTabTabs li").outerHeight();
					var headertop1 = jQuery("#sp-top-bar").outerHeight();
					var header1 = jQuery("#sp-header").outerHeight();
					//var breadcrumb1 = jQuery("#sp-page-title").outerHeight();
					var parentUlheight = licount * liheight;
					var childUltopheight = headertop1 + header1 + parentUlheight;
					jQuery("#tjucm_myTabTabs1.nav").css("top", childUltopheight);
					jQuery("#tjucm_myTabTabs1.nav").css("margin-top","15px");

}
function normalwindowClick(){
				var liheight = jQuery("#tjucm_myTabTabs li").outerHeight();
				var headertop1 = jQuery("#sp-top-bar").outerHeight();
				var header1 = jQuery("#sp-header").outerHeight();
				var breadcrumb1 = jQuery("#sp-page-title").outerHeight();
				var parentUlheight = licount * liheight;
				var childUltopheight = headertop1 + header1 + breadcrumb1 + parentUlheight;
				jQuery("#tjucm_myTabTabs1.nav").css("top", childUltopheight);

}

  setTimeout(actionbtn,200);

    jQuery(window).scroll(function () {

			if (jQuery(window).scrollTop()>10){
				//setTimeout(childulheight,1000);
		}
		// For form height
		if (jQuery(window).scrollTop()){
			formHeightafterscroll(action_btn_form);
			heightafterscroll();

		}
		else{
			formHeight(action_btn_form);
			heightbeforescroll();
			var	myTimeout = setTimeout(parentulHeight, 90);

		}

		// for Navigation height
		if(( jQuery('#tjucm_myTabTabs1').css('display') == 'block') && (jQuery(window).scrollTop())) {
			ulHeight();
		}
		else if (( jQuery('#tjucm_myTabTabs1').css('display') == 'none') && (jQuery(window).scrollTop())){
			ulHeight();
		}
		else{
		}

	});
});
</script>
<script>
	jQuery(document).change(function(evt) {
			 var url = '<?php echo Uri::root();?>'
			getSubFormsFeedback(evt,url);
	});
	
// Select the target node where new alerts will be added
var targetNode = document.body; // Example: observe changes in the whole body

// Options for the observer (which mutations to observe)
var config = { childList: true, subtree: true };

// Callback function to execute when mutations are observed
var callback = function(mutationsList, observer) {
    for(var mutation of mutationsList) {
        if (mutation.type === 'childList') {
            // Check added nodes for Joomla alert structure
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeName.toLowerCase() === 'joomla-alert') {
                	jQuery("#tjucm-auto-save-disabled-msg").remove();
                    // Do something with the newly added Joomla alert
                    // For example, fade it out after a few seconds
                    setTimeout(function() {
                        jQuery(node).fadeOut('slow', function() {
                            jQuery(this).remove();

                        });
                    }, 5000);
                }
            });
        }
    }
};

var observer = new MutationObserver(callback);
observer.observe(targetNode, config);

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

// Add CSS for radio button horizontal layout only
jQuery(document).ready(function() {
  if (!jQuery('#radio-only-css').length) {
    jQuery('head').append('<style id="radio-only-css">' +
      '.btn-group.radio { display: flex !important; flex-direction: row !important; margin-top: 5px !important; }' +
      '.btn-group.radio .btn { margin-right: 5px !important; padding: 6px 12px !important; font-size: 14px !important; line-height: 1.4 !important; width: auto !important; flex: 0 0 auto !important; }' +
      '.btn-group.radio input[type="radio"] { display: none !important; }' +
      '.btn-group.radio label { display: inline-block !important; margin-bottom: 0 !important; }' +
      '.btn-group.radio { gap: 5px !important; justify-content: flex-start !important; }' +
      
      '.control-label { display: block !important; margin-bottom: 8px !important; font-weight: bold !important; }' +
      '.controls { display: block !important; width: 100% !important; }' +
      '.controls fieldset { display: block !important; width: 100% !important; }' +
      '.controls .btn-group.radio { margin-top: 8px !important; }' +
      '</style>');
  }
});


</script>
