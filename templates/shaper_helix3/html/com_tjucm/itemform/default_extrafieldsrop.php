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
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;

HTMLHelper::script('media/com_dpe/js/tjucm.js');
Text::script('COM_TJUCM_ROP_ITEM_FORM_NEXT_DATE_REVIEW_VALIDATION_MESSAGE');
$ucmConfigs = ComponentHelper::getParams('com_tjucm');
$useTooltip = $ucmConfigs->get('enable_custom_tooltip');
// Call js file to update the link to ticket 
HTMLHelper::script('media/com_dpe/js/logsticket.js');
$fieldsets_counter = 0;
//~ $layout  = Factory::getApplication()->input->get('layout');
Factory::getApplication()->input->set('extralayout', "rop");

$app        = Factory::getApplication();
$baseUrl    = $app->input->server->get('REQUEST_URI', '', 'STRING');
$calledFrom = (strpos($baseUrl, 'administrator')) ? 'backend' : 'frontend';
$layouts    = new FileLayout('feedbackformfield', JPATH_SITE . '/templates/shaper_helix3/html/layouts/com_tjucm/form');
Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
$ucmDataTable = Table::getInstance('data', 'TjucmTable', array('dbo', $db));
$ucmDataTable->load(array('id' => $this->item->id));
$clusterId = $ucmDataTable->cluster_id;

JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
$params = DPE::config();
 $genericClusterId = (String) $params->get('cluster_id');

 $user = Factory::getUser();	
 $params     			    = ComponentHelper::getParams('com_multiagency');
 $orgAdmin           		= (int) $params->get('multiagency_school_admin_group', '0', 'INT');
 $orgAdmin 		   			= in_array($orgAdmin, $user->groups);
 $orgStaff           		=  in_array((int) $params->get('member_role_id', '0', 'INT'), $user->groups);

if(($clusterId == $genericClusterId) && ($orgAdmin))
{
	?>

<script>
	jQuery('document').ready(function(){

		jQuery("#jform_com_tjucm_rop_clusterclusterid_chosen .chosen-single").empty();
		jQuery("#jform_com_tjucm_rop_clusterclusterid_chosen .chosen-drop").empty();
		jQuery("input[name='jform[com_tjucm_rop_clusterclusterid]']").val("<?php echo $genericClusterId;?>");
		jQuery('input, :radio, :checkbox, select, textarea').prop('disabled', true);
		jQuery('.chosen-results').css('display', 'none');
		jQuery('.search-choice-close').css('display', 'none');
		jQuery('button').removeAttr('disabled');

	})
</script>

<?php }

if ($this->item->id)
{
	$itemState = ($this->item->draft && ($this->allow_auto_save || $this->allow_draft_save)) ? 1 : 0;
}
else
{
	$itemState = ($this->allow_auto_save || $this->allow_draft_save) ? 1 : 0;
}

$editeForm = false;
if ($this->form_extra)
{
	// Iterate through the normal form fieldsets and display each one
	$fieldSets = $this->form_extra->getFieldsets();

	foreach ($fieldSets as $fieldset)
	{
		if (count($fieldSets) > 1)
		{
			if ($fieldsets_counter == 0)
			{
				//echo '<div class="tab-content-form-cover">';
				echo HTMLHelper::_('bootstrap.startTabSet', 'tjucm_myTab',array('active' => OutputFilter::stringURLUnicodeSlug(trim($fieldset->name))));
			}
			$fieldsets_counter++;

			if (count($this->form_extra->getFieldset($fieldset->name)))
			{
				foreach ($this->form_extra->getFieldset($fieldset->name) as $field)
				{
					if (!$field->hidden && $field->type != 'Note')
					{
						$tabName = OutputFilter::stringURLUnicodeSlug(trim($fieldset->name));
						echo HTMLHelper::_("bootstrap.addTab", "tjucm_myTab", $tabName, $fieldset->name);
						break;
					}
				}
			}
		}
		?>

			<div id="item-form" >
				<div class="overlay" id="tjucm_loader" style="display:none;">
					<div class="loader"></div>
				</div>
			</div>

		<div class="form-horizontal clear-both pull-left pb-10 pt-25 w-100 dp-rop-form row px-3">
			<?php
			// Iterate through the fields and display them
			if ($fieldset->name == 'Legal & Summary')
			{
				//echo HTMLHelper::_('bootstrap.startTabSet', 'tjucm_myTab1');
			}


			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
			$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');

			$fieldArray =array();


			//	Only load first tab in i.e()'Description' tab)

			if ($this->id == 0 || ($this->id != 0 && !$editeForm))
			{
				
			foreach ($this->form_extra->getFieldset($fieldset->name) as $field)
			{
				if (!empty($field->getAttribute('tags')))
				{
					$temp     = new TagsHelper;
					$tagnames = $temp->getTagNames(array(
						$field->getAttribute('tags')
					));

					if (array_key_exists($fieldsArray, (array) $tagnames[0]))
					{
						$fieldArray[$tagnames[0]][] = $field;
					}
					else
					 {
						$fieldArray[$tagnames[0]][] = $field;
					  }
				}
				else
				{
					$fieldArray[] = $field;
				}

			}

			foreach ($fieldArray as $key=> $field)
			{?>

				<?php
				if (is_array($field))
				{

				$i =0;
				?>
				<div class="clearfix"></div>
				<div class="accordion" id="accordion<?php echo $i++; ?>"><?php echo  ucwords(str_replace('_', ' ', $key)); ?></div>
				<div id="pan" class="panel row" style="display: none;">
				<?php foreach($field as $fieldTag)
				{
					$description = $fieldTag->description;

					$tjFieldFieldTable->load(array('name' => $fieldTag->fieldname));

					$isUcmsubform = 0;

					if ($field->type == 'Ucmsubform')
					{
						$customColClass = 'col-md-12 ucmsubform';
						if($field->max == 1)
					{
					?>

					<script>
						jQuery(document).ready(function(){

							jQuery('.btn-toolbar').hide();
						})
					</script>

				<?php }
					}
					elseif (!empty($tjFieldFieldTable->validation_class) && strpos(trim($tjFieldFieldTable->validation_class), 'ucm-full-width') !== false)
					{
						$customColClass = 'col-md-12';
					}
					else
					{
						$customColClass = 'col-md-4';
					}

					if (strpos($fieldTag->class, 'twoColumnUcmsubform') !== false)
					{
						$isUcmsubform   = 0;
					}


					if (!$fieldTag->hidden)
					{
						$className = ($fieldTag->type == 'Spacer') ? 'w-100' : '';

						if ($fieldTag->type == 'Note' && $fieldset->name == 'Legal & Summary')
						{
							echo '<div class="legal-summary-fieldset mb-20 mx-15"><div class="row">';
						}

						if ($fieldTag->type == 'Spacer' && $fieldset->name == 'Legal & Summary')
						{
							echo '</div></div>';
							continue;
						}
					?>
					<div class="col-xs-12 <?php echo $customColClass . ' ' . $className;  ?> custom-form-style dataflow-tab">
						<div class="form-group">
							<?php
							if($useTooltip)
							{
								$fieldTag->description = '';
							}

						  if ($fieldTag->type != 'Note'): ?>
										<?php if($useTooltip && $description && $fieldTag->type != 'Ucmsubform'){
										$fieldTag->description = $description;
								}?>

								<?php
								if ($fieldTag->type != 'Ucmsubform')
								{
									echo $fieldTag->renderField(); 
								}
								else
								{
									?>
									<div class="col-sm-12 rop-inputs w-100">
									<?php echo $fieldTag->input; ?>
									</div>
									<?php
								}
								?>
								
								<script>
									<?php if ($fieldTag->type == 'Checkbox'){?>
										jQuery('#<?php echo $fieldTag->id;?>').addClass('d-block');
									<?php } ?>
								</script>
								
								<div class="<?php echo 'col-sm-10';?>">
									  <?php 
											echo $layouts->render($fieldTag);
									   ?>
								</div>

								<?php elseif ($fieldTag->type == 'Note' && $fieldset->name == 'Legal & Summary'):

									switch ($fieldTag->fieldname)
									{
										case "com_tjucm_rop_Lawfulbasis":
											echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>Lawful basis</strong></h4></div>';
											break;
										case "com_tjucm_rop_DataSubjects":
											echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'Data Subjects'.'</strong></h4></div>';
											break;
										case "com_tjucm_rop_Accuracy":
											echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'Accuracy'.'</strong></h4></div>';
											break;
										case "com_tjucm_rop_Destination":
											echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'Destination'.'</strong></h4></div>';
											break;
										case "com_tjucm_rop_Retention":
											echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'Retention'.'</strong></h4></div>';
											break;
										case "com_tjucm_rop_ImpactAssessment":
											echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'Impact Assessment'.'</strong></h4></div>';
											break;
										case "com_tjucm_rop_StatusandAttachment":
											echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'Status and Attachment'.'</strong></h4></div>';
											break;
										case "com_tjucm_rop_FileUpload":
											echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'File Upload'.'</strong></h4></div>';
											break;
									}

									?>
								<?php endif; ?>

								<?php
								// TODO :- Check and remove
								if ($fieldTag->type == 'File')
								{
									if ($this->copyRecId)
									{
										$fieldTag->setValue('');
									}

									?>
									<script type="text/javascript">
										jQuery(document).ready(function ()
										{
											var fieldValue = "<?php echo $fieldTag->value; ?>";
											var AttrRequired = jQuery('#<?php echo $fieldTag->id;?>').attr('required');
											if (typeof AttrRequired !== typeof undefined && AttrRequired !== false)
											{
												if (fieldValue)
												{
													jQuery('#<?php echo $fieldTag->id;?>').removeAttr("required");
													jQuery('#<?php echo $fieldTag->id;?>').removeClass("required");
												}
											}
										});
									</script>
								<?php
								}
								?>
<!--
								<?php //if ($fieldTag->type != 'Note'): ?>
								<div class="col-sm-12 rop-inputs w-100">
									<?php //echo $fieldTag->input; ?>
								</div>
-->
								
								<?php //endif; ?>
								
						</div>
					</div>

				<?php
				}

			}?>
</div>
<div class="clearfix"></div>
<div class="clearfix"></div>

<?php
				}?>


				<?php
				if (!is_array($field))
				{?>

				<?php
				$description = $field->description;

				$tjFieldFieldTable->load(array('name' => $field->fieldname));

				$isUcmsubform = 0;

				if ($field->type == 'Ucmsubform')
				{
					$customColClass = 'col-md-12 ucmsubform';
					if($field->max == 1)
					{
					?>

					<script>
						jQuery(document).ready(function(){

							jQuery('.btn-toolbar').hide();
						})
					</script>

				<?php }
				}
				elseif (!empty($tjFieldFieldTable->validation_class) && strpos(trim($tjFieldFieldTable->validation_class), 'ucm-full-width') !== false)
				{
					$customColClass = 'col-md-12';
				}
				else
				{
					$customColClass = 'col-md-4';
				}

				if (strpos($field->class, 'twoColumnUcmsubform') !== false)
				{
					$isUcmsubform   = 0;
				}


				if (!$field->hidden)
				{
					$className = ($field->type == 'Spacer') ? 'w-100' : '';

					if ($field->type == 'Note' && $fieldset->name == 'Legal & Summary')
					{
						echo '<div class="legal-summary-fieldset mb-20 mx-15"><div class="row">';
					}

					if ($field->type == 'Spacer' && $fieldset->name == 'Legal & Summary')
					{
						echo '</div></div>';
						continue;
					}
				?>
					<div class="col-xs-12 <?php echo $customColClass . ' ' . $className;  ?> custom-form-style dataflow-tab">
						<div class="form-group">
								<?php

                  	if($useTooltip)
					{
						$field->description = '';
					}

                  if ($field->type != 'Note'): ?>
				 				<?php if($useTooltip && $description && $field->type != 'Ucmsubform'){
										$field->description = $description;
								}?>

								<?php
								if ($field->type != 'Ucmsubform')
								{
									echo $field->renderField(); 
								}
								else
								{
									?>
									<div class="col-sm-12 rop-inputs w-100">
									<?php echo $field->input; ?>
									</div>
									<?php
								}
								?>
								
								<script>
									<?php if ($field->type == 'Checkbox'){?>
										jQuery('#<?php echo $field->id;?>').addClass('d-block');
									<?php } ?>
								</script>
								
								<div class="<?php echo 'col-sm-10';?>">
									  <?php 
											echo $layouts->render($field);
									   ?>
								</div>

								<?php elseif ($field->type == 'Note' && $fieldset->name == 'Legal & Summary'):

									switch ($field->fieldname)
									{
										case "com_tjucm_rop_Lawfulbasis":
											echo '<div class="col-12 custom-form-style "  id="'.$field->value.'"><h4><strong>Lawful basis</strong></h4></div>';
											break;
										case "com_tjucm_rop_DataSubjects":
											echo '<div class="col-12 custom-form-style "  id="'.$field->value.'"><h4><strong>'.'Data Subjects'.'</strong></h4></div>';
											break;
										case "com_tjucm_rop_Accuracy":
											echo '<div class="col-12 custom-form-style "  id="'.$field->value.'"><h4><strong>'.'Accuracy'.'</strong></h4></div>';
											break;
										case "com_tjucm_rop_Destination":
											echo '<div class="col-12 custom-form-style "  id="'.$field->value.'"><h4><strong>'.'Destination'.'</strong></h4></div>';
											break;
										case "com_tjucm_rop_Retention":
											echo '<div class="col-12 custom-form-style "  id="'.$field->value.'"><h4><strong>'.'Retention'.'</strong></h4></div>';
											break;
										case "com_tjucm_rop_ImpactAssessment":
											echo '<div class="col-12 custom-form-style "  id="'.$field->value.'"><h4><strong>'.'Impact Assessment'.'</strong></h4></div>';
											break;
										case "com_tjucm_rop_StatusandAttachment":
											echo '<div class="col-12 custom-form-style "  id="'.$field->value.'"><h4><strong>'.'Status and Attachment'.'</strong></h4></div>';
											break;
										case "com_tjucm_rop_FileUpload":
											echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'File Upload'.'</strong></h4></div>';
											break;
									}

									?>
								<?php endif; ?>

								<?php
								// TODO :- Check and remove
								if ($field->type == 'File')
								{
									if ($this->copyRecId)
									{
										$field->setValue('');
									}

									?>
									<script type="text/javascript">
										jQuery(document).ready(function ()
										{
											var fieldValue = "<?php echo $field->value; ?>";
											var AttrRequired = jQuery('#<?php echo $field->id;?>').attr('required');
											if (typeof AttrRequired !== typeof undefined && AttrRequired !== false)
											{
												if (fieldValue)
												{
													jQuery('#<?php echo $field->id;?>').removeAttr("required");
													jQuery('#<?php echo $field->id;?>').removeClass("required");
												}
											}
										});
									</script>
								<?php
								}
								?>
<!--
								<?php //if ($field->type != 'Note'): ?>
								<div class="col-sm-12 rop-inputs w-100">
									<?php //echo $field->input; ?>
								</div>
-->
								
								<?php //endif; ?>
								
						</div>
					</div>

				<?php
				}
			}
		}
	}

			?>
		</div>
		<div class="clearfix"></div>

		<?php

		if (count($fieldSets) > 1)
		{
			if (count($this->form_extra->getFieldset($fieldset->name)))
			{
				foreach ($this->form_extra->getFieldset($fieldset->name) as $field)
				{
					if (!$field->hidden && $field->type != 'Note')
					{
						echo HTMLHelper::_("bootstrap.endTab");
						?>

				<?php
					}
					break;
				}
			}
		}
		if ($this->id != 0 && $this->item->id != 0) {
			$editeForm = true;
		}
	}


	if (count($fieldSets) > 1)
	{
		echo HTMLHelper::_('bootstrap.endTabSet');
	}

}
else
{
?>
	<div class="alert alert-info">
		<?php echo Text::_('COM_TJLMS_NO_EXTRA_FIELDS_FOUND');?>
	</div>
<?php
}?>					<?php 
    	    		// Check the log has link field to create ticket
					Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
		  			$typeDetails = Table::getInstance('Type', 'TjucmTable');
		   			$typeDetails->load(array('unique_identifier' => $this->client));
		   			$ticketConditionData = json_decode(json_decode($typeDetails->params)->type_options);
					
					// Get the id of link text box check that field is present or not.
					Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
 					$fieldTable = Table::getInstance('field', 'TjfieldsTable');
 					$fieldTable->load(array('name'=>$ticketConditionData->linkField));
 				?>

						<div class="form-actions buttons-mobile-view border-0 bg-none action-btns mb-20 mt-10 mr-20">
							<span class="pull-right">
							<?php
							if (($this->allow_auto_save || $this->allow_draft_save) && $itemState)
							{
								?>
								<input type="button" class="btn btn-default px-25 mobile-space" id="tjUcmSectionDraftSave"
								value="<?php echo Text::_("COM_TJUCM_SAVE_AS_DRAFT_ITEM"); ?>"
								onclick="tjUcmItemForm.saveUcmFormData();" />
								<?php
							}
							?>
							<input type="button" class="btn btn-primary px-25 mobile-space" value="<?php echo Text::_('COM_TJUCM_SAVE_ITEM'); ?>" id="tjUcmSectionFinalSave" onclick="tjUcmItemForm.saveUcmFormData();<?php if ($fieldTable->id){?>updateLinkField();<?php }?>" />

							<input type="button" class="btn btn-primary px-25 mobile-space" value="<?php echo Text::_("COM_TJUCM_SAVE_CLOSE_ITEM"); ?>" id="tjUcmSectionFinalSaveClose" onclick="tjUcmItemForm.saveUcmFormData();" />
							<input type="button" class="btn btn-warning mobile-space" value="<?php echo Text::_('COM_TJUCM_CANCEL_BUTTON'); ?>" onclick="Joomla.submitbutton('itemform.cancel');" />
							</span>
							<div class="clearfix"></div>
						</div>

<?php
// DPE - Hack - To copy the record
if ($this->copyRecId)
{
?>
<script type="text/javascript">
		jQuery(document).ready(function ()
	{
		// Check record id is empty and user tried to copy record
		if (jQuery.trim(jQuery('#recordId').val()) == '' || jQuery('#recordId').val() == undefined)
		{
			// Find the all parent contentid fields of subforms
			jQuery('.ucmsubform').find("input[name*='_contentid']").each(function(){

				// Check the field type is hidden and confirm its parent reference number
				if (jQuery(this).attr('type') == 'hidden')
				{
					// Reset the field value if trying to copy the record
					jQuery(this).val('');
				}
			});
		}
	});
</script>
<?php
}

// DPE - Hack - End
?>
<script type="text/javascript">
jQuery(document).on('subform-row-add', function(event, row){
	
	jQuery(".subform-repeatable-group>div.control-group>div.control-label").removeClass("col-sm-12 w-100 text-left");

	jQuery("[data-toggle=tooltip]").tooltip();
 jQuery(".panel.row").css("display", "none");

		var subformname = event.detail.row.getAttribute('data-group');
	accordionCount = jQuery(event.detail.row).find(".accordion");

		for (i = 0; i < accordionCount.length; i++) {

		jQuery(event.detail.row).find(".accordion").addClass(subformname+i);

		  accordionCount[i].addEventListener("click", function() {
			  
		 this.classList.toggle("active");

			var panel = this.nextElementSibling;
			if (panel.style.display === "flex") {
			  panel.style.display = "none";
			} else {
			  panel.style.display = "flex";
			}
		  });
		} 
	});

jQuery(document).ready(function() {

jQuery(".subform-repeatable-group>div.control-group>div.control-label").removeClass("col-sm-12 w-100 text-left");
 jQuery(".panel.row").css("display", "none");

var acc = document.getElementsByClassName("accordion");
var i;

for (i = 0; i < acc.length; i++) {
  acc[i].addEventListener("click", function() {
 this.classList.toggle("active");

    var panel = this.nextElementSibling;
    if (panel.style.display === "block") {
      panel.style.display = "none";
    } else {
      panel.style.display = "block";
    }
  });
}
});
</script>

<script type="">

	jQuery(document).ready(function($){
			 
		var dataShowonFields = jQuery("#item-form").find("div[data-showon]");
					
					dataShowonFields.each(function() {
						var value = jQuery(this).data("showon");
						var fieldName = value[0].field;
						var signVal = value[0].sign;

						var inputValue = jQuery("select[name=\'"+ fieldName +"[]\']").val();
						if (inputValue == null && (signVal == "!="))
						{
							jQuery(this).css("display", "");
						}
					});
					jQuery("select[multiple]").on("change", function () {

						dataShowonFields.each(function() {
						var value = jQuery(this).data("showon");
						var fieldName = value[0].field;
						var inputValue = jQuery("select[name=\'"+ fieldName +"[]\']").val();
						var signVal = value[0].sign;
                                            
						if (inputValue == null && signVal == "!=")
						{ 
							jQuery(this).css("display", "");
						}
					});

						});
				});
</script>
<script>
	function updateLinkField()
	{
			if ("<?php echo $fieldTable->id?>".length > 1)
			{
				var recordId = jQuery('#recordId').val();
				logticket.afterSaveLinkFieldUpdate(recordId+','+ <?php echo ($fieldTable->id)?$fieldTable->id:0?>)
			}
	}

	

	jQuery('select').change(function() {
	  var selectEl = jQuery(this);
	  setTimeout(function() {
	    jQuery('div[data-showon]').each(function() {
		  var id = selectEl.attr('id').replace('jform_','');
		   var styleElem = jQuery(this).attr('style');
		   
	      var dataAttr = jQuery(this).attr('data-showon');
	      var regexPattern = 'jform['+id+']';

	      if (dataAttr.indexOf(regexPattern) !== -1) {
	        var nextDivId = jQuery(this).closest('div').find('label').attr('id');
	        var divID = nextDivId.replace('jform_', '').replace('-lbl', '');

	       if (divID != id && styleElem == 'display: none;' )
		      {
		  			 jQuery('.'+divID).hide(); // hide the div
			  } else if(divID != id && styleElem == 'display: block;') {
			  	
			   jQuery('.'+divID).show(); // show the div
				}
	      }
	    });
	  }, 500);
	});

jQuery(document).ready(function(){
	var selectEl = jQuery(this);
  jQuery('div[data-showon]').each(function(index) {

    var dataAttr = jQuery(this).attr('data-showon');
    var styleElem = jQuery(this).attr('style');
    var json = JSON.parse(dataAttr);
	var field = json[0].field;
    var regexPattern = field;

    if (dataAttr.indexOf(regexPattern) !== -1) {

      var nextDivId = jQuery(this).closest('div').find('label').attr('id');
      var divID = nextDivId.replace('jform_', '').replace('-lbl', '');

      if (styleElem == 'display: none;' )
      {
  			 jQuery('.'+divID).hide(); // hide the div
	  } else {
	  	
	   jQuery('.'+divID).show(); // show the div
		}
    }
  });
});

// For redirection after user submit and Exit the form
document.getElementById("tjUcmSectionFinalSaveClose").addEventListener("click", function () {
    document.getElementById("tjucm_action_mode").value = "submitExit";
});
</script>

<?php
if($this->item->id !=0 || $this->id !=0){
?>
<script>
// Handles lazy-loading of tab content via AJAX in the DPE component.
jQuery(function ($) {
  $(document).on('shown.bs.tab', 'a[data-bs-toggle="tab"]', function (e) {
    const $targetTabLink = $(e.target);
    const href = $targetTabLink.attr('href'); // e.g. "#details"
    const tabText = $targetTabLink
      .contents()
      .filter(function () {
        return this.nodeType === 3; // Only text nodes
      })
      .text()
      .trim();
    const $container = $(href);

    // PHP variables injected from server-side template
    const copyRecID = <?php echo (int) $this->copyRecId; ?>;
    const client    = "<?php echo $this->client; ?>";
    const contentId = <?php echo (int) $this->id; ?>;
    const itemId    = <?php echo (int) Factory::getApplication()->input->getInt('Itemid'); ?>;
    const clusterId = <?php echo (int) $clusterId; ?>;

    // Skip if already loaded before
    if ($container.hasClass('loaded')) return;

    // Show loading spinner
    $container.html(	'<div id="item-form">'+
				'<div class="" id="tjucm_loader">'+
					'<div class="loader"></div>'+
				'</div>'+
			'</div>');

    // Perform AJAX request to load fieldset HTML
            let joomlaRoot = Joomla.getOptions("system.paths").root;

    $.ajax({
      url: joomlaRoot+'/index.php?option=com_dpe&task=tjucm.loadTabFields',
      type: 'POST',
      data: {
        fieldset: tabText,
        client: client,
        copyRecID: copyRecID,
        content_id: contentId,
        clusterId: clusterId,
        itemid: itemId
      },
      success: function (response) {
        // Insert HTML into the tab container
        $container.html(response).addClass('loaded');

        $container.find('script').each(function () {
          const code = this.text || this.textContent || this.innerHTML || '';
          if (code) {
            try {
              $.globalEval(code);
            } catch (e) {
              console.error("Script execution failed:", e);
            }
          }
        });

        // Joomla 4+ WebComponent re-initialization
        if (window.Joomla?.WebComponent?.upgradeAll) {
          window.Joomla.WebComponent.upgradeAll($container[0]);
        }

        // Re-initialize chosen dropdowns if available
        if ($.fn.chosen) {
          $container.find('select').each(function () {
            if (!$(this).parent().hasClass('chosen-container')) {
              $(this).chosen();
            }
          });
        }

        if (typeof Joomla !== 'undefined' && Joomla.initMultiField) {
          $container.find('.subform-repeatable').each(function () {
            Joomla.initMultiField(this);
          });
        }

        // Enable Bootstrap tooltips
        if ($.fn.tooltip) {
          $container.find('[data-toggle=tooltip]').tooltip();
        }
      },
      error: function () {
        $container.html('<div class="alert alert-danger">Failed to load content.</div>');
      }
    });
  });

  // Mark the first (active) tab as already loaded
  $('.tab-pane.active').addClass('loaded');
});

</script>
<?php
}
?>