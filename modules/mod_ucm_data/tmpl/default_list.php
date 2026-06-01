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
use Joomla\CMS\Table\Table;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Layout\FileLayout;

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
$fieldLayout['Editor'] = "editor";

// Load the tj-fields helper
JLoader::import('components.com_tjfields.helpers.tjfields', JPATH_SITE);
$TjfieldsHelper = new TjfieldsHelper;

// Load itemForm model
JLoader::import('components.com_tjucm.models.itemform', JPATH_SITE);
$tjucmItemFormModel = BaseDatabaseModel::getInstance('ItemForm', 'TjucmModel', array('ignore_request' => true));
$item               = $displayData['item'];
$columnsToShow      = $displayData['columnsToShow'];
$fieldsData         = $displayData['fieldsData'];
$ucmType            = $displayData['ucmType'];
$paramEditRecord    = $displayData['param'];


if ($ucmType)
{
	JLoader::import('components.com_tjucm.tables.type', JPATH_ADMINISTRATOR);
	$ucmTypeTable = Table::getInstance('type', 'TjucmTable');
	$ucmTypeTable->load($ucmType);
}


$view = explode('.', $ucmTypeTable->unique_identifier);
JLoader::import('components.com_tjucm.models.itemform', JPATH_SITE);
$itemFormModel = BaseDatabaseModel::getInstance('ItemForm', 'TjucmModel', array('ignore_request' => true));
$formObject    = $itemFormModel->getFormExtra(
	array(
		"clientComponent" => 'com_tjucm',
		"client" => $ucmTypeTable->unique_identifier,
		"view" => $view[1],
		"layout" => 'edit')
		);
?>
<tr class="row<?php echo $item->id?>">
	<?php
	if (!empty($item))
	{
		if($paramEditRecord  == 1)
			{
				if (!empty($ucmTypeTable->unique_identifier))
			{
				$appendUrl .= "&client=" . $ucmTypeTable->unique_identifier;
			}

			$link = 'index.php?option=com_tjucm&view=items' . $appendUrl;
			JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
			$tjUcmFrontendHelper = new TjucmHelpersTjucm;
			$itemId = $tjUcmFrontendHelper->getItemId($link);


			?>
			<td>
				<span class="">
			<a href="<?php  echo  Route::_('index.php?option=com_tjucm&task=itemform.edit&id=' . $item->id . $appendUrl.'&Itemid='.$itemId); ?>" type="button" title="<?php echo Text::_('EDIT');?>">

				<?php echo $item->id; ?>
			</a>
			</span>
			</td>

			<?php
			}

		foreach ($item as $key => $fieldValue)
		{
			
			if (array_key_exists($key, $columnsToShow))
			{
				$tjFieldsFieldTable = $fieldsData[$key];
				$fieldXml = $formObject->getFieldXml($tjFieldsFieldTable->name);
				?>
				<td>
					<?php
					$field = $formObject->getField($tjFieldsFieldTable->name);
					$field->setValue($fieldValue);

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
						<div class="tj-wordwrap">
							<?php  echo $output; ?>
						</div>
						<?php
					}
					else
					{
						echo $output;
					}
					?>
				</td>
				<?php
			}
		}
		if($paramEditRecord  == 1)
			{
				if (!empty($ucmTypeTable->unique_identifier))
			{
				$appendUrl .= "&client=" . $ucmTypeTable->unique_identifier;
			}

			$link = 'index.php?option=com_tjucm&view=items' . $appendUrl;
			JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
			$tjUcmFrontendHelper = new TjucmHelpersTjucm;
			$itemId = $tjUcmFrontendHelper->getItemId($link);


			?>
			<td>
				<span class="">
			<a href="<?php  echo  Route::_('index.php?option=com_tjucm&task=itemform.edit&id=' . $item->id . $appendUrl.'&Itemid='.$itemId); ?>" type="button" title="<?php echo Text::_('EDIT');?>">

				<span class="">
					<i class="fa fa-pencil-square-o" aria-hidden="true"></i>
				</span>
			</a>
			</span>
			</td>

			<?php
			}

	}
	?>
</tr>
