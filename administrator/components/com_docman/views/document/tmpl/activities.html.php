<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2020 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die;?>

<? if (isset($activities) && $activities->count): ?>
    <? $date = $old_date = '';   ?>

    <div class="k-table-container">
        <div class="k-table">
            <table class="k-js-responsive-table">
                <thead>
                <tr>
                    <th><?= translate('Activity') ?></th>
                    <th width="1%"><?= translate('Time') ?></th>
                </tr>
                </thead>
                <tbody>
                <? foreach ($activities->entities as $activity) : ?>
                    <? $date = helper('date.format', array('date' => $activity->created_on, 'format' => translate('DATE_FORMAT_LC3')))?>
                    <? if ($date != $old_date): ?>
                        <? $old_date = $date; ?>
                        <tr>
                            <td colspan="2"><strong><?= $date; ?></strong></td>
                        </tr>
                    <? endif ?>

                    <tr>
                        <td class="k-table-data--multiline">
                            <i class="<?= $activity->image ?>"></i>
                            <?= helper('com://admin/logman.activity.activity', array('entity' => $activity, 'links' => true, 'scripts' => true)) ?>
                        </td>
                        <td><?= helper('com://admin/logman.activity.when', array('entity' => $activity)) ?></td>
                    </tr>
                <? endforeach ?>
                </tbody>
            </table>

            <? if ($show_view_more): ?>
                <a href="<?= route(sprintf('view=activities&package=docman&search=%s', urlencode('#document_id:' . $activity->row))) ?>" class="k-button k-button--default k-button--block">
                    <?= translate('View more') ?>
                </a>
            <? endif ?>
        </div>
    </div>
<? else: ?>
<div class="k-alert k-alert--info">
    <p class="activities_empty">
        <?= translate('There are no activities at this moment') ?>
    </p>
</div>
<? endif ?>