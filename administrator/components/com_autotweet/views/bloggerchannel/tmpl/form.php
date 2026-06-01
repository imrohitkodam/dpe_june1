<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

$session = \Joomla\CMS\Factory::getSession();
$session->set('channelId', $this->item->id);

?>
<!-- com_autotweet_OUTPUT_START -->
<p style="text-align:center;">
    <span class="loaderspinner">&nbsp;</span>
</p>

<?php echo JText::_('COM_AUTOTWEET_CHANNEL_BLOGGER_DESC'); ?>
<hr>

<div class="control-group">
    <label class="required control-label" for="developer_key" id="developer_key-lbl"><?php echo JText::_('COM_AUTOTWEET_CHANNEL_BLOGGER_FIELD_DEVELOPER_KEY'); ?> <span class="star">&#160;*</span></label>
    <div class="controls">
        <input type="text" maxlength="255" value="<?php echo $this->item->xtform->get('developer_key'); ?>" id="developer_key" name="xtform[developer_key]" class="required" required="required">
    </div>
</div>

<div class="control-group">
    <label class="required control-label" for="client_id" id="client_id-lbl"><?php echo JText::_('COM_AUTOTWEET_CHANNEL_BLOGGER_FIELD_CLIENT_ID'); ?> <span class="star">&#160;*</span></label>
    <div class="controls">
        <input type="text" maxlength="255" value="<?php echo $this->item->xtform->get('client_id'); ?>" id="client_id" name="xtform[client_id]" class="required" required="required">
    </div>
</div>

<div class="control-group">
    <label class="required control-label" for="client_secret" id="client_secret-lbl"><?php echo JText::_('COM_AUTOTWEET_CHANNEL_BLOGGER_FIELD_CLIENT_SECRET'); ?> <span class="star">&#160;*</span></label>
    <div class="controls">
        <input type="password" maxlength="255" value="<?php echo $this->item->xtform->get('client_secret'); ?>" id="client_secret" name="xtform[client_secret]" class="required" required="required">
    </div>
</div>

<?php

$accessToken = null;
$userId = null;
$expiresIn = null;

$hasClientId = (bool) $this->item->xtform->get('client_id');
$authUrl = '#';
$authUrlButtonStyle = 'disabled';

$validationGroupStyle = 'hide';

    // New channel, not even saved
if (!(bool) $this->item->id) {
    $message = JText::_('COM_AUTOTWEET_CHANNEL_BLOGGER_NEWCHANNEL_NOAUTHORIZATION');
    require_once 'auth_button.php';
} else {
    $bloggerChannelHelper = new BloggerChannelHelper($this->item);
    $isAuth = $bloggerChannelHelper->isAuth();

    // New channel, but saved
    if ($isAuth) {
        // We have an access Token!

        $accessToken = $bloggerChannelHelper->getAccessToken();

        $user = $bloggerChannelHelper->getUser();
        $userId = $user->id;
        $this->item->xtform->set('social_url', $user->url);

        $expiresIn = $bloggerChannelHelper->getExpiresIn();

        $validationGroupStyle = null;

        require_once 'validation_button.php';
    } else {
        $message = JText::_('COM_AUTOTWEET_CHANNEL_BLOGGER_NEWCHANNEL_AUTHORIZATION');

        if ($hasClientId) {
            $authUrl = $bloggerChannelHelper->getAuthorizationUrl();
            $authUrlButtonStyle = null;
        }

        require_once 'auth_button.php';
        require_once 'validation_button.php';
    }
}
?>

<hr>
<div class="xt-alert xt-alert-info">
    <!-- Removed button close data-dismiss="alert" -->

    <ul class="unstyled">
        <li>
            <i class="xticon far fa-thumbs-up"></i>
            <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')">
                Tutorial: How to publish to Google Blogger</a>
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
