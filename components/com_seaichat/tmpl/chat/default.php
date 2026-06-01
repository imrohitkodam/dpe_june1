<?php

/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;

$wa = $this->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_seaichat.chat', 'com_seaichat/chat-widget.css');

$user = Factory::getApplication()->getIdentity();

// Read params directly from DB to avoid Joomla filtering the API key
$db = Factory::getContainer()->get('DatabaseDriver');
$query = $db->getQuery(true)
    ->select($db->quoteName('params'))
    ->from($db->quoteName('#__extensions'))
    ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
    ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
$db->setQuery($query);
$p = json_decode($db->loadResult(), true) ?: [];

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
    'gdpr_unavailable_msg' => 'You have declined the data privacy notice. The chat is not available without your consent. You can refresh the chat above to try again.',
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

$config = [
    'enabled'         => true,
    'welcome_message' => $p['aichat_welcome_message'] ?? 'Hi! How can I help you today?',
    'position'        => $p['aichat_widget_position'] ?? 'bottom-right',
    'color'           => $p['aichat_primary_color'] ?? '#2E486B',
    'max_messages'    => (int)($p['aichat_max_messages'] ?? 10),
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
    'inline'          => true,
];

$color = $config['color'];
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />

<?php if ($color !== '#2E486B') : ?>
<style>:root { --se-chat-color: <?php echo htmlspecialchars($color); ?>; }</style>
<?php endif; ?>

<div id="se-chat-inline" class="se-chat-inline-container">
    <div id="se-chat-panel-inline" class="se-chat-panel-inline">
        <div id="se-chat-header">
            <div class="se-chat-header-info">
                <div class="se-chat-avatar">
                    <?php if (!empty($p['aichat_avatar'])) : ?>
                        <img src="<?php echo rtrim(Uri::root(), '/') . '/' . htmlspecialchars($p['aichat_avatar']); ?>" alt="Avatar" class="se-chat-avatar-img" />
                    <?php else : ?>
                        <i class="fa-solid fa-robot"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="se-chat-header-title"><?php echo htmlspecialchars($config['header_title']); ?></div>
                    <div class="se-chat-header-status"><span class="se-chat-status-dot"></span> <?php echo htmlspecialchars($_strings['status_online']); ?></div>
                </div>
            </div>
            <button id="se-chat-reset" title="<?php echo htmlspecialchars($_strings['new_conversation']); ?>" style="background:rgba(255,255,255,0.15);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;transition:background 0.2s">
                <i class="fa-solid fa-rotate-right"></i>
            </button>
        </div>
        <div id="se-chat-messages"></div>
        <div id="se-chat-input-area">
            <div class="se-chat-input-wrap">
                <textarea id="se-chat-input" placeholder="<?php echo htmlspecialchars($config['placeholder_text']); ?>" rows="1"></textarea>
                <button id="se-chat-send" title="<?php echo htmlspecialchars($_strings['send']); ?>"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
            <?php if (!empty($config['contact_url'])) : ?>
                <div class="se-chat-footer">
                    <a href="<?php echo htmlspecialchars($config['contact_url']); ?>" target="<?php echo htmlspecialchars($config['contact_target']); ?>" class="se-chat-footer-link">
                        <i class="fa-solid fa-headset"></i> <?php echo htmlspecialchars($config['contact_text']); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.se-chat-inline-container { max-width:800px; margin:0 auto; }
.se-chat-panel-inline {
    width:100%; min-height:500px; max-height:calc(100vh - 200px); background:#fff;
    border-radius:16px; box-shadow:0 12px 48px rgba(0,0,0,0.1), 0 0 0 1px rgba(0,0,0,0.05);
    display:flex; flex-direction:column; overflow:hidden;
    font-family:'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
}
.se-chat-panel-inline #se-chat-messages { flex:1; min-height:400px; }
.se-chat-panel-inline #se-chat-input-area { display:block !important; }
<?php if ($gdprEnabled) : ?>
.se-chat-panel-inline #se-chat-input-area { display:none !important; }
.se-chat-panel-inline #se-chat-input-area.se-gdpr-passed { display:block !important; }
<?php endif; ?>
</style>

<script>window.SE_CHAT_CONFIG = <?php echo json_encode($config); ?>;</script>
<script src="<?php echo Uri::root(); ?>media/com_seaichat/js/chat-widget.js?v=2.0.2c" defer></script>
