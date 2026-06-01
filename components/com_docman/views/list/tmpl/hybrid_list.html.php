<?
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die; ?>

<? if ($params->track_downloads): ?>
    <?= helper('behavior.download_tracker'); ?>
<? endif; ?>

<? if (!empty($can_add)): ?>
    <?= helper('behavior.modal'); ?>
<? endif; ?>

<? $category_title = null ?>

<? foreach ($documents as $document): ?>
    <? if ($category_title != $document->category_title): ?>
        <? // Category ?>
        <? $category = $categories[$document->docman_category_id] ?>
        <? if (($params->show_category_title && $category->title)
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
                    <?= import('com://site/docman.document.icon.html', array('icon' => $category->icon, 'class' => 'k-icon--size-medium')) ?>
                </span>
                        <? endif ?>

                        <? // Header title ?>
                        <? if ($params->show_category_title): ?>
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
                        <? endif; ?>
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
    <? endif ?>
    <? // Document | Import child template from document view ?>
    <?= import('com://site/docman.document.document.html', array(
        'document' => $document,
        'params' => $params,
        'heading' => '4',
        'buttonstyle' => 'btn-default',
        'link' => 1,
        'description' => 'summary'
    )) ?>
    <? $category_title = $document->category_title ?>
<? endforeach ?>