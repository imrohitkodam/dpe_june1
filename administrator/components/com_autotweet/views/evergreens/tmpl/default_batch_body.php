<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

?>

<fieldset class="xt-batch container-fluid">

	<div class="xt-grid">
		<div class="xt-col-span-6">
<?php
        if (PERFECT_PUB_PRO) {
            ?>
			<div class="control-group">
				<label class="control-label" for="batch_evergreen" id="batch_evergreen-lbl" rel="tooltip" data-original-title="<?php
            echo JText::_('COM_AUTOTWEET_REQ_EVERGREEN_DESC'); ?>"><i class="xticon fas fa-leaf"></i> <?php echo JText::_('COM_AUTOTWEET_REQ_EVERGREEN_TITLE'); ?> </label>
				<div class="controls inline">
					<?php echo EHtmlSelect::yesNo(0, 'batch_evergreen'); ?>
				</div>
			</div>

			<p class="text-center"><button class="btn btn-success" type="submit" onclick="Joomla.submitbutton('batchevergreen');">
				<?php echo JText::_('COM_AUTOTWEET_BATCH_MOVE_BUTTON'); ?>
			</button></p>

			<p class="text-center muted"><em>
				<?php echo JText::_('COM_AUTOTWEET_BATCH_EVERGREEN_DESC'); ?>
			</em></p>
<?php
        }
?>
		</div>
		<div class="xt-col-span-6">

		</div>

	</div>

</fieldset>
