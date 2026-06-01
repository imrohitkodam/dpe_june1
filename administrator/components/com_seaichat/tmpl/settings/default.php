<?php

/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$wa = $this->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_seaichat.admin', 'com_seaichat/admin.css');

$db = \Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
$query = $db->getQuery(true)
    ->select($db->quoteName('params'))
    ->from($db->quoteName('#__extensions'))
    ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
    ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
$db->setQuery($query);
$p = json_decode($db->loadResult(), true) ?: [];

$provider = $p['aichat_provider'] ?? 'anthropic';
$apiKey = trim($p['aichat_api_key'] ?? '');
$token = Session::getFormToken();

// Load published frontend menu items
$menuQuery = $db->getQuery(true)
    ->select('m.id, m.title, m.alias, m.level, m.menutype, mt.title AS menu_title')
    ->from($db->quoteName('#__menu', 'm'))
    ->join('LEFT', $db->quoteName('#__menu_types', 'mt') . ' ON mt.menutype = m.menutype')
    ->where($db->quoteName('m.client_id') . ' = 0')
    ->where($db->quoteName('m.published') . ' = 1')
    ->where($db->quoteName('m.level') . ' > 0')
    ->order('m.menutype ASC, m.lft ASC');
$db->setQuery($menuQuery);
$menuItems = $db->loadObjectList() ?: [];

$selectedMenuItems = $p['aichat_menu_items'] ?? '';
$selectedIds = array_filter(array_map('intval', explode(',', (string) $selectedMenuItems)));

$providerKeyUrls = [
    'anthropic' => ['url' => 'https://console.anthropic.com/settings/keys', 'label' => 'Anthropic Console'],
    'openai'    => ['url' => 'https://platform.openai.com/api-keys', 'label' => 'OpenAI Platform'],
    'gemini'    => ['url' => 'https://aistudio.google.com/apikey', 'label' => 'Google AI Studio'],
    'deepseek'  => ['url' => 'https://platform.deepseek.com/api_keys', 'label' => 'DeepSeek Platform'],
];

$models = [
    'anthropic' => ['claude-sonnet-4-20250514' => 'Claude Sonnet 4', 'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5'],
    'openai' => ['gpt-4o' => 'GPT-4o', 'gpt-4o-mini' => 'GPT-4o Mini', 'gpt-4.1-mini' => 'GPT-4.1 Mini', 'gpt-4.1-nano' => 'GPT-4.1 Nano'],
    'gemini' => ['gemini-2.5-pro' => 'Gemini 2.5 Pro', 'gemini-2.5-flash' => 'Gemini 2.5 Flash', 'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash Lite', 'gemini-2.0-flash' => 'Gemini 2.0 Flash'],
    'deepseek' => ['deepseek-chat' => 'DeepSeek Chat (V3)', 'deepseek-reasoner' => 'DeepSeek Reasoner (R1)'],
];

$currentModel = \SolarEclipse\Component\SeAiChat\Administrator\Helper\AiChatHelper::getModelForProviderStatic($provider, $p);

// Free / Pro gating
require_once JPATH_ADMINISTRATOR . '/components/com_seaichat/helpers/LicenseChecker.php';
$isPro      = \SeAiChatLicenseChecker::isPro();
$upgradeUrl = \SeAiChatLicenseChecker::upgradeUrl();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />

<style>
.se-settings-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; max-width:1000px; }
.se-settings-full { grid-column:1/-1; }
@media(max-width:768px) { .se-settings-grid { grid-template-columns:1fr; } }
.se-field { margin-bottom:16px; }
.se-field label { display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.03em; }
.se-field label i { margin-right:4px; color:#6b7280; }
.se-field .se-hint { font-size:11px; color:#9ca3af; margin-top:4px; }
.se-field input[type=text], .se-field input[type=number], .se-field select, .se-field textarea {
    width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; font-family:inherit; box-sizing:border-box; background:#fff;
}
.se-field textarea { resize:vertical; line-height:1.6; }
.se-toggle-group { display:flex; gap:0; border-radius:8px; overflow:hidden; border:1px solid #d1d5db; }
.se-toggle-group button { flex:1; padding:8px 16px; font-size:13px; font-weight:600; font-family:inherit; border:none; cursor:pointer; transition:all 0.15s; background:#f9fafb; color:#6b7280; }
.se-toggle-group button.active { background:#2E486B; color:#fff; }
.se-toggle-group button:hover:not(.active) { background:#f3f4f6; }
.se-menu-picker { max-height:280px; overflow-y:auto; border:1px solid #d1d5db; border-radius:8px; background:#fff; }
.se-menu-group-title { font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; padding:10px 14px 4px; background:#f9fafb; border-bottom:1px solid #f0f1f5; position:sticky; top:28px; z-index:1; }
.se-menu-item { display:flex; align-items:center; gap:8px; padding:7px 14px; cursor:pointer; transition:background 0.1s; font-size:13px; color:#374151; border-bottom:1px solid #f9fafb; }
.se-menu-item:hover { background:#f3f4f6; }
.se-menu-item input[type=checkbox] { accent-color:#2E486B; width:16px; height:16px; flex-shrink:0; cursor:pointer; }
.se-menu-toolbar { display:flex; gap:8px; padding:8px 14px; background:#f9fafb; border-bottom:1px solid #e5e7eb; position:sticky; top:0; z-index:2; }
.se-menu-toolbar button { background:none; border:none; color:#2E486B; font-size:12px; font-weight:600; font-family:inherit; cursor:pointer; padding:2px 6px; border-radius:4px; }
.se-menu-toolbar button:hover { background:#e8edf3; }
.se-menu-count { font-size:11px; color:#9ca3af; margin-left:auto; }
.se-avatar-upload-area { display:flex; align-items:center; gap:16px; }
.se-avatar-preview { width:64px; height:64px; border-radius:50%; background:#2E486B; color:#fff; display:flex; align-items:center; justify-content:center; font-size:28px; flex-shrink:0; overflow:hidden; border:2px solid #e5e7eb; }
.se-avatar-preview img { width:100%; height:100%; object-fit:cover; }
.se-avatar-controls { display:flex; gap:8px; flex-wrap:wrap; }
.se-avatar-upload-btn:hover { background:#e8edf3 !important; }
</style>

<div class="se-settings-grid">

    <!-- Chat Widget -->
    <div class="se-card se-settings-full">
        <div class="se-card-header"><h3 class="se-card-title"><i class="fa-solid fa-comments"></i> Chat Widget</h3></div>
        <div class="se-card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                <div>
                    <div class="se-field">
                        <label><i class="fa-solid fa-toggle-on"></i> Enable AI Chat</label>
                        <div class="se-toggle-group" data-param="aichat_enabled">
                            <button type="button" data-val="1" class="<?php echo !empty($p['aichat_enabled']) ? 'active' : ''; ?>">Yes</button>
                            <button type="button" data-val="0" class="<?php echo empty($p['aichat_enabled']) ? 'active' : ''; ?>">No</button>
                        </div>
                        <div class="se-hint">Master switch for the AI chatbot.</div>
                    </div>
                </div>
                <div>
                    <div class="se-field">
                        <label><i class="fa-solid fa-bars-staggered"></i> Show Widget on These Pages</label>
                        <div class="se-menu-picker" id="se-menu-picker">
                            <div class="se-menu-toolbar">
                                <button type="button" id="se-menu-all">Select All</button>
                                <button type="button" id="se-menu-none">Select None</button>
                                <span class="se-menu-count"><span id="se-menu-count-num">0</span> selected</span>
                            </div>
                            <?php
                            $currentMenuType = '';
                            foreach ($menuItems as $item) :
                                if ($item->menutype !== $currentMenuType) :
                                    $currentMenuType = $item->menutype;
                            ?>
                                <div class="se-menu-group-title"><?php echo htmlspecialchars($item->menu_title ?: $item->menutype); ?></div>
                            <?php endif;
                                $indent = str_repeat('&nbsp;&nbsp;&nbsp;', max(0, (int)$item->level - 1));
                                $dash = ((int)$item->level > 1) ? '&#x2514; ' : '';
                                $checked = in_array((int)$item->id, $selectedIds) ? 'checked' : '';
                            ?>
                                <label class="se-menu-item">
                                    <input type="checkbox" class="se-menu-cb" value="<?php echo (int)$item->id; ?>" <?php echo $checked; ?> />
                                    <span class="se-menu-label"><span class="se-menu-indent"><?php echo $indent . $dash; ?></span><?php echo htmlspecialchars($item->title); ?></span>
                                </label>
                            <?php endforeach; ?>
                            <?php if (empty($menuItems)) : ?>
                                <div style="padding:20px;text-align:center;color:#9ca3af;font-size:13px">No published menu items found.</div>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" id="se-menu-items-val" data-param="aichat_menu_items" value="<?php echo htmlspecialchars($selectedMenuItems); ?>" />
                        <div class="se-hint">Tick the pages where the floating chat bubble should appear. If none are selected, the widget will not appear on any page.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Provider & Model -->
    <div class="se-card">
        <div class="se-card-header"><h3 class="se-card-title"><i class="fa-solid fa-brain"></i> AI Provider</h3></div>
        <div class="se-card-body">
            <div class="se-field">
                <label><i class="fa-solid fa-server"></i> Provider</label>
                <select id="se-provider" data-param="aichat_provider">
                    <option value="anthropic" <?php echo $provider === 'anthropic' ? 'selected' : ''; ?>>Anthropic (Claude)</option>
                    <option value="openai" <?php echo $provider === 'openai' ? 'selected' : ''; ?>>OpenAI (GPT)</option>
                    <option value="gemini" <?php echo $provider === 'gemini' ? 'selected' : ''; ?>>Google (Gemini)</option>
                    <option value="deepseek" <?php echo $provider === 'deepseek' ? 'selected' : ''; ?>>DeepSeek</option>
                </select>
            </div>
            <div class="se-field">
                <label><i class="fa-solid fa-microchip"></i> Model</label>
                <?php foreach ($models as $prov => $modList) : ?>
                    <select id="se-model-<?php echo $prov; ?>" data-param="aichat_model_<?php echo $prov === 'anthropic' ? '' : $prov; ?>" class="se-model-select" style="<?php echo $prov !== $provider ? 'display:none' : ''; ?>" data-provider="<?php echo $prov; ?>">
                        <?php foreach ($modList as $val => $label) :
                            $isProModel = \SolarEclipse\Component\SeAiChat\Administrator\Helper\AiChatHelper::isProModel($prov, $val);
                        ?>
                            <option value="<?php echo $val; ?>"
                                <?php echo $currentModel === $val ? 'selected' : ''; ?>
                                <?php echo (!$isPro && $isProModel) ? 'disabled' : ''; ?>>
                                <?php echo $label; ?><?php echo $isProModel ? ' (Pro)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endforeach; ?>
                <?php if (!$isPro): ?>
                <div class="se-hint" style="margin-top:6px">
                    <i class="fa-solid fa-lock" style="color:#f59e0b"></i>
                    Premium models require a Pro license.
                    <a href="<?php echo $upgradeUrl; ?>" target="_blank" style="color:#2E486B;font-weight:600">Upgrade to Pro →</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- API Key -->
    <div class="se-card se-settings-full">
        <div class="se-card-header"><h3 class="se-card-title"><i class="fa-solid fa-key"></i> API Key</h3></div>
        <div class="se-card-body">
            <div class="se-field">
                <label><i class="fa-solid fa-key"></i> API Key</label>
                <div style="display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap">
                    <input type="text" id="se-api-key" placeholder="Paste your API key here..." style="flex:1;min-width:300px;font-family:monospace" value="<?php echo htmlspecialchars($apiKey); ?>" />
                    <button type="button" id="se-test-btn" class="se-btn se-btn-primary" style="padding:9px 18px;white-space:nowrap"><i class="fa-solid fa-vial" id="se-test-icon"></i> Test</button>
                    <button type="button" id="se-save-btn" class="se-btn se-btn-primary" style="padding:9px 18px;white-space:nowrap;background:#10b981"><i class="fa-solid fa-floppy-disk" id="se-save-icon"></i> Save Key</button>
                </div>
                <div class="se-hint" id="se-key-link">Get your key from <a href="<?php echo $providerKeyUrls[$provider]['url']; ?>" target="_blank" id="se-key-link-a"><?php echo $providerKeyUrls[$provider]['label']; ?> <i class="fa-solid fa-external-link" style="font-size:9px"></i></a></div>
                <div class="se-hint" style="margin-top:6px;color:#92400e;background:#fffbeb;padding:8px 12px;border-radius:6px;border:1px solid #fde68a"><i class="fa-solid fa-triangle-exclamation" style="margin-right:4px"></i> After you have successfully tested your connection, ensure you click the <strong>Save All Settings</strong> button at the bottom of the page.</div>
            </div>
            <div id="se-result" style="display:none;margin-top:4px;padding:12px 16px;border-radius:8px;font-size:13px;line-height:1.5"></div>
        </div>
    </div>

    <!-- Appearance -->
    <div class="se-card">
        <div class="se-card-header"><h3 class="se-card-title"><i class="fa-solid fa-palette"></i> Appearance</h3></div>
        <div class="se-card-body">
            <div class="se-field">
                <label><i class="fa-solid fa-image"></i> Bot Avatar</label>
                <div class="se-avatar-upload-area">
                    <div class="se-avatar-preview" id="se-avatar-preview">
                        <?php if (!empty($p['aichat_avatar'])) : ?>
                            <img src="<?php echo rtrim(\Joomla\CMS\Uri\Uri::root(), '/') . '/' . htmlspecialchars($p['aichat_avatar']); ?>" alt="Avatar" id="se-avatar-img" />
                        <?php else : ?>
                            <i class="fa-solid fa-robot" id="se-avatar-icon"></i>
                        <?php endif; ?>
                    </div>
                    <div class="se-avatar-controls">
                        <label class="se-btn se-btn-secondary se-avatar-upload-btn" style="padding:7px 14px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-size:12px">
                            <i class="fa-solid fa-upload"></i> Upload Image
                            <input type="file" id="se-avatar-file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" style="display:none" />
                        </label>
                        <button type="button" id="se-avatar-remove" class="se-btn se-btn-secondary" style="padding:7px 14px;font-size:12px;color:#dc2626;<?php echo empty($p['aichat_avatar']) ? 'display:none' : ''; ?>"><i class="fa-solid fa-trash"></i> Remove</button>
                    </div>
                </div>
                <div id="se-avatar-result" style="display:none;margin-top:6px;padding:8px 12px;border-radius:6px;font-size:12px"></div>
                <div class="se-hint">Upload a custom avatar image (JPG, PNG, GIF, WebP, SVG — max 2 MB). The default robot icon is used when no avatar is set.</div>
            </div>
            <div class="se-field">
                <label><i class="fa-solid fa-location-dot"></i> Widget Position</label>
                <select data-param="aichat_widget_position">
                    <option value="bottom-right" <?php echo ($p['aichat_widget_position'] ?? 'bottom-right') === 'bottom-right' ? 'selected' : ''; ?>>Bottom Right</option>
                    <option value="bottom-left" <?php echo ($p['aichat_widget_position'] ?? '') === 'bottom-left' ? 'selected' : ''; ?>>Bottom Left</option>
                </select>
            </div>
            <div class="se-field">
                <label><i class="fa-solid fa-droplet"></i> Widget Colour</label>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="color" id="se-color-picker" value="<?php echo htmlspecialchars($p['aichat_primary_color'] ?? '#2E486B'); ?>" style="width:40px;height:36px;border:1px solid #d1d5db;border-radius:6px;padding:2px;cursor:pointer" />
                    <input type="text" data-param="aichat_primary_color" value="<?php echo htmlspecialchars($p['aichat_primary_color'] ?? '#2E486B'); ?>" maxlength="7" style="width:100px;font-family:monospace" />
                </div>
            </div>
        </div>
    </div>

    <!-- Behaviour -->
    <div class="se-card">
        <div class="se-card-header"><h3 class="se-card-title"><i class="fa-solid fa-sliders"></i> Behaviour</h3></div>
        <div class="se-card-body">
            <div class="se-field">
                <label><i class="fa-solid fa-arrow-right-to-bracket"></i> Max Messages per Conversation</label>
                <input type="number" data-param="aichat_max_messages" value="<?php echo (int)($p['aichat_max_messages'] ?? 10); ?>" min="2" max="50" style="width:100px" />
                <div class="se-hint">After this many messages, suggest starting a new conversation.</div>
            </div>
            <div class="se-field">
                <label><i class="fa-solid fa-id-badge"></i> Chat Header Title</label>
                <input type="text" data-param="aichat_header_title" value="<?php echo htmlspecialchars($p['aichat_header_title'] ?? 'AI Assistant'); ?>" placeholder="AI Assistant" />
                <div class="se-hint">The name shown in the chat widget header.</div>
            </div>
            <div class="se-field">
                <label><i class="fa-solid fa-hand-wave"></i> Welcome Message</label>
                <input type="text" data-param="aichat_welcome_message" value="<?php echo htmlspecialchars($p['aichat_welcome_message'] ?? "Hi! I'm your AI assistant. Ask me anything!"); ?>" />
            </div>
            <div class="se-field">
                <label><i class="fa-solid fa-keyboard"></i> Input Placeholder Text</label>
                <input type="text" data-param="aichat_placeholder_text" value="<?php echo htmlspecialchars($p['aichat_placeholder_text'] ?? 'Type your question...'); ?>" placeholder="Type your question..." />
                <div class="se-hint">The placeholder text shown in the chat input box before the user starts typing.</div>
            </div>
        </div>
    </div>

    <!-- Contact Link -->
    <div class="se-card">
        <div class="se-card-header"><h3 class="se-card-title"><i class="fa-solid fa-headset"></i> Contact Link</h3></div>
        <div class="se-card-body">
            <div class="se-field">
                <label><i class="fa-solid fa-link"></i> Contact URL</label>
                <input type="text" data-param="aichat_contact_url" value="<?php echo htmlspecialchars($p['aichat_contact_url'] ?? ''); ?>" placeholder="https://yoursite.com/contact-us" />
                <div class="se-hint">URL where users can reach a human. Leave empty to hide the link.</div>
            </div>
            <div class="se-field">
                <label><i class="fa-solid fa-pen"></i> Link Text</label>
                <input type="text" data-param="aichat_contact_text" value="<?php echo htmlspecialchars($p['aichat_contact_text'] ?? 'Contact Support'); ?>" placeholder="Contact Support" />
            </div>
            <div class="se-field">
                <label><i class="fa-solid fa-arrow-up-right-from-square"></i> Open In</label>
                <select data-param="aichat_contact_target">
                    <option value="_blank" <?php echo ($p['aichat_contact_target'] ?? '_blank') === '_blank' ? 'selected' : ''; ?>>New Window</option>
                    <option value="_self" <?php echo ($p['aichat_contact_target'] ?? '') === '_self' ? 'selected' : ''; ?>>Same Window</option>
                </select>
            </div>
        </div>
    </div>

    <!-- GDPR Compliance -->
    <div class="se-card se-settings-full">
        <div class="se-card-header"><h3 class="se-card-title"><i class="fa-solid fa-shield-halved"></i> GDPR Compliance</h3></div>
        <div class="se-card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                <div>
                    <div class="se-field">
                        <label><i class="fa-solid fa-toggle-on"></i> Enable GDPR Consent</label>
                        <div class="se-toggle-group" data-param="aichat_gdpr_enabled">
                            <button type="button" data-val="1" class="<?php echo !empty($p['aichat_gdpr_enabled']) ? 'active' : ''; ?>">Yes</button>
                            <button type="button" data-val="0" class="<?php echo empty($p['aichat_gdpr_enabled']) ? 'active' : ''; ?>">No</button>
                        </div>
                        <div class="se-hint">When enabled, users must accept a GDPR consent notice before using the chat. The notice will automatically display the name of the AI provider you have configured.</div>
                    </div>
                </div>
                <div>
                    <div class="se-field">
                        <label><i class="fa-solid fa-link"></i> Privacy Policy URL</label>
                        <input type="text" data-param="aichat_gdpr_privacy_url" value="<?php echo htmlspecialchars($p['aichat_gdpr_privacy_url'] ?? ''); ?>" placeholder="https://yoursite.com/privacy-policy" />
                        <div class="se-hint">If provided, the words "privacy policy" in the consent text will link to this URL. Leave empty to show plain text.</div>
                    </div>
                </div>
            </div>
            <div class="se-field">
                <label><i class="fa-solid fa-file-lines"></i> GDPR Consent Text</label>
                <textarea data-param="aichat_gdpr_text" rows="4"><?php echo htmlspecialchars($p['aichat_gdpr_text'] ?? 'This chatbot uses {ai_provider} to process your requests. Your inputs and conversation history are transmitted to and processed by {ai_provider}. No personal data is stored. Data is transmitted on the basis of your consent (Art. 6 (1) (a) GDPR). You may withdraw your consent at any time by closing the chat. For further information, please refer to our privacy policy.'); ?></textarea>
                <div class="se-hint">Use <code>{ai_provider}</code> as a placeholder — it will be automatically replaced with the name of your configured AI provider (e.g. Anthropic Claude, OpenAI GPT). You can customise this text to suit your needs.</div>
            </div>
        </div>
    </div>

    <!-- Call to Actions -->
    <div class="se-card se-settings-full">
        <div class="se-card-header"><h3 class="se-card-title"><i class="fa-solid fa-bullhorn"></i> Call to Actions</h3></div>
        <div class="se-card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                <div>
                    <div class="se-field">
                        <label><i class="fa-solid fa-toggle-on"></i> Enable CTA Buttons</label>
                        <div class="se-toggle-group" data-param="aichat_cta_enabled">
                            <button type="button" data-val="1" class="<?php echo ($p['aichat_cta_enabled'] ?? '1') === '1' ? 'active' : ''; ?>">Yes</button>
                            <button type="button" data-val="0" class="<?php echo ($p['aichat_cta_enabled'] ?? '1') === '0' ? 'active' : ''; ?>">No</button>
                        </div>
                        <div class="se-hint">Show keyword-triggered CTA buttons in chat responses. <a href="<?php echo \Joomla\CMS\Router\Route::_('index.php?option=com_seaichat&view=calltoactions'); ?>" style="color:#2E486B;font-weight:600">Manage CTAs <i class="fa-solid fa-external-link" style="font-size:9px"></i></a></div>
                    </div>
                    <div class="se-field">
                        <label><i class="fa-solid fa-toggle-on"></i> Enable Source Links</label>
                        <div class="se-toggle-group" data-param="aichat_sources_enabled">
                            <button type="button" data-val="1" class="<?php echo ($p['aichat_sources_enabled'] ?? '1') === '1' ? 'active' : ''; ?>">Yes</button>
                            <button type="button" data-val="0" class="<?php echo ($p['aichat_sources_enabled'] ?? '1') === '0' ? 'active' : ''; ?>">No</button>
                        </div>
                        <div class="se-hint">Show "Read more" links to the documentation pages the AI used to answer.</div>
                    </div>
                    <div style="display:flex;gap:16px">
                        <div class="se-field" style="flex:1">
                            <label><i class="fa-solid fa-hashtag"></i> Max CTA Buttons</label>
                            <input type="number" data-param="aichat_cta_max" value="<?php echo (int)($p['aichat_cta_max'] ?? 3); ?>" min="1" max="10" style="width:80px" />
                            <div class="se-hint">Per response.</div>
                        </div>
                        <div class="se-field" style="flex:1">
                            <label><i class="fa-solid fa-hashtag"></i> Max Source Links</label>
                            <input type="number" data-param="aichat_sources_max" value="<?php echo (int)($p['aichat_sources_max'] ?? 3); ?>" min="1" max="10" style="width:80px" />
                            <div class="se-hint">Per response.</div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="se-field">
                        <label><i class="fa-solid fa-fill-drip"></i> Button Background</label>
                        <div style="display:flex;gap:8px;align-items:center">
                            <input type="color" id="se-cta-bg-picker" value="<?php echo htmlspecialchars($p['aichat_cta_bg_color'] ?? ($p['aichat_primary_color'] ?? '#2E486B')); ?>" style="width:40px;height:36px;border:1px solid #d1d5db;border-radius:6px;padding:2px;cursor:pointer" />
                            <input type="text" data-param="aichat_cta_bg_color" id="se-cta-bg-text" value="<?php echo htmlspecialchars($p['aichat_cta_bg_color'] ?? ''); ?>" maxlength="7" style="width:100px;font-family:monospace" placeholder="<?php echo htmlspecialchars($p['aichat_primary_color'] ?? '#2E486B'); ?>" />
                        </div>
                        <div class="se-hint">Leave empty to use the widget colour.</div>
                    </div>
                    <div class="se-field">
                        <label><i class="fa-solid fa-font"></i> Button Text Colour</label>
                        <div style="display:flex;gap:8px;align-items:center">
                            <input type="color" id="se-cta-text-picker" value="<?php echo htmlspecialchars($p['aichat_cta_text_color'] ?? '#ffffff'); ?>" style="width:40px;height:36px;border:1px solid #d1d5db;border-radius:6px;padding:2px;cursor:pointer" />
                            <input type="text" data-param="aichat_cta_text_color" id="se-cta-text-text" value="<?php echo htmlspecialchars($p['aichat_cta_text_color'] ?? ''); ?>" maxlength="7" style="width:100px;font-family:monospace" placeholder="#ffffff" />
                        </div>
                        <div class="se-hint">Colour of the text and icon on CTA buttons.</div>
                    </div>
                    <div style="margin-top:16px">
                        <div style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.03em;margin-bottom:8px">Preview</div>
                        <div style="background:#f9fafb;border-radius:8px;padding:12px">
                            <a href="#" id="se-cta-preview-btn" onclick="return false" style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:<?php echo htmlspecialchars($p['aichat_cta_bg_color'] ?? ($p['aichat_primary_color'] ?? '#2E486B')); ?>;color:<?php echo htmlspecialchars($p['aichat_cta_text_color'] ?? '#ffffff'); ?>;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;cursor:default">
                                <i class="fa-solid fa-ticket"></i> Open a Ticket
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Widget Text Overrides -->
    <div class="se-card se-settings-full">
        <div class="se-card-header"><h3 class="se-card-title"><i class="fa-solid fa-language"></i> Widget Text Overrides</h3></div>
        <div class="se-card-body">
            <div class="se-hint" style="margin-bottom:16px;padding:10px 14px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;color:#0369a1">
                <i class="fa-solid fa-circle-info" style="margin-right:4px"></i>
                All widget text is automatically translated via Joomla language files (EN, DE, FR, ES, IT, NL, AR). Use the fields below only if you want to <strong>override</strong> a specific string regardless of language. Leave blank to use the default translation.
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px 24px">
                <div class="se-field">
                    <label><i class="fa-solid fa-circle-dot"></i> Status Text</label>
                    <input type="text" data-param="aichat_str_status_online" value="<?php echo htmlspecialchars($p['aichat_str_status_online'] ?? ''); ?>" placeholder="Online" />
                </div>
                <div class="se-field">
                    <label><i class="fa-solid fa-comments"></i> Bubble Tooltip</label>
                    <input type="text" data-param="aichat_str_chat_with_ai" value="<?php echo htmlspecialchars($p['aichat_str_chat_with_ai'] ?? ''); ?>" placeholder="Chat with AI" />
                </div>
                <div class="se-field">
                    <label><i class="fa-solid fa-rotate-right"></i> New Conversation</label>
                    <input type="text" data-param="aichat_str_new_conversation" value="<?php echo htmlspecialchars($p['aichat_str_new_conversation'] ?? ''); ?>" placeholder="New conversation" />
                </div>
                <div class="se-field">
                    <label><i class="fa-solid fa-xmark"></i> Close</label>
                    <input type="text" data-param="aichat_str_close" value="<?php echo htmlspecialchars($p['aichat_str_close'] ?? ''); ?>" placeholder="Close" />
                </div>
                <div class="se-field">
                    <label><i class="fa-solid fa-paper-plane"></i> Send</label>
                    <input type="text" data-param="aichat_str_send" value="<?php echo htmlspecialchars($p['aichat_str_send'] ?? ''); ?>" placeholder="Send" />
                </div>
                <div class="se-field">
                    <label><i class="fa-solid fa-shield-halved"></i> GDPR Title</label>
                    <input type="text" data-param="aichat_str_gdpr_title" value="<?php echo htmlspecialchars($p['aichat_str_gdpr_title'] ?? ''); ?>" placeholder="Data Privacy Notice" />
                </div>
                <div class="se-field">
                    <label><i class="fa-solid fa-check"></i> GDPR Accept Button</label>
                    <input type="text" data-param="aichat_str_gdpr_accept" value="<?php echo htmlspecialchars($p['aichat_str_gdpr_accept'] ?? ''); ?>" placeholder="Accept" />
                </div>
                <div class="se-field">
                    <label><i class="fa-solid fa-xmark"></i> GDPR Decline Button</label>
                    <input type="text" data-param="aichat_str_gdpr_decline" value="<?php echo htmlspecialchars($p['aichat_str_gdpr_decline'] ?? ''); ?>" placeholder="Decline" />
                </div>
                <div class="se-field">
                    <label><i class="fa-solid fa-ban"></i> Chat Unavailable Title</label>
                    <input type="text" data-param="aichat_str_gdpr_unavailable_title" value="<?php echo htmlspecialchars($p['aichat_str_gdpr_unavailable_title'] ?? ''); ?>" placeholder="Chat Unavailable" />
                </div>
                <div class="se-field">
                    <label><i class="fa-solid fa-triangle-exclamation"></i> Error Prefix</label>
                    <input type="text" data-param="aichat_str_error_prefix" value="<?php echo htmlspecialchars($p['aichat_str_error_prefix'] ?? ''); ?>" placeholder="Sorry:" />
                </div>
            </div>
            <div class="se-field" style="margin-top:8px">
                <label><i class="fa-solid fa-ban"></i> Chat Unavailable Message</label>
                <textarea data-param="aichat_str_gdpr_unavailable_msg" rows="2" placeholder="You have declined the data privacy notice. The chat is not available without your consent. You can refresh the chat above to try again."><?php echo htmlspecialchars($p['aichat_str_gdpr_unavailable_msg'] ?? ''); ?></textarea>
            </div>
            <div class="se-field">
                <label><i class="fa-solid fa-triangle-exclamation"></i> Generic Error Message</label>
                <input type="text" data-param="aichat_str_error_generic" value="<?php echo htmlspecialchars($p['aichat_str_error_generic'] ?? ''); ?>" placeholder="Sorry, something went wrong." />
            </div>
        </div>
    </div>

    <!-- System Prompt -->
    <div class="se-card se-settings-full">
        <div class="se-card-header">
            <h3 class="se-card-title">
                <i class="fa-solid fa-terminal"></i> System Prompt
                <?php if (!$isPro): ?>
                <span style="font-size:12px;font-weight:500;color:#f59e0b;margin-left:8px"><i class="fa-solid fa-lock"></i> Pro</span>
                <?php endif; ?>
            </h3>
        </div>
        <div class="se-card-body">
            <div class="se-field">
                <label><i class="fa-solid fa-robot"></i> AI Instructions</label>
                <textarea data-param="aichat_system_prompt" rows="5"
                    <?php echo !$isPro ? 'readonly style="opacity:0.6;cursor:not-allowed;background:#f9fafb"' : ''; ?>
                ><?php echo htmlspecialchars($p['aichat_system_prompt'] ?? 'You are a helpful AI assistant. Answer questions based on the provided documentation. If you cannot find the answer, say so honestly. Be concise, friendly and professional. Do not make up information.'); ?></textarea>
                <?php if ($isPro): ?>
                <div class="se-hint">Instructions given to the AI. Customise its personality and behaviour.</div>
                <?php else: ?>
                <div class="se-hint" style="color:#92400e">
                    <i class="fa-solid fa-lock" style="color:#f59e0b"></i>
                    Custom system prompts require a Pro license.
                    <a href="<?php echo $upgradeUrl; ?>" target="_blank" style="color:#2E486B;font-weight:600">Upgrade to Pro →</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Save All -->
    <div class="se-settings-full" style="text-align:right">
        <button type="button" id="se-save-all" class="se-btn se-btn-primary" style="padding:12px 32px;font-size:14px"><i class="fa-solid fa-floppy-disk" id="se-save-all-icon"></i> Save All Settings</button>
        <div id="se-save-all-result" style="display:none;margin-top:10px;padding:12px 16px;border-radius:8px;font-size:13px;line-height:1.5;text-align:left"></div>
    </div>

    <!-- Quick Links -->
    <div class="se-card se-settings-full">
        <div class="se-card-header"><h3 class="se-card-title"><i class="fa-solid fa-link"></i> Quick Links</h3></div>
        <div class="se-card-body">
            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <a href="<?php echo Route::_('index.php?option=com_seaichat&view=knowledgebases'); ?>" class="se-btn se-btn-secondary" style="padding:10px 18px"><i class="fa-solid fa-book-open"></i> Knowledge Base</a>
                <a href="<?php echo Route::_('index.php?option=com_seaichat&view=chatlogs'); ?>" class="se-btn se-btn-secondary" style="padding:10px 18px"><i class="fa-solid fa-comments"></i> Chat Logs</a>
                <a href="<?php echo Route::_('index.php?option=com_seaichat&view=dashboard'); ?>" class="se-btn se-btn-secondary" style="padding:10px 18px"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var token = '<?php echo $token; ?>';
    var result = document.getElementById('se-result');
    var keyUrls = <?php echo json_encode($providerKeyUrls); ?>;

    function showResult(el, success, html) {
        el.style.display = 'block';
        el.style.background = success ? '#ecfdf5' : '#fef2f2';
        el.style.border = '1px solid ' + (success ? '#a7f3d0' : '#fecaca');
        el.style.color = success ? '#065f46' : '#991b1b';
        el.innerHTML = html;
    }

    // Safe JSON parse — handles HTML error responses
    function safeJson(response) {
        return response.text().then(function(text) {
            try {
                return JSON.parse(text);
            } catch(e) {
                // Response was not JSON (probably an HTML error page)
                var msg = text.substring(0, 300).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                return { success: false, error: 'Server returned non-JSON response: ' + (msg || 'Empty response') };
            }
        });
    }

    // Toggle groups
    document.querySelectorAll('.se-toggle-group').forEach(function(group) {
        group.querySelectorAll('button').forEach(function(btn) {
            btn.addEventListener('click', function() {
                group.querySelectorAll('button').forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
            });
        });
    });

    // Menu item picker
    var menuHidden = document.getElementById('se-menu-items-val');
    var menuCheckboxes = document.querySelectorAll('.se-menu-cb');
    var menuCountEl = document.getElementById('se-menu-count-num');
    function updateMenuValue() {
        var ids = [];
        menuCheckboxes.forEach(function(cb) { if (cb.checked) ids.push(cb.value); });
        menuHidden.value = ids.join(',');
        menuCountEl.textContent = ids.length ? ids.length : 'None (widget hidden)';
    }
    menuCheckboxes.forEach(function(cb) { cb.addEventListener('change', updateMenuValue); });
    document.getElementById('se-menu-all').addEventListener('click', function() { menuCheckboxes.forEach(function(cb) { cb.checked = true; }); updateMenuValue(); });
    document.getElementById('se-menu-none').addEventListener('click', function() { menuCheckboxes.forEach(function(cb) { cb.checked = false; }); updateMenuValue(); });
    updateMenuValue();

    // Provider change
    document.getElementById('se-provider').addEventListener('change', function() {
        var prov = this.value;
        document.querySelectorAll('.se-model-select').forEach(function(s) { s.style.display = s.getAttribute('data-provider') === prov ? '' : 'none'; });
        var linkA = document.getElementById('se-key-link-a');
        if (keyUrls[prov]) { linkA.href = keyUrls[prov].url; linkA.textContent = keyUrls[prov].label + ' '; }
    });

    // Colour picker sync
    var colorPicker = document.getElementById('se-color-picker');
    var colorText = document.querySelector('[data-param="aichat_primary_color"]');
    colorPicker.addEventListener('input', function() { colorText.value = this.value; });
    colorText.addEventListener('input', function() { if (/^#[0-9a-fA-F]{6}$/.test(this.value)) colorPicker.value = this.value; });

    // CTA colour pickers sync + live preview
    var ctaBgPicker = document.getElementById('se-cta-bg-picker');
    var ctaBgText = document.getElementById('se-cta-bg-text');
    var ctaTextPicker = document.getElementById('se-cta-text-picker');
    var ctaTextText = document.getElementById('se-cta-text-text');
    var ctaPreview = document.getElementById('se-cta-preview-btn');
    function updateCtaPreview() {
        var bg = ctaBgText.value || colorText.value || '#2E486B';
        var fg = ctaTextText.value || '#ffffff';
        ctaPreview.style.background = bg;
        ctaPreview.style.color = fg;
    }
    ctaBgPicker.addEventListener('input', function() { ctaBgText.value = this.value; updateCtaPreview(); });
    ctaBgText.addEventListener('input', function() { if (/^#[0-9a-fA-F]{6}$/.test(this.value)) ctaBgPicker.value = this.value; updateCtaPreview(); });
    ctaTextPicker.addEventListener('input', function() { ctaTextText.value = this.value; updateCtaPreview(); });
    ctaTextText.addEventListener('input', function() { if (/^#[0-9a-fA-F]{6}$/.test(this.value)) ctaTextPicker.value = this.value; updateCtaPreview(); });

    // Test API Key
    document.getElementById('se-test-btn').addEventListener('click', function() {
        var btn = this, icon = document.getElementById('se-test-icon');
        var key = document.getElementById('se-api-key').value.trim();
        if (!key) { showResult(result, false, '<i class="fa-solid fa-circle-xmark" style="margin-right:6px"></i>Please paste an API key first.'); return; }
        btn.disabled = true; icon.className = 'fa-solid fa-spinner fa-spin'; result.style.display = 'none';
        var prov = document.getElementById('se-provider').value;
        var modelSelect = document.querySelector('.se-model-select[data-provider="' + prov + '"]');
        var fd = new FormData(); fd.append('api_key', key); fd.append('provider', prov); fd.append('model', modelSelect ? modelSelect.value : ''); fd.append(token, '1');
        fetch('index.php?option=com_seaichat&task=ajax.testdirect&format=raw', { method:'POST', body:fd, credentials:'same-origin' })
        .then(safeJson).then(function(d) {
            btn.disabled = false; icon.className = 'fa-solid fa-vial';
            showResult(result, d.success, d.success ? '<i class="fa-solid fa-circle-check" style="margin-right:6px"></i><strong>Connection successful!</strong> Provider: '+(d.provider||'')+' &middot; Model: '+(d.model||'') : '<i class="fa-solid fa-circle-xmark" style="margin-right:6px"></i><strong>Failed.</strong> '+(d.error||'Unknown error'));
        }).catch(function(e) { btn.disabled=false; icon.className='fa-solid fa-vial'; showResult(result,false,'<i class="fa-solid fa-circle-xmark" style="margin-right:6px"></i>Request error: '+e.message); });
    });

    // Save Key
    document.getElementById('se-save-btn').addEventListener('click', function() {
        var btn = this, icon = document.getElementById('se-save-icon');
        var key = document.getElementById('se-api-key').value.trim();
        if (!key) { showResult(result, false, 'Please paste an API key first.'); return; }
        btn.disabled = true; icon.className = 'fa-solid fa-spinner fa-spin';
        var fd = new FormData(); fd.append('api_key', key); fd.append(token, '1');
        fetch('index.php?option=com_seaichat&task=ajax.savekey&format=raw', { method:'POST', body:fd, credentials:'same-origin' })
        .then(safeJson).then(function(d) {
            btn.disabled = false; icon.className = 'fa-solid fa-floppy-disk';
            showResult(result, d.success, d.success ? '<i class="fa-solid fa-circle-check" style="margin-right:6px"></i><strong>API key saved!</strong>' : '<i class="fa-solid fa-circle-xmark" style="margin-right:6px"></i>'+(d.error||'Failed to save'));
        }).catch(function(e) { btn.disabled=false; icon.className='fa-solid fa-floppy-disk'; showResult(result,false,'Request error: '+e.message); });
    });

    // Save All Settings
    document.getElementById('se-save-all').addEventListener('click', function() {
        var btn = this, icon = document.getElementById('se-save-all-icon');
        var res = document.getElementById('se-save-all-result');
        btn.disabled = true; icon.className = 'fa-solid fa-spinner fa-spin';
        var settings = {};
        document.querySelectorAll('[data-param]').forEach(function(el) {
            var key = el.getAttribute('data-param');
            if (el.classList.contains('se-model-select') && el.style.display === 'none') return;
            if (key === 'aichat_model_') key = 'aichat_model';
            settings[key] = el.value;
        });
        document.querySelectorAll('.se-toggle-group').forEach(function(group) {
            var key = group.getAttribute('data-param');
            var active = group.querySelector('button.active');
            if (active) settings[key] = active.getAttribute('data-val');
        });
        var fd = new FormData(); fd.append('settings', JSON.stringify(settings)); fd.append(token, '1');
        fetch('index.php?option=com_seaichat&task=ajax.savesettings&format=raw', { method:'POST', body:fd, credentials:'same-origin' })
        .then(safeJson).then(function(d) {
            btn.disabled = false; icon.className = 'fa-solid fa-floppy-disk';
            showResult(res, d.success, d.success ? '<i class="fa-solid fa-circle-check" style="margin-right:6px"></i><strong>All settings saved!</strong>' : '<i class="fa-solid fa-circle-xmark" style="margin-right:6px"></i>'+(d.error||'Failed to save'));
        }).catch(function(e) { btn.disabled=false; icon.className='fa-solid fa-floppy-disk'; showResult(res,false,'Request error: '+e.message); });
    });

    // Avatar Upload
    var avatarResult = document.getElementById('se-avatar-result');
    document.getElementById('se-avatar-file').addEventListener('change', function() {
        var file = this.files[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) { showResult(avatarResult, false, 'File too large. Maximum size is 2 MB.'); return; }
        var fd = new FormData(); fd.append('avatar_file', file); fd.append(token, '1');
        showResult(avatarResult, true, '<i class="fa-solid fa-spinner fa-spin" style="margin-right:4px"></i> Uploading...');
        fetch('index.php?option=com_seaichat&task=ajax.uploadavatar&format=raw', { method:'POST', body:fd, credentials:'same-origin' })
        .then(safeJson).then(function(d) {
            if (d.success) {
                showResult(avatarResult, true, '<i class="fa-solid fa-circle-check" style="margin-right:4px"></i> Avatar uploaded!');
                var preview = document.getElementById('se-avatar-preview');
                var baseUrl = '<?php echo rtrim(\Joomla\CMS\Uri\Uri::root(), '/'); ?>/';
                preview.innerHTML = '<img src="' + baseUrl + d.avatar_url + '?t=' + Date.now() + '" alt="Avatar" id="se-avatar-img" />';
                document.getElementById('se-avatar-remove').style.display = '';
            } else {
                showResult(avatarResult, false, '<i class="fa-solid fa-circle-xmark" style="margin-right:4px"></i> ' + (d.error || 'Upload failed'));
            }
        }).catch(function(e) { showResult(avatarResult, false, 'Request error: ' + e.message); });
        this.value = '';
    });

    // Avatar Remove
    document.getElementById('se-avatar-remove').addEventListener('click', function() {
        if (!confirm('Remove the custom avatar and revert to the default robot icon?')) return;
        var fd = new FormData(); fd.append(token, '1');
        fetch('index.php?option=com_seaichat&task=ajax.removeavatar&format=raw', { method:'POST', body:fd, credentials:'same-origin' })
        .then(safeJson).then(function(d) {
            if (d.success) {
                showResult(avatarResult, true, '<i class="fa-solid fa-circle-check" style="margin-right:4px"></i> Avatar removed.');
                document.getElementById('se-avatar-preview').innerHTML = '<i class="fa-solid fa-robot" id="se-avatar-icon"></i>';
                document.getElementById('se-avatar-remove').style.display = 'none';
            } else {
                showResult(avatarResult, false, '<i class="fa-solid fa-circle-xmark" style="margin-right:4px"></i> ' + (d.error || 'Failed'));
            }
        }).catch(function(e) { showResult(avatarResult, false, 'Request error: ' + e.message); });
    });
});
</script>
