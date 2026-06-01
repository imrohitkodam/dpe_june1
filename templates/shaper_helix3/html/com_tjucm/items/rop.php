<?php
/**
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;

HTMLHelper::_('jquery.token');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('behavior.multiselect');

HTMLHelper::_('bootstrap.renderModal');

$listLimit = Factory::getConfig()->get('list_limit', 20);
$document = Factory::getDocument();
$document->addScript(Uri::root() . 'media/com_dpe/js/tjucm.js');
$document->addScript(Uri::root() . 'media/com_dpe/vendors/jquery-ui.min.js');

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models');
$tjfieldsModelOptions = BaseDatabaseModel::getInstance('Options', 'TjfieldsModel', array('ignore_request' => true));

// While creating a business field need to add same class name
$tjfieldsModelFields  = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
$tjfieldsModelFields->setState('filter.client', 'com_tjucm.rop');
$tjfieldsModelFields->setState('filter.validation_class', 'business-function');
$ropbusinessFieldData = $tjfieldsModelFields->getItems();

// While creating a school field need to add same class name
$tjfieldsModelFields  = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
$tjfieldsModelFields->setState('filter.client', 'com_tjucm.rop');
$tjfieldsModelFields->setState('filter.validation_class', 'cluster-ownership');
$ropSchoolData = $tjfieldsModelFields->getItems();

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

JLoader::import('clusters', JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
$clusterTableClusters = Table::getInstance('clusters', 'ClusterTable', array());

$businessFunctionFieldId = $this->businessFunctionFieldId = 0;

if (!empty($ropbusinessFieldData))
{
	$businessFunctionFieldId = $this->businessFunctionFieldId = $ropbusinessFieldData[0]->id;

	$tjfieldsModelOptions->setState('filter.field_id', $businessFunctionFieldId);
	$ropbusinessFieldOptions = $tjfieldsModelOptions->getItems();
}

$other = new stdClass;
$other->value = 'other-options';
$other->options = 'other';
array_push($ropbusinessFieldOptions, $other);

Text::script('COM_DPE_SELECT_SINGLE_ROP');
Text::script('COM_TJUCM_DELETE_MESSAGE');

// Get Process Addtion form Itemid
$menu   = Factory::getApplication()->getMenu();
$itemId = $menu->getActive()->id;

$createRopLink = Route::_('index.php?option=com_dpe&view=ropform&tmpl=component&client=' . $this->client. '&Itemid=' . $itemId);
$createRopLink1 = Route::_('index.php?option=com_dpe&view=ropform&tmpl=component&layout=ropcopy&client=' . $this->client. '&Itemid=' . $itemId);
$user          = Factory::getUser();
$canEditOwn    = $user->authorise('core.type.editownitem', 'com_tjucm.type.' . $this->ucmTypeId);
$canDeleteOwn  = $user->authorise('core.type.deleteitem', 'com_tjucm.type.' . $this->ucmTypeId);

$importItemsPopUpUrl = Uri::root() . '/index.php?option=com_tjucm&view=items&layout=importitems&tmpl=component&client=' . $this->client;
$copyItemPopupUrl = Uri::root() . 'index.php?option=com_tjucm&view=items&layout=copyitems&tmpl=component&client=' . $this->client;
Factory::getDocument()->addScriptDeclaration('
	jQuery(document).ready(function(){
		jQuery("#adminForm #import-items").click(function() {
			SqueezeBox.open("' . $importItemsPopUpUrl . '" ,{handler: "iframe", size: {x: window.innerWidth-250, y: window.innerHeight-150}});
		});
	});
');

Factory::getDocument()->addScriptDeclaration("
	function copySameUcmTypeItem()
	{
		var afterCopyItem = function(error, response){
			jQuery('#item-form #tjucm_loader').hide();
			jQuery('html, body').animate({scrollTop: jQuery('#item-form #tjucm_loader').position().top}, 'slow');
			response = JSON.parse(response);

			sessionStorage.setItem('message', response.message);
			if(response.data !== null)
			{
				window.parent.location.reload();
				sessionStorage.setItem('class', 'alert alert-success');
			}
			else
			{
				sessionStorage.setItem('class', 'alert alert-danger');
			}
		}

		var copyItemData =  jQuery('#ropBusinessFunctionForm9').serialize();

		// Code to copy item to ucm type
		com_tjucm.Services.Items.copyItem(copyItemData, afterCopyItem);
	}
");

if (!empty($this->created_by))
{
	$appendUrl .= "&created_by=" . $this->created_by;
}

if (!empty($this->client))
{
	$appendUrl .= "&client=" . $this->client;
}

$tjUcmFrontendHelper = new TjucmHelpersTjucm;
$link = 'index.php?option=com_tjucm&view=items' . $appendUrl;
$itemId = $tjUcmFrontendHelper->getItemId($link);
?>
<form action="" method="post" name="adminForm" id="adminForm">
	<div class="row mb-10" id="">
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
		<div class="col-sm-3" id="">
			<h4 id="ropListlabel" class="font-bold"><?php echo Text::_($ropListTitle);?></h4>
		</div>
		<div class="col-sm-9" id="">
			<div class="pull-right">
				<!--Generic Checkbox-->
				<input type="checkbox" name="ropProcessCheck" id="ropProcessCheck" <?php echo $check;?>>
				<label for="ropProcessCheck" class=""><?php echo Text::_('COM_TJUCM_ROP_GENERIC_PROCESSES');?></label>

				<!-- Cluster Filter-->
				<?php
				if ($selected == 'myprocess')
				{
					// Check if com_cluster component is installed
					if (ComponentHelper::getComponent('com_cluster', true)->enabled)
					{
						FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
						$cluster           = FormHelper::loadFieldType('cluster', false);
						$this->clusterList = $cluster->getOptionsExternally();
						?>
						<div class="btn-group  mx-10 md-w-300px">
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
							class=" btn btn-add btn-small">
							<i class="icon-plus"></i><?php echo Text::_('COM_TJUCM_ROP_ADD_PROCESS_ITEM'); ?>
						</a>
					<?php
					}
				?>
			</div>
		</div>
	</div>
	<input type="hidden" id="client" name="client" value="<?php echo $this->client ?>"/>
	<input type="hidden" name="boxchecked" value="0"/>
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>

	<div class="row rop-process-list mt-20" id="">
		<div class="col-md-12" id="">
			<div class="panel-group" id="accordion">
			<?php
				$j = 1;

				foreach ($ropbusinessFieldOptions as $key => $option)
				{
					?>
					<div class="panel panel-default">
						<div class="panel-heading" id="card-header<?php echo $j;?>">
							<h4 class="panel-title">
								<a
								class="ucm-loadmore-tab"
								data-toggle="collapse"
								data-parent="#accordion"
								data-target="#collapse_<?php echo $j;?>"
								data-target-form="ropBusinessFunctionForm<?php echo $j;?>"
								data-rel="<?php echo $businessFunctionFieldId . '_' . $option->options;?>"
								data-accordian-id="<?php echo $j;?>"
								aria-expanded="true"
								aria-controls="collapse<?php echo $j;?>" href="javascript:void(0)">
									<?php echo ucwords($option->options);?>
									<i class="fa fa-angle-down pull-right"></i>
								</a>
							</h4>
						</div>
						<div id="collapse_<?php echo $j;?>" class="panel-collapse rop-collapse-scroll collapse" aria-labelledby="card-header<?php echo $j;?>" data-parent="#accordionExample">
							<div class="panel-body">
								<form action="" method="post" name="ropBusinessFunctionForm<?php echo $j;?>" id="ropBusinessFunctionForm<?php echo $j;?>" onsubmit="return false;">
									<?php echo HTMLHelper::_('form.token'); ?>
									<!--Filter Div-->
									<div class="col-md-12">
										<ul class="pull-right list-inline">
											<li class="list__separation">
												<input
													type="text"
													name="filter[search]"
													id="filterSearch_<?php echo $j;?>"
													value=""
													class="ucm-rop-search"
													placeholder="<?php echo JTEXT::_('COM_TJUCM_ROP_PROCESS_SEARCH');?>"
													title="<?php echo JTEXT::_('COM_TJUCM_ROP_PROCESS_SEARCH');?>"
													data-rel="<?php echo $businessFunctionFieldId . '_' . $option->options;?>"
													data-accordian-id="<?php echo $j;?>"
													data-target-form="ropBusinessFunctionForm<?php echo $j;?>"
													size="30">
											</li>
<!--
											<li class="">
												<a class=""
													href="javascript:void(0);"
													onclick="tjucm.itmes.openRopPopups('<?php echo addslashes(Route::_($createRopLink . '&iscopy=1'));?>', this)"
													id="rop-make-copy"
													data-target-form="ropBusinessFunctionForm<?php echo $j;?>"
													title="<?php echo TEXT::_('COM_TJUCM_ROP_PROCESS_MAKE_COPY');?>" >
													<i class="fa fa-copy"></i><?php echo ' ' . TEXT::_('COM_TJUCM_ROP_PROCESS_MAKE_COPY')?>
												</a>
											</li>
-->
<!--
											<li>
-->
											<?php
											/*
											if ($this->canCopyItem)
											{
												if ($this->canCopyToSameUcmType)
												{?>
													<a onclick="if(document.adminForm.boxchecked.value==0){alert(Joomla.Text._('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));}else{jQuery('#item-form #tjucm_loader').show(); copySameUcmTypeItem()}" class="btn btn-default btn-small">
													<i class="fa fa-clone"></i> <?php echo Text::_('COM_TJUCM_COPY_ITEM'); ?>
													</a><?php
												}
												else
												{
												?>
												<a href="#"
												onclick="if(document.adminForm.boxchecked.value==0){alert(Joomla.Text._('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));}else{jQuery( '#collapseModal<?php echo $j;?>' ).modal('show'); return true;}"  class="btn btn-default btn-small">
													<i class="fa fa-clone"></i> <?php echo Text::_('COM_TJUCM_COPY_ITEM'); ?>
												</a>
												<?php
												}
												?>
												<?php echo HTMLHelper::_(
													'bootstrap.renderModal',
													'collapseModal' . $j,
													array(
														'title'  => Text::_('COM_TJUCM_COPY_ITEMS'),
														'j'  => $j,
													),
													$this->loadTemplate('copyitems_rop')
												); ?>
												<?php
											}
											*/
											?>
<!--
											</li>
-->
											<li class="">
												<a class=""
													href="javascript:void(0);"
													onclick="tjucm.itmes.openRopPopups('<?php echo addslashes(Route::_($createRopLink1 . '&iscopy=1'));?>', this)"
													id="rop-make-copy"
													data-target-form="ropBusinessFunctionForm<?php echo $j;?>"
													title="<?php echo TEXT::_('COM_TJUCM_ROP_PROCESS_MAKE_COPY');?>" >
													<i class="fa fa-copy"></i><?php echo ' ' . TEXT::_('COM_TJUCM_ROP_PROCESS_MAKE_COPY')?>
												</a>
											</li>

										</ul>
									</div>
									<div class="col-md-12 col-xs-12 col-sm-12 mt-15 ">
										<div class="no-more-tables overflow-x ucm-loadmore-tab-content<?php echo $j;?>" id="ropProcessList_<?php echo $j?>">
										</div>
									</div>

									<input type="hidden" name="option" value="com_tjucm" />
									<input type="hidden" name="view" value="items" />
									<input type="hidden" name="controller" value="items" />
									<input type="hidden" name="task" value="items.display" />
									<input type="hidden" name="total" value="" />
									<input type="hidden" name="client" value="<?php echo $this->client ? $this->client : "com_tjucm.rop";?>"/>
									<input type="hidden" name="typeId" value="<?php echo $this->ucmTypeId;?>"/>
									<input type="hidden" name="field_data" value="<?php echo $businessFunctionFieldId . '_' . $option->value;?>"/>
									<input type="hidden" name="request_status_field_value" value=""/>
									<input type="hidden" name="request_status_field_id" value="<?php echo $requestStatusField->id; ?>"/>

									<input type="hidden" name="exisitng_process_field_value" value=""/>
									<input type="hidden" name="exisitng_process__field_id" value="<?php echo $existingProcssField->id; ?>"/>

									<input type="hidden" name="nextReviewDateField" value="<?php echo $nextReviewDateField->id; ?>"/>
									<input type="hidden" name="limit" value="<?php echo $listLimit; ?>" />
									<input type="hidden" name="limitstart" value="0" />
									<input type="hidden" name="loaded" value="false" />
									<input type="hidden" name="format" value="json" />
									<input type="hidden" name="filter_order" value="<?php echo $nextReviewDateField->id; ?>" />
									<input type="hidden" name="filter_order_Dir" value="desc" />
									<input type="hidden" name="filter[cluster_id]" value="<?php echo $this->escape($this->state->get($this->client . '.filter.cluster_id', '', 'INT'));?>" />
									<input type="hidden" name="filter[process]" value="<?php echo $this->escape($selected);?>" />

									<div class="text-center">
										<span class="font-600 text-center" id="recordcounter<?php echo $j;?>">
										</span>
									</div>
									<div class="text-center hide" id="rop-loadmore<?php echo $j;?>">
												<button data-accordian-id="<?php echo $j;?>" class="btn btn-primary rop-loadmore"><?php echo Text::_('COM_TJUCM_ROP_LOAD_MORE');?></button>
									</div>
								</form>
							</div>
						</div>
					</div>
				<?php
					$j++;
				}
			?>
			</div>
		</div>
	</div>
<script type="text/javascript">
 var menuItemId = "<?php echo $itemId;?>";
 tjucm.itmes.init();
</script>
