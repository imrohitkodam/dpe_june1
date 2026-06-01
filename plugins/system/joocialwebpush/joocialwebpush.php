<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

use XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\ScriptHelper;

/**
 * PlgSystemJoocialWebpush class.
 *
 * @since       1.0
 */
class PlgSystemJoocialWebpush extends \Joomla\CMS\Plugin\CMSPlugin
{
    protected $manifest;

    protected $onesignalScript = "
// Onesignal
var OneSignal = window.OneSignal || [];
OneSignal.push(function() {
	OneSignal.init({
		appId: 'JOOCIAL_YOUR_APP_ID',
		safari_web_id: JOOCIAL_YOUR_SAFARI_WEB_ID,
		autoRegister: JOOCIAL_AUTO_REGISTER,
		notifyButton: {
			enable: JOOCIAL_NOTIFY_BUTTON,
			text: {
				'tip.state.unsubscribed': 'PLG_JOOCIALWEBPUSH_TIP_STATE_UNSUBSCRIBED',
				'tip.state.subscribed': 'PLG_JOOCIALWEBPUSH_TIP_STATE_SUBSCRIBED',
				'tip.state.blocked': 'PLG_JOOCIALWEBPUSH_TIP_STATE_BLOCKED',
				'message.prenotify': 'PLG_JOOCIALWEBPUSH_MESSAGE_PRENOTIFY',
				'message.action.subscribed': 'PLG_JOOCIALWEBPUSH_MESSAGE_ACTION_SUBSCRIBED',
				'message.action.resubscribed': 'PLG_JOOCIALWEBPUSH_MESSAGE_ACTION_RESUBSCRIBED',
				'message.action.unsubscribed': 'PLG_JOOCIALWEBPUSH_MESSAGE_ACTION_UNSUBSCRIBED',
				'dialog.main.title': 'PLG_JOOCIALWEBPUSH_DIALOG_MAIN_TITLE',
				'dialog.main.button.subscribe': 'PLG_JOOCIALWEBPUSH_DIALOG_MAIN_BUTTON_SUBSCRIBE',
				'dialog.main.button.unsubscribe': 'PLG_JOOCIALWEBPUSH_DIALOG_MAIN_BUTTON_UNSUBSCRIBE',
				'dialog.blocked.title': 'PLG_JOOCIALWEBPUSH_DIALOG_BLOCKED_TITLE',
				'dialog.blocked.message': 'PLG_JOOCIALWEBPUSH_DIALOG_BLOCKED_MESSAGE'
			}
		},
		persistNotification: JOOCIAL_PERSIST_NOTIFICATION,
		promptOptions: {
			actionMessage: JOOCIAL_ACTION_MESSAGE,
			acceptButtonText: JOOCIAL_ACCEPT_BUTTON_TEXT,
			cancelButtonText: JOOCIAL_CANCEL_BUTTON_TEXT,
			autoAcceptTitle: JOOCIAL_AUTO_ACCEPT_TITLE
		},
	});
});
// Onesignal
	";

    protected $pushwooshScript = "
// Pushwoosh
var Pushwoosh = Pushwoosh || [];
Pushwoosh.push([
	'init',
	{
		logLevel: 'error',
		applicationCode: 'JOOCIAL_APPLICATIONCODE',
		safariWebsitePushID: JOOCIAL_SAFARIWEBSITEPUSHID,
		defaultNotificationTitle: JOOCIAL_DEFAULTNOTIFICATIONTITLE,
		defaultNotificationImage: 'JOOCIAL_DEFAULTNOTIFICATIONIMAGE',
		autoSubscribe: JOOCIAL_AUTO_SUBSCRIBE,
		userId: JOOCIAL_USER_ID,
	},
]);
// Pushwoosh
	";

    protected $pushAlertScript = "
// PushAlert
document.addEventListener('DOMContentLoaded', function() {
	(function(d, t) {
		var g = d.createElement(t),
		s = d.getElementsByTagName(t)[0];
		g.src = 'https://cdn.pushalert.co/integrate_JOOCIAL_PUSHALERT_WEBSITE_ID.js';
		s.parentNode.insertBefore(g, s);
	}(document, 'script'));
});
// PushAlert
";

    /**
     * onBeforeRender.
     */
    public function onBeforeRender()
    {
        if ((\Joomla\CMS\Factory::getApplication()->isClient('administrator')) || (\Joomla\CMS\Factory::getConfig()->get('offline'))) {
            return;
        }

        $currentItemId = \Joomla\CMS\Factory::getApplication()->input->get('Itemid', 0);
        $setItemid = $this->params->get('set_itemid', ['0']);

        if (!is_array($setItemid)) {
            $setItemid = [$setItemid];
        }

        $allPages = in_array('0', $setItemid, true);
        $specificPages = in_array($currentItemId, $setItemid, true);
        $enabled = (($allPages) || ($specificPages));

        if (!$enabled) {
            return;
        }

        if ('html' !== \Joomla\CMS\Factory::getDocument()->getType()) {
            return;
        }

        if (!defined('AUTOTWEET_API') && !@include_once(JPATH_ADMINISTRATOR.'/components/com_autotweet/api/autotweetapi.php')) {
            return;
        }

        $this->addManifest();

        $pushservice = $this->params->get('pushservice');

        switch ($pushservice) {
            case 'onesignal':
                $this->renderOnesignalWebpush();

                break;
            case 'pushalert':
                $this->renderPushalertWebpush();

                break;
            case 'pushwoosh':
                $this->renderPushwooshWebpush();

                break;
        }
    }

    /**
     * addManifest.
     */
    protected function addManifest()
    {
        $base = \Joomla\CMS\Uri\Uri::root(true);

        if (file_exists(JPATH_ROOT.'/manifest.json')) {
            \Joomla\CMS\Factory::getDocument()->addCustomTag('<link rel="manifest" href="'.
                $base.'/manifest.json">');

            return;
        }

        if (\Joomla\CMS\Plugin\PluginHelper::isEnabled('ajax', 'joocialwebpushmanifest')) {
            \Joomla\CMS\Factory::getDocument()->addCustomTag('<link rel="manifest" href="'.
                $base.'/index.php?option=com_ajax&plugin=joocialWebpushManifest&format=raw">');

            return;
        }
    }

    /**
     * renderOnesignalWebpush.
     */
    protected function renderOnesignalWebpush()
    {
        $jlang = \Joomla\CMS\Factory::getLanguage();
        $jlang->load('plg_system_joocialwebpush', JPATH_ADMINISTRATOR);

        $script = $this->onesignalScript;

        $script = str_replace('JOOCIAL_YOUR_APP_ID', $this->params->get('onesignal_app_id'), $script);
        $script = str_replace('JOOCIAL_YOUR_SAFARI_WEB_ID', $this->nullOrString($this->params->get('onesignal_safari_website_push_id')), $script);

        $script = str_replace('JOOCIAL_AUTO_REGISTER', $this->trueOrFalse($this->params->get('onesignal_auto_register')), $script);
        $script = str_replace('JOOCIAL_PERSIST_NOTIFICATION', $this->trueOrFalse($this->params->get('onesignal_persist_notification')), $script);
        $script = str_replace('JOOCIAL_ACTION_MESSAGE', json_encode($this->params->get('onesignal_action_message')), $script);
        $script = str_replace('JOOCIAL_ACCEPT_BUTTON_TEXT', json_encode($this->params->get('onesignal_accept_button_text')), $script);
        $script = str_replace('JOOCIAL_CANCEL_BUTTON_TEXT', json_encode($this->params->get('onesignal_cancel_button_text')), $script);
        $script = str_replace('JOOCIAL_AUTO_ACCEPT_TITLE', json_encode($this->params->get('onesignal_auto_accept_title')), $script);

        $onesignalNotifyButton = (bool) $this->params->get('onesignal_notify_button', true);
        $script = str_replace('JOOCIAL_NOTIFY_BUTTON', $this->trueOrFalse($onesignalNotifyButton), $script);

        $script = str_replace('PLG_JOOCIALWEBPUSH_TIP_STATE_UNSUBSCRIBED', JText::_('PLG_JOOCIALWEBPUSH_TIP_STATE_UNSUBSCRIBED'), $script);
        $script = str_replace('PLG_JOOCIALWEBPUSH_TIP_STATE_SUBSCRIBED', JText::_('PLG_JOOCIALWEBPUSH_TIP_STATE_SUBSCRIBED'), $script);
        $script = str_replace('PLG_JOOCIALWEBPUSH_TIP_STATE_BLOCKED', JText::_('PLG_JOOCIALWEBPUSH_TIP_STATE_BLOCKED'), $script);
        $script = str_replace('PLG_JOOCIALWEBPUSH_MESSAGE_PRENOTIFY', JText::_('PLG_JOOCIALWEBPUSH_MESSAGE_PRENOTIFY'), $script);
        $script = str_replace('PLG_JOOCIALWEBPUSH_MESSAGE_ACTION_SUBSCRIBED', JText::_('PLG_JOOCIALWEBPUSH_MESSAGE_ACTION_SUBSCRIBED'), $script);
        $script = str_replace('PLG_JOOCIALWEBPUSH_MESSAGE_ACTION_RESUBSCRIBED', JText::_('PLG_JOOCIALWEBPUSH_MESSAGE_ACTION_RESUBSCRIBED'), $script);
        $script = str_replace('PLG_JOOCIALWEBPUSH_MESSAGE_ACTION_UNSUBSCRIBED', JText::_('PLG_JOOCIALWEBPUSH_MESSAGE_ACTION_UNSUBSCRIBED'), $script);
        $script = str_replace('PLG_JOOCIALWEBPUSH_DIALOG_MAIN_TITLE', JText::_('PLG_JOOCIALWEBPUSH_DIALOG_MAIN_TITLE'), $script);
        $script = str_replace('PLG_JOOCIALWEBPUSH_DIALOG_MAIN_BUTTON_SUBSCRIBE', JText::_('PLG_JOOCIALWEBPUSH_DIALOG_MAIN_BUTTON_SUBSCRIBE'), $script);
        $script = str_replace('PLG_JOOCIALWEBPUSH_DIALOG_MAIN_BUTTON_UNSUBSCRIBE', JText::_('PLG_JOOCIALWEBPUSH_DIALOG_MAIN_BUTTON_UNSUBSCRIBE'), $script);
        $script = str_replace('PLG_JOOCIALWEBPUSH_DIALOG_BLOCKED_TITLE', JText::_('PLG_JOOCIALWEBPUSH_DIALOG_BLOCKED_TITLE'), $script);
        $script = str_replace('PLG_JOOCIALWEBPUSH_DIALOG_BLOCKED_MESSAGE', JText::_('PLG_JOOCIALWEBPUSH_DIALOG_BLOCKED_MESSAGE'), $script);

        ScriptHelper::addScript(
            'https://cdn.onesignal.com/sdks/OneSignalSDK.js',
            [],
            [
                'async' => true,
                'defer' => false,
            ]
        );
        ScriptHelper::addScriptDeclaration($script);
    }

    /**
     * renderPushAlertWebpush.
     */
    protected function renderPushAlertWebpush()
    {
        $script = $this->pushAlertScript;
        $script = str_replace('JOOCIAL_PUSHALERT_WEBSITE_ID', $this->params->get('pushalert_website_id'), $script);
        ScriptHelper::addScriptDeclaration($script);
    }

    /**
     * renderPushwooshWebpush.
     */
    protected function renderPushwooshWebpush()
    {
        $script = $this->pushwooshScript;

        $script = str_replace('JOOCIAL_APPLICATIONCODE', $this->params->get('pushwoosh_application_code'), $script);
        $script = str_replace('JOOCIAL_SAFARIWEBSITEPUSHID', $this->nullOrString($this->params->get('pushwoosh_safari_website_push_id')), $script);
        $script = str_replace(
            'JOOCIAL_DEFAULTNOTIFICATIONTITLE',
            json_encode($this->params->get('pushwoosh_default_notification_title', \Joomla\CMS\Factory::getConfig()->get('sitename'))),
            $script
        );
        $script = str_replace(
            'JOOCIAL_DEFAULTNOTIFICATIONIMAGE',
            $this->params->get('pushwoosh_default_notification_image', 'https://cp.pushwoosh.com/img/logo-medium.png'),
            $script
        );
        $script = str_replace('JOOCIAL_AUTO_SUBSCRIBE', $this->trueOrFalse($this->params->get('pushwoosh_auto_subscribe')), $script);

        $user = \Joomla\CMS\Factory::getUser();
        $script = str_replace('JOOCIAL_USER_ID', $this->nullOrString($user->id), $script);
        $script = str_replace('JOOCIAL_NAME', ($user->guest ? '' : "'Name': '{$user->name}'"), $script);

        ScriptHelper::addScript(
            'https://cdn.pushwoosh.com/webpush/v3/pushwoosh-web-notifications.js',
            [],
            [
                'async' => true,
                'defer' => false,
            ]
        );
        ScriptHelper::addScriptDeclaration($script);
    }

    /**
     * onBeforeRender.
     *
     * @param string $value Param
     */
    protected function nullOrString($value)
    {
        if (empty($value)) {
            return 'null';
        }

        return "'".$value."'";
    }

    /**
     * trueOrFalse.
     *
     * @param string $value Param
     */
    protected function trueOrFalse($value)
    {
        if ((bool) $value) {
            return 'true';
        }

        return 'false';
    }
}
