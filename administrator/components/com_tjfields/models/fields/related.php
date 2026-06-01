<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');

use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Form Field class for the Joomla Platform.
 * Supports a generic list of options.
 *
 * @since  1.7.0
 */
class JFormFieldRelated extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.7.0
	 */
	protected $type = 'Related';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   3.7.0
	 */
	public function getOptions()
	{
		// Load TJ-Fields language file
		$lang = Factory::getLanguage()->load('com_tjfields', JPATH_ADMINISTRATOR);

		$fieldname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);

		$db = Factory::getDbo();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$fieldTable = Table::getInstance('field', 'TjfieldsTable', array('dbo', $db));
		$fieldTable->load(array('name' => $fieldname));


		// Get object of TJ-Fields field model
		JLoader::import('components.com_tjfields.models.field', JPATH_ADMINISTRATOR);
		$tjFieldsModelField = BaseDatabaseModel::getInstance('Field', 'TjfieldsModel');
		$options = $tjFieldsModelField->getRelatedFieldOptions($fieldTable->id);


		// DPE HACK TO SHOW ALSOKNOWN AS FIELD
		
		$params              = ComponentHelper::getParams('com_dpe');// Also known as for vendor
 		$alsoKnownAsFieldnames  = json_decode($params->get('alsoknownas'));



		$fieldTable->load(array('id' => $fieldTable->id));
		// Get decoded data object
		$fieldParams = new Registry($fieldTable->params);
	
		// UCM fields and fields from which options are to be generated
		$realtedFieldsName  = $fieldParams->get('fieldName');
		$fieldTable->load(array('id' => $realtedFieldsName->fieldName0->fieldIds[0]));
		$realtedFieldsName = $fieldTable->name;
		$alsoKnownAsFieldnames = is_object($alsoKnownAsFieldnames) ? get_object_vars($alsoKnownAsFieldnames) : [];

		if (array_key_exists($realtedFieldsName, $alsoKnownAsFieldnames))
		{

					$fieldValueTable = Table::getInstance('fieldsvalue', 'TjfieldsTable', array('dbo', $db));
			 		$fieldTable->load(array('name' => $alsoKnownAsFieldnames->$realtedFieldsName));
			 		$alsoKnownAsFieldId = $fieldTable->id;

					foreach($options as $key => $option)
					{
						BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models');
						$tjfieldsModelFields  = BaseDatabaseModel::getInstance('Users', 'DpeModel', array('ignore_request' => true));
						$fieldValuesFieldId = $tjfieldsModelFields->getFieldsValueBycontentId((int) $option['value']);

						 foreach($fieldValuesFieldId as $fieldAvail )
						 {
						 	if ($alsoKnownAsFieldId == $fieldAvail)
						 	{
						 			$fieldValueTable->load(array('content_id' => $option['value'],'field_id'=>$alsoKnownAsFieldId));

									if ($fieldValueTable->value)
									{
										 $options[$key]['text'] = $options[$key]['text'].' ('. $fieldValueTable->value.')';
									}
						 	}
						 }
						
					}			
		}


		return $options;
	}

	/**
	 * Method to get the field input markup for a generic list.
	 * Use the multiple attribute to enable multiselect.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   3.7.0
	 */
	protected function getInput()
	{
		$html      = parent::getInput();

		$fieldname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);
		$user      = Factory::getUser();
		$input     = Factory::getApplication()->input;

		
		$db        = Factory::getDbo();

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$fieldTable = Table::getInstance('field', 'TjfieldsTable', array('dbo', $db));
		$fieldTable->load(array('name' => $fieldname));
		// Get decoded data object
		$fieldParams = new Registry($fieldTable->params);

		// UCM fields and fields from which options are to be generated
		$realtedFields = (array) $fieldParams->get('fieldName');

		// DPE Hack
		$params              = ComponentHelper::getParams('com_dpe');
		$codeDataFieldConfig = json_decode($params->get('coredatatitlefields'), true);
		$masterListClients   = array_keys($codeDataFieldConfig);

		// Get current menu details
		$app = Factory::getApplication();
		$menuitem   = $app->getMenu()->getActive(); // get the active item

		if (count($realtedFields) == 1)
		{
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjucm/tables');
			$ucmTypeTable = Table::getInstance('Type', 'TjucmTable', array('dbo', $db));
			$ucmTypeTable->load(array('unique_identifier' => $realtedFields['fieldName0']->client));

			// Check if user is authorised to add the record in given UCM Type
			$canCreate = $user->authorise('core.type.createitem', 'com_tjucm.type.' . $ucmTypeTable->id);

			if ($canCreate)
			{
				foreach ($realtedFields['fieldName0']->fieldIds as $fieldId)
				{
					$canCreate = ($user->authorise('core.field.addfieldvalue', 'com_tjfields.field.' . $fieldId)) ? true : false;

					if (empty($canCreate))
					{
						break;
					}
				}
			}

			// UCM fields and field value to get
			$showAddNewRecordLink = $fieldParams->get('showAddNewRecordLink');
			$clusterAware = $fieldParams->get('clusterAware');

			// Hide add new in POP up
			$hideAddNew = $input->get("hideaddnew", 0, "INT");

			if ($canCreate && !empty($showAddNewRecordLink) && !$hideAddNew)
			{
				$tjUcmFrontendHelper = new TjucmHelpersTjucm;
				$itemId = $tjUcmFrontendHelper->getItemId('index.php?option=com_tjucm&view=itemform&client=' . $ucmTypeTable->unique_identifier);
				$masterUcmLink = Route::_('index.php?option=com_tjucm&view=itemform&Itemid=' . $itemId, false);

				if ($clusterAware)
				{
					$clusterId = $input->get("cluster_id", 0, "INT");

					if ($clusterId)
					{
						$masterUcmLink .= (strpos($masterUcmLink, '?')) ? '&cluster_id=' . $clusterId : '?cluster_id=' . $clusterId;
					}
				}

				if (in_array($ucmTypeTable->unique_identifier, $masterListClients))
				{
					// Get Process Addtion form Itemid
					$itemId = $tjUcmFrontendHelper->getItemId('index.php?option=com_tjucm&view=itemform&client=' . $ucmTypeTable->unique_identifier);
					$recordId = $input->getInt('id', 0);

					$createRopLink1 = Route::_('index.php?option=com_tjucm&view=items&tmpl=component&showall=1&Itemid=' . $itemId);
					$masterListUrl = addslashes(Route::_($createRopLink1 . '&isMasterlist=1&filter_coredata=1&ucmdataid=' . $recordId . '&filter_process=generic'));		// DPE HACK TO SHOW generic field data


					$html .= '<div>
					<a class="" href="javascript:void(0);" onclick="tjucm.itmes.openMasterlistPopups(' . "'" . $masterListUrl . "'" . ', this)"
					id="rop-make-copy"><i class="fa fa-copy"></i> '.Text::_('ROP_POPUP_MASTERLIST_COPY_BTN_TEXT').'</a></div>';
				}
				else
				{
					$html .= "<div><a target='_blank' href='" . $masterUcmLink . "'>" . Text::_("COM_TJFIELDS_FORM_DESC_FIELD_RELATED_ADD_RECORD") . "</a></div>";
				}
			}
		}

		if ($fieldParams['showAddNewRecordLink'] && $this->id && $fieldTable->id)
		{
			$clusterId = $input->get("cluster_id", 0, "INT");
			$document  = Factory::getDocument();
			if(!$clusterId)
			{
				$clusterId = $input->get("clusterId", 0, "INT");

				$html .= '
				<script>
				jQuery(function($) {
					$("#' . $this->id . '_chosen")
						.off("click.relatedField") // remove previous handler with same namespace
						.on("click.relatedField", function() {
							tjUcmItemForm.getRelatedFieldOptions(
								"' . $this->id . '",
								"' . $fieldTable->id . '",
								"' . $clusterId . '"
							);
						});
				});
				</script>
				';

			}
			else{



				$document->addScript(Uri::root() . 'media/com_tjucm/js/ui/itemform.min.js');

				// !in_array($ucmTypeTable->unique_identifier, $masterListClients))
				if ($clusterId && $menuitem->getParams('ucm_type')->get('ucm_type') == 'rop')
				{
					$document->addScriptDeclaration('jQuery(document).ready(function() {
				

						jQuery("#' . $this->id . '_chosen").click(function(){

								tjUcmItemForm.getRelatedFieldOptions("' . $this->id . '", "' . $fieldTable->id . '", "' . $clusterId . '");
						});
					});');
				}
				else
				{
					$document->addScriptDeclaration('jQuery(document).ready(function() {
						jQuery("#' . $this->id . '_chosen").click(function(){
							var cluster_id = jQuery("#cluster_id").val();
							tjUcmItemForm.getRelatedFieldOptions("' . $this->id . '", "' . $fieldTable->id . '", cluster_id);
						});
					});');
				}
			}
		}
		return $html;
	}
}
