<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

$channeltypeId = $this->input->get('channeltype_id', AutotweetModelChanneltypes::TYPE_ONESIGNAL_WEB_CHANNEL, 'cmd');
$isOnesignalWebPush = (AutotweetModelChanneltypes::TYPE_ONESIGNAL_WEB_CHANNEL === (int) $channeltypeId);

?>
<!-- com_autotweet_OUTPUT_START -->
<p style="text-align:center;">
    <span class="loaderspinner">&nbsp;</span>
</p>

<?php

if ($isOnesignalWebPush) {
    echo JText::_('COM_AUTOTWEET_CHANNEL_ONESIGNAL_WEB_DESC');
} else {
    echo JText::_('COM_AUTOTWEET_CHANNEL_ONESIGNAL_PUSH_DESC');
}

?>
<hr>
<div class="control-group">
    <label class="required control-label" for="app_id" id="app_id-lbl"><?php
        echo JText::_('COM_AUTOTWEET_CHANNEL_ONESIGNAL_APP_ID'); ?> <span class="star">&#160;*</span></label>
    <div class="controls">
        <input type="text" maxlength="255" value="<?php
        echo $this->item->xtform->get('app_id'); ?>" id="app_id" name="xtform[app_id]" class="required" required="required">
    </div>
</div>

<div class="control-group">
    <label class="required control-label" for="rest_api_key" id="rest_api_key-lbl"><?php
        echo JText::_('COM_AUTOTWEET_CHANNEL_ONESIGNAL_REST_API_KEY'); ?> <span class="star">&#160;*</span></label>
    <div class="controls">
        <input type="password" maxlength="255" value="<?php
        echo $this->item->xtform->get('rest_api_key'); ?>" id="rest_api_key" name="xtform[rest_api_key]" class="required" required="required">
    </div>
</div>

<div class="control-group">
    <label class="required control-label" for="user_auth_key" id="user_auth_key-lbl"><?php
        echo JText::_('COM_AUTOTWEET_CHANNEL_ONESIGNAL_USER_AUTH_KEY'); ?> <span class="star">&#160;*</span></label>
    <div class="controls">
        <input type="password" maxlength="255" value="<?php
        echo $this->item->xtform->get('user_auth_key'); ?>" id="user_auth_key" name="xtform[user_auth_key]" class="required" required="required">
    </div>
</div>
<?php

if ($isOnesignalWebPush) {
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
    <label class="control-label"> <a class="btn btn-info" id="oneSignalvalidationbutton"><?php

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

if ($isOnesignalWebPush) {
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
                Perfect Publish: auto-posting to Web Push OneSignal</a></li>

            <li><i class="xticon far fa-thumbs-up"></i> <a
            href="#" onclick="document.location='https://documentation.onesignal.com/docs/web-push-http-vs-https';"
            target="_blank"> HTTP vs. HTTPS</a> Using push notifications on HTTPS websites allows
                extra features not available on HTTP websites.</li>

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
                 Perfect Publish: auto-posting to Push Notifications with OneSignal</a></li>

            <li><i class="xticon far fa-thumbs-up"></i> <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')">
                 How to publish from Joomla</a></li>
        </ul>
    </div>
<?php
    }
?>
<!-- com_autotweet_OUTPUT_START -->
