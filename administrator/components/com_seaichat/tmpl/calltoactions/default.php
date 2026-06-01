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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

// Free / Pro gating
require_once JPATH_ADMINISTRATOR . '/components/com_seaichat/helpers/LicenseChecker.php';
$isPro       = \SeAiChatLicenseChecker::isPro();
$upgradeUrl  = \SeAiChatLicenseChecker::upgradeUrl();
$ctaCount    = count($this->items ?? []);
$ctaLimitHit = !$isPro && $ctaCount >= 1;

$wa = $this->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_seaichat.admin', 'com_seaichat/admin.css');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />

<form action="<?php echo Route::_('index.php?option=com_seaichat&view=calltoactions'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="se-tickets-list">
        <div class="se-list-toolbar">
            <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
        </div>

        <?php if ($ctaLimitHit): ?>
        <div style="margin-bottom:16px;padding:12px 16px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:13px;color:#92400e;display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-lock" style="font-size:16px;color:#f59e0b;flex-shrink:0"></i>
            <span>
                <strong>Free plan:</strong> limited to 1 call-to-action button.
                <a href="<?php echo $upgradeUrl; ?>" target="_blank" style="color:#2E486B;font-weight:700;margin-left:4px">Upgrade to Pro →</a>
                for unlimited CTAs.
            </span>
        </div>
        <?php endif; ?>

        <?php if (empty($this->items)) : ?>
            <div class="se-empty-state">
                <div class="se-empty-icon"><i class="fa-solid fa-bullhorn"></i></div>
                <h3><?php echo Text::_('COM_SEAICHAT_CTA_NO_ITEMS'); ?></h3>
                <p><?php echo Text::_('COM_SEAICHAT_CTA_NO_ITEMS_DESC'); ?></p>
                <a href="<?php echo Route::_('index.php?option=com_seaichat&task=calltoaction.add'); ?>" class="se-btn se-btn-primary">
                    <i class="fa-solid fa-plus"></i> <?php echo Text::_('COM_SEAICHAT_CTA_ADD'); ?>
                </a>
                <?php if ($ctaLimitHit): ?>
                <p style="margin-top:10px;font-size:13px;color:#6b7280">
                    <i class="fa-solid fa-lock" style="color:#f59e0b"></i>
                    Free plan: 1 CTA button.
                    <a href="<?php echo $upgradeUrl; ?>" target="_blank" style="color:#2E486B;font-weight:600">Upgrade to Pro</a> for unlimited.
                </p>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="se-card">
                <div class="se-card-body se-card-body--flush">
                    <table class="se-table" id="ctaList">
                        <thead>
                            <tr>
                                <td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
                                <th scope="col">Title</th>
                                <th scope="col">Keywords</th>
                                <th scope="col">Button</th>
                                <th scope="col" style="width:80px">Status</th>
                                <th scope="col" style="width:60px">Order</th>
                                <th scope="col" class="w-1">ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                                    <td>
                                        <a href="<?php echo Route::_('index.php?option=com_seaichat&task=calltoaction.edit&id=' . $item->id); ?>" class="se-ticket-subject">
                                            <?php echo $this->escape($item->title); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $keywords = array_map('trim', explode(',', $item->keywords));
                                        foreach ($keywords as $kw) :
                                            if ($kw === '') continue;
                                        ?>
                                            <span class="se-badge" style="background:#e0e7ff;color:#3730a3;margin:1px 2px"><?php echo $this->escape($kw); ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        <span style="font-size:13px">
                                            <?php if (!empty($item->button_icon)) : ?>
                                                <i class="fa-solid <?php echo $this->escape($item->button_icon); ?>" style="margin-right:4px;opacity:0.6"></i>
                                            <?php endif; ?>
                                            <a href="<?php echo $this->escape($item->button_url); ?>" target="_blank" style="color:inherit">
                                                <?php echo $this->escape($item->button_label); ?>
                                                <i class="fa-solid fa-external-link" style="font-size:10px;opacity:0.4;margin-left:3px"></i>
                                            </a>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($item->published) : ?>
                                            <span class="se-badge" style="background:#10b981;color:#fff">Published</span>
                                        <?php else : ?>
                                            <span class="se-badge" style="background:#6b7280;color:#fff">Unpublished</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center se-text-muted"><?php echo (int) $item->ordering; ?></td>
                                    <td class="se-text-muted"><?php echo $item->id; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php echo $this->pagination->getListFooter(); ?>
        <?php endif; ?>
    </div>
    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
