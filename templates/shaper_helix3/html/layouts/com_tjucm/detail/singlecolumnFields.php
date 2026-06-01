<?php
/**
 * @package    TJ-UCM
 *
 * @author     TechJoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri; 
use Joomla\CMS\Plugin\PluginHelper;


// Call to utilize the tab structure in URL
HTMLHelper::script('media/com_dpe/js/dpe_ucm_tab.js');

if (!key_exists('formObject', $displayData) || !key_exists('xmlFormObject', $displayData))
{
	return;
}
// DPE - Hack  - Start
JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
$params = DPE::config();
$dateFomat = (String) $params->get('dateFormat');
// DPE - Hack  - End

$app = Factory::getApplication();
$user = Factory::getUser();
$params     			   = ComponentHelper::getParams('com_multiagency');
$orgAdminRoleId           = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
$orgAdminRoleId 		   = in_array($orgAdminRoleId, $user->groups);

// Layout for field types
$fieldLayout = array();
$fieldLayout['File'] = $fieldLayout['Image'] = $fieldLayout['Captureimage'] = "file";
$fieldLayout['Checkbox'] = "checkbox";
$fieldLayout['Color'] = "color";
$fieldLayout['multi_select'] = $fieldLayout['single_select'] = $fieldLayout['Radio'] = $fieldLayout['List'] = $fieldLayout['tjlist'] = "list";
$fieldLayout['Itemcategory'] = "itemcategory";
$fieldLayout['Video'] = $fieldLayout['Audio'] = $fieldLayout['Url'] = "link";
$fieldLayout['Calendar'] = "calendar";
$fieldLayout['Cluster'] = "cluster";
$fieldLayout['Related'] = $fieldLayout['SQL'] = "sql";
$fieldLayout['Subform'] = "subform";
$fieldLayout['Ownership'] = "ownership";
$fieldLayout['Editor'] = "editor";
$fieldLayout['Assignee'] = "assignee";
$allowedTags = '<a><strong><br><ul><li>';


// Load the tj-fields helper
JLoader::import('components.com_tjfields.helpers.tjfields', JPATH_SITE);

$TjfieldsHelper = new TjfieldsHelper;

// Get JLayout data
$xmlFormObject = $displayData['xmlFormObject'];
$formObject = $displayData['formObject'];
$itemData = $displayData['itemData'];
$isSubForm = isset($displayData['isSubForm']) ? $displayData['isSubForm'] : '';
$data = $TjfieldsHelper->FetchDatavalue(array('content_id' => $itemData->id, 'client' => $itemData->client));

// Define the classes for subform and normal form rendering
$controlGroupDivClass = ($isSubForm) ? 'col-xs-12' : 'col-xs-12 col-md-6';
$labelDivClass = ($isSubForm) ? 'col-xs-6' : 'col-xs-4';
$controlDivClass = ($isSubForm) ? 'col-xs-6' : 'col-xs-8';

// Get Field table
Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
$tjFieldsFieldTable = Table::getInstance('field', 'TjfieldsTable');

$fieldSets = $formObject->getFieldsets();
$count = 0;

$tjUcmFrontendHelper = new TjucmHelpersTjucm;

$link = 'index.php?option=com_tjucm&view=items&client=' . $this->client;
$itemId = $tjUcmFrontendHelper->getItemId($link);



Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
$typeDetails = Table::getInstance('Type', 'TjucmTable');
$typeDetails->load(array('unique_identifier' => $itemData->client));
$ticketConditionData = json_decode(json_decode($typeDetails->params)->type_options);

	// Get the id of link text box check that field is present or not.
if(isset($ticketConditionData->linkField))
{
	$fieldTableLink = Table::getInstance('field', 'TjfieldsTable');
	$fieldTableLink->load(array('name'=>$ticketConditionData->linkField,'state'=>1));
}

?>

<div class="overlay" id="loader-overlay">
	<div class="loader"></div>
</div>
<div class="form-validate mt-30">
	<div id="backBtn" class="">
		<a class="fs-16 font-600 cursor-pointer" href="<?php echo Route::_($link . '&Itemid=' . $itemId); ?>">
			<i class="fa fa-arrow-left mr-10" aria-hidden="true"></i>Back
		</a>
		<?php

// if (($user->authorise('core.type.edititem', 'com_tjucm.type.' . $this->ucmTypeId)) || ($user->authorise('core.type.editownitem', 'com_tjucm.type.' . $this->ucmTypeId) && JFactory::getUser()->id == $this->item->created_by))
		$redirectURL = Route::_('index.php?option=com_tjucm&task=itemform.edit&id=' . $itemData->id. '&client=' . $itemData->client, false);
		if (Factory::getUser()->id == $itemData->created_by || empty($itemData->created_by))
		{
			
			?>
			<a class="px-25 ml-10 pull-right edit-record" href="<?php echo $redirectURL; ?>"><i class="fa fa-edit mr-10"></i><?php echo Text::_("COM_TJUCM_EDIT_ITEM"); ?></a>
			<?php
		}
		else if(!$orgAdminRoleId){


			?>
			<a class="px-25 ml-10 pull-right edit-record" href="<?php echo $redirectURL; ?>"><i class="fa fa-edit mr-10"></i><?php echo Text::_("COM_TJUCM_EDIT_ITEM"); ?></a>

			<?php

		}?>
		<a class="ml-36 pull-right edit-record" href="#" onclick="printData(); return false;" value ='print'><i class="fa fa-file-pdf-o"></i> &nbsp Pdf Export</a>
	</div>

	<!--Create Tabs for Details View -->
	<ul class="nav nav-tabs detail-view-tabs">
		<?php
		$fieldSetsCnt = 1;
		foreach ($fieldSets as $fieldset)
		{
			?>
			<li class="<?php echo ($fieldSetsCnt ==1) ? 'active' : ''  ?> tabItem">
				<a data-toggle="tab" href="#<?php echo str_replace(' ', '', $fieldset->name); ?>">
					<?php echo $fieldset->name; ?>
				</a>
			</li>
			<?php
			$fieldSetsCnt++;
		}
		?>
	</ul>

	<div class="tab-content">

		<?php
		$fieldSetsCnt = 1;

// Call the model 
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjfields/models');
		$fieldsModelForFeedback = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel');



// Iterate through the normal form fieldsets and display each one
		foreach ($fieldSets as $fieldset)
		{
			$xmlFieldSet = $xmlFormObject[$count];
			$count++;
			$fieldCount = 0;
			?>
			<div id="<?php echo str_replace(' ', '', $fieldset->name); ?>" class="tab-pane fade <?php echo ($fieldSetsCnt ==1) ? 'in active' : ''  ?>">

				<div class="tjucm-wrapper">
					<div class="row">
						<?php   

						$fieldArray = array();

						foreach ($formObject->getFieldset($fieldset->name) as $field)
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

						foreach ($fieldArray as $key => $field)
						{
							if (is_array($field))
								{ ?>
									<div class="col-12 accordDetail">
										<div class="row">
											<div class="col-md-12">
												<span class="accordspan"><?php echo  ucwords(str_replace('_', ' ', $key));?></span>
											</div>


											<?php foreach($field as $k => $fieldTags)
											{
												
											$fieldName = str_replace('jform', '', $fieldTags->name);
											$fieldName = str_replace('[', '', $fieldName);
											$fieldName = str_replace(']', '', $fieldName);
											$getFieldId = Table::getInstance('field', 'TjfieldsTable');
											$getFieldId->load(array('name' => $fieldName));
											$getFieldConditionId=Table::getInstance('condition', 'TjfieldsTable');
											$getFieldConditionId->load(array('field_to_show' => $getFieldId->id));
											
											if($getFieldConditionId->id)
											{
												PluginHelper::importPlugin('dpe');
												$condition=Factory::getApplication()->triggerEvent('onBeforeViewLoadGetConditionData', array($getFieldId->id, $itemData->id, $getFieldConditionId->show));
												
												if (!$condition[0])
												{
													continue;
												} 
											}  
												$fieldDetail = Table::getInstance('field', 'TjfieldsTable');

							// No need to show tooltip/description for field on details view
												$fieldTags->description = '';

							// Get the field data by field name to check the field type

												$tjFieldsFieldTable->load(array('name' => $fieldTags->__get("fieldname")));
												$canView = false;

												if ($user->authorise('core.field.viewfieldvalue', 'com_tjfields.group.' . $tjFieldsFieldTable->group_id))
												{
													$canView = $user->authorise('core.field.viewfieldvalue', 'com_tjfields.field.' . $tjFieldsFieldTable->id);
												}

												if ($canView || ($itemData->created_by == $user->id))
												{
								// Get xml for the field
													$xmlField = $xmlFieldSet->field[$fieldCount];
													$fieldCount++;

													if ($fieldTags->hidden)
													{
														echo $fieldTags->input;
														continue;
													}
													if ($fieldTags->type == 'Ucmsubform')
													{
														?>
														<div class="col-xs-12">
									<!-- <div class="form-fieldset-area py-10">
										<div class="form-horizontal"> -->

											<div class="w-100 font-bold mb-10"><?php echo $fieldTags->getAttribute('label'); ?>:</div>
											<div class="">
												<?php
												$count = 0;
												$ucmSubFormXmlFieldSets = array();

										// Call to extra fields
												JLoader::import('components.com_tjucm.models.item', JPATH_SITE);
												$tjucmItemModel = BaseDatabaseModel::getInstance('Item', 'TjucmModel');

										// Get Subform field data
												$formData = $TjfieldsHelper->getFieldData($fieldTags->getAttribute('name'));
												$ucmSubFormFieldValue = json_decode($formObject->getvalue($fieldTags->getAttribute('name')));

												$ucmSubFormFieldParams = json_decode($formData->params);
												$ucmSubFormFormSource = explode('/', $ucmSubFormFieldParams->formsource);
												$ucmSubFormClient = $ucmSubFormFormSource[1] . '.' . str_replace('form_extra.xml', '', $ucmSubFormFormSource[4]);
												$view = explode('.', $ucmSubFormClient);

												if (!empty($ucmSubFormFieldValue))
												{ 
													foreach ($ucmSubFormFieldValue as $ucmSubFormData)
													{  

														$contentIdFieldname = str_replace('.', '_', $ucmSubFormClient) . '_contentid';

														$ucmSubformFormObject = $tjucmItemModel->getFormExtra(
															array(
																"clientComponent" => 'com_tjucm',
																"client" => $ucmSubFormClient,
																"view" => $view[1],
																"layout" => 'default',
																"content_id" => $ucmSubFormData->$contentIdFieldname)
														);

														$ucmSubFormFormXml = simplexml_load_file($fieldTags->formsource);

														$ucmSubFormCount = 0;

														foreach ($ucmSubFormFormXml as $ucmSubFormXmlFieldSet)
														{
															$ucmSubFormXmlFieldSets[$ucmSubFormCount] = $ucmSubFormXmlFieldSet;
															$ucmSubFormCount++;
														}

														$ucmSubFormRecordData = $tjucmItemModel->getData($ucmSubFormData->$contentIdFieldname);

												// Call the JLayout recursively to render fields of ucmsubform
														$layout = new FileLayout('ucm-field-onecolumn', JPATH_ROOT . '/templates/shaper_helix3/html/layouts/com_tjucm/detail');
														echo htmlspecialchars_decode($layout->render(array('xmlFormObject' => $ucmSubFormXmlFieldSets, 'formObject' => $ucmSubformFormObject, 'itemData' => $ucmSubFormRecordData, 'isSubForm' => 1)));

														$tjFieldsFieldTable->load(array('name' => $ucmSubFormClient));


													}
												}
												?>
											</div>
										</div>

										<?php
									}elseif ($fieldTags->type == 'Calendar')
									{
										?>
										<div class="col-md-12 col-sm-12 col-12">
											<div class="form-fieldset-area py-10">
												<div class="form-horizontal">
													<div class=" row form-group">
														<div class="field-label col-md-2 col-sm-12"><?php echo $fieldTags->getAttribute('label'); ?></div>
														<div class="field-data col-md-8 col-sm-12">
															<?php
										// DPE - Hack  - Start

															$dateFomat = (String) $params->get('dateFormat');

															if ($fieldTags->showtime != 'false')
															{
																$dateFomat = (String) $params->get('dateTimeFormat');
															}
												// DPE - Hack  - End
															echo $output = HTMLHelper::date($fieldTags->value, $dateFomat);
															?>
														</div>
													</div>
												</div>
											</div>
										</div>
										<?php
									}

									else
									{ 

										
										$layoutToUse = (array_key_exists($fieldTags->type, $fieldLayout)) ? $fieldLayout[$fieldTags->type] : 'field';

										if($fieldTags->type =='Cluster'){

											$layoutc = new FileLayout($layoutToUse, JPATH_ROOT . '/components/com_tjfields/layouts/fields');
											$outputc = $layoutc->render(array('fieldXml' => $xmlField, 'field' => $fieldTags));?>

											<input type="hidden" id='orgName' value="<?php  echo $outputc; ?>">

										<?php }

										if($fieldTags->type == 'Ownership'){
											$layout = new FileLayout($layoutToUse, JPATH_ROOT . '/components/com_tjfields/layouts/fields');
											$output = $layout->render(array('fieldXml' => $xmlField, 'field' => $fieldTags));?>

											<input type="hidden" id='conductedBy' value="<?php  echo $output?>">

										<?php }

										if ($fieldTags->type == 'Freetext')
										{
											?>
											<div class="col-12">
												<?php 
											}else
											{?>
												<div class="col-md-12 col-sm-12 col-12">
													<?php 
												}?>
												<div class="form-fieldset-area py-10">
													<div class="form-horizontal">
														<div class="row form-group">
															<?php 
															if ($fieldTags->type == 'Freetext')
																{?>

																	<div class="field-label col-md-2 col-sm-12 freetextMod" ><?php echo html_entity_decode($fieldTags->getAttribute('freetext')); ?></div>
																<?php }elseif ($fieldTags->type == 'Spacer'){ 
																	?>

																<?php }else{ ?>
																	<div class="field-label col-md-2 col-sm-12"><?php echo $fieldTags->getAttribute('label'); ?></div>
																<?php } ?>
																<div class="field-data col-md-8 col-sm-12">
																	<?php
																	$valueFound = 0;

																	foreach ($data as $fieldData)
																	{
																		if ($fieldTags->getAttribute('name') == $fieldData->name)
																		{
																			$valueFound = 1;
																			break;
																		}
																	}

																	if (empty($valueFound))
																	{
																		$fieldTags->setValue('');
																	}


																	if ($fieldTags->type == 'Tjfile' || $fieldTags->type == 'Captureimage')
																	{
																		$layout = new FileLayout('file', JPATH_ROOT . '/templates/shaper_helix3/html/layouts/com_tjfields/fields');
																	}
																	else
																	{
																		$layout = new FileLayout($layoutToUse, JPATH_ROOT . '/components/com_tjfields/layouts/fields');
																	}


																		if ($fieldTags->type == 'Dpechecklist')
																		{
																			$checklistName = str_replace(['jform', '[', ']'], '', $fieldTags->name);

																			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
																			$fieldDetail = Table::getInstance('field', 'TjfieldsTable');
																			$fieldDetail->load(['name' => $checklistName]);
																			$checklistParams = json_decode($fieldDetail->params);

																			if (isset($checklistParams->enablechecklistscore) && $checklistParams->enablechecklistscore)
																			{
																				if (isset($checklistParams->tjfields) && is_array($checklistParams->tjfields))
																				{
																					foreach ($checklistParams->tjfields as $optionValue)
																					{
																						if (isset($optionValue->numeric_value) && $fieldTags->value == $optionValue->numeric_value)
																						{
																							$fieldTags->value = $optionValue->optionvalue;
																						}
																					}
																				}
																			}

																			$fieldTags->value = ($fieldTags->value == 'todo') ? '<span class="btn checklistBtn dpe-danger danger active btn-outline-success has-success">To-Do</span>' :
																				(($fieldTags->value == 'inprogress') ? '<span class="btn checklistBtn dpe-warning warning active btn-outline-success has-success">In Progress</span>' :
																				(($fieldTags->value == 'done') ? '<span class="btn checklistBtn dpe-info info active btn-outline-success has-success">Done</span>' :
																				(($fieldTags->value == 'na') ? '<span class="btn checklistBtn dpe-na na active btn-outline-success has-success">N/A</span>' : $fieldTags->value)));
																		}
													$output = $layout->render(array('fieldXml' => $xmlField, 'field' => $fieldTags));



																	if ($fieldTags->type == 'Textarea'|| $fieldTags->type == 'Textareacounter'|| $fieldTags->type == 'Text' || $fieldTags->type == 'Editor' || $fieldTags->type == 'tjlist' || $fieldTags->type == 'Dpechecklist')
																	{
																		?>
																		<div class="tj-wordwrap">
																			<?php 

																			if (isset($ticketConditionData->linkField) && $field->value && 'jform_'.$fieldTableLink->name == $fieldTags->id)
																				{?>

																					<a href="<?php echo $field->value;?>" target="_blank"><?php echo Text::_('COM_DPE_LOG_TO_TICKET');?> </a>

																				<?php }
																				else
																				{
																					if (empty($xmlField) && $fieldTags->type == 'tjlist')
																					{
																						if(is_array($fieldTags->value)){
																							foreach($fieldTags->value as $fv)
																							{
																								echo $fv . "<br>";
																							}
																						}else{echo $fieldTags->value;}

																					}else
																					{
																						echo htmlspecialchars_decode($output);
																					}
																				}

																				if ($tjFieldsFieldTable->showFeedback)
																				{  
																					if (is_array($fieldTags->value))
																						{ ?>
																							<p class='feedbackDetailColor'> 
																								<?php foreach($fieldTags->value as $values)
																								{ 
																									$fieldFeedbackValue = $fieldsModelForFeedback->getFieldValueByFieldValue($tjFieldsFieldTable->id, $values);

																									if ($fieldFeedbackValue[0]->feedback)
																									{
																										echo "<br> <span>". Strip_tags($fieldFeedbackValue[0]->feedback,$allowedTags) . '</span>';
																									}

																								}
																								?>
																							</p>
																							<?php
																						}
																						else
																						{ 
																							$fieldFeedbackValue = $fieldsModelForFeedback->getFieldValueByFieldValue($tjFieldsFieldTable->id, $fieldTags->value);

																							if ($fieldFeedbackValue[0]->feedback)
																							{
																								echo "<br><p class='feedbackDetailColor'> <br>  <span>". Strip_tags($fieldFeedbackValue[0]->feedback,$allowedTags) . '</span>  </p>';
																							}
																						}
																					}

																					?>
																				</div>
																				<?php
																			}
																			elseif ($fieldTags->type == 'Radio')
																			{

																				$radioName = str_replace('jform', '', $fieldTags->name);
																				$radioName = str_replace('[', '', $radioName);
																				$radioName = str_replace(']', '', $radioName);
																				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
																				$fieldDetail = Table::getInstance('field', 'TjfieldsTable');
																				$fieldDetail->load(array('name' => $radioName));
																				$radioDataId = $fieldDetail->id;
																				$tjFieldsHelper = new TjfieldsHelper;
																				$optionsData = $tjFieldsHelper->getRadioOptions($radioDataId);
																				foreach($optionsData as $option)
																				{
																					if($fieldTags->value == $option->value)
																					{
																						echo $option->options;
																					}

																				}
																				if ($tjFieldsFieldTable->showFeedback)
																				{  
																					if (is_array($fieldTags->value))
																						{ ?>
																							<p class='feedbackDetailColor'> 
																								<?php foreach($fieldTags->value as $values)
																								{
																									$fieldFeedbackValue = $fieldsModelForFeedback->getFieldValueByFieldValue($tjFieldsFieldTable->id, $values);
																									if ($fieldFeedbackValue[0]->feedback)
																									{
																										echo "<br> <span>". Strip_tags($fieldFeedbackValue[0]->feedback,$allowedTags) . '</span>';
																									}

																								}
																								?>
																							</p>
																							<?php
																						}
																						else
																						{ 
																							$fieldFeedbackValue = $fieldsModelForFeedback->getFieldValueByFieldValue($tjFieldsFieldTable->id, $fieldTags->value);

																							if ($fieldFeedbackValue[0]->feedback)
																							{
																								echo "<br><p class='feedbackDetailColor'>  <br>  <span>". Strip_tags($fieldFeedbackValue[0]->feedback,$allowedTags) . '</span>  </p>';
																							}
																						}
																					}
																				}
												// DPE Hack can go in core
																				elseif($fieldTags->type == 'numericcalculation')
																				{
																					$colorcombination = json_decode($fieldTags->getAttribute('colorcombination'));

																					if (empty($output))
																					{
																						$output = 0;
																					}

																					foreach($colorcombination as $key => $colors)
																					{										
																						if (($output >= $colors->min) && ($output <= $colors->max) )
																						{
																							echo "<p class='numericcalculation detailnumeric' style='color:".$colors->color."'>".$colors->value."</p>";
																						}
																					}
																				}

																				else
																				{
																					echo $output;

																					if ($tjFieldsFieldTable->showFeedback)
																					{  
																						if (is_array($fieldTags->value))
																							{ ?>
																								<p class='feedbackDetailColor'> 
																									<?php foreach($fieldTags->value as $values)
																									{ 
																										$fieldFeedbackValue = $fieldsModelForFeedback->getFieldValueByFieldValue($tjFieldsFieldTable->id, $values);

																										if ($fieldFeedbackValue[0]->feedback)
																										{
																											echo "<br> <span>". Strip_tags($fieldFeedbackValue[0]->feedback,$allowedTags) . '</span>';
																										}

																									}
																									?>
																								</p>
																								<?php
																							}
																							else
																							{ 
																								$fieldFeedbackValue = $fieldsModelForFeedback->getFieldValueByFieldValue($tjFieldsFieldTable->id, $fieldTags->value);

																								if ($fieldFeedbackValue[0]->feedback)
																								{
																									echo "<br><p class='feedbackDetailColor'>  <br>  <span>". Strip_tags($fieldFeedbackValue[0]->feedback,$allowedTags) . '</span>  </p>';
																								}
																							}
																						}
																					}

																					if ($fieldTags->type == 'Dpechecklist' && isset($checklistParams->enablechecklistnote) && $checklistParams->enablechecklistnote)
																					{
																						Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
																						$checklistNoteTable = Table::getInstance('CheckListNoteExtend', 'DpeTable');

																						if ($checklistNoteTable)
																						{
																							$checklistNoteTable->load(['fieldId' => $fieldDetail->id, 'content_id' => $itemData->id]);

																							if (!empty($checklistNoteTable->fieldValue))
																							{
																								echo "<br><p class='feedbackDetailColor'> <br> <span>" . Strip_tags($checklistNoteTable->fieldValue, $allowedTags) . '</span> </p>';
																							}
																						}
																					}
																					?>
																				</div>
																			</div>
																		</div>
																	</div>
																</div>

																<?php

															}

														}?>


														<?php 

													}?>
												</div>
											</div>
											<?php
										}
										else
										{
											$fieldName = str_replace('jform', '', $field->name);
											$fieldName = str_replace('[', '', $fieldName);
											$fieldName = str_replace(']', '', $fieldName);
											$getFieldId = Table::getInstance('field', 'TjfieldsTable');
											$getFieldId->load(array('name' => $fieldName));
											$getFieldConditionId=Table::getInstance('condition', 'TjfieldsTable');
											$getFieldConditionId->load(array('field_to_show' => $getFieldId->id));
											
											if($getFieldConditionId->id)
											{
												PluginHelper::importPlugin('dpe');
												$condition=Factory::getApplication()->triggerEvent('onBeforeViewLoadGetConditionData', array($getFieldId->id,$itemData->id,$getFieldConditionId->show));
												
												if (!$condition[0])
												{
													continue;
												} 
											}  

						// No need to show tooltip/description for field on details view
											$field->description = '';

						// Get the field data by field name to check the field type
											$tjFieldsFieldTable->load(array('name' => $field->__get("fieldname")));
											$canView = false;

											if ($user->authorise('core.field.viewfieldvalue', 'com_tjfields.group.' . $tjFieldsFieldTable->group_id))
											{
												$canView = $user->authorise('core.field.viewfieldvalue', 'com_tjfields.field.' . $tjFieldsFieldTable->id);
											}

											if ($canView || ($itemData->created_by == $user->id))
											{
				           // Get xml for the field
												$xmlField = $xmlFieldSet->field[$fieldCount];
												$fieldCount++;

												if ($field->hidden)
												{
													echo $field->input;
													continue;
												}

												if ($field->type == 'Ucmsubform')
												{
													?>
													<div class="col-xs-12">
						<!-- <div class="form-fieldset-area py-10">
							<div class="form-horizontal"> -->

								<div class="w-100 font-bold mb-10"><?php echo $field->getAttribute('label'); ?>:</div>
								<div class="">
									<?php
									$count = 0;
									$ucmSubFormXmlFieldSets = array();

							// Call to extra fields
									JLoader::import('components.com_tjucm.models.item', JPATH_SITE);
									$tjucmItemModel = BaseDatabaseModel::getInstance('Item', 'TjucmModel');

							// Get Subform field data
									$formData = $TjfieldsHelper->getFieldData($field->getAttribute('name'));
									$ucmSubFormFieldValue = json_decode($formObject->getvalue($field->getAttribute('name')));

									$ucmSubFormFieldParams = json_decode($formData->params);
									$ucmSubFormFormSource = explode('/', $ucmSubFormFieldParams->formsource);
									$ucmSubFormClient = $ucmSubFormFormSource[1] . '.' . str_replace('form_extra.xml', '', $ucmSubFormFormSource[4]);
									$view = explode('.', $ucmSubFormClient);

									if (!empty($ucmSubFormFieldValue))
									{ 
										foreach ($ucmSubFormFieldValue as $ucmSubFormData)
										{  

											$contentIdFieldname = str_replace('.', '_', $ucmSubFormClient) . '_contentid';

											$ucmSubformFormObject = $tjucmItemModel->getFormExtra(
												array(
													"clientComponent" => 'com_tjucm',
													"client" => $ucmSubFormClient,
													"view" => $view[1],
													"layout" => 'default',
													"content_id" => $ucmSubFormData->$contentIdFieldname)
											);

											$ucmSubFormFormXml = simplexml_load_file($field->formsource);

											$ucmSubFormCount = 0;

											foreach ($ucmSubFormFormXml as $ucmSubFormXmlFieldSet)
											{
												$ucmSubFormXmlFieldSets[$ucmSubFormCount] = $ucmSubFormXmlFieldSet;
												$ucmSubFormCount++;
											}

											$ucmSubFormRecordData = $tjucmItemModel->getData($ucmSubFormData->$contentIdFieldname);

									// Call the JLayout recursively to render fields of ucmsubform
											$layout = new FileLayout('ucm-field-onecolumn', JPATH_ROOT . '/templates/shaper_helix3/html/layouts/com_tjucm/detail');
											echo htmlspecialchars_decode($layout->render(array('xmlFormObject' => $ucmSubFormXmlFieldSets, 'formObject' => $ucmSubformFormObject, 'itemData' => $ucmSubFormRecordData, 'isSubForm' => 1)));
									//echo "<hr>";

											$tjFieldsFieldTable->load(array('name' => $ucmSubFormClient));


										}
									}
									?>
								</div>
							</div>

							<?php
						}
						elseif ($field->type == 'Calendar')
						{
							?>
							<div class="col-md-12 col-sm-12 col-12">
								<div class="form-fieldset-area py-10">
									<div class="form-horizontal">
										<div class="row form-group">
											<div class="field-label col-md-2 col-sm-12"><?php echo $field->getAttribute('label'); ?></div>
											<div class="field-data col-md-8 col-sm-12">
												<?php
								// DPE - Hack  - Start

												$dateFomat = (String) $params->get('dateFormat');

												if ($field->showtime != 'false')
												{
													$dateFomat = (String) $params->get('dateTimeFormat');
												}
										// DPE - Hack  - End
												echo $output = HTMLHelper::date($field->value, $dateFomat);
												?>
											</div>
										</div>
									</div>
								</div>
							</div>
							<?php
						}
						else
						{ 



							$layoutToUse = (array_key_exists($field->type, $fieldLayout)) ? $fieldLayout[$field->type] : 'field';
							if($field->type=='Cluster'){
								$layoutc = new FileLayout($layoutToUse, JPATH_ROOT . '/components/com_tjfields/layouts/fields');
								$outputc = $layoutc->render(array('fieldXml' => $xmlField, 'field' => $field));?>

								<input type="hidden" id='orgName' value="<?php  echo $outputc;?>">

							<?php }

							if($field->type == 'Ownership'){
								$layout = new FileLayout($layoutToUse, JPATH_ROOT . '/components/com_tjfields/layouts/fields');
								$output = $layout->render(array('fieldXml' => $xmlField, 'field' => $field));?>

								<input type="hidden" id='conductedBy' value="<?php  echo $output?>">

							<?php }

							if ($field->type == 'Freetext')
							{
								?>
								<div class="col-12">
								<?php }else{?>
									<div class="col-md-12 col-sm-12 col-12">
									<?php }?>
									<div class="form-fieldset-area py-10">
										<div class="form-horizontal">
											<div class="row form-group">
												<?php 
												if ($field->type == 'Freetext')
													{?>

														<div class="field-label col-md-2 col-sm-12 freetextMod"><?php echo html_entity_decode($field->getAttribute('freetext')); ?></div>
													<?php }elseif ($field->type == 'Spacer'){ 
														?>
														<div></div>
													<?php }else{ ?>
														<div class="field-label col-md-2 col-sm-12"><?php echo $field->getAttribute('label'); ?></div>
													<?php } ?>
													<div class="field-data col-md-8 col-sm-12">
														<?php
														$valueFound = 0;

														foreach ($data as $fieldData)
														{
															if ($field->getAttribute('name') == $fieldData->name)
															{
																$valueFound = 1;
																break;
															}
														}

														if (empty($valueFound))
														{
															$field->setValue('');
														}


														if ($field->type == 'Tjfile' || $field->type == 'Captureimage')
														{
															$layout = new FileLayout('file', JPATH_ROOT . '/templates/shaper_helix3/html/layouts/com_tjfields/fields');
														}
														else
														{
															$layout = new FileLayout($layoutToUse, JPATH_ROOT . '/components/com_tjfields/layouts/fields');
														}

														
														if ($field->type == 'Dpechecklist')
														{
															$checklistName = str_replace(['jform', '[', ']'], '', $field->name);

															Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
															$fieldDetail = Table::getInstance('field', 'TjfieldsTable');
															$fieldDetail->load(['name' => $checklistName]);
															$checklistParams = json_decode($fieldDetail->params);

															if (isset($checklistParams->enablechecklistscore) && $checklistParams->enablechecklistscore)
															{
																if (isset($checklistParams->tjfields) && is_array($checklistParams->tjfields))
																{
																	foreach ($checklistParams->tjfields as $optionValue)
																	{
																		if (isset($optionValue->numeric_value) && $field->value == $optionValue->numeric_value)
																		{
																			$field->value = $optionValue->optionvalue;
																		}
																	}
																}
															}

															$field->value = ($field->value == 'todo') ? '<span class="btn checklistBtn dpe-danger danger active btn-outline-success has-success">To-Do</span>' :
																(($field->value == 'inprogress') ? '<span class="btn checklistBtn dpe-warning warning active btn-outline-success has-success">In Progress</span>' :
																(($field->value == 'done') ? '<span class="btn checklistBtn dpe-info info active btn-outline-success has-success">Done</span>' :
																(($field->value == 'na') ? '<span class="btn checklistBtn dpe-na na active btn-outline-success has-success">N/A</span>' : $field->value)));
														}

													

														$output = $layout->render(array('fieldXml' => $xmlField, 'field' => $field));




														if ($field->type == 'Textarea'|| $field->type == 'Textareacounter'|| $field->type == 'Text' || $field->type == 'Editor' || $field->type == 'tjlist' || $field->type == 'Dpechecklist')
														{
															?>
															<div class="tj-wordwrap">
																<?php 

																if (isset($ticketConditionData->linkField) && $field->value && 'jform_'.$fieldTableLink->name == $field->id)
																	{?>

																		<a href="<?php echo $field->value;?>" target="_blank"><?php echo Text::_('COM_DPE_LOG_TO_TICKET');?> </a>

																	<?php }
																	else
																	{
																		if (empty($xmlField) && $field->type == 'tjlist')
																		{
																			if(is_array($field->value)){
																				foreach($field->value as $fv)
																				{
																					echo $fv . "<br>";
																				}
																			}else{echo $field->value;}
																			
																		}else
																		{
																			echo htmlspecialchars_decode($output); 
																		}
																	}

																	if ($tjFieldsFieldTable->showFeedback)
																	{  
																		if (is_array($field->value))
																			{ ?>
																				<p class='feedbackDetailColor'> 
																					<?php foreach($field->value as $values)
																					{ 
																						$fieldFeedbackValue = $fieldsModelForFeedback->getFieldValueByFieldValue($tjFieldsFieldTable->id, $values);

																						if ($fieldFeedbackValue[0]->feedback)
																						{
																							echo "<br> <span>". Strip_tags($fieldFeedbackValue[0]->feedback,$allowedTags) . '</span>';
																						}

																					}
																					?>
																				</p>
																				<?php
																			}
																			else
																			{ 
																				$fieldFeedbackValue = $fieldsModelForFeedback->getFieldValueByFieldValue($tjFieldsFieldTable->id, $field->value);

																				if ($fieldFeedbackValue[0]->feedback)
																				{
																					echo "<br><p class='feedbackDetailColor'> <br>  <span>". Strip_tags($fieldFeedbackValue[0]->feedback,$allowedTags) . '</span>  </p>';
																				}
																			}
																		}

																		?>
																	</div>
																	<?php
																}
																elseif ($field->type == 'Radio')
																{

																	$radioName = str_replace('jform', '', $field->name);
																	$radioName = str_replace('[', '', $radioName);
																	$radioName = str_replace(']', '', $radioName);

																	Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
																	$fieldDetail = Table::getInstance('field', 'TjfieldsTable');

																	$fieldDetail->load(array('name' => $radioName));
																	$radioDataId = $fieldDetail->id;

																	$tjFieldsHelper = new TjfieldsHelper;
																	$optionsData = $tjFieldsHelper->getRadioOptions($radioDataId);

																	foreach($optionsData as $option)
																	{
																		if($field->value == $option->value)
																		{
																			echo $option->options;
																		}

																	}
																	if ($tjFieldsFieldTable->showFeedback)
																	{  
																		if (is_array($field->value))
																			{ ?>
																				<p class='feedbackDetailColor'> 
																					<?php foreach($field->value as $values)
																					{ 
																						$fieldFeedbackValue = $fieldsModelForFeedback->getFieldValueByFieldValue($tjFieldsFieldTable->id, $values);

																						if ($fieldFeedbackValue[0]->feedback)
																						{
																							echo "<br> <span>". Strip_tags($fieldFeedbackValue[0]->feedback,$allowedTags) . '</span>';
																						}

																					}
																					?>
																				</p>
																				<?php
																			}
																			else
																			{ 
																				$fieldFeedbackValue = $fieldsModelForFeedback->getFieldValueByFieldValue($tjFieldsFieldTable->id, $field->value);

																				if ($fieldFeedbackValue[0]->feedback)
																				{
																					echo "<br><p class='feedbackDetailColor'>  <br>  <span>". Strip_tags($fieldFeedbackValue[0]->feedback,$allowedTags) . '</span>  </p>';
																				}
																			}
																		}

																	}
							                   // DPE Hack can go in core
																	elseif($field->type == 'numericcalculation')
																	{
																		$colorcombination = json_decode($field->getAttribute('colorcombination'));

																		if (empty($output))
																		{
																			$output = 0;
																		}

																		foreach($colorcombination as $key => $colors)
																		{										
																			if (($output >= $colors->min) && ($output <= $colors->max) )
																			{
																				echo "<p class='numericcalculation detailnumeric' style='color:".$colors->color."'>".$colors->value."</p>";
																			}
																		}
																	}

																	else
																	{


																		echo $output;

																		if ($tjFieldsFieldTable->showFeedback)
																		{  
																			if (is_array($field->value))
																				{ ?>
																					<p class='feedbackDetailColor'> 
																						<?php foreach($field->value as $values)
																						{ 
																							$fieldFeedbackValue = $fieldsModelForFeedback->getFieldValueByFieldValue($tjFieldsFieldTable->id, $values);

																							if ($fieldFeedbackValue[0]->feedback)
																							{
																								echo "<br> <span>". Strip_tags($fieldFeedbackValue[0]->feedback,$allowedTags) . '</span>';
																							}

																						}
																						?>
																					</p>
																					<?php
																				}
																				else
																				{ 
		$fieldFeedbackValue = $fieldsModelForFeedback->getFieldValueByFieldValue($tjFieldsFieldTable->id, $field->value);

		if ($fieldFeedbackValue[0]->feedback)
			{
			echo "<br><p class='feedbackDetailColor'>  <br>  <span>". Strip_tags($fieldFeedbackValue[0]->feedback,$allowedTags) . '</span>  </p>';
																					}
																				}
																			}
																		}
																		if ($field->type == 'Dpechecklist' && isset($checklistParams->enablechecklistnote) && $checklistParams->enablechecklistnote)
																		{
																			Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
																			$checklistNoteTable = Table::getInstance('CheckListNoteExtend', 'DpeTable');

																			if ($checklistNoteTable)
																			{
																				$checklistNoteTable->load(['fieldId' => $fieldDetail->id, 'content_id' => $itemData->id]);

																				if (!empty($checklistNoteTable->fieldValue))
																				{
																					echo "<p class='feedbackDetailColor'> <br> <span>" . Strip_tags($checklistNoteTable->fieldValue, $allowedTags) . '</span> </p>';
																				}
																			}
																		}
																		?>
																	</div>
																</div>
															</div>
														</div>
													</div>
													<?php
												}
											}

										}
										?>

			<!-- </div>
			</div>
		</div> -->
		<?php
		} // End of foreach
		?>
	</div>
</div>
<!-- tab-pane Ends Here -->
</div>

<?php
$fieldSetsCnt++;

}

?>
<!-- tab-content Ends Here -->
</div>

<!-- Container Ends Here -->
</div>

<!-- PDF Tab Selection Modal -->
<div class="modal fade" id="pdfTabSelectorModal" tabindex="-1" role="dialog" aria-labelledby="pdfTabSelectorModalLabel">
   <div class="modal-dialog" role="document">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="pdfTabSelectorModalLabel"><?php echo Text::_('COM_DPE_PDF_TAB_MODAL_TITLE'); ?></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
               <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body">
            <p><?php echo Text::_('COM_DPE_PDF_TAB_MODAL_INSTRUCTION'); ?></p>
            <div class="mb-2">
               <button type="button" class="btn btn-sm btn-default" id="selectAllTabsBtn"><?php echo Text::_('COM_DPE_PDF_TAB_MODAL_SELECT_ALL'); ?></button>
               <button type="button" class="btn btn-sm btn-default" id="clearAllTabsBtn"><?php echo Text::_('COM_DPE_PDF_TAB_MODAL_CLEAR_ALL'); ?></button>
            </div>
            <div id="tabCheckboxContainer" class="mb-3">
               <!-- Dynamically populated checkboxes -->
            </div>
            <div id="tabSelectionError" class="alert alert-danger" style="display:none;" role="alert"></div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo Text::_('COM_DPE_PDF_TAB_MODAL_CANCEL'); ?></button>
            <button type="button" class="btn btn-primary" id="generatePdfBtn"><?php echo Text::_('COM_DPE_PDF_TAB_MODAL_GENERATE'); ?></button>
         </div>
      </div>
   </div>
</div>

<!-- Hidden input to store client type for JavaScript -->
<input type="hidden" id="ucmClientType" value="<?php echo $itemData->client; ?>">

<script src="<?php echo Uri::root();?>/media/com_dpe/js/html2pdf.bundle.min.js"></script>

<script>
   // Language constants
   var LANG_MANDATORY = '<?php echo Text::_('COM_DPE_PDF_TAB_MODAL_MANDATORY'); ?>';
   var LANG_ERROR_NO_SELECTION = '<?php echo Text::_('COM_DPE_PDF_TAB_MODAL_ERROR_NO_SELECTION'); ?>';
   
   // Configuration for mandatory tabs (Making the Rounds client)
   var MAKING_ROUNDS_CLIENT = 'com_tjucm.monitoringcompliancemakingtherounds'; // TODO: Confirm actual client identifier
   var MANDATORY_TABS = ['Overview', 'DPO Feedback and Actions Taken']; // TODO: Confirm exact tab names
   
   // Main function called when user clicks "Pdf Export"
function printData() 
{
      // Show the tab selection modal
      showTabSelectionModal();
   }
   
   // Function to show and populate the tab selection modal
   function showTabSelectionModal()
   {
      var clientType = jQuery('#ucmClientType').val();
      var isMakingRounds = (clientType === MAKING_ROUNDS_CLIENT);
      var tabCheckboxContainer = jQuery('#tabCheckboxContainer');
      
      // Clear previous checkboxes
      tabCheckboxContainer.empty();
      
      // Collect all tabs from the navigation
      var tabs = [];
      jQuery('.nav-tabs .tabItem').each(function() {
         var tabLink = jQuery(this).find('a');
         var tabHref = tabLink.attr('href');
         var tabTitle = tabLink.text().trim();
         var tabId = tabHref.substring(1); // Remove the # from href
         
         tabs.push({
            id: tabId,
            title: tabTitle
         });
      });
      
      // Create checkboxes for each tab
      tabs.forEach(function(tab) {
         var isMandatory = isMakingRounds && MANDATORY_TABS.includes(tab.title);
         var checkboxHtml = '<div class="form-check mb-2">' +
            '<input class="form-check-input tab-checkbox" type="checkbox" ' +
            'id="tab_' + tab.id + '" value="' + tab.id + '" ' +
            (isMandatory ? 'checked disabled' : '') + '>' +
            '<label class="form-check-label" for="tab_' + tab.id + '">' +
            tab.title + (isMandatory ? ' <span class="badge badge-primary">' + LANG_MANDATORY + '</span>' : '') +
            '</label>' +
            '</div>';
         
         tabCheckboxContainer.append(checkboxHtml);
      });
      
      // Hide error message
      jQuery('#tabSelectionError').hide();
      
      // Show the modal
      jQuery('#pdfTabSelectorModal').modal('show');
   }
   
   // Handle Generate PDF button click
   jQuery(document).on('click', '#generatePdfBtn', function() {
      var selectedTabs = [];
      
      // Collect selected tabs (including disabled/mandatory ones)
      jQuery('.tab-checkbox').each(function() {
         if (jQuery(this).is(':checked')) {
            selectedTabs.push(jQuery(this).val());
         }
      });
      
      // Validate: at least one tab must be selected
      if (selectedTabs.length === 0) {
         jQuery('#tabSelectionError').text(LANG_ERROR_NO_SELECTION).show();
         return;
      }
      
      // Hide the modal
      jQuery('#pdfTabSelectorModal').modal('hide');
      
      // Generate PDF with selected tabs
      generatePdfWithSelectedTabs(selectedTabs);
   });
   
   // Handle modal close button (X) click
   jQuery(document).on('click', '#pdfTabSelectorModal .close', function() {
      jQuery('#pdfTabSelectorModal').modal('hide');
   });
   
   // Handle Cancel button click
   jQuery(document).on('click', '#pdfTabSelectorModal [data-dismiss="modal"]', function() {
      jQuery('#pdfTabSelectorModal').modal('hide');
   });
   
   // Handle Select All button click
   jQuery(document).on('click', '#selectAllTabsBtn', function() {
      jQuery('.tab-checkbox').not(':disabled').prop('checked', true);
   });
   
   // Handle Clear All button click
   jQuery(document).on('click', '#clearAllTabsBtn', function() {
      jQuery('.tab-checkbox').not(':disabled').prop('checked', false);
   });
   
   // Original printData function renamed and modified to accept selected tabs
   function generatePdfWithSelectedTabs(selectedTabIds)
{
    document.getElementById('loader-overlay').style.display = 'block';
    // Gather the data you want to send
    var dataToSend = {
        title: jQuery('.breadcrumb-item.active').text() || document.querySelector('title').textContent,
        orgname: jQuery('#orgName').val(),
        conductedBy: jQuery('#conductedBy').val(),
        date: new Date().toLocaleDateString('en-GB'),
        content: [] // This will hold the content to be sent
    };

    var uniqueAccordions = new Set(); // Set to store unique accordion formats

    // Collect content from the tab panes - ONLY from selected tabs
    jQuery(".tab-content").find('.tab-pane').filter(function() {
        // Filter to only include selected tabs
        return selectedTabIds.includes(jQuery(this).attr('id'));
    }).each(function () {
        var navBar = jQuery(this).attr('id'); // Get the ID of the tab pane
        var tabTitle = jQuery('a[href="#' + navBar + '"]').text(); // Get the tab title from the link
        var content = { title: tabTitle, fields: [] }; // Start with the tab title
        // Collect data from accordion fields
 
        // Collect fields from the tab pane (excluding accordion fields)
        jQuery(this).find('.field-label').each(function () {
            var $fieldLabel = jQuery(this);
            var $fieldData = $fieldLabel.next(); 
    		var $accordion = $fieldLabel.closest('.accordDetail');
			    // Check if the '.accordspan' exists inside this div
			if ($accordion.length > 0 && $accordion.find('.accordspan').length > 0) {
			      var accordionData = $accordion.html(); // Get the full HTML of the div
			      var accordionFormat = $(accordionData).find('span.accordspan').prop('outerHTML');

			 }

            var fieldDataText = $fieldData.prop('outerHTML'); // Get the text content
            var $image = $fieldData.find('img'); // Find any <img> tags within the field data
            
            if (!uniqueAccordions.has(accordionFormat)) {
                uniqueAccordions.add(accordionFormat); 
               
            } else {
                accordionFormat="";
            }


           var anchorTag = $fieldData.find("a").attr("onclick");
            var imageurl = "";
			
			if (anchorTag) {
			    var match = anchorTag.match(/tjFieldsFileField\.previewMedia\('([^']+)'/);

			    if (match && match[1]) {
			        imageurl = match[1]; // Extracted URL
			    } 
			}
           var imagePath = $image.length > 0 ? $image.attr('src') : imageurl; // Get the image source if it exists
           
            // Prepare the content for the PDF
            
            var fieldFeedback = ''; // Initialize feedback variable
            var feedbackStyles = ''; 
            var $feedback = $fieldData.find('.feedbackDetailColor');
            
			// Assuming feedback is in a specific class
            if ($feedback.length > 0) {
                fieldFeedback = $feedback.html(); // Get the feedback text
                feedbackStyles = $feedback.attr('class') || ''; // Capture feedback styles
            }

            // Remove duplicate feedback from value
            
            if ((fieldDataText || '').includes($feedback.text().trim())) {
   			 fieldDataText = (fieldDataText || '').replace($feedback.text().trim(), '').trim();
				}
            // Push the field data into the content.fields array
            content.fields.push({ 
        		label: $fieldLabel.hasClass('freetextMod') ? $fieldLabel.prop('outerHTML'):$fieldLabel.prop('outerHTML'),
                value: fieldDataText,
                accordion:accordionFormat, // Original value without feedback
                image: imagePath, // Add the image path to the content
                feedback: fieldFeedback, // Add feedback to the content
				feedbackstyle:feedbackStyles
            });
        });
        // Add the collected content for this tab to the main dataToSend object
        dataToSend.content.push(content);
    });

    document.getElementById('loader-overlay').style.display = 'block'; // Show loader

jQuery.ajax({
    url: Joomla.getOptions("system.paths").root + '/index.php?option=com_dpe&task=tjucm.getUcmDetailPdfDownload&format=json',
    type: 'POST',
    data: { data: JSON.stringify(dataToSend) },
    xhrFields: {
        responseType: 'blob' // Important: Handle binary response for PDF
    },
    success: function (response, status, xhr) {
        var blob = new Blob([response], { type: 'application/pdf' });
        var link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);

        // Extract filename from the content-disposition header
        var contentDisposition = xhr.getResponseHeader('Content-Disposition');
        var fileName = 'download.pdf'; // Default filename
        if (contentDisposition) {
            var matches = contentDisposition.match(/filename="?([^"]+)"?/);
            if (matches && matches[1]) {
                fileName = matches[1];
            }
        }

        link.download = fileName; // Set filename
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Hide loader after download
        document.getElementById('loader-overlay').style.display = 'none';
    },
    error: function (xhr, status, error) {
        console.error("PDF download failed: ", error);
        alert("Error downloading the PDF. Please try again.");
        document.getElementById('loader-overlay').style.display = 'none';
    }
});
 // Clean up the form
}

function splitTextToLines(text, maxLength) {
	const regex = new RegExp(`.{1,${maxLength}}`, 'g');
	return text.match(regex) || [];
}
</script>
<script>
	jQuery('window').ready(function(){

		jQuery('.tabItem').click(function(){

			jQuery('.tabItem').removeClass('active');
			jQuery(this).addClass('active');

			var clickedHref = $(this).find('a').attr('href').substring(1);

			jQuery('.tab-pane').removeClass('in active');
			jQuery('#' + clickedHref.replace('/', '\\/')).addClass('in active');
		    return false; // Prevent the default link behavior


		})

	})
</script>
