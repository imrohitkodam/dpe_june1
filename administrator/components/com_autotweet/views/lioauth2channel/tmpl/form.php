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
$channeltypeId = $this->input->get('channeltype_id', AutotweetModelChanneltypes::TYPE_LIOAUTH2_CHANNEL, 'cmd');

?>
<!-- com_autotweet_OUTPUT_START -->
<p style="text-align:center;">
	<span class="loaderspinner">&nbsp;</span>
</p>
<?php

    echo JText::_('COM_AUTOTWEET_CHANNEL_LIOAUTH2_DESC').'<hr>';

    $required = ['class' => 'required', 'required' => 'required'];
    echo EHtml::textControl($this->item->xtform->get('consumer_key'), 'xtform[consumer_key]', 'COM_AUTOTWEET_CHANNEL_LIOAUTH2_CONSUMER_KEY', 'COM_AUTOTWEET_CHANNEL_LIOAUTH2_CONSUMER_KEY_DESC', 'consumer_key', 60, $required);
    echo EHtml::textControl($this->item->xtform->get('consumer_secret'), 'xtform[consumer_secret]', 'COM_AUTOTWEET_CHANNEL_LIOAUTH2_CONSUMER_SECRET', 'COM_AUTOTWEET_CHANNEL_LIOAUTH2_CONSUMER_SECRET_DESC', 'consumer_secret', 60, $required);

    $options = [];
    // $options[] = ['name' => 'v1', 'value' => LiOAuth2ChannelHelper::API_v1];
    $options[] = ['name' => 'v2', 'value' => LiOAuth2ChannelHelper::API_v2];

    echo EHtmlSelect::btnGroupListControl(
        $this->item->xtform->get('li_api_version', LiOAuth2ChannelHelper::API_v2),
        'xtform[li_api_version]',
        'COM_AUTOTWEET_CHANNEL_LIOAUTH2_API_VERSION_LABEL',
        'COM_AUTOTWEET_CHANNEL_LIOAUTH2_API_VERSION_DESC',
        $options,
        'li_api_version'
    );

    $accessToken = null;
    $user = null;
    $userId = null;

    $authUrl = '#';
    $authUrlButtonStyle = 'disabled';
    $validationGroupStyle = 'hide';

    // New channel, not even saved
    if (!(bool) $this->item->id) {
        $message = JText::_('COM_AUTOTWEET_CHANNEL_LIOAUTH2_NEWCHANNEL_NOAUTHORIZATION');
        require_once 'auth_button.php';
    } else {
        $isCompanyChannel = (AutotweetModelChanneltypes::TYPE_LIOAUTH2COMPANY_CHANNEL === (int) $channeltypeId);

        if ($isCompanyChannel) {
            $lioauth2ChannelHelper = new LiOAuth2CompanyChannelHelper($this->item);
        } else {
            $lioauth2ChannelHelper = new LiOAuth2ChannelHelper($this->item);
        }

        $isAuth = $lioauth2ChannelHelper->isAuth();

        // New channel, but saved
        if (($isAuth) && (is_array($isAuth)) && (array_key_exists('user', $isAuth))) {
            // We have an access Token!
            $user = $isAuth['user'];
            $userId = $user->id;
            $this->item->xtform->set('social_url', $lioauth2ChannelHelper->getSocialUrl($user));

            $validationGroupStyle = null;

            $accessToken = $this->item->xtform->get('access_token');

            require_once 'validation_button.php';
        } else {
            $message = JText::_('COM_AUTOTWEET_CHANNEL_LIOAUTH2_NEWCHANNEL_AUTHORIZATION');
            $authUrl = $lioauth2ChannelHelper->getAuthorizationUrl();

            if (empty($authUrl)) {
                $authUrl = '#';
                $message = JText::_('COM_AUTOTWEET_CHANNEL_LIOAUTH2_NEWCHANNEL_NOAUTHORIZATION');
                require_once 'auth_button.php';
            } else {
                $authUrlButtonStyle = null;

                require_once 'auth_button.php';
                require_once 'validation_button.php';
            }
        }
    }
?>

<hr>
<div class="xt-alert xt-alert-info">
    <ul class="unstyled">
        <li>
            <i class="xticon far fa-thumbs-up"></i>
            <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')">
                Tutorial: How to publish to LinkedIn</a>
        </li>
        <li>
            <i class="xticon fab fa-youtube"></i>
            <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')"> How to publish from Joomla to LinkedIn - Video</a>
        </li>
        <li>
            <i class="xticon far fa-thumbs-up"></i>
            <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')"> How to publish from Joomla</a>
        </li>
    </ul>
</div>

<!-- com_autotweet_OUTPUT_START -->
