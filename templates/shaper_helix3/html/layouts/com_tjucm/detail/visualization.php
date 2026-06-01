<?php
/**
 * @package	TJ-UCM
 *
 * @author	 TechJoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

$document = Factory::getDocument();
 $document->addScript(Uri::root() . 'media/vendor/jquery/js/jquery.min.js');
$document->addStyleSheet(Uri::root() . 'media/com_dpe/css/dpe.css');
$document->addScript(Uri::root() . 'media/com_dpe/js/mermaid.min.js');
$document->addScript(Uri::root() . 'media/com_dpe/js/dom-to-image.js');
$document->addScript(Uri::root() . 'media/com_dpe/js/FileSaver.js');


JLoader::import('clusters', JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
$clustersTable  = Table::getInstance('clusters', 'ClusterTable', array());
$app            = Factory::getApplication();
$ROPDescription = '';

$clusterId = $app->input->get('cluster_id', 0, 'INT');

if ($clusterId)
{
	$clustersTable->load(array('id' => (int) $clusterId));
	$organizationName = ucwords($clustersTable->name);
}

$params                  = ComponentHelper::getParams('com_dpe');
$boxColors               = $params->get('box_color');
$borderColors            = $params->get('border_color');
$arrowColors             = $params->get('line_color');
$visualizationFields     = json_decode($params->get('visualisationFields'));
$visualizationFields     = (array) $visualizationFields[0];

// Field list for which we need data
$visualizationDataFields = array_keys($visualizationFields);

array_push($visualizationDataFields, 'com_tjucm_ropdataflow_contentid', 'com_tjucm_ropdataflow_parentstepindataflow','com_tjucm_ropdataflow_systemsecurity', 'com_tjucm_ropdataflow_whereisthedataprocessed');

$boxColors    = json_decode(json_encode($boxColors), true);
$borderColors = json_decode(json_encode($borderColors), true);
$arrowColors  = json_decode(json_encode($arrowColors), true);

// In school box color
$boxColor0      = $boxColors['box_color0']['box_color_code'];
$boxColor0Value = $boxColors['box_color0']['box_color_field_value'];

// Out of school box color
$boxColor1      = $boxColors['box_color1']['box_color_code'];
$boxColor1Value = $boxColors['box_color1']['box_color_field_value'];

// other school
$boxColor2      = $boxColors['box_color2']['box_color_code'];
$boxColor2Value = $boxColors['box_color2']['box_color_field_value'];

// Border None/unknown
$borderColor0      = $borderColors['border_color0']['border_color_code'];
$borderColor0Value = $borderColors['border_color0']['border_color_field_value'];

// other
$borderColor1      = $borderColors['border_color1']['border_color_code'];
$borderColor1Value = $borderColors['border_color1']['border_color_field_value'];


// Arrow None/unknown
$arrowColor0      = $arrowColors['line_color0']['line_color_code'];
$arrowColor0Value = $arrowColors['line_color0']['line_color_field_value'];

// other
$arrowColor1      = $arrowColors['line_color1']['line_color_code'];
$arrowColor1Value = $arrowColors['line_color1']['line_color_field_value'];


// Add color dynamically
if ($boxColor0)
{
	$boxColorCSS = ".inschool rect,circle,ellipse,polygon {
	  fill: {$boxColor0};
	  color: #FFF;
	}";
	$document->addStyleDeclaration($boxColorCSS);
}

if ($boxColor1)
{
	$boxColorCSS = ".outofschool rect,circle,ellipse,polygon {
	  fill: {$boxColor1};
	  color: #FFF;
	}";
	$document->addStyleDeclaration($boxColorCSS);
}

if ($boxColor2)
{
	$boxColorCSS = ".otherschool rect,circle,ellipse,polygon {
	  fill: {$boxColor2};
	  color: #FFF;
	}";
	$document->addStyleDeclaration($boxColorCSS);
}


// Add Border dynamically
if ($borderColor0)
{
	$boxColorCSS = ".securitynone rect,circle,ellipse,polygon {
	  stroke: {$borderColor0};
	  stroke-width: 3px;
	}";
	$document->addStyleDeclaration($boxColorCSS);
}

if ($borderColor1)
{
	$boxColorCSS = ".securityother rect,circle,ellipse,polygon {
	  stroke: {$borderColor1};
	  stroke-width: 3px;
	}";
	$document->addStyleDeclaration($boxColorCSS);
}

if (!key_exists('formObject', $displayData) || !key_exists('xmlFormObject', $displayData))
{
	return;
}

$user = Factory::getUser();

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
JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
$tjFieldsFieldTable = Table::getInstance('field', 'TjfieldsTable');

$fieldSets                = $formObject->getFieldsets();
$count                    = 0;
$visualizationData        = array();
$isParentExistInUCMRecord = 0;
$isUCMRecordInvalid       = 0
?>
<?php
// Load contentform model to get content id
JLoader::import('document', JPATH_SITE . '/components/com_tjucm/models');
$documentModel = new TjucmModelDocument;

// Iterate through the normal form fieldsets and display each one
foreach ($fieldSets as $fieldset)
{
	$xmlFieldSet = $xmlFormObject[$count];
	$count++;
	$fieldCount = 0;

	foreach ($formObject->getFieldset($fieldset->name) as $field)
	{

		if ($field->name == 'jform[com_tjucm_rop_descriptionofprocess]')
		{
			$ROPDescription = ucfirst($field->value);
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
							$subFormCount = 0;

							foreach ($ucmSubFormFieldValue as $ucmSubFormData)
							{
								if ($ucmSubFormClient != 'com_tjucm.ropdataflow')
								{
									continue;
								}

								$contentIdFieldname = str_replace('.', '_', $ucmSubFormClient) . '_contentid';

								$ucmSubformFormObject = $tjucmItemModel->getFormExtra(
									array(
										"clientComponent" => 'com_tjucm',
										"client" => $ucmSubFormClient,
										"view" => $view[1],
										"layout" => 'default',
										"content_id" => $ucmSubFormData->$contentIdFieldname)
										);

								// Check if UCM record is invalid for visualization
								if (!array_key_exists('com_tjucm_ropdataflow_parentstepindataflow', (array) $ucmSubFormData))
								{
									$isParentExistInUCMRecord = 1;
								}

								if ($ucmSubFormData->com_tjucm_ropdataflow_parentstepindataflow == $ucmSubFormData->com_tjucm_ropdataflow_contentid)
								{
									$isUCMRecordInvalid = 1;
								}

								foreach ($ucmSubFormData as $ucmSubFormFieldName => $fieldValues)
								{
									$fieldName = trim($ucmSubFormXmlField['name']);
									Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
									$fieldInstance = Table::getInstance('field', 'TjfieldsTable');
									$fieldInstance->load(array('name' => "{$ucmSubFormFieldName}"));

									// Get data for configured fields only
									if (isset($ucmSubFormFieldName) && in_array(trim($ucmSubFormFieldName), $visualizationDataFields))
									{

										if ($fieldInstance->type == 'related' && $ucmSubFormFieldName != 'com_tjucm_ropdataflow_parentstepindataflow')
										{
											unset($relatedValueArray);
											$relatedValueArray = array();

											$fieldData = new Registry($fieldInstance->params);
											// $fieldDataArray = array_flatten($fieldData['fieldName']);

											// Array flatten
											$fieldDataArray = array();

											array_walk_recursive($fieldData['fieldName'], function($x) use (&$fieldDataArray) { $fieldDataArray[] = $x; });


											if ($fieldInstance->id)
											{
												$relatedfieldValues = $documentModel->getFieldValues($fieldValues, $fieldDataArray[0]->fieldIds);
											}

											foreach ($relatedfieldValues as $fieldValue)
											{
												$relatedValueArray[] = $fieldValue->value;
											}

											if (!isset($visualizationData[$subFormCount]["{$ucmSubFormFieldName}"]))
											{
												$visualizationData[$subFormCount]["{$ucmSubFormFieldName}"] .= implode(', ', $relatedValueArray);
											}
										}
										elseif($fieldInstance->type == 'tjlist')
										{


											$fvalue = $ucmSubformFormObject->getValue("{$ucmSubFormFieldName}");

											// Convert field value to array
											if (!is_array($fvalue))
											{
												$fvalue = array($fvalue);
											}

											$fieldValues = $documentModel->getFieldOptions($fieldInstance->id, $fvalue);

											$tjlistValueArray = array();

											if (count($fieldValues) == 1)
											{
												$tjlistValueArray[] = $fieldValues['0']->options;
											}
											else
											{
												foreach ($fieldValues as $fieldValue)
												{
													$tjlistValueArray[] = $fieldValue->options;
												}
											}

											$fieldData = new Registry($fieldInstance->params);

											if ($fieldData->get('other') == 1)
											{
												foreach ($fvalue as $default_option)
												{
													if (strpos($default_option, 'tjlist:-') !== false)
													{
														$tjlistValueArray[] = str_replace('tjlist:-', '', $default_option);
													}
												}
											}

											$visualizationData[$subFormCount]["{$ucmSubFormFieldName}"] .= implode(', ', $tjlistValueArray);


										}
										else
										{
											$visualizationData[$subFormCount]["{$ucmSubFormFieldName}"] = $ucmSubformFormObject->getValue("{$ucmSubFormFieldName}");
										}

									}
								}
								$subFormCount++;
							}
						}
			}
		}
	}
} 
// Generate Mermaid String
$mermaidString            = 'graph TB
';
$visualizationCnt         = 0;
$mermaidParentChildString = '';
$mermaidColorCoding       = '';
$mermaidLineColor         = '';

if (!$isParentExistInUCMRecord || $isUCMRecordInvalid)
{
	$app->enqueueMessage(Text::_('COM_TJUCM_ROP_VISUALISATION_INVALID_RECORD'), 'error');
	
	return;
}

foreach ($visualizationData as $visualization)
{
	$mermaidString .= ' DATAFLOW' . $visualizationCnt;
	$mermaidString .= '(';

	$fieldCount = 0;
	foreach ($visualizationFields as $visualizationFieldName => $visualizationFieldLabel)
	{
		$fieldDataArr = explode(",", $visualization[$visualizationFieldName]);
		$parts        = array_chunk($fieldDataArr, 2);
		$valueStr     = "";

		if (count($parts) > 1)
		{
			$fieldValueCount = 0;

			foreach ($parts as $part)
			{

				if (!$fieldValueCount)
				{
                  $valueStr .= implode(",", $part);
				  $valueStr = str_replace(")"," ", $valueStr);
				  $valueStr = str_replace(")"," ", $valueStr);
                }
				else
				{
					$valueStr .= ',<br>';
                    $valueStr .= implode(",", $part);
    				$valueStr = str_replace(")"," ", $valueStr);
				    $valueStr = str_replace(")"," ", $valueStr);

                }

				$fieldValueCount++;
			}
		}
		else
		{
			if (!empty($visualization[$visualizationFieldName]))
			{
              $visualization[$visualizationFieldName] = str_replace("("," ", $visualization[$visualizationFieldName]);
              $visualization[$visualizationFieldName] = str_replace(")"," ", $visualization[$visualizationFieldName]);
              $valueStr = $visualization[$visualizationFieldName];
			}
			else
			{
				$valueStr = ' - ';
			}
		}

		if (!$fieldCount)
		{
              $visualization[$visualizationFieldName] = str_replace("("," ", $visualization[$visualizationFieldName]);
              $visualization[$visualizationFieldName] = str_replace(")"," ", $visualization[$visualizationFieldName]);

          $mermaidString .= $visualizationFieldLabel. $visualization[$visualizationFieldName] . ' ';
		}
		else
		{
			$mermaidString .= '<br>' . $visualizationFieldLabel . $valueStr . ' ';
		}

		$fieldCount++;
	}

	$mermaidString .= ')';
	$mermaidString .= '
';

	if ($visualization['com_tjucm_ropdataflow_parentstepindataflow'])
	{
		$key = array_search($visualization['com_tjucm_ropdataflow_parentstepindataflow'], array_column($visualizationData, 'com_tjucm_ropdataflow_contentid'));
		$key = $key? $key: 0;
		$mermaidParentChildString .= '
DATAFLOW' . $key . ' --> ' . 'DATAFLOW'. $visualizationCnt;
	}

	// In school or Out of school
	$whereIsDataProccessed  = array_map('trim', explode(',', $visualization['com_tjucm_ropdataflow_whereisthedataprocessed']));
	$systemSecurity         = array_map('trim', explode(',', $visualization['com_tjucm_ropdataflow_systemsecurity']));
	$securityWhenMovingData = array();

	if ($visualization['com_tjucm_ropdataflow_parentstepindataflow'])
	{
		$securityWhenMovingData = array_map('trim', explode(',', $visualizationData[$key]['com_tjucm_ropdataflow_securitywhenmovingdata']));
	}

	// Box color on the basis of In school or Out of school
	if (in_array($boxColor0Value, $whereIsDataProccessed) && !in_array($boxColor1Value, $whereIsDataProccessed))
	{
		$mermaidColorCoding .= '

class DATAFLOW'. $visualizationCnt.' inschool';
	}
	elseif (in_array($boxColor1Value, $whereIsDataProccessed) && !in_array($boxColor0Value, $whereIsDataProccessed))
	{
		$mermaidColorCoding .= '

class DATAFLOW'. $visualizationCnt.' outofschool';
	}
	else
	{
		$mermaidColorCoding .= '

class DATAFLOW'. $visualizationCnt.' otherschool';
	}

	// Border System Secuirty field value None/unknown
	if (in_array($borderColor0Value, $systemSecurity))
	{
		$mermaidColorCoding .= '

class DATAFLOW'. $visualizationCnt.' securitynone';
	}
	else
	{
		$mermaidColorCoding .= '

class DATAFLOW'. $visualizationCnt.' securityother';
	}

	if (!empty($securityWhenMovingData) && $visualization['com_tjucm_ropdataflow_parentstepindataflow'])
	{
		if (in_array($arrowColor0Value, $securityWhenMovingData))
		{
			$mermaidLineColor .= '

linkStyle '. ($visualizationCnt - 1)." stroke:{$arrowColor0},stroke-width:2px,fill:none";
		}
		else
		{
			$mermaidLineColor .= '

linkStyle '. ($visualizationCnt - 1)." stroke:{$arrowColor1},stroke-width:2px,fill:none";
		}
	}

	$visualizationCnt++;
}

$mermaidString .= $mermaidParentChildString;
$mermaidString .= $mermaidColorCoding;
$mermaidString .= $mermaidLineColor;
$imageName = !empty(substr($ROPDescription,0,50)) ? substr($ROPDescription,0,50).'.png' : 'ROP.png';
?>
<script>
	document.onreadystatechange = function() {
		if (document.readyState !== "complete") {
			document.querySelector("#visualizationCover").style.visibility = "hidden";
			document.querySelector("#visualizationLoader").style.visibility = "visible";
		} else {
			setTimeout(function()
			{
				document.querySelector("#visualizationLoader").style.display = "none";
				document.querySelector("#visualizationCover").style.visibility = "visible";
			}, 3000);
		}
	};
</script>
<div class="scroll-fix height-100vh">
	<div id="visualizationLoader" class="centerloader"></div>
	<div class="row">
		<div class="model-header col-xs-12 border-bottom">
		<h3 class="pull-left m-0 p-0 pl-10"><?php echo substr($ROPDescription, 0, 100); ?></h3>
		<button id="visualizationDownload"  type="button" class="btn btn-primary tj-ucm-document-download ml-10 pull-right" >
			<i class="fa fa-download mr-10"></i> Download
		</button>
		</div>
	</div>
	<div class="height-vh overflow-y-auto">
		<div class="mb-25" id="visualizationCover" style="hidden;">
			<div class="  center">
				<img class="sp-default-logo hidden-xs mt-20 ml-25" src="<?php echo JURI::root();?>/images/dpe-logo.jpg" alt="DPE Knowledge Bank">
				<h3 style="text-align:center;"><?php echo $organizationName; ?></h3>
			</div>
			<div class="mermaid center" style="text-align:center;">
				<?php echo $mermaidString; ?>
			</div>
			<div class="mb-25 ml-20 pull-right">
				<div class="mb-10">
				  <div class="mb-10">
					<p class="mr-5" style="font-size: 12px;line-height: 24px;font-weight: normal;padding: 0;margin: 0;">
						<span style="width: 25px; height: 25px; margin:auto; display: inline-block; border: 1px solid gray; vertical-align: middle; border-radius: 2px; background: <?php echo $boxColor0; ?> ">
						</span>
					<span class=""><?php echo Text::_('COM_TJUCM_ROP_VISUALISATION_BOX_COLOR_LBL_IN_SCHOOL'); ?></span>
					</p>
				  </div>
				  <div class="mb-10">
					<p style="font-size: 12px;line-height: 24px;font-weight: normal;padding: 0;margin: 0;">
						<span style="width: 25px; height: 25px; margin:auto; display: inline-block; border: 1px solid gray; vertical-align: middle; border-radius: 2px; background: <?php echo $boxColor1; ?> ">
						</span>
					<span class=""><?php echo Text::_('COM_TJUCM_ROP_VISUALISATION_BOX_COLOR_LBL_OUT_OF_SCHOOL'); ?></span>
					</p>
				  </div>
				  <div class="mb-10">
					<p style="font-size: 12px;line-height: 24px;font-weight: normal;padding: 0;margin: 0;">
						<span style="width: 25px; height: 25px; margin:auto; display: inline-block; border: 1px solid gray; vertical-align: middle; border-radius: 2px; background: <?php echo $boxColor2; ?> ">
						</span>
					<span class=""><?php echo Text::_('COM_TJUCM_ROP_VISUALISATION_BOX_COLOR_LBL_SCHOOL_OTHER'); ?></span>
					</p>
				  </div>
				  <div class="mb-10">
					<p style="font-size: 12px;line-height: 24px;font-weight: normal;padding: 0;margin: 0;">
						<span style="width: 25px; height: 25px; margin:auto; display: inline-block; border: 1px solid gray; vertical-align: middle; border-radius: 2px; background: <?php echo $borderColor0; ?> ">
						</span>
					<span class=""><?php echo Text::_('COM_TJUCM_ROP_VISUALISATION_BORDER_COLOR_LBL_SYSTEM_SECURITY'); ?></span>
					</p>
				  </div>
				  <div class="mb-10">
					<p style="font-size: 12px;line-height: 24px;font-weight: normal;padding: 0;margin: 0;">
						<span style="width: 25px; height: 25px; margin:auto; display: inline-block; border: 1px solid gray; vertical-align: middle; border-radius: 2px; background: <?php echo $borderColor1; ?> ">
						</span>
					<span class=""><?php echo Text::_('COM_TJUCM_ROP_VISUALISATION_BORDER_COLOR_LBL_SYSTEM_SECURITY_OTHER'); ?></span>
					</p>
				  </div>
				  <div class="mb-10">
					<p style="font-size: 12px;line-height: 24px;font-weight: normal;padding: 0;margin: 0;">
						<span style="width: 25px; height: 25px; margin:auto; display: inline-block; border: 1px solid gray; vertical-align: middle; border-radius: 2px; background: <?php echo $arrowColor0; ?> ">
						</span>
					<span class=""><?php echo Text::_('COM_TJUCM_ROP_VISUALISATION_ARROW_COLOR_LBL_SECURITY_WHEN_MOVING'); ?></span>
					</p>
				  </div>
				  <div class="mb-10">
					<p style="font-size: 12px;line-height: 24px;font-weight: normal;padding: 0;margin: 0;">
						<span style="width: 25px; height: 25px; margin:auto; display: inline-block; border: 1px solid gray; vertical-align: middle; border-radius: 2px; background: <?php echo $arrowColor1; ?> ">
						</span>
					<span class=""><?php echo Text::_('COM_TJUCM_ROP_VISUALISATION_ARROW_COLOR_LBL_SECURITY_WHEN_MOVING_OTHER'); ?></span>
					</p>
				  </div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
mermaid.flowchartConfig = {
  width: "100%"
}

mermaid.initialize({
  theme: "none",
  flowchart: {
	curve: "basis"
  }
});
</script>

<script>
document.getElementById("visualizationDownload").addEventListener("click", function() {
domtoimage.toBlob(document.getElementById('visualizationCover'), { bgcolor: 'white' })
    .then(function (blob) {
        window.saveAs(blob,'<?php echo$imageName; ?>');
    });
});
</script>
