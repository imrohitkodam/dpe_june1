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
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;

HTMLHelper::_('jquery.token');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('bootstrap.renderModal');

$noRecordClass = 'hide';

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models');

JLoader::import('field', JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
$tjfieldsTablefield = Table::getInstance('field', 'TjfieldsTable', array());

JLoader::import('clusters', JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
$clusterTableClusters = Table::getInstance('clusters', 'ClusterTable', array());

// Get document type id
Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
$typeInstance = Table::getInstance('type', 'TjucmTable');
$documentInstance = Table::getInstance('document', 'TjucmTable');
$typeInstance->load(array('unique_identifier' => $this->client));
$documentInstance->load(array('ucm_type' => $typeInstance->id, 'state' => 1));

// Get Documents of type single
BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/models');
$UCMDocumentsModel = BaseDatabaseModel::getInstance('Documents', 'TjucmModel', array('ignore_request' => true));
$UCMDocumentsModel->setState('filter.document_type', 0);
$UCMDocumentsModel->setState('filter.state', 1);
$documents = $UCMDocumentsModel->getItems();

$ucmTypeParams = json_decode($typeInstance->params);
$loggedUser = Factory::getUser();
$this->ucmTypeParams = json_decode($typeInstance->params);


// Check ROP is generic process and User not having permission to manage all cluster
if ($this->state->get('filter.process') == 'generic')
{
	if (!Factory::getUser()->authorise('core.manageall', 'com_cluster'))
	{
		$this->canEditOwn = false;
		$this->canDeleteOwn = false;
	}
}


// Is DPE Admin
$isDPEAdmin = 0;

if ($loggedUser->authorise('core.manageall', 'com_cluster'))
{
	$isDPEAdmin = 1;
}

$tjUcmFrontendHelper = new TjucmHelpersTjucm;

// DPE - Hack  - Start
JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
$params = DPE::config();
// DPE - Hack  - End
$statusColumnWidth = 0;
$fieldsData = array();

$user = Factory::getUser();
$canCopyItem        = $user->authorise('core.type.copyitem', 'com_tjucm.type.' . $ucmTypeId);
$app      = Factory::getApplication();

// Get Joomla session and input
$session = Factory::getSession();
$clusterID = $app->input->get('cluster') ?? ''; // Shorter ternary syntax

if($clusterID !=''){
	$session->set('selectedCluster', $clusterID);

}

?>

<script>
function openVisualizationPopup(url)
{
	var wwidth = jQuery(window).width() - 50;
	var wheight = jQuery(window).height() - 50;

	SqueezeBox.open(url, {
		handler: 'iframe',
		closable: true,
		size: {
			x: wwidth,
			y: wheight
        },
        classWindow: 'tjucm-rop-doc',
	});
}
function openDocument(url, UcmId)
{
	// Open Document and Reset selected dropdown
	//~ jQuery('#rop-documents-'+ UcmId).get(0).selectedIndex = 1;;
	openDocumentPopup(url);
}

function SortByDateOfReview(element)
{
	var accordian = element.closest(".rop-list-cover").attributes["data-accordian-id"].value;
	var form = jQuery('#ropBusinessFunctionForm' + accordian);
	form.find('[name=filter_order]').val(form.find('[name=nextReviewDateField]').val());
	var ordering = form.find('[name=filter_order_Dir]').val() == 'asc' ? 'desc' : 'asc';
	form.find('[name=filter_order_Dir]').val(ordering);
	var el = jQuery('#ropBusinessFunctionAccordian'+accordian);
	ucmRopLoadData(el);
}

function FilterByRequestStatus(element)
{ 
	var accordian = element.closest(".rop-list-cover").attributes["data-accordian-id"].value;
	var form = jQuery('#ropBusinessFunctionForm' + accordian);
	form.find('[name=request_status_field_value]').val(element.value);
	var el = jQuery('#ropBusinessFunctionAccordian'+accordian);
	ucmRopLoadData(el);
}

function FilterByRequestProcess(element)
{
	var accordian = element.closest(".rop-list-cover").attributes["data-accordian-id"].value;
	var form = jQuery('#ropBusinessFunctionForm' + accordian);
	form.find('[name=exisitng_process_field_value]').val(element.value);
	var el = jQuery('#ropBusinessFunctionAccordian'+accordian);
	ucmRopLoadData(el);
}


</script>
<div class="scroll1">
	<div class="div1">
	Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type
	</div>
</div>
<div class="scroll12">
	<div class="div2">
			<table class="table table-responsive">
				<thead>
					<tr>
						<?php
						if (!empty($this->listcolumn))
						{
							?>
							<?php // if ($canCopyItem) { ?>
							<!-- TODO- copy and copy to other feature is not fully stable hence relate buttons are hidden-->
							<th width="1%" class="checkbox-header">
								<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
							</th>
							<?php //  } ?>

							<!-- <?php
							if (!empty($this->ucmTypeParams->allow_draft_save) && $this->ucmTypeParams->allow_draft_save == 1)
							{
								$statusColumnWidth = 2;
							?>
								<th width="2%">
									<?php
									// echo HTMLHelper::_('grid.sort', 'COM_TJUCM_DATA_STATUS', 'a.draft', $listDirn, $listOrder);
									?>
									<?php //echo TEXT::_('COM_TJUCM_DATA_STATUS'); ?>
								</th> -->
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

								switch($tjFieldsFieldTable->name)
								{
									case 'com_tjucm_rop_dateofnextreview':
											?>
										<th class="center">
											<a href="#" onclick="loadpreloader(); SortByDateOfReview(this);" >
											<?php echo $col_name->label; ?>
											<span class="date-of-review icon-arrow-up-3"></span>
											</a>
										</th>
											<?php
									break;
									case 'com_tjucm_rop_status':
										$tjFieldsOptionsModel = BaseDatabaseModel::getInstance('Options', 'TjfieldsModel', array('ignore_request' => true));
										$tjFieldsOptionsModel->setState('filter.field_id', $fieldId);
										$options = $tjFieldsOptionsModel->getItems();

										if (!empty($options))
										{
											$defaultOption = new stdclass;
											$defaultOption->value = "";
											$defaultOption->options = Text::_("JSELECT") . ' Status';

											$options = array_merge(array($defaultOption), $options);
										}
										?>
										<th width="15%" class="center">
											<?php echo $col_name->label; ?>
											<?php
												echo HTMLHelper::_(
													'select.genericlist', $options, $field->name, 'class="input-medium rop-request-status"
													size="1" onchange="loadpreloader(); FilterByRequestStatus(this);"', "value", "options",
													$this->state->get('filter.field.' . $field->name)
												);
											?>
										</th>
										<?php
										break;
										case 'com_tjucm_rop_neworexisting':

										$tjFieldsOptionsModel = BaseDatabaseModel::getInstance('Options', 'TjfieldsModel', array('ignore_request' => true));
										$tjFieldsOptionsModel->setState('filter.field_id', $fieldId);
										$options = $tjFieldsOptionsModel->getItems();

										if (!empty($options))
										{
											$defaultOption = new stdclass;
											$defaultOption->value = "";
											$defaultOption->options = Text::_("COM_TJUCM_NEWEXISTING_PROCESS");

											$options = array_merge(array($defaultOption), $options);
										}
										?>
										<th width="15%" class="center">
											<?php echo $col_name->label; ?>
											<?php

												echo HTMLHelper::_(

													'select.genericlist', $options, $field->name, 'class="input-medium rop-process-status"
													size="1" onchange="loadpreloader(); FilterByRequestProcess(this);"', "value", "options",
													$this->state->get('filter.field.' . $tjFieldsFieldTable->name)
												);
											?>
										</th>

								    <?php break;
								     default:
										?>
										<th class="center">
											<?php echo $col_name->label; ?>
										</th>
										<?php
								}
							}

							// Show edit only user when he seen own created process only
							if ($this->canEditOwn || $this->canDeleteOwn)
							{
								?>
								<!-- <th class="hidden-phone center" width="10%">
									<?php //echo TEXT::_('COM_TJUCM_ROP_PROCESS_EDIT_RECORD'); ?>
								</th> -->
								<?php
							}
						}
						?>
								<th class="hidden-phone center" width="10%">
									<?php echo TEXT::_('COM_TJUCM_ROP_VISUALISATION_TITLE'); ?>
								</th>

								<!-- Condition is not added to show document heading, because dpeadmin, org manager, org admin will have access for document  -->
								<th class="hidden-phone center" width="10%">
									<?php echo TEXT::_('COM_TJUCM_ROP_DOCUMENT_COL_TITLE'); ?>
								</th>

					</tr>
				</thead>
				<tbody>
				<?php
				if (!empty($this->items))
				{
				?>
					<?php
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

					foreach($this->items as $item)
					{
						$editRecordLink = 'index.php?option=com_tjucm&task=itemform.edit&id=' . $item->id . '&client=' . $this->client . '&cluster_id=' . $item->cluster_id;

						// Check document generate permission
						$isDocumentGenerate = true;

						if (!$loggedUser->authorise('core.manageall', 'com_cluster'))
						{
							$isDocumentGenerate = RBACL::check($loggedUser->id, 'com_cluster', 'document.generate', 'com_multiagency', $item->cluster_id);
						}
						?>
						<?php

							// Call the JLayout to render the fields in the details view
							$layout = new FileLayout('list.rops_list', JPATH_ROOT . '/components/com_tjucm/');
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
									'listcolumn' => $this->listcolumn,
									'documents' => $documents
								)
							);
					}
				?>

				<?php
				}
				else
				{
					$noRecordClass = '';
				}
				?>

				</tbody>
			</table>

	</div>
</div>

<div class="alert alert-warning mt-5 no-items-result <?php echo $noRecordClass; ?>">
	<?php echo Text::_('COM_DPE_NO_DATA_FOUND'); ?>
</div>

<script>
jQuery(document).ready(function(){

 jQuery(".scroll1").scroll(function(){
    jQuery(".scroll12").scrollLeft(jQuery(".scroll1").scrollLeft());
  });

  jQuery(".scroll12").scroll(function(){
    jQuery(".scroll1").scrollLeft(jQuery(".scroll12").scrollLeft());
  });

});
</script>


