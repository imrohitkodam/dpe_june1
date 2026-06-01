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
$sourceType = $this->item->source_type ?? 'url';
$isArticles = ($sourceType === 'articles');
$isSppb = ($sourceType === 'sppb');
$isText = ($sourceType === 'text');
$isUrl = ($sourceType === 'url');

// Load Joomla content categories for the picker
$db = \Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
$catQuery = $db->getQuery(true)
    ->select('id, title, level, parent_id')
    ->from($db->quoteName('#__categories'))
    ->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'))
    ->where($db->quoteName('published') . ' = 1')
    ->where($db->quoteName('level') . ' > 0')
    ->order('lft ASC');
$db->setQuery($catQuery);
$joomlaCategories = $db->loadObjectList() ?: [];

$selectedCats = array_filter(array_map('intval', explode(',', $this->item->categories ?? '')));
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />

<form action="<?php echo Route::_('index.php?option=com_seaichat&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="se-ticket-form">
    <div class="se-ticket-layout">
        <div class="se-ticket-main">
            <div class="se-card">
                <div class="se-card-header">
                    <h3 class="se-card-title"><i class="fa-solid fa-book-open"></i> Knowledge Base Source</h3>
                </div>
                <div class="se-card-body">
                    <div class="se-form-group">
                        <label class="se-form-label"><i class="fa-solid fa-heading"></i> Title *</label>
                        <?php echo $this->form->getInput('title'); ?>
                    </div>
                    <div class="se-form-group">
                        <label class="se-form-label"><i class="fa-solid fa-toggle-on"></i> Source Type</label>
                        <?php echo $this->form->getInput('source_type'); ?>
                        <div class="form-text text-muted" style="margin-top:4px;font-size:12px">
                            <i class="fa-solid fa-info-circle"></i>
                            <strong>Website URL</strong> — crawl an external docs site.
                            <strong>Manual Text</strong> — paste content directly.
                            <strong>Joomla Articles</strong> — use your site's own articles as knowledge.
                            <strong>SP Page Builder</strong> — use SP Page Builder pages.
                        </div>
                    </div>

                    <!-- URL source -->
                    <div id="se-source-url" class="se-form-group" style="<?php echo !$isUrl ? 'display:none' : ''; ?>">
                        <label class="se-form-label"><i class="fa-solid fa-link"></i> Documentation URL</label>
                        <?php echo $this->form->getInput('url'); ?>
                        <div class="form-text text-muted" style="margin-top:4px;font-size:12px">
                            <i class="fa-solid fa-info-circle"></i> Enter the base URL. The crawler will follow internal links to index all pages.
                        </div>
                    </div>

                    <!-- Text source -->
                    <div id="se-source-text" class="se-form-group" style="<?php echo !$isText ? 'display:none' : ''; ?>">
                        <label class="se-form-label"><i class="fa-solid fa-file-lines"></i> Documentation Content</label>
                        <?php echo $this->form->getInput('content'); ?>
                        <div class="form-text text-muted" style="margin-top:4px;font-size:12px">
                            <i class="fa-solid fa-info-circle"></i> Paste your documentation or FAQ content. Use blank lines to separate sections.
                        </div>
                    </div>

                    <!-- Articles source — category picker -->
                    <div id="se-source-articles" class="se-form-group" style="<?php echo !$isArticles ? 'display:none' : ''; ?>">
                        <label class="se-form-label"><i class="fa-solid fa-newspaper"></i> Article Categories</label>
                        <div style="margin-bottom:8px">
                            <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:500;cursor:pointer;padding:6px 0">
                                <input type="checkbox" id="se-cat-all" <?php echo empty($selectedCats) ? 'checked' : ''; ?> style="accent-color:#2E486B;width:16px;height:16px" />
                                All categories (every published article)
                            </label>
                        </div>
                        <div id="se-cat-picker" style="max-height:240px;overflow-y:auto;border:1px solid #d1d5db;border-radius:8px;padding:6px 0;background:#fff;<?php echo empty($selectedCats) ? 'opacity:0.5;pointer-events:none' : ''; ?>">
                            <?php foreach ($joomlaCategories as $cat) :
                                $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', max(0, (int) $cat->level - 1));
                                $dash = ((int) $cat->level > 1) ? '&#x2514; ' : '';
                                $checked = in_array((int) $cat->id, $selectedCats) ? 'checked' : '';
                            ?>
                                <label style="display:flex;align-items:center;gap:8px;padding:5px 14px;cursor:pointer;font-size:13px;transition:background 0.1s" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background=''">
                                    <input type="checkbox" class="se-cat-cb" value="<?php echo (int) $cat->id; ?>" <?php echo $checked; ?> style="accent-color:#2E486B;width:16px;height:16px" />
                                    <span><?php echo $indent . $dash . htmlspecialchars($cat->title); ?></span>
                                </label>
                            <?php endforeach; ?>
                            <?php if (empty($joomlaCategories)) : ?>
                                <div style="padding:16px;text-align:center;color:#9ca3af;font-size:13px">No published article categories found.</div>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="jform[categories]" id="se-cat-value" value="<?php echo htmlspecialchars($this->item->categories ?? ''); ?>" />
                        <div class="form-text text-muted" style="margin-top:4px;font-size:12px">
                            <i class="fa-solid fa-info-circle"></i> Select which article categories to include, or choose "All" for every published article. Articles are automatically re-indexed when saved.
                        </div>
                    </div>

                    <!-- SP Page Builder source -->
                    <div id="se-source-sppb" class="se-form-group" style="<?php echo !$isSppb ? 'display:none' : ''; ?>">
                        <label class="se-form-label"><i class="fa-solid fa-layer-group"></i> SP Page Builder Categories</label>
                        <div style="margin-bottom:8px">
                            <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:500;cursor:pointer;padding:6px 0">
                                <input type="checkbox" id="se-sppb-cat-all" <?php echo ($isSppb && empty($selectedCats)) ? 'checked' : ''; ?> style="accent-color:#2E486B;width:16px;height:16px" />
                                All pages (every published SP Page Builder page)
                            </label>
                        </div>
                        <div class="form-text text-muted" style="margin-top:4px;font-size:12px">
                            <i class="fa-solid fa-info-circle"></i> Indexes all published SP Page Builder pages. Category filtering available if SP Page Builder uses categories on your site. Pages are automatically re-indexed when saved.
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$isNew) : ?>
                <div class="se-card">
                    <div class="se-card-header">
                        <h3 class="se-card-title"><i class="fa-solid fa-<?php echo $isUrl ? 'spider' : ($isArticles ? 'newspaper' : ($isSppb ? 'layer-group' : 'gears')); ?>"></i> <?php echo $isUrl ? 'Crawl Information' : 'Processing Information'; ?></h3>
                    </div>
                    <div class="se-card-body">
                        <div class="se-info-list">
                            <div class="se-info-item">
                                <span class="se-info-label"><i class="fa-solid fa-signal"></i> Status</span>
                                <span class="se-info-value">
                                    <?php
                                    $statusColors = ['pending' => '#6b7280', 'crawling' => '#f59e0b', 'processing' => '#f59e0b', 'complete' => '#10b981', 'error' => '#ef4444'];
                                    $color = $statusColors[$this->item->crawl_status] ?? '#6b7280';
                                    ?>
                                    <span class="se-badge" style="background:<?php echo $color; ?>;color:#fff"><?php echo ucfirst($this->escape($this->item->crawl_status)); ?></span>
                                </span>
                            </div>
                            <div class="se-info-item">
                                <span class="se-info-label"><i class="fa-solid fa-file-lines"></i> <?php echo $isArticles ? 'Articles Indexed' : ($isSppb ? 'Pages Indexed' : ($isUrl ? 'Pages Indexed' : 'Sections')); ?></span>
                                <span class="se-info-value"><?php echo (int) $this->item->page_count; ?></span>
                            </div>
                            <div class="se-info-item">
                                <span class="se-info-label"><i class="fa-solid fa-puzzle-piece"></i> Content Chunks</span>
                                <span class="se-info-value"><?php echo (int) $this->item->chunk_count; ?></span>
                            </div>
                            <?php if (!empty($this->item->last_crawled)) : ?>
                                <div class="se-info-item">
                                    <span class="se-info-label"><i class="fa-solid fa-clock"></i> Last Processed</span>
                                    <span class="se-info-value"><?php echo HTMLHelper::_('date', $this->item->last_crawled, 'M j, Y g:ia'); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($this->item->crawl_status === 'error' && !empty($this->item->crawl_error)) : ?>
                                <div class="se-info-item">
                                    <span class="se-info-label"><i class="fa-solid fa-triangle-exclamation"></i> Error</span>
                                    <span class="se-info-value" style="color:#ef4444"><?php echo $this->escape($this->item->crawl_error); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top:16px">
                            <?php if ($isUrl) : ?>
                                <a href="<?php echo Route::_('index.php?option=com_seaichat&task=knowledgebases.crawl&id=' . $this->item->id . '&' . \Joomla\CMS\Session\Session::getFormToken() . '=1'); ?>"
                                   class="se-btn se-btn-primary" onclick="return confirm('Start crawling?');" style="padding:10px 20px">
                                    <i class="fa-solid fa-spider"></i> <?php echo $this->item->crawl_status === 'complete' ? 'Re-crawl' : 'Crawl Now'; ?>
                                </a>
                            <?php elseif ($isArticles) : ?>
                                <a href="<?php echo Route::_('index.php?option=com_seaichat&task=knowledgebases.processarticles&id=' . $this->item->id . '&' . \Joomla\CMS\Session\Session::getFormToken() . '=1'); ?>"
                                   class="se-btn se-btn-primary" onclick="return confirm('Re-index articles now?');" style="padding:10px 20px">
                                    <i class="fa-solid fa-rotate"></i> <?php echo $this->item->crawl_status === 'complete' ? 'Re-index Articles' : 'Index Articles Now'; ?>
                                </a>
                                <?php if ($this->item->crawl_status === 'complete') : ?>
                                    <span style="margin-left:10px;font-size:12px;color:#6b7280"><i class="fa-solid fa-info-circle"></i> Articles are also re-indexed automatically when saved.</span>
                                <?php endif; ?>
                            <?php elseif ($isSppb) : ?>
                                <a href="<?php echo Route::_('index.php?option=com_seaichat&task=knowledgebases.processsppb&id=' . $this->item->id . '&' . \Joomla\CMS\Session\Session::getFormToken() . '=1'); ?>"
                                   class="se-btn se-btn-primary" onclick="return confirm('Re-index SP Page Builder pages now?');" style="padding:10px 20px">
                                    <i class="fa-solid fa-rotate"></i> <?php echo $this->item->crawl_status === 'complete' ? 'Re-index Pages' : 'Index Pages Now'; ?>
                                </a>
                                <?php if ($this->item->crawl_status === 'complete') : ?>
                                    <span style="margin-left:10px;font-size:12px;color:#6b7280"><i class="fa-solid fa-info-circle"></i> Pages are also re-indexed automatically when saved.</span>
                                <?php endif; ?>
                            <?php else : ?>
                                <a href="<?php echo Route::_('index.php?option=com_seaichat&task=knowledgebases.processtext&id=' . $this->item->id . '&' . \Joomla\CMS\Session\Session::getFormToken() . '=1'); ?>"
                                   class="se-btn se-btn-primary" onclick="return confirm('Process text content?');" style="padding:10px 20px">
                                    <i class="fa-solid fa-gears"></i> <?php echo $this->item->crawl_status === 'complete' ? 'Re-process' : 'Process Now'; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="se-ticket-sidebar">
            <div class="se-card">
                <div class="se-card-header"><h3 class="se-card-title"><i class="fa-solid fa-sliders"></i> Properties</h3></div>
                <div class="se-card-body">
                    <div class="se-form-group">
                        <label class="se-form-label"><i class="fa-solid fa-eye"></i> Published</label>
                        <?php echo $this->form->getInput('published'); ?>
                        <div class="form-text text-muted" style="margin-top:4px;font-size:12px">Unpublished sources won't be used by the AI chat.</div>
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
                            <p style="margin:0 0 10px"><strong>Website URL:</strong></p>
                            <p style="margin:0 0 4px">1. Add your docs URL and save</p>
                            <p style="margin:0 0 12px">2. Click "Crawl Now" to index pages</p>
                            <p style="margin:0 0 10px"><strong>Manual Text:</strong></p>
                            <p style="margin:0 0 4px">1. Paste content and save</p>
                            <p style="margin:0 0 12px">2. Click "Process" to chunk and index</p>
                            <p style="margin:0 0 10px"><strong>Joomla Articles:</strong></p>
                            <p style="margin:0 0 4px">1. Pick categories (or all) and save</p>
                            <p style="margin:0 0 4px">2. Click "Index Articles" to process</p>
                            <p style="margin:0">3. Auto-updates when articles are saved</p>
                            <p style="margin:12px 0 10px 0"><strong>SP Page Builder:</strong></p>
                            <p style="margin:0 0 4px">1. Select source type and save</p>
                            <p style="margin:0 0 4px">2. Click "Index Pages" to process</p>
                            <p style="margin:0">3. Auto-updates when pages are saved</p>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    var typeField = document.getElementById('jform_source_type');
    if (!typeField) return;

    function toggleFields() {
        var val = typeField.value;
        document.getElementById('se-source-url').style.display = val === 'url' ? '' : 'none';
        document.getElementById('se-source-text').style.display = val === 'text' ? '' : 'none';
        document.getElementById('se-source-articles').style.display = val === 'articles' ? '' : 'none';
        var sppbEl = document.getElementById('se-source-sppb'); if (sppbEl) sppbEl.style.display = val === 'sppb' ? '' : 'none';
    }

    typeField.addEventListener('change', toggleFields);
    toggleFields();

    // Category picker logic
    var catAll = document.getElementById('se-cat-all');
    var catPicker = document.getElementById('se-cat-picker');
    var catHidden = document.getElementById('se-cat-value');
    var catCheckboxes = document.querySelectorAll('.se-cat-cb');

    function updateCatValue() {
        if (catAll.checked) {
            catHidden.value = '';
            catPicker.style.opacity = '0.5';
            catPicker.style.pointerEvents = 'none';
            catCheckboxes.forEach(function(cb) { cb.checked = false; });
        } else {
            catPicker.style.opacity = '1';
            catPicker.style.pointerEvents = '';
            var ids = [];
            catCheckboxes.forEach(function(cb) { if (cb.checked) ids.push(cb.value); });
            catHidden.value = ids.join(',');
        }
    }

    catAll.addEventListener('change', updateCatValue);
    catCheckboxes.forEach(function(cb) {
        cb.addEventListener('change', function() {
            if (this.checked) catAll.checked = false;
            updateCatValue();
        });
    });
});
</script>
