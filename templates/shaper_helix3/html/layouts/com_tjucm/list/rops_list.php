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
JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;

if (!key_exists('itemsData', $displayData))
{
	return;
}

$fieldsData = $displayData['fieldsData'];
$app = Factory::getApplication();
$user = Factory::getUser();

// Layout for field types
$fieldLayout = array();
$fieldLayout['File'] = $fieldLayout['Image'] = "file";
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
$documents     = $displayData['documents'];


$allowDraftSave = $displayData['ucmTypeParams']->allow_draft_save;
$i = isset($displayData['key']) ? $displayData['key'] : '';


$appendUrl = '';
$csrf = "&" . Session::getFormToken() . '=1';

$canEditOwn   = TjucmAccess::canEditOwn($ucmTypeId, $item->id);

// DPE override changes - DPE Roles should use action from UCM and Also changes for DPE Admin
$canEditOwn   = TjucmAccess::canEditOwn($ucmTypeId, $item->id);
$canDeleteOwn = TjucmAccess::canDeleteOwn($ucmTypeId, $item->id);
$canEditState = TjucmAccess::canEditState($ucmTypeId, $item->id);
$canEdit      = TjucmAccess::canEdit($ucmTypeId, $item->id);
$canDelete    = TjucmAccess::canDelete($ucmTypeId, $item->id);
$canCopyItem  = TjucmAccess::canCopyItem($ucmTypeId, 0, $item->cluster_id);

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

// Check document generate permission
$isDocumentGenerate = true;

if (!$user->authorise('core.manageall', 'com_cluster'))
{
	$isDocumentGenerate = RBACL::check($user->id, 'com_cluster', 'document.generate', 'com_multiagency', $item->cluster_id);
}


// DPE - Hack  - Start
JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
$params = DPE::config();
$dateFomat = (String) $params->get('dateFormat');
// DPE - Hack  - End
?>
<div class="tjucm-wrapper">
<tr class="row<?php echo $item->id?>">
	<?php if ($canCopyItem) { ?>
	<!-- TODO- copy and copy to other feature is not fully stable hence relate buttons are hidden-->
	<td class="center check-column">
		<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
	</td>
	<?php  } ?>

	<?php
	if ($canEdit || $editown)
	{
		?>
		<!-- <td>
			<a href="<?php //echo 'index.php?option=com_tjucm&task=itemform.edit&id=' . $item->id . $appendUrl; ?>" type="button" title="<?php echo ($item->draft) ? Text::_('COM_TJUCM_DATA_STATUS_DRAFT') : Text::_('COM_TJUCM_DATA_STATUS_SAVE'); ?>">
			<?php if ($item->draft) : ?>
				<i class="fa fa-pencil-square-o" aria-hidden="true"></i>
			<?php else : ?>
				<i class="fa fa-floppy-o" aria-hidden="true"></i>
			<?php endif;?>
			</a>
		</td> -->
	<?php
	}
	else
	{
	?>
	<!-- If don't have any permission -->
<!--
	<td>
		<?php
			// echo '-';
		?>
	</td>
-->
	<?php
	}

	if (!empty($item))
	{ 

	Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
  	     $tjFieldOptionTable = Table::getInstance('Option', 'TjfieldsTable');
            
            
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
				$field = $formObject->getField($tjFieldsFieldTable->name);
				$field->setValue($fieldValue);

				$isCalendarField = 0;
				$isCalendarField = ($field->type == 'Calendar') ? 1 : 0;

				$ropStatusClass = '';
				
				if ($field->name == 'jform[com_tjucm_rop_neworexisting]')
				{   		
					$documentWithProcess = array();

					foreach ($documents as $dockey =>  $document)
					{ 
					    $tjFieldOptionTable->load(array('id' => $document->ropprocess));

						if (($tjFieldOptionTable->value == $fieldValue) && ($document->ropprocess != 0))
						{   
							$documentWithProcess[$dockey] = $document;
						}
					 }
				}

				if ($field->name == 'jform[com_tjucm_rop_status]')
				{
					$ropStatusClass = ($fieldValue == 'DPO Review') ? "dpo-review" : (($fieldValue == 'Complete')  ? "complete" : "in-progress");
				}
				?>
				<td class="tj-wordwrap <?php echo $isCalendarField ? 'text-nowrap' :(85 - $displayData['statusColumnWidth']) / count($displayData['listcolumn']) . '%';?> ">
				<?php
				if ($canView || ($item->created_by == $user->id))
				{
				?>
					<?php
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

								if (property_exists($clusterTabel, 'name'))
								{
									echo $clusterTabel->name;
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

								$output = $layout->render(array('fieldXml' => $fieldXml, 'field' => $field));

								// To align text, textarea, textareacounter, editor and tjlist fields properly
								if ($field->type == 'Textarea'|| $field->type == 'Textareacounter'|| $field->type == 'Text' || $field->type == 'Editor' || $field->type == 'tjlist')
								{
									if ($field->name == 'jform[com_tjucm_rop_descriptionofprocess]')
									{
										
									?>
										<a href="<?php echo Route::_('index.php?option=com_tjucm&task=itemform.edit&id=' . $item->id . $appendUrl); ?>" type="button" title="<?php echo $output;?>" class="">
											<!-- Text::_('COM_TJUCM_EDIT_ITEM') -->
											<div class="tj-wordwrap">
												<span><?php echo $output; ?></span>

											</div>
										</a>
										<span  class="float-start"><?php echo $statusText = $item->draft ? 'Drafted' : 'Submitted';?></span>
									<?php
										
									}else
									{
										?>
										<div class="tj-wordwrap">
											<?php if ($output): ?>
											<span class="<?php echo $ropStatusClass; ?>"><?php echo $output; ?></span>
											<?php endif; ?>
										</div>
										<?php
									}
								}
								else
								{
									echo $output;
								}
							}
				}
				?></td><?php
			}
		}
	}

	if ($canEdit || $canDelete || $editown || $deleteOwn)
	{
	?>
	<!-- <td class="center">

	<?php
	if ($canEdit || $editown)
	{
		?>
			<a href="<?php //echo 'index.php?option=com_tjucm&task=itemform.edit&id=' . $item->id . $appendUrl; ?>" type="button" title="<?php //echo Text::_('COM_TJUCM_EDIT_ITEM');?>" class="pr-10">
				<i class="icon-apply" aria-hidden="true"></i>
			</a>
		<?php
	}

	if ($canDelete || $deleteOwn)
	{
		?>
		<a href="<?php //echo 'index.php?option=com_tjucm&task=itemform.remove' . '&id=' . $item->id . $appendUrl . $csrf; ?>"
			class="delete-button" type="button" onclick="return confirm('<?php //echo Text::_('COM_TJUCM_DELETE_CONFIRM_ITEM');?>');"
			title="<?php //echo Text::_('COM_TJUCM_DELETE_ITEM');?>">
			<i class="icon-trash" aria-hidden="true"></i>
		</a>
		<?php
	}
	?>
	</td> -->
	<?php
	}
	?>
	<td class="center">
		<?php
		$ROPlink = 'index.php?option=com_tjucm&view=itemform&client=' . $item->client . '&id=' . $item->id;
		$ROPItemId = $tjUcmFrontendHelper->getItemId($ROPlink);

		$docLink = Route::_('index.php?option=com_tjucm&view=item&id='.$item->id.'&cluster_id='.$item->cluster_id.'&Itemid=' . $ROPItemId . '&layout=visualization&tmpl=component', false); ?>

		<a class="d-inline-block mr-4" href="javascript:void(0);" onclick="openVisualizationPopup('<?php echo $docLink;?>')" title="<?php echo JTEXT::_('COM_TJUCM_DOCUMENT_PREVIEW');?>" >
				<i class="fa fa-file-text" aria-hidden="true"></i>
		</a>
	</td>
	<?php if ($isDocumentGenerate) : ?>
		<td class="center">
			<?php
			$documentOptions = $userOptions = array();
			$documentOptions[] = HTMLHelper::_('select.option', "", TEXT::_('COM_TJUCM_ROP_SELECT_DOCUMENT_LBL'));

			foreach ($documentWithProcess as $document)
			{
				$documentLink = Route::_('index.php?option=com_tjucm&view=document&id=' . $document->id . '&tmpl=component&cluster_id=' . $item->cluster_id.'&ucm_id='.$item->id, false);

				$documentOptions[] = HTMLHelper::_('select.option', trim($documentLink), trim($document->title));
			}
			?>
			<?php
			if (!$item->draft)
			{?>
				<!-- <div class="doc-dropdown">
					<button class="fa fa-chevron-down"></button>
				</div> -->
			<?php
				echo HTMLHelper::_('select.genericlist', $documentOptions, '', 'class="rop-documents" size="1" id="rop-documents-' . $item->id . '" onchange="openDocument(this.value,' . $item->id . ');"', 'value', 'text');
			}
			else
			{
				echo '-';
			}
			 ?>
		</td>
	<!-- if dont have access then show empty table data -->
    <?php else : ?>
		<td><?php echo '-'; ?></td>
	<?php endif; ?>
</tr>
</div>
