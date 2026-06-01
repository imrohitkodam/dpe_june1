<?php


/**
 * @package     Joomla
 * @subpackage  mod_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

$mediaUrl = Uri::root() . 'media/com_seaichat';
$color = $chatConfig['color'] ?? '#2E486B';
$isInline = ($displayMode === 'inline');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
<link rel="stylesheet" href="<?php echo $mediaUrl; ?>/css/chat-widget.css?v=2.0.2c" />

<?php if ($color !== '#2E486B') : ?>
<style>:root { --se-chat-color: <?php echo htmlspecialchars($color); ?>; }</style>
<?php endif; ?>

<?php if ($isInline) : ?>
<!-- Inline chat panel rendered inside the module position -->
<div id="se-chat-inline" class="mod-seaichat-inline">
    <div id="se-chat-panel-inline" class="se-chat-panel-inline" style="min-height:<?php echo htmlspecialchars($chatHeight); ?>">
        <div id="se-chat-header">
            <div class="se-chat-header-info">
                <div class="se-chat-avatar">
                    <?php if (!empty($chatConfig['avatar_url'])) : ?>
                        <img src="<?php echo htmlspecialchars($chatConfig['avatar_url']); ?>" alt="Avatar" class="se-chat-avatar-img" />
                    <?php else : ?>
                        <i class="fa-solid fa-robot"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="se-chat-header-title"><?php echo htmlspecialchars($chatConfig['header_title'] ?? 'AI Assistant'); ?></div>
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
                <textarea id="se-chat-input" placeholder="<?php echo htmlspecialchars($chatConfig['placeholder_text'] ?? 'Type your question...'); ?>" rows="1"></textarea>
                <button id="se-chat-send" title="<?php echo htmlspecialchars($_strings['send']); ?>"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
            <?php if (!empty($chatConfig['contact_url'])) : ?>
                <div class="se-chat-footer">
                    <a href="<?php echo htmlspecialchars($chatConfig['contact_url']); ?>" target="<?php echo htmlspecialchars($chatConfig['contact_target']); ?>" class="se-chat-footer-link">
                        <i class="fa-solid fa-headset"></i> <?php echo htmlspecialchars($chatConfig['contact_text']); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.mod-seaichat-inline { width:100%; }
.mod-seaichat-inline .se-chat-panel-inline {
    width:100%; background:#fff;
    border-radius:16px; box-shadow:0 12px 48px rgba(0,0,0,0.1), 0 0 0 1px rgba(0,0,0,0.05);
    display:flex; flex-direction:column; overflow:hidden;
    font-family:'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
}
.mod-seaichat-inline .se-chat-panel-inline #se-chat-messages { flex:1; min-height:350px; }
.mod-seaichat-inline .se-chat-panel-inline #se-chat-input-area { display:block !important; }
<?php if ($gdprEnabled) : ?>
.mod-seaichat-inline .se-chat-panel-inline #se-chat-input-area { display:none !important; }
.mod-seaichat-inline .se-chat-panel-inline #se-chat-input-area.se-gdpr-passed { display:block !important; }
<?php endif; ?>
</style>
<?php endif; ?>

<?php // Both modes: output the config and load the JS ?>
<script>window.SE_CHAT_CONFIG = <?php echo json_encode($chatConfig); ?>; window.SE_CHAT_MODULE_ACTIVE = true;</script>
<script src="<?php echo $mediaUrl; ?>/js/chat-widget.js?v=2.0.2c" defer></script>
