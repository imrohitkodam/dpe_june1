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
Text::script('COM_DPE_RSTICKET_SAVE_THELOG_FORM');


$fieldsets_counter = 0;
$layout  = Factory::getApplication()->input->get('layout');
Factory::getApplication()->input->set('extralayout', "sarlog");

$user = Factory::getUser();	
$params     			    = ComponentHelper::getParams('com_multiagency');
$orgAdmin           		= (int) $params->get('multiagency_school_admin_group', '0', 'INT');
$orgAdmin	   			= in_array($orgAdmin, $user->groups);
$staff           		    =in_array((int) $params->get('member_role_id', '0', 'INT'), $user->groups);


$uri = Uri::getInstance();

$allowTicketForStaff = false;

if($this->client == 'com_tjucm.breachlog') {

	$allowTicketForStaff = true;
}

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

if ($this->form_extra)
{
	// Iterate through the normal form fieldsets and display each one
	$fieldSets = $this->form_extra->getFieldsets();

$mainHelper = JPATH_SITE . '/components/com_dpe/helpers/main.php';
JLoader::register('DpeMainHelper', $mainHelper);

$assignedUsers = '';

if($this->item->id)
{
	$dpeMainHelper = new DpeMainHelper;
	$assignedUsers = $dpeMainHelper->getFieldValues($user->id, null, $this->client);
}
?>

<div id="item-form" >
				<div class="overlay" id="tjucm_loader" style="display:none;">
					<div class="loader"></div>
				</div>
			</div>

<?php

if(($this->client == 'com_tjucm.dpialite' ) && (!$orgAdmin) &&( !$user->authorise('core.manageall', 'com_cluster')) && empty($assignedUsers)) {

	Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
	$fieldGroupTable = Table::getInstance('group', 'TjfieldsTable');
	$fieldGroupTable->load(array('id'=>'269'));

	BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/components/com_dpe/models');
	$userModel = BaseDatabaseModel::getInstance('Users', 'DpeModel');
	$groupAssetsRule = $userModel->getAssetRuleByAssetId($fieldGroupTable->asset_id);


	if (($fieldGroupTable->name == 'Main') && (count($user->groups) <=2 ))
	{
		$userKey = key($user->groups);
		
		$assetKey = array_keys((array)$groupAssetsRule['core.field.viewfieldvalue']);

		if (!in_array($user->groups,$assetKey))
		{
			$hideTab = 1;
		}
	}

}

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
							<div class="col-sm-9 col-12 pe-sm-4">
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
														<div class="<?php echo ' col-sm-4 qq'?>">
															<?php echo $fieldTag->label; ?>
														</div>
														<div class="<?php echo ' col-sm-8 mb-10'?>">
															<?php echo $fieldTag->input; ?>
														</div>
														<?php
													}
													else
														{ 	echo $fieldTag->renderField();

															if (isset($ticketConditionData->linkField) && $fieldTag->value && 'jform_'.$fieldTableLink->name == $fieldTag->id && ($user->authorise('core.manageall', 'com_cluster') || $orgAdmin))
															{ 
																?>
																<div class="row ticketbtnclass"  >
																	<div class="<?php echo 'col-sm-4';?> float-right" >
																	</div>
																	<div class="<?php echo 'col-sm-5 mb-10';?> gototicket float-right fw-bold" >
																		<a href="<?php echo $fieldTag->value;?>" target="_blank"><?php echo Text::_('COM_DPE_LOG_TO_TICKET');?> </a>

																	</div>
																</div>
																<script type="text/javascript">
																	jQuery('#<?php echo $fieldTag->id;?>').removeAttr('readonly');
																	jQuery('#<?php echo $fieldTag->id;?>').css('margin-top','20px');

																</script>

																<?php
															}

															if (isset($ticketConditionData->linkField) && 'jform_'.$fieldTableLink->name == $fieldTag->id && $ticketConditionData->isCreateTicket == 'true' && ($fieldTag->value=='' && $user->authorise('core.manageall', 'com_cluster') || $orgAdmin || $allowTicketForStaff))
															{ 
																$ticketConditionDatas = json_encode($ticketConditionData);
																$ticketConditionDatas = str_replace('"', '&quot;', $ticketConditionDatas);

																?>
																<div class='float-end ticketbtnclass'>
																	<input type="button" class='btn btn-sm btn-primary ' name="addTicket" id='addTicket' value="<?php echo Text::_('COM_DPE_ADDTICKET')?>" onclick="logticket.addTicketfromUcm('<?php echo $ticketConditionDatas ?>'); <?php if ($fieldTableLink->id){?> updateLinkField();<?php }?>"><br><br><p id='addTicketMessage' class='d-none '></p>
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

															<div class="<?php echo 'col-sm-10 px-0';?>">
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
								<div class="col-xs-12 col-md-12 <?php echo $isUcmsubform? ' custom-form-style ucmsubform': ''?>">
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
											<div class="row">
												<div class="<?php echo ' col-sm-4 aa'?>">
													<?php echo $field->label; ?>
												</div>
												<div class="<?php echo ' col-sm-5 mb-10'?>">
													<?php echo $field->input; ?>
												</div>
											</div>	
											<?php
										}
										else
										{
											echo $field->renderField();

											if (isset($ticketConditionData->linkField) && $field->value && 'jform_'.$fieldTableLink->name == $field->id && ($user->authorise('core.manageall', 'com_cluster') || $orgAdmin))
											{ 
												?>
												<div class="row ticketbtnclass" >
													<div class="<?php echo 'col-sm-4 ';?> float-right" >
													</div>
													<div class="<?php echo 'col-sm-5 mb-10';?>  gototicket float-right fw-bold" >
														<a href="<?php echo $field->value;?>" target="_blank"><?php echo Text::_('COM_DPE_LOG_TO_TICKET');;?> </a>

													</div>
												</div>
												<script type="text/javascript">

													jQuery('#<?php echo $field->id;?>').removeAttr('readonly');
												</script>

												<?php
											}


											if (isset($ticketConditionData->linkField) && ($ticketConditionData->isCreateTicket == 'true') && ('jform_'.$fieldTableLink->name == $field->id) && ($field->value =='') && ($user->authorise('core.manageall', 'com_cluster') || $orgAdmin || $allowTicketForStaff))
											{
												echo $field->value;
												$ticketConditionDatas = json_encode($ticketConditionData);
												$ticketConditionDatas = str_replace('"', '&quot;', $ticketConditionDatas);

												?>
												<div class='float-end ticketbtnclass'>
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

											<div class="<?php echo 'col-sm-10 px-0 ms-51';?> ">

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
		jQuery(document).ready(function(){


			// var lineBreak = jQuery('<br> <br>');
			// lineBreak.insertBefore('#sp-page-title');

			// var divToMove = jQuery('.float-end.ticketbtnclass');
			// divToMove.insertAfter('#sp-page-title');
			jQuery('#addTicketMessage').css({
				'margin-top': '-50px',
			});

			jQuery('#addTicket').css({
				'margin-top': '-95px',
			});
			// if (jQuery('#addTicket').length) {

			// 	jQuery('#sp-main-body').css('margin-top','-61px');
			// }

			

		})

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
<script>
	jQuery('document').ready(function(){

		jQuery('#sbox-btn-close').click(function(){window.parent.SqueezeBox.close();});
	   // jQuery(".ticketbtnclass").insertAfter("#tjucm_myTabTabs");

		var ticketUrlElement = window.parent.jQuery('#ticketUrl');
		if (ticketUrlElement.length > 0) {

			var currentUrl = ticketUrlElement.val();
      // Check if the URL contains "view-ticket"
      if (currentUrl.indexOf("view-ticket") !== -1) {
      	var organisationId = (window.parent.jQuery('#ticketcluster').val())?window.parent.jQuery('#ticketcluster').val():window.parent.jQuery('#schoolId').val();
      	var subject = window.parent.jQuery('#ticket_subject').val();
      	var message = window.parent.jQuery('.com-rsticketspro-has-overflow').text();
      	var leadStaffMember  = window.parent.parent.jQuery('#ticketcustomer_id').val();


      	jQuery('#jform_<?php echo $ticketConditionData->clusterId;?>').val((organisationId)?organisationId:window.parent.jQuery('#schoolId').val()).trigger("chosen:updated");

      	var clusterUserData = {cluster_id: organisationId, user_id: ''}

      	var ajaxUrl  =  '<?php echo Uri::root();?>'+'index.php?option=com_cluster&task=clusterusers.getUsersByClientId&format=json';
      	var ownershipFieldId = 'jform_<?php echo$ticketConditionData->toUser;?>';

      	ownership.getUsers(clusterUserData, ajaxUrl, ownershipFieldId);

      	setTimeout(function(){
      		jQuery('#jform_<?php echo $ticketConditionData->toUser;?>').val(leadStaffMember).trigger("chosen:updated");

      	},1500)

      	jQuery('#jform_<?php echo $ticketConditionData->message;?>').val(message);
      	jQuery('#jform_<?php echo $ticketConditionData->subject;?>').val(subject);
      	jQuery('#jform_<?php echo $fieldTableLink->subject;?>').click();

      	if (jQuery('#jform_<?php echo $fieldTableLink->name;?>').length)
      	{
      		var ticketUrl = window.parent.jQuery('#ticketUrl').val();

      		var ticketId =  ticketUrl.substring(ticketUrl.lastIndexOf('view-ticket/') +  'view-ticket/'.length);

      		var FullTicketId = "<?php echo URI::root().'index.php?option=com_rsticketspro&view=ticket&id='?>"+ticketId;

      		jQuery('#jform_<?php echo $fieldTableLink->name;?>').val(FullTicketId);

      	}

      }
  } 
      	 var ticketValue = jQuery('#jform_<?php echo $fieldTableLink->name;?>').val();
      	 
      	 if ((ticketValue == undefined) || (ticketValue.length < 1))
	     {
			jQuery(".ticketbtnclass").insertAfter("#tjucm_myTabTabs");	
	     }else
	     {
	     	jQuery(".gototicket").insertBefore('#jform_<?php echo $fieldTableLink->name;?>');
	     	jQuery(".gototicket").removeClass('float-right');
	     }


})

	function closePopup()
	{
		if (window.parent.$('#logid').val().length > 0)
		{
			window.parent.SqueezeBox.close();
		}
		else
		{

			var result = confirm("<?php echo Text::_('COM_DPE_RSTICKET_SAVE_THELOG_FORM');?>");

			if (result) {

				window.parent.SqueezeBox.close();
			} else {

				return false;
			}

		}
	}

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
	jQuery(document).ready(function() {
    jQuery(".panel").each(function() {
        if (jQuery.trim(jQuery(this).html()) === "") { // Check if .panel is empty
            jQuery(this).prev('.accordion').hide(); // Hide the previous .accordion element
        }
    });
});
</script>
