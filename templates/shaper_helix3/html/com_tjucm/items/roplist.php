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
use Joomla\CMS\Form\FormHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;



HTMLHelper::_('jquery.token');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('bootstrap.renderModal');
HTMLHelper::_('formbehavior.chosen', 'select');

// While creating a business field need to add same class name
Text::script('COM_TJUCM_DELETE_MESSAGE');
Text::script('COM_TJUCM_NO_ITEM_SELECTED');


$document = Factory::getDocument();
$document->addScript(Uri::root() . 'media/system/js/messages.min.js');
$document->addScript(Uri::root() . 'media/com_dpe/js/tjucmroplist.js');
$document->addScript(Uri::root() . 'media/com_dpe/vendors/jquery-ui.min.js');
$document->addStyleSheet(Uri::root() . 'media/com_dpe/css/rop.css');
$document->addScript(Uri::root() . 'media/com_dpe/js/dpepreloader.js');

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models');
$tjfieldsModelFields  = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
$tjfieldsModelFields->setState('filter.client', 'com_tjucm.rop');
$tjfieldsModelFields->setState('filter.validation_class', 'business-function');
$ropbusinessFieldData = $tjfieldsModelFields->getItems();

$input        = Factory::getApplication()->input;
$selected     = $input->get('filter_process', 'myprocess', 'STRING');
$user         = Factory::getUser();

if (!empty($ropbusinessFieldData))
{
	$businessFunctionFieldId = $this->businessFunctionFieldId = $ropbusinessFieldData[0]->id;

	BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models');
	$tjfieldsModelOptions = BaseDatabaseModel::getInstance('Options', 'TjfieldsModel', array('ignore_request' => true));

	$tjfieldsModelOptions->setState('filter.field_id', $businessFunctionFieldId);
	$ropbusinessFieldOptions = $tjfieldsModelOptions->getItems();
}


// Get Request status field ID
JLoader::import('field', JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
$requestStatusField = Table::getInstance('field', 'TjfieldsTable', array());
$requestStatusField->load(array('name' => 'com_tjucm_rop_status'));

$existingProcssField = Table::getInstance('field', 'TjfieldsTable', array());
$existingProcssField->load(array('name' => 'com_tjucm_rop_neworexisting'));

// Get Date of Review Field ID
JLoader::import('field', JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
$nextReviewDateField = Table::getInstance('field', 'TjfieldsTable', array());
$nextReviewDateField->load(array('name' => 'com_tjucm_rop_dateofnextreview'));

// Get Process Addtion form Itemid
$menu   = Factory::getApplication()->getMenu();
$itemId = $menu->getActive()->id;

$createRopLink = Route::_('index.php?option=com_dpe&view=ropform&tmpl=component&client=' . $this->client. '&Itemid=' . $itemId);
$createRopLink1 = Route::_('index.php?option=com_dpe&view=ropform&tmpl=component&layout=ropcopy&client=' . $this->client. '&Itemid=' . $itemId);

$ropbusinessFieldValue = $input->get('business_function', 'Commercial and Community', 'STRING');

// Hide as wanted to show all the process
// if (empty($ropbusinessFieldValue))
// {
// 	$ropbusinessFieldValue = 'Commercial and Community';
// }


$accordion = 0;
// Hide as wanted to show all the process

// if ($ropbusinessFieldValue)
// {
// 	$key = array_search($ropbusinessFieldValue, array_column(json_decode(json_encode($ropbusinessFieldOptions),TRUE), 'options'));
// 	$accordion = $key + 1;
// }

// Get Joomla session and input
$session = Factory::getSession();
$clusterID = $input->get('cluster') ?? ''; // Shorter ternary syntax

if($clusterID !=''){
	$session->set('selectedCluster', $clusterID);
}

?>
<script>

const storedCheck     = sessionStorage.getItem("ropProcessCheck");
const storedTabId     = sessionStorage.getItem("ropBusinessFunctionId");
const storedHighLevel = sessionStorage.getItem("ropProcessHighLevelCheck");

document.addEventListener("DOMContentLoaded", function () {
	const processCheckbox = document.getElementById("ropProcessCheck");
	const highLevelCheckbox = document.getElementById("ropProcessHighLevelCheck");

	// Set checkbox UI
	if (storedCheck === "1" && processCheckbox) {
		processCheckbox.checked = true;
		handleRopProcessCheckChange(); // move outside tab block
	}

	if (storedHighLevel === "1" && highLevelCheckbox) {
		highLevelCheckbox.checked = true;

		// Only run high-level logic if processCheck wasn't already triggered
		if (storedCheck !== "1") {
			handleRopHighLevelCheckChange(); // move outside tab block
		}
	}

	// Restore tab and load content if set
	if (storedTabId !== null) {
		const tabEl = jQuery("#ropBusinessFunctionAccordian" + storedTabId);

		if (tabEl.length) {
			handleUcmLoadMoreTabClick(tabEl);
		}
	}
});

jQuery(document).ready(function()
{
	// Text search
	jQuery(".resetSearchText").click(function()
	{
		var form = jQuery('#ropBusinessFunctionForm' + jQuery(this).data('accordian-id'));
		jQuery('#filterSearch_' + jQuery(this).data('accordian-id')).val('');
		form.find('[name=field_data]').val(form.find('[name=field_data]').data('field-data'));

		var ischecked= jQuery('#ropProcessCheck').is(':checked');
		if(!ischecked)
		{
			form.find('input[name="filter[process]"]').val('myprocess');
		}
		else
		{
			form.find('input[name="filter[process]"]').val('generic');
		}
		var el = jQuery('#ropBusinessFunctionAccordian' + jQuery(this).data('accordian-id'));
		ucmRopLoadData(el);
    });


	// Free text search
	jQuery(".ropListSearch").click(function()
	{
		var form = jQuery('#ropBusinessFunctionForm' + jQuery(this).data('accordian-id'));

		var ischecked= jQuery('#ropProcessCheck').is(':checked');
		if(!ischecked)
		{
			form.find('input[name="filter[process]"]').val('myprocess');
		}
		else
		{
			form.find('input[name="filter[process]"]').val('generic');
		}
		var iscategorychecked= jQuery('#ropProcessHighLevelCheck').is(':checked');
		
		if (iscategorychecked)
		{
			form.find('input[name="process_category"]').val('High Level');
		}
		else
		{
			form.find('input[name="process_category"]').val('');
		}

		var el = jQuery('#ropBusinessFunctionAccordian'+jQuery(this).data('accordian-id'));
		ucmRopLoadData(el);
    });
    jQuery(".ucm-loadmore-tab").click(function()
	{
		genericProcessChange();
	})

	jQuery('#ropProcessCheck').click(function()
	{
		genericProcessChange();

	})
	 function genericProcessChange()
		{
			if(jQuery('#ropProcessCheck').is(':checked'))
			{
				jQuery('input[name="filter[cluster_id]"]').val('all');		
				jQuery('#cluster').val('all').trigger('chosen:updated');
			}
		}
		
		jQuery('#ropProcessHighLevelCheck').click(function()
		{
		var ischecked= jQuery('#ropProcessHighLevelCheck').is(':checked');

		})
});

</script>
<div id="system-message-container"></div>
<form action="" method="post" name="adminForm" id="adminForm">
	<div id="ropCopyLoader" class="centerloader hide"></div>
	<input type="hidden" id="business_function" name="business_function" value="" />

	<div class="row mb-10 process-list-header" id="">
		<!--By changing the check box value list of process will change-->
		<?php
			$input        = Factory::getApplication()->input;
			$selected     = $input->get('filter_process', 'myprocess', 'STRING');
			$check        = ($selected == 'generic') ? 'checked': '';
			$ropListTitle = ($selected == 'generic') ? Text::plural('COM_TJUCM_GENERIC_PROCESSES', count($this->items)): Text::plural('COM_TJUCM_MY_PROCESSES', count($this->items));

			// To hide School column for generic process
			$ropSchoolId = $this->ropSchoolId = 0;

			if ($selected == 'generic')
			{
				// @todo Need to server side validation
				if (!$user->authorise('core.manageall', 'com_cluster'))
				{
					$canDeleteOwn  = false;
					$canEditOwn    = false;
				}

				$ropSchoolId = $this->ropSchoolId = $ropSchoolData[0]->id;
			}
		?>
		<div class="col-sm-3 col-xs-12 my-25" id="">
			<h4 id="ropListlabel" class="font-bold"><?php echo Text::_($ropListTitle);?></h4>
		</div>
		<div class="col-sm-9 col-xs-12 my-25" id="">
			<div class="pull-right">
				<!--Generic Checkbox-->
				<input type="checkbox" name="ropProcessCheck" onclick="loadpreloader();" id="ropProcessCheck" <?php echo $check;?>>
				<label for="ropProcessCheck" class="ropProcessCheck-lable mr-25 mb-15"><?php echo Text::_('COM_TJUCM_ROP_GENERIC_PROCESSES');?></label>

				<!--Generic Checkbox-->
				

				<!-- Cluster Filter-->
				<?php
				$params     			   = ComponentHelper::getParams('com_multiagency');
				$orgAdminRoleId            = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
			    $orgAdminRoleId 		   = in_array($orgAdminRoleId, $user->groups);

				$user = Factory::getUser();
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
				<div class="btn-group md-w-300px mb-15">
					<fieldset id="filter-bar">
					<div class="filter-select fltrt">
					<select name="filter_tags[]" id = "tags" class="inputbox" multiple="multiple" onchange="this.form.submit()">
						<option value=""> <?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?> </option>
						<?php echo HTMLHelper::_('select.options', $dpeTag, 'value', 'text', $this->state->get('filter.tags'));?>
					</select>
				</div>
			</fieldset>
			</div>
			<?php
			}
				if ($selected == 'myprocess')
				{
					// Check if com_cluster component is installed
					if (ComponentHelper::getComponent('com_cluster', true)->enabled)
					{
						FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
						$cluster           = FormHelper::loadFieldType('cluster', false);
						$this->clusterList = $cluster->getOptionsExternally();
						?>
						<div class="btn-group md-w-300px mb-15" id="rop_cluster_field_cover">
							<?php
								echo HTMLHelper::_('select.genericlist', $this->clusterList, "cluster", 'class="input-medium" size="1" onchange="this.form.submit();"', "value", "text", $this->state->get($this->client . '.filter.cluster_id', '', 'INT'));
							?>
						</div>
						<?php
					}
				}
				?>
				<!-- Add Process button-->
				<?php
					if ($this->allowedToAdd)
					{
						$appendUrl = "";

						if (!empty($this->created_by))
						{
							$appendUrl .= "&created_by=" . $this->created_by;
						}

						if (!empty($this->client))
						{
							$appendUrl .= "&client=" . $this->client;
						}

						$config = Factory::getConfig();
						$url    = Route::_('index.php?option=com_tjucm&view=items' . $appendUrl, true);

						if ($config->get('sef') == "1")
						{
							$url = Route::_(Uri::base() . 'index.php?option=com_tjucm&view=items' . $appendUrl);
						}

						?>
						<a href="javascript:void(0);" onclick="tjucm.itmes.openRopPopups('<?php echo addslashes(Route::_($createRopLink));?>')"
							class="btn btn-add add-process-btn btn-small ml-10 mb-15">
							<i class="icon-plus"></i><?php echo Text::_('COM_TJUCM_ROP_ADD_PROCESS_ITEM'); ?>
						</a>
					<?php
					}
				?>
				<br>
				<input type="checkbox" name="ropProcessHighLevelCheck" onclick="loadpreloader();" id="ropProcessHighLevelCheck" vlaue="High Level" <?php echo $check;?>>
				<label for="ropProcessHighLevelCheck" class="ropProcessCheck-lable mr-25 mb-15"><?php echo Text::_('COM_TJUCM_ROP_HIGHLEVEL_PROCESSES');?></label>
			</div>
		</div>
		<div class="col-sm-12 col-xs-12 nav-cover">
			<ul class="nav nav-tabs" id="ropListtabsId">
			<?php
			$k = 0;
			$j = 1;
			?>

			<li data-tab-content="some-id<?php echo $k;?>" id="ropBusinessFunctionLi<?php echo $k;?>" class="" onclick="loadpreloader();">
					<a class="ucm-loadmore-tab" href="javascript:void(0)"
						id="ropBusinessFunctionAccordian<?php echo $k;?>"
						data-target-form="ropBusinessFunctionForm<?php echo $k;?>"
						data-business-function=""
						data-accordian-id="<?php echo $k;?>"
						data-rel="<?php echo $businessFunctionFieldId . '_' ?>"
						>
						<?php echo Text::_('COM_TJUCM_ROP_SHOW_ALL'); ?>
					</a>
				</li>
			<?php foreach ($ropbusinessFieldOptions as $key => $ropbusinessFieldOption)
			{

				?>
				<li data-tab-content="some-id<?php echo $j;?>" id="ropBusinessFunctionLi<?php echo $j;?>" class="" onclick="loadpreloader();">
					<a class="ucm-loadmore-tab" href="javascript:void(0)"
						id="ropBusinessFunctionAccordian<?php echo $j;?>"
						data-target-form="ropBusinessFunctionForm<?php echo $j;?>"
						data-business-function="<?php echo $ropbusinessFieldOption->options;?>"
						data-accordian-id="<?php echo $j;?>"
						data-rel="<?php echo $businessFunctionFieldId . '_' . $ropbusinessFieldOption->options;?>"
						>
						<?php echo $ropbusinessFieldOption->options;?>
					</a>
				</li>
				<?php
				$j++;
			}
			?>
		</ul>
		</div>
	</div>
	<input type="hidden" id="client" name="client" value="<?php echo $this->client ?>"/>
	<input type="hidden" name="boxchecked" value="0"/>
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
<style>.tab-content { display: none; }</style>
<div class="container-fluid px-0">
    <div id="panelBodyCover" class="panel-body-cover">
    <?php
		$j = 1;
		$k = 0;

		$allBusinessFunction[] = 'AllProcess';

		foreach($ropbusinessFieldOptions as $allOption)
		{
			$allBusinessFunction[] = $allOption->value;
		}

		$allBusinessFunction = implode(',', $allBusinessFunction);

		?>


		<div id="panelBody<?php echo $k;?>" class="" data-accordian-id="<?php echo $k;?>">
					<form action="" method="post" name="ropBusinessFunctionForm<?php echo $k;?>" id="ropBusinessFunctionForm<?php echo $k;?>" class="ropBusinessFunctionForm" onsubmit="return false;">
						<?php echo HTMLHelper::_('form.token'); ?>
						<!--Filter Div-->
						<div class="col-md-12 px-0">
							<div class="btn-group pull-left mb-2 me-2">
								<div class="pull-left">
									<input
										type="text"
										name="filter[search]"
										id="filterSearch_<?php echo $k;?>"
										onchange="loadpreloader();"
										value=""
										class="ucm-rop-search"
										placeholder="<?php echo Text::_('COM_TJUCM_ROP_PROCESS_SEARCH');?>"
										title="<?php echo Text::_('COM_TJUCM_ROP_PROCESS_SEARCH');?>"
										data-rel="<?php echo $businessFunctionFieldId . '_' . $option->options;?>"
										data-accordian-id="<?php echo $k;?>"
										data-target-form="ropBusinessFunctionForm<?php echo $k;?>"
										size="30">
								</div>
								<div class="btn-group pull-left">
									<button class="btn btn-primary">
										<i class="fa fa-search fa-2 ropListSearch"
										id="ropListSearch<?php echo $k;?>"
										data-target-form="ropBusinessFunctionForm<?php echo $k;?>"
										data-accordian-id="<?php echo $k;?>" aria-hidden="true"
										>
										</i>
									</button>
									<button class="btn">
										<i class="fa fa-times fa-2 resetSearchText"
										id="resetSearchText<?php echo $k;?>"
										onclick="loadpreloader();"
										aria-hidden="true"
										data-accordian-id="<?php echo $k;?>"
										data-target-form="ropBusinessFunctionForm<?php echo $k;?>"
										>
										</i>
									</button>
								</div>
							</div>
							<ul class="list-inline">
								<li class="me-3">
									<?php $alert = TEXT::_('COM_TJUCM_RPO_NOT_CHECKEDLIST_MSG')?>
									<a class=""
										href="javascript:void(0);"
										onclick="if(ValidateRecordList()){
											tjucm.itmes.openRopPopups('<?php echo addslashes(Route::_($createRopLink1 . '&iscopy=1'));?>', this)
										}
										else
										{alert('<?php echo $alert;?>');
										}; "
										id="rop-make-copy"
										data-target-form="ropBusinessFunctionForm<?php echo $k;?>"
										title="<?php echo TEXT::_('COM_TJUCM_ROP_PROCESS_MAKE_COPY');?>" >
										<i class="fa fa-copy"></i><?php echo ' ' . TEXT::_('COM_TJUCM_ROP_PROCESS_MAKE_COPY')?>
									</a>
								</li>
								<li class="">
									<a class=""
										href="javascript:void(0);"
										onclick="tjucm.itmes.deleteMultipleItems(this);"
										id="rop-delete"
										data-target-form="ropBusinessFunctionForm<?php echo $k;?>"
										title="<?php echo TEXT::_('COM_TJUCM_RECORD_DELETE_BTN_TEXT');?>" >
										<i class="fa fa-trash"></i><?php echo ' ' . TEXT::_('COM_TJUCM_RECORD_DELETE_BTN_TEXT')?>
									</a>
								</li>
							</ul>

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
							
								<div class="preloader-wrap">
									 <div class="percentage" id="precent"></div>
										<div class="newloader">
											 <div class="trackbar">
											   <div class="loadbar"></div>
											 </div>
										 <div class="glow"></div>
									</div><br><br>
								 <div class="getdata" id="getdata"><?php echo ' ' . TEXT::_('COM_DPE_LOADER_GETTING_DATA');?></div>
								</div>
						</div>
						<div class="col-md-12 col-xs-12 col-sm-12 mt-15 px-0 ">
							<div class="rop-list-cover no-more-tables overflow-x ucm-loadmore-tab-content<?php echo $k;?>" id="ropProcessList_<?php echo $j?>" data-accordian-id="<?php echo $k;?>">
							</div>
						</div>

						<input type="hidden"id="option<?php echo $k?>" name="option" value="com_tjucm" />
						<input type="hidden" name="view" value="items" />
						<input type="hidden" id="controller<?php echo $j?>" name="controller" value="items" />
						<input type="hidden" id="task<?php echo $k?>" name="task" value="items.display" />
						<input type="hidden" name="total" value="" />
						<input type="hidden" name="client" value="<?php echo $this->client ? $this->client : "com_tjucm.rop";?>"/>
						<input type="hidden" name="typeId" value="<?php echo $this->ucmTypeId;?>"/>
						<input type="hidden" name="field_data" value="<?php echo $businessFunctionFieldId . '_' . $allBusinessFunction;?>"   id="field_data_<?php echo $k;?>" data-field-data="<?php echo $businessFunctionFieldId . '_' . $allBusinessFunction;?>"/>

						<input type="hidden" name="request_status_field_value" value=""/>
						<input type="hidden" name="request_status_field_id" value="<?php echo $requestStatusField->id; ?>"/>
						<input type="hidden" name="nextReviewDateField" value="<?php echo $nextReviewDateField->id; ?>"/>

						<input type="hidden" name="exisitng_process_field_value" value=""/>
						<input type="hidden" name="exisitng_process__field_id" value="<?php echo $existingProcssField->id; ?>"/>

						<input type="hidden" name="limit" value="<?php echo $listLimit; ?>" />
						<input type="hidden" name="limitstart" value="0" />
						<input type="hidden" name="loaded" value="false" />
						<input type="hidden" name="format" value="json" />
						<input type="hidden" name="filter_order" value="<?php echo $nextReviewDateField->id; ?>" />
						<input type="hidden" name="filter_order_Dir" value="desc" />
						<input type="hidden" name="filter[cluster_id]" value="<?php echo $this->escape($this->state->get($this->client . '.filter.cluster_id', '', 'INT'));?>" />
						<input type="hidden" name="filter[process]" value="" />
						<input type="hidden" name="process_category" value="" />
						<input type="hidden" name="filter_coredata" id='filter_coredata' value="1"/>


						<div class="text-center">
							<span class="font-600 text-center" id="recordcounter<?php echo $k;?>">
							</span>
						</div>

						<div class="preloader-wrap-loadmore" style="display: none;">
									 <div class="percentage-loadmore" id="precent-loadmore"></div>
										<div class="newloader-loadmore">
											 <div class="trackbar-loadmore">
											   <div class="loadbar-loadmore"></div>
											 </div>
										 <div class="glow-loadmore"></div>
									</div><br><br>
								 <div class="getdata-loadmore" id="getdata-loadmore"><?php echo ' ' . TEXT::_('COM_DPE_LOADER_GETTING_DATA');?></div>
						</div>

						<div class="text-center hide" id="rop-loadmore<?php echo $k;?>">
									<button data-accordian-id="<?php echo $k;?>"  onclick="loadpreloaderformore()" class="btn btn-primary rop-loadmore mb-20"><?php echo Text::_('COM_TJUCM_ROP_LOAD_MORE');?></button>
						</div>

						
					</form>
				</div>




 		<?php foreach ($ropbusinessFieldOptions as $key => $option)
		{
			?>
				<div id="panelBody<?php echo $j;?>" class="hide" data-accordian-id="<?php echo $j;?>">
					<form action="" method="post" name="ropBusinessFunctionForm<?php echo $j;?>" id="ropBusinessFunctionForm<?php echo $j;?>" class="ropBusinessFunctionForm" onsubmit="return false;">
						<?php echo HTMLHelper::_('form.token'); ?>
						<!--Filter Div-->
						<div class="col-md-12 px-0">
							<div class="btn-group pull-left mb-2 me-2">
								<div class="pull-left">
									<input
										type="text"
										name="filter[search]"
										id="filterSearch_<?php echo $j;?>"
										onchange="loadpreloader();"
										value=""
										class="ucm-rop-search"
										placeholder="<?php echo Text::_('COM_TJUCM_ROP_PROCESS_SEARCH');?>"
										title="<?php echo Text::_('COM_TJUCM_ROP_PROCESS_SEARCH');?>"
										data-rel="<?php echo $businessFunctionFieldId . '_' . $option->options;?>"
										data-accordian-id="<?php echo $j;?>"
										data-target-form="ropBusinessFunctionForm<?php echo $j;?>"
										size="30">
								</div>
								<div class="btn-group pull-left">
									<button class="btn btn-primary">
										<i class="fa fa-search fa-2 ropListSearch"
										id="ropListSearch<?php echo $j;?>"
										data-target-form="ropBusinessFunctionForm<?php echo $j;?>"
										data-accordian-id="<?php echo $j;?>" aria-hidden="true"
										>
										</i>
									</button>
									<button class="btn">
										<i class="fa fa-times fa-2 resetSearchText"
										id="resetSearchText<?php echo $j;?>"
										onclick="loadpreloader();"
										aria-hidden="true"
										data-accordian-id="<?php echo $j;?>"
										data-target-form="ropBusinessFunctionForm<?php echo $j;?>"
										>
										</i>
									</button>
								</div>
							</div>
							<ul class="list-inline">
					
								<li class="me-3">
									<?php $alert = TEXT::_('COM_TJUCM_RPO_NOT_CHECKEDLIST_MSG')?>
									<a class=""
										href="javascript:void(0);"
										onclick="if(ValidateRecordList()){
											tjucm.itmes.openRopPopups('<?php echo addslashes(Route::_($createRopLink1 . '&iscopy=1'));?>', this)
										}
										else
										{alert('<?php echo $alert;?>');
										}; "
										id="rop-make-copy"
										data-target-form="ropBusinessFunctionForm<?php echo $j;?>"
										title="<?php echo TEXT::_('COM_TJUCM_ROP_PROCESS_MAKE_COPY');?>" >
										<i class="fa fa-copy"></i><?php echo ' ' . TEXT::_('COM_TJUCM_ROP_PROCESS_MAKE_COPY')?>
									</a>
								</li>
								<li class="">
									<a class=""
										href="javascript:void(0);"
										onclick="tjucm.itmes.deleteMultipleItems(this);"
										id="rop-delete"
										data-target-form="ropBusinessFunctionForm<?php echo $j;?>"
										title="<?php echo TEXT::_('COM_TJUCM_RECORD_DELETE_BTN_TEXT');?>" >
										<i class="fa fa-trash"></i><?php echo ' ' . TEXT::_('COM_TJUCM_RECORD_DELETE_BTN_TEXT')?>
									</a>
								</li>
							</ul>

								<div class="preloader-wrap">
									 <div class="percentage" id="precent"></div>
										<div class="newloader">
											 <div class="trackbar">
											   <div class="loadbar"></div>
											 </div>
										 <div class="glow"></div>
									</div><br><br>
								 <div class="getdata" id="getdata"><?php echo ' ' . TEXT::_('COM_DPE_LOADER_GETTING_DATA');?></div>
								</div>
						</div>
						<div class="col-md-12 col-xs-12 col-sm-12 mt-15 px-0 ">
							<div class="rop-list-cover no-more-tables overflow-x ucm-loadmore-tab-content<?php echo $j;?>" id="ropProcessList_<?php echo $j?>" data-accordian-id="<?php echo $j;?>">
							</div>
						</div>

						<input type="hidden"id="option<?php echo $j?>" name="option" value="com_tjucm" />
						<input type="hidden" name="view" value="items" />
						<input type="hidden" id="controller<?php echo $j?>" name="controller" value="items" />
						<input type="hidden" id="task<?php echo $j?>" name="task" value="items.display" />
						<input type="hidden" name="total" value="" />
						<input type="hidden" name="client" value="<?php echo $this->client ? $this->client : "com_tjucm.rop";?>"/>
						<input type="hidden" name="typeId" value="<?php echo $this->ucmTypeId;?>"/>
						<input type="hidden" name="field_data" value="<?php echo $businessFunctionFieldId . '_' . $option->value;?>"   id="field_data_<?php echo $j;?>" data-field-data="<?php echo $businessFunctionFieldId . '_' . $option->value;?>"/>
						<input type="hidden" name="request_status_field_value" value=""/>
						<input type="hidden" name="request_status_field_id" value="<?php echo $requestStatusField->id; ?>"/>
						<input type="hidden" name="nextReviewDateField" value="<?php echo $nextReviewDateField->id; ?>"/>

						<input type="hidden" name="exisitng_process_field_value" value=""/>
						<input type="hidden" name="exisitng_process__field_id" value="<?php echo $existingProcssField->id; ?>"/>

						<input type="hidden" name="limit" value="<?php echo $listLimit; ?>" />
						<input type="hidden" name="limitstart" value="0" />
						<input type="hidden" name="loaded" value="false" />
						<input type="hidden" name="format" value="json" />
						<input type="hidden" name="filter_order" value="<?php echo $nextReviewDateField->id; ?>" />
						<input type="hidden" name="filter_order_Dir" value="desc" />
						<input type="hidden" name="filter[cluster_id]" value="<?php echo $this->escape($this->state->get($this->client . '.filter.cluster_id', '', 'INT'));?>" />
						<input type="hidden" name="filter[process]" value="" />
						<input type="hidden" name="filter_coredata" id='filter_coredata' value="1"/>
						<input type="hidden" name="process_category" id = "process_category" value="" />
						<div class="text-center">
							<span class="font-600 text-center" id="recordcounter<?php echo $j;?>">
							</span>
						</div>

						<div class="preloader-wrap-loadmore" style="display: none;">
									 <div class="percentage-loadmore" id="precent-loadmore"></div>
										<div class="newloader-loadmore">
											 <div class="trackbar-loadmore">
											   <div class="loadbar-loadmore"></div>
											 </div>
										 <div class="glow-loadmore"></div>
									</div><br><br>
								 <div class="getdata-loadmore" id="getdata-loadmore"><?php echo ' ' . TEXT::_('COM_DPE_LOADER_GETTING_DATA');?></div>
						</div>

						<div class="text-center hide" id="rop-loadmore<?php echo $j;?>">
									<button data-accordian-id="<?php echo $j;?>"  onclick="loadpreloaderformore()" class="btn btn-primary rop-loadmore mb-20"><?php echo Text::_('COM_TJUCM_ROP_LOAD_MORE');?></button>
						</div>

						
					</form>
				</div>

			<?php
			$j++;
		} 
    ?>
    <input type="hidden" name="activeAccordion" id="activeAccordion" value="<?php echo $accordion; ?>" />
    <!-- Panel body cover end-->
	</div>
</div>
<script>
jQuery(document).ready(function() {
        jQuery("#ropListtabsId").on("click", "li", function(evt) {
			var current = document.getElementsByClassName("active");

			if (current.length >= 1)
			{
				current[0].className = this.className.replace("active", "");
				this.className += "active";
			}
        });
    });

var menuItemId = "<?php echo $itemId;?>";
tjucm.itmes.init();

<?php if ($accordion == 0) { ?>
	var el = jQuery("#ropBusinessFunctionAccordian<?php echo $accordion; ?>");
	var currentActive = document.getElementsByClassName("active");
	currentActive[0].className = currentActive[0].className.replace("active", "");
	document.getElementById("ropBusinessFunctionLi<?php echo $accordion; ?>").className="active";
	var form = jQuery('#' + el.data('target-form'));
	jQuery('.currentActive').addClass('hide').removeClass('currentActive');
	jQuery('#panelBody' + el.data('accordian-id')).removeClass('hide').addClass('currentActive');
	if (storedCheck != "1" && storedHighLevel !='1' && storedTabId == null) {
	ucmRopLoadData(el);
	}
<?php } ?>

function ValidateRecordList() {

	if (jQuery('input[name="cid[]"]:checked').length < 1) 
	{ 
		 return false;
	}
	else {

		return true;

		}
	}
</script>
<script>
	jQuery(document).ready(function() {

		jQuery('#tags').change(function(e) {
	        
			// check the tag filter is empty or not and set the tag filter
	        var selectData = jQuery("#tags").chosen().val();       
	       
	        if ((selectData == null )|| (selectData == ''))
	        { 
	        	jQuery("#tags").val(jQuery("#tags option:first").val());
	        }

	        if (selectData  || (selectData == null ) )
	        {
	        	var uri = window.location.toString();
	            if (uri.indexOf("filter[tags]") > 0) {
	                var clean_uri = uri.substring(0,
	                                uri.indexOf("filter[tags]"));
	 
	                window.history.replaceState({},
	                        document.title, clean_uri);
	            }
	        }
	   	}); 

		// update the plcaeholder
   		jQuery("#tags").attr("data-placeholder", "<?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?>");
		jQuery("#tags").trigger("chosen:updated");

		var isDpeAdmin = "<?php echo $user->authorise('core.manageall', 'com_cluster'); ?>";
		if (!isDpeAdmin)
		{
			jQuery('#filter_tags_chosen').hide();
		}

		jQuery('#tags').on('change', function() {
			jQuery("#cluster").val(jQuery("#cluster option:first").val());
	    });
	    jQuery('#cluster').on('change', function() {	
			jQuery("#tags").val(jQuery("#tags option:first").val());
	    });

	    var elementsWithSameId = document.querySelectorAll("#tags_chosen");
	
	if (elementsWithSameId.length > 0) {
		  elementsWithSameId[0].style.display = "none";
}
})
</script>
