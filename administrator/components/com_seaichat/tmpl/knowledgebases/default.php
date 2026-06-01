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
use Joomla\CMS\Component\ComponentHelper;

// Free / Pro gating
require_once JPATH_ADMINISTRATOR . '/components/com_seaichat/helpers/LicenseChecker.php';
$isPro      = \SeAiChatLicenseChecker::isPro();
$upgradeUrl = \SeAiChatLicenseChecker::upgradeUrl();
$kbCount    = count($this->items ?? []);
$kbLimitHit = !$isPro && $kbCount >= 1;

$wa = $this->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_seaichat.admin', 'com_seaichat/admin.css');

$statusColors = [
    'pending'    => '#6b7280',
    'crawling'   => '#f59e0b',
    'processing' => '#f59e0b',
    'complete'   => '#10b981',
    'error'      => '#ef4444',
];
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />

<form action="<?php echo Route::_('index.php?option=com_seaichat&view=knowledgebases'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="se-tickets-list">
        <div class="se-list-toolbar">
            <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-bottom:12px;gap:8px">
            <button type="button" id="btnIndexAllArticles"
               class="se-btn se-btn-primary"
               style="padding:8px 16px;font-size:13px;background:#f59e0b;border-color:#f59e0b;cursor:pointer">
                <i class="fa-solid fa-newspaper"></i> Index All Articles
            </button>
        </div>

        <!-- Index All Articles progress modal -->
        <div id="seIndexModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10000;align-items:center;justify-content:center">
            <div style="background:#fff;border-radius:12px;padding:32px 36px;max-width:460px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);text-align:center">
                <div id="seIndexIcon" style="font-size:36px;margin-bottom:16px;color:#f59e0b">
                    <i class="fa-solid fa-newspaper"></i>
                </div>
                <h3 id="seIndexTitle" style="margin:0 0 8px;font-size:18px;font-weight:600">Indexing All Articles</h3>
                <p id="seIndexMsg" style="margin:0 0 20px;color:#6b7280;font-size:14px">Processing all published Joomla articles. This may take a moment…</p>
                <div id="seIndexProgressWrap" style="background:#e5e7eb;border-radius:8px;height:12px;overflow:hidden;margin-bottom:20px">
                    <div id="seIndexProgressBar" style="height:100%;border-radius:8px;background:linear-gradient(90deg,#f59e0b,#f97316);width:0%;transition:width 0.3s ease"></div>
                </div>
                <button type="button" id="seIndexCloseBtn" style="display:none;padding:8px 24px;border-radius:6px;border:1px solid #d1d5db;background:#fff;cursor:pointer;font-size:14px" onclick="document.getElementById('seIndexModal').style.display='none';location.reload();">
                    Close
                </button>
            </div>
        </div>

        <style>
            @keyframes seIndexProgress {
                0% { width: 15%; }
                25% { width: 35%; }
                50% { width: 55%; }
                75% { width: 70%; }
                100% { width: 85%; }
            }
            #seIndexProgressBar.se-animating {
                animation: seIndexProgress 8s ease-out forwards;
            }
        </style>

        <script>
        document.getElementById('btnIndexAllArticles').addEventListener('click', function() {
            if (!confirm('This will index ALL published Joomla articles on your site into the knowledge base.\n\nContinue?')) return;

            var modal = document.getElementById('seIndexModal');
            var bar = document.getElementById('seIndexProgressBar');
            var title = document.getElementById('seIndexTitle');
            var msg = document.getElementById('seIndexMsg');
            var icon = document.getElementById('seIndexIcon');
            var closeBtn = document.getElementById('seIndexCloseBtn');
            var btn = this;

            // Reset and show modal
            bar.style.width = '0%';
            bar.classList.remove('se-animating');
            title.textContent = 'Indexing All Articles';
            msg.textContent = 'Processing all published Joomla articles. This may take a moment…';
            icon.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            icon.style.color = '#f59e0b';
            closeBtn.style.display = 'none';
            modal.style.display = 'flex';
            btn.disabled = true;

            // Start animated progress
            setTimeout(function() { bar.classList.add('se-animating'); }, 50);

            // AJAX call
            var formData = new FormData();
            formData.append('<?php echo \Joomla\CMS\Session\Session::getFormToken(); ?>', '1');

            fetch('<?php echo Route::_("index.php?option=com_seaichat&task=ajax.processallarticles&format=json", false); ?>', {
                method: 'POST',
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                bar.classList.remove('se-animating');
                bar.style.transition = 'width 0.4s ease';
                bar.style.width = '100%';

                if (data.success) {
                    bar.style.background = 'linear-gradient(90deg, #10b981, #059669)';
                    icon.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
                    icon.style.color = '#10b981';
                    title.textContent = 'Indexing Complete';
                    msg.textContent = data.pages + ' article' + (data.pages !== 1 ? 's' : '') + ' indexed, ' + data.chunks + ' chunk' + (data.chunks !== 1 ? 's' : '') + ' created.';
                } else {
                    bar.style.background = 'linear-gradient(90deg, #ef4444, #dc2626)';
                    icon.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';
                    icon.style.color = '#ef4444';
                    title.textContent = 'Indexing Failed';
                    msg.textContent = data.error || 'An unknown error occurred.';
                }
                closeBtn.style.display = 'inline-block';
                btn.disabled = false;
            })
            .catch(function(err) {
                bar.classList.remove('se-animating');
                bar.style.width = '100%';
                bar.style.background = 'linear-gradient(90deg, #ef4444, #dc2626)';
                icon.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';
                icon.style.color = '#ef4444';
                title.textContent = 'Connection Error';
                msg.textContent = 'Could not reach the server. Please try again.';
                closeBtn.style.display = 'inline-block';
                btn.disabled = false;
            });
        });
        </script>

        <?php if ($kbLimitHit): ?>
        <div style="margin-bottom:16px;padding:12px 16px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:13px;color:#92400e;display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-lock" style="font-size:16px;color:#f59e0b;flex-shrink:0"></i>
            <span>
                <strong>Free plan:</strong> limited to 1 knowledge base source.
                <a href="<?php echo $upgradeUrl; ?>" target="_blank" style="color:#2E486B;font-weight:700;margin-left:4px">Upgrade to Pro →</a>
                to add unlimited sources.
            </span>
        </div>
        <?php endif; ?>

        <?php if (empty($this->items)) : ?>
            <div class="se-empty-state">
                <div class="se-empty-icon"><i class="fa-solid fa-book-open"></i></div>
                <h3>No documentation sources</h3>
                <p>Add your documentation website URL so the AI chat assistant can answer customer questions from your docs.</p>
                <a href="<?php echo Route::_('index.php?option=com_seaichat&task=kbsource.add'); ?>" class="se-btn se-btn-primary">
                    <i class="fa-solid fa-plus"></i> Add Documentation Source
                </a>
                <?php if ($kbLimitHit): ?>
                <p style="margin-top:10px;font-size:13px;color:#6b7280">
                    <i class="fa-solid fa-lock" style="color:#f59e0b"></i>
                    Free plan: 1 knowledge base source.
                    <a href="<?php echo $upgradeUrl; ?>" target="_blank" style="color:#2E486B;font-weight:600">Upgrade to Pro</a> for unlimited sources.
                </p>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="se-card">
                <div class="se-card-body se-card-body--flush">
                    <table class="se-table" id="kbsourceList">
                        <thead>
                            <tr>
                                <td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
                                <th scope="col">Title</th>
                                <th scope="col" style="width:90px">Type</th>
                                <th scope="col">Source</th>
                                <th scope="col" style="width:100px">Status</th>
                                <th scope="col" style="width:80px">Chunks</th>
                                <th scope="col" style="width:140px">Last Processed</th>
                                <th scope="col" style="width:130px">Actions</th>
                                <th scope="col" class="w-1">ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) :
                                $isText = ($item->source_type ?? 'url') === 'text';
                                $isArticles = ($item->source_type ?? 'url') === 'articles';
                                $isSppb = ($item->source_type ?? 'url') === 'sppb';
                                $statusColors['processing'] = '#f59e0b';
                            ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                                    <td>
                                        <a href="<?php echo Route::_('index.php?option=com_seaichat&task=kbsource.edit&id=' . $item->id); ?>" class="se-ticket-subject">
                                            <?php echo $this->escape($item->title); ?>
                                        </a>
                                        <?php if (!$item->published) : ?>
                                            <span class="se-badge" style="background:#6b7280;color:#fff;margin-left:6px">Unpublished</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isSppb) : ?>
                                            <span class="se-badge" style="background:#e11d48;color:#fff"><i class="fa-solid fa-layer-group"></i> SP PB</span>
                                        <?php elseif ($isArticles) : ?>
                                            <span class="se-badge" style="background:#f59e0b;color:#fff"><i class="fa-solid fa-newspaper"></i> Articles</span>
                                        <?php elseif ($isText) : ?>
                                            <span class="se-badge" style="background:#8b5cf6;color:#fff"><i class="fa-solid fa-file-lines"></i> Text</span>
                                        <?php else : ?>
                                            <span class="se-badge" style="background:#3b82f6;color:#fff"><i class="fa-solid fa-globe"></i> URL</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isSppb) : ?>
                                            <span class="se-text-muted" style="font-size:13px">SP Page Builder pages</span>
                                        <?php elseif ($isArticles) : ?>
                                            <span class="se-text-muted" style="font-size:13px"><?php echo empty($item->categories) ? 'All categories' : count(array_filter(explode(',', $item->categories))) . ' categories'; ?></span>
                                        <?php elseif ($isText) : ?>
                                            <span class="se-text-muted" style="font-size:13px"><?php echo number_format(strlen($item->content ?? '')) ?> chars</span>
                                        <?php else : ?>
                                            <a href="<?php echo $this->escape($item->url); ?>" target="_blank" class="se-text-muted" style="font-size:13px;word-break:break-all">
                                                <?php echo $this->escape($item->url); ?> <i class="fa-solid fa-external-link" style="font-size:10px"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="se-badge" style="background:<?php echo $statusColors[$item->crawl_status] ?? '#6b7280'; ?>;color:#fff">
                                            <?php if (in_array($item->crawl_status, ['crawling','processing'])) : ?><i class="fa-solid fa-spinner fa-spin"></i> <?php endif; ?>
                                            <?php echo ucfirst($this->escape($item->crawl_status)); ?>
                                        </span>
                                        <?php if ($item->crawl_status === 'error' && !empty($item->crawl_error)) : ?>
                                            <div class="small text-danger" style="margin-top:4px;font-size:11px"><?php echo $this->escape(substr($item->crawl_error, 0, 100)); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center" style="font-weight:600"><?php echo (int) $item->chunk_count; ?></td>
                                    <td class="se-text-muted">
                                        <?php if (!empty($item->last_crawled)) : ?>
                                            <?php echo HTMLHelper::_('date', $item->last_crawled, 'M j, Y g:ia'); ?>
                                        <?php else : ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isSppb) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_seaichat&task=knowledgebases.processsppb&id=' . $item->id . '&' . \Joomla\CMS\Session\Session::getFormToken() . '=1'); ?>"
                                               class="se-btn se-btn-sm se-btn-primary"
                                               onclick="return confirm('Re-index SP Page Builder pages?');"
                                               style="padding:6px 12px;font-size:12px">
                                                <i class="fa-solid fa-rotate"></i>
                                                <?php echo $item->crawl_status === 'complete' ? 'Re-index' : 'Index Now'; ?>
                                            </a>
                                        <?php elseif ($isArticles) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_seaichat&task=knowledgebases.processarticles&id=' . $item->id . '&' . \Joomla\CMS\Session\Session::getFormToken() . '=1'); ?>"
                                               class="se-btn se-btn-sm se-btn-primary"
                                               onclick="return confirm('Re-index articles now?');"
                                               style="padding:6px 12px;font-size:12px">
                                                <i class="fa-solid fa-rotate"></i>
                                                <?php echo $item->crawl_status === 'complete' ? 'Re-index' : 'Index Now'; ?>
                                            </a>
                                        <?php elseif ($isText) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_seaichat&task=knowledgebases.processtext&id=' . $item->id . '&' . \Joomla\CMS\Session\Session::getFormToken() . '=1'); ?>"
                                               class="se-btn se-btn-sm se-btn-primary"
                                               onclick="return confirm('Process this text content?');"
                                               style="padding:6px 12px;font-size:12px">
                                                <i class="fa-solid fa-gears"></i>
                                                <?php echo $item->crawl_status === 'complete' ? 'Re-process' : 'Process'; ?>
                                            </a>
                                        <?php else : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_seaichat&task=knowledgebases.crawl&id=' . $item->id . '&' . \Joomla\CMS\Session\Session::getFormToken() . '=1'); ?>"
                                               class="se-btn se-btn-sm se-btn-primary"
                                               onclick="return confirm('Start crawling this documentation source?');"
                                               style="padding:6px 12px;font-size:12px">
                                                <i class="fa-solid fa-spider"></i>
                                                <?php echo $item->crawl_status === 'complete' ? 'Re-crawl' : 'Crawl Now'; ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
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
