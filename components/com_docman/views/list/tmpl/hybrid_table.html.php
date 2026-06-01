<?
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2012 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die; ?>

<ktml:script src="media://com_docman/js/footable.js" />
<ktml:script src="media://com_docman/js/clipboardjs/clipboard.min.js" />
<ktml:script src="media://com_docman/js/docman.js" />

<ktml:style src="media://com_docman/css/tooltips.css"/>

<script>
kQuery(function($) {
    $('.k-js-documents-table').footable({
        toggleSelector: '.footable-toggle',
        breakpoints: {
            phone: 400,
            tablet: 600,
            desktop: 800
        }
    }).bind('footable_row_detail_updated', function(event) {
        var container = event.detail;

        container.find('.btn-mini').addClass('btn-small').removeClass('btn-mini');

        container.find('.footable-row-detail-value').css('display', 'inline-block');

        container.find('.footable-row-detail-name').css('display', 'inline-block')
            .each(function() {
                var $this = $(this);

                if ($.trim($this.text()) == '') {
                    $this.remove();
                }
            });

    });

    Docman.copyboard({
        target: '.k-js-docman-copy',
        tooltips: {
            message: <?= json_encode(translate('Copied!')) ?>
        }
    });

    Docman.tooltips({
        target: '.k-js-docman-copy',
        message: <?= json_encode(translate('Copy download link to clipboard')) ?>,
        handlers: {
            show: function (el) {
                var that = this;
                el.mouseover(function () {
                    that.show(el);
                });
            }
        }
    });
});
</script>

<?= helper('behavior.downloadlabel', array('params' => $params)); ?>

<? if ($params->track_downloads): ?>
    <?= helper('behavior.download_tracker'); ?>
<? endif; ?>

<? if (!empty($can_add)): ?>
    <?= helper('behavior.modal'); ?>
<? endif; ?>

<? // Search ?>
<?= import('com://site/docman.documents.search.html') ?>

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
    <thead style="display: none">

    <?
    $can_manage = false;
    foreach ($documents as $document) {
        if ($document->canPerform('edit') || $document->canPerform('delete')) {
            $can_manage = true;
            break;
        }
    } ?>

    <tr>
        <? if ($can_delete || $multi_download): ?>
        <th width="1%" data-hide="phone"><?= translate('Select'); ?></th>
        <? endif ?>
        <th width="1%" data-toggle="true" class="k-table-data--toggle"><?= translate('Toggle'); ?></th>
        <th><?= translate('Title'); ?></th>
        <? if ($params->show_document_created): ?>
        <th width="1%" data-hide="phone"><?= translate('Date'); ?></th>
        <? endif; ?>
        <? if ($params->document_title_link !== 'download'): ?>
        <th width="1%" data-hide="phone,tablet"><?= translate('Download'); ?></th>
        <? endif; ?>
        <? if ($can_manage): ?>
        <th data-hide="phone,tablet"><?= translate('Manage'); ?></th>
        <? endif ?>
    </tr>
    </thead>
    <tbody>

    <? $category_title = null ?>

    <? foreach ($documents as $document): ?>

        <? if ($category_title != $document->category_title): ?>
            <? $category = $categories[$document->docman_category_id] ?>
            <tr class="k-table__sub-header">
                <th colspan="7">
                    <? // Category ?>
                    <? if (($params->show_icon && $category->icon)
                        || ($params->show_category_title)
                        || ($params->show_image && $category->image)
                        || ($category->description_full && $params->show_description)
                    ): ?>
                        <div class="docman_category">

                            <? // Header ?>
                            <? if ($params->show_category_title && $category->title): ?>
                                <h3 class="koowa_header">
                                    <? // Header image ?>
                                    <? if ($params->show_icon && $category->icon): ?>
                                        <span class="koowa_header__item koowa_header__item--image_container">
                                            <?= import('com://site/docman.document.icon.html', array('icon' => $category->icon, 'class' => ' k-icon--size-medium')) ?>
                                        </span>
                                    <? endif ?>

                                    <? // Header title ?>
                                    <span class="koowa_header__item">
                                        <span class="koowa_wrapped_content">
                                            <span class="whitespace_preserver">
                                                <?= escape($category->title); ?>

                                                <? // Label locked ?>
                                                <? if ($category->canPerform('edit') && $category->isLockable() && $category->isLocked()): ?>
                                                    <span class="label label-warning badge bg-dark"><?= translate('Locked'); ?></span>
                                                <? endif; ?>

                                                <? // Label status ?>
                                                <? if (!$category->enabled): ?>
                                                    <span class="label label-draft badge bg-danger"><?= translate('Draft'); ?></span>
                                                <? endif; ?>

                                                <? // Label owner ?>
                                                <? if ($params->get('show_category_owner_label', 1) && !$category->isNew() && object('user')->getId() == $category->created_by): ?>
                                                    <span class="label label-success badge bg-primary"><?= translate('Owner'); ?></span>
                                                <? endif; ?>
                                            </span>
                                        </span>
                                    </span>
                                </h3>
                            <? endif; ?>

                            <? // Edit area | Import partial template from category view ?>
                            <?= import('com://site/docman.category.manage.html', array('category' => $category)) ?>

                            <? // Category image ?>
                            <? if ($params->show_image && $category->image): ?>
                                <?= helper('behavior.thumbnail_modal'); ?>
                                <a class="docman_thumbnail thumbnail" href="<?= $category->image_path ?>">
                                    <img src="<?= $category->image_path ?>" alt="<?= escape($category->title); ?>" />
                                </a>
                            <? endif ?>

                            <? // Category description full ?>
                            <? if ($category->description_full && $params->show_description): ?>
                                <div class="docman_description">
                                    <?= prepareText($category->description_full); ?>
                                </div>
                            <? endif; ?>
                        </div>
                    <? endif; ?>
                </th>
            </tr>
        <? endif ?>

        <tr class="docman_item" data-document="<?= $document->uuid ?>" itemscope itemtype="http://schema.org/CreativeWork">
            <? if ($can_delete || $multi_download): ?>
            <td>
                <input name="item-select" class="k-js-item-select" type="checkbox"
                       data-id="<?= $document->id ?>" type="checkbox" data-url="<?= $document->document_link ?>"
                       data-storage-type="<?= $document->storage_type ?>"
                       data-can-download="<?= $document->canPerform('download') ?>"
                />
            </td>
            <? endif; ?>
            <td class="k-table-data--toggle"></td>
            <? // Title and labels ?>
            <td>
                <meta itemprop="contentUrl" content="<?= $document->image_download_path ?>">
                <span class="koowa_header">


                    <? // Title ?>
                    <span class="koowa_header__item">
                        <span class="koowa_wrapped_content">
                            <span class="whitespace_preserver">
                                <? if ($params->document_title_link): ?>
                                    <a href="<?= $document->title_link ?>" title="<?= escape($document->storage->name);?>"
                                       class="<?= $params->document_title_link === 'download' ? 'docman_track_download' : ''; ?>"
                                       data-title="<?= escape($document->title); ?>"
                                       data-id="<?= $document->id; ?>"
                                        <?= $params->document_title_link === 'download'  ? 'type="'.$document->mimetype.'"' : ''; ?>
                                        <?= $params->download_in_blank_page && $params->document_title_link === 'download'  ? 'target="_blank"' : ''; ?>
                                    >
                                        <? // Icon ?>
                                        <? if ($document->icon && $params->show_document_icon): ?>
                                            <span class="koowa_header__item--image_container">
                                                <?= import('com://site/docman.document.icon.html', array(
                                                    'icon'  => $document->icon,
                                                    'class' => ' k-icon--size-default'.strlen($document->extension) ? ' k-icon-type-'.$document->extension : ' k-icon-type-remote'
                                                )) ?>
                                            </span>
                                        <? endif ?>

                                        <span itemprop="name"><?= escape($document->title);?></span><!--
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

                                <? // Document hits ?>
                                <? if ($params->show_document_hits && $document->hits): ?>
                                    <meta itemprop="interactionCount" content=”UserDownloads:<?= $document->hits ?>">
                                    <span class="detail-label">(<?= object('translator')->choose(array('{number} download', '{number} downloads'), $document->hits, array('number' => $document->hits)) ?>)</span>
                                <? endif; ?>

                                <? // Label new ?>
                                <? if ($params->show_document_recent && isRecent($document)): ?>
                                    <span class="label label-success badge bg-success"><?= translate('New'); ?></span>
                                <? endif; ?>

                                <? // Label locked ?>
                                <? if ($document->canPerform('edit') && $document->isLockable() && $document->isLocked()): ?>
                                    <span class="label label-warning badge bg-warning"><?= translate('Locked'); ?></span>
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
                        'date' => parameters()->sort === 'touched_on' ? $document->touched_on : $document->publish_date, 'format' => translate('Date format compact'))); ?>
                </time>
            </td>
            <? endif; ?>

            <? // Download ?>
            <? if ($params->document_title_link !== 'download'): ?>
            <td width="5" class="koowa_table__download k-no-wrap">
                <? //hide download for audio/video ?>
                <? $can_show_player = !$params->force_download && $params->show_player; ?>
                <? if (!$can_show_player || !$document->isPlayable()): ?>
                <a class="btn btn-default btn-mini docman_track_download docman_download__button" href="<?= $document->download_link; ?>"
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
            <? endif; ?>

            <? if ($can_copy): ?>
            <td>
                <a class="btn btn-small k-js-docman-copy"
                    data-clipboard-text="<?= $document->copy_link ?>" href="#">
                    <span class="k-icon-clipboard"></span>
                </a>
            </td>
            <? endif ?>

            <? // Edit buttons ?>
            <? if ($can_manage): ?>
            <td class="koowa_table__manage">
                <? if ($document->canPerform('edit') || $document->canPerform('delete')): ?>
                <? // Manage | Import partial template from document view ?>
                <?= import('com://site/docman.document.manage.html', array(
                    'document' => $document,
                    'button_size' => 'mini'
                )) ?>
                <? endif; ?>
            </td>
            <? endif; ?>
        </tr>

        <? $category_title = $document->category_title ?>

    <? endforeach ?>
    </tbody>
</table>
