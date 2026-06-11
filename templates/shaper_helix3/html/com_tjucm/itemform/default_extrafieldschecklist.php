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
use Joomla\CMS\Filter\OutputFilter;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;


Text::script('COM_DPE_TICKET_GENERATION_FAIL');
Text::script('COM_DPE_TICKET_GENERATION_SUCCESS');
Text::script('COM_DPE_TICKET_FIELD_REQUIRED');
JLoader::import('components.com_tjfields.helpers.tjfields', JPATH_SITE);
// Call js file to update the link to ticket 
HTMLHelper::script('media/com_dpe/js/logsticket.js');
HTMLHelper::script('media/com_dpe/js/tjreportaddtodo.js');


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

$fieldsets_counter = 0;
$layout  = Factory::getApplication()->input->get('layout');
Factory::getApplication()->input->set('extralayout', "checklist");
$user = Factory::getUser();	
$params     			    = ComponentHelper::getParams('com_multiagency');
$orgAdmin           		= (int) $params->get('multiagency_school_admin_group', '0', 'INT');
$orgAdmin 		   			= in_array($orgAdmin, $user->groups);
$orgStaff           		= in_array((int) $params->get('member_role_id', '0', 'INT'), $user->groups);

$ucmConfigs = ComponentHelper::getParams('com_tjucm');
$useTooltip = $ucmConfigs->get('enable_custom_tooltip');
$this->item->id;
$checklistId = Factory::getApplication()->input->get("id", '', 'INT');

Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
$checklistReview = Table::getInstance('ChecklistNextReviewDate', 'DpeTable');
$checklistReview->load(array('content_id' => $checklistId));

Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jlike/tables');
$todosTable = Table::getInstance('Todos', 'JlikeTable');
$todosTable->load(array('id' => $checklistReview->todo_id));
$dueDate =  $todosTable->due_date;
$application = Factory::getApplication();
$sitemenu = $application->getMenu();
$mainmenuItems = $sitemenu->getItems(array('unpublish-menu'), array(''));

foreach ($mainmenuItems as $mainmenuItem) {
    if ($mainmenuItem->link === 'index.php?option=com_jlike&view=recommendationform&layout=edit') {
       $menuItem = $mainmenuItem->id;
    }
}
$actualUrl = Route::_('index.php?option=com_jlike&tmpl=component&view=recommendationform&layout=edit&Itemid='.$menuItem,false);

?>
<div id="item-form" >
	<div class="overlay" id="tjucm_loader" style="display:none;">
		<div class="loader"></div>
	</div>
</div>
<div class="checklisttododiv norchecklist">
    <label style="flex: 4;color:red;">
        <?php echo Text::_('COM_DPE_DATE_OF_NEXT_REVIEW') . ' ' ;?> </label>
        <?php

        echo HTMLHelper::_('calendar', ($dueDate)?$dueDate:'', 'checklistTodoDate', 'checklistTodoDate', '%d-%m-%Y %H:%M', ['style' => 'width:100%;']); ?>
    

   <a href='#' onclick='if (checklistSave()) { openPopup(Joomla.getOptions("system.paths").base+ "recomendation-form?tmpl=component&view=recommendationform&layout=edit&source=checklist"); } return false;' style="margin-left: 10px;">
    <i class="fa fa-calendar checklisttodo" title="<?php echo Text::_('PLG_SYSTEM_ADDTODO_BTN')?>"></i>
</a>

</div>


<?php
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
		}
		?>
		<div class="section-title visible-print"><h3><?php echo $fieldset->name;?></h3></div>
		<div class="form-horizontal col-xs-12 custom-form-fields">
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

			// Iterate through the fields and display them
			foreach ($fieldArray as $key => $field)
			{
				
				if(is_array($field))
					{?>
						<div class="clearfix"></div>	
						<div class="accordion" id="accordion<?php echo $i++; ?>"><?php echo  ucwords(str_replace('_', ' ', $key)); ?></div>
						<div id="pan" class="panel row">
							<?php foreach($field as $fieldTag) 
							{


								$description = $fieldTag->description;

								if($useTooltip)
								{
									$fieldTag->description = '';
								}
								if ($fieldTag->type == 'Freetext')
									{ ?>
										<script>
											jQuery(document).ready(function ()
											{
												jQuery('#<?php echo $fieldTag->id;?>').addClass('pull-left');
												jQuery('#<?php echo $fieldTag->id;?>').removeClass('pull-right');
												jQuery('label#<?php echo $fieldTag->id.'-lbl';?>').text('');
												jQuery('label#<?php echo $fieldTag->id.'-lbl';?>').parent().closest("div").removeClass('col-sm-9');

												var freeTextlable = jQuery('label#<?php echo $fieldTag->id.'-lbl';?>').text();

											});
										</script>
									<?php }


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
										?>
										<div class="col-12  <?php echo $isUcmsubform? ' custom-form-style ucmsubform': ''?>">
											<div class="form-group row  <?php echo (strtolower($fieldTag->type) == 'cluster' ? ' hide ' :'' ) ;?><?php echo $isUcmsubform? '': 'checklist py-10'?>">
												<?php
												if($useTooltip && $description)
												{
													$fieldTag->description = $description;
												}
												?>
												<?php if($isUcmsubform){ ?>
													<div class="<?php echo ' col-sm-12 control-label w-100 text-left'?>">
														<?php echo $fieldTag->label; ?>
													</div>
													<div class="<?php echo ' col-sm-12 rop-inputs w-100'?>">
														<?php echo $fieldTag->input; ?>
													</div>
												<?php }
												elseif ($fieldTag->type == 'DpechecklistExpectation')
												{
													?>
													<div class="<?php echo ' col-sm-4'?>">
														<?php echo $fieldTag->label; ?>
													</div>
													<div class="<?php echo ' col-sm-8 checklist-expectation px-0 mb-10'?>">
														<?php echo $fieldTag->input; ?>
													</div>
													<?php
												}
												else
												{
													echo $fieldTag->renderField();

													if (isset($ticketConditionData->linkField) && $fieldTag->value && 'jform_'.$fieldTableLink->name == $fieldTag->id)
													{ 
														?>
														<div class="row ticketbtnclass" style="margin-top: -40px;">
															<div class="<?php echo 'col-sm-4';?> float-right" >
															</div>
															<div class="<?php echo 'col-sm-5 mb-10';?> float-right fw-bold" >
																<a href="<?php echo $fieldTag->value;?>"target="_blank"><?php echo Text::_('COM_DPE_LOG_TO_TICKET');?> </a>

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

												<?php
									// TODO :- Check and remove
												if ($fieldTag->type == 'File')
												{
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
											</div>
										</div>
										<?php
									}

								}

								?>


							</div>						


							<?php	
						}


						if(!is_array($field))
						{
							$description = $field->description;

							if($useTooltip)
							{
								$field->description = '';
							}

							if ($field->type == 'Freetext')
								{?>
									<script>
										jQuery(document).ready(function ()
										{
											jQuery('#<?php echo $field->id;?>').addClass('pull-left');
											jQuery('#<?php echo $field->id;?>').removeClass('pull-right');
											jQuery('label#<?php echo $field->id.'-lbl';?>').text('');
											jQuery('label#<?php echo $field->id.'-lbl';?>').parent().closest("div").removeClass('col-sm-9');
											var freeTextlable = jQuery('label#<?php echo $field->id.'-lbl';?>').text();										
										});
									</script>
								<?php }


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
									?>
									<div class="col-12 <?php echo $isUcmsubform? ' custom-form-style ucmsubform': ''?>">
										<div class="form-group row <?php echo (strtolower($field->type) == 'cluster' ? ' hide ' :'' ) ;?><?php echo $isUcmsubform? '': 'checklist py-10'?>">
											<?php

											

											if($useTooltip && $description)
											{
												$field->description = $description;
											}
											?>
											<?php if($isUcmsubform){ ?>
												<div class="<?php echo ' col-12 control-label w-100 text-left'?>">
													<?php echo $field->label; ?>
												</div>
												<div class="<?php echo ' col-12 rop-inputs w-100'?>">
													<?php echo $field->input; ?>
												</div>
											<?php }
											elseif ($field->type == 'DpechecklistExpectation')
												{ 			?>
													<div class="<?php echo ' col-sm-4 col-12'?>">
														<?php echo $field->label; ?>
													</div>
													<div class="<?php echo ' col-sm-8 col-12 checklist-expectation px-0 mb-10'?>">
														<?php echo $field->input; ?>
													</div>
													<?php
												}
												else
												{
													echo $field->renderField();

													if($field->type =='Dpechecklist')
													{
														preg_match('/jform\[(.*?)\]/', $field->name, $matches);

														if (isset($matches[1])) {
															$actualFieldName = $matches[1];
														}

														$fieldNmm = str_replace("[",'',$field->name);
														$fieldNm = str_replace("]",'',$fieldNmm);
														$notefieldName = 'jform['.str_replace('jform', '', $fieldNm) . '_checklistNote]';

														$tjFieldHelper = new TjfieldsHelper;
														$fieldId = $tjFieldHelper->getFieldIdFromName($actualFieldName);
														
														Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
														$checklistNote = Table::getInstance('CheckListNoteExtend', 'DpeTable');
														$checklistNote->load(array('fieldId' => $fieldId, 'content_id' => Factory::getApplication()->input->get("id", '', 'INT')));
														$checkNoteValue = $checklistNote->fieldValue;


													?>
												
												<div class="checklistnote">
												 <label for="<?php echo $fieldNm .'_checklistnote';?>" class="checklistnotelabel"><?php echo Text::_('COM_DPE_CHEKLIST_NOTE'); ?></label>
												  <textarea id="<?php echo $notefieldName.'_checklistnote'?>" name="<?php echo $notefieldName;?>" class="checklisttextarea"rows="2" cols="50"><?php echo $checkNoteValue;?></textarea>
												</div>
													<?php }

													if (isset($ticketConditionData->linkField) && $field->value && 'jform_'.$fieldTableLink->name == $field->id)
													{ 
														?>
														<div class="row ticketbtnclass" style="margin-top: -40px;">
															<div class="<?php echo 'col-sm-4';?> float-right" >
															</div>
															<div class="<?php echo 'col-sm-5 mb-10';?> float-right fw-bold" >
																<a href="<?php echo $field->value;?>" target="_blank"><?php echoText::_('COM_DPE_LOG_TO_TICKET');?> </a>

															</div>
														</div>
														<script type="text/javascript">
															
															jQuery('#<?php echo $field->id;?>').hide();
														</script>

														<?php
													}



													if (isset($ticketConditionData->linkField) && 'jform_'.$fieldTable->name == $field->id && $ticketConditionData->isCreateTicket == 'true' && (!$field->value && $user->authorise('core.manageall', 'com_cluster')))
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
										<?php
									}
								}
							}	
							?>
						</div>
						<?php

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

						var acc = document.getElementsByClassName("accordion");
						var i;
						jQuery(".panel.row").css("display", "none");

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
				<script type="text/javascript">
					window.tjSiteRoot ="<?php echo Uri::root(); ?>";
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


<script type="text/javascript">

	jQuery(document).ready(function(){
		var currentUrl = window.parent.location.href;
		if(currentUrl.includes("best-practice-library/best-practice/") || currentUrl.includes('com_sppagebuilder'))
		{
			jQuery('.norchecklist').hide();
		}
	})
	function checklistSave()
	{
		var currentUrl = window.parent.location.href;
		var url = new URL(currentUrl);
		var checklistId = url.searchParams.get('id');


		if(!checklistId && !currentUrl.includes("best-practice-library/best-practice/"))
		{
			alert("Please submit the form first");
			return false;
		}else
		{
			return true;
		}
	}
</script>