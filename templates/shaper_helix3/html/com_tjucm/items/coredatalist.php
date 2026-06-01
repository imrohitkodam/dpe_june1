<?php
/**
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;


HTMLHelper::_('jquery.token');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('bootstrap.renderModal');


// While creating a business field need to add same class name
Text::script('COM_TJUCM_DELETE_MESSAGE');

$document = Factory::getDocument();
$document->addStyleSheet(Uri::root() . 'media/system/css/modal.css');
// $document->addScript(Uri::root() . 'media/system/js/mootools-core.js');
// $document->addScript(Uri::root() . 'media/system/js/mootools-more.js');
$document->addScript(Uri::root() . 'media/system/js/modal.js');
$document->addScript(Uri::root() . 'media/com_dpe/js/tjucmcoredatalist.js');
$document->addStyleSheet(Uri::root() . 'media/com_dpe/css/rop.css');

$input    = Factory::getApplication()->input;
$selected = $input->get('filter_process', 'myprocess', 'STRING');
$user     = Factory::getUser();


$tmpl       = $input->getString('tmpl', '');
$popupClass = '';

// Check template component set or not.
if (!empty($tmpl))
{
	$doc = Factory::getDocument();
	$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
	$doc->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
	$doc->addStyleSheet('templates/shaper_helix3/js/bootstrap.min.js');
	$doc->addStyleSheet('templates/shaper_helix3/js/jquery.sticky.js');
	$doc->addStyleSheet('templates/shaper_helix3/js/main.js');
	$doc->addStyleSheet('templates/shaper_helix3/js/frontend-edit.js');
	$doc->addStyleSheet('media/system/js/frontediting.js');

	$popupClass = 'notification-add-form rop-popup';
	$cancelButton = "";
}
else
{
	$cancelButton = "Joomla.submitbutton('recommendation.cancel')";
}

$tjUcmFrontendHelper = new TjucmHelpersTjucm;
$itemId = $tjUcmFrontendHelper->getItemId('index.php?option=com_tjucm&view=itemform&client=' . $this->client);
$masterUcmLink = Route::_('index.php?option=com_tjucm&view=itemform&Itemid=' . $itemId.'&tmpl=component&hideaddnew=1', false);

// Construct URL and pass data
$menu     = Factory::getApplication()->getMenu();
$itemId   = $menu->getActive()->id;
$app      = Factory::getApplication();
$selected = $input->get('filter_process', 'myprocess', 'STRING');
$check    = ($selected == 'generic') ? 'checked': '';

$createRopLink = Route::_('index.php?option=com_dpe&view=ropform&tmpl=component&layout=coredatacopy&client=' . $this->client. '&Itemid=' . $itemId);


// Get UCM type Name
Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
$ucmTypeDetails = Table::getInstance('type', 'TjucmTable');
$ucmTypeDetails->load(array('unique_identifier' => $this->client));


// Get field groups
JLoader::import('components.com_tjfields.models.groups', JPATH_ADMINISTRATOR);
$fieldGroupsModel = BaseDatabaseModel::getInstance('groups', 'TjfieldsModel', array('ignore_request' => true));
$fieldGroupsModel->setState('filter.state', 1);
$fieldGroupsModel->setState('filter.client', $this->client);
$fieldGroups = $fieldGroupsModel->getItems();
JText::script('COM_TJUCM_ROP_COPY_SELECT_RECORD_VALIDATION_MSG');
JText::script('COM_TJUCM_ROP_COPY_SELECT_RECORD_CLUSTER_VALIDATION_MSG');

// Get Request status field ID
$softwareManagedby = $input->get('softwareManagedby', 0, 'INT');

JLoader::import('field', JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
$softwareManagedbyField = Table::getInstance('field', 'TjfieldsTable', array());
$softwareManagedbyField->load(array('name' => 'com_tjucm_software_Managedby'));

?>
<div id="system-message-container"></div>
<div id="ropCopyLoader" class="centerloader hide"></div>
<div id="ropCopypopCover">
<script>
jQuery(document).ready(function()
{
	jQuery(".copy-buttons-cordata-list").prop("disabled", false);

	jQuery("input:checkbox").change(function()
	{
		var ischecked= jQuery(this).is(':checked');
		if(!ischecked)
		{
			jQuery('#coreDataProcess').val('myprocess');
			var el = jQuery("#ropBusinessFunctionAccordian");
			var form = jQuery('#' + el.data('target-form'));
			ucmRopLoadData(el);
		}
		else
		{
			jQuery('#coreDataProcess').val('generic');
			var el = jQuery("#ropBusinessFunctionAccordian");
			var form = jQuery('#' + el.data('target-form'));
			ucmRopLoadData(el);
		}
	});
	jQuery("#resetCoreData").click(function() {
			jQuery('#filterSearch').val('');

			var ischecked= jQuery('#ropProcessCheck').is(':checked');
			if(!ischecked)
			{
				jQuery('#coreDataProcess').val('myprocess');
			}
			else
			{
				jQuery('#coreDataProcess').val('generic');
			}

			var el = jQuery("#ropBusinessFunctionAccordian");
			var form = jQuery('#' + el.data('target-form'));
			ucmRopLoadData(el);
    });
	jQuery("#resetCoreDataSearch").click(function() {
			var el = jQuery("#ropBusinessFunctionAccordian");
			var form = jQuery('#' + el.data('target-form'));
			ucmRopLoadData(el);
    });
});

</script>

<div class="container-fluid px-0">
	<div class="timelog-add-form activity-edit front-end-edit jlike-timelog px-3 pt-3">
		<h2 class="activity-header fs-20 mb-20 pb-2"><?php echo JTEXT::_('COM_TJUCM_CORE_DATA_ADD_NEW_RECORD_TITLE'); ?></h2>
	</div>
	<div id="panelBody" >
		<form action="" method="post" name="ropBusinessFunctionForm" id="ropBusinessFunctionForm" class="ucm-form-styling ropBusinessFunctionForm <?php echo $popupClass;?>" onsubmit="return false;">
				<!--Generic Checkbox-->
			<div class="ml-20">
			<input class="ml-10" type="checkbox" name="ropProcessCheck" id="ropProcessCheck" checked>
			<label for="ropProcessCheck" class="ropProcessCheck-lable mr-25 mb-15">
				<?php echo Text::sprintf('COM_TJUCM_ROP_GENERIC_TYPE_CHECKBOX_TITLE', $ucmTypeDetails->title);?>
			</label>
			</div>

			<?php echo HTMLHelper::_('form.token'); ?>
			<!--Filter Div-->
			<div class="row popup-search">
				<div class="col-8">
				<input
							type="text"
							name="filter[search]"
							id="filterSearch"
							value=""
							class="ucm-rop-search"
							placeholder="<?php echo JTEXT::_('COM_TJUCM_ROP_PROCESS_SEARCH');?>"
							title="<?php echo JTEXT::_('COM_TJUCM_ROP_PROCESS_SEARCH');?>"
							data-target-form="ropBusinessFunctionForm"
							size="30">
				</div>
				<div class="col-4 my-10 search-clear-btn">
					<button class="btn"><i class="fa fa-search fa-2" id="resetCoreDataSearch" aria-hidden="true"></i>
					</button>
					<button class="btn"><i class="fa fa-times fa-2" id="resetCoreData"  aria-hidden="true"></i></button>
				</div>
			</div>
			<div class="col-12 mt-2 px-0 ">
				<div class="mb-15"><h5><?php echo JTEXT::_('COM_TJUCM_CORE_DATA_SEARCH'); ?></h5></div>
				<div class="no-more-tables overflow-x ucm-loadmore-tab-content mb-20" id="ropProcessList">
				</div>
			</div>

			<!-- Note display code -->
			<?php
				if (isset($this->ucmTypeParams->note_enable) && $this->ucmTypeParams->note_enable == 1 && !empty($this->ucmTypeParams->note_editor))
					{
						$noteContent = str_replace(array('<p>', '</p>'), array('', ''), $this->ucmTypeParams->note_editor);
						?>
						<div class="alert alert-warning alert-dismissible fade show mt-2 mb-3" role="alert">
							<strong>Note:</strong> <?php echo $noteContent; ?>
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>
				<?php }
			?>
			<input type="hidden"id="option" name="option" value="com_tjucm" />
			<input type="hidden" name="view" value="items" />
			<input type="hidden" id="controller" name="controller" value="items" />
			<input type="hidden" id="task" name="task" value="items.displayCoreData" />
			<input type="hidden" name="total" value="" />
			<input type="hidden" name="client" id="client" value="<?php echo $this->client;?>"/>
			<input type="hidden" name="typeId" value="<?php echo $this->ucmTypeId;?>"/>
			<input type="hidden" name="limit" value="<?php echo $listLimit; ?>" />
			<input type="hidden" name="limitstart" value="0" />
			<input type="hidden" name="loaded" value="false" />
			<input type="hidden" name="format" value="json" />
			<input type="hidden" name="filter_order" value="<?php echo $this->escape($this->state->get('list.ordering')); ?>" />
			<input type="hidden" name="filter_order_Dir" value="<?php echo $this->escape($this->state->get('list.direction')); ?>" />
			<input type="hidden" name="filter[cluster_id]" value="<?php echo $this->escape($this->state->get($this->client . '.filter.cluster_id', '', 'INT'));?>" />
			<input type="hidden" id="coreDataProcess" name="filter[process]" value="generic" />
			<input type="hidden" id="ucmdataid" name="ucmdataid" value="" />
			<input type="hidden" id="clutername" name="clutername" value="" />
			<input type="hidden" id="recordId" name="recordId" value="" />
			<input type="hidden" id="clusterId" name="clusterId" value="<?php echo $clusterId; ?>" />
			<input name="recordIds" id="recordIds" type="hidden" value="" />
			<input type="hidden" id="filter_coredata" name="filter_coredata" value="1" />

			<?php if ($this->client == 'com_tjucm.software') : ?>
			<input type="hidden" name="customeFieldValue" value="<?php echo $softwareManagedby; ?>"/>
			<input type="hidden" name="customeFieldId" value="<?php echo $softwareManagedbyField->id; ?>"/>
			<?php endif; ?>


			<div class="text-center">
				<span class="font-600 text-center" id="recordcounter">
				</span>
			</div>
			<div class="text-center hide" id="rop-loadmore<?php echo $j;?>">
			<button data-accordian-id="<?php echo $j;?>" class="btn btn-primary rop-loadmore mb-20"><?php echo Text::_('COM_TJUCM_ROP_LOAD_MORE');?></button>
			</div>
		</form>
		<div class="popup-btns">
			<?php if (count($fieldGroups) > 1): ?>
				<button class="btn rop-copy  mb-1 mr-20 pull-right copy-buttons-cordata-list" disabled="disabled">
					<a class=""
						href="javascript:void(0);"
						onclick="OpenMasterlistLayout();"
						id="rop-make-copy"
						data-target-form="ropBusinessFunctionForm"
						title="<?php echo TEXT::_('COM_TJUCM_ROP_PROCESS_DUPLICATE_AND_EDIT_TITLE');?>" >
						<i class="fa fa-copy mr-5"></i><?php echo ' ' . TEXT::_('COM_TJUCM_ROP_PROCESS_DUPLICATE_AND_EDIT_TITLE')?>
					</a>
				</button>
			<?php else: ?>
				<button class="btn rop-copy  mb-2  mr-15 pull-right copy-buttons-cordata-list" disabled="disabled" onclick="jQuery('#item-form #tjucm_loader').show(); tjucm.itmes.copyItemMasterList();">
					<i class="fa fa-copy mr-5"></i><?php echo Text::_('COM_TJUCM_ROP_PROCESS_DUPLICATE_TITLE'); ?>
				</button>
			<?php endif; ?>
			<button class="btn rop-create-new mb-2 mr-15 pull-right copy-buttons-cordata-list" disabled="disabled">
				<a href="javascript:void(0);" onclick="tjucm.itmes.openVendorPopups('<?php echo addslashes(Route::_($masterUcmLink));?>', this)">
					<?php echo Text::_('COM_TJUCM_CORE_DATA_ADD_NEW_RECORD_POP_UP_TITLE');?>
				</a>
			</button>
			<div class="clearfix"></div>

		</div>
	</div>
    <!-- Panel body cover end-->
</div>
</div>
<script>
var menuItemId = "<?php echo $itemId;?>";
tjucm.itmes.init();
	var el = jQuery("#ropBusinessFunctionAccordian");
	var form = jQuery('#' + el.data('target-form'));
	ucmRopLoadData(el);
</script>
<script>
function OpenMasterlistLayout()
{
	ucmdataid = document.getElementById('ucmdataid').value;
	var recordId = window.parent.document.getElementById('recordId').value;
	jQuery("#recordId").val(recordId);

	var validationMessage = "<?php echo Text::_('COM_TJUCM_ROP_COPY_SELECT_RECORD_VALIDATION_MSG');?>";

	if (!ucmdataid)
	{
		alert(validationMessage);
		return false;
	}
	tjucm.itmes.openMasterlistPopups('<?php echo addslashes(Route::_($createRopLink . '&iscopy=1'));?>', this);
}
</script>
<style>
	iframe{
		max-width: 100%;
	}
</style>