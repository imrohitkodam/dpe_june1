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
use Joomla\CMS\Uri\Uri;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Helper\TagsHelper;

HTMLHelper::script('media/com_dpe/js/tjucm.js');
Text::script('COM_TJUCM_ROP_ITEM_FORM_NEXT_DATE_REVIEW_VALIDATION_MESSAGE');
Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjucm/tables');
Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
JLoader::import('components.com_tjfields.helpers.tjfields', JPATH_ADMINISTRATOR);
HTMLHelper::_('script','media/com_dpe/js/dpefeedbacksubform.js');
Factory::getApplication()->input->set('extralayout', "vendors");

Text::script('COM_DPE_TICKET_GENERATION_FAIL');
Text::script('COM_DPE_TICKET_GENERATION_SUCCESS');
Text::script('COM_DPE_TICKET_FIELD_REQUIRED');

// Call js file to update the link to ticket 
HTMLHelper::script('media/com_dpe/js/logsticket.js');
$user = Factory::getUser(); 
  $params     			    = ComponentHelper::getParams('com_multiagency');
 $orgAdmin           		= (int) $params->get('multiagency_school_admin_group', '0', 'INT');
$orgAdmin                   = in_array($orgAdmin, $user->groups);

// Check the log has link field to create ticket
	Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
	$typeDetails = Table::getInstance('Type', 'TjucmTable');
	$typeDetails->load(array('unique_identifier' => $this->client));
	$ticketConditionData = json_decode(json_decode($typeDetails->params)->type_options);
		
	// Get the id of link text box check that field is present or not.
	
	if(isset($ticketConditionData->toUser))
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
	 	$fieldTable = Table::getInstance('field', 'TjfieldsTable');
	 	$fieldTable->load(array('name'=>$ticketConditionData->addticketplace));
	}
	
			
	// Get the id of link text box check that field is present or not.
	if(isset($ticketConditionData->linkField))
	{
	 	$fieldTableLink = Table::getInstance('field', 'TjfieldsTable');
	 	$fieldTableLink->load(array('name'=>$ticketConditionData->linkField,'state'=>1));
    }


$fieldsets_counter  = 0;
$layout             = Factory::getApplication()->input->get('layout');
$params             = ComponentHelper::getParams('com_dpe');
$reverseListClients = explode(",", $params->get('coredataReverseUcmTypes'));
$clusterFieldName   = '';
$app                = Factory::getApplication();
$calledFrom         = (strpos($baseUrl, 'administrator')) ? 'backend' : 'frontend';
$app                = Factory::getApplication();
$tmpl               = $app->input->get('tmpl', '', 'STRING');

$ucmConfigs = ComponentHelper::getParams('com_tjucm');
$useTooltip = $ucmConfigs->get('enable_custom_tooltip');
$layouts  = new FileLayout('feedbackformfield', JPATH_SITE . '/templates/shaper_helix3/html/layouts/com_tjucm/form');

if ($this->item->id)
{
    $itemState = ($this->item->draft && ($this->allow_auto_save || $this->allow_draft_save)) ? 1 : 0;
}
else
{
    $itemState = ($this->allow_auto_save || $this->allow_draft_save) ? 1 : 0;
}

$tjfieldsHelper = new TjfieldsHelper;

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
                echo HTMLHelper::_('bootstrap.startTabSet', 'tjucm_myTab',array('active' => OutputFilter::stringURLUnicodeSlug(trim($fieldset->name))));
            }

            $fieldsets_counter++;

            if (count($this->form_extra->getFieldset($fieldset->name)))
            {
                foreach ($this->form_extra->getFieldset($fieldset->name) as $field)
                {
                    if (!$field->hidden)
                    {
                        $tabName = OutputFilter::stringURLUnicodeSlug(trim($fieldset->name));
                        echo HTMLHelper::_("bootstrap.addTab", "tjucm_myTab", $tabName, $fieldset->name);
                        break;
                    }
                }
            }
        }?>
       <div class="form-horizontal clear-both pull-left pb-10 w-100 dp-rop-form row">
        <?php

        $fieldArray = array();

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

        if (!empty($fieldArray))
        {?>
			<div class="clearfix"></div>
            <?php
				foreach ($fieldArray as $key => $fieldTagarray)
				{
					if (!is_array($fieldTagarray))
					{
						$description = $fieldTagarray->description;

						if($useTooltip)
						{
							$fieldTagarray->description = '';
						}

						$isUcmsubform = 0;

						if ($fieldTagarray->type == 'Ucmsubform')
						{
							$customColClass = 'col-xs-12 col-md-12 ucmsubform';
						}
						else
						{
							$customColClass = 'col-md-4 col-xs-12';
						}

						if (strpos($fieldTagarray->class, 'twoColumnUcmsubform') !== false)
						{
							$isUcmsubform = 0;
						}

						if (!$fieldTagarray->hidden)
						{
							$className = ($fieldTagarray->type == 'Spacer') ? 'w-100' : '';?>
							<div class="<?php echo $customColClass . ' ' . $className;?> custom-form-style">
								<div class="form-group">
									<?php if($useTooltip && $description)
										 {
											 $fieldTagarray->description = $description;
										 }
									 ?>

								   <?php
									// TODO :- Check and remove
									if ($fieldTagarray->type == 'File')
									{
										if ($this->copyRecId)
										{
											$fieldTagarray->setValue('');
										}
										?>
										<script type="text/javascript">
											jQuery(document).ready(function ()
											{
												var fieldValue = "<?php echo $field->value;?>";
												var AttrRequired = jQuery('#<?php echo $fieldTagarray->id;?>').attr('required');
												if (typeof AttrRequired !== typeof undefined && AttrRequired !== false)
												{
													if (fieldValue)
													{
														jQuery('#<?php echo $fieldTagarray->id; ?>').removeAttr("required");
														jQuery('#<?php echo $fieldTagarray->id; ?>').removeClass("required");
													}
												}
											});
										</script>
										<?php
									}?>

									<?php echo $fieldTagarray->renderField();

									if (isset($ticketConditionData->linkField) && $fieldTagarray->value && 'jform_'.$fieldTableLink->name == $fieldTagarray->id)
													{ 
														?>
														<div class="row ticketbtnclass" style="margin-top: -40px;">
															<div class="<?php echo 'col-sm-4';?> float-right" >
															</div>
															<div class="<?php echo 'col-sm-5 mb-10';?> float-right fw-bold" >
															<a href="<?php echo $fieldTagarray->value;?>" target="_blank"><?php echo Text::_('COM_DPE_LOG_TO_TICKET');?> </a>

															</div>
														</div>
														<script type="text/javascript">
															
															jQuery('#<?php echo $fieldTagarray->id;?>').hide();
														</script>

														<?php
													}


									if (isset($ticketConditionData->linkField) && 'jform_'.$fieldTable->name == $fieldTagarray->id && $ticketConditionData->isCreateTicket == 'true' && (!$fieldTagarray->value && $user->authorise('core.manageall', 'com_cluster')))
										{ 
										  	  $ticketConditionDatas = json_encode($ticketConditionData);
											  $ticketConditionDatas = str_replace('"', '&quot;', $ticketConditionDatas);

										  	?>
										  	<div class='float-end ticketbtn'>
										  	<input type="button"  class='btn btn-sm btn-primary ' name="addTicket" id='addTicket' value="<?php echo Text::_('COM_DPE_ADDTICKET')?>" onclick="logticket.addTicketfromUcm('<?php echo $ticketConditionDatas ?>'); <?php if ($fieldTableLink->id){?> updateLinkField();<?php }?>"><br><br><p id='addTicketMessage' class='d-none '></p>
										  </div>
										  	<?php
										  }

										?>
										
									<div class="<?php echo 'col-sm-10';?>">
										<?php 
											echo $layouts->render($fieldTagarray);
										?>
									</div>
									<!-- end of feddback-->
									<div>
										<?php
										if (strpos($fieldTagarray->fieldname, 'clusterclusterid'))
										{
											$clusterFieldName = $fieldTagarray->fieldname;
										}?>
									</div>
								</div>
							</div>
						<?php
						}?>
					<?php
					}
					?>

					<?php
					if (is_array($fieldTagarray))
					{
						$i = 0;
						?>
						<div class="clearfix"></div>
							<div class="accordion" id="accordion<?php echo $i++; ?>">
								<?php
								echo ucfirst(str_replace('_', ' ', $key));
								?>
							</div>
							<div id="pan" class="panel row">
							<?php
							foreach ($fieldTagarray as $fieldTag)
							{
								$isUcmsubform = 0;

								$description = $fieldTag->description;

								if($useTooltip)
								{
									$field->description = '';
								}

								if ($fieldTag->type == 'Ucmsubform')
								{
									$customColClass = 'col-xs-12 col-md-12 ucmsubform';
								}
								else
								{
									$customColClass = 'col-md-4 col-xs-12';
								}

								if (strpos($fieldTag->class, 'twoColumnUcmsubform') !== false)
								{
									$isUcmsubform = 0;
								}

								if (!$fieldTag->hidden)
								{
									$className = ($field->type == 'Spacer') ? 'w-100' : '';?>
									<div class="<?php
										echo $customColClass . ' ' . $className;?> custom-form-style">
                                        <div class="form-group">
										<?php if($useTooltip && $description)
											 {
												 $fieldTag->description = $description;
											 }
										?>
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
														var fieldValue = "<?php  echo $fieldTag->value;?>";
														var AttrRequired = jQuery('#<?php echo $field->id;?>').attr('required');
														if (typeof AttrRequired !== typeof undefined && AttrRequired !== false)
														{
															if (fieldValue)
															{
																jQuery('#<?php echo $fieldTag->id; ?>').removeAttr("required");
																jQuery('#<?php echo $fieldTag->id;?>').removeClass("required");
															}
														}
													});
												</script>
                                                <?php
											}?>
											
									<?php echo $fieldTag->renderField();
									
									if (isset($ticketConditionData->linkField) && $fieldTag->value && 'jform_'.$fieldTableLink->name == $fieldTag->id)
													{ 
														?>
														<div class="row ticketbtnclass" style="margin-top: -40px;">
															<div class="<?php echo 'col-sm-4';?> float-right" >
															</div>
															<div class="<?php echo 'col-sm-5 mb-10';?> float-right fw-bold" >
															<a href="<?php echo $fieldTag->value;?>" target="_blank"><?php echo Text::_('COM_DPE_LOG_TO_TICKET');?> </a>

															</div>
														</div>
														<script type="text/javascript">
															
															jQuery('#<?php echo $fieldTag->id;?>').hide();
														</script>

														<?php
													}

									if (isset($ticketConditionData->linkField) && 'jform_'.$fieldTable->name == $fieldTag->id && $ticketConditionData->isCreateTicket == 'true' && (!$fieldTag->value && $user->authorise('core.manageall', 'com_cluster')))
										{ 
										  	  $ticketConditionDatas = json_encode($ticketConditionData);
											  $ticketConditionDatas = str_replace('"', '&quot;', $ticketConditionDatas);

										  	?>
										  	<div class='float-end ticketbtn'>
										  	<input type="button"  class='btn btn-sm btn-primary ' name="addTicket" id='addTicket' value="<?php echo Text::_('COM_DPE_ADDTICKET')?>" onclick="logticket.addTicketfromUcm('<?php echo $ticketConditionDatas ?>'); <?php if ($fieldTableLink->id){?> updateLinkField();<?php }?>"><br><br><p id='addTicketMessage' class='d-none '></p>
										  </div>
										  	<?php
										  }

									?>

										
									<div class="<?php echo 'col-sm-10';?>">
										<?php 
											echo $layouts->render($fieldTag);
										?>
									</div>
											
												<div>
                                                    <?php
                                                        if (strpos($fieldTag->fieldname, 'clusterclusterid'))
                                                        {
                                                            $clusterFieldName = $fieldTag->fieldname;
                                                        }?>
												</div>
                                        </div>
                                    </div>
								<?php
								}
							}
						?>
					</div>
					<div class="clearfix"></div>
				<?php
                }
            }
        }
		?>
       </div>
        <?php

        /* //if (count($fieldSets) > 1)
        { */
        if (count($this->form_extra->getFieldset($fieldset->name)))
        {

            foreach ($this->form_extra->getFieldset($fieldset->name) as $field)
            {
                if (!$field->hidden)
                {
                    echo HTMLHelper::_("bootstrap.endTab");

                    break;
                }
            }
        }
        //}

    }
?>
<div id="item-form" >
	<div class="overlay" id="tjucm_loader" style="display:none;">
		<div class="loader"></div>
	</div>
</div>
    <div class="form-actions buttons-mobile-view border-0 bg-none action-btns" style="bottom:0;">
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

    if (isset($setnavigation) && $setnavigation == true && empty($tmpl)) {?>
       <!-- <button type="button" class="btn btn-primary mt-20" id="previous_button" >
            <i class="icon-arrow-left-2"></i>
            <?php //echo Text::_('COM_TJUCM_PREVIOUS_BUTTON'); ?>
       </button>
        <button type="button" class="btn btn-primary mt-20" id="next_button" >
            <?php //echo Text::_('COM_TJUCM_NEXT_BUTTON'); ?>
           <i class="icon-arrow-right-2"></i>
        </button> -->
        <?php
    }

    if ($calledFrom == 'frontend') {?>
           <span class="pull-right">
            <?php

        if (($this->allow_auto_save || $this->allow_draft_save) && $itemState) {?>
               <input type="button" class="btn btn-default px-25 mobile-space" id="tjUcmSectionDraftSave"
                value="<?php
            echo Text::_("COM_TJUCM_SAVE_AS_DRAFT_ITEM");?>"
                onclick="tjUcmItemForm.saveUcmFormData();" />
                <?php
        }?>
    	    <?php 
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

            <input type="button" class="btn btn-primary px-25 mobile-space" value="<?php
        echo Text::_('COM_TJUCM_SAVE_ITEM');?>" id="tjUcmSectionFinalSave" onclick="tjUcmItemForm.saveUcmFormData();<?php if ($fieldTable->id){?>updateLinkField();<?php }?>" />

            <?php
        if (empty($tmpl)):?>
           <input type="button" class="btn btn-primary px-25 mobile-space" value="<?php
            echo Text::_("COM_TJUCM_SAVE_CLOSE_ITEM"); ?>" id="tjUcmSectionFinalSaveClose" onclick="tjUcmItemForm.saveUcmFormData();" />
            <input type="button" class="btn btn-warning mobile-space" value="<?php
            echo Text::_('COM_TJUCM_CANCEL_BUTTON');?>" onclick="Joomla.submitbutton('itemform.cancel');" />
            <?php
        endif;?>
           </span>
		   <div class="clearfix"></div>
            <?php
    }?>
</div>
    <?php

    // Check if AI is enabled globally and for this type
    $dpeParams = \Joomla\CMS\Component\ComponentHelper::getParams('com_dpe');
    $enableAi = $dpeParams->get('enable_ai', 0);
    
    // Load type params
    $db = Factory::getDbo();
    $query = $db->getQuery(true)
        ->select('params')
        ->from('#__tj_ucm_types')
        ->where('unique_identifier = ' . $db->quote($this->client));
    $db->setQuery($query);
    $typeParamsJson = $db->loadResult();
    $typeParams = json_decode($typeParamsJson);

    $aiEnabledForType = isset($typeParams->ai_enable_insights) && $typeParams->ai_enable_insights == 1;

    if ($enableAi && $aiEnabledForType && count($fieldSets) > 1)
    {
        include __DIR__ . '/ask_kb.php';
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
        <?php
    echo Text::_('COM_TJLMS_NO_EXTRA_FIELDS_FOUND');
?>
   </div>
<?php
}
?>

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
            jQuery('.ucmsubform').find("input[name*='_contentid']").each(function()
            {

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
?>
<input type="hidden" name="clusterFieldName" id="clusterFieldUniqueName" value="<?php
echo $clusterFieldName;
?>"/>
<?php
$tmpl = $app->input->get('tmpl', '', 'STRING');

if ($clusterFieldName == 'com_tjucm_ropvendors_clusterclusterid' && empty($tmpl)) {
    $doc = Factory::getDocument();
    $doc->addScript(Uri::root() . 'media/com_dpe/js/tjucmreverselist.js');
?>

<script type="text/javascript">
jQuery(document).ready(function() {
    jQuery("#jform_<?php
    echo $clusterFieldName;
?>").change(function(){
        tjucmreverselist.getReverseListUrl();
    });
});
</script>
<?php
}
// DPE - Hack - End
?>
<script type="text/javascript">
jQuery(document).on('subform-row-add', function(event, row){
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
//vendor

jQuery(document).ready(function() {
 jQuery(".panel.row").css("display", "none");

var acc = document.getElementsByClassName("accordion");
var i;

for (i = 0; i < acc.length; i++) {
  acc[i].addEventListener("click", function() {
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
</script>
<script>
    jQuery(document).ready(function(){
    jQuery(document).change(function(evt) {
    var url = '<?php echo Uri::root();?>'
   getSubFormsFeedback(evt,url)
            });
        });
</script>
<script type="">jQuery(document).ready(function($){
				 jQuery(document).on('click', '.joomla-alert--close', function() {
          jQuery(this).closest('joomla-alert').remove();
       });

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
	jQuery(document).ready(function($){
				 

				var dataShowonFields = jQuery("#item-form").find("div[data-showon]");
					
					dataShowonFields.each(function() {
						var value = jQuery(this).data("showon");
						var fieldName = value[0].field;
						var signVal = value[0].sign;

						var inputValue = jQuery("select[name=\'"+ fieldName +"[]\']").val();
						if (inputValue == null && (signVal == "!="))
						{;
							jQuery(this).css("display", "");
						}
					});

					jQuery("select[multiple]").on("change", function () {
						var showOnFieldData;
						var multipleFieldValue = jQuery(this);
						dataShowonFields.each(function() {
						var values = jQuery(this).data("showon");
						if (values != undefined)
						{
							values.each(function(item,index){

							if (item.field+'[]' == multipleFieldValue.attr('name'))
							{ 
								showOnFieldData = item;
							}
							
						})

						var fieldName = showOnFieldData.field;
						var signVal = showOnFieldData.sign;


						var inputValue = jQuery("select[name=\'"+ fieldName +"[]\']").val();
						
                                            
						if (inputValue == null && signVal == "!=")
						{
							jQuery(this).css("display", "");
						}
						}
						
					});

						});
				});
</script>
	<?php

	// Code to run the script
	if ($ticketConditionData)
	{
		$this->fieldTable= $fieldTable;
		$this->ticketConditionData = $ticketConditionData;
		$this->fieldTableLink = $fieldTableLink;
		echo $this->loadTemplate('extraticketgeneration');
	}
	?>
<script>

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


	jQuery(':checkbox').change(function() {

      if(this.checked) {

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

      } else {
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
      }
   });



	jQuery(':radio').click(function() {
       	 var selectEl = jQuery(this);
		  setTimeout(function() {
		    jQuery('div[data-showon]').each(function() {

			  var id = selectEl.attr('id').replace('jform_','');
			   var styleElem = jQuery(this).attr('style');

			   var nameAttr = selectEl.attr('name');

		      var dataAttr = jQuery(this).attr('data-showon');

		  if (dataAttr.indexOf(nameAttr) !== -1) {

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



</script>

