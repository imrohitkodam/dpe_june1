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
$document->addScript(Uri::root() . 'media/com_dpe/js/tjucm.js');

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$document->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
$document->addStyleSheet('templates/shaper_helix3/css/custom.css');

$app = Factory::getApplication();
$isCopy = $app->input->getInt('iscopy', 0);
$recordId = $app->input->getInt('recordId', 0);
$Itemid = $app->input->getInt('Itemid', 0);
$popUpTitle = Text::_('ROP_POPUP_HEADING_PROCESS');
Text::script('COM_DPE_SELECT_SCHOOL_ROP');

$ropLink = 'index.php?option=com_tjucm&task=itemform.edit&client=com_tjucm.rop&Itemid=' . $Itemid . '&' . Session::getFormToken() . '=1';

$doc = Factory::getDocument();
$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');

if ($isCopy)
{
	$popUpTitle = Text::_('ROP_POPUP_HEADING_COPY');
	$ropLink = 'index.php?option=com_tjucm&task=itemform.prepareForCopy&client=com_tjucm.rop&Itemid=' . $Itemid . '&' . Session::getFormToken() . '=1';
}
?>
<h3 class="rop-popup-header px-3 py-2"><?php echo $popUpTitle; ?></h3>
<div class="rop-popup-from mt-20 ml-20">
	<form action="<?php echo Route::_($ropLink); ?>" method="post" name="ropForm" id="ropForm">
		<?php echo $this->form->renderField('cluster_id'); ?>
		<div class="control-group">
			<div class="controls mt-30">
				<button type="button" onclick="tjucm.itmes.createRopProcess();" class="btn btn-blue"><?php echo Text::_('COM_DPE_CREATE_ROP'); ?></button>
				<a onclick="tjucm.itmes.closePopup();" class="btn btn-default btn-popup-cancel" href="javascript:void(0);" title="<?php echo Text::_('JCANCEL'); ?>">
				<?php echo Text::_('JCANCEL'); ?>
				</a>
			</div>
		</div>
		<input type="hidden" name="recordId" id="recordId" value="<?php echo $recordId; ?>" />
		<input type="hidden" name="copyRop" id="copyRop" value="<?php echo $isCopy;?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
