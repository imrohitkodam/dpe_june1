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
<form id="adminForm" name="adminForm" action="index.php" method="post"
    class="form form-horizontal form-validate"
    ng-controller="EditorController as editorCtrl">

    <input type="hidden" name="option" value="com_autotweet"
        ng-init="editorCtrl.option = 'com_autotweet'"
         ng-value="editorCtrl.option"/>

    <input type="hidden" name="view" value="composer"
        ng-init="editorCtrl.view = 'composer'"
         ng-value="editorCtrl.view"/>

    <input type="hidden" name="task" value="" ng-model="editorCtrl.task"
        ng-init="editorCtrl.task = ''"
         ng-value="editorCtrl.task"/>

    <input type="hidden" name="returnurl" value="<?php

        $returlurl = base64_encode(JRoute::_('index.php?option=com_autotweet&view=cpanels'));
        echo $returlurl;

        ?>"
        ng-init="editorCtrl.returnurl = '<?php echo $returlurl; ?>'"
         ng-value="editorCtrl.returnurl"/>
<?php

echo EHtml::renderRoutingTags();

// Publish_up

echo '<input type="hidden" name="plugin"
         ng-init="editorCtrl.plugin = \'autotweetpost\'"
         ng-value="editorCtrl.plugin" />';

echo '<input type="hidden" name="ref_id"
         ng-init="editorCtrl.ref_id = \''.AutotweetBaseHelper::getHash().'\'"
         ng-value="editorCtrl.ref_id"/>';

echo '<input type="hidden" name="id"
         ng-init="editorCtrl.request_id = 0"
         ng-value="editorCtrl.request_id"/>';

echo '<input type="hidden" name="published" value="0"
         ng-init="editorCtrl.published = 0"
         ng-value="editorCtrl.published"/>';

$list_limit = \Joomla\CMS\Factory::getConfig()->get('list_limit');
echo '<input type="hidden" id="list_limit" value="'.$list_limit.'" />';

?>
<fieldset ng-controller="MessageController as messageCtrl">
        <div class="xt-grid">
            <div class="xt-col-span-12">

                <p class="text-center" ng-if="editorCtrl.waiting"><span class="loaderspinner72 loading72">
                    <?php echo JText::_('COM_AUTOTWEET_LOADING'); ?>
                </span></p>

                <div class="control-group" ng-if="editorCtrl.showDialog">
                    <div class="xt-alert xt-alert-success" ng-if="editorCtrl.messageResult">
                        <button type="button" class="close"
                            ng-click="editorCtrl.showDialog = false">&times;</button>
                        <div ng-bind-html="editorCtrl.messageText"></div>
                    </div>
                    <div class="xt-alert xt-alert-error" ng-if="!editorCtrl.messageResult">
                        <button type="button" class="close"
                            ng-click="editorCtrl.showDialog = false">&times;</button>
                        <div ng-bind-html="editorCtrl.messageText"></div>
                    </div>
                </div>
<?php

                echo JLayoutHelper::render('free.field.description', null, JPATH_AUTOTWEET_LAYOUTS);

?>
                <div class="control-group">

                    <div class="xt-editor__post-attrs-group post-attrs-group">
                        <input type="hidden" value="2" id="xtformid5095" name="postAttrs"
                            class="ng-pristine ng-untouched ng-valid">

                        <div data-toggle="buttons-radio" class="xt-group">
                            <a class="xt-button btn btn-small" data-value="link" data-ref="xtformid5095"
                                data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_LINK_DESC'); ?>" rel="tooltip">
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_LINK_ICON'); ?></a>
                            <a class="xt-button btn btn-small" data-value="image" data-ref="xtformid5095"
                                data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_IMGCHOOSER_DESC'); ?>" rel="tooltip">
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_IMGCHOOSER_ICON'); ?></a>
<?php
        if (PERFECT_PUB_PRO) {
            ?>
                            <a class="xt-button btn btn-small" data-value="basic" data-ref="xtformid5095"
                                data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_BASIC_DESC'); ?>" rel="tooltip">
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_BASIC_ICON'); ?></a>
                            <a class="xt-button btn btn-small" data-value="channelchooser" data-ref="xtformid5095"
                                data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_CHANNELCHOOSER_DESC'); ?>" rel="tooltip">
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_CHANNELCHOOSER_ICON'); ?></a>
                            <a class="xt-button btn btn-small" data-value="scheduler" data-ref="xtformid5095"
                                data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_SCHEDULER_DESC'); ?>" rel="tooltip">
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_SCHEDULER_ICON'); ?></a>
                            <a class="xt-button btn btn-small" data-value="repeat" data-ref="xtformid5095"
                                data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_DESC'); ?>" rel="tooltip">
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_ICON'); ?></a>
<?php
        } else {
            ?>
                            <a class="xt-button btn btn-small disabled" data-value="basic" data-ref="xtformid5095"
                                data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_BASIC_DESC'); ?>" rel="tooltip">
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_BASIC_ICON'); ?></a>
                            <a class="xt-button btn btn-small disabled" data-value="channelchooser" data-ref="xtformid5095"
                                data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_CHANNELCHOOSER_DESC'); ?>" rel="tooltip">
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_CHANNELCHOOSER_ICON'); ?></a>
                            <a class="xt-button btn btn-small disabled" data-value="scheduler" data-ref="xtformid5095"
                                data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_SCHEDULER_DESC'); ?>" rel="tooltip">
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_SCHEDULER_ICON'); ?></a>
                            <a class="xt-button btn btn-small disabled" data-value="repeat" data-ref="xtformid5095"
                                data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_DESC'); ?>" rel="tooltip">
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_ICON'); ?></a>
<?php
        }
?>
                        </div>
<?php
        if (!PERFECT_PUB_PRO) {
            echo '<p></p><p class="xt-text-right">'.JText::_('COM_AUTOTWEET_UPDATE_TO_PERFECT_PUBLISHER_PRO_LABEL').'</p>';
        }
?>

                    </div>
                </div>
            </div>
        </div>

<?php
        // Link subform
        //
        echo '<div class="xt-subform xt-subform-link">';
        echo JLayoutHelper::render('free.field.link', null, JPATH_AUTOTWEET_LAYOUTS);
        echo '</div>';

        // Image subform
        //
        echo '<div class="xt-subform xt-subform-image">';
        $attrs = ['controller' => 'editorCtrl'];
        echo JLayoutHelper::render('free.field.image', $attrs, JPATH_AUTOTWEET_LAYOUTS);
        echo '</div>';

        if (PERFECT_PUB_PRO) {
            // Basic subform
            //
            echo '<div class="xt-subform xt-subform-basic">';
            echo JLayoutHelper::render('pro.field.postthis', null, JPATH_AUTOTWEET_LAYOUTS);
            echo JLayoutHelper::render('pro.field.evergreen', null, JPATH_AUTOTWEET_LAYOUTS);

            // Hashtags
            echo JLayoutHelper::render('pro.field.hashtags', null, JPATH_AUTOTWEET_LAYOUTS);

            // Fulltext
            $attrs = ['controller' => 'editorCtrl'];
            echo JLayoutHelper::render('pro.field.fulltext', $attrs, JPATH_AUTOTWEET_LAYOUTS);
            echo '</div>';

            // Channels subform
            //
            echo '<div class="xt-subform xt-subform-channelchooser">';
            echo JLayoutHelper::render('pro.field.channels', $attrs, JPATH_AUTOTWEET_LAYOUTS);
            echo '</div>';

            // Scheduler subform
            //
            echo '<div class="xt-subform xt-subform-scheduler">';
            echo JLayoutHelper::render('pro.field.scheduler', null, JPATH_AUTOTWEET_LAYOUTS);
            echo '</div>';

            // Repeat subform
            //
            echo '<div class="xt-subform xt-subform-repeat">';
            $attrs = [
                'controller' => 'editorCtrl',
                'classes' => [
                    'class' => 'disabled-12',
                    'field-class' => 'disabled-12',
                ],
            ];
            echo JLayoutHelper::render('pro.field.repeat', $attrs, JPATH_AUTOTWEET_LAYOUTS);
            echo '</div>';
        }
?>

        <input type="hidden" name="author" value="<?php echo \Joomla\CMS\Factory::getUser()->username; ?>"/>

    </fieldset>
</form>
