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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Layout\FileLayout;

use Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('bootstrap.renderModal');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('jquery.token');

$importItemsPopUpUrl = Uri::root() . 'index.php?option=com_tjucm&view=items&layout=importitems&tmpl=component&client=' . $this->client;
$copyItemPopupUrl = Uri::root() . 'index.php?option=com_tjucm&view=items&layout=copyitems&tmpl=component&client=' . $this->client;
Factory::getDocument()->addScriptDeclaration('
	jQuery(document).ready(function(){
		jQuery("#adminForm #import-items").click(function() {
			SqueezeBox.open("' . $importItemsPopUpUrl . '" ,{handler: "iframe", size: {x: window.innerWidth-250, y: window.innerHeight-150}});
			});
			});
			');
/*To load language constant of js file*/
Text::script('COM_TJUCM_DELETE_MESSAGE');
Text::script('COM_TJUCM_EXPORT_NO_ITEM_SELECT');
Text::script('COM_TJUCM_NO_ITEM_SELECTED');

$user = Factory::getUser();
$userId = $user->get('id');
$tjUcmFrontendHelper = new TjucmHelpersTjucm;
$listOrder  = $this->escape($this->state->get('list.ordering'));
$listDirn   = $this->escape($this->state->get('list.direction'));
$createUcmCopyLink = Route::_('index.php?option=com_dpe&view=ropform&tmpl=component&layout=coredatamultiplecopy&client=' . $this->client. '&Itemid=' . $itemId);

$document = Factory::getDocument();
$importItemsPopUpUrl = Uri::root() . 'index.php?option=com_tjucm&view=items&layout=importitems&tmpl=component&client=' . $this->client;
$copyItemPopupUrl = Uri::root() . 'index.php?option=com_tjucm&view=items&layout=copyitems&tmpl=component&client=' . $this->client;
$document->addScriptDeclaration('
	jQuery(document).ready(function(){
		jQuery("#adminForm #import-items").click(function() {
			SqueezeBox.open("' . $importItemsPopUpUrl . '" ,{handler: "iframe", size: {x: window.innerWidth-250, y: window.innerHeight-150}});
			});
			});
			');

$document->addScript(Uri::root() . 'media/com_dpe/js/genericprocess.min.js');

$appendUrl = '';
$csrf = "&" . Session::getFormToken() . '=1';

if (!empty($this->created_by))
{
	$appendUrl .= "&created_by=" . $this->created_by;
}

if (!empty($this->client))
{
	$appendUrl .= "&client=" . $this->client;
}

$link = 'index.php?option=com_tjucm&view=items' . $appendUrl;
$itemId = $tjUcmFrontendHelper->getItemId($link);
$fieldsData = array();

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

		var copyItemData =  jQuery('#adminForm').serialize();

		// Code to copy item to ucm type
		com_tjucm.Services.Items.copyItem(copyItemData, afterCopyItem);
	}
	");

$statusColumnWidth = 0;

// Get Current url for notification manager widget
$extraParams = Uri::getInstance()->toString(array('query'));
$extraParams = str_replace('?', '&', $extraParams);
$jinput      = Factory::getApplication();

$currentUrl =  'index.php?option=' . $jinput->input->get('option') . '&view=' . $jinput->input->get('view') . '&client=' . $this->client . $extraParams .'&Itemid=' . $jinput->input->get('Itemid');
?>
<script>
	jQuery(document).ready(function(){
		if(sessionStorage.getItem('message'))
		{
			jQuery('#message').html('<div class="'+sessionStorage.getItem('class')+'"><a href="#" class="close" data-dismiss="alert">&times;</a>'+sessionStorage.getItem('message')+'</div>');
		}
		sessionStorage.removeItem("class");
		sessionStorage.removeItem("message");
	});
</script>
<style>
.centerloader {
	position: absolute;
	top: 0;
	bottom: 0;
	left: 0;
	right: 0;
	margin: auto;
}
#ropCopyLoader {
	border: 8px solid #22b8f0;
	border-radius: 50%;
	border-top: 8px solid #ccc;
	width: 50px;
	height: 50px;
	animation: spin 1s linear infinite;
}
</style>
<div id="ropCopyLoader" class="centerloader hide"></div>

<div class="tjucm-wrapper">
	<form action="<?php echo Route::_($link . '&Itemid=' . $itemId); ?>" enctype="multipart/form-data" method="post" name="adminForm" id="adminForm" class="form-validate">
		<div id="message" class=""></div>
		<?php echo $this->loadTemplate('filter'); ?>

		<?php
		if ($this->allowedToAdd)
		{
			?>
<!--
			<a href="<?php echo Route::_('index.php?option=com_tjucm&task=itemform.edit' . $appendUrl, false); ?>" class="btn btn-success btn-small">
				<i class="icon-plus"></i> <?php echo Text::_('COM_TJUCM_ADD_ITEM'); ?>
			</a>
		-->
		<?php
			/*
			if ($this->canImport)
			{
				?>
				<a href="#" id="import-items" class="btn btn-default btn-small">
					<i class="fa fa-upload"></i> <?php echo Text::_('COM_TJUCM_IMPORT_ITEM'); ?>
				</a>
				<?php
			}
			*/
			if ($this->canCopyItem)
			{
				if ($this->canCopyToSameUcmType)
					{?>

						<?php
					}
					?>
					<?php echo HTMLHelper::_(
						'bootstrap.renderModal',
						'collapseModal',
						array(
							'title'  => Text::_('COM_TJUCM_COPY_ITEMS'),
						),
						$this->loadTemplate('copyitems')
					); ?>
					<?php
				}
			}
			?>
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
			<div class="clearfix">&nbsp;</div>
			<div class="clearfix">&nbsp;</div>
			<div class="row">
				<div class="col-xs-12">
					<div class="table-responsive">
						<table class="table table-striped" id="itemList">
							<?php
							if (!empty($this->showList))
							{
								if (!empty($this->items))
									{?>
										<thead>
											<tr>
												<?php if ($this->canCopyItem) { ?>
													<!-- TODO- copy and copy to other feature is not fully stable hence relate buttons are hidden-->
													<th width="1%" class="">
														<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
													</th>
												<?php } ?>
												<?php
												if (isset($this->items[0]->state))
												{
													?>
<!--
							<th class="center" width="3%">
							<?php//  echo HTMLHelper::_('grid.sort', 'JPUBLISHED', 'a.state', $listDirn, $listOrder); ?>
							</th>
						-->
						<?php
					}
					?>
					<th width="2%">
						<?php echo HTMLHelper::_('grid.sort', 'COM_TJUCM_ITEMS_ID', 'a.id', $listDirn, $listOrder); ?>
					</th>

					<th>
						<?php echo HTMLHelper::_('grid.sort', 'COM_TJUCM_DATE_LOGGED', 'a.created_date', $listDirn, $listOrder); ?>
					</th>

					<?php
					if (!empty($this->ucmTypeParams->allow_draft_save) && $this->ucmTypeParams->allow_draft_save == 1)
					{
						$statusColumnWidth = 2;
						?>
						<th width="2%">
							<?php echo HTMLHelper::_('grid.sort', 'COM_TJUCM_DATA_STATUS', 'a.draft', $listDirn, $listOrder); ?>
						</th>
						<?php
					}

					foreach ($this->listcolumn as $fieldId => $col_name)
					{   


						if (isset($fieldsData[$fieldId]))
						{
							$tjFieldsFieldTable = $fieldsData[$fieldId];
						}
						else
						{
							Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
							$tjFieldsFieldTable = Table::getInstance('field', 'TjfieldsTable');
							$tjFieldsFieldTable->load($fieldId);
							$fieldsData[$fieldId] = $tjFieldsFieldTable;
						}

						$isCalendarField = 0;
						$isCalendarField = ($col_name->type == 'calendar') ? 1 : 0;

						if (in_array($col_name->type, $this->sortableFields) && (!$col_name->validation_class == 'riskdescription'))
						{
							?>
							<th width="<?php echo $isCalendarField ? 'width=11%' :(($col_name->validation_class == 'riskdescription')?1:(85 - $statusColumnWidth) / count($this->listcolumn)) . '%'?>">
								<?php echo HTMLHelper::_('grid.sort', htmlspecialchars($col_name->label, ENT_COMPAT, 'UTF-8'), $fieldId, $listDirn, $listOrder);?>
							</th>
							<?php
						}
						else
						{
							?>
							<th width="<?php echo $isCalendarField ? 'width=11%' :(($col_name->validation_class == 'riskdescription')?1:(85 - $statusColumnWidth) / count($this->listcolumn)) . '%'?>">
								<?php 	
								if (!$col_name->validation_class == 'riskdescription')
								{
									echo $col_name->label;
								} ?>
							</th>
							<?php
						}
					}
					?>
					<th class="center" width="10%">
						<?php echo Text::_('COM_TJUCM_ITEMS_ACTIONS'); ?>
					</th>
				</tr>
			</thead>
			<?php
		}
	}?>
	<tbody>
		<?php
		if (!empty($this->showList))
		{
			if (!empty($this->items))
			{
				$xmlFileName = explode(".", $this->client);
				$xmlFilePath = JPATH_SITE . "/administrator/components/com_tjucm/models/forms/" . $xmlFileName[1] . "_extra" . ".xml";
				$formXml = simplexml_load_file($xmlFilePath);

				$view = explode('.', $this->client);
				JLoader::import('components.com_tjucm.models.itemform', JPATH_SITE);
				$itemFormModel    = BaseDatabaseModel::getInstance('ItemForm', 'TjucmModel');
				$formObject = $itemFormModel->getFormExtra(
					array(
						"clientComponent" => 'com_tjucm',
						"client" => $this->client,
						"view" => $view[1],
						"layout" => 'edit')
				);

				foreach ($this->items as $i => $item)
				{
					// Call the JLayout to render the fields in the details view
					$layout = new FileLayout('list.genericprocesslist', JPATH_ROOT . '/components/com_tjucm/');
					echo $layout->render(
						array(
							'itemsData' => $item,
							'created_by' => $this->created_by,
							'client' => $this->client,
							'xmlFormObject' => $formXml,
							'ucmTypeId' => $this->ucmTypeId,
							'ucmTypeParams' => $this->ucmTypeParams,
							'fieldsData' => $fieldsData,
							'formObject' => $formObject,
							'statusColumnWidth' => $statusColumnWidth,
							'listcolumn' => $this->listcolumn
						)
					);
				}
			}
			else
			{
				?>
				<div class="alert alert-warning"><?php echo Text::_('COM_TJUCM_NO_DATA_FOUND');?></div>
				<?php
			}
		}
		else
		{
			?>
			<div class="alert alert-warning"><?php echo Text::_('COM_TJUCM_NO_DATA_FOUND');?></div>
			<?php
		}
		?>
	</tbody>
</table>
</div>
<?php
if (!empty($this->items))
{
	?>
	<div class="pager" id="pagination">
		<?php echo $this->pagination->getPagesLinks(); ?>
		<hr class="hr hr-condensed"/>
	</div>
	<?php
}
?>
</div>
</div>
<?php
if ($this->allowedToAdd)
{
	?>
<!--
		<a href="<?php echo Route::_('index.php?option=com_tjucm&task=itemform.edit' . $appendUrl, false); ?>"
		class="btn btn-success btn-small">
			<i class="icon-plus"></i>
			<?php echo Text::_('COM_TJUCM_ADD_ITEM'); ?>
		</a>
	-->
	<?php
}
?>
<input type="hidden" id="client" name="client" value="<?php echo $this->client ?>"/>
<input type="hidden" name="boxchecked" value="0"/>
<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>

<!-- DPE Hack start to add hidden fields to create content for notification manager -->
<input type="hidden" name="url" id="url" value="<?php echo $currentUrl;?>"/>
<input type="hidden" name="element" id="element" value="<?php echo $this->client;?>"/>
<input type="hidden" name="element_id" id="element_id" value="<?php echo $this->ucmTypeId;?>"/>
<input type="hidden" name="cluster_id" id="cluster_id" value=""/>
<input type="hidden" name="filter_process" id='filter_process' value="<?php echo $this->state->get('filter.process', "STRING"); ?>"/>
<input type="hidden" name="filter_genericlist1" id='filter_genericlist1' value="1"/>

<!-- DPE Hack end -->

<?php echo HTMLHelper::_('form.token'); ?>
</form>
</div>

<script type="text/javascript">

// <!-- Following js added to set cluster dropdown value to hidden 'cluster_id' field -->
if (jQuery('#filter_agencies').val() != "all")
{
	jQuery('#cluster_id').val(jQuery('#cluster').val());
}

jQuery(document).ready(function () {
	jQuery('.delete-button').click(deleteItem);
});

function deleteItem()
{
	if (!confirm("<?php echo Text::_('COM_TJUCM_DELETE_MESSAGE'); ?>"))
	{
		return false;
	}
}
</script>
<script type="text/javascript">
	var menuItemId = "<?php echo $itemId;?>";
	tjucm.itmes.init();
</script>

<script type="text/javascript">
	function ValidateRecordList() {

		if (jQuery('input[name="cid[]"]:checked').length < 1)
		{
			return false;
		}
		else
		{
			return true;
		}
	}
</script>

<script type="text/javascript">
	var tablewidth = jQuery('.genericdescription').position();
	var descriptionwidth = jQuery(".table").find("td:eq(1)").position();
  //jQuery('.genericdescription').css('margin-left',"-"+ (tablewidth.left+18) +"px");
</script>

<script type="text/javascript">
	jQuery(document).ready(function () {
		jQuery('th a[onclick^="Joomla.tableOrdering"]').attr('onchange', 'addGenericValue();');

	});

	function addGenericValue() 
	{

		var ischecked= jQuery('#recordProcessCheck').is(':checked');

		if(!ischecked)
		{
			jQuery('#filter_process').val('myprocess');
		}
		else
		{
			jQuery('#filter_process').val('generic');

		}
		


	};

</script>
