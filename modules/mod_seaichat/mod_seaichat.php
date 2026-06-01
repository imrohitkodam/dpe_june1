<?php


/**
 * @package     Joomla
 * @subpackage  mod_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ModuleHelper;

// Check if com_seaichat is installed and enabled
if (!ComponentHelper::isEnabled('com_seaichat')) {
    return;
}

// Read component params directly from DB (bypasses Joomla filtering of API key)
$db = Factory::getContainer()->get('DatabaseDriver');
$query = $db->getQuery(true)
    ->select($db->quoteName('params'))
    ->from($db->quoteName('#__extensions'))
    ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
    ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
$db->setQuery($query);
$p = json_decode($db->loadResult(), true) ?: [];

// Must be enabled with an API key
if (empty($p['aichat_enabled']) || empty(trim($p['aichat_api_key'] ?? ''))) {
    return;
}

$user = Factory::getApplication()->getIdentity();

$providerNames = [
    'anthropic' => 'Anthropic Claude',
    'openai'    => 'OpenAI GPT',
    'gemini'    => 'Google Gemini',
    'deepseek'  => 'DeepSeek',
];
$currentProvider = $p['aichat_provider'] ?? 'anthropic';
$providerDisplayName = $providerNames[$currentProvider] ?? $currentProvider;

$gdprEnabled = !empty($p['aichat_gdpr_enabled']);
$gdprText = $p['aichat_gdpr_text'] ?? 'This chatbot uses {ai_provider} to process your requests. Your inputs and conversation history are transmitted to and processed by {ai_provider}. No personal data is stored. Data is transmitted on the basis of your consent (Art. 6 (1) (a) GDPR). You may withdraw your consent at any time by closing the chat. For further information, please refer to our privacy policy.';
$gdprText = str_replace('{ai_provider}', $providerDisplayName, $gdprText);

// Load frontend language strings (with admin overrides taking priority)
$lang = Factory::getApplication()->getLanguage();
$lang->load('com_seaichat', JPATH_SITE);
$lang->load('com_seaichat', JPATH_SITE . '/components/com_seaichat');
$_stringKeys = [
    'status_online'          => 'COM_SEAICHAT_STATUS_ONLINE',
    'chat_with_ai'           => 'COM_SEAICHAT_CHAT_WITH_AI',
    'new_conversation'       => 'COM_SEAICHAT_NEW_CONVERSATION',
    'close'                  => 'COM_SEAICHAT_CLOSE',
    'send'                   => 'COM_SEAICHAT_SEND',
    'gdpr_title'             => 'COM_SEAICHAT_GDPR_TITLE',
    'gdpr_accept'            => 'COM_SEAICHAT_GDPR_ACCEPT',
    'gdpr_decline'           => 'COM_SEAICHAT_GDPR_DECLINE',
    'gdpr_unavailable_title' => 'COM_SEAICHAT_GDPR_UNAVAILABLE_TITLE',
    'gdpr_unavailable_msg'   => 'COM_SEAICHAT_GDPR_UNAVAILABLE_MSG',
    'error_prefix'           => 'COM_SEAICHAT_ERROR_PREFIX',
    'error_generic'          => 'COM_SEAICHAT_ERROR_GENERIC',
];
$_fallbacks = [
    'status_online' => 'Online', 'chat_with_ai' => 'Chat with AI',
    'new_conversation' => 'New conversation', 'close' => 'Close', 'send' => 'Send',
    'gdpr_title' => 'Data Privacy Notice', 'gdpr_accept' => 'Accept', 'gdpr_decline' => 'Decline',
    'gdpr_unavailable_title' => 'Chat Unavailable',
    'gdpr_unavailable_msg' => 'You have declined the data privacy notice. The chat is not available without your consent. You can close and reopen the chat to try again.',
    'error_prefix' => 'Sorry:', 'error_generic' => 'Sorry, something went wrong.',
];
$_strings = [];
foreach ($_stringKeys as $jsKey => $langKey) {
    $overrideParam = 'aichat_str_' . $jsKey;
    $override = trim($p[$overrideParam] ?? '');
    if (!empty($override)) {
        $_strings[$jsKey] = $override;
    } else {
        $translated = $lang->_($langKey);
        $_strings[$jsKey] = ($translated !== $langKey) ? $translated : $_fallbacks[$jsKey];
    }
}

// Build chat config
$chatConfig = [
    'enabled'         => true,
    'welcome_message' => $p['aichat_welcome_message'] ?? 'Hi! How can I help you today?',
    'position'        => $p['aichat_widget_position'] ?? 'bottom-right',
    'color'           => $p['aichat_primary_color'] ?? '#2E486B',
    'max_messages'    => (int) ($p['aichat_max_messages'] ?? 10),
    'header_title'    => $p['aichat_header_title'] ?? 'AI Assistant',
    'placeholder_text'=> $p['aichat_placeholder_text'] ?? 'Type your question...',
    'contact_url'     => $p['aichat_contact_url'] ?? '',
    'contact_text'    => $p['aichat_contact_text'] ?? 'Contact Support',
    'contact_target'  => $p['aichat_contact_target'] ?? '_blank',
    'gdpr_enabled'    => $gdprEnabled,
    'gdpr_text'       => $gdprText,
    'gdpr_privacy_url'=> $p['aichat_gdpr_privacy_url'] ?? '',
    'avatar_url'      => !empty($p['aichat_avatar']) ? rtrim(Uri::root(), '/') . '/' . $p['aichat_avatar'] : '',
    'strings'         => $_strings,
    'token'           => Session::getFormToken(),
    'logged_in'       => !$user->guest,
    'base_url'        => rtrim(Uri::root(), '/') . '/',
];

// Module params
$displayMode = $params->get('display_mode', 'inline');
$chatHeight  = $params->get('chat_height', '500px');

// If inline mode, set the inline flag so the JS renders inside the module div
if ($displayMode === 'inline') {
    $chatConfig['inline'] = true;
}

require ModuleHelper::getLayoutPath('mod_seaichat', $params->get('layout', 'default'));
