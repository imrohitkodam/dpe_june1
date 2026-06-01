<?
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('_JEXEC') or die; ?>


<? if(count($activities)) : ?>
    <?= helper('ui.load', array(
        'type'   => 'mod',
        'styles' => array('file' => 'admin-module'))
    ) ?>
    <?= helper('behavior.jquery') ?>
    <?= helper('behavior.modal')?>

    <div class="logman_container">
        <div class="k-table-container">
            <div class="k-table">
                <table class="logman_table_layout logman_table_layout--list">
                    <tbody>
                    <? foreach ($activities as $activity) : ?>
                        <tr class="logman_table_layout__item">
                            <td width="1%" class="k-table-data--icon logman_table_layout__icon">
                                <span class="<?=$activity->image?>"></span>
                            </td>
                            <td class="k-table-data--multiline logman_table_layout__message">
                                <?= helper('activity.activity', array('entity' => $activity, 'scripts' => true))?>
                            </td>
                            <td width="1%" class="k-table-data--right k-table-data--nowrap logman_table_layout__time">
                                <?= helper('activity.when', array('entity' => $activity, 'humanize' => true))?>
                            </td>
                        </tr>
                    <? endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <? if ($view_all): ?>
            <div class="logman__viewall d-grid p-3">
                <a href="<?= JRoute::_('index.php?option=com_logman&view=activities'); ?>" class="btn btn-block btn-secondary"><?=translate('View all')?></a>
            </div>
        <? endif; ?>
    </div>
<? endif ?>

