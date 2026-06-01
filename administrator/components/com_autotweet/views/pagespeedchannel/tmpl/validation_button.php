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
<div id="validationGroup">

	<div class="control-group">

		<label class="control-label">

		<a class="btn btn-info" id="pagespeedvalidationbutton"><?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_VALIDATEBUTTON'); ?></a>&nbsp;

		</label>

		<div id="validation-notchecked" class="controls">
			<span class="lead"><i class="xticon far fa-question-circle"></i> </span><span class="loaderspinner">&nbsp;</span>
		</div>

		<div id="validation-success" class="controls" style="display: none">
			<span class="lead"><i class="xticon fas fa-check"></i> <?php echo JText::_('COM_AUTOTWEET_STATE_PUBSTATE_SUCCESS'); ?></span><span class="loaderspinner">&nbsp;</span>
		</div>

		<div id="validation-error" class="controls" style="display: none">
			<span class="lead"><i class="xticon fas fa-exclamation"></i> <?php echo JText::_('COM_AUTOTWEET_STATE_PUBSTATE_ERROR'); ?></span><span class="loaderspinner">&nbsp;</span>
		</div>

	</div>

	<div id="validation-errormsg" class="xt-alert xt-alert-block alert-error" style="display: none">
		<!-- Removed button close data-dismiss="alert" -->
		<div id="validation-theerrormsg">
			<?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_AUTH_MSG'); ?>
		</div>
	</div>
</div>
