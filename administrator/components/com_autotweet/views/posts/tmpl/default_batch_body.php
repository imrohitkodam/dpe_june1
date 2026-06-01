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

			<div class="control-group">
				<label class="control-label required" for="batch_pubstate" id="batch_pubstate-lbl" rel="tooltip" data-original-title="<?php
            echo JText::_('COM_AUTOTWEET_BATCH_POSTS_DESC');
            ?>"><i class="xticon fas fa-cog"></i> <?php echo JText::_('COM_AUTOTWEET_POST_STATE'); ?> </label>

				<div class="controls inline">
					<?php echo SelectControlHelper::pubstates(null, 'batch_pubstate', ['class' => 'input']); ?>
				</div>
			</div>

			<p class="text-center"><button class="btn btn-success" type="submit" onclick="Joomla.submitbutton('batch');">
				<?php echo JText::_('COM_AUTOTWEET_BATCH_MOVE_BUTTON'); ?>
			</button></p>

			<p class="text-center muted"><em>
				<?php echo JText::_('COM_AUTOTWEET_POST_STATE_DESC'); ?>
			</em></p>

		</div>
		<div class="xt-col-span-6">
		</div>
	</div>

</fieldset>
