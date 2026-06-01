<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('bootstrap.renderModal');
HTMLHelper::_('bootstrap.tooltip');

$options['relative'] = true;
HTMLHelper::_('script', 'com_tjlms/tjService.js', $options);
HTMLHelper::_('script', 'com_tjlms/tjlmsAdmin.js', $options);
HTMLHelper::_('script', 'com_tjlms/common.js', $options);
HTMLHelper::script(Uri::root() . 'administrator/components/com_tjlms/assets/js/ajax_file_upload.js');

HTMLHelper::script('media/com_dpe/js/dpe.min.js');

// Check record exist in table if not exist then do not create a copy use it
$loggedInUser = Factory::getUser();
$xrefTable = DPE::table('TjlmsClusterXref');
$xrefTable->load(array('lesson_id' => $this->lessonId));

$canAcccess = RBACL::check($loggedInUser->id, 'com_cluster', 'core.manage.lessons', 'com_tjlms', $xrefTable->cluster_id);

if (!$canAcccess && !$this->course_id && !$loggedInUser->authorise('core.manageall', 'com_cluster'))
{
	$app  = Factory::getApplication();
	$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
	$app->setHeader('status', 403, true);

	return false;
}


if (!$this->format && !$this->lessonId)
{
	echo $this->loadTemplate('lessontypesmodal');
}
else
{
	// Get plugin Jlike_Tjlmsinteraction of type 'content'
	$this->plugin = PluginHelper::getPlugin('content', 'jlike_tjlmslesson');
	$this->pluginParams  = new Registry($this->plugin->params);

	$showInteractionflag = (($this->pluginParams->get('read_interaction') == 1) || ($this->pluginParams->get('practice_interaction') == 1)) ? true : false;

	?>
	<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?> tjBs3 manage-lesson manage-<?php echo $this->lessonId ? 'edit' : 'new';?>-lesson admin com_tjlms" id="lesson-form">
		<div class="tjlms_add_lesson_form" data-js-unique="<?php echo $this->formId;?>">
			<fieldset>
				<?php
				if ($this->lessonId == 0)
				{
					?>
				<h1 class="fs-24 font-600 mt-0 mb-30">
					<?php echo Text::_('COM_TJLMS_TITLE_ADD_LESSON')?>
				</h1>
				<?php
				}

				echo HTMLHelper::_('bootstrap.startTabSet', 'lessonform', array('active' => 'general'));

				// Basic Detail Tab
				echo HTMLHelper::_('bootstrap.addTab', 'lessonform', 'general', Text::_('COM_TJLMS_TITLE_LESSON_DETAILS', true));
				echo $this->loadTemplate('basic');
				echo HTMLHelper::_('bootstrap.endTab');

				// Document Format Tab
				echo HTMLHelper::_('bootstrap.addTab', 'lessonform', 'format', Text::_('COM_TJLMS_TITLE_LESSON_FORMAT', true));
				echo $this->loadTemplate('format');
				echo HTMLHelper::_('bootstrap.endTab');

				// Document Interaction Tab
				// Check if plugin is enabled then onlt show this tab
				if ($this->plugin && $showInteractionflag === true)
				{
					echo HTMLHelper::_('bootstrap.addTab', 'lessonform', 'interaction', Text::_('COM_TJLMS_TITLE_LESSON_INTERACTION', true));
					echo $this->loadTemplate('interaction');
					echo HTMLHelper::_('bootstrap.endTab');
				}

				 /*if ($this->assessment):
				 echo HTMLHelper::_('bootstrap.addTab', 'lessonform', 'assessment', Text::_('COM_TJLMS_TITLE_LESSON_ASSESSMENT', true));
				 echo $this->loadTemplate('assessment');
				 echo HTMLHelper::_('bootstrap.endTab');
				endif; */
				/*

				<!--ASSOCIATE FILES STARTS-->
				<?php $allowAssocFiles = $this->params->get('allow_associate_files','0','INT');

				if ($allowAssocFiles == 1):
					echo HTMLHelper::_('bootstrap.addTab', 'lessonform', 'assocFiles', Text::_('COM_TJLMS_TITLE_LESSON_ASSOCIATE_FILES', true));

					echo $this->loadTemplate('associatefiles');
					echo HTMLHelper::_('bootstrap.endTab');
				endif; ?>
				<!--ENDS-->
				*/
				echo HTMLHelper::_('bootstrap.endTabSet');

				if ($this->ifintmpl == 'component'):
					?>
					<!-- show action buttons/toolbar -->
					<!--container-fluid-->
					<div class="clearfix"></div>
					<div class="row">
						<div class="col-sm-9">
							<div class="form-actions pt-5 pr-5 text-end">
								<div class="btn-toolbar clearfix d-inline-block" data-js-attr="form-actions">
								<!--
									<div id="toolbar-prev" class="btn-wrapper">
										<button type="button" data-js-attr="form-actions-prev" class="btn com_tmt_button hide">
											<span class="valign-middle"><i class="icon-arrow-left"></i></span>
											<?php echo Text::_('COM_TJLMS_PREV') ?>
										</button>
									</div>
								-->

									<div id="toolbar-next" class="btn-wrapper">
										<button type="button"data-js-attr="form-actions-next" class="btn btn-success com_tmt_button">
												<?php echo Text::_('COM_TJLMS_SAVE_NEXT') ?>
												<span class="valign-middle"><i class="icon-arrow-right"></i></span>
										</button>
									</div>

									<div id="toolbar-apply" class="btn-wrapper pull-right pr-10">
										<button type="button" id="button_save" class="btn btn-success com_tmt_button hide" onclick="Joomla.submitbutton('lesson.apply');">
											<span class="fa fa-check mr-0"></span>
											<?php echo Text::_('COM_TJLMS_BUTTON_SAVE') ?>
										</button>
									</div>

									<div id="toolbar-save" class="btn-wrapper text-center pull-right pl-20">
										<button type="button" id="button_save_and_close" class="btn btn-success com_tmt_button d-none">
											<?php /*echo (!$this->parentDiv) ? Text::_('COM_TMT_BUTTON_SAVE_AND_CLOSE') : Text::_('COM_TMT_BUTTON_SAVE_AND_ADD_TOQUIZ');*/?>
											<span class="fa fa-check mr-0"></span>
											<?php echo Text::_('COM_TJLMS_SAVE_CLOSE');?>
										</button>
									</div>

								<!--
									<div id="toolbar-cancel" class="btn-wrapper">
										<button type="button" class="btn com_tmt_button" onclick="Joomla.submitbutton('lesson.cancel')">
											<span class="icon-delete valign-middle"></span>
											<?php echo Text::_('COM_TJLMS_CANCEL') ?>
										</button>
									</div>
								-->

								</div><!--btn-toolbar-->
							</div>
						</div><!--row-fluid-->
					</div><!--container-fluid-->
				<?php
				endif;
				?>
			</fieldset>
		</div>
	</div>
	<?php

	if ($this->courseId)
	{
		$redirectURL = "index.php?option=com_tjlms&view=managecourse&id=" . $this->courseId;
	}
	else
	{
		$redirectURL = "index.php?option=com_tjlms&view=managelessons";
	}

	$redirectURL = $this->comtjlmsHelper->tjlmsRoute($redirectURL);

	Factory::getDocument()->addScriptDeclaration("const lessonredirectURL = '" . $redirectURL . "';");
	?>
	<script>
		var tjlmsAdmin = {
	calculateCourseUrl : "index.php?option=com_tjlms&view=tools&task=tools.calculateCourseProgress&format=json",
	showEnrolledUsersUrl : 'index.php?option=com_tjlms&task=course.enrolledUsersCount&format=json',
	validateDates: function (formElement){
		var startDateElement = jQuery(formElement).find('input[name=\"jform[start_date]\"]');
		var endDateElement = jQuery(formElement).find('input[name=\"jform[end_date]\"]');
		var startDate = startDateElement.val();
		var endDate = endDateElement.val();

		if (startDate != '')
		{
			var temp = startDate.split(" ");

			var validDate = temp[0].match(/^\d{4}[-](0?[1-9]|1[012])[-](0?[1-9]|[12][0-9]|3[01])$/);
			if (validDate ==  null)
			{
				Joomla.renderMessages({"error":[Joomla.JText._('COM_TJLMS_INVALID_START_DATE')]});
				return false;
			}
		}
		else if (endDate != '')	/*Validate course end date*/
		{
			endDate = endDate.split(" ");

			var validDate = endDate[0].match(/^\d{4}[-](0?[1-9]|1[012])[-](0?[1-9]|[12][0-9]|3[01])$/);

			if (validDate ==  null)
			{
				Joomla.renderMessages({"error":[Joomla.JText._('COM_TJLMS_INVALID_END_DATE')]});
				return false;
			}
		}

		/* Validate time_finished_duration < time_duration */
		if (endDate != '' && startDate > endDate)
		{
			jQuery(endDateElement).focus();
			Joomla.renderMessages({"error":[Joomla.JText._('COM_TJLMS_DATE_RANGE_VALIDATION')]});
			return false;
		}

		return true;
	},
	eachform: {
		$form: null,
		saveurl: '',
		extraValidations: '',
		validate : function() {
			var isValid = document.formvalidator.isValid(document.getElementById(this.$form.attr('id')));
			var formElement = this.$form;
			if (isValid){
				if (this.extraValidations){

					var extravalid =  true;
					var arr = this.extraValidations.split(',');
					jQuery(arr).each(function (index, strFun){
						var func = window;
						var funcSplit = strFun.split('.');
						for (i = 0;i < funcSplit.length;i++){
							func = func[funcSplit[i]];
						}
						extravalid = func(formElement);

						if (!extravalid){
							return false;
						}
					});
					if (!extravalid)
					{
						isValid = false;
					}
				}
			}

			return isValid;
		},
		ajaxsave : function() {
			var doProcess = this.validate();

			if (doProcess){
				if (!this.saveurl){
					this.saveurl= jQuery(this.$form).attr('action');
				}
				this.saveurl  = Joomla.getOptions('system.paths').base + "/" + this.saveurl;
				var thisform = this.$form;
				var params = {};

				if (jQuery(thisform).attr("enctype") == "multipart/form-data")
				{
					var jformData = new FormData(thisform[0]);
					params['contentType'] = false;
					params['processData'] = false;
				}
				else
				{
					var jformData = thisform.serialize();
				}

				var promise = tjService.postData(this.saveurl,jformData, params);

				promise.fail(
					function(response) {
						doProcess =  false;
					}
				).done(
					function(response) {
						if (!response.success && response.message){
								var messages = { "error": [response.message]};
								Joomla.renderMessages(messages);
								doProcess =  false;
							}

						if (response.messages){
							Joomla.renderMessages(response.messages);
						}
							/*if (response.success && response.message){
								var messages = { "success": [response.message]};
								Joomla.renderMessages(messages);
							}*/

						if (jQuery(thisform).attr("enctype") == "multipart/form-data")
						{
							jQuery(thisform).find("input[type='file']").val('');
						}

						doProcess = response.data;
					}
				);
			}
		return doProcess;
		}
	},
	stepform: {
		ajaxSaveTabs: true,
		ifintmpl: true,
		init: function() {
			ifintmpl = this.ifintmpl;
			var stepFormObj = this;

			jQuery(document).ready(function(){
				if (ifintmpl){
					jQuery('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
						stepFormObj.formactions();
					});
				}

				stepFormObj.formactions();
			});

			jQuery(window).on('load', function(){

				/*validation on each tab click*/
				jQuery(".nav-tabs li").click(function(){
					var linumber  = jQuery(this).index();

					var result = stepFormObj.validateTabs(linumber);

					if (!result){
						return false;
					}
				});

				/*validation on each tab click*/
				jQuery("[data-js-attr='form-actions-next']").click(function(){
					// jQuery(".nav-tabs li.active").next().find('a').trigger("click");
					jQuery(".nav-tabs li a.nav-link.active").closest('li').next('li').find(".nav-link")[0].click();
					jQuery("html, body").animate({scrollTop: 0}, 500);
					
					if(jQuery(".nav-tabs li a.nav-link.active").closest('li').is(':last-child')){
						jQuery("#toolbar-prev button", "[data-js-attr='form-actions']").show().removeClass('d-none');
						jQuery("#toolbar-next button", "[data-js-attr='form-actions']").hide().addClass('d-none');
						jQuery("#toolbar-save button", "[data-js-attr='form-actions']").show().removeClass('d-none');
					}
				});

				/*validation on each tab click*/
				jQuery("[data-js-attr='form-actions-prev']").click(function(){
					// jQuery(".nav-tabs li.active").prev().find('a').trigger("click");
					jQuery(".nav-tabs li a.nav-link.active").closest('li').prev('li').find(".nav-link")[0].click();
				});

			});
		},
		formactions : function (){
			jQuery("#toolbar-prev button, #toolbar-next button, #toolbar-apply button, #toolbar-save button","[data-js-attr='form-actions']").hide();

			if(jQuery(".nav-tabs li a.nav-link.active").closest('li').is(':first-child')){
				jQuery("#toolbar-prev button","[data-js-attr='form-actions']").hide().addClass('d-none');
				jQuery("#toolbar-next button","[data-js-attr='form-actions']").show().removeClass('d-none');
			}
			else if(jQuery(".nav-tabs li a.nav-link.active").closest('li').is(':last-child')){
				jQuery("#toolbar-prev button", "[data-js-attr='form-actions']").show().removeClass('d-none');
				jQuery("#toolbar-next button", "[data-js-attr='form-actions']").hide().addClass('d-none');
				jQuery("#toolbar-save button", "[data-js-attr='form-actions']").show().removeClass('d-none');
			}
			else{
				jQuery("#toolbar-prev button","[data-js-attr='form-actions']").show().removeClass('d-none');
				jQuery("#toolbar-next button","[data-js-attr='form-actions']").show().removeClass('d-none');
			}

			if (jQuery("[data-js-id='item-id']").val()){
				jQuery("#toolbar-save", "[data-js-attr='form-actions']").show();
			}
		},
		validateTabs : function(tabcounttovalidate) {
			var formprocessdone = true;
			var eachFormObject = tjlmsAdmin.eachform;
			for(var i=0 ; i < tabcounttovalidate ; i++){

				var navli = jQuery(".nav-tabs li").get(i);
				var thiscontenttab	= jQuery('a', jQuery(navli)).attr('href');

				eachFormObject.extraValidations =  jQuery(".extra_validations", jQuery(thiscontenttab)).attr('data-js-validation-functions');

				if (this.ajaxSaveTabs){
					eachFormObject.$form = jQuery('form', thiscontenttab);
					var task= jQuery("form [name='task']", thiscontenttab).val();
					var option= jQuery("form [name='option']", thiscontenttab).val();
					eachFormObject.saveurl= "index.php?option=" + option +"&task=" + task + "&format=json";
					formprocessdone = eachFormObject.ajaxsave();
				}
				else{
					eachFormObject.$form = jQuery(thiscontenttab);
					formprocessdone = eachFormObject.validate();
				}

				if (!formprocessdone){
					break;
				}

				if (this.ajaxSaveTabs){
					jQuery.each(formprocessdone, function( key, value ) {
						jQuery("[data-js-id='" + key +"']").val(value);
					});
				}

				var formid = jQuery(eachFormObject.$form).attr('id');

				if (window["tjlmsAdmin"][formid])
				{
					if (window["tjlmsAdmin"][formid]["afterSave"])
					{
						window["tjlmsAdmin"][formid]["afterSave"](formprocessdone);
					}
				}
			}

			return formprocessdone;
		},
	},
	modules: {
		init: function() {
			var parentObj =  this;
			jQuery(window).on("load", function (){
				jQuery(".tjlms_module__changestate, .tjlms_module__edit, .tjlms_module__delete").show();
				jQuery(".tjlms_lesson__edit, .tjlms_lesson__delete").show();

				jQuery('.content-li').click(function(){
					var section_tr	=	jQuery(this).closest('li').attr('id');
					var module_id	=	section_tr.replace("modlist_", "");
					jQuery("#curriculum-lesson-ul_"+module_id).toggle();
				});

				jQuery(document).on("click", "[data-js-id='course-module'] [data-js-id='change-module-state']", function(){
					var module = jQuery(this).closest("[data-js-id='course-module']");
					var moduleId = jQuery(module).attr("data-js-itemid");

					var currentState = jQuery(module).find('[data-js-id="module-state"]').val();

					var targetState = (parseInt(currentState) === 1) ? 0 : 1;
					var res = parentObj.changeModuleState(moduleId, targetState);
				});

				jQuery(document).on("click", "[data-js-id='edit-module']", function(){

					var moduleId = jQuery(this).closest("[data-js-id='course-module']").attr("data-js-itemid");

					parentObj.toggleForm(moduleId, 'show');
				});

				jQuery(document).on("click", "[data-js-id='delete-module']", function(){

					var module = jQuery(this).closest("[data-js-id='course-module']");

					var res = jQuery(module).find('[data-js-id="module-lesson"]').length;

					if (res > 0)
					{
						alert(Joomla.JText._('COM_TJLMS_MODUELS_MODULE_WITH_LESSONS_DELETE_ERROR'));
						return false;
					}

					var moduleId = jQuery(module).attr("data-js-itemid");
					parentObj.deleteModule(moduleId);
				});

				jQuery(document).on("click", "[data-js-id='delete-lesson']", function(){
					var lessonId = jQuery(this).closest("[data-js-id='module-lesson']").attr("data-js-itemid");
					var moduleId = jQuery(this).closest("[data-js-id='course-module']").attr("data-js-itemid");
					var courseId = jQuery("#course_id").val();

					parentObj.deleteLesson(lessonId, moduleId, courseId);
				});

				parentObj.updateModules();
				parentObj.sort()
			});
		},
		sort: function(){
			jQuery("[data-js-id='lessons_container']").sortable({
				scroll: false,
				connectWith: "[data-js-id='lessons_container']",
				handle:'.lessonSortingHandler',
				items: "> li:not(.non-sortable-lesson-li)",
				update: function() {
					var courseId	= jQuery("#course_id", jQuery(this).closest('.curriculum-container')).val();
					var module = jQuery(this).closest("[data-js-id='course-module']");
					var moduleId = jQuery(module).attr('data-js-itemid');
					var lessons = [];
					var j= 0;
					jQuery(module).find('[data-js-id="module-lesson"]').each(function(j){
						lessons[j] = jQuery(this).attr("data-js-itemid");
						j++;
					});

					var formData = {'courseId': courseId, 'moduleId' : moduleId, 'lessons': lessons};
					var url= "index.php?option=com_tjlms&task=modules.sortModuleLessons&format=json";
					var promise = tjService.postData(url,formData);

					promise.fail(
						function(response) {
							var messages = { "error": [response]};
							Joomla.renderMessages(messages);
						}
					).done(function(response) {
						if (!response.success && response.message){
							var messages = { "error": [response.message]};
							Joomla.renderMessages(messages);
						}

						tjlmsAdmin.modules.updateModules();
					});
				}
			});

			jQuery('.courseModules').sortable({
				handle:'.moduleSortingHandler',
				update: function() {
					var courseId	= jQuery("#course_id", jQuery(this).closest('.curriculum-container')).val();

					/* All module ordering stored in stringDiv along with their ID as the key. */
					var modules = [];
					var j=0;
					jQuery("[data-js-id='course-module']").each(function(j) {
						modules[j] = jQuery(this).attr("data-js-itemid");
						j++;
					});

					var formData = {'courseId': courseId, 'modules': modules};
					var url= "index.php?option=com_tjlms&task=modules.sortModules&format=json";
					var promise = tjService.postData(url,formData);

					promise.fail(
						function(response) {
							var messages = { "error": [response]};
							Joomla.renderMessages(messages);
						}
					).done(function(response) {
						if (!response.success && response.message){
							var messages = { "error": [response.message]};
							Joomla.renderMessages(messages);
						}
					});
				}
			});
		},
		updateModules: function()
		{
			jQuery('[data-js-id="course-module"]', window.parent.document).each(function(){

				var res = jQuery(this).find('[data-js-id="module-lesson"]').length;
				jQuery(this).find(".tjlms_module__delete").hide();

				if (res == 0)
				{
					jQuery(this).find(".tjlms_module__delete").show();
				}
			});
		},
		changeModuleState: function(mod_id,state,name)
		{
			var saveurl = "index.php?option=com_tjlms&task=modules.changeState&format=json";
			var formData = {'mod_id' :mod_id, 'state' : state};
			var promise = tjService.postData(saveurl,formData);

			promise.fail(
				function(response) {
					var messages = { "error": [response]};
					Joomla.renderMessages(messages);
				}
			).done(function(response) {

				if (!response.success && response.message){
					var messages = { "error": [response.message]};
					Joomla.renderMessages(messages);
				}
				if (response.message){
					var messages = { "success": [response.message]};
					Joomla.renderMessages(messages);
				}

				var iconClass = (state) ? "publish" : "unpublish";
				var sectionClass = (state) ? "modPublish" : "modUnpublish";
				var targetState = (response.data.state == 1) ? 0 : 1;

				jQuery('#modlist_' + mod_id + ' .tjlms_module').removeClass('modUnpublish').removeClass('modPublish').addClass(sectionClass);
				jQuery('#modlist_' + mod_id + ' .tjlms_module .tjlms_module__changestate i').removeClass('icon-publish').removeClass('icon-unpublish').addClass('icon-' + iconClass);
				jQuery('#modlist_' + mod_id + ' .tjlms_module [data-js-id="module-state"]').val(state);

			});
		},
		deleteModule: function(moduleId) {
			var comfirmDelete = confirm(Joomla.JText._('COM_TJLMS_SURE_DELETE_MODULE'));
			if(comfirmDelete == true)
			{
				var saveurl = "index.php?option=com_tjlms&task=modules.deleteModule&format=json";
				var formToken = jQuery('[data-js-id="form-token"]').attr('name');

				var formData = {};
				formData[formToken] = 1;
				formData['moduleId']=moduleId;

				var promise = tjService.postData(saveurl,formData);

				promise.fail(
					function(response) {
						var messages = { "error": [response]};
						Joomla.renderMessages(messages);
					}
				).done(function(response) {

					if (!response.success && response.message){
						var messages = { "error": [response.message]};
						Joomla.renderMessages(messages);
					}
					if (response.success){
						var messages = { "success": [response.message]};
						Joomla.renderMessages(messages);

						jQuery("[data-js-id='course-module'][data-js-itemid='" + moduleId +"']").remove();
					}
				});
			}
			return false;
		},
		editModule: function(moduleId) {

			var mediaformData = new FormData();
			let modulefile = jQuery('#module-image'+moduleId)[0].files[0];
			let courseId = jQuery("#course_id").val();
			let desc = jQuery("#module-description"+moduleId).val();
			let title = jQuery("#module-title"+moduleId).val();
			let state = jQuery("#state"+moduleId).val();

			mediaformData.append('jform[image]', modulefile);
			mediaformData.append('tjlms_module[id]', moduleId);
			mediaformData.append('tjlms_module[description]', desc);
			mediaformData.append('tjlms_module[name]', title);
			mediaformData.append('tjlms_module[course_id]', courseId);
			mediaformData.append('tjlms_module[state]', state);
			params = {};
			params['contentType'] = false;
			params['processData'] = false;

			url = "index.php?option=com_tjlms&task=modules.saveModule&format=json";
			var promise  = tjService.postData(url,mediaformData, params);

			promise.fail(
					function(response) {
						var errorMessages = { "error": [response.message]};
						Joomla.renderMessages(errorMessages);
					}
				).done(function(response) {
					if (moduleId == 0)
					{
						location.reload(true);
					}

					var moduleTitle = jQuery('#tjlms_module_form_' + moduleId).find(".module-title").val();

					jQuery("[data-js-id='course-module'][data-js-itemid='" + moduleId +"']").find(".tjlms_module_title").text(moduleTitle);

					jQuery("[data-js-id='course-module'][data-js-itemid='" + moduleId +"']").find(".tjlms_module_thumbnail_image").val(response.data.image);

					if (response.data.imagePath)
					{
						jQuery("[data-js-id='course-module'][data-js-itemid='" + moduleId +"']").find(".tjlms_module_thumbail").removeClass('hide');
						jQuery("[data-js-id='course-module'][data-js-itemid='" + moduleId +"']").find(".tjlms_module_image_path").attr("src", response.data.imagePath);
					}

					tjlmsAdmin.modules.toggleForm(moduleId, 'hide');
					jQuery('#module-image'+moduleId).val('');

					if (response.success){
						var successMessages = { "success": [response.message]};
						Joomla.renderMessages(successMessages);
					}
					else
					{
						var responceErrorMessages = { "error": [response.message]};
						Joomla.renderMessages(responceErrorMessages);
					}
				});
		},
		toggleModuleAdditionalInfo: function(moduleId)
		{
			if (moduleId === 0)
			{
				var toggleModule = jQuery("[data-js-id='create-module-form']");
			}
			else
			{
				var toggleModule = jQuery("[data-js-id='course-module'][data-js-itemid='" + moduleId +"']");
			}

			if (toggleModule.find('.tjlms_module_image_desc').is(":hidden") === true)
			{
				toggleModule.find('.toggleModuleButton').html("<a>"+Joomla.JText._('COM_TJLMS_ADDITIONAL_DETAILS_HIDE')+"</a>")
			}
			else
			{
				toggleModule.find('.toggleModuleButton').html("<a>"+Joomla.JText._('COM_TJLMS_ADDITIONAL_DETAILS')+"</a>");
			}

			toggleModule.find('.tjlms_module_image_desc').toggle("open");
		},
		deleteMedia:function(moduleId) {

			if(!confirm(Joomla.JText._('JGLOBAL_CONFIRM_DELETE')))
			{
				return false;
			}

			data = {'moduleId': moduleId};
			url = "index.php?option=com_tjlms&task=modules.deleteImage&format=json";
			var promise  = tjService.postData(url,data);

			promise.fail(
					function(response) {
						var errorMessages = { "error": [response]};
						Joomla.renderMessages(errorMessages);
					}
				).done(function(response) {
					jQuery("[data-js-id='course-module'][data-js-itemid='" + moduleId +"']").find(".tjlms_module_thumbnail_image").val('');
					jQuery("[data-js-id='course-module'][data-js-itemid='" + moduleId +"']").find(".tjlms_module_thumbail").addClass('hide');
					jQuery("[data-js-id='course-module'][data-js-itemid='" + moduleId +"']").find(".tjlms_module_image_path").attr("src", '');

					if (response.success){
						var successMessages = { "success": [response.message]};
						Joomla.renderMessages(successMessages);
					}
					else
					{
						var responseErrorMessages = { "error": [response.message]};
						Joomla.renderMessages(responseErrorMessages);
					}
				});
		},
		toggleForm : function(moduleId, action = 'show'){
			var module = jQuery("[data-js-id='course-module'][data-js-itemid='" + moduleId +"']");

			if (action == "show")
			{
				if (module.length)
				{
					jQuery(module).find("[data-js-id='edit-module-form']").show();
					jQuery(module).find("[data-js-id='edit-module-form'] .module-title").focus();
				}
				else
				{
					jQuery('[data-js-id="create-module-form"]').show();
					jQuery('.add-module-div').hide();
					jQuery('[data-js-id="create-module-form"] .module-title').focus();
				}
			}
			else
			{
				if (module.length)
				{
					jQuery(module).find("[data-js-id='edit-module-form']").hide();
				}
				else
				{
					jQuery('[data-js-id="create-module-form"]').hide();
					jQuery('.add-module-div').show();
				}
			}

			return false;
		},
		deleteLesson: function(lessonId, moduleId, courseId) {
			var comfirmDelete = confirm(Joomla.JText._('COM_TJLMS_SURE_DELETE'));
			if(comfirmDelete == true)
			{
				var saveurl = "index.php?option=com_tjlms&task=modules.deleteLesson&format=json";
				var formToken = jQuery('[data-js-id="form-token"]').attr('name');

				var formData = {};
				formData[formToken] = 1;
				formData['lessonId']=lessonId;
				formData['moduleId']=moduleId;
				formData['courseId']=courseId;

				var promise = tjService.postData(saveurl,formData);

				promise.fail(
					function(response) {
						var messages = { "error": [response]};
						Joomla.renderMessages(messages);
					}
				).done(function(response) {

					if (!response.success && response.message){
						var messages = { "error": [response.message]};
						Joomla.renderMessages(messages);
					}
					if (response.success){
						var messages = { "success": [response.message]};
						Joomla.renderMessages(messages);

						jQuery("#lessonlist_" + lessonId).remove();
					}
				});
			}
			return false;
		},
	},
	lesson: {
		init: function(ifintmpl, cid, lessonUploadSize, redirectURL, livetrackReviews){

			/* if user is uploading the file*/
			jQuery("input[type='file'][data-upload-ajax='1']").change(function(){

				/*remove status bar if already appneded*/
				/*jQuery(thisfile).closest('.questions ').children( ".statusbar" ).remove();*/
				thisfile = jQuery(this);

				/* Get uploaded file object */
				var uploadedfile	=	jQuery(thisfile)[0].files[0];
				var formData = new FormData();
				formData.append( 'FileInput', uploadedfile );

				var lessonId = jQuery('[data-js-id="id"]').val();
				var lessonFormat = jQuery('[data-js-id="format"]').val();
				var subFormat = jQuery('[data-js-id="subformat"]').val();

				formData.append('mediaformat', lessonFormat);
				formData.append('subformat', subFormat);
				formData.append('formatData[' + lessonFormat +'][' + subFormat + '][lessonId]', lessonId);

				tjLmsCommon.file.$file = thisfile;
				tjLmsCommon.file.formData = formData;
				tjLmsCommon.file.allowedSize = lessonUploadSize;
				tjLmsCommon.file.showstatusbar = true;
				tjLmsCommon.file.afterProcessDone='tjlmsAdmin.lessonFormatForm.populateMediaId';

				var returnvar = tjLmsCommon.file.upload();
			});

			jQuery(document).ready(function()
			{
				if (livetrackReviews > 0)
				{
					jQuery("#assessmentform .subform-repeatable").addClass("disabled").css("pointer-events", "none");
					jQuery("[data-js-id='disable-if-reviewed']").addClass("disabled").css("pointer-events", "none");
				}
			})

			Joomla.submitbutton = function(task)
			{
				if(task == "lesson.save")
				{
					var tabscount  = jQuery(".tjlms_add_lesson_form .nav-tabs li").length;
					var result = tjlmsAdmin.stepform.validateTabs(tabscount);

					if (!result){
						return false;
					}
				}

				window.location = redirectURL;
			}
		},
		preview: function(lessonId)
		{
			var lesson_preview_link = root_url+ "index.php?option=com_tjlms&view=lesson&tmpl=component&lesson_id=" +  lessonId + "&mode=preview&isAdmin=1" ;
			var wwidth = jQuery(window).width();
				var wheight = jQuery(window).height();
				SqueezeBox.open(lesson_preview_link, {
					handler: 'iframe',
					closable:false,
					size: {x: wwidth, y: wheight},
					//iframePreload:true,
					sizeLoading: { x: wwidth, y: wheight },
					classWindow: 'tjlms_lesson_screen',
					classOverlay: 'tjlms_lesson_screen_overlay',
				});
		},
		selectionPopup: function()
		{
			jQuery('#lessonTypeModal').modal('show');
		},
	},
	basicForm :{
		validate: function(formElement){
			var attemptField = jQuery(formElement).find("[name$='[no_of_attempts]']");
			var newNoOfAttempts = jQuery(formElement).find("[name$='[no_of_attempts]']").val();
			var oldNoOfAttempts = jQuery(formElement).find('#no_attempts').val();
			var maxAttemptsDone = jQuery(formElement).find('#max_attempt').val();

			if (isNaN(newNoOfAttempts)){
				jQuery(attemptField).val(0);
			}

			// Check if attempt is less than original attempt
			if (newNoOfAttempts != 0 && newNoOfAttempts < maxAttemptsDone)
			{
				var msg = Joomla.JText._('COM_TJLMS_MAX_ATTEMPT_VALIDATION_MSG').replace("%s", maxAttemptsDone);
				Joomla.renderMessages({"error":[msg]});
				jQuery(attemptField).val(oldNoOfAttempts).focus();
				return false;
			}

			return true;
		},
	},
	lessonFormatForm: {
		$form	: null,
		initForm: function(){
			return this.$form = jQuery('.lesson-format-form');
		},
		showSubformat: function(format, subformat){
			jQuery(this.$form).find(".subformat").hide().addClass("d-none");
			jQuery(this.$form).find("." + format + "_subformat[data-subformat='"+ subformat +"']").show().removeClass("d-none");

		},
		validate: function(formElement){
			var format = jQuery(formElement).find("[name$='[format]']").val();
			var subformat =jQuery(formElement).find("[name$='[subformat]']").val();
			var media_id =jQuery(formElement).find("[name$='[media_id]']").val();
			var formId =jQuery(formElement).find("[name$='[form_id]']").val();

			var function_to_call = 'validate'+format+subformat;
			var check_validation = eval(function_to_call)(formId,format,subformat,media_id);

			if (check_validation.check == '0')
			{
				Joomla.renderMessages({"error":[check_validation.message]});
				return false;
			}

			return true;
		},
		populateMediaId : function(formData) {
			jQuery("[data-js-id='media_id']").val(formData.media_id);
		}
	},
	associateFileForm: {
		$form	: null,
		initForm: function(){
			return this.$form = jQuery('.lesson-format-form');
		},
		init : function(){

				/* if user is uploading the file*/
			jQuery("[data-js-type='associate_file_upload'] input[type='file']").change(function(){
				 /*remove status bar if already appneded*/

				thisfile = jQuery(this);

				/* Get uploaded file object */
				var uploadedfile	=	jQuery(thisfile)[0].files[0];
				var formData = new FormData();
				formData.append( 'FileInput', uploadedfile );

				var lessonId = jQuery('[data-js-id="id"]').val();
				formData.append( 'mediaformat', 'associate' );
				formData.append( 'subformat', 'upload' );
				formData.append('formatData[associate][upload][lessonId]', lessonId);

				tjLmsCommon.file.$file = thisfile;
				tjLmsCommon.file.formData = formData;

				if (parseFloat(thisfile.attr('data-allowedsize')))
				{
					tjLmsCommon.file.allowedSize = parseFloat(thisfile.attr('data-allowedsize'));
				}

				tjLmsCommon.file.showstatusbar = true;
				tjLmsCommon.file.afterProcessDone='tjlmsAdmin.associateFileForm.showFileinAssociatelist';

				var returnvar = tjLmsCommon.file.upload();

			});

			jQuery(document).on("click", "[data-js-id='associated-file-remove']" , function() {
				var mediaId = jQuery(this).attr("data-js-val");
				var lessonId = jQuery(this).closest(".lesson-associatefile-form").find("[data-js-id='id']").val();
				tjlmsAdmin.associateFileForm.removeFile(lessonId, mediaId);
			});
		},
		showFileinAssociatelist: function(formData) {
			var lesonsId = jQuery("[data-js-id='id']").val();
			var associatedform = jQuery(".lesson-associatefile-form");
			var table_content = '<tr id="assocfiletr_'+formData.media_id+'"><td class="tjlmscenter" id="list_select_files'+formData.media_id+'"><span>'+formData.org_filename +'</span><input type="hidden" name="lesson_files[][media_id]" value="'+formData.media_id+'"/></td><td class="tjlmscenter"><i id="removeFile'+formData.media_id+'" onclick="tjlmsAdmin.associateFileForm.removeFile(\''+lesonsId+'\', \''+formData.media_id+'\')" class="remove btn">×</i></td></tr>';

			jQuery(associatedform).find('.no_selected_files').hide();
			jQuery(associatedform).find('#list_selected_files').append(table_content);
			jQuery(associatedform).find('#list_selected_files').show();
		},
		batchSelect: function(formId){
			var checked = jQuery("input[id*='cb']:checked").length;

			if (checked==0)
			{
				alert(Joomla.JText._("COM_TJLMS_ASSOCIATE_MESSAGE_SELECT_ITEMS"));
				return false;
			}

			window.parent.jQuery('.lesson-associatefile-form[data-js-unique="'+ formId +'"] .no_selected_files').hide();
			window.parent.jQuery('#lesson-associatefile-form_'+formId+' #list_selected_files').show();

			var lessonId = window.parent.jQuery('.lesson-associatefile-form[data-js-unique="'+ formId +'"]').find("[data-js-id='id']").val();

			var c = 1;
			jQuery("input[id*='cb']:checked").each(function() {
				var mediaId = jQuery(this).val();
				var mediaTitle = jQuery(this).closest(".associtefile").find(".associtefile__name").text();
				var saveurl= Joomla.getOptions('system.paths').root + "/index.php?option=com_tjlms&task=lessonform.addMediaAsAssociatetoLesson&format=json";
				var formData = {'mediaId' :mediaId, 'lessonId' : lessonId};
				var promise = tjService.postData(saveurl,formData);

				var doProcess =  false;

					promise.fail(
						function(response) {
							var messages = { "error": [response]};
							Joomla.renderMessages(messages);
							doProcess =  false;
						}
					).done(function(response) {

						if (!response.success && response.message){
									var messages = { "error": [response.message]};
							Joomla.renderMessages(messages);
							doProcess =  false;
						}
						if (response.messages){
							Joomla.renderMessages(response.messages);
						}

						var table_content = '<tr id="assocfiletr_'+mediaId+'" data-js-id="associated-file"><td class="tjlmscenter" id="list_select_files'+mediaId+'"><span>'+ mediaTitle +'</span><input type="hidden" name="lesson_files[][media_id]" value="'+mediaId+'"/></td><td class="tjlmscenter"><i data-js-id="associated-file-remove" data-js-val="' + mediaId + '" class="remove btn">×</i></td></tr>';
						jQuery('#lesson-associatefile-form_'+formId+' #list_selected_files',parent.document).append(table_content);

						if (c == checked)
						{
							window.parent.Joomla.Modal.getCurrent().close();
						}

						c++;
					});
				});
		},
		removeFile : function(lessonId, mediaId) {
			var confirmMsg = confirm(Joomla.JText._('COM_TJLMS_REMOVE_ASSOCIATE_FILE_MESSAGE'));

			if (confirmMsg == 1)
			{
				var saveurl= Joomla.getOptions('system.paths').root + "/index.php?option=com_tjlms&task=lessonform.removeAssociatedFileFromLesson&format=json";
				var formData = {'mediaId' :mediaId, 'lessonId' : lessonId};
				var promise = tjService.postData(saveurl,formData);

				var doProcess =  false;

				promise.fail(
					function(response) {
						var messages = { "error": [response]};
						Joomla.renderMessages(messages);
						doProcess =  false;
					}
				).done(function(response) {

					if (!response.success && response.message){
						var messages = { "error": [response.message]};
						Joomla.renderMessages(messages);
						doProcess =  false;
					}
					if (response.message){
						var messages = { "success": [response.message]};
						Joomla.renderMessages(messages);

						jQuery('#assocfiletr_'+mediaId).remove();
					}
				});
			}
		}
	},
	assessmentform: {
		$form : null,
		init: function() {
			jQuery(document).on('subform-row-add', function(event, row){
				jQuery(row).find('.param_value').attr("onBlur", "tjlmsAdmin.assessmentform.calculateTotal()");
				jQuery(row).find('.param_weightage').attr('onBlur', "tjlmsAdmin.assessmentform.calculateTotal()");
			});

			jQuery(".param_weightage, .param_value").blur(function() {
				tjlmsAdmin.assessmentform.calculateTotal();
			});

			/* @TODO- check if can be done by showon */
			jQuery("[name='jform[add_assessment]']").click(function() {
				if (jQuery(this).val() == 1)
				{
					jQuery("[data-js-id='assessment-details']").show().removeClass("hide")
					jQuery(".assessment-param-required").addClass("required").attr("required", "required");
				}
				else
				{
					jQuery("[data-js-id='assessment-details']").hide().addClass("hide");
					jQuery(".assessment-param-required").removeClass("required").removeAttr("required");
				}
			});

			jQuery("[data-js-id='assessment-details']").hide().addClass("hide");
			jQuery(".assessment-param-required").removeClass("required").removeAttr("required");

			if (jQuery("[name='jform[add_assessment]']:checked").val() == 1)
			{
				jQuery("[data-js-id='assessment-details']").show().removeClass("hide");
				jQuery(".assessment-param-required").addClass("required").attr("required", "required");
			}

			jQuery("[name='jform[assessment_answersheet]']").click(function() {
				if (jQuery(this).val() == 1)
				{
					jQuery("[data-js-id='answersheet_options']").show().removeClass("hide")
				}
				else
				{
					jQuery("[data-js-id='answersheet_options']").hide().addClass("hide")
				}
			});

			jQuery("[data-js-id='answersheet_options']").hide().addClass("hide");

			if (jQuery("[name='jform[assessment_answersheet]']:checked").val() == 1)
			{
				jQuery("[data-js-id='answersheet_options']").show().removeClass("hide");
			}
		},
		initForm: function(){
			return this.$form = jQuery('#assessmentform');
		},
		calculateTotal: function()
		{
			var total_marks = 0;
			var assessForm =  this.$form;//jQuery("#" + formid);
			jQuery(".subform-repeatable-group", assessForm).each(function(){
				total_marks += jQuery('.param_value', this).val() *  jQuery('.param_weightage', this).val();
			});

			jQuery("#jform_total_marks", assessForm).val(total_marks);
		},
		validate: function(assessForm)
		{
			if (jQuery(assessForm).find("[name='jform[add_assessment]']:checked").val() == 1)
			{
				var count = jQuery(".subform-repeatable-group", assessForm).length;

				if (count == 0)
				{
					Joomla.renderMessages({"error":[Joomla.JText._('COM_TJLMS_ASSESSMENT_MSG_NO_PARAMS')]});
					return false;
				}

				tjlmsAdmin.assessmentform.calculateTotal();

				var total_marks = parseInt(jQuery("#jform_total_marks", assessForm).val());
				var passing_marks = parseInt(jQuery("#jform_passing_marks", assessForm).val());

				if ((isNaN(passing_marks) || passing_marks <= 0) || (passing_marks > total_marks))
				{
					jQuery("input[name='jform[passing_marks]']",assessForm).val('');
					jQuery("input[name='jform[passing_marks]']",assessForm).focus();

					Joomla.renderMessages({"error":[Joomla.JText._('COM_TJLMS_ASSESSMENT_MARKS_MISMATCH')]});
					return false;
				}

				var noOfAssessmentField = jQuery(assessForm).find("[name$='[assessment_attempts]']");
				var newNoOfAssessment = jQuery(assessForm).find("[name$='[assessment_attempts]']").val();
				var oldNoOfAssessment = jQuery(assessForm).find('#no_assessment').val();
				var maxAssessmentDone = jQuery(assessForm).find('#max_assessment').val();

				if (isNaN(newNoOfAssessment))
				{
					jQuery(noOfAssessmentField).val(0);
				}

				// Check if number of assessment is less than original number of assessment
				if (newNoOfAssessment !== 0 && newNoOfAssessment < maxAssessmentDone)
				{
					var msg = Joomla.JText._('COM_TJLMS_MIN_NO_OF_ASSESSMENT_VALIDATION_MSG').replace("%s", maxAssessmentDone);
					Joomla.renderMessages({"error":[msg]});
					jQuery(noOfAssessmentField).val(oldNoOfAssessment).focus();
					return false;
				}
			}
			else
			{
				jQuery("input[name='jform[passing_marks]']",assessForm).val('');
				jQuery("input[name='jform[total_marks]']",assessForm).val('');
			}

			return true;
		}
	},
	initialize: function(){
		var that = this;
		jQuery(window).on("load", function (){

			if(that.lessonFormatForm.initForm().length)
			{
				var format = jQuery(that.lessonFormatForm.$form).find("input[name$='[format]']").val();
				
				jQuery(".subformat").hide().addClass("d-none");
				jQuery("." + format + "_subformat[data-subformat='"+ jQuery("[name$='[subformat]']").val() +"']").show().removeClass("d-none");

				jQuery("[name$='[subformat]']").change(function(){
					that.lessonFormatForm.showSubformat(format, jQuery(this).val());
				})
			}

			if(that.associateFileForm.initForm().length)
			{
				that.associateFileForm.init();
			}


			if(that.assessmentform.initForm().length)
			{
				that.assessmentform.init();
			}
		});
		return this;
	},
	certificateTemplate : {
		editor :'',
		usedCertCount :'',
		init: function() {
			var usedCertCount = this.usedCertCount;
			jQuery(document).ready(function(){
				jQuery("#jformcertificatetemplate").change(function(event){
					tjlmsAdmin.certificateTemplate.loadTemplate();
					});
					if (usedCertCount >0){
					jQuery("#jform_access0").attr('disabled' , true);
					jQuery("#jform_access").parent(".controls").append("<div class='alert alert-info'>" + Joomla.JText._('COM_TJLMS_CERTIFICATE_ACCESS_MSG') + "</div>");
				}
				Joomla.submitbutton = function (task) {
					if (task == 'certificatetemplate.cancel') {
						Joomla.submitform(task, document.getElementById('certificatetemplate-form'));
					}
					else {

						if (task != 'certificatetemplate.cancel' && document.formvalidator.isValid(document.id('certificatetemplate-form'))) {
							Joomla.submitform(task, document.getElementById('certificatetemplate-form'));
						}
					}
				}
			});
		},
		loadTemplate: function() {
			var url= Joomla.getOptions('system.paths').base + "/index.php?option=com_tjlms&task=certificatetemplate.loadtemplate";
			var formData = {'id' : jQuery("#jformcertificatetemplate").val()};
			var promise = tjService.postData(url,formData);

			promise.fail(
				function(response) {
					var messages = { "error": [response.responseText]};
					Joomla.renderMessages(messages);
				}
			).done(
				function(response) {
					if (!response.success && response.message){
							var messages = { "error": [response.message]};
							Joomla.renderMessages(messages);
						}

					else{

						if(this.editor=='tinymce' ||  this.editor=='jce')
						{
							jQuery("iframe").contents().find("body#tinymce").html(response.data.body);
						}
						else
						{
							/*cke_show_borders*/
							jQuery("iframe").contents().find("body").html(response.data.body);
						}

						jQuery('#jform_body').val(response.data.body);
						/*change text area value*/
						jQuery("#jform_template_css").val(response.data.template_css);

						/*programatically click on toggle editor twice to have better template preview*/
						if(this.editor=='tinymce' ||  this.editor=='jce')
						{
							tinyMCE.execCommand('mceToggleEditor', false, 'response.data.body');
							tinyMCE.execCommand('mceToggleEditor', false, 'response.data.body');
						}
					}
				}
			);
			}
	},
	tools : {
		startLimit : 0,
		init: function() {
			jQuery(window).on("load", function (){
				var courseId = jQuery('#filter_course_names').val();
				if (courseId)
				{
					tjlmsAdmin.tools.showEnrolledUsers(courseId);
				}
			});
		},
		calculateCourseProgress: function(flag, totalCount) {
			var courseId = jQuery('#filter_course_names').val();

			if (flag && totalCount)
			{
				var msg = Joomla.JText._('COM_TJLMS_TOOLS_COMPLETED_SUCCESSFULLY');
				tjlmsAdmin.tools.uploadProgressBar(totalCount, msg);
			}
			else{
				tjlmsAdmin.tools.startLimit = 0;
			}

			tjlmsAdmin.tools.sendAjaxRequest(tjlmsAdmin.calculateCourseUrl, courseId, tjlmsAdmin.tools.startLimit);
		},
		sendAjaxRequest: function(url, courseId, startLimit) {
			jQuery.ajax({
				url: url,
				data:{courseId:courseId, batchsize:batchsize, startLimit:startLimit},
				type: "POST",
				success: function(response) {
					var responseData = JSON.parse(response);
					var data = responseData.data;
					if (responseData.success)
					{
						if (data.flag)
						{
							tjlmsAdmin.tools.calculateCourseProgress(data.flag, data.totalEnrolledUsers);
						}
					}
					else
					{
						Joomla.renderMessages({"error":[responseData.message]});
					}
				},
				error: function(response) {
					Joomla.renderMessages({"error":[Joomla.JText._('COM_TJLMS_CALCULATE_COURSE_PROGRESS_FAILURE')]});
				}
			});
		},
		showEnrolledUsers: function(course_id) {
			jQuery.ajax({
				url:tjlmsAdmin.showEnrolledUsersUrl,
				type:"POST",
				data:{cid:course_id},
				success: function (result) {
					var enrolledUserData = JSON.parse(result);
					if (course_id && enrolledUserData.data > 0)
					{
						jQuery("#enrolled_user_notice").show().html(Joomla.JText._('COM_TJLMS_CALCULATE_COURSE_TRACK_ENROLLED_USER_COUNT').replace("%s", enrolledUserData.data));
						jQuery("#recalculate").removeClass('inactiveLink');
						jQuery("#recalculate").removeClass('disabled');
					}
					else
					{
						jQuery("#enrolled_user_notice").hide();
						jQuery("#recalculate").addClass('inactiveLink');
						jQuery("#recalculate").addClass('disabled');
					}
				}
			});
		},
		uploadProgressBar: function(toatalCount, msg){
			/** global: startLimit */
			jQuery('.progressbar').removeClass('hide');
			tjlmsAdmin.tools.startLimit = parseInt(tjlmsAdmin.tools.startLimit) + parseInt(batchsize);
			var percentage = Math.round((tjlmsAdmin.tools.startLimit * 100)/toatalCount);

			if(batchsize > toatalCount)
			{
				percentage = 100;
			}

			jQuery(".progressbar").html('<div class="center alert alert-info"><strong>'+Joomla.JText._('COM_TJLMS_TOOLS_PROGRESS_MESSAGE')+'</strong></div><div class="progress progress-striped active"><div class="bar progress-bar progress-bar-striped active" style="width: '+percentage+'%;">'+percentage+'%</div><div>');
			if (percentage >= 100)
			{
				jQuery('.progressbar').addClass('hide');
				Joomla.renderMessages({"success":[msg]});
			}
		}
	},
	dashboard: {
		downloadArabicLib: function(){
			jQuery.ajax({
				url: 'index.php?option=com_tjlms&task=dashboard.downloadArabicLib&tmpl=component',
				type: 'POST',
				dataType:'json',
				async:false,
				beforeSend: function() {Joomla.loadingLayer('show');},
				success: function(response)
				{
					if (response.data == true)
					{
						Joomla.renderMessages({
		                'success': [response.message]});
					}
					else
					{
						Joomla.renderMessages({
		                'error': [response.message]});
					}

					Joomla.loadingLayer('hide');
				}
			});
		}
	}
};
tjlmsAdmin.initialize();

		tjlmsAdmin.stepform.init("<?php echo $this->ifintmpl;?>", 1);
		tjlmsAdmin.lesson.init(
			"<?php echo $this->ifintmpl;?>",
			"<?php echo $this->courseId;?>",
			"<?php echo $this->params->get('lesson_upload_size', '0', 'INT'); ?>",
			"<?php echo $redirectURL;?>"
			);
	</script>
	<script>
        jQuery(document).ready(function() {
            jQuery(".nav-link[href='#format']").on("click", function() {
              if (!jQuery('#jform_cluster_id_chosen').val() && !jQuery('#jform_title').val())
              {
              	jQuery(".nav-link[href='#format']").removeClass('active');
              	jQuery('#format').removeClass('active');
              	jQuery(".nav-link[href='#general']").addClass('active');
              	jQuery('#general').addClass('active');
              	setTimeout(function(){jQuery('.form-control-feedback').hide();},300);

              }                
            });

            jQuery(".nav-link[href='#interaction']").on("click", function() {
              if (!jQuery('#jform_cluster_id_chosen').val() && !jQuery('#jform_title').val())
              {
              	jQuery(".nav-link[href='#interaction']").removeClass('active');
              	jQuery('#interaction').removeClass('active');
              	jQuery(".nav-link[href='#general']").addClass('active');
              	jQuery('#general').addClass('active');
              }
              else{
              
                if (jQuery(".alert.alert-success").length == 0) {
										jQuery(".nav-link[href='#format']").Class('active');
              	    jQuery('#format').addClass('active');
              	 }
              	 else{
              	 	jQuery('#button_save_and_close').removeClass('hide');
              	 } 
            };
        });
             });
    </script>
	<?php
}
