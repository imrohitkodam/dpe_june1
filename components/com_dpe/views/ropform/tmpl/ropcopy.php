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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

$document = Factory::getDocument();
$document->addScript(Uri::root() . 'media/system/js/messages.min.js');
$document->addScript(Uri::root() . 'media/com_dpe/js/tjucm.js');

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$document->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
$document->addStyleSheet('templates/shaper_helix3/css/custom.css');

$app          = Factory::getApplication();
$isCopy       = $app->input->getInt('iscopy', 0);
$recordId     = $app->input->getInt('recordId', 0);
$client       = $app->input->get('client', '','STRING');
$recordIds    = $app->input->get('recordIds', '', 'RAW');
$recordIdsCommaSeparted = trim(preg_replace("/[^0-9,]/", "", json_encode($recordIds)));

$Itemid = $app->input->getInt('Itemid', 0);
$popUpTitle = Text::_('ROP_POPUP_HEADING_PROCESS');
Text::script('COM_DPE_SELECT_SCHOOL_ROP');

$popUpTitle = Text::_('ROP_POPUP_HEADING_COPY');

// Construct ROP link
$link = 'index.php?option=com_tjucm&view=items&client=' . $client;
JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
$tjucmHelper = new TjucmHelpersTjucm;
$itemId = $tjucmHelper->getItemId($link);

$filters = Factory::getApplication()->input->get('filter', '', 'Array');
$clusterFilter = '';

// Check school filter applied  or not
if (!empty($filters['cluster_id']))
{
	// Add filter in ULR to apply filter on list views
	$clusterFilter = '&cluster=' . (INT) $filters['cluster_id'];
}
elseif(Factory::getUser()->authorise('core.manageall', 'com_cluster') && empty($filters['cluster_id']))
{
	$clusterFilter = '&cluster=all';
}
else
{
	$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
	$clusters = $clusterUserModel->getUsersClusters(Factory::getUser()->id);

	if (count($clusters) > 1)
	{
		$clusterFilter = '&cluster=all';
	}
}

$ropCopyRedirectUrl = Route::_($link . $clusterFilter . '&Itemid=' . $itemId, false);
?>
<!-- <div id="ropCopyLoader" class="centerloader hide"></div> -->
<div id="ropCopyProgress" class="hide">
    <div class="progress-bar-background">
        <div id="ropCopyProgressBar" class="progress-bar-fill">0%</div>
    </div>
</div>
<div id="system-message-container"></div>

<h3 class="rop-popup-header ml-20 mr-20"><?php echo $popUpTitle; ?></h3>
<div id="ropCopypopCover">
	<div id="countermsg" class="alert alert-info d-none"></div>
	<div class="rop-popup-from mt-20 ml-20">
		<?php echo $this->form->renderField('cluster_ids'); ?>
		<div class="control-group">
			<div class="controls mt-30">
					<button class="btn btn-blue" onclick="jQuery('#item-form #tjucm_loader').show(); tjucm.itmes.copyItemRop();">
						<i class="fa fa-clone"></i>
						<?php echo Text::_('COM_DPE_CREATE_ROP'); ?>
					</button>
					<a onclick="tjucm.itmes.closePopup();" class="btn btn-default" href="javascript:void(0);" title="Cancel">
				Cancel				</a>
			</div>
			<?php echo "<input name='recordIds' id='recordIds' type='hidden' value='" . $recordIdsCommaSeparted . "' />"; ?>
			<input type="hidden" name="ropCopyRedirectUrl" id ="ropCopyRedirectUrl" value="<?php echo $ropCopyRedirectUrl; ?>" />
			<input type="hidden" name="filter" value="" />
			<input type="hidden" name="client" id="client" value="<?php echo $client; ?>" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
	</div>
</div>
