<?php
/**
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('jquery.token');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('bootstrap.renderModal');

$document = Factory::getDocument();
$document->addScript(Uri::root() . 'media/com_dpe/js/tjucmcoredatalist.js');

$input      = Factory::getApplication()->input;
$cluster_id = $input->getString('cluster_id', 0,'INT');
$tmpl       = $input->getString('tmpl', '');
$popupClass = '';

// Get Cluster name

if ($cluster_id)
{
	Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
	$clusterTable = Table::getInstance('clusters', 'ClusterTable');
	$clusterTable->load(array('id' => $cluster_id));
}

// Get UCM Type name
Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
$ucmTable = Table::getInstance('type', 'TjucmTable');
$ucmTable->load(array('unique_identifier' => $this->client));

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
}

$tjUcmFrontendHelper = new TjucmHelpersTjucm;
$itemId = $tjUcmFrontendHelper->getItemId('index.php?option=com_tjucm&view=itemform&client=' . $this->client);

// Get Request status field ID
$softwareManagedby = $input->get('softwareManagedby', 0, 'INT');

JLoader::import('field', JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
$softwareManagedbyField = Table::getInstance('field', 'TjfieldsTable', array());
$softwareManagedbyField->load(array('name' => 'com_tjucm_software_Managedby'));
?>
<div class="container-fluid px-0">
	<div id="panelBody" >
		<div class="timelog-add-form activity-edit front-end-edit ml-15 mr-20 jlike-timelog">
			<h2 class="activity-header fs-20"><?php echo Text::sprintf('COM_DPE_REVERSELIST_TITLE', $ucmTable->title, $clusterTable->name); ?></h2>
		</div>
		<form action="" method="post" name="ropBusinessFunctionForm" id="ropBusinessFunctionForm" class="ucm-form-styling ropBusinessFunctionForm <?php echo $popupClass;?>" onsubmit="return false;">
			<?php echo HTMLHelper::_('form.token'); ?>
			<!--Filter Div-->

			<div class="col-md-12 col-xs-12 col-sm-12 mt-10 px-0 ">
				<div class="no-more-tables overflow-x ucm-loadmore-tab-content" id="ropProcessList">
				</div>
			</div>

			<input type="hidden"id="option" name="option" value="com_tjucm" />
			<input type="hidden" name="view" value="items" />
			<input type="hidden" id="controller" name="controller" value="items" />
			<input type="hidden" id="task" name="task" value="items.displayCoreData" />
			<input type="hidden" name="total" value="" />
			<input type="hidden" name="client" value="<?php echo $this->client;?>"/>
			<input type="hidden" name="typeId" value="<?php echo $this->ucmTypeId;?>"/>
			<input type="hidden" name="limit" value="<?php echo $listLimit; ?>" />
			<input type="hidden" name="limitstart" value="0" />
			<input type="hidden" name="loaded" value="false" />
			<input type="hidden" name="format" value="json" />
			<input type="hidden" name="filter_order" value="<?php echo $this->escape($this->state->get('list.ordering')); ?>" />
			<input type="hidden" name="filter_order_Dir" value="<?php echo $this->escape($this->state->get('list.direction')); ?>" />
			<input type="hidden" name="filter[cluster_id]" value="<?php echo $cluster_id; ?>" />
			<input type="hidden" id="coreDataProcess" name="filter[process]" value="" />
			<input type="hidden" id="ucmdataid" name="ucmdataid" value="" />
			<input type="hidden" id="clutername" name="clutername" value="" />
			<input type="hidden" id="ropBusinessFunctionAccordian" data-target-form="ropBusinessFunctionForm" name="ropBusinessFunctionAccordian" />

			<?php if ($this->client == 'com_tjucm.software') : ?>
			<input type="hidden" name="customeFieldValue" value="<?php echo $softwareManagedby; ?>"/>
			<input type="hidden" name="customeFieldId" value="<?php echo $softwareManagedbyField->id; ?>"/>
			<?php endif; ?>

			<div class="text-center">
				<span class="font-600 text-center" id="recordcounter">
				</span>
			</div>
		</form>
	</div>
    <!-- Panel body cover end-->
</div>
<script>
var menuItemId = "<?php echo $itemId;?>";
tjucm.itmes.init();
	var el = jQuery("#ropBusinessFunctionAccordian");
	var form = jQuery('#' + el.data('target-form'));
	ucmRopLoadData(el);
</script>
