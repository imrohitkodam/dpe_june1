<?php
/**
 * @package	    TJ-UCM
 *
 * @author	     TechJoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license  	  GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;
JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);
use Joomla\CMS\Table\Table;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;


if (!key_exists('itemsData', $displayData))
{
	return;
}
$document = Factory::getDocument();

$document->addScript(Uri::root() . 'media/com_dpe/js/tjucmroplist.js');

$fieldsData = $displayData['fieldsData'];
$app = Factory::getApplication();
$user = Factory::getUser();

// Layout for field types
$fieldLayout = array();
$fieldLayout['File'] = $fieldLayout['Image'] = $fieldLayout['Captureimage'] = "file";
$fieldLayout['Checkbox'] = "checkbox";
$fieldLayout['Color'] = "color";
$fieldLayout['Tjlist'] = $fieldLayout['Radio'] = $fieldLayout['List'] = $fieldLayout['Single_select'] = $fieldLayout['Multi_select'] = "list";
$fieldLayout['Itemcategory'] = "itemcategory";
$fieldLayout['Video'] = $fieldLayout['Audio'] = $fieldLayout['Url'] = "link";
$fieldLayout['Calendar'] = "calendar";
$fieldLayout['Cluster'] = "cluster";
$fieldLayout['Related'] = $fieldLayout['Sql'] = "sql";
$fieldLayout['Subform'] = "subform";
$fieldLayout['Ownership'] = "ownership";
$fieldLayout['Editor'] = "editor";
$fieldLayout['Assignee'] = "assignee";

// Load the tj-fields helper
JLoader::import('components.com_tjfields.helpers.tjfields', JPATH_SITE);
$TjfieldsHelper = new TjfieldsHelper;

// Load itemForm model
JLoader::import('components.com_tjucm.models.itemform', JPATH_SITE);
$tjucmItemFormModel = BaseDatabaseModel::getInstance('ItemForm', 'TjucmModel');

// Get JLayout data
$item          = $displayData['itemsData'];
$created_by    = $displayData['created_by'];
$client        = $displayData['client'];
$xmlFormObject = $displayData['xmlFormObject'];
$formObject    = $displayData['formObject'];
$ucmTypeId     = $displayData['ucmTypeId'];
$allowDraftSave = $displayData['ucmTypeParams']->allow_draft_save;
$i = isset($displayData['key']) ? $displayData['key'] : '';

$appendUrl = '';
$csrf = "&" . Session::getFormToken() . '=1';

// DPE override changes - DPE Roles should use action from UCM and Also changes for DPE Admin
$canEditOwn   = TjucmAccess::canEditOwn($ucmTypeId, $item->id);
$canEdit      = TjucmAccess::canEdit($ucmTypeId, $item->id);
$canDelete    = TjucmAccess::canDelete($ucmTypeId, $item->id);
$canDeleteOwn = TjucmAccess::canDeleteOwn($ucmTypeId, $item->id);

$canCopyItem        = $user->authorise('core.type.copyitem', 'com_tjucm.type.' . $ucmTypeId);

if (!empty($created_by))
{
	$appendUrl .= "&created_by=" . $created_by;
}

if (!empty($client))
{
	$appendUrl .= "&client=" . $client;
}

$link = 'index.php?option=com_tjucm&view=items' . $appendUrl;
$tjUcmFrontendHelper = new TjucmHelpersTjucm;
$itemId = $tjUcmFrontendHelper->getItemId($link);

$link = Route::_('index.php?option=com_tjucm&view=item&id=' . $item->id . "&client=" . $client . '&Itemid=' . $itemId, false);

$editown = false;

if ($canEditOwn)
{
	$editown = (Factory::getUser()->id == $item->created_by ? true : false);
}

$deleteOwn = false;

if ($canDeleteOwn)
{
	$deleteOwn = (Factory::getUser()->id == $item->created_by ? true : false);
}

// DPE - Hack  - Start
JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
$params = DPE::config();
$dateFomat = (String) $params->get('dateFormat');

$mainHelper = JPATH_SITE . '/components/com_dpe/helpers/main.php';
JLoader::register('DpeMainHelper', $mainHelper);

$dpeMainHelper = new DpeMainHelper; 
$assignedUsers = $dpeMainHelper->getFieldValues($user->id, $item->id, $client);

// Give action edit permission if user is assignee
if (!empty($assignedUsers))
{
	$canEdit = true;
}


 Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
	$typeDetails = Table::getInstance('Type', 'TjucmTable');
	$typeDetails->load(array('unique_identifier' => $client));
	$ticketConditionData = json_decode(json_decode($typeDetails->params)->type_options);

	// Get the id of link text box check that field is present or not.
	if(isset($ticketConditionData->linkField))
	{
	 	$fieldTableLink = Table::getInstance('field', 'TjfieldsTable');
	 	$fieldTableLink->load(array('name'=>$ticketConditionData->linkField,'state'=>1));
    }



// DPE - Hack  - End
?>
<div class="overlay" id="loader-overlay">
	<div class="loader"></div>
</div>
<div class="tjucm-wrapper">
<tr class="row<?php echo $item->id?>">
	<?php if ($canCopyItem || $canEdit || $canDelete || $editown || $deleteOwn) { ?>
	<!-- TODO- copy and copy to other feature is not fully stable hence relate buttons are hidden-->
	<td class="center ">
		<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
	</td>
	<?php } ?>
	<td>
<!--
		<a href="<?php echo Route::_(
		'index.php?option=com_tjucm&view=item&id=' .
		(int) $item->id . "&client=" . $client . '&Itemid=' . $itemId, false
		); ?>">
-->

		<!-- <a href="<?php echo $link; ?>" type="button" title="<?php echo Text::_('COM_TJUCM_VIEW_ITEM');?>"> -->
			<?php echo $this->escape($item->id); ?>
		<!-- </a> -->

<!--
		</a>
-->


	</td>
	<td class="text-nowrap">
		<?php echo  HTMLHelper::date($item->created_date, $dateFomat); ?>
	</td>
	<?php
	if ($allowDraftSave)
	{
		// $tjUcmFrontendHelper = new TjucmHelpersTjucm;
		// 	$itemId = $tjUcmFrontendHelper->getItemId('index.php?option=com_tjucm&view=itemform&client=' . $this->client);
		// 	// $masterUcmLink = Route::_('index.php?option=com_tjucm&task=itemform&id=' . $item->id'.&Itemid=' . $itemId, false);
		?>
		<td>
			<a href="<?php echo 'index.php?option=com_tjucm&task=itemform.edit&id=' . $item->id . $appendUrl; ?>" type="button" title="<?php echo ($item->draft) ? Text::_('COM_TJUCM_DATA_STATUS_DRAFT') : Text::_('COM_TJUCM_DATA_STATUS_SAVE'); ?>">
			<?php if ($item->draft) : ?>
				<i class="fa fa-pencil-square-o" aria-hidden="true"></i>
			<?php else : ?>
				<i class="fa fa-floppy-o" aria-hidden="true"></i>
			<?php endif;?>
			</a>
		</td>
	<?php
	}
	?>
<?php

	if (!empty($item))
	{
		foreach ($item as $key => $fieldValue)
		{
			if (array_key_exists($key, $displayData['listcolumn']))
			{
				$tjFieldsFieldTable = $fieldsData[$key];

				$canView = false;

				if ($user->authorise('core.field.viewfieldvalue', 'com_tjfields.group.' . $tjFieldsFieldTable->group_id))
				{
					$canView = $user->authorise('core.field.viewfieldvalue', 'com_tjfields.field.' . $tjFieldsFieldTable->id);
				}

				$fieldXml = $formObject->getFieldXml($tjFieldsFieldTable->name);
				$field    = $formObject->getField($tjFieldsFieldTable->name);
				
				if ($fieldValue && $field !== false && is_object($field))
				{
				    $field->setValue($fieldValue);
				}

				$isCalendarField = 0;
				$isCalendarField = ($field->type == 'Calendar') ? 1 : 0;
				?>
				<td class="<?php echo $isCalendarField ? 'text-nowrap' :(85 - $displayData['statusColumnWidth']) / count($displayData['listcolumn']) . '%';?>">
					<?php
						if ($canView || ($item->created_by == $user->id))
						{


							if ($field->type == 'Ucmsubform' && $fieldValue)
							{
								$ucmSubFormData = json_decode($tjucmItemFormModel->getUcmSubFormFieldDataJson($item->id, $field));
								$field->setValue($ucmSubFormData);
								?>
								<div>
									<div class="col-xs-4"><?php echo $field->label; ?>:</div>
									<div class="col-xs-8">
										<?php
										$count = 0;
										$ucmSubFormXmlFieldSets = array();

										// Call to extra fields
										JLoader::import('components.com_tjucm.models.item', JPATH_SITE);
										$tjucmItemModel = BaseDatabaseModel::getInstance('Item', 'TjucmModel');

										// Get Subform field data
										$fieldData = $TjfieldsHelper->getFieldData($field->getAttribute('name'));

										$ucmSubFormFieldParams = json_decode($fieldData->params);
										$ucmSubFormFormSource = explode('/', $ucmSubFormFieldParams->formsource);
										$ucmSubFormClient = $ucmSubFormFormSource[1] . '.' . str_replace('form_extra.xml', '', $ucmSubFormFormSource[4]);
										$view = explode('.', $ucmSubFormClient);
										$ucmSubFormData = (array) $ucmSubFormData;

										if (!empty($ucmSubFormData))
										{
											$count = 0;

											foreach ($ucmSubFormData as $subFormData)
											{
												$count++;
												$contentIdFieldname = str_replace('.', '_', $ucmSubFormClient) . '_contentid';

												$ucmSubformFormObject = $tjucmItemModel->getFormExtra(
													array(
														"clientComponent" => 'com_tjucm',
														"client" => $ucmSubFormClient,
														"view" => $view[1],
														"layout" => 'default',
														"content_id" => $subFormData->$contentIdFieldname)
														);

												$ucmSubFormFormXml = simplexml_load_file($field->formsource);

												$ucmSubFormCount = 0;

												foreach ($ucmSubFormFormXml as $ucmSubFormXmlFieldSet)
												{
													$ucmSubFormXmlFieldSets[$ucmSubFormCount] = $ucmSubFormXmlFieldSet;
													$ucmSubFormCount++;
												}

												$ucmSubFormRecordData = $tjucmItemModel->getData($subFormData->$contentIdFieldname);

												// Call the JLayout recursively to render fields of ucmsubform
												$layout = new FileLayout('fields', JPATH_ROOT . '/components/com_tjucm/layouts/detail');
												echo $layout->render(array('xmlFormObject' => $ucmSubFormXmlFieldSets, 'formObject' => $ucmSubformFormObject, 'itemData' => $ucmSubFormRecordData, 'isSubForm' => 1));

												if (count($ucmSubFormData) > $count)
												{
													echo "<hr>";
												}
											}
										}
										?>
									</div>
								</div>
								<?php
							}
							elseif ($field->type == 'Calendar' && !empty($fieldValue))
							{
								// DPE - Hack  - Start

								$dateFomat = (String) $params->get('dateFormat');

								if ($field->showtime != 'false')
								{
									$dateFomat = (String) $params->get('dateTimeFormat');
								}
										// DPE - Hack  - End
								echo $output = HTMLHelper::date($fieldValue, $dateFomat);
							}
							elseif ($field->type == 'Cluster' && !empty($item->cluster_id))
							{
								$clusterTabel = ClusterFactory::table('Clusters');
								$clusterTabel->load(array('id' =>$item->cluster_id));

								if (($client == 'com_tjucm.sarlog' || $client == 'com_tjucm.breachlog' || $client =='com_tjucm.FOIlog') && !empty($clusterTabel->client_id))
								{
									JLoader::import('components.com_multiagency.includes.multiagency', JPATH_SITE);
									$multiagencyTable = Multiagency::table('multiagency');
									$multiagencyTable->load($clusterTabel->client_id);
									echo $multiagencyTable->title;

									// Fetch licence and SLA data for badges
									BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
									$schoolModel = BaseDatabaseModel::getInstance('School', 'DpeModel');
									$licenceData = $schoolModel->getLicenceSlaData($clusterTabel->client_id);

									if ($licenceData)
									{
										// Lite badge
										if (strpos((string)$licenceData->sla_name, 'DPO Lite') !== false)
										{
											echo ' <span class="badge btn-primary pull">Lite</span>';
										}

										// R badge
										$licence_tools = $schoolModel->getLicenceToolsList($licenceData->id);

										if (isset($licence_tools['com_dpe.redaction']))
										{
											echo ' <span class="badge btn-primary pull">R</span>';
										}
									}
								}
								else
								{
									echo $clusterTabel->name;
								}
							}
							elseif($field->type == 'numericcalculation')
							{
								$colorcombination = json_decode($field->getAttribute('colorcombination'));
								foreach($colorcombination as $key => $colors)
								{
									
									if (($fieldValue >= $colors->min) && ($fieldValue <= $colors->max) )
									{
										echo "<p class='numericcalculation' style='color:".$colors->color."'>".$colors->value."</p>";
									}
								}
							}
							else
							{
								$layoutToUse = (
									array_key_exists(
										ucfirst($tjFieldsFieldTable->type), $fieldLayout
									)
								) ? $fieldLayout[ucfirst($tjFieldsFieldTable->type)] : 'field';
								$layout = new FileLayout($layoutToUse, JPATH_ROOT . '/components/com_tjfields/layouts/fields');

								// DPE - Hack  - Start
								if ($field->type == 'Dpechecklist')
								{
									$checklistName = str_replace(
										['jform_', '[', ']'],
										'',
										$field->id
									);

									Table::addIncludePath(
										JPATH_ADMINISTRATOR . '/components/com_tjfields/tables'
									);

									$fieldDetail = Table::getInstance('field', 'TjfieldsTable');
									$fieldDetail->load(['name' => $checklistName]);

									$checklistParams = json_decode($fieldDetail->params);

									if (
										isset($checklistParams->enablechecklistscore)
										&& $checklistParams->enablechecklistscore
									)
									{
										if (
											isset($checklistParams->tjfields)
											&& is_array($checklistParams->tjfields)
										)
										{
											foreach ($checklistParams->tjfields as $optionValue)
											{
												if (
													isset($optionValue->numeric_value)
													&& $field->value == $optionValue->numeric_value
												)
												{
													$field->value = $optionValue->optionvalue;
												}
											}
										}
									}

									$field->value = ($field->value == 'todo')
										? 'To-Do'
										: (($field->value == 'inprogress')
											? 'In Progress'
											: (($field->value == 'done')
												? 'Done'
												: $field->value
											)
										);
								}
								// DPE - Hack  - End
								$output = $layout->render(array('fieldXml' => $fieldXml, 'field' => $field));

								// To align text, textarea, textareacounter, editor and tjlist fields properly
								if ($field->type == 'Textarea'|| $field->type == 'Textareacounter'|| $field->type == 'Text' || $field->type == 'Editor' || $field->type == 'tjlist')
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
												echo htmlspecialchars_decode($output);
											}?>
									</div>
									<?php
								}
								else
								{
									echo $output;
								}
							}
						}
					?>
				</td>
				<?php
			}
		}
	}

	if ($canEdit || $canDelete || $editown || $deleteOwn)
	{
	?>
	<td class="center  text-nowrap">
<!--
		DPE - Ovrride  - Start
		<a href="<?php echo $link; ?>" type="button" title="<?php echo Text::_('COM_TJUCM_VIEW_RECORD');?>"><i class="icon-eye-open"></i></a>
		DPE - Ovrride  - ends here
-->
	<?php
	if ($canEdit || $editown)
	{
		?>
		<span class="">
		<a class="btn btn-info actionbutton" href="<?php  echo 'index.php?option=com_tjucm&task=itemform.edit&id=' . $item->id . $appendUrl; ?>" type="button" title="<?php echo Text::_('COM_TJUCM_EDIT_ITEM_TITLE');?>">
			<i class="icon-apply" aria-hidden="true"></i><?php echo Text::_('COM_TJUCM_EDIT_ITEM');?>
		</a>
		</span>
		<?php
	}

	if ($canDelete || $deleteOwn)
	{
		?>
		<!-- <span class="">
		<a href="<?php echo 'index.php?option=com_tjucm&task=itemform.remove' . '&id=' . $item->id . $appendUrl . $csrf; ?>"
			class="delete-button" type="button"
			title="<?php echo Text::_('COM_TJUCM_DELETE_ITEM_TITLE');?>">
			<i class="icon-trash" aria-hidden="true"></i>
		</a>
		</span> -->
		<?php
	}

	?>
	&nbsp &nbsp 
	<a class="btn btn-info actionbuttondel" href="<?php echo $link; ?>" type="button" title="<?php echo Text::_('COM_TJUCM_VIEW__LIST_ITEM_TITLE');?>">
			<i class="fa fa-file-o" aria-hidden="true"></i>&nbsp <?php echo Text::_('COM_TJUCM_VIEW__LIST_ITEM');?>
		</a>
	</td>
	<?php
	}
	?>
</tr>
</div>

<script type="text/javascript">
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

jQuery(document).ready(function() {
    jQuery('#com_tjucm_sarlog_requeststatus_chosen').css('width', '300px');
    jQuery('#com_tjucm_breachlog_breachstatus_chosen').css('width', '300px');
    jQuery('#com_tjucm_FOIlog_requeststatus_chosen').css('width', '300px');

    jQuery('#com_tjucm_FOIlog_requeststatus_chosen .search-choice').each(function() {
        if (jQuery(this).find('span').text() == 'Select Request status') {
        	jQuery(this).find('span').text('- Select Request status - ');
        	jQuery(this).removeClass('search-choice');
            jQuery(this).css({'background': 'none', 'border': 'none', 'color': '#9f9c9c'});
            jQuery(this).find('.search-choice-close').remove();
        }
    });
    jQuery('#com_tjucm_sarlog_requeststatus_chosen .search-choice').each(function() {
        if (jQuery(this).find('span').text() == 'Select Request status') {
        	jQuery(this).find('span').text('- Select Request status - ');
        	jQuery(this).removeClass('search-choice');
            jQuery(this).css({'background': 'none', 'border': 'none', 'color': '#9f9c9c'});
            jQuery(this).find('.search-choice-close').remove();
        }
    });
    jQuery('#com_tjucm_breachlog_breachstatus_chosen .search-choice').each(function() {
        if (jQuery(this).find('span').text() == 'Select Breach status') {
        	jQuery(this).find('span').text('- Select Breach status - ');
        	jQuery(this).removeClass('search-choice');
            jQuery(this).css({'background': 'none', 'border': 'none', 'color': '#9f9c9c'});
            jQuery(this).find('.search-choice-close').remove();
        }
    });
});

</script>
