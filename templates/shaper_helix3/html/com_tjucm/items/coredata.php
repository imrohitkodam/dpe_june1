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
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Layout\FileLayout;


HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('bootstrap.renderModal');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('jquery.token');

Text::script('COM_TJUCM_DELETE_MESSAGE');
Text::script('COM_TJUCM_NO_ITEM_SELECTED');

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

$document->addScript(Uri::root() . 'media/com_dpe/js/tjucmroplist.js');
$document->addScript(Uri::root() . 'media/system/js/messages.min.js');

$user = Factory::getUser();
$userId = $user->get('id');
$tjUcmFrontendHelper = new TjucmHelpersTjucm;
$listOrder  = $this->escape($this->state->get('list.ordering'));
$listDirn   = $this->escape($this->state->get('list.direction'));
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

$document->addScriptDeclaration("
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
 <script>
    jQuery(document).ready(function () {
        var isChecked = jQuery('#recordProcessCheck').is(':checked');

        if (isChecked) {
            var menuItemId = "<?php echo $itemId; ?>";
            var baseUrl = "<?php echo Route::_($link . '&Itemid=' . $itemId); ?>";
            var currentUrl = window.location.href;

            // Redirect only if filter is not already in the URL
            if (!currentUrl.includes('filter_process=generic')) {
                // Uncomment this if you want an automatic redirect
                // window.location = baseUrl + '?filter_process=generic&Itemid=' + menuItemId;
            }

            // Update pagination links with extra parameters
            jQuery('.page-link').each(function () {
                var originalHref = jQuery(this).attr('href');

                if (originalHref) {
                    // Choose correct query parameter separator
                    var separator = originalHref.includes('?') ? '&' : '?';

                    // Avoid duplicating parameters
                    if (!originalHref.includes('filter_process=generic')) {
                        var updatedHref = originalHref + separator + 'filter_process=generic&filter_coredata=1';
                        jQuery(this).attr('href', updatedHref);
                    }
                }
            });
        }
    });
</script>


<script>
jQuery(document).ready(function() {
	var menuItemId = "<?php echo $itemId;?>";

	jQuery("#ropProcessCheck").click(function() {
		var url = "<?php echo Route::_($link . '&Itemid=' . $itemId); ?>"

		if (jQuery(this).prop("checked") == true) {
			window.location = url + '?filter_process=generic&Itemid=' + menuItemId;

		} else {
			window.location = url;
		}
	});
});
</script>
<style>
.centerloader {
	position: absolute;
	left: 0;
	right: 0;
	margin: auto;
	z-index: 1;
	bottom:650px;
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
<div class="tjucm-wrapper vendors-view">
	<form action="<?php echo Route::_($link . '&Itemid=' . $itemId); ?>" enctype="multipart/form-data" method="post" name="adminForm" id="adminForm" class="form-validate mt-30">
		<div id="ropCopyLoader" class="centerloader hide"></div>
		<div class="row vendors-progress-bar">
			<div class="">
				<div class="col-md-offset-2">
					<?php
						echo $this->loadTemplate('coredata_filter');
					?>
				</div>
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
			<?php }?>

		<div id="message" class=""></div>
		<?php echo HTMLHelper::_(
							'bootstrap.renderModal',
							'collapseModal',
							array(
								'title'  => Text::_('COM_TJUCM_COPY_ITEMS'),
							),
							$this->loadTemplate('copyitems')
							);
		?>

		<div class="row d-flex flex-wrap mt-20">
			<div class="col-md-2 col-sm-2 col-xs-12 side-navigation mb-20">
				<?php echo HTMLHelper::_('content.prepare', '{loadposition vendors-position}'); ?>
			</div>
				<div class="table-responsive col-md-10 col-sm-10 col-xs-12">
					<table class="table" id="itemList">
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
										<th>
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
											<th>
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

											if (in_array($col_name->type, $this->sortableFields))
											{
												?>
												<th width="<?php //echo $isCalendarField ? 'width=11%' :(85 - $statusColumnWidth) / count($this->listcolumn) . '%'?>">
													<?php echo HTMLHelper::_('grid.sort', htmlspecialchars($col_name->label, ENT_COMPAT, 'UTF-8'), $fieldId, $listDirn, $listOrder); ?>
												</th>
												<?php
											}
											else
											{
												?>
												<th width="<?php //echo $isCalendarField ? 'width=11%' :(85 - $statusColumnWidth) / count($this->listcolumn) . '%'?>">
													<?php echo $col_name->label; ?>
												</th>
												<?php
											}
										}
										?>
										<th class="center">
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
									$layout = new FileLayout('list.coredata_list', JPATH_ROOT . '/components/com_tjucm/');
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
		</div>
		<?php
				if (!empty($this->items))
				{
					?>
					<div class="pager" id="pagination">
						<?php echo $this->pagination->getPagesLinks(); ?>
						<!-- <hr class="hr hr-condensed"/> -->
					</div>
					<?php
				}
		?>
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
		<input type="hidden" name="filter_process" id='filter_process' value="<?php echo $this->state->get('filter.process', "STRING"); ?>"/>
		<input type="hidden" name="filter_coredata" id='filter_coredata' value="1"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>

<script type="text/javascript">
jQuery(document).ready(function () {
	jQuery('.delete-button').click(deleteItem);

	            jQuery('th a[onclick^="Joomla.tableOrdering"]').attr('onchange', 'addGenericValue();');

});

jQuery(document).ready(function(){
			var ischecked= jQuery('#recordProcessCheck').is(':checked');
			if(!ischecked)
			{
				          	    jQuery('.page-link').each(function () {
                var originalHref = jQuery(this).attr('href');
                var clusterId = jQuery('#cluster').val();
                if (originalHref) {
                    // Choose correct query parameter separator
                    var separator = originalHref.includes('?') ? '&' : '?';
                    // Avoid duplicating parameters
                        var updatedHref = originalHref + separator + 'cluster='+clusterId;
                        jQuery(this).attr('href', updatedHref);
                }
            });
			}
          })


function deleteItem()
{
	if (!confirm("<?php echo Text::_('COM_TJUCM_DELETE_MESSAGE'); ?>"))
	{
		return false;
	}
}
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
          jQuery('#cluster').on('change', function(){

        	if (jQuery('#recordProcessCheck').is(':checked'))
        	{
        		jQuery('#recordProcessCheck').prop('checked', false);
        		addGenericValue();
       		}
        }) 
			
			jQuery('#tags').on('change', function(){

        	if (jQuery('#recordProcessCheck').is(':checked'))
        	{
        		jQuery('#recordProcessCheck').prop('checked', false);
        		addGenericValue();
       		}


        }) 
          
          jQuery(document).ready(function(){
			var ischecked= jQuery('#recordProcessCheck').is(':checked');
			if(!ischecked)
			{
				          	    jQuery('.page-link').each(function () {
                var originalHref = jQuery(this).attr('href');
                var clusterId = jQuery('#cluster').val();
                if (originalHref) {
                    // Choose correct query parameter separator
                    var separator = originalHref.includes('?') ? '&' : '?';

                    // Avoid duplicating parameters
                        var updatedHref = originalHref + separator + 'cluster='+clusterId;
                        jQuery(this).attr('href', updatedHref);
                    
                }
            });
			}

          })
</script>

   