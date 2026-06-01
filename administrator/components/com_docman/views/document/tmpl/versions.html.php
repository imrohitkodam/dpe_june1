<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die;?>


<?
$versions = $document->getFileVersions();
$active = $document->getActiveVersion();
?>

<? if (count($versions)): ?>
    <div class="k-toolbar">
        <a id="toolbar-revert" class="<?= $document->isLocked() ? 'k-is-disabled k-is-unauthorized' : null ?> btn-success btn toolbar k-button k-button--primary k-button-revert" data-action="revert" href="#">
            <span class="k-icon-action-undo" aria-hidden="true"></span>
            <span class="k-button__text"><?= translate('Set as active') ?></span>
        </a>
    </div>

    <div class="k-table-container">
        <div class="k-table">
            <table class="k-js-responsive-table">
                <thead>
                <tr>
                    <th width="1%" class="k-table-data--form">&nbsp;</th>
                    <th width="1%" class="k-table-data--toggle" data-toggle="true">&nbsp;</th>
                    <th width="20%"><?= translate('Date') ?></th>
                    <th><?= translate('File name') ?></th>
                    <th width="10%"><?= translate('User') ?></th>
                </tr>
                </thead>
                <tbody>
                <? foreach ($versions as $version_id => $version) : ?>
                    <? $date = helper('date.format', array('date' => $version['date'], 'format' => 'd F Y H:i'))?>
                    <tr>
                        <td class="k-table-data--form">
                            <input type="radio" name="version_id" value="<?= $version_id ?>" <?= $version_id == $active['id'] ? 'checked' : null ?> />
                        </td>
                        <td class="k-table-data--toggle"></td>
                        <td><?= $date; ?></td>
                        <td class="k-table-data--ellipsis"><?= $version['path'] ?></td>
                        <td><?= $user = object('user.provider')->load($version['user'])->getName(); ?></td>
                    </tr>
                <? endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
<? else: ?>
    <div class="k-alert k-alert--info">
        <p>
            <?= translate('There are no file versions at this moment') ?>
        </p>
    </div>
<? endif ?>