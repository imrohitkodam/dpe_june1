<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

?>
    <div class="control-group">
        <label for="composer_link" class="control-label" rel="tooltip" data-original-title="<?php

        echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_LINK_DESC');

        ?>"><?php

        echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_LINK');

        ?></label>
        <div class="controls">
            <div class="xt-input-append">
                <input type="text" name="url"
                    placeholder="<?php echo JText::_('COM_AUTOTWEET_COMPOSER_TYPE_URL_LABEL'); ?>"
                    ng-model="editorCtrl.url_value">
                <span class="add-on">
                    <a ng-click="editorCtrl.menuitemlistHide()">
                        <i class="xticon far fa-caret-square-right "></i>
                    </a>
                </span>
            </div>
        </div>
    </div>

    <div id="menulist_group" class="control-group hide">
        <label></label>
        <div class="controls">
            <div class="xt-input-prepend">
                <span class="add-on">
                    <i class="xticon fas fa-list"></i>
                </span>
        <?php
                        echo EHtmlSelect::menuitemlist(
            null,
            'selectedMenuItem',
            [
                'ng-model' => 'editorCtrl.menuitemValue',
                'ng-change' => 'editorCtrl.loadUrl(editorCtrl.menuitemValue)',
                'class' => 'xt-col-span-12',
                'size' => 1,
            ]
        );
        ?>
            </div>
        </div>
    </div>
