<?
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2012 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die; ?>

<?= helper('ui.load'); ?>
<?= helper('behavior.modal'); ?>
<?= helper('behavior.downloadlabel', array('params' => $params)); ?>

<? if ($params->track_downloads): ?>
    <?= helper('behavior.download_tracker'); ?>
<? endif; ?>

<? // RSS feed ?>
<link href="<?=route('format=rss');?>" rel="alternate" type="application/rss+xml" title="RSS 2.0" />

<div class="docman_list_layout docman_list_layout--default">

    <? if ($params->get('show_page_heading')): ?>
        <h1>
            <?= escape($params->get('page_heading')); ?>
        </h1>
    <? endif; ?>

    <? // Documents header & sorting ?>
    <div class="docman_block">
        <? if ($params->show_documents_header): ?>
            <h3 class="koowa_header">
                <?= translate('Documents')?>
            </h3>
        <? endif; ?>
    </div>

    <? // Documents & pagination  ?>
    <form action="<?= $is_standalone ?  route('itemless=1') : '' ?>" method="get" class="k-js-grid-controller">

        <? // Search ?>
        <?= import('search.html') ?>

        <? // Sorting ?>
        <? if ($params->show_document_sort_limit && count($documents)): ?>
            <div class="docman_sorting form-search">
                <label for="sort-documents" class="control-label"><?= translate('Order by') ?></label>
                <?= helper('paginator.sort_documents', array(
                    'sort'      => 'document_sort',
                    'direction' => 'document_direction',
                    'attribs'   => array('class' => 'input-medium', 'id' => 'sort-documents')
                )); ?>
            </div>
        <? endif; ?>

        <? // Table ?>
        <table class="table table-striped koowa_table koowa_table--documents k-js-documents-table">
            <tbody>
            <? foreach ($documents as $document): ?>
                <tr class="docman_item" data-document="<?= $document->uuid ?>" itemscope itemtype="http://schema.org/CreativeWork">
                    <? // Title and labels ?>
                    <td>
                        <meta itemprop="contentUrl" content="<?= $document->image_download_path ?>">
                        <span class="koowa_header">
                            <? // Icon ?>
                            <? if ($document->icon && $params->show_document_icon): ?>
                                <span class="koowa_header__item koowa_header__item--image_container">
                                    <? if ((($is_standalone && $document->canPerform('download')) || (!$is_standalone && (!object('user')->isAuthentic() || $document->canPerform('download')))) && $params->document_title_link): ?>
                                        <a href="<?= ($document->download_link) ?>"
                                        <?= $params->download_in_blank_page && $params->document_title_link === 'download'  ? 'target="_blank"' : ''; ?>
                                        >
                                    <? endif; ?>

                                    <?= import('com://site/docman.document.icon.html', array(
                                        'icon'  => $document->icon,
                                        'class' => ' k-icon--size-default'.strlen($document->extension) ? ' k-icon-type-'.$document->extension : ' k-icon-type-remote'
                                    )) ?>

                                    <? if ($params->document_title_link): ?>
                                        </a>
                                    <? endif; ?>
                                </span>
                            <? endif ?>

                            <? // Title ?>
                            <span class="koowa_header__item">
                                <span class="koowa_wrapped_content">
                                    <span class="whitespace_preserver">
                                        <? if ($params->show_document_title): ?>
                                            <? if ((($is_standalone && $document->canPerform('download')) || (!$is_standalone && (!object('user')->isAuthentic() || $document->canPerform('download')))) && $params->document_title_link): ?>
                                                <a href="<?= ($params->document_title_link == 'download') ? $document->download_link : $document->title_link ?>" title="<?= escape($document->storage->name);?>"
                                                class="<?= $params->document_title_link === 'download' ? 'docman_track_download' : ''; ?>"
                                                data-title="<?= escape($document->title); ?>"
                                                data-id="<?= $document->id; ?>"
                                                    type="<?= $document->mimetype ?>"
                                                    <?= $params->download_in_blank_page  ? 'target="_blank"' : ''; ?>
                                                ><span itemprop="name"><?= helper('text.highlight', [
                                                        'keyword' => parameters()->search,
                                                        'string' => escape($document->title)]);?></span><!--
                                                    --><? if ($document->title_link === $document->download_link): ?>
                                                    <? // Filetype and Filesize  ?>
                                                    <? if (($params->show_document_size && $document->size) || ($document->storage_type == 'file' && $params->show_document_extension)): ?>
                                                        <span class="docman_download__info">(
                                                                <? if ($document->storage_type == 'file' && $params->show_document_extension): ?>
                                                                    <?= escape($document->extension . ($params->show_document_size && $document->size ? ', ':'')) ?>
                                                                <? endif ?>
                                                            <? if ($params->show_document_size && $document->size): ?>
                                                                <?= helper('string.humanize_filesize', array('size' => $document->size)) ?>
                                                            <? endif ?>
                                                            )</span>
                                                    <? endif; ?>
                                                    <? endif ?><!--
                                                --></a>
                                            <? else: ?>
                                                <span title="<?= escape($document->storage->name);?>">
                                                    <span itemprop="name"><?= escape($document->title);?></span>
                                                    <? if ($document->title_link === $document->download_link
                                                        && ($params->show_document_size && $document->size || $document->storage_type == 'file' && $params->show_document_extension)): ?>
                                                        (<?= $document->extension ? $document->extension.', ' : '' ?><?= helper('string.humanize_filesize', array('size' => $document->size)); ?>)
                                                    <? endif; ?>
                                                </span>
                                            <? endif; ?>
                                        <? endif; ?>

                                        <? // Category ?>
                                        <? if ($params->show_document_category): ?>
                                            <span class="detail-label"><?= strtolower(translate('In')) ?></span>
                                            <? $route = sprintf('view=search&category=%s&%s', $document->docman_category_id, $is_standalone ? 'itemless=1' : '') ?>
                                            <span class="detail-label"><a href="<?= route("{$route}") ?>"><?= $document->category->title ?></a></span>
                                        <? endif ?>

                                        <? // Document hits ?>
                                        <? if ($params->show_document_hits && $document->hits): ?>
                                            <meta itemprop="interactionCount" content=”UserDownloads:<?= $document->hits ?>">
                                            <span class="detail-label">(<?= object('translator')->choose(array('{number} download', '{number} downloads'), $document->hits, array('number' => $document->hits)) ?>)</span>
                                        <? endif; ?>

                                        <? // Label new ?>
                                        <? if ($params->show_document_recent && isRecent($document)): ?>
                                            <span class="label label-success badge bg-success"><?= translate('New'); ?></span>
                                        <? endif; ?>

                                        <? // Label status ?>
                                        <? if (!$document->enabled || $document->status !== 'published'): ?>
                                            <? $status = $document->enabled ? translate($document->status) : translate('Draft'); ?>
                                            <span class="label label-<?= $document->enabled ? $document->status : 'draft' ?> badge badge-<?= $document->enabled ? 'info' : 'danger' ?>"><?= ucfirst($status); ?></span>
                                        <? endif; ?>

                                        <? // Label owner ?>
                                        <? if ($params->get('show_document_owner_label', 1) && object('user')->getId() == $document->created_by): ?>
                                            <span class="label label-success badge bg-primary"><?= translate('Owner'); ?></span>
                                        <? endif; ?>

                                        <? // Label popular ?>
                                        <? if ($params->show_document_popular && ($document->hits >= $params->hits_for_popular)): ?>
                                            <span class="label label-danger label-important badge bg-warning"><?= translate('Popular') ?></span>
                                        <? endif ?>
                                    </span>
                                </span>
                            </span>
                        </span>
                    </td>

                    <? // Date ?>
                    <? if ($params->show_document_created): ?>
                        <td width="5" class="koowa_table__dates">
                            <time itemprop="datePublished"
                                datetime="<?= parameters()->sort === 'touched_on' ? $document->touched_on : $document->publish_date ?>"
                            >
                                <?= helper('date.format', array(
                                    'date' => parameters()->sort === 'touched_on' ? $document->touched_on : $document->publish_date,
                                    'format' => 'd M Y')); ?>
                            </time>
                        </td>
                    <? endif; ?>

                    <? // Download ?>
                    <td width="5" class="koowa_table__download k-no-wrap">
                        <? //hide download for audio/video ?>
                        <? $can_show_player = !$params->force_download && $params->show_player; ?>
                        <? if ((($is_standalone && $document->canPerform('download')) || (!$is_standalone && (!object('user')->isAuthentic() || $document->canPerform('download')))) && (!$can_show_player || !$document->isPlayable())): ?>
                            <a class="btn btn-primary btn-sm docman_track_download docman_download__button" href="<?= $document->download_link; ?>"
                                <?= $params->download_in_blank_page ? 'target="_blank"' : ''; ?>
                            data-title="<?= escape($document->title); ?>"
                            data-id="<?= $document->id; ?>"
                            type="<?= $document->mimetype ?>"
                                <? if(!$params->force_download): ?>
                                    data-mimetype="<?= $document->mimetype ?>"
                                    data-extension="<?= $document->extension ?>"
                                <? endif; ?>
                            >
                                <span class="docman_download_label">
                                    <?= translate('Download'); ?>
                                </span>

                                <? // Filetype and Filesize  ?>
                                <? if (($params->show_document_size && $document->size) || ($document->storage_type == 'file' && $params->show_document_extension)): ?>
                                    <span class="docman_download__info docman_download__info--inline">(<!--
                                --><? if ($document->storage_type == 'file' && $params->show_document_extension): ?><!--
                                    --><?= escape($document->extension . ($params->show_document_size && $document->size ? ', ':'')) ?><!--
                                --><? endif ?><!--
                                --><? if ($params->show_document_size && $document->size): ?><!--
                                    --><?= helper('string.humanize_filesize', array('size' => $document->size)) ?><!--
                                --><? endif ?><!--
                                -->)</span>
                                <? endif; ?>
                            </a>
                        <? endif; ?>
                    </td>
                </tr>
            <? endforeach ?>
            </tbody>
        </table>

        <? // Pagination  ?>
        <? if (parameters()->total) : ?>
            <?= helper('paginator.pagination', array(
                'show_limit' => (bool) $params->show_document_sort_limit
            )) ?>
        <? endif; ?>

    </form>

</div>