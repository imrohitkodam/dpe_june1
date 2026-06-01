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
use Joomla\CMS\Layout\FileLayout;
JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;

if (!key_exists('itemsData', $displayData))
{
	return;
}

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

$params              = ComponentHelper::getParams('com_dpe');
$codeDataFieldConfig = json_decode($params->get('coredatatitlefields'), true);

if (!empty($client) && array_key_exists($client, $codeDataFieldConfig))
{
	$fieldUniqueName = $codeDataFieldConfig[$client];
}


// DPE - Hack  - End
?>
<?php

	if (!empty($item))
	{
		$orgName = '';
		$ucmDataId = '';

		foreach ($item as $key => $fieldValue)
		{

			if ($key == 'id')
			{
				$ucmDataId = $fieldValue;
			}

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
					<?php
						if ($canView || ($item->created_by == $user->id))
						{
							if ($field->type == 'Cluster' && !empty($item->cluster_id))
							{
								$clusterTabel = ClusterFactory::table('Clusters');
								$clusterTabel->load(array('id' =>$item->cluster_id));

								if (property_exists($clusterTabel, 'name'))
								{
									$orgName = $clusterTabel->name;
								}
							}
							else if ($tjFieldsFieldTable->name == $fieldUniqueName)
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
									<li class="list-group-item" data-ucm-id="<?php echo $ucmDataId; ?>" data-org-name="<?php echo $orgName; ?>">
										<?php echo $output; ?><?php echo ' ('.$orgName.') '?>
									</li>
									<?php
								}
								else
								{
									echo $output;
								}
							}
						}
					?>
				<?php
			}
		}
	}
?>
