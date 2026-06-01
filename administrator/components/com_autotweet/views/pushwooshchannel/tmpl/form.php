<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

$channeltypeId = $this->input->get('channeltype_id', AutotweetModelChanneltypes::TYPE_PUSHWOOSH_WEB_CHANNEL, 'cmd');
$isPushwooshWebPush = (AutotweetModelChanneltypes::TYPE_PUSHWOOSH_WEB_CHANNEL === (int) $channeltypeId);

?>
<!-- com_autotweet_OUTPUT_START -->
<p style="text-align:center;">
    <span class="loaderspinner">&nbsp;</span>
</p>

<?php

if ($isPushwooshWebPush) {
    echo JText::_('COM_AUTOTWEET_CHANNEL_PUSHWOOSH_WEB_DESC');
} else {
    echo JText::_('COM_AUTOTWEET_CHANNEL_PUSHWOOSH_PUSH_DESC');
}

?>
<hr>

<div class="control-group">
    <label class="required control-label" for="application_id" id="application_id-lbl"><?php
        echo JText::_('COM_AUTOTWEET_CHANNEL_PUSHWOOSH_APPLICATION_ID'); ?> <span class="star">&#160;*</span></label>
    <div class="controls">
        <input type="text" maxlength="255" value="<?php
        echo $this->item->xtform->get('application_id'); ?>" id="application_id" name="xtform[application_id]" class="required" required="required">
    </div>
</div>

<div class="control-group">
    <label class="required control-label" for="access_token" id="access_token-lbl"><?php
        echo JText::_('COM_AUTOTWEET_CHANNEL_PUSHWOOSH_ACCESS_TOKEN'); ?> <span class="star">&#160;*</span></label>
    <div class="controls">
        <input type="password" maxlength="255" value="<?php
        echo $this->item->xtform->get('access_token'); ?>" id="access_token" name="xtform[access_token]" class="required disabled" required="required">
    </div>
</div>

<div class="control-group">
    <label class="required control-label" for="platform" id="platform-lbl"><?php
        echo JText::_('COM_AUTOTWEET_CHANNEL_PUSHWOOSH_PLATFORM'); ?> <span class="star">&#160;*</span></label>
    <div class="controls">
    </div>
</div>
<?php

if ($isPushwooshWebPush) {
    echo EHtmlSelect::yesNoControl($this->item->xtform->get('chrome', 1), 'xtform[chrome]', 'Chrome', '');
    echo EHtmlSelect::yesNoControl($this->item->xtform->get('firefox', 1), 'xtform[firefox]', 'Firefox', '');
    echo EHtmlSelect::yesNoControl($this->item->xtform->get('safari', 0), 'xtform[safari]', 'Safari', '');

    echo '<input type="hidden" name="xtform[ios]" value="0" />';
    echo '<input type="hidden" name="xtform[android]" value="0" />';
    echo '<input type="hidden" name="xtform[adm]" value="0" />';
    echo '<input type="hidden" name="xtform[wp]" value="0" />';
} else {
    echo EHtmlSelect::yesNoControl($this->item->xtform->get('ios', 1), 'xtform[ios]', 'iOS', '');
    echo EHtmlSelect::yesNoControl($this->item->xtform->get('android', 1), 'xtform[android]', 'Android', '');
    echo EHtmlSelect::yesNoControl($this->item->xtform->get('adm', 0), 'xtform[adm]', 'Amazon', '');
    echo EHtmlSelect::yesNoControl($this->item->xtform->get('wp', 0), 'xtform[wp]', 'Windows Phone', '');

    echo '<input type="hidden" name="xtform[chrome]" value="0" />';
    echo '<input type="hidden" name="xtform[firefox]" value="0" />';
    echo '<input type="hidden" name="xtform[safari]" value="0" />';
}

?>
<div class="control-group">
    <label class="control-label"> <a class="btn btn-info" id="pushwooshvalidationbutton"><?php

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
<?php

if ($isPushwooshWebPush) {
    ?>

    <hr>
    <div class="xt-alert xt-alert-info">
        <!-- Removed button close data-dismiss="alert" -->
        <ul class="unstyled">
            <li><i class="xticon far fa-thumbs-up"></i> <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')">
                Web Push notifications for Joomla</a></li>

            <li><i class="xticon far fa-thumbs-up"></i> <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')">
                Perfect Publish: auto-posting to Web Push Pushwoosh</a></li>

            <li><i class="xticon far fa-thumbs-up"></i> <a
            href="#" onclick="document.location='https://documentation.onesignal.com/docs/web-push-http-vs-https'"
            target="_blank"> HTTP vs. HTTPS</a> Using push notifications on HTTPS
            websites allows extra features not available on HTTP websites.</li>

            <li><i class="xticon far fa-thumbs-up"></i> <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')">
                How to publish from Joomla</a></li>
        </ul>
    </div>
<?php
} else {
        ?>

    <hr>
    <div class="xt-alert xt-alert-info">
        <!-- Removed button close data-dismiss="alert" -->
        <ul class="unstyled">
            <li><i class="xticon far fa-thumbs-up"></i> <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')">
                Push Notifications for Joomla</a></li>

            <li><i class="xticon far fa-thumbs-up"></i> <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')">
                Perfect Publish: auto-posting to Push Notifications with Pushwoosh</a></li>

            <li><i class="xticon far fa-thumbs-up"></i> <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')">
                How to publish from Joomla</a></li>
        </ul>
    </div>
<?php
    }
?>
<!-- com_autotweet_OUTPUT_START -->
