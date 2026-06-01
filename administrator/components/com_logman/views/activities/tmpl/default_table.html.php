<?
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('_JEXEC') or die; ?>


<!-- Table -->
<div class="k-table-container">
    <div class="k-table">

        <table class="k-js-responsive-table">
            <thead>
            <tr>
                <th width="1%" class="k-table-data--form">
                    <?= helper('grid.checkall') ?>
                </th>
                <th width="1%" class="k-table-data--toggle" data-toggle="true"></th>
                <th width="1%" class="k-table-data--icon"></th>
                <th data-toggle="true">
                    <?=translate('Message')?>
                </th>
                <th width="1%" data-hide="phone,tablet">
                    <?=translate('Time')?>
                </th>
                <th width="1%" data-hide="phone,tablet">
                    <?=translate('IP')?>
                </th>
            </tr>
            </thead>
            <tbody>
            <? $date = $old_date = '';   ?>
            <? foreach ($activities as $activity) : ?>
                <? $date = helper('date.format', array('date' => $activity->created_on, 'format' => translate('DATE_FORMAT_LC3')))?>
                <? if ($date != $old_date): ?>
                    <? $old_date = $date; ?>
                    <tr class="k-table__sub-header">
                        <th colspan="6">
                            <?= $date; ?>
                        </th>
                    </tr>
                <? endif; ?>
                <tr>
                    <td class="k-table-data--form">
                        <?= helper('grid.checkbox',array('entity' => $activity)); ?>
                    </td>
                    <td class="k-table-data--toggle"></td>
                    <td class="k-table-data--icon">
                        <span class="logman-icon <?=$activity->image?>"></span>
                    </td>
                    <td class="k-table-data--multiline">
                        <?= helper('activity.activity', array('entity' => $activity, 'scripts' => true))?>
                    </td>
                    <td class="k-table-data--nowrap">
                        <?= helper('activity.when', array('entity' => $activity))?>
                    </td>
                    <td class="k-table-data--nowrap">
                        <?= $activity->ip ?>
                    </td>
                </tr>
            <? endforeach; ?>
            </tbody>
        </table>
    </div><!-- .k-table -->

    <? if (count($activities)): ?>
        <div class="k-table-pagination">
            <?= helper('paginator.pagination') ?>
        </div><!-- .k-table-pagination -->
    <? endif; ?>

</div><!-- .k-table-container -->
