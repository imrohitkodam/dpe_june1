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
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\HTML\HTMLHelper;


// Call to utilize the tab structure in URL
HTMLHelper::script('media/com_dpe/js/dpe_ucm_tab.js');

if (!key_exists('formObject', $displayData) || !key_exists('xmlFormObject', $displayData))
{
	return;
}

$app = JFactory::getApplication();
$user = JFactory::getUser();

// Layout for field types
$fieldLayout = array();
$fieldLayout['File'] = $fieldLayout['Image'] = "file";
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
$allowedTags = '<a><strong><br>';

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
$controlGroupDivClass = ($isSubForm) ? 'form-group' : 'col-xs-12 col-md-6';
$labelDivClass = ($isSubForm) ? 'field-label' : 'col-xs-12';
$controlDivClass = ($isSubForm) ? 'field-data' : 'col-xs-12';

// Get Field table
JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
$tjFieldsFieldTable = JTable::getInstance('field', 'TjfieldsTable');


// Call the model 
JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_tjfields/models');
$fieldsModelForFeedback = JModelLegacy::getInstance('Fields', 'TjfieldsModel');


$fieldSets = $formObject->getFieldsets();
$count = 0;

// Iterate through the normal form fieldsets and display each one
foreach ($fieldSets as $fieldset)
{
	$xmlFieldSet = $xmlFormObject[$count];
	$count++;
	$fieldCount = 0;
	?>
	<div class="tjucm-wrapper">
		<div class="row d-flex">
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
				$canView = false;

				if (is_array($field))
					{?>
						<div class="col-md-12 font-bold accordDetail"><span class='accordspan'><?php echo  ucwords(str_replace('_', ' ', $key));?></span>
						<br>

						<?php foreach($field as $fieldTags)
						{ 


			// No need to show tooltip/description for field on details view
							$fieldTags->description = '';

			// Get the field data by field name to check the field type
							$tjFieldsFieldTable->load(array('name' => $fieldTags->__get("fieldname")));
							

							if ($user->authorise('core.field.viewfieldvalue', 'com_tjfields.group.' . $tjFieldsFieldTable->group_id))
							{
								$canView = $user->authorise('core.field.viewfieldvalue', 'com_tjfields.field.' . $tjFieldsFieldTable->id);
							}

							if ($canView || ($itemData->created_by == $user->id))
							{
				// Get xml for the field
								$pattern = '/\[(.*?)\]/';
									preg_match($pattern, $fieldTags->name, $matches);
									$actualFieldName = $matches[1];
									$xmlField = '';
									
									foreach($xmlFieldSet->field as $keyv => $fieldsName)
									{  $fieldname = (array)$fieldsName['name'];
									 

										if($fieldname[0] == $actualFieldName)
										{

	 										$xmlField = $fieldsName;
	 									}
										
									}
								$fieldCount++;

								if ($fieldTags->hidden)
								{
									echo $fieldTags->input;
									continue;
								}

								if ($fieldTags->type == 'Ucmsubform')
								{
									?>
									<div class="col-xs-12 col-md-6">
										<div class="col-xs-4"><?php echo $fieldTags->label; ?>:</div>
										<div class="col-xs-8">
											<?php
											$count = 0;
											$ucmSubFormXmlFieldSets = array();

							// Call to extra fields
											JLoader::import('components.com_tjucm.models.item', JPATH_SITE);
											$tjucmItemModel = JModelLegacy::getInstance('Item', 'TjucmModel');

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
													$layout = new JLayoutFile('fields', JPATH_ROOT . '/components/com_tjucm/layouts/detail');
													echo $layout->render(array('xmlFormObject' => $ucmSubFormXmlFieldSets, 'formObject' => $ucmSubformFormObject, 'itemData' => $ucmSubFormRecordData, 'isSubForm' => 1));
													echo "<hr>";
												}
											}
											?>
										</div>
									</div>
									<?php
								}
								else
								{
									$layoutToUse = (array_key_exists($fieldTags->type, $fieldLayout)) ? $fieldLayout[$fieldTags->type] : 'field';
									if ($field->type == 'Freetext')
											{
						?>
						<div class="col-md-12 col-sm-12 col-xs-12">
						<?php }else{?>
						<div class="col-md-4 col-sm-6 col-xs-12">
						<?php }?>
										<div class="form-fieldset-area py-10">
											<div class="<?php echo $controlGroupDivClass;?>">
												<?php if ($fieldTags->type == 'Freetext')
												{?>
													<div class="field-label"><?php echo html_entity_decode($fieldTags->getAttribute('freetext')); ?></div>
												<?php }elseif($field->type =='Spacer'){}else{ ?>
													<div class="<?php echo $labelDivClass;?>"><?php echo $fieldTags->label; ?></div>
												<?php } ?>
												<div class="<?php echo $controlDivClass;?>">
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
														$layout = new JLayoutFile('file', JPATH_ROOT . '/templates/shaper_helix3/html/layouts/com_tjfields/fields');
													}
													else
													{
														$layout = new JLayoutFile($layoutToUse, JPATH_ROOT . '/components/com_tjfields/layouts/fields');
													}

													$output = $layout->render(array('fieldXml' => $xmlField, 'field' => $fieldTags));

							// To align text, textarea, textareacounter, editor and tjlist fields properly
													if ($fieldTags->type == 'Textarea'|| $fieldTags->type == 'Textareacounter'|| $fieldTags->type == 'Text' || $fieldTags->type == 'Editor' || $fieldTags->type == 'tjlist')
													{
														?>
														<div class="tj-wordwrap">
															<?php echo $output; 

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
																					echo "<br><span>". Strip_tags($fieldFeedbackValue[0]->feedback, $allowedTags) . '</span>';
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
																			echo "<br><p class='feedbackDetailColor'> <br>  <span >". Strip_tags($fieldFeedbackValue[0]->feedback,$allowedTags) . '</span>  </p>';
																		}
																	}
																}

																?>
															</div>
															<?php
														}
							// DPE Hack can go in core
														elseif($fieldTags->type == 'numericcalculation')
														{
															$colorcombination = json_decode($fieldTags->getAttribute('colorcombination'));

															foreach($colorcombination as $key => $colors)
															{				


																if (empty($output))
																{
																	$output = 0;
																}
																
																if (($output >= $colors->min) && ($output <= $colors->max) )
																{
																	echo "<p class='numericcalculation' style='color:".$colors->color."'>".$colors->value."</p>";
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
																					echo "<br>  <span>". Strip_tags($fieldFeedbackValue[0]->feedback, $allowedTags) . '</span>';
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
																			echo "<br><p class='feedbackDetailColor'>  <br>  <span>". Strip_tags($fieldFeedbackValue[0]->feedback, $allowedTags) . '</span>  </p>';
																		}
																	}
																}
															}
															?>
														</div>
													</div>
												</div>
											</div>
											<?php
										}
									}
								}
								?>
							</div>

							<?php


						}

						else
						{
							$tjFieldsFieldTable->load(array('name' => $field->__get("fieldname")));
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
									<div class="col-xs-12 col-md-6">
										<div class="col-xs-4"><?php echo $field->label; ?>:</div>
										<div class="col-xs-8">
											<?php
											$count = 0;
											$ucmSubFormXmlFieldSets = array();

							// Call to extra fields
											JLoader::import('components.com_tjucm.models.item', JPATH_SITE);
											$tjucmItemModel = JModelLegacy::getInstance('Item', 'TjucmModel');

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
													$layout = new JLayoutFile('fields', JPATH_ROOT . '/components/com_tjucm/layouts/detail');
													echo $layout->render(array('xmlFormObject' => $ucmSubFormXmlFieldSets, 'formObject' => $ucmSubformFormObject, 'itemData' => $ucmSubFormRecordData, 'isSubForm' => 1));
													echo "<hr>";
												}
											}
											?>
										</div>
									</div>
									<?php
								}$layoutToUse = (array_key_exists($field->type, $fieldLayout)) ? $fieldLayout[$field->type] : 'field';
								if ($field->type == 'Freetext')
											{
						?>
						<div class="col-md-12 col-sm-12 col-xs-12">
						<?php }else{?>
						<div class="col-md-4 col-sm-6 col-xs-12">
						<?php }?>
									<div class="form-fieldset-area py-10">
										<div class="<?php echo $controlGroupDivClass;?>">
											<?php if ($field->type == 'Freetext')
											{?>
												<div class="field-label"><?php echo html_entity_decode($field->getAttribute('freetext')); ?></div>
											<?php }elseif($field->type =='Spacer'){}else{ ?>
												<div class="<?php echo $labelDivClass;?>"><?php echo $field->label; ?></div>
											<?php } ?>
											<div class="<?php echo $controlDivClass;?>">
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
													$layout = new JLayoutFile('file', JPATH_ROOT . '/templates/shaper_helix3/html/layouts/com_tjfields/fields');
												}
												else
												{
													$layout = new JLayoutFile($layoutToUse, JPATH_ROOT . '/components/com_tjfields/layouts/fields');
												}

												$output = $layout->render(array('fieldXml' => $xmlField, 'field' => $field));

							// To align text, textarea, textareacounter, editor and tjlist fields properly
												if ($field->type == 'Textarea'|| $field->type == 'Textareacounter'|| $field->type == 'Text' || $field->type == 'Editor' || $field->type == 'tjlist')
												{
													?>
													<div class="tj-wordwrap">
														<?php echo $output; 

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
																				echo "<br><span>". Strip_tags($fieldFeedbackValue[0]->feedback, $allowedTags) . '</span>';
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
																		echo "<br><p class='feedbackDetailColor'> <br>  <span >". Strip_tags($fieldFeedbackValue[0]->feedback,$allowedTags) . '</span>  </p>';
																	}
																}
															}

															?>
														</div>
														<?php
													}
							// DPE Hack can go in core
													elseif($field->type == 'numericcalculation')
													{
														$colorcombination = json_decode($field->getAttribute('colorcombination'));

														foreach($colorcombination as $key => $colors)
														{				

														if (empty($output))
														{
															$output = 0;
														}

															if (($output >= $colors->min) && ($output <= $colors->max) )
															{
																echo "<p class='numericcalculation' style='color:".$colors->color."'>".$colors->value."</p>";
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
																				echo "<br>  <span>". Strip_tags($fieldFeedbackValue[0]->feedback,$allowedTags) . '</span>';
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
														?>
													</div>
												</div>
											</div>
											</div><?php
										}
									}
								}?>

								 </div>
								</div><?php

							}




