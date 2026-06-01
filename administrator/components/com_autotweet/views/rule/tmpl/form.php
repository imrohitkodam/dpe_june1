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

?>

<div class="extly">
    <div class="xt-body">

        <form name="adminForm" id="adminForm" action="index.php" method="post" class="form form-horizontal form-validate">
            <input type="hidden" name="option" value="com_autotweet" />
            <input type="hidden" name="view" value="rules" />
            <input type="hidden" name="task" value="" />
            <?php

                echo EHtml::renderRoutingTags();

            ?>
            <div class="xt-grid">

                <div class="xt-col-span-6">

                    <fieldset class="basic">

                        <legend><?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_OPTIONS'); ?></legend>

                        <div class="control-group">
                            <label for="name" class="control-label"><?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_NAME_TITLE'); ?> <span class="star">&#160;*</span> </label>
                            <div class="controls">
                                <input type="text" name="name" id="name" value="<?php echo htmlentities($this->item->name); ?>" class="required" maxlength="64" />
                            </div>
                        </div>

                        <div class="control-group">
                            <label for="plugin" class="control-label required" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_VIEW_RULE_PLUGIN_DESC'); ?>"> <?php
                            echo JText::_('COM_AUTOTWEET_VIEW_PLUGIN_TITLE');
                            ?> <span class="star">&#160;*</span>
                            </label>
                            <div class="controls">
                                <?php echo SelectControlHelper::plugins($this->item->plugin, 'plugin', ['class' => 'required']); ?>
                            </div>
                        </div>

                        <div class="control-group">
                            <label for="channel_id" class="control-label required" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_VIEW_RULE_CHANNEL_DESC');
                            ?>"> <?php
                            echo JText::_('COM_AUTOTWEET_VIEW_RULE_CHANNEL_TITLE');
                            ?>
                            </label>
                            <div class="controls">
                                <?php echo SelectControlHelper::channels($this->item->channel_id, 'channel_id'); ?>
                            </div>
                        </div>

                        <div class="control-group">
                            <label for="ruletype_id" class="required control-label" rel="tooltip" data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_TYPE_DESC'); ?>"><?php
                            echo JText::_('COM_AUTOTWEET_VIEW_TYPE_TITLE'); ?> <span class="star">&#160;*</span></label>
                            <div class="controls">
                                <?php echo SelectControlHelper::ruletypes($this->item->ruletype_id, 'ruletype_id', ['class' => 'required']); ?>
                            </div>
                        </div>

                        <div class="control-group">
                            <label for="cond" class="control-label" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_VIEW_RULE_CONDITION_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_CONDITION_TITLE'); ?> </label>
                            <div class="controls">
                                <input type="text" name="cond" id="cond" value="<?php echo htmlentities($this->item->cond); ?>" maxlength="64" />
                            </div>
                        </div>

<?php

                        echo EHtmlSelect::yesNoControl(
                            $this->item->xtform->get('only_featured', 0),
                            'xtform[only_featured]',
                            'COM_AUTOTWEET_VIEW_RULE_ONLYFEATURED_TITLE',
                            'COM_AUTOTWEET_VIEW_RULE_ONLYFEATURED_DESC'
                        );

                        echo EHtmlSelect::publishedControl($this->item->get('published', 1), 'published');

?>

                        <div class="control-group">
                            <label for="rule_id" class="control-label" rel="tooltip" data-original-title="<?php echo JText::_('JGLOBAL_FIELD_ID_DESC'); ?>"><?php
                            echo JText::_('JGLOBAL_FIELD_ID_LABEL'); ?> </label>
                            <div class="controls">
                                <input type="text" name="id" id="rule_id" value="<?php echo $this->item->id; ?>" class="disabled" readonly="readonly">
                            </div>
                        </div>

                    </fieldset>

                    <p class="xt-alert xt-alert-info">
                        <?php
                            $denyall_rulemode = EParameter::getComponentParam(CAUTOTWEETNG, 'denyall_rulemode', 0);
                            echo JText::_('COM_AUTOTWEET_COMPARAM_DENYALL_LABEL').': <span class="xt-label xt-label-success">'.
                                ($denyall_rulemode ? JText::_('JYES') : JText::_('JNO')).'</span>';
                        ?>
                        <span class="xt-float-right">
                            <a href="index.php?option=com_config&view=component&component=com_autotweet#advanced">
                                <i class="xticon fas fa-cog"></i>
                            </a>
                        </span>
                    </p>

                </div>

                <div class="xt-col-span-6">
<?php

if (EXTLY_J3) {
    ?>
                    <ul class="xt-nav-tabs-joomla3 nav nav-tabs" id="ruletypeTabs">
                        <li class="active"><a data-toggle="tab" href="#overrideconditions">
                            <i class="xticon fas fa-wrench"></i>
                            <?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_OVERRIDECONDITIONS_TITLE'); ?></a>
                        </li>
                        <li><a data-toggle="tab" href="#advancedrmc">
                            <i class="xticon fas fa-pencil-alt"></i>
                            <?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_ADVANCED_RMC_TITLE'); ?></a>
                        </li>
                        <li><a data-toggle="tab" href="#advancedaddtext">
                            <i class="xticon far fa-file-alt"></i>
                            <?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_ADVANCED_ADDTEXT_TITLE'); ?></a>
                        </li>
                        <li><a data-toggle="tab" href="#advancedreplace">
                            <i class="xticon fas fa-ellipsis-h"></i>
                            <?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_ADVANCED_REPLACE_TITLE'); ?></a>
                        </li>
                    </ul>
    <?php
}

if (EXTLY_J4) {
    ?>
                    <ul class="xt-nav xt-nav-tabs xt-nav-tabs-joomla4 nav nav-tabs" id="ruletypeTabs">
                        <li class="nav-item" role="presentation"><a class="nav-link active" id="overrideconditions-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#overrideconditions" type="button" role="tab"
                            aria-controls="overrideconditions"
                            aria-selected="true">
                            <i class="xticon fas fa-wrench"></i>
                            <?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_OVERRIDECONDITIONS_TITLE'); ?></a>
                        </li>
                        <li class="nav-item" role="presentation"><a class="nav-link" id="advancedrmc-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#advancedrmc" type="button" role="tab" aria-controls="advancedrmc">
                            <i class="xticon fas fa-pencil-alt"></i>
                            <?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_ADVANCED_RMC_TITLE'); ?></a>
                        </li>
                        <li class="nav-item" role="presentation"><a class="nav-link" id="advancedaddtext-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#advancedaddtext" type="button" role="tab" aria-controls="advancedaddtext">
                            <i class="xticon far fa-file-alt"></i>
                            <?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_ADVANCED_ADDTEXT_TITLE'); ?></a>
                        </li>
                        <li class="nav-item" role="presentation"><a class="nav-link" id="advancedreplace-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#advancedreplace" type="button" role="tab" aria-controls="advancedreplace">
                            <i class="xticon fas fa-ellipsis-h"></i>
                            <?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_ADVANCED_REPLACE_TITLE'); ?></a>
                        </li>
                    </ul>
    <?php
}

?>
                    <div class="tab-content" id="fbchannel-tabsContent">
                        <div id="overrideconditions" class="<?php echo AutotweetToolbar::tabPaneActive(); ?>">
                            <div class="control-group">
                                <label class="control-label" for="autopublish" id="autopublish-lbl" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_VIEW_RULE_AUTOPUBLISH_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_AUTOPUBLISH_TITLE'); ?></label>
                                <div class="inline controls">
                                    <?php echo SelectControlHelper::autopublish($this->item->autopublish, 'autopublish'); ?>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label" for="show_url" id="show_url-lbl" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_VIEW_RULE_SHOWURL_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_SHOWURL_TITLE'); ?></label>
                                <div class="inline controls">
                                    <?php echo SelectControlHelper::showurl($this->item->show_url, 'show_url'); ?>
                                </div>
                            </div>
                        </div>
                        <div id="advancedrmc" class="tab-pane fade">
                            <div class="control-group">
                                <label class=" required control-label" for="rmc_textpattern" id="access_token-lbl" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_VIEW_RULE_ADVANCED_RMCTEXTPATTERN_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_ADVANCED_RMCTEXTPATTERN_TITLE'); ?>
                                </label>
                                <div class="controls">
                                    <textarea class="inputbox" rows="3" cols="40" id="rmc_textpattern" name="rmc_textpattern"><?php echo $this->item->rmc_textpattern; ?></textarea>
                                </div>
                            </div>

                            <p>
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_ADVANCED_RMCTEXTPATTERN_DESC'); ?>
                            </p>

                            <h4>Examples</h4>

                            <p>
                                [message] / #Joomla - [fulltext,60]
                            </p>
                        </div>
                        <div id="advancedaddtext" class="tab-pane fade">
                            <div class="control-group">
                                <label class="control-label" for="show_static_text_id" id="show_static_text-lbl" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_VIEW_SHOWSTATICTEXT_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_SHOWSTATICTEXT_TITLE'); ?></label>
                                <div class="controls">
                                    <?php echo SelectControlHelper::showstatictext($this->item->show_static_text, 'show_static_text'); ?>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label" for="statix_text" id="statix_text-lbl" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_VIEW_STATICTEXT_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_STATICTEXT_TITLE'); ?></label>
                                <div class="controls">
                                    <input type="text" maxlength="255" value="<?php echo htmlentities($this->item->statix_text); ?>" id="statix_text" name="statix_text">
                                </div>
                            </div>
                        </div>
                        <div id="advancedreplace" class="tab-pane fade">
                            <div class="control-group">
                                <label class="control-label" for="reg_ex" id="reg_ex-lbl" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_VIEW_RULE_REPLACEREGEX_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_REPLACEREGEX_TITLE'); ?></label>
                                <div class="controls">
                                    <input type="text" maxlength="4096" size="30" value="<?php echo htmlentities($this->item->reg_ex); ?>" id="reg_ex" name="reg_ex">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label" for="reg_replace" id="reg_replace-lbl" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_VIEW_RULE_REPLACETEXT_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_RULE_REPLACETEXT_TITLE'); ?></label>
                                <div class="controls">
                                    <input type="text" maxlength="4096" size="30" value="<?php echo htmlentities($this->item->reg_replace); ?>" id="reg_replace" name="reg_replace">
                                </div>
                            </div>

                            <div class="xt-alert xt-alert-info">
                                <!-- Removed button close data-dismiss="alert" -->
                                <h4>Example 1</h4>

                                <dl>
                                    <dt>Regular expression</dt>
                                    <dd>/ autotweetng/i</dd>
                                    <dt>Replacement text</dt>
                                    <dd> #PerfectPublisher</dd>
                                </dl>
                            </div>
                        </div>

                    </div>

        <hr/>
        <div class="xt-alert xt-alert-info">
            <!-- Removed button close data-dismiss="alert" -->
            <?php
            echo JText::_('COM_AUTOTWEET_VIEW_RULE_DATA_DESC');
            ?>
            <br/>
            <i class="xticon far fa-thumbs-up"></i> <a href="https://www.extly.com/docs/perfect_publisher/user_guide/activities/rules/" target="_blank">
            A practical case: Two Channels and Two Categories</a>
            <br/>
            <i class="xticon far fa-thumbs-up"></i> <a href="https://www.extly.com/docs/perfect_publisher/user_guide/activities/rules/" target="_blank">
            ACCEPT ALL vs DENY ALL mode: Posting to selected channels only</a>
        </div>

                </div>

            </div>
        </form>
    </div>
</div>
