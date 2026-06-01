<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die;?>

<div class="logman_table_layout logman_table_layout--default">
    <? if (parameters()->total): ?>
        <table class="table table-striped koowa_table koowa_table--activities">
            <tbody>
            <? $date = $old_date = '';   ?>
            <? foreach ($activities as $activity) : ?>
                <? if ($show_date): ?>
                    <? $date = helper('date.format', array('date' => $activity->created_on, 'format' => translate('DATE_FORMAT_LC3')))?>
                    <? if ($date != $old_date): ?>
                        <? $old_date = $date; ?>
                        <tr class="logman_table_layout__item--header">
                            <th colspan="<?= ($show_time && $show_date) ? 2 : 1 ?>">
                                <?= $date; ?>
                            </th>
                        </tr>
                    <? endif ?>
                <? endif ?>
                <tr class="logman_table_layout__item">
                    <td class="logman_table_layout__message">
                        <div class="koowa_wrapped_content">
                            <div class="whitespace_preserver">
                                <? if ($show_icons): ?>
                                    <i class="<?= $activity->image ?>"></i>
                                <? endif ?>
                                <?= helper('activity.activity', array('entity' => $activity, 'scripts' => true)) ?>
                            </div>
                        </div>
                    </td>
                    <? if ($show_time && $show_date): ?>
                        <td class="logman_table_layout__time"><?= helper('activity.when', array('entity' => $activity)) ?></td>
                    <? endif ?>
                </tr>
            <? endforeach ?>
            </tbody>
        </table>
    <? endif ?>
</div>
