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
use Joomla\CMS\HTML\HTMLHelper;

$wa = $this->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_seaichat.admin', 'com_seaichat/admin.css');

$stats = $this->stats;
$recentChats = $this->recentChats;
$diag = $this->diagnostics;
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />

<div class="se-dashboard">
    <div class="se-dashboard-header">
        <div class="se-brand">
            <div class="se-brand-icon">
                <i class="fa-solid fa-robot"></i>
            </div>
            <div>
                <h1 class="se-brand-title">SE AI Chatbot</h1>
                <p class="se-brand-subtitle">AI-Powered Chat Assistant</p>
            </div>
        </div>
        <a href="<?php echo Route::_('index.php?option=com_seaichat&view=settings'); ?>" class="se-btn se-btn-primary">
            <i class="fa-solid fa-gear"></i> Settings
        </a>
    </div>


<?php
$lic = $this->licenseStatus;
$licState = $lic['state'] ?? 'error';
?>

<?php if ($licState === 'active') : ?>
<!-- License Active — Green -->
<div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div style="display:flex;align-items:center;gap:12px">
        <i class="fa-solid fa-circle-check" style="color:#10b981;font-size:22px"></i>
        <div>
            <div style="font-weight:700;font-size:14px;color:#065f46">License Active</div>
            <div style="font-size:12px;color:#047857">
                <?php echo htmlspecialchars($lic['masked_key'] ?? ''); ?>
                <?php if (!empty($lic['plan_title'])) : ?> &middot; <?php echo htmlspecialchars($lic['plan_title']); ?><?php endif; ?>
                <?php if (!empty($lic['expires_at'])) : ?> &middot; Expires <?php echo HTMLHelper::_('date', $lic['expires_at'], 'M j, Y'); ?><?php endif; ?>
            </div>
        </div>
    </div>
    <form action="<?php echo Route::_('index.php?option=com_seaichat&task=display.checkLicense'); ?>" method="post" style="margin:0">
        <button type="submit" class="se-btn se-btn-secondary" style="padding:7px 16px;font-size:12px"><i class="fa-solid fa-rotate"></i> Check License</button>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>

<?php elseif ($licState === 'expired') : ?>
<!-- License Expired — Amber -->
<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div style="display:flex;align-items:center;gap:12px">
        <i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b;font-size:22px"></i>
        <div>
            <div style="font-weight:700;font-size:14px;color:#92400e">License Expired</div>
            <div style="font-size:12px;color:#a16207">
                <?php echo htmlspecialchars($lic['masked_key'] ?? ''); ?>
                <?php if (!empty($lic['expires_at'])) : ?> &middot; Expired <?php echo HTMLHelper::_('date', $lic['expires_at'], 'M j, Y'); ?><?php endif; ?>
            </div>
            <div style="font-size:11px;color:#92400e;margin-top:4px">The extension continues to work, but updates require an active license. Renew your license and click Check License.</div>
        </div>
    </div>
    <form action="<?php echo Route::_('index.php?option=com_seaichat&task=display.checkLicense'); ?>" method="post" style="margin:0">
        <button type="submit" class="se-btn se-btn-primary" style="padding:7px 16px;font-size:12px"><i class="fa-solid fa-rotate"></i> Check License</button>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>

<?php else : ?>
<!-- Not Activated — Red -->
<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div style="display:flex;align-items:center;gap:12px">
        <i class="fa-solid fa-circle-xmark" style="color:#ef4444;font-size:22px"></i>
        <div>
            <div style="font-weight:700;font-size:14px;color:#991b1b">License Not Activated</div>
            <div style="font-size:12px;color:#b91c1c"><?php echo htmlspecialchars($lic['message'] ?? 'Please enter your license key in Options and activate.'); ?></div>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <?php if (!empty($lic['can_activate'])) : ?>
            <form action="<?php echo Route::_('index.php?option=com_seaichat&task=display.activateLicense'); ?>" method="post" style="margin:0">
                <button type="submit" class="se-btn se-btn-primary" style="padding:7px 16px;font-size:12px"><i class="fa-solid fa-key"></i> Activate License</button>
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        <?php endif; ?>
        <a href="<?php echo Route::_('index.php?option=com_config&view=component&component=com_seaichat'); ?>" class="se-btn se-btn-secondary" style="padding:7px 16px;font-size:12px"><i class="fa-solid fa-gear"></i> Open Options</a>
    </div>
</div>
<?php endif; ?>

    <!-- Diagnostics -->
    <div class="se-card" style="margin-bottom:24px">
        <div class="se-card-header"><h3 class="se-card-title"><i class="fa-solid fa-stethoscope"></i> System Status</h3></div>
        <div class="se-card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:12px">
                <?php
                $checks = [
                    ['label' => 'AI Enabled', 'ok' => $diag->ai_enabled, 'icon' => 'fa-toggle-on'],
                    ['label' => 'API Key Set', 'ok' => $diag->api_key_set, 'icon' => 'fa-key'],
                    ['label' => 'Plugin Registered', 'ok' => $diag->plugin_registered, 'icon' => 'fa-puzzle-piece'],
                    ['label' => 'Plugin Enabled', 'ok' => $diag->plugin_enabled, 'icon' => 'fa-plug-circle-check'],
                    ['label' => 'Plugin File Exists', 'ok' => $diag->plugin_file_exists, 'icon' => 'fa-file-code'],
                ];
                foreach ($checks as $check) :
                    $color = $check['ok'] ? '#10b981' : '#ef4444';
                    $icon = $check['ok'] ? 'fa-circle-check' : 'fa-circle-xmark';
                ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:<?php echo $check['ok'] ? '#ecfdf5' : '#fef2f2'; ?>;border-radius:8px;font-size:13px">
                        <i class="fa-solid <?php echo $icon; ?>" style="color:<?php echo $color; ?>;font-size:16px"></i>
                        <div>
                            <div style="font-weight:600;color:#374151"><?php echo $check['label']; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#eff6ff;border-radius:8px;font-size:13px">
                    <i class="fa-solid fa-bars-staggered" style="color:#3b82f6;font-size:16px"></i>
                    <div>
                        <div style="font-weight:600;color:#374151">Pages: <?php echo $diag->menu_filter; ?></div>
                    </div>
                </div>
            </div>
            <?php if (!$diag->plugin_file_exists) : ?>
                <div style="margin-top:12px;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:13px;color:#991b1b">
                    <i class="fa-solid fa-triangle-exclamation" style="margin-right:6px"></i>
                    <strong>Plugin file missing.</strong> The file <code>plugins/system/seaichat/seaichat.php</code> was not found. Try reinstalling the component, or manually copy the plugin files from <code>administrator/components/com_seaichat/plugins/system/seaichat/</code> to <code>plugins/system/seaichat/</code>.
                </div>
            <?php elseif (!$diag->plugin_enabled) : ?>
                <div style="margin-top:12px;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:13px;color:#92400e">
                    <i class="fa-solid fa-triangle-exclamation" style="margin-right:6px"></i>
                    <strong>Plugin is disabled.</strong> Go to <strong>System → Manage → Plugins</strong>, search for "SE AI Chatbot" and enable it.
                </div>
            <?php elseif (!$diag->ai_enabled) : ?>
                <div style="margin-top:12px;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:13px;color:#92400e">
                    <i class="fa-solid fa-info-circle" style="margin-right:6px"></i>
                    AI Chat is disabled. Go to <a href="<?php echo Route::_('index.php?option=com_seaichat&view=settings'); ?>"><strong>Settings</strong></a> and enable it.
                </div>
            <?php elseif (!$diag->api_key_set) : ?>
                <div style="margin-top:12px;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:13px;color:#92400e">
                    <i class="fa-solid fa-info-circle" style="margin-right:6px"></i>
                    No API key configured. Go to <a href="<?php echo Route::_('index.php?option=com_seaichat&view=settings'); ?>"><strong>Settings</strong></a> to add your API key.
                </div>
            <?php endif; ?>
            <?php if (!$diag->plugin_registered || !$diag->plugin_enabled || !$diag->plugin_file_exists) : ?>
                <div style="margin-top:12px">
                    <button type="button" id="se-repair-plugin" class="se-btn se-btn-primary" style="padding:8px 18px;font-size:13px">
                        <i class="fa-solid fa-wrench" id="se-repair-icon"></i> Repair Plugin Installation
                    </button>
                    <span id="se-repair-result" style="margin-left:10px;font-size:13px"></span>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('se-repair-plugin').addEventListener('click', function() {
                        var btn = this, icon = document.getElementById('se-repair-icon'), res = document.getElementById('se-repair-result');
                        btn.disabled = true; icon.className = 'fa-solid fa-spinner fa-spin'; res.textContent = '';
                        var fd = new FormData();
                        fd.append('<?php echo \Joomla\CMS\Session\Session::getFormToken(); ?>', '1');
                        fetch('index.php?option=com_seaichat&task=ajax.repairplugin&format=raw', { method:'POST', body:fd, credentials:'same-origin' })
                        .then(function(r) { return r.text().then(function(t) { try { return JSON.parse(t); } catch(e) { return {success:false, message:'Server error: ' + t.substring(0,200).replace(/<[^>]*>/g,' ')}; } }); })
                        .then(function(d) {
                            btn.disabled = false; icon.className = 'fa-solid fa-wrench';
                            if (d.success) {
                                res.innerHTML = '<span style="color:#10b981"><i class="fa-solid fa-circle-check"></i> ' + d.message + '</span>';
                                setTimeout(function() { location.reload(); }, 1500);
                            } else {
                                res.innerHTML = '<span style="color:#ef4444"><i class="fa-solid fa-circle-xmark"></i> ' + (d.message || 'Failed') + '</span>';
                            }
                        }).catch(function(e) {
                            btn.disabled = false; icon.className = 'fa-solid fa-wrench';
                            res.innerHTML = '<span style="color:#ef4444">Error: ' + e.message + '</span>';
                        });
                    });
                });
                </script>
            <?php endif; ?>
        </div>
    </div>

    <div class="se-stats-grid">
        <div class="se-stat-card se-stat-total">
            <div class="se-stat-icon"><i class="fa-solid fa-comments"></i></div>
            <div class="se-stat-content">
                <span class="se-stat-number"><?php echo $stats->total_chats; ?></span>
                <span class="se-stat-label">Total Chats</span>
            </div>
        </div>
        <div class="se-stat-card se-stat-open">
            <div class="se-stat-icon"><i class="fa-solid fa-bolt"></i></div>
            <div class="se-stat-content">
                <span class="se-stat-number"><?php echo $stats->chats_today; ?></span>
                <span class="se-stat-label">Today</span>
            </div>
        </div>
        <div class="se-stat-card se-stat-answered">
            <div class="se-stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            <div class="se-stat-content">
                <span class="se-stat-number"><?php echo $stats->active_chats; ?></span>
                <span class="se-stat-label">Active</span>
            </div>
        </div>
        <div class="se-stat-card se-stat-urgent">
            <div class="se-stat-icon"><i class="fa-solid fa-book-open"></i></div>
            <div class="se-stat-content">
                <span class="se-stat-number"><?php echo $stats->kb_sources; ?></span>
                <span class="se-stat-label">KB Sources</span>
            </div>
        </div>
    </div>

    <div class="se-card">
        <div class="se-card-header">
            <h3 class="se-card-title"><i class="fa-solid fa-clock-rotate-left"></i> Recent Conversations</h3>
            <a href="<?php echo Route::_('index.php?option=com_seaichat&view=chatlogs'); ?>" class="se-btn se-btn-ghost">
                View All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <div class="se-card-body se-card-body--flush">
            <?php if (empty($recentChats)) : ?>
                <div class="se-empty-state">
                    <div class="se-empty-icon"><i class="fa-solid fa-comments"></i></div>
                    <p>No conversations yet. Enable the chatbot and add knowledge base sources to get started.</p>
                    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
                        <a href="<?php echo Route::_('index.php?option=com_seaichat&view=settings'); ?>" class="se-btn se-btn-primary"><i class="fa-solid fa-gear"></i> Configure</a>
                        <a href="<?php echo Route::_('index.php?option=com_seaichat&task=kbsource.add'); ?>" class="se-btn se-btn-secondary"><i class="fa-solid fa-plus"></i> Add KB Source</a>
                    </div>
                </div>
            <?php else : ?>
                <table class="se-table">
                    <thead><tr><th>User</th><th>First Message</th><th>Messages</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentChats as $chat) :
                            $messages = json_decode($chat->messages, true) ?: [];
                            $msgCount = count($messages);
                            $firstUserMsg = '';
                            foreach ($messages as $m) { if (($m['role'] ?? '') === 'user') { $firstUserMsg = $m['content'] ?? ''; break; } }
                        ?>
                            <tr>
                                <td class="se-text-muted"><?php echo $this->escape($chat->user_name ?: 'Guest'); ?></td>
                                <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo $this->escape(substr($firstUserMsg, 0, 80)); ?></td>
                                <td class="text-center" style="font-weight:600"><?php echo $msgCount; ?></td>
                                <td><span class="se-badge" style="background:<?php echo $chat->status === 'active' ? '#3b82f6' : '#6b7280'; ?>;color:#fff"><?php echo ucfirst($chat->status); ?></span></td>
                                <td class="se-text-muted"><?php echo HTMLHelper::_('date', $chat->modified, 'M j, Y g:ia'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
