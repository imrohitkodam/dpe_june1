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

$wa = $this->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_seaichat.admin', 'com_seaichat/admin.css');

$isNew = empty($this->item->id);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />

<form action="<?php echo Route::_('index.php?option=com_seaichat&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="se-ticket-form">
    <div class="se-ticket-layout">
        <div class="se-ticket-main">
            <div class="se-card">
                <div class="se-card-header">
                    <h3 class="se-card-title"><i class="fa-solid fa-bullhorn"></i> Call to Action</h3>
                </div>
                <div class="se-card-body">
                    <div class="se-form-group">
                        <label class="se-form-label"><i class="fa-solid fa-heading"></i> Title *</label>
                        <?php echo $this->form->getInput('title'); ?>
                        <div class="form-text text-muted" style="margin-top:4px;font-size:12px">
                            <i class="fa-solid fa-info-circle"></i> Internal name for this CTA — not shown to visitors.
                        </div>
                    </div>

                    <div class="se-form-group">
                        <label class="se-form-label"><i class="fa-solid fa-tags"></i> Trigger Keywords *</label>
                        <?php echo $this->form->getInput('keywords'); ?>
                        <div class="form-text text-muted" style="margin-top:4px;font-size:12px">
                            <i class="fa-solid fa-info-circle"></i> Comma-separated words. When any of these appear in the user's question <strong>or</strong> the AI's response, this button will be shown. E.g. <code>ticket, support, help, issue</code>
                        </div>
                    </div>

                    <div style="border-top:1px solid #e5e7eb;margin:20px 0;padding-top:20px">
                        <div style="font-size:14px;font-weight:600;color:#374151;margin-bottom:16px">
                            <i class="fa-solid fa-hand-pointer"></i> Button Appearance
                        </div>

                        <div class="se-form-group">
                            <label class="se-form-label"><i class="fa-solid fa-font"></i> Button Label *</label>
                            <?php echo $this->form->getInput('button_label'); ?>
                            <div class="form-text text-muted" style="margin-top:4px;font-size:12px">
                                <i class="fa-solid fa-info-circle"></i> The text displayed on the button, e.g. "Open a Ticket" or "View Pricing".
                            </div>
                        </div>

                        <div class="se-form-group">
                            <label class="se-form-label"><i class="fa-solid fa-link"></i> Button URL *</label>
                            <?php echo $this->form->getInput('button_url'); ?>
                            <div class="form-text text-muted" style="margin-top:4px;font-size:12px">
                                <i class="fa-solid fa-info-circle"></i> Where the button links to. Use a relative path (e.g. <code>/support/tickets</code>) or full URL.
                            </div>
                        </div>

                        <div style="display:flex;gap:16px">
                            <div class="se-form-group" style="flex:1">
                                <label class="se-form-label"><i class="fa-solid fa-icons"></i> Button Icon</label>
                                <?php echo $this->form->getInput('button_icon'); ?>
                                <div class="form-text text-muted" style="margin-top:4px;font-size:12px">
                                    <i class="fa-solid fa-info-circle"></i> Font Awesome icon class without <code>fa-solid</code> prefix. E.g. <code>fa-ticket</code>, <code>fa-tag</code>, <code>fa-envelope</code>.
                                    <a href="https://fontawesome.com/search?o=r&m=free&s=solid" target="_blank" style="color:#2E486B">Browse icons <i class="fa-solid fa-external-link" style="font-size:10px"></i></a>
                                </div>
                            </div>

                            <div class="se-form-group" style="flex:1">
                                <label class="se-form-label"><i class="fa-solid fa-up-right-from-square"></i> Open Link In</label>
                                <?php echo $this->form->getInput('button_target'); ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!$isNew) : ?>
                        <div style="border-top:1px solid #e5e7eb;margin:20px 0;padding-top:20px">
                            <div style="font-size:14px;font-weight:600;color:#374151;margin-bottom:12px">
                                <i class="fa-solid fa-eye"></i> Preview
                            </div>
                            <div style="background:#f9fafb;border-radius:12px;padding:16px">
                                <div id="se-cta-preview" style="display:flex;flex-wrap:wrap;gap:8px">
                                    <a href="<?php echo $this->escape($this->item->button_url); ?>"
                                       target="<?php echo $this->escape($this->item->button_target ?: '_self'); ?>"
                                       style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#2E486B;color:#fff;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:all 0.2s;cursor:pointer">
                                        <?php if (!empty($this->item->button_icon)) : ?>
                                            <i class="fa-solid <?php echo $this->escape($this->item->button_icon); ?>"></i>
                                        <?php endif; ?>
                                        <?php echo $this->escape($this->item->button_label); ?>
                                    </a>
                                </div>
                                <div class="form-text text-muted" style="margin-top:8px;font-size:11px">
                                    This is how the button will appear in the chat widget.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="se-ticket-sidebar">
            <div class="se-card">
                <div class="se-card-header"><h3 class="se-card-title"><i class="fa-solid fa-sliders"></i> Properties</h3></div>
                <div class="se-card-body">
                    <div class="se-form-group">
                        <label class="se-form-label"><i class="fa-solid fa-eye"></i> Published</label>
                        <?php echo $this->form->getInput('published'); ?>
                        <div class="form-text text-muted" style="margin-top:4px;font-size:12px">Unpublished CTAs won't appear in the chat.</div>
                    </div>
                    <div class="se-form-group">
                        <label class="se-form-label"><i class="fa-solid fa-sort"></i> Ordering</label>
                        <?php echo $this->form->getInput('ordering'); ?>
                    </div>
                </div>
            </div>

            <?php if ($isNew) : ?>
                <div class="se-card">
                    <div class="se-card-header"><h3 class="se-card-title"><i class="fa-solid fa-lightbulb"></i> How It Works</h3></div>
                    <div class="se-card-body">
                        <div style="font-size:13px;color:#6b7280;line-height:1.6">
                            <p style="margin:0 0 10px"><strong>1. Set keywords</strong></p>
                            <p style="margin:0 0 12px">Choose words that relate to this action. When a user asks about "tickets" or "support", the button appears.</p>
                            <p style="margin:0 0 10px"><strong>2. Configure the button</strong></p>
                            <p style="margin:0 0 12px">Set the label, URL, and icon. The button appears below the AI's response in the chat widget.</p>
                            <p style="margin:0 0 10px"><strong>3. Keyword matching</strong></p>
                            <p style="margin:0">Keywords are matched against both the user's question and the AI's response (case-insensitive). Multiple CTAs can appear if different keywords match.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <input type="hidden" name="task" value="">
    <?php echo $this->form->getInput('id'); ?>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
