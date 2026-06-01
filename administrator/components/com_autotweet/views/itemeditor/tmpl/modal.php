<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

$this->loadHelper('select');

?>
<div id="itemeditor-modal" ng-app="starter" class="extly ng-cloak">
    <div class="xt-body">
        <h4>
            <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_TITLE'); ?>
            <span class="loaderspinner">&nbsp;</span>

            <img src="<?php echo \Joomla\CMS\Uri\Uri::root(); ?>media/com_autotweet/images/perfectpub-logo.svg"
                class="xt-float-right" width="16" height="16">
        </h4>

        <form name="itemEditorForm" id="itemEditorForm" action="#" method="post" class="form form-horizontal form-validate"
            ng-controller="ItemEditorController as itemEditorCtrl">

            <div class="xt-alert xt-alert-block alert-error" ng-if="itemEditorCtrl.showDialog">
                <div>{{itemEditorCtrl.messageText}}</div>
            </div>

            <?php

            if (EXTLY_J3) {
                ?>
            <ul class="xt-nav-tabs-joomla3 nav nav-tabs" id="itemEditorTabs">
                <li class="active"><a data-toggle="tab" href="#msg-tab"
                        data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_MSG_DESC'); ?>" rel="tooltip">
                    <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_MSG_ICON');
                        ?> <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_MSG'); ?></a></li>
                <li><a data-toggle="tab" href="#basic-tab"
                        data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_BASIC_DESC'); ?>" rel="tooltip">
                    <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_BASIC_ICON');
                        ?> <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_BASIC'); ?></a></li>
                <li><a data-toggle="tab" href="#channelchooser-tab"
                        data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_CHANNELCHOOSER_DESC'); ?>" rel="tooltip">
                    <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_CHANNELCHOOSER_ICON');
                        ?> <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_CHANNELCHOOSER'); ?></a></li>
                <li><a data-toggle="tab" href="#scheduler-tab"
                        data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_SCHEDULER_DESC'); ?>" rel="tooltip">
                    <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_SCHEDULER_ICON');
                        ?> <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_SCHEDULER'); ?></a></li>
                <li><a data-toggle="tab" href="#repeat-tab"
                        data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_DESC'); ?>" rel="tooltip">
                    <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_ICON');
                        ?> <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT'); ?></a></li>
                <li><a data-toggle="tab" href="#imagechooser-tab"
                        data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_IMGCHOOSER_DESC'); ?>" rel="tooltip">
                    <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_IMGCHOOSER_ICON');
                        ?> <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_IMGCHOOSER'); ?></a></li>
            </ul>
                <?php
            }

            if (EXTLY_J4) {
                ?>
            <ul class="xt-nav xt-nav-tabs xt-nav-tabs-joomla4 nav nav-tabs" id="itemEditorTabs">
                <li class="nav-item" role="presentation"><a class="nav-link active" id="msg-tab-link"
                    data-bs-toggle="tab"
                    data-bs-target="#msg-tab" type="button" role="tab" aria-controls="msg-tab"
                    aria-selected="true">
                    <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_MSG_ICON');
                        ?> <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_MSG'); ?></a></li>
                <li class="nav-item" role="presentation"><a class="nav-link" id="basic-tab-link"
                    data-bs-toggle="tab"
                    data-bs-target="#basic-tab" type="button" role="tab" aria-controls="basic-tab">
                    <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_BASIC_ICON');
                        ?> <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_BASIC'); ?></a></li>
                <li class="nav-item" role="presentation"><a class="nav-link" id="channelchooser-tab-link"
                    data-bs-toggle="tab"
                    data-bs-target="#channelchooser-tab" type="button" role="tab"
                    aria-controls="channelchooser-tab">
                    <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_CHANNELCHOOSER_ICON');
                        ?> <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_CHANNELCHOOSER'); ?></a></li>
                <li class="nav-item" role="presentation"><a class="nav-link" id="scheduler-tab-link"
                    data-bs-toggle="tab"
                    data-bs-target="#scheduler-tab" type="button" role="tab" aria-controls="scheduler-tab">
                    <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_SCHEDULER_ICON');
                        ?> <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_SCHEDULER'); ?></a></li>
                <li class="nav-item" role="presentation"><a class="nav-link" id="repeat-tab-link"
                    data-bs-toggle="tab"
                    data-bs-target="#repeat-tab" type="button" role="tab" aria-controls="repeat-tab">
                    <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_ICON');
                        ?> <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT'); ?></a></li>
                <li class="nav-item" role="presentation"><a class="nav-link" id="imagechooser-tab-link"
                    data-bs-toggle="tab"
                    data-bs-target="#imagechooser-tab" type="button" role="tab"
                    aria-controls="imagechooser-tab">
                    <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_IMGCHOOSER_ICON');
                        ?> <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_IMGCHOOSER'); ?></a></li>
            </ul>
                <?php
            }

            ?>
            <div class="tab-content" id="itemEditorTabsContent">
<?php
                $class = AutotweetToolbar::tabPaneActive();
                echo '<div id="msg-tab" class="'.$class.'" ng-controller="MessageController as messageCtrl">';
                echo JLayoutHelper::render('free.field.description', null, JPATH_AUTOTWEET_LAYOUTS);
                echo JLayoutHelper::render('pro.field.hashtags', null, JPATH_AUTOTWEET_LAYOUTS);

                $attrs = ['controller' => 'itemEditorCtrl'];
                echo JLayoutHelper::render('pro.field.fulltext', $attrs, JPATH_AUTOTWEET_LAYOUTS);

                echo '</div><div id="basic-tab" class="xt-tab-pane">';
                echo JLayoutHelper::render('pro.field.postthis', null, JPATH_AUTOTWEET_LAYOUTS);
                echo JLayoutHelper::render('pro.field.evergreen', null, JPATH_AUTOTWEET_LAYOUTS);

                echo '</div><div id="channelchooser-tab" class="xt-tab-pane">';
                echo JLayoutHelper::render('pro.field.channels', $attrs, JPATH_AUTOTWEET_LAYOUTS);

                echo '</div><div id="scheduler-tab" class="xt-tab-pane">';
                echo JLayoutHelper::render('pro.field.scheduler', null, JPATH_AUTOTWEET_LAYOUTS);

                echo '</div><div id="repeat-tab" class="tab-pane fade shortcuts-tab">';

                $attrs = [
                    'controller' => 'itemEditorCtrl',
                    'classes' => [
                        'class' => 'xt-col-span-8',
                        'field-class' => 'xt-col-span-6',
                    ],
                ];
                echo JLayoutHelper::render('pro.field.repeat', $attrs, JPATH_AUTOTWEET_LAYOUTS);

                echo '</div><div id="imagechooser-tab" class="xt-tab-pane">';
                echo JLayoutHelper::render('pro.field.imagepicker', null, JPATH_AUTOTWEET_LAYOUTS);

                $attrs = ['controller' => 'itemEditorCtrl'];
                echo JLayoutHelper::render('free.field.image', $attrs, JPATH_AUTOTWEET_LAYOUTS);

                echo '</div>';

?>
            </div>

            <br>
            <p class="text-center">
                <a class="btn btn-info itemEditorSubmit" ng-click="itemEditorCtrl.onSubmit()" data-bs-dismiss="modal"><?php echo JText::_('JSUBMIT'); ?></a>
            </p>
        </form>
    </div>
</div>
