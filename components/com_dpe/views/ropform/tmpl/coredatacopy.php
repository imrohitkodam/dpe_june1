<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\ComponentHelper;

$document = Factory::getDocument();
$document->addScript(Uri::root() . 'media/com_dpe/js/tjucmroplist.js');
$document->addScript(Uri::root() . 'media/system/js/messages.min.js');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$document->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
$document->addStyleSheet('templates/shaper_helix3/css/custom.css');

$app           = Factory::getApplication();
$recordId      = $app->input->getInt('ucmdataid', 0);
$ucmDataIdCopy = $app->input->getInt('ucmdataidcopy', 0);
$clutername    = $app->input->get('clutername','','RAW');
$recordIdsCommaSeparted = trim(preg_replace("/[^0-9,]/", "", json_encode($ucmDataIdCopy)));

Text::script('COM_DPE_SELECT_FIELDSET_FOR_COPY');


// Construct ROP link
$link = 'index.php?option=com_tjucm&view=items&client=com_tjucm.rop';
JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
$tjucmHelper        = new TjucmHelpersTjucm;
$itemId             = $tjucmHelper->getItemId($link);
$ropCopyRedirectUrl = Route::_($link . $clusterFilter . '&Itemid=' . $itemId, false);

// Get cluster ID from record ID
Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
$ucmTable = Table::getInstance('item', 'TjucmTable');
$ucmTable->load(array('id' => $recordId));

// Get client ID
$ucmTableCopy = Table::getInstance('item', 'TjucmTable');
$ucmTableCopy->load(array('id' => $ucmDataIdCopy));

if (property_exists($ucmTable, 'cluster_id'))
{
	$clusterId = $ucmTable->cluster_id;
}

if (property_exists($ucmTableCopy, 'cluster_id'))
{
	$client    = $ucmTableCopy->client;
}

// Get field groups
JLoader::import('components.com_tjfields.models.groups', JPATH_ADMINISTRATOR);
$fieldGroupsModel = BaseDatabaseModel::getInstance('groups', 'TjfieldsModel', array('ignore_request' => true));
$fieldGroupsModel->setState('filter.state', 1);
$fieldGroupsModel->setState('filter.client', $client);
$fieldGroups = $fieldGroupsModel->getItems();


$params              = ComponentHelper::getParams('com_dpe');
$codeDataFieldConfig = json_decode($params->get('coredatatitlefields'), true);

if (!empty($client) && array_key_exists($client, $codeDataFieldConfig))
{
	$fieldUniqueName = $codeDataFieldConfig[$client];
}

Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');

// Get Record title
$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');
$tjFieldFieldTable->load(array('client' => $client, 'name' => $fieldUniqueName));

if (property_exists($tjFieldFieldTable, 'id'))
{
	$tjFieldFieldValueTable = Table::getInstance('fieldsvalue', 'TjfieldsTable');
	$tjFieldFieldValueTable->load(array('field_id' => $tjFieldFieldTable->id, 'content_id' => $ucmDataIdCopy));

	if (property_exists($tjFieldFieldValueTable, 'value'))
	{
		$recordTitle = $tjFieldFieldValueTable->value;
	}
}

?>
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
<script>
<?php if (count($fieldGroups) > 1): ?>
jQuery(document).ready(function()
{
	jQuery(".fieldgroup").change(function()
	{
		var fieldGroupValues = [];

		jQuery('input[type="checkbox"]:checked').each(function()
		{
			fieldGroupValues.push(this.value);
		});

		jQuery('#fieldGroupValues').val(fieldGroupValues.join());
	});
});
<?php endif; ?>
</script>
<div id="system-message-container"></div>
<div id="ropCopyLoader" class="centerloader hide"></div>
<h3 class="rop-popup-header ml-20 mr-20"><?php echo Text::_('CORE_DATA_DUPLICATE_POPUP_HEADING'); ?></h3>
<div id="ropCopypopCover">
	<div id="countermsg" class="alert alert-info d-none"></div>
	<div class="rop-popup-from mt-20 ml-20 ucm-form-styling">
		<div class="control-group">
			<div class="controls mt-30">
				<label for="cname"><?php echo Text::_('CORE_DATA_DUPLICATE_POPUP_ORG_NAME_LABEL'); ?></label>
				<input type="text" class="ucm-full-width" id="recordTitle" name="orgname" value="<?php echo $recordTitle; ?>"><br><br>
				<?php if (count($fieldGroups) > 1): ?>
						<Strong><?php echo Text::_('CORE_DATA_DUPLICATE_POPUP_CHECKBOX_LABEL'); ?></Strong>
						<br>
						<?php foreach($fieldGroups as $fieldGroup): ?>
							<label><input class="fieldgroup" type="checkbox" value="<?php echo $fieldGroup->id;?>" name="fieldgroup"><span class="ml-10"><?php echo ucwords(trim($fieldGroup->title));?><span>
							</label>
							<br>
						<?php endforeach; ?>
						<br>
				<?php endif; ?>
				<div class="popup-btns">
				<button class="btn rop-copy  mb-20 mr-20 pull-right" onclick="jQuery('#item-form #tjucm_loader').show(); tjucm.itmes.copyItemCoreData();">
					<i class="fa fa-clone mr-5"></i><?php echo Text::_('COM_TJUCM_ROP_PROCESS_DUPLICATE_TITLE'); ?>
				</button>
				</div>
			</div>
			<?php echo "<input name='recordIds' id='recordIds' type='hidden' value='" . $recordIdsCommaSeparted . "' />"; ?>

			<input type="hidden" id="clusterId" name="clusterId" value="<?php echo $clusterId; ?>" />
			<input type="hidden" id="client" name="client" value="<?php echo $client; ?>" />
			<input type="hidden" id="fieldGroupValues" name="fieldGroupValues" value="" />
			<input type="hidden" id="fieldGroupCount" name="fieldGroupCount" value="<?php echo count($fieldGroups); ?>" />

			<input type="hidden" name="ropCopyRedirectUrl" id ="ropCopyRedirectUrl" value="<?php echo $ropCopyRedirectUrl; ?>" />
			<input type="hidden" name="filter" value="" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
	</div>
</div>
