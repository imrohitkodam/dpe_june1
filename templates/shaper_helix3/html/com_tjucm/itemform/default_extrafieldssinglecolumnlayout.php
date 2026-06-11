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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;


HTMLHelper::_('script', 'media/com_multiagency/js/multiagencyService.js');
HTMLHelper::_('script', 'media/com_multiagency/js/multiagency.js');
HTMLHelper::_('script','media/com_dpe/js/dpefeedbacksubform.js');
$params   = ComponentHelper::getParams('com_dpe');
$disabledFields = array();

Text::script('COM_DPE_TICKET_GENERATION_FAIL');
Text::script('COM_DPE_TICKET_GENERATION_SUCCESS');
Text::script('COM_DPE_TICKET_FIELD_REQUIRED');


$fieldsets_counter = 0;
$layout  = Factory::getApplication()->input->get('layout');
Factory::getApplication()->input->set('extralayout', "sarlog");

$user = Factory::getUser(); 
  $params     			    = ComponentHelper::getParams('com_multiagency');
 $orgAdmin           		= (int) $params->get('multiagency_school_admin_group', '0', 'INT'); 
$orgAdmin                   = in_array($orgAdmin, $user->groups);

$ucmConfigs = ComponentHelper::getParams('com_tjucm');
$useTooltip = $ucmConfigs->get('enable_custom_tooltip');
$layout  = new FileLayout('feedbackformfield', JPATH_SITE . '/templates/shaper_helix3/html/layouts/com_tjucm/form');

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

	?>
	
	<style>

	.checklistnote textarea{

  		width: 38% !important;
	}
	</style>

<div id="item-form" >
	<div class="overlay" id="tjucm_loader" style="display:none;">
		<div class="loader"></div>
	</div>
</div>
<?php
if ($this->form_extra)
{
	// Iterate through the normal form fieldsets and display each one
	$fieldSets = $this->form_extra->getFieldsets();

	// CHECK DPIA LITE FIELD GROUPS ACESS
$mainHelper = JPATH_SITE . '/components/com_dpe/helpers/main.php';
JLoader::register('DpeMainHelper', $mainHelper);

$dpeMainHelper = new DpeMainHelper;
$assignedUsers = $dpeMainHelper->getFieldValues($user->id, null, $this->client);

if(($this->client == 'com_tjucm.dpialite' ) && (!$orgAdmin) &&( !$user->authorise('core.manageall', 'com_cluster')) && empty($assignedUsers)) {

	Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
	$fieldGroupTable = Table::getInstance('group', 'TjfieldsTable');
	$fieldGroupTable->load(array('id'=>'253'));

	BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/components/com_dpe/models');
	$userModel = BaseDatabaseModel::getInstance('Users', 'DpeModel');
	$groupAssetsRule = $userModel->getAssetRuleByAssetId($fieldGroupTable->asset_id);


	if (($fieldGroupTable->name == 'Main') && (count($user->groups) <=2 ))
	{
		$userKey = key($user->groups);
		$assetKey = array_keys($groupAssetsRule['core.field.viewfieldvalue']);

		if (!in_array($user->groups,$assetKey))
		{
			$hideTab = 1;
		}
	}

}
// END

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
		}
		?>
		<div class="section-title visible-print"><h3><?php echo $fieldset->name;?></h3></div>
		<div class="form-horizontal col-12 custom-form-fields">
			<?php
			// Get disabled fields from assignee attribute before rendering the fields in the form
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
			$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');
			$tjFieldFieldTable->load(array('client' => $this->item->client, 'type' => 'assignee', 'state' => 1));

			$params = new Registry($tjFieldFieldTable->params);
			$disabledFields = $params['ucmFields'];


        $fieldArray = array();

        foreach ($this->form_extra->getFieldset($fieldset->name) as $field)
        {

            if (!empty($field->getAttribute('tags')))
            {
                $temp     = new TagsHelper;
                $tagnames = $temp->getTagNames(array(
                    $field->getAttribute('tags')
                ));

                if (array_key_exists($fieldsArray, (array) $tagnames[0])) {
                    $fieldArray[$tagnames[0]][] = $field;
                } else {
                    $fieldArray[$tagnames[0]][] = $field;
                }
            }
            else
            {
                $fieldArray[] = $field;
            }

        }

			if(!empty($fieldArray)){


			// Iterate through the fields and display them
			foreach ($fieldArray as $key => $field)
			{
				if (is_array($field))
				{
				?>
				<div class="w-100">
				<div class="accordion" id="accordion<?php echo $i++; ?>"><?php echo  ucwords(str_replace('_', ' ', $key)); ?></div>
				<div id="pan" class="panel">
		<?php foreach($field as $fieldTag)
				{
					$description = $fieldTag->description;

					if($useTooltip)
					{
						$fieldTag->description = '';
					}

					$isUcmsubform = 0;
					if ($fieldTag->type == 'Ucmsubform')
					{
						$isUcmsubform   = 1;
						if($fieldTag->max == 1)
					{
					?>

					<script>
						jQuery(document).ready(function(){

							jQuery('.btn-toolbar').hide();
						})
					</script>

				<?php }
					}

					if (strpos($fieldTag->class, 'twoColumnUcmsubform') !== false)
					{
						$isUcmsubform   = 0;
					}


				if (!$fieldTag->hidden)
				{
					// If user having TJCUM RBACL edit access then user can edit the record by default it is disabled using the joomla permission
					if ($this->canEdit)
					{
						$fieldTag->readonly = false;
						$fieldTag->disabled = false;
					}

					// Disabled fields for assginee users if they dont have access for ucm type
					if ($this->assignedUsers && $disabledFields)
					{
						// If field available in config then disable the field
						if (in_array($fieldTag->getAttribute('name'), $disabledFields))
						{
							$fieldTag->readonly = true;
							$fieldTag->disabled = true;
						}
						else
						{
							// Enable fields which are disabled by core functionality
							$fieldTag->readonly = false;
							$fieldTag->disabled = false;
						}
					}
					//$className = ($field->type == 'Spacer') ? 'w-100' : '';

					?>
					<div class="col-12 <?php echo $isUcmsubform? ' custom-form-style ucmsubform': ''?>">
						<div class="form-group row">
							<?php
							if($useTooltip && $description)
							{
								$fieldTag->description = $description;
							}
							?>
							<?php if($isUcmsubform){ ?>
								<div class="<?php echo ' col-sm-4 control-label w-100 text-left'?>">
									<?php echo $fieldTag->label; ?>
								</div>
								<div class="<?php echo ' col-sm-5 rop-inputs w-100'?>">
									<?php echo $fieldTag->input; ?>
								</div>
							<?php }
							elseif ($fieldTag->type == 'DpechecklistExpectation')
							{
								?>
								<div class="<?php echo ' col-sm-4'?>">
									<?php echo $fieldTag->label; ?>
								</div>
								<div class="<?php echo ' col-sm-8 mb-10'?>">
									<?php echo $fieldTag->input; ?>
								</div>
								<?php
							}
							else
							{ 	echo $fieldTag->renderField();

															  if($fieldTag->type =='Dpechecklist')
													{
														$checklistName = str_replace(['jform', '[', ']'], '', $fieldTag->name);
														Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
														$fieldDetail = Table::getInstance('field', 'TjfieldsTable');
														$fieldDetail->load(['name' => $checklistName]);
														$checklistParams = json_decode($fieldDetail->params);
														$enableChecklistNote = isset($checklistParams->enablechecklistnote) ? $checklistParams->enablechecklistnote : 1;
														
													if ($enableChecklistNote) {

														preg_match('/jform\[(.*?)\]/', $fieldTag->name, $matches);

														if (isset($matches[1])) {
															$actualFieldName = $matches[1];
														}

														$fieldName = str_replace("[",'',$fieldTag->name);
														$fieldName = str_replace("]",'',$fieldName);

														
														$notefieldName = 'jform['.str_replace('jform', '', $fieldName) . '_checklistNote]';

																			
														Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
														$checklistNote = Table::getInstance('CheckListNoteExtend', 'DpeTable');
														$checklistNote->load(array('fieldId' => $fieldDetail->id, 'content_id' => Factory::getApplication()->input->get("id", '', 'INT')));
														$checkNoteValue = $checklistNote->fieldValue;


													?>
												
												<div class="checklistnote">
												 <label for="<?php echo $fieldName .'_checklistnote';?>" class="checklistnotelabel"><?php echo Text::_('COM_DPE_CHEKLIST_NOTE'); ?></label>
												  <textarea id="<?php echo $notefieldName.'_checklistnote'?>" name="<?php echo $notefieldName;?>" class="checklisttextarea"rows="2" cols="5"><?php echo $checkNoteValue;?></textarea>
												</div>
													<?php }}



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
							}
							?>
								
							<div class="<?php echo 'col-sm-8';?> float-right" >
								<script>
									<?php if ($fieldTag->type == 'Checkbox'){?>
										jQuery('#<?php echo $fieldTag->id;?>').addClass('d-block');
									<?php } ?>
								</script>
								
								<div class="<?php echo 'col-sm-10 px-0 ms-51';?>">
									  <?php 
											echo $layout->render($fieldTag);
									   ?>
								</div>
							</div>
							
							<!-- DPE Hack: To show the org info -->

							<?php
							if ($fieldTag->type === "Cluster")
							{
							?>
							<div class="col-sm-12">
								<a id="agencyInfoLink" href="javascript:void(0);"><?php echo Text::_('COM_DPE_ORGANISATION_INFORMATION');?></a>
							</div>
							<?php
							}
							?>

							<!-- DPE Hack End -->

							<?php
							// TODO :- Check and remove
							if ($fieldTag->type == 'File')
							{
								?>
								<script type="text/javascript">
									jQuery(document).ready(function ()
									{
										var fieldValue = "<?php echo $fieldTag->value; ?>";
										var AttrRequired = jQuery('#<?php echo $field->id;?>').attr('required');
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
						</div>
					</div>
				<?php
				}

				}?>

				</div>
				</div>
				<div class="clearfix"></div>

				<?php
				}


				if (!is_array($field))
				{

					$description = $field->description;

					if($useTooltip)
					{
						$field->description = '';
					}

				$isUcmsubform = 0;
				if ($field->type == 'Ucmsubform')
				{
					$isUcmsubform   = 1;
				
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

				if (strpos($field->class, 'twoColumnUcmsubform') !== false)
				{
					$isUcmsubform   = 0;
				}

				if (!$field->hidden)
				{
					// If user having TJCUM RBACL edit access then user can edit the record by default it is disabled using the joomla permission
					if ($this->canEdit)
					{
						$field->readonly = false;
						$field->disabled = false;
					}

					// Disabled fields for assginee users if they dont have access for ucm type
					if ($this->assignedUsers && $disabledFields)
					{
						// If field available in config then disable the field
						if (in_array($field->getAttribute('name'), $disabledFields))
						{
							$field->readonly = true;
							$field->disabled = true;
						}
						else
						{
							// Enable fields which are disabled by core functionality
							$field->readonly = false;
							$field->disabled = false;
						}
					}
					//$className = ($field->type == 'Spacer') ? 'w-100' : '';

					?>
					<div class="col-xs-12 col-md-12 <?php echo $isUcmsubform? ' custom-form-style1 making-round-form ucmsubform': ''?>">
						<div class="form-group row">
						<?php
							if($useTooltip && $description)
							{
								$field->description = $description;
							}
						?>
							<?php if($isUcmsubform){ ?>
								<div class="<?php echo ' col-sm-4 control-label w-100 text-left'?>">
									<?php echo $field->label; ?>
								</div>
								<div class="<?php echo ' col-sm-5 rop-inputs w-100'?>">
									<?php echo $field->input; ?>
								</div>
							<?php }
							elseif ($field->type == 'DpechecklistExpectation')
							{
								?>
								<div class="<?php echo ' col-sm-4'?>">
									<?php echo $field->label; ?>
								</div>
								<div class="<?php echo ' col-sm-8 mb-10 ps-sm-1'?>">
									<?php echo $field->input; ?>
								</div>
								<?php
							}
							else
							{
							  echo $field->renderField();

							  if($field->type =='Dpechecklist')
													{
														$checklistName = str_replace(['jform', '[', ']'], '', $field->name);
														Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
														$fieldDetail = Table::getInstance('field', 'TjfieldsTable');
														$fieldDetail->load(['name' => $checklistName]);
														$checklistParams = json_decode($fieldDetail->params);
														$enableChecklistNote = isset($checklistParams->enablechecklistnote) ? $checklistParams->enablechecklistnote : 1;
														
													if ($enableChecklistNote) {

														preg_match('/jform\[(.*?)\]/', $field->name, $matches);

														if (isset($matches[1])) {
															$actualFieldName = $matches[1];
														}

														$fieldName = str_replace("[",'',$field->name);
														$fieldName = str_replace("]",'',$fieldName);

														
														$notefieldName = 'jform['.str_replace('jform', '', $fieldName) . '_checklistNote]';
														Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
														$checklistNote = Table::getInstance('CheckListNoteExtend', 'DpeTable');
														$checklistNote->load(array('fieldId' => $fieldDetail->id, 'content_id' => Factory::getApplication()->input->get("id", '', 'INT')));
														$checkNoteValue = $checklistNote->fieldValue;


													?>
												
												<div class="checklistnote">
												 <label for="<?php echo $fieldName .'_checklistnote';?>" class="checklistnotelabel"><?php echo Text::_('COM_DPE_CHEKLIST_NOTE'); ?></label>
												  <textarea id="<?php echo $notefieldName.'_checklistnote'?>" name="<?php echo $notefieldName;?>" class="checklisttextarea"rows="2" cols="5"><?php echo $checkNoteValue;?></textarea>
												</div>
													<?php }}
							  if (isset($ticketConditionData->linkField) && $field->value && 'jform_'.$fieldTableLink->name == $field->id)
													{ 
														?>
														<div class="row ticketbtnclass" style="margin-top: -40px;">
															<div class="<?php echo 'col-sm-4';?> float-right" >
															</div>
															<div class="<?php echo 'col-sm-5 mb-10';?> float-right fw-bold" >
															<a href="<?php echo $field->value;?>" target="_blank"><?php echo Text::_('COM_DPE_LOG_TO_TICKET');?> </a>

															</div>
														</div>
														<script type="text/javascript">
															
															jQuery('#<?php echo $field->id;?>').hide();
														</script>

														<?php
													}

							  if (isset($ticketConditionData->linkField) && $ticketConditionData->isCreateTicket == 'true' && 'jform_'.$field->name == $field->id  && (!$field->value && $user->authorise('core.manageall', 'com_cluster')))
							  { 
								  	$ticketConditionDatas = json_encode($ticketConditionData);
									  $ticketConditionDatas = str_replace('"', '&quot;', $ticketConditionDatas);

								  	?>
								  		<div class='float-end ticketbtn'>
								  	<input type="button"  class='btn btn-sm btn-primary ' name="addTicket" id='addTicket' value="<?php echo Text::_('COM_DPE_ADDTICKET')?>" onclick="logticket.addTicketfromUcm('<?php echo $ticketConditionDatas ?>'); <?php if ($fieldTableLink->id){?> updateLinkField();<?php }?>"><br><br><p id='addTicketMessage' class='d-none '></p>
								  </div>
								  
								  	<?php
								  }
							}
							?>
								
						
							<!-- DPE Hack: To show the org info -->
							
							<!-- This is added to get the feedback data for the fields -->	
							<div class="<?php echo 'col-sm-8';?> float-right" >
							<script>
									<?php if ($field->type == 'Checkbox'){?>
										jQuery('#<?php echo $field->id;?>').addClass('d-block');
									<?php } ?>
								</script>
								
								<div class="<?php echo 'col-sm-10 px-0 ms-51';?>">
									  <?php 
											echo $layout->render($field);
									   ?>
								</div>
							</div>
						<!-- feedback end -->
							<?php
							if ($field->type === "Cluster")
							{
							?>
							<div class="col-sm-12">
								<a id="agencyInfoLink" href="javascript:void(0);"><?php echo Text::_('COM_DPE_ORGANISATION_INFORMATION');?></a>
							</div>
							<?php
							}
							?>

							<!-- DPE Hack End -->

							<?php
							// TODO :- Check and remove
							if ($field->type == 'File')
							{
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
						</div>
					</div>
					<div class="clearfix"></div>

				<?php
					}
				}
			}
			?>
		</div>
		<div class="clearfix"></div>

		<?php
		}

		if (count($fieldSets) > 1)
		{
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
		}
	}

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
		<?php echo Text::_('COM_TJLMS_NO_EXTRA_FIELDS_FOUND');?>
	</div>
	<?php
}
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
			if (panel.style.display === "block") {
			  panel.style.display = "none";
			} else {
			  panel.style.display = "block";
			}
		  });

		}
	
	});	
	
	
jQuery(document).ready(function() {
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
<script>
	jQuery(document).ready(function(){
	jQuery(document).change(function(evt) {
	var url = '<?php echo Uri::root();?>'
	getSubFormsFeedback(evt,url)
			});
		});
	jQuery(document).ready(function($){
				 // Temp solution for conditional field

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
<script type="text/javascript">
	jQuery(document).ready(function($){
				 // Temp solution for conditional field

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
<script type="text/javascript">
		jQuery('document').ready(function(){

	    var hidetabs = "<?php echo $hideTab;?>";
		if(hidetabs)
		{
			jQuery('.joomla-tabs li').each(function() {
		        var anchorText = jQuery(this).find('a').text().trim();
		        if (anchorText !== 'Main') {
		            jQuery(this).hide();
		        }
		    });
		    jQuery('.tab-pane').each(function() {
		    	if (jQuery(this).prop('id') !== 'main')
		    	{
					jQuery('#' + jQuery(this).prop('id')).hide();
		    	}
		    	else
		    	{
		    		jQuery('#' + jQuery(this).prop('id')).addClass('active');
		    	}
		    })
		}
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
<!-- <script type="text/javascript">
	jQuery(document).ready(function(){
	jQuery('.copyOwnership').css('float','right');
	jQuery('.copyAssignee').css('float','right');

});
	function copyEmail(evet)
	{
		var btnId = evet.getAttribute('id');
		var actualEmailaValue="";
		btnId = btnId.replace('copy','');
		var emailValue = jQuery('#'+btnId).chosen().find('option:selected').text();
		emailValue = emailValue.replaceAll(')', ',').trim();
		emailValue = emailValue.split(',');
		emailValue.forEach(function(item) {

   			 actualEmailaValue  += item.split("(").pop()+" ";
		});

         var value= `<input value="`+actualEmailaValue+`" id="selVal" />`;
         
		  jQuery(value).insertAfter('#'+btnId);
		  jQuery("#selVal").select();
		  document.execCommand("Copy");
		  jQuery('body').find("#selVal").remove();
	}
</script> -->
