<?php
/**
 * @package     UCM
 * @subpackage  module-ucm
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;

$document = Factory::getDocument();
$path     = Uri::base() . 'modules/mod_ucm_data/assets/css/ucmdata.css';
$document->addStyleSheet($path);
$document->addScriptDeclaration('const site_root = "' . Uri::root() . '"');
HTMLHelper::script('modules/mod_ucm_data/assets/js/ucmajaxlist.min.js');

$appendUrl = "&client=" . $itemsData['client'];
$fieldsData = array();

Text::script('MOD_UCM_DATA_NO_DATA_AVAILABLE_LABEL');

$app  = Factory::getApplication();
$user = Factory::getUser();



// Check user have permission to view records of assigned cluster
		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

			// Check user has permission for mentioned cluster
			if (!RBACL::check($user->id, 'com_cluster', 'core.viewitemlist.' . $params->get('ucmtypename'), 'com_tjucm'))
			{
				// Provide access on ucm list view if user is assignee
				$mainHelper = JPATH_SITE . '/components/com_dpe/helpers/main.php';
				JLoader::register('DpeMainHelper', $mainHelper);

				$dpeMainHelper = new DpeMainHelper;
				$assignedUsers = $dpeMainHelper->getFieldValues($user->id, null, $itemsData['client']);

				if (empty($assignedUsers))
				{
					echo Text::_('JERROR_ALERTNOAUTHOR');
					return false;
				}
			}
		}elseif($itemsData['total'] == 0)
{
	echo Text::_('MOD_UCM_DATA_NO_DATA_AVAILABLE_LABEL');
	return false;
}

?>
<style>
	.loader-center{
		    display: block;
    margin-left: auto;
    margin-right: auto;
    width: 8%;
	}
</style>
<div class="ucm-list-cover">
	<div class="row">
		<div class="col-xs-12">
			<?php  if ($module->showtitle && $module->showtitle): ?>
					<h2 ><?php echo $module->title;?></h2>
			<?php endif; ?>
		</div>
		<div class="col-xs-12 bb-gray py-20">
			<div class="row" id="ucmListModule">
			<div id="filter-progress-bar" class="ml-15 ">
				<div class="btn-group pull-left mb-2 me-2">
				<div class="pull-left rounded-0 valid form-control-success">
					<input type="text" name="filter_search" id="filter_search"
						title="<?php echo Text::_('MOD_UCM_ITEMS_SEARCH_TITLE'); ?>"
						value=""
						placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>"/>
				</div>
				<div class="pull-left btn-group">
					<button class="btn btn-primary rounded-0" onclick="applyFilters(this);" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><span class="icon-search"></span></button>
					<button class="btn btn-default qtc-hasTooltip" id="clear-search-button" onclick="document.getElementById('filter_search').value=''; applyFilters(this);" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">clear</button>
				</div>
				</div>
			<?php
				// Check if com_cluster component is installed
				if (ComponentHelper::getComponent('com_cluster', true)->enabled)
				{
					JLoader::import('components.com_tjfields.tables.field', JPATH_ADMINISTRATOR);
					$fieldTable = Table::getInstance('Field', 'TjfieldsTable', array('dbo', $db));
					$fieldTable->load(array('client' => $itemsData['client'], 'type' => 'cluster'));

					if (property_exists($fieldTable, 'id') && $fieldTable->id)
					{
						FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
						$cluster           = FormHelper::loadFieldType('cluster', false);
						$clusterList = $cluster->getOptionsExternally();
						?>
						<div class="btn-group ml-10 md-w-200px pull-left">
							<?php
								echo HTMLHelper::_('select.genericlist', $clusterList, "cluster", 'class="input-medium" size="1" onchange="applyFilters(this);"', "value", "text");
							?>
						</div>
						<?php
					}
				}
			?>
			<?php
			if ($params->get('addRecord') == 1)
			{
				?>
				<div class="">
					<a href="<?php echo Route::_('index.php?option=com_tjucm&task=itemform.edit' . $appendUrl, false); ?>"
						class="btn btn-primary btn-small pull-right mr-15" target="_blank">
						<i class="icon-plus"></i>
						<?php echo Text::_('MOD_UCM_DATA_ADD_RECORD_BUTTON_LABEL'); ?>
					</a>
				</div>
				<?php
			}
			?>
			</div>
				<div class="col-sm-12 no-more-tables" id="tjucm_items_list_cover">
				<div id="no_data_ucmlist">
					<?php echo Text::_('MOD_UCM_DATA_NO_DATA_AVAILABLE_LABEL');?>
				</div>
					<div id="ajax_loader_ucm" class="loader-center">
			    <div id="loading-indicator">
			        <img src="<?php echo Uri::root().'media/com_tjcertificate/images/loader/loader.gif';?> " alt="Loading">
			    </div>
			</div>
					<table class="table items-list" id="tjucm_items_list_table">
						<thead id="tjucm_items_list_head">
							<tr>
								<?php

									if($params->get('showEditButton') == 1)
									{?>
										<th>
										<?php echo Text::_('MOD_TJUCM_ITEMS_ID');?>
										</th>
									<?php }
								foreach ($columnsToShow as $fieldId => $col_name)
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
									?>
									<th>
										<?php echo $col_name->label; ?>
									</th>
									<?php
								}
								if($params->get('showEditButton') == 1)
									{?>
										<th>
										<?php echo Text::_('Actions');?>
										</th>
									<?php }
								?>
							</tr>
						</thead>
						<tbody id="tjucm_items_list_body">
							<?php

							if (!empty($itemsData['items']))
							{
								foreach ($itemsData['items'] as $item)
								{
									echo LayoutHelper::render('default_list', array('item' => $item, 'param'=>$params->get('showEditButton'),'fieldsData' => $fieldsData, 'columnsToShow' => $columnsToShow, 'ucmType' => $params->get('ucmtypename')), JPATH_SITE . '/' . 'modules/mod_ucm_data/tmpl');
								}
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="ucm-list-footer">
				<div class="text-center">
					<span class="font-600 text-center" id="ucm_list_counter"><?php echo ($itemsData['total']<$params->get('limit'))?$itemsData['total']:$params->get('limit');echo ' / '.$itemsData['total']; ?></span>
				</div>
				<div class="text-center">
				<?php  if (!empty($itemsData['items']) && ($itemsData['total'] > $params->get('limit'))) { ?>
					<button id="btn_showMore" class="btn btn-info btn-md" type="button" onclick="loadMore()">
						<?php echo Text::_('MOD_UCM_DATA_LOAD_MORE_BUTTON_LABEL'); ?>
					</button>
				<?php } ?>
				</div>
			</div>
		
			<input type="hidden" name="view" id="view" value="items" />
			<input type="hidden" name="editparam" id="editparam" value="<?php echo $params->get('showEditButton');?>" />
			<input type="hidden" name="total" id="total" value="<?php echo $itemsData['total'];?>" />
			<input type="hidden" name="cluster_id" id="cluster_id" value="0" />
			<input type="hidden" name="client" id="client" value="<?php echo $itemsData['client'];?>"/>
			<input type="hidden" name="typeId" id="typeId" value="<?php echo $params->get('ucmtypename');?>"/>
			<input type="hidden" id="paginationIndex" value="<?php echo $params->get('limit'); ?>" />
			<input type="hidden" id="limit" value="<?php echo $params->get('limit'); ?>" />
			<input type="hidden" id="ucmfields" name="ucmfields[]" value="<?php echo base64_encode(json_encode($params->get('ucmfieldname'))); ?>" />
		</div>
	</div>
</div>
