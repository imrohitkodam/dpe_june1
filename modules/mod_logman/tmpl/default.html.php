<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die;?>

<?= helper('ui.load') ?>

<? if (parameters()->total): ?>
    <? $date = $old_date = '';   ?>
    <div class="k-ui-namespace">
        <div class="mod_logman mod_logman--activities<?= $params->moduleclass_sfx; ?> <?= JFactory::getLanguage()->isRTL() ? ' k-ui-rtl' : 'k-ui-ltr' ?>">
            <ul<?= $params->show_icons ? ' class="mod_logman_icons"' :'' ?>>
                <? foreach ($activities as $activity) : ?>
                    <? if ($params->show_date): ?>
                        <? $date = helper('date.format', array('date' => $activity->created_on, 'format' => translate('DATE_FORMAT_LC3')))?>
                        <? if ($date != $old_date): ?>
                            <? $old_date = $date; ?>
                            <li class="mod_logman__header">
                                <?= $date; ?>
                            </li>
                        <? endif ?>
                    <? endif ?>
                    <li class="mod_logman__item">
                        <div class="koowa_header">
                            <? if ($params->show_icons): ?>
                            <span class="koowa_header__item koowa_header__item--image_container">
                                <i class="<?= $activity->image ?>"></i>
                            </span>
                            <? endif ?>
                            <span class="koowa_header__item">
                                <span class="koowa_wrapped_content">
                                    <span class="whitespace_preserver">
                                        <?= helper('activity.activity', array('entity' => $activity, 'links' => $links, 'scripts' => true)) ?>
                                    </span>
                                </span>
                            </span>
                        </div>
                        <? if ($params->show_time && $params->show_date): ?>
                            <div class="activity__date">
                                <?= helper('activity.when', array('entity' => $activity)) ?>
                            </div>
                        <? endif ?>
                    </li>
                <? endforeach ?>
            </ul>
        </div>
    </div>
<? else: ?>
    <p class="alert alert-info">
        <?= translate('There are no activities at this moment') ?>
    </p>
<? endif ?>

