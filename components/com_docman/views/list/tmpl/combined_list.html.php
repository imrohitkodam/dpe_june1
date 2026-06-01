<?
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die; ?>

<?= helper('ui.load'); ?>
<?= helper('behavior.modal');?>


<? // RSS feed ?>
<link href="<?=route('format=rss');?>" rel="alternate" type="application/rss+xml" title="RSS 2.0" />


<div class="docman_list_layout docman_list_layout--default">

    <? // Page Heading ?>
    <? if ($params->get('show_page_heading')): ?>
    <h1 class="docman_page_heading">
        <?= escape($params->get('page_heading')); ?>
    </h1>
    <? endif; ?>

    <? // Toolbar ?>
    <ktml:toolbar type="actionbar">

    <? // Documents header & sorting ?>
    <? if (count($documents) || ($params->show_document_search)): ?>
        <? // Documents & pagination  ?>
        <form action="" method="get" class="k-js-grid-controller">

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

            <? // Document list | Import child template from documents view ?>
            <?= import('hybrid_list.html',array(
                'documents' => $documents,
                'params' => $params
            ))?>

            <? // Pagination  ?>
            <? if (parameters()->total) : ?>
                <?= helper('paginator.pagination', array(
                    'show_limit' => (bool) $params->show_document_sort_limit
                )) ?>
            <? endif; ?>

        </form>
    <? endif; ?>
</div>
