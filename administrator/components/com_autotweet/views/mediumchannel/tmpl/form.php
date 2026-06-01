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
<!-- com_autotweet_OUTPUT_START -->
<p style="text-align:center;">
    <span class="loaderspinner">&nbsp;</span>
</p>

<?php echo JText::_('COM_AUTOTWEET_CHANNEL_MEDIUM_DESC'); ?>
<hr>

<div class="control-group">
    <label class="required control-label" for="integration_token" id="integration_token-lbl"><?php
        echo JText::_('COM_AUTOTWEET_CHANNEL_MEDIUM_FIELD_INTEGRATION_TOKEN'); ?> <span class="star">&#160;*</span></label>
    <div class="controls">
        <input type="password" maxlength="255" value="<?php
        echo $this->item->xtform->get('integration_token'); ?>" id="integration_token" name="xtform[integration_token]" class="required" required="required">
    </div>
</div>

<div class="control-group">
    <label class="control-label"> <a class="btn btn-info" id="mediumvalidationbutton"><?php

    echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_VALIDATEBUTTON');

    ?></a>
    </label>

    <div id="validation-notchecked" class="controls">
        <span class="lead"><i class="xticon far fa-question-circle"></i> </span><span class="loaderspinner">&nbsp;</span>
    </div>

    <div id="validation-success" class="controls" style="display: none">
        <span class="lead"><i class="xticon fas fa-check"></i> <?php
        echo JText::_('COM_AUTOTWEET_STATE_PUBSTATE_SUCCESS'); ?></span><span class="loaderspinner">&nbsp;</span>
    </div>

    <div id="validation-error" class="controls" style="display: none">
        <span class="lead"><i class="xticon fas fa-exclamation"></i> <?php
        echo JText::_('COM_AUTOTWEET_STATE_PUBSTATE_ERROR'); ?></span><span class="loaderspinner">&nbsp;</span>
    </div>

</div>

<div id="validation-errormsg" class="xt-alert xt-alert-block alert-error" style="display: none">
    <!-- Removed button close data-dismiss="alert" -->
    <div id="validation-theerrormsg">
        <?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_AUTH_MSG'); ?>
    </div>
</div>

<div class="control-group">
    <label class=" required control-label" for="user_id" id="user_id-lbl"><?php echo JText::_('COM_AUTOTWEET_VIEW_USERID_TITLE');

    ?><span class="star">&nbsp;*</span>
    </label>
    <div class="controls">
        <input type="text" maxlength="255" value="<?php

        echo $this->item->xtform->get('user_id');

        ?>" id="user_id" name="xtform[user_id]" class="required" required="required" readonly="readonly">
<?php

        require __DIR__.'/../../channel/tmpl/social_url.php';

?>
    </div>
</div>

<hr>
<div class="xt-alert xt-alert-info">
    <!-- Removed button close data-dismiss="alert" -->
    <ul class="unstyled">
        <li>
            <i class="xticon far fa-thumbs-up"></i>
            <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')">
                Tutorial: How to publish to Medium</a>
        </li>
        <li>
            <i class="xticon far fa-thumbs-up"></i>
            <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')">
                How to publish from Joomla</a>
        </li>
    </ul>
</div>

<!-- com_autotweet_OUTPUT_START -->
