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

JHtml::_('behavior.formvalidator');

$isFrontendEnabled = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')->isFrontendEnabled($this->item->channeltype_id);
$showAdvancedParameters = EParameter::getComponentParam(CAUTOTWEETNG, 'show_advanced_parameters');

?>

<div class="extly">
    <div class="xt-body">

        <?php echo Extly::showInvalidFormAlert(); ?>

        <form name="adminForm" id="adminForm" action="index.php" method="post"
            class="form form-horizontal form-validate cronjob-expression-form">
            <input type="hidden" name="option" value="com_autotweet" />
            <input type="hidden" name="view" value="channels" />
            <input type="hidden" name="task" value="" />
            <?php

                echo EHtml::renderRoutingTags();

            ?>

            <div class="xt-grid">

                <div class="xt-col-span-6">

                    <fieldset class="basic">

                        <legend>
                            <?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_SELECTCHANNEL_TITLE'); ?>
                        </legend>

                        <div class="control-group">
                            <label for="channeltype_id" class="control-label required" rel="tooltip" data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_SELECTCHANNEL_DESC'); ?>"> <?php echo JText::_('COM_AUTOTWEET_VIEW_TYPE_TITLE');
                            ?> <span class="star">&#160;*</span>
                            </label>
                            <div class="controls">
                                <?php echo SelectControlHelper::channeltypes($this->item->channeltype_id, 'channeltype_id', ['class' => 'required']); ?>
                            </div>
                        </div>

                    </fieldset>

                    <fieldset class="details">

                        <legend>
                            <?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_CHANNELDATA_TITLE'); ?>
                        </legend>
<?php
                        echo EHtml::requiredTextControl($this->item->get('name'), 'name', 'COM_AUTOTWEET_VIEW_CHANNEL_NAME_TITLE', 'COM_AUTOTWEET_VIEW_CHANNEL_NAME_DESC', null, 64);

                        echo EHtml::textareaControl($this->item->get('description'), 'description', 'COM_AUTOTWEET_VIEW_DESCRIPTION_TITLE', 'COM_AUTOTWEET_VIEW_DESCRIPTION_DESC');

                        echo EHtmlSelect::publishedControl($this->item->get('published', 1), 'published');

                        ?>
                        <div class="show-advanced-parameters <?php echo $showAdvancedParameters ? '' : 'hide'; ?>">
                        <?php
                            echo EHtmlSelect::yesNoControl($this->item->get('autopublish'), 'autopublish', 'COM_AUTOTWEET_VIEW_AUTOPUBLISH_TITLE', 'COM_AUTOTWEET_VIEW_AUTOPUBLISH_DESC');

                            echo EHtmlSelect::yesNoControl($this->item->get('xtform')->get('hashtags', true), 'xtform[hashtags]', 'COM_AUTOTWEET_VIEW_HASHTAGS_TITLE', 'COM_AUTOTWEET_VIEW_HASHTAGS_DESC');
                        ?>

                            <div class="control-group">
                                <label for="media_mode" class="control-label" rel="tooltip" data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_MEDIAMODE_DESC'); ?>"><?php
                                echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_MEDIAMODE_TITLE'); ?> </label>
                                <div class="controls">
                                    <?php echo SelectControlHelper::mediamodes($this->item->media_mode, 'media_mode', null); ?>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label" for="show_url" id="show_url-lbl" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_SHOWURL_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_SHOWURL_TITLE'); ?></label>
                                <div class="inline controls">
                                    <?php echo SelectControlHelper::showurl($this->item->xtform->get('show_url', PostShareManager::STATICTEXT_END), 'xtform[show_url]'); ?>
                                </div>
                            </div>
                        </div>

                        <?php

                        echo EHtml::idControl($this->item->get('id'), 'id', 'channel_id');

                        ?>

                    </fieldset>

                </div>

                <div class="xt-col-span-6">
                    <div class="xt-card" id="channelTabs">
                        <div class="xt-card-title">
                                <i class="xticon fas fa-bullhorn"></i>
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_ACCOUNTDATA_TITLE'); ?>
                        </div>
                        <div class="xt-card-content" id="channelTabsContent">
                            <div id="channel_data">
                                <fieldset class="channel_data">
                                    <p class="text-center">
                                        <span class="loaderspinner">&nbsp;</span>
                                        <legend>
                                            <?php echo JText::_('...requesting channel data...'); ?>
                                        </legend>
                                    </p>
                                </fieldset>
                            </div>
                        </div>
                    </div>

                    <?php

                    $alert_message = $this->get('alert_message');

                    if ($this->item->id) {
                        require_once 'audit.php';
                    }

                    ?>
                </div>

            </div>
        </form>
    </div>
</div>
<script type="text/javascript">

jQuery(document).ready(function() {

    document.formvalidator.setHandler('token',
            function (value) {
                regex=/^[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]$/;
                return regex.test(value);
            }
    );

    document.formvalidator.setHandler('facebookapp',
            function (value) {
                regex=/^http(s)?\:\/\/apps\.facebook\.com\/[a-zA-Z0-9-_]+$/;
                return regex.test(value);
            }
    );

});

</script>
