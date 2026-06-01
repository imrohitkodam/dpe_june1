<?
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die; ?>


<div class="k-dynamic-content-holder">
    <div id="plugins-list" class="k-ui-namespace k-small-inline-modal-holder mfp-hide">
        <div class="k-inline-modal">
            <div class="k-inline-modal__title"><?= translate('Manage integrations'); ?></div>
            <div class="k-table-container">
                <div class="k-table">
                    <table>
                        <thead>
                        <tr>
                            <th><?= translate('Integration'); ?></th>
                            <th><?= translate('State'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <? foreach ($plugins as $plugin): ?>
                            <tr data-identifier="<?= $plugin->identifier ?>">
                                <td>
                                    <?= translate($plugin->identifier) ?>
                                </td>
                                <td>

                                    <div class="k-optionlist k-optionlist--boolean">
                                        <div class="k-optionlist__content">
                                            <input type="radio" id="<?= $plugin->identifier ?>-enable" name="<?= $plugin->identifier ?>" value="1" <? if ($plugin->isEnabled()): ?>checked="checked"<? endif ?> />
                                            <label for="<?= $plugin->identifier ?>-enable"><?= translate('On') ?></label>
                                            <input type="radio" id="<?= $plugin->identifier ?>-disable" name="<?= $plugin->identifier ?>" value="0" <? if (!$plugin->isEnabled()): ?>checked="checked"<? endif ?> />
                                            <label for="<?= $plugin->identifier ?>-disable"><?= translate('Off') ?></label>
                                            <div class="k-optionlist__focus"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <? endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
