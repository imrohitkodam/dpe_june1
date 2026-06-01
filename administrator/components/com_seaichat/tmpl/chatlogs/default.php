<?php


/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

$wa = $this->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_seaichat.admin', 'com_seaichat/admin.css');

$statusColors = [
    'active'    => '#3b82f6',
    'escalated' => '#f59e0b',
    'closed'    => '#6b7280',
];
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />

<form action="<?php echo Route::_('index.php?option=com_seaichat&view=chatlogs'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="se-admin-list">
        <div class="se-card">
            <div class="se-card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
                <h3 class="se-card-title"><i class="fa-solid fa-comments"></i> Chat Logs</h3>
                <div style="display:flex;gap:8px;align-items:center">
                    <div class="se-search-inline">
                        <input type="text" name="filter_search" id="filter_search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" placeholder="Search chats..." style="padding:6px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;font-family:inherit" />
                    </div>
                    <select name="filter_status" onchange="this.form.submit()" style="padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;font-family:inherit">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $this->state->get('filter.status') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="escalated" <?php echo $this->state->get('filter.status') === 'escalated' ? 'selected' : ''; ?>>Escalated</option>
                        <option value="closed" <?php echo $this->state->get('filter.status') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                    <button type="submit" class="se-btn se-btn-primary" style="padding:6px 14px;font-size:13px"><i class="fa-solid fa-search"></i></button>
                    <a href="<?php echo Route::_('index.php?option=com_seaichat&task=ajax.exportcsv&format=raw&' . \Joomla\CMS\Session\Session::getFormToken() . '=1'); ?>" class="se-btn se-btn-secondary" style="padding:6px 14px;font-size:13px" title="Export as CSV"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
                </div>
            </div>
            <div class="se-card-body" style="padding:0">
                <?php if (empty($this->items)) : ?>
                    <div style="padding:40px;text-align:center;color:#6b7280">
                        <i class="fa-solid fa-comments" style="font-size:32px;opacity:0.3;margin-bottom:12px;display:block"></i>
                        <p>No chat sessions found.</p>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="se-table table">
                            <thead>
                                <tr>
                                    <th style="width:50px"></th>
                                    <th>User</th>
                                    <th style="width:100px">Status</th>
                                    <th style="width:80px">Messages</th>
                                                                        <th style="width:150px">Started</th>
                                    <th style="width:150px">Last Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->items as $i => $item) :
                                    $messages = json_decode($item->messages, true) ?: [];
                                    $msgCount = count($messages);
                                    $firstUserMsg = '';
                                    foreach ($messages as $m) {
                                        if (($m['role'] ?? '') === 'user') {
                                            $firstUserMsg = $m['content'] ?? '';
                                            break;
                                        }
                                    }
                                ?>
                                    <tr class="row<?php echo $i % 2; ?>">
                                        <td class="text-center">
                                            <button type="button" class="se-btn-expand" onclick="toggleChat(<?php echo $item->id; ?>)" style="background:none;border:none;cursor:pointer;font-size:14px;color:#6b7280;padding:4px">
                                                <i class="fa-solid fa-chevron-right" id="se-expand-icon-<?php echo $item->id; ?>"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <?php if (!empty($item->user_name)) : ?>
                                                <div style="font-weight:600;font-size:13px"><?php echo $this->escape($item->user_name); ?></div>
                                                <div style="font-size:12px;color:#6b7280"><?php echo $this->escape($item->user_email); ?></div>
                                            <?php else : ?>
                                                <span style="color:#9ca3af;font-size:13px"><i class="fa-solid fa-user-secret"></i> Guest</span>
                                            <?php endif; ?>
                                            <?php if ($firstUserMsg) : ?>
                                                <div style="font-size:12px;color:#9ca3af;margin-top:2px;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo $this->escape(substr($firstUserMsg, 0, 80)); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="se-badge" style="background:<?php echo $statusColors[$item->status] ?? '#6b7280'; ?>;color:#fff">
                                                <?php echo ucfirst($this->escape($item->status)); ?>
                                            </span>
                                        </td>
                                        <td class="text-center" style="font-weight:600"><?php echo $msgCount; ?></td>
                                        
                                        <td class="se-text-muted" style="font-size:12px"><?php echo HTMLHelper::_('date', $item->created, 'M j, Y g:ia'); ?></td>
                                        <td class="se-text-muted" style="font-size:12px"><?php echo HTMLHelper::_('date', $item->modified, 'M j, Y g:ia'); ?></td>
                                    </tr>
                                    <tr id="se-chat-row-<?php echo $item->id; ?>" style="display:none">
                                        <td colspan="6" style="padding:0">
                                            <div style="background:#f9fafb;padding:16px 24px;border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb">
                                                <div style="max-width:600px;display:flex;flex-direction:column;gap:8px">
                                                    <?php foreach ($messages as $m) :
                                                        $role = $m['role'] ?? 'user';
                                                        $content = htmlspecialchars($m['content'] ?? '', ENT_QUOTES, 'UTF-8');
                                                        $isAssistant = $role === 'assistant';
                                                    ?>
                                                        <div style="display:flex;gap:8px;<?php echo $isAssistant ? 'align-self:flex-start' : 'align-self:flex-end;flex-direction:row-reverse'; ?>">
                                                            <?php if ($isAssistant) : ?>
                                                                <div style="width:24px;height:24px;border-radius:50%;background:#2E486B;color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;flex-shrink:0"><i class="fa-solid fa-robot"></i></div>
                                                            <?php else : ?>
                                                                <div style="width:24px;height:24px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;flex-shrink:0"><i class="fa-solid fa-user"></i></div>
                                                            <?php endif; ?>
                                                            <div style="padding:8px 12px;border-radius:10px;font-size:13px;line-height:1.5;max-width:80%;<?php echo $isAssistant ? 'background:#fff;border:1px solid #e5e7eb;color:#1a1d26' : 'background:#2E486B;color:#fff'; ?>">
                                                                <?php echo nl2br($content); ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <?php if (empty($messages)) : ?>
                                                        <div style="color:#9ca3af;font-size:13px;text-align:center;padding:12px">No messages recorded.</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<script>
function toggleChat(id) {
    var row = document.getElementById('se-chat-row-' + id);
    var icon = document.getElementById('se-expand-icon-' + id);
    if (row.style.display === 'none') {
        row.style.display = '';
        icon.className = 'fa-solid fa-chevron-down';
    } else {
        row.style.display = 'none';
        icon.className = 'fa-solid fa-chevron-right';
    }
}
</script>
