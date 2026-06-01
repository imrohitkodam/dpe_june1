<?
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2012 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die; ?>

<? if ($can_delete): ?>
    <ktml:script src="media://com_docman/js/site/items.js" />
<? endif; ?>

<? if (!empty($can_add)): ?>
    <?= helper('behavior.modal'); ?>
<? endif; ?>

<? if ($multi_download): ?>
    <?= helper('behavior.multidownload'); ?>
<? endif; ?>

<? if ($multi_download || $can_delete): ?>
    <?= helper('behavior.multiselect'); ?>
<? endif; ?>

<?= helper('ui.load'); ?>

<? // RSS feed ?>
<link href="<?=route('format=rss');?>" rel="alternate" type="application/rss+xml" title="RSS 2.0" />


<? if ($params->track_downloads): ?>
    <?= helper('behavior.download_tracker'); ?>
<? endif; ?>

<div class="docman_table_layout docman_table_layout--default">

    <? // Page heading ?>
    <? if ($params->get('show_page_heading')): ?>
        <h1 class="docman_page_heading">
            <?= escape($params->get('page_heading')); ?>
        </h1>
    <? endif; ?>

    <? // Toolbar ?>
    <ktml:toolbar type="actionbar">

        <? // Tables ?>
        <form action="" method="get" class="k-js-grid-controller koowa_table_list">

            <? // Documents table | Import child template from documents view ?>
            <? if (count($documents) || ($params->show_document_search)): ?>
                <?= import('hybrid_table.html') ?>
            <? endif; ?>

            <? // Pagination ?>
            <? if (parameters()->total): ?>
                <?= helper('paginator.pagination', array(
                    'show_limit' => (bool) $params->show_document_sort_limit
                )) ?>
            <? endif; ?>

        </form>
</div>
