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
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;


$document = Factory::getDocument();
$document->addScript(Uri::root() . 'media/com_dpe/js/tjucm.js');

$tmpListColumn = $this->listcolumn;
reset($tmpListColumn);
$firstListColumn = key($tmpListColumn);

// Get generic filter update
$input         = Factory::getApplication()->input;
$coredata = $input->get('filter_coredata', '', 'string');
$selected = $this->state->get('filter.process', 'string');

$check = (($selected == 'generic') && ($coredata == 1)) ? 'checked' : '';

// Get Joomla session and input
$session = Factory::getSession();

$clusterID = $input->get('cluster') ?? ''; // Shorter ternary syntax

if($clusterID !=''){
	$session->set('selectedCluster', $clusterID);
}

// Get Process Addtion form Itemid
$menu   = Factory::getApplication()->getMenu();
$itemId = $menu->getActive()->id;
$createRopCopyLink = Route::_('index.php?option=com_dpe&view=ropform&tmpl=component&layout=coredatamultiplecopy&client=' . $this->client. '&Itemid=' . $itemId);
$user = Factory::getUser();

// Get UCM type Name
Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
$ucmTypeDetails = Table::getInstance('type', 'TjucmTable');
$ucmTypeDetails->load(array('unique_identifier' => $this->client));
?>
<div id="filter-progress-bar" class="row pb-30">
	<div class="col-md-8 col-sm-12 col-12">
				<div class="pull-left mb-20">
					<div class="pull-left">
						<input type="text" name="filter_search" id="filter_search" class="rounded-0"
							title="<?php echo empty($firstListColumn) ? Text::_('JSEARCH_FILTER') :
							Text::sprintf('COM_TJUCM_ITEMS_SEARCH_TITLE', $this->listcolumn[$firstListColumn]->label); ?>"
							value="<?php echo $this->escape($this->state->get($this->client . '.filter.search')); ?>"
							placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>"/>
					</div>
					<div class="pull-left btn-group me-2">
						<button class="btn btn-primary rounded-0" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><span class="icon-search"></span></button>
						<button class="btn btn-default qtc-hasTooltip rounded-0" id="clear-search-button" style="background-color: #efefef;" onclick="document.getElementById('filter_search').value='';this.form.submit();" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
							<!-- <span class="icon-remove"></span> -->
							<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>
						</button>
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
						<div class="btn-group me-2 md-w-200px pull-left mb-15">
							<?php
								echo HTMLHelper::_('select.genericlist', $this->clusterList, "cluster", 'class="input-medium" size="1" onchange="this.form.submit();"', "value", "text", $this->state->get($this->client . '.filter.cluster_id', '', 'INT'));
							?>
						</div>
						<?php
					}

			    $params     			   = ComponentHelper::getParams('com_multiagency');
				$orgAdminRoleId            = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
			    $orgAdminRoleId 		   = in_array($orgAdminRoleId, $user->groups);

			// Hide the Tags filter for non-Super Admin users
			if ($user->authorise('core.manageall', 'com_cluster') || $user->authorise('core.admin'))
			{ 

				FormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');
				$dpeTags = FormHelper::loadFieldType('Dpetags', false);
				$dpeTag= $dpeTags->getOptions(); 

				if($orgAdminRoleId)
				{
					JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
					$dpeModel = DPE::model('school', array('ignore_request' => true));
					$dpeTag = $dpeModel->getAgencyTags($orgAdminRoleId);
				}

			?>
					<div class="btn-group pull-left pe-4 mb-2 md-w-200px">
					<fieldset id="filter-bar">
					<div class="filter-select fltrt">
					<select name="filter_tags[]" id = "tags"class="inputbox" data-placeholder="<?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?>"multiple="multiple" onchange="this.form.submit()">
						<option value=""> <?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?> </option>
						<?php echo HTMLHelper::_('select.options', $dpeTag, 'value', 'text', $this->state->get('filter.tags'));?>
					</select>
				</div>
			</fieldset>
			</div><?php } 
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
				}

				// Load filter fields
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
						<div class="btn-group pull-left px-2 mb-10 md-w-200px">
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

			?>
			<div class="clearfix"></div>
			<div class="search-box ">
				
				<!--Generic Checkbox-->
				<div class="d-inline-block mr-20">
					<input class="" type="checkbox" name="recordProcessCheck"  id="recordProcessCheck" onchange="addGenericValue(); this.form.submit();" <?php echo $check;?> >
					<label for="ropProcessCheck" class="">
					<?php echo Text::sprintf('COM_TJUCM_ROP_GENERIC_TYPE_CHECKBOX_TITLE', $ucmTypeDetails->type_description);?>
					</label>
				</div>

				<div class="d-inline-block mr-20">
					<?php $alert = TEXT::_('COM_TJUCM_RPO_NOT_CHECKEDLIST_MSG')?>
					<a class=""href="javascript:void(0);"onclick="if(ValidateRecordList()){tjucm.itmes.openRopPopups('<?php echo addslashes(Route::_($createRopCopyLink . '&iscopy=1'));?>', this)}else{alert('<?php echo $alert;?>');}; "
						id="rop-make-copy"
						data-target-form="adminForm"
						title="<?php echo TEXT::_('COM_TJUCM_ROP_PROCESS_MAKE_COPY');?>" >
						<i class="fa fa-copy"></i><?php echo ' ' . TEXT::_('COM_TJUCM_ROP_PROCESS_MAKE_COPY')?>
					</a>
				</div>

				<div class="d-inline-block mr-20">
					<a class=""
						href="javascript:void(0);"
						onclick="deleteMultipleItems(this);"
						id="rop-delete"
						data-target-form="adminForm"
						title="<?php echo TEXT::_('COM_TJUCM_RECORD_DELETE_BTN_TEXT');?>" >
						<i class="fa fa-trash"></i><?php echo ' ' . TEXT::_('COM_TJUCM_RECORD_DELETE_BTN_TEXT')?>
					</a>
				</div>
			</div>
	</div>
	<div class="col-md-4 col-sm-12 col-12 text-end">
		<div class="btn-group hidden-xs d-inline-block ms-2">
			<?php echo $this->pagination->getLimitBox(); ?>
		</div>
		<div class="ms-2 mb-10 d-inline-block">
			<?php
				if ($this->canImport)
				{?>
					<a href="#" id="import-items" class="btn btn-primary btn-small import-records">
						<i class="fa fa-upload"></i> <?php echo Text::_('COM_TJUCM_IMPORT_ITEM'); ?>
					</a>
			<?php
				}
				?>
		</div>
		<div class="ms-2 mb-10 d-inline-block">
				<?php
				$tjUcmFrontendHelper = new TjucmHelpersTjucm;
				$itemId = $tjUcmFrontendHelper->getItemId('index.php?option=com_tjucm&view=itemform&client=' . $this->client);
				$masterUcmLink = Route::_('index.php?option=com_tjucm&view=itemform&Itemid=' . $itemId, false);
				if ($this->allowedToAdd)
				{?>
					<a href="<?php echo $masterUcmLink; ?>" class="btn btn-primary btn-small add-records">
						<i class="icon-plus"></i><?php echo Text::_('COM_TJUCM_ADD_ITEM'); ?>
					</a>
					<?php
				}if ($user->authorise('core.manageall', 'com_cluster') || $orgAdminRoleId)
					{   $dpeAdmin = $user->authorise('core.manageall', 'com_cluster');
					?>
					<a href="#" id="export-items" onclick="exportItems('<?php echo $dpeAdmin;?>');"class="btn btn-primary btn-small"style="padding: 9px;">
							<i class="fa fa-download"></i> <?php echo Text::_('COM_TJUCM_EXPORT_ITEM_PDF'); ?>
						</a>
					<?php }
				$multiagencyParams = ComponentHelper::getParams('com_multiagency');
				$orgAdminRoleId    = (int) $multiagencyParams->get('multiagency_school_admin_group', '0', 'INT');
			    $orgAdminRoleId 	 = in_array($orgAdminRoleId, $user->groups);
				?>
		</div>
	</div>

</div>

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
<script>
		jQuery(document).ready(function() {

			jQuery('#tags').change(function(e) {

				// check the tag filter is empty or not and set the tag filter
		        var selectData = jQuery("#tags").chosen().val();       	       
		        if ((selectData == null) || (selectData == ""))
		        {	
		        	jQuery("#tags").val(jQuery("#tags option:first").val());
		        }
	   		}); 
		// Check dpe admin
			var isDpeAdmin = "<?php echo $user->authorise('core.manageall', 'com_cluster'); ?>";
			var isorgAdmin = "<?php echo $orgAdminRoleId; ?>";


		if (!isDpeAdmin || !isorgAdmin)
			{
				jQuery('#filter_tags_chosen').hide();
			}

		jQuery('#tags').on('change', function() {
			jQuery("#cluster").val(jQuery("#cluster option:first").val());
	    });
	    jQuery('#cluster').on('change', function() {	
			jQuery("#tags").val(jQuery("#tags option:first").val());
	    });
})
 function deleteMultipleItems(element) {

			var elementForm = jQuery(element).data('target-form');
			var redirectUrl = jQuery('#ropForm').attr('action');
			var client      = jQuery('#client').val();
			var recordIds   = [];

			if ( jQuery( "input[name='cid[]']:checked").length > 0) {

					if (!confirm(Joomla.JText._('COM_TJUCM_DELETE_MESSAGE'))) {
						 return false;
					}
				}
				else
				{ 
					
                alert(Joomla.JText._('COM_TJUCM_NO_ITEM_SELECTED'));
				return false;
				}

			jQuery("#" + elementForm + " input[name='cid[]']").each(function() {

				if (jQuery(this).prop("checked") == true) {
					recordIds.push(jQuery(this).val());
				}
			});

            	if (recordIds.length >= 1)
            	{
            		jQuery("#ropCopyLoader").remove("hide");
				document.querySelector("#ropCopyLoader").classList.remove("hide"); // DPE Hack
				document.querySelector("#ropCopyLoader").style.bottom = '80%';
				// Update parent IDs
				jQuery.ajax({
					url: Joomla.getOptions("system.paths").root + "/index.php?option=com_dpe&task=tjucm.remove&format=json",
					type: "POST",
					data: {'recordIds':recordIds,'client':client},
					dataType: 'json',
					complete: function()
					{
						jQuery("#ropCopyLoader").addClass("hide");
					},
				}).done(function(data) {
					
					if (data && (data.success == true))
					{
							document.querySelector("#ropCopyLoader").classList.add("hide"); // DPE Hack
							Joomla.renderMessages({"success":[data.message]});


							var timmer = 1;

							setInterval(function()
							{
								timmer = (timmer - 1);

								if (timmer == 0)
								{
									recordIds.forEach(function(id) {
                            // Use the id to find elements and add CSS
                            var element =  document.querySelector('.row' + id);
                            if (element) {
                            	if (element) {
                            		element.parentNode.removeChild(element); 
                            	}

                            }
                        });
									// window.document.location.reload(true);
								}
							}, 2000);

						}
						else
						{

							Joomla.renderMessages(data.messages);
						}
					}).fail(function(result) {
					})
					.always(function() {
						// el.removeClass('btn-loading');
						document.querySelector("#ropCopyLoader").classList.add("hide"); // DPE Hack
					});
			}
		}
</script>
