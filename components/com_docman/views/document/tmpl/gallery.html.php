<?
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die; ?>

<?= helper('ui.load'); ?>
<?= helper('com://site/docman.behavior.modal'); ?>
<?= helper('com://site/docman.behavior.thumbnail_modal'); ?>

<? if ($params->track_downloads): ?>
    <?= helper('com://site/docman.behavior.download_tracker'); ?>
<? endif; ?>

<?= helper('translator.script', array('strings' => array(
    'Download'
))) ?>

<meta itemprop="contentUrl" content="<?= $document->image_download_path ?>">

<? if ($params->document_title_link): ?>
<? if ($document->isPreviewableImage()): ?>
<? if ($document->storage->width): ?>
    <meta itemprop="width" content="<?= $document->storage->width; ?>">
<? endif; ?>
<? if ($document->storage->height): ?>
    <meta itemprop="height" content="<?= $document->storage->height; ?>">
<? endif; ?>
<!-- <meta itemprop="contentUrl" content="<?= $document->image_download_path ?>"> -->

<a class="koowa_media__item__link <?= $params->document_title_link == 'slideshow' ? 'k-js-gallery-item' : '' ?> <?= $params->document_title_link === 'download' ? 'docman_track_download' : ''; ?>"
   data-path="<?= $document->image_path ?>"
   data-title="<?= escape($document->title); ?>"
   data-id="<?= $document->id; ?>"
   data-width="<?= $document->storage->width; ?>"
   data-height="<?= $document->storage->height; ?>"
   href="<?= $document->title_link ?>"
   title="<?= escape($document->title) ?>"
    <?= $params->document_title_link === 'download'  ? 'type="'.$document->mimetype.'"' : ''; ?>
>
<? else: ?>
<a class="koowa_media__item__link <?= $params->document_title_link === 'download' ? 'docman_track_download' : ''; ?>"
    <?= $params->download_in_blank_page ? 'target="_blank"' : ''; ?>
    data-title="<?= escape($document->title); ?>"
    data-id="<?= $document->id; ?>"
    href="<?= $document->title_link ?>"
    title="<?= escape($document->title) ?>"
    <?= $params->document_title_link === 'download'  ? 'type="'.$document->mimetype.'"' : ''; ?>
>
<? endif; ?>
<? endif; // if ($params->document_title_link) ?>
    <div class="koowa_media__item__content-holder">
        <? if( $document->image_path ): ?>
            <div class="koowa_media__item__thumbnail">
                <img itemprop="thumbnail" src="<?= $document->image_path ?>" alt="<?= escape($document->title) ?>">
            </div>
        <? else: ?>
            <div class="koowa_media__item__icon">
                <?= import('com://site/docman.document.icon.html', array(
                    'icon'  => $document->icon,
                    'class' => ' k-icon--size-xlarge'.(strlen($document->extension) ? ' k-icon-type-'.$document->extension : '')
                )); ?>
            </div>
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

        <? // Label new ?>
        <? if ($params->show_document_recent && isRecent($document)): ?>
            <span class="label label-success badge bg-success"><?= translate('New'); ?></span>
        <? endif; ?>

        <? // Label popular ?>
        <? if ($params->show_document_popular && ($document->hits >= $params->hits_for_popular)): ?>
            <span class="label label-danger label-important badge bg-warning"><?= translate('Popular') ?></span>
        <? endif ?>

        <? if ($params->show_document_title): ?>
            <div class="koowa_header koowa_media__item__label">
                <div class="koowa_header__item koowa_header__item--title_container">
                    <div class="koowa_wrapped_content">
                        <div class="whitespace_preserver">
                            <div class="overflow_container">
                                <?= escape($document->title) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <? endif; ?>
    </div>
<? if ($params->document_title_link): ?>
</a>
<? endif ?>
<? if($multi_download || ((!isset($manage) || $manage === true) && ($can_delete || $document->canPerform('edit')))): ?>
<div class="koowa_media__item__options">
    <? if ($can_delete || $multi_download): ?>
        <span class="koowa_media__item__options__select">
            <input id="document-select-<?= $count; ?>" name="item-select" type="checkbox"
                   class="k-js-item-select"
                   data-can-download="<?= $document->canPerform('download') ?>"
                   data-storage-type="<?= $document->storage_type ?>"
                   data-id="<?= $document->id ?>"
                   data-url="<?= $document->document_link ?>" />
            <label for="document-select-<?= $count; ?>" class="k-visually-hidden">Select an item</label>
        </span>
    <? endif ?>

    <? if ($can_delete): ?>
        <?
        $data = array(
            'url'    => (string)helper('route.document',array('entity' => $document), true, false),
            'params' => array(
                '_method'    => 'delete',
            )
        );
        ?>
        <a href="#" data-action="delete-item" class="koowa_media__item__options__delete" aria-label="<?= translate('Delete document') ?>"
           data-params="<?= escape(json_encode($data['params'])) ?>"
           data-url="<?= escape($data['url']) ?>"
            >
            <span class="k-icon-trash k-icon--size-default"></span>
        </a>
    <? endif; ?>

    <? if ($document->canPerform('edit')): ?>
        <a href="<?= helper('route.document', array('entity' => $document, 'layout' => 'form'));?>" class="koowa_media__item__options__edit" aria-label="<?= translate('Edit document') ?>">
            <span class="k-icon-pencil k-icon--size-default"></span>
        </a>
    <? endif ?>
</div>
<? endif; ?>
