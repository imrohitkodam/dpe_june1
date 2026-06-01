<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;


$tmpListColumn = $this->listcolumn;
reset($tmpListColumn);
$firstListColumn = key($tmpListColumn);
$user = Factory::getUser();
$document = Factory::getDocument();
$document->addScript(Uri::root() . 'media/com_dpe/js/genericprocess.min.js');

// Get generic filter update
$input         = Factory::getApplication()->input;
$selected      = $this->state->get('filter.process', "STRING");

$check         = ($selected == 'generic') ? 'checked': '';


// Get Process Addtion form Itemid
$menu   = Factory::getApplication()->getMenu();
$itemId = $menu->getActive()->id;
$createUCMCopyLink = Route::_('index.php?option=com_dpe&view=ropform&tmpl=component&layout=coredatamultiplecopy&client=' . $this->client. '&Itemid=' . $itemId);
$document = Factory::getDocument();
 $document->addScript(Uri::root() . 'media/com_dpe/js/tjucm.js');

// Get Joomla session and input
$session = Factory::getSession();

$clusterID = $input->get('cluster') ?? ''; // Shorter ternary syntax

if($clusterID !=''){
	$session->set('selectedCluster', $clusterID);
}

?>

<script>
jQuery(document).ready(function() {
	var menuItemId = "<?php echo $itemId;?>";

	jQuery("#ucmgenericCheck").click(function() {
		var url = "<?php echo Route::_($link . '&Itemid=' . $itemId); ?>"

		if (jQuery(this).prop("checked") == true) {
			window.location = url + '?filter_process=generic&Itemid=' + menuItemId;

		} else {
			window.location = url;
		}
	});
});
</script>
<div id="filter-progress-bar" class="row">
	<div class="col-lg-8 col-12">
		<div class="btn-group pull-left mb-2 me-2">
			<div class="pull-left">
				<input type="text" name="filter_search" id="filter_search"
				title="<?php echo empty($firstListColumn) ? Text::_('JSEARCH_FILTER') :
				Text::sprintf('COM_TJUCM_ITEMS_SEARCH_TITLE', $this->listcolumn[$firstListColumn]->label); ?>"
				value="<?php echo $this->escape($this->state->get($this->client . '.filter.search')); ?>"
				placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>"/>
			</div>
			<div class="pull-left">
				<button class="btn btn-default" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><span class="icon-search"></span></button>
				<button class="btn btn-default qtc-hasTooltip" id="clear-search-button" onclick="document.getElementById('filter_search').value='';this.form.submit();" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><span class="icon-remove"></span></button>
			</div>
		</div>

		<?php 

		$db = Factory::getDbo();

		// Check if com_cluster component is installed
		if (ComponentHelper::getComponent('com_cluster', true)->enabled)
		{
			JLoader::import('components.com_tjfields.tables.field', JPATH_ADMINISTRATOR);
			$fieldTable = Table::getInstance('Field', 'TjfieldsTable', array('dbo', $db));
			$fieldTable->load(array('client' => $this->client, 'type' => 'cluster'));

			if ($fieldTable->id)
			{
				FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
				$cluster           = FormHelper::loadFieldType('cluster', false);
				$this->clusterList = $cluster->getOptionsExternally();

				?>
				<div class="btn-group mr-10 md-w-200px pull-left mb-2">
					<?php
					echo HTMLHelper::_('select.genericlist', $this->clusterList, "cluster", 'class="input-medium" size="1" onchange="this.form.submit();"', "value", "text", $this->state->get($this->client . '.filter.cluster_id', '', 'INT'));
					?>
				</div>
				<?php
			}
		}

		// Get the item category filter
		JLoader::import('components.com_tjfields.tables.field', JPATH_ADMINISTRATOR);
		$fieldTable = Table::getInstance('Field', 'TjfieldsTable', array('dbo', $db));
		$fieldTable->load(array('client' => $this->client, 'type' => 'itemcategory', 'state' => '1'));

		if ($fieldTable->id)
		{
			$selectCategory = new stdClass;
			$selectCategory->value = '';
			$selectCategory->text = Text::_("COM_TJUCM_FILTER_SELECT_CATEGORY_LABEL");

			$categoryOptions = HTMLHelper::_('category.options', $this->client, $config = array('filter.published' => array(1)));
			$categoryOptions = array_merge(array($selectCategory), $categoryOptions);
			?>
			<div class="btn-group pull-right hidden-xs">
				<?php
				echo HTMLHelper::_(
					'select.genericlist', $categoryOptions, "itemcategory", 'class="input-medium"
					size="1" onchange="this.form.submit();"', "value", "text",
					$this->state->get($this->client . '.filter.category_id', '', 'INT')
				);
				?>
			</div>
			<?php
		}?>
		<!--filter for calculations-->
		<?php 
		$numericoptions = array();
		foreach ($this->listcolumn as $colorconfigValue) 
		{
			$numericRanges = json_decode(json_decode($colorconfigValue->params)->colorcombination);
			$isFilterable  = $colorconfigValue->filterable;
			($isFilterable) ? $canFilter = 1 : '';

			if ($numericRanges && ($isFilterable == '1'))
			{
				foreach($numericRanges as $key => $numericRange)
				{	
					$numericoptions[$key] = array('value'=>$numericRange->min .'-'. $numericRange->max,'text'=>$numericRange->value);
				}
				array_unshift( $numericoptions,array('value'=>'0','text'=> Text::sprintf('COM_TJUCM_SELECT_NUMERICCALCULATIO_FILTER', $colorconfigValue->label)));
			}	
		}

		if ($canFilter)
			{  ?>
				<div class="btn-group pull-left mr-10 mb-5 md-w-200px">
					<fieldset id="filter-bar">
						<div class="filter-select">
							<?php
							echo HTMLHelper::_('select.genericlist', $numericoptions, "numeirccalculation", 'class="input-medium" size="1" onchange="this.form.submit();"', "value", "text", $this->state->get($this->client . '.filter.numeirccalculation', '', 'STRING'));
							?>
						</div>
					</fieldset>
				</div> 

				<?php
			}
			?>
			
			<!--calculation filter end-->

<?php		// Load filter fields
JLoader::import('components.com_tjfields.models.options', JPATH_ADMINISTRATOR);
JLoader::import('components.com_tjfields.models.fields', JPATH_ADMINISTRATOR);
$fieldsModel = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
$fieldsModel->setState('filter.client', $this->client);
$fieldsModel->setState('filter.filterable', 1);
$fields = $fieldsModel->getItems();

foreach ($fields as $field)
{


	$options = array();

	if ($field->type != "assignee")
	{
		$tjFieldsOptionsModel = BaseDatabaseModel::getInstance('Options', 'TjfieldsModel', array('ignore_request' => true));
		$tjFieldsOptionsModel->setState('filter.field_id', $field->id);
		$options = $tjFieldsOptionsModel->getItems();
	}
	else
	{
				// Load options dynamically for assignee type field filter

		FormHelper::addFieldPath(JPATH_SITE . '/components/com_dpe/models/fields/');
		$assignee = FormHelper::loadFieldType('assigneeFilter', false);
		$options  = $assignee->getOptionsExternally();
	}

	if (!empty($options))
	{
		$defaultOption = new stdclass;
		$defaultOption->value = "";
		$defaultOption->options = Text::_("JSELECT") . ' ' . ucfirst($field->label);

		$options = array_merge(array($defaultOption), $options);
		?>
		<div class="btn-group pull-left pe-2 mb-2 md-w-200px">
			<?php
			echo HTMLHelper::_(
				'select.genericlist', $options, $field->name, 'class="input-medium"
				size="1" onchange="this.form.submit();"', "value", "options",
				$this->state->get('filter.field.' . $field->name)
			);
			?>
		</div>
		<?php
	}
}

				$params     			   = ComponentHelper::getParams('com_multiagency');
				$orgAdminRoleId            = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
			    $orgAdminRoleId 		   = in_array($orgAdminRoleId, $user->groups);

if ($user->authorise('core.manageall', 'com_cluster') || $orgAdminRoleId)
{ 
	FormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');
	$dpeTags = FormHelper::loadFieldType('Dpetags', false);
	$dpeTag  = $dpeTags->getOptions();

	if($orgAdminRoleId)
				{
					JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
				$dpeModel = DPE::model('school', array('ignore_request' => true));
				$dpeTag = $dpeModel->getAgencyTags($orgAdminRoleId);
				}
	?>
	<div class="btn-group pull-left pr-10 mb-5 md-w-200px">
		<fieldset id="filter-bar">
			<div class="filter-select fltrt">
				<select name="filter_tags[]" id = "tags" data-placeholder="<?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?>" class="inputbox" multiple="multiple" onchange="this.form.submit()">
					<option value=""><?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?> </option>
					<?php echo HTMLHelper::_('select.options', $dpeTag, 'value', 'text', $this->state->get('filter.tags'));?>
				</select>
			</div>
		</fieldset>
		</div><?php } ?>
		

	</div>
	<div class="col-lg-4 col-12">
		<div class="btn-group pull-right pl-10 hidden-xs">
			<?php echo $this->pagination->getLimitBox(); ?>
		</div>
		<div class="pull-right pl-10 mb-10">
					<a class="btn btn-primary btn-small"
						href="javascript:void(0);"
						onclick="tjucm.itmes.deleteMultipleItems(this);"
						id="ucm-delete"
						data-target-form="adminForm"
						title="<?php echo TEXT::_('COM_TJUCM_RECORD_DELETE_BTN_TEXT');?>" >
						<i class="fa fa-trash"></i><?php echo ' ' . TEXT::_('COM_TJUCM_RECORD_DELETE_BTN_TEXT')?>
					</a>
				</div>

		<div class="pull-right pl-10 mb-10">
			<?php

			$tjUcmFrontendHelper = new TjucmHelpersTjucm;
			$itemId = $tjUcmFrontendHelper->getItemId('index.php?option=com_tjucm&view=itemform&client=' . $this->client);
			$masterUcmLink = Route::_('index.php?option=com_tjucm&view=itemform&Itemid=' . $itemId, false);
			
			if ($this->allowedToAdd)
				{?>
					<a href="<?php echo $masterUcmLink; ?>" class="btn btn-primary btn-small">
						<i class="icon-plus"></i><?php echo Text::_('COM_TJUCM_ADD_ITEM'); ?>
					</a>
					<?php
				}

				if ($user->authorise('core.manageall', 'com_cluster') || $orgAdminRoleId)
					{   $dpeAdmin = $user->authorise('core.manageall', 'com_cluster');
					?>
					<a href="#" id="export-items" onclick="exportItems('<?php echo $dpeAdmin;?>');"class="btn btn-primary btn-small">
							<i class="fa fa-download"></i> <?php echo Text::_('COM_TJUCM_EXPORT_ITEM_PDF'); ?>
						</a>
					<?php }
				?>
			</div>

			<div class="pull-right pl-10 mb-10">
				<?php
				if ($this->canImport)
					{?>
						<a href="#" id="import-items" class="btn btn-primary btn-small ">
							<i class="fa fa-upload"></i> <?php echo Text::_('COM_TJUCM_IMPORT_ITEM'); ?>
						</a>
						<?php
					}
					?>
				</div>
			</div>
		</div>
		<!--Generic Checkbox-->
				<div class="d-inline-block mr-20">
					<input class="" type="checkbox" name="recordProcessCheck"  id="recordProcessCheck" onchange="addGenericValue(); this.form.submit();" <?php echo $check;?> >
					<label for="ropProcessCheck" class="">
					<?php echo Text::sprintf('COM_TJUCM_ROP_GENERIC_TYPE_CHECKBOX_TITLE', $ucmTypeDetails->type_description);?>
					</label>
				</div>

				<div class="d-inline-block mr-20">
					<?php $alert = TEXT::_('COM_TJUCM_GENERIC_NOT_CHECKEDLIST_MSG')?>
					<a class=""href="javascript:void(0);"onclick="if(ValidateRecordList()){openUcmPopups('<?php echo addslashes(Route::_($createUCMCopyLink . '&iscopy=1'));?>', this)}else{alert('<?php echo $alert;?>');}; "
						id="ucm-make-copy"
						data-target-form="adminForm"
						title="<?php echo TEXT::_('COM_TJUCM_GENERIC_PROCESS_MAKE_COPY');?>" >
						<i class="fa fa-copy"></i><?php echo ' ' . TEXT::_('COM_TJUCM_GENERIC_PROCESS_MAKE_COPY')?>
					</a>
				</div>

				<div class="d-inline-block mr-20">
					<a class=""
						href="javascript:void(0);"
						onclick="tjucm.itmes.deleteMultipleItems(this);"
						id="ucm-delete"
						data-target-form="adminForm"
						title="<?php echo TEXT::_('COM_TJUCM_RECORD_DELETE_BTN_TEXT');?>" >
						<i class="fa fa-trash"></i><?php echo ' ' . TEXT::_('COM_TJUCM_RECORD_DELETE_BTN_TEXT')?>
					</a>
				</div>
		<?php  
				//echo JHtml::_('content.prepare', '{loadposition testposition}');

		$multiagencyParams = ComponentHelper::getParams('com_multiagency');
			$orgAdminRoleId    = (int) $multiagencyParams->get('multiagency_school_admin_group', '0', 'INT');
		   $orgAdminRoleId 	 = in_array($orgAdminRoleId, $user->groups);
		   ?>
		<script>
			jQuery(document).ready(function() {
				jQuery('#tags').change(function(e) {

			// check tag list is empty or not and set tag filter 
			var selectData = jQuery("#tags").chosen().val();       
			if ((selectData == null) || (selectData == ""))
			{	
				jQuery("#tags").val(jQuery("#tags option:first").val());
			}
		}); 

			// check dpeadmin or not 
			var isDpeAdmin = "<?php echo $user->authorise('core.manageall', 'com_cluster'); ?>";
			var isorgAdmin = "<?php echo $orgAdminRoleId; ?>";


		if (!isDpeAdmin && !isorgAdmin)
		{
				jQuery('#filter_tags_chosen').hide();
			}

		// set filters according to the custer and tags 
		jQuery('#tags').on('change', function() {
			jQuery("#cluster").val(jQuery("#cluster option:first").val());
			if (jQuery('#recordProcessCheck').is(':checked'))
        	{
        		jQuery('#recordProcessCheck').prop('checked', false);
        		addGenericValue();
       		}
		});
		jQuery('#cluster').on('change', function() {	
			jQuery("#tags").val(jQuery("#tags option:first").val());
			if (jQuery('#recordProcessCheck').is(':checked'))
        	{
        		jQuery('#recordProcessCheck').prop('checked', false);
        		addGenericValue();
       		}
		});
	})
      
       
</script>
  <script>
      jQuery(document).ready(function () {
    var isChecked = jQuery('#recordProcessCheck').is(':checked');

    if (isChecked) {
        jQuery('.page-link').each(function () {
            var originalHref = jQuery(this).attr('href');

            // Skip if href is not defined
            if (!originalHref) return;

            // Use correct separator depending on existing query parameters
            var separator = originalHref.includes('?') ? '&' : '?';

            // Avoid duplicate parameters if already appended
            if (!originalHref.includes('filter_process')) {
                var updatedHref = originalHref + separator + 'filter_process=generic&filter_genericlist=1';
                jQuery(this).attr('href', updatedHref);
            }
        });
    }
});

    </script>

<script type="text/javascript">
	jQuery(document).ready(function(){

		if (!jQuery('#import-items').length )
		{
			jQuery('.dpecustomdelete').css('margin-left', '151px');
		}
	})

	function openUcmPopups(url, element = '') {
		var isCopy = url.indexOf("iscopy=1");

		if (isCopy !== -1) {
			if (element == '') {
				return;
			}
			var elementForm = jQuery(element).data('target-form');
			var recordIds = [];

			jQuery("#" + elementForm + " input[name='cid[]']").each(function() {

				if (jQuery(this).prop("checked") == true) {
					recordIds.push(jQuery(this).val());
				}
			});

			if (recordIds.length >= 1)
			{
				url += '&recordIds=' + JSON.stringify(recordIds);
			}
		}

		SqueezeBox.open(url, {
			handler: 'iframe',
			size: {
				x: window.innerWidth - 200,
				y: window.innerHeight - 200
			},
			classWindow: 'tjucm-addprocess-doc',
		});
	}
</script>