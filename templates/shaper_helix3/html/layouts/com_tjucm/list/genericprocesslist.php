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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;

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

// Get generic filter update
$input         = Factory::getApplication()->input;
$filterProcess = $input->get('filter_process', 'myprocess', "STRING");

$params     			   = ComponentHelper::getParams('com_multiagency');
 $orgAdminRoleId           = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
 $orgAdminRoleId 		   = in_array($orgAdminRoleId, $user->groups);

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

// DPE - Hack  - End
?>
<div class="overlay" id="loader-overlay">
	<div class="loader"></div>
</div>
<div class="tjucm-wrapper">
<tr class="row<?php echo $item->id?>">
	<?php if ($canCopyItem) { ?>
	<!-- TODO- copy and copy to other feature is not fully stable hence relate buttons are hidden-->
	<td class="center">
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

		<!-- <a href="<?php echo $link; ?>" type="button" title="<?php echo Text::_('COM_TJUCM_VIEW_ITEM');?>">
			<?php echo $this->escape($item->id); ?>
		</a> -->
		<?php echo $this->escape($item->id); ?>
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
		?>
		<td>
			<a href="<?php echo 'index.php?option=com_tjucm&task=itemform.edit&id=' . $item->id . $appendUrl; ?>" type="button" title="<?php echo ($item->draft) ? Text::_('COM_TJUCM_DATA_STATUS_DRAFT') : Text::_('COM_TJUCM_DATA_STATUS_SAVE'); ?>">
			<?php
if ($item->draft && ($filterProcess == 'generic' && !$orgAdminRoleId)) {
    echo '<i class="fa fa-pencil-square-o" aria-hidden="true"></i>';
} else if ($item->draft && $filterProcess != 'generic') {
    echo '<i class="fa fa-pencil-square-o" aria-hidden="true"></i>';
} else if ($filterProcess == 'generic' && !$orgAdminRoleId) {
    echo '<i class="fa fa-floppy-o" aria-hidden="true"></i>';
} else if ($filterProcess != 'generic') {
    echo '<i class="fa fa-floppy-o" aria-hidden="true"></i>';
}
?>

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
				$field->setValue($fieldValue);

				$isCalendarField = 0;
				$isCalendarField = ($field->type == 'Calendar') ? 1 : 0;
				?>
				<td class="<?php echo $isCalendarField ? 'text-nowrap' :(($field->class == 'riskdescription') ? 1 :(85 - $displayData['statusColumnWidth']) / count($displayData['listcolumn'])) . '%';?>">
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

								if (property_exists($clusterTabel, 'name'))
								{
									//echo $clusterTabel->name;
								}
							}
							if($field->type == 'numericcalculation')
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
								$output = $layout->render(array('fieldXml' => $fieldXml, 'field' => $field));

								// To align text, textarea, textareacounter, editor and tjlist fields properly
								if ($field->type == 'Textarea'|| $field->type == 'Textareacounter'|| $field->type == 'Text' || $field->type == 'Editor' || $field->type == 'tjlist')
								{
									?>
									<div class="tj-wordwrap <?php echo ($field->class == 'riskdescription') ? 'genericdescription':''?> <?php echo ($field->class == 'risktitleheight') ? 'risktitleheight':''?>">
										<?php echo ($field->class == 'riskdescription')? Text::_('COM_TJUCM_VIEW_DESCRIPTION')."&nbsp&nbsp&nbsp&nbsp              " :'';

												echo $output; ?>
									</div>
									<?php
								}
								else
								{
									echo $output;
								}?>
							<?php }
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
	<td class="center text-nowrap" >
<!--
		DPE - Ovrride  - Start
		<a href="<?php echo $link; ?>" type="button" title="<?php echo Text::_('COM_TJUCM_VIEW_RECORD');?>"><i class="icon-eye-open"></i></a>
		DPE - Ovrride  - ends here
-->
	<?php
	if ($canEdit || $editown)
	{
		if(($filterProcess == 'generic' && !$orgAdminRoleId))
		{?>
			<span class="">
		<a class="btn btn-info actionbutton" href="<?php  echo 'index.php?option=com_tjucm&task=itemform.edit&id=' . $item->id . $appendUrl; ?>" type="button" title="<?php echo Text::_('COM_TJUCM_EDIT_ITEM_TITLE');?>">
			<i class="icon-apply" aria-hidden="true"></i> <?php echo Text::_('COM_TJUCM_EDIT_ITEM');?>
		</a>
		</span>
			
		<?php }else if(($filterProcess != 'generic')){ ?>
			
			<span class="">
		<a class="btn btn-info actionbutton" href="<?php  echo 'index.php?option=com_tjucm&task=itemform.edit&id=' . $item->id . $appendUrl; ?>" type="button" title="<?php echo Text::_('COM_TJUCM_EDIT_ITEM_TITLE');?>">
			<i class="icon-apply" aria-hidden="true"></i> <?php echo Text::_('COM_TJUCM_EDIT_ITEM');?>
		</a>
		</span>
			
			<?php
			}
		?>
		
		<?php
	}

	if ($canDelete || $deleteOwn || $orgAdminRoleId)
	{
		?>
		&nbsp &nbsp
		<span class="">
		<a class="btn btn-info  actionbuttondel" href="<?php echo $link; ?>" type="button" title="<?php echo Text::_('COM_TJUCM_VIEW__LIST_ITEM_TITLE');?>">
					<i class="fa fa-file-o" aria-hidden="true"></i>&nbsp  <?php echo Text::_('COM_TJUCM_VIEW__LIST_ITEM');?>

		</a>
		</span>
		<?php
	}
	?>
	</td>
	<?php
	}
	?>
</tr>
</div>


<script type="text/javascript">
  customeheight = jQuery('.row<?php echo $item->id?> .risktitleheight').height();
     jQuery('.row<?php echo $item->id?> .genericdescription').css('margin-top', (customeheight + 20) +"px");
</script>
