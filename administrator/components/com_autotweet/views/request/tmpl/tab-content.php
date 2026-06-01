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
            <div class="tab-content" id="qContent">

                <div id="auditinfo" class="<?php echo AutotweetToolbar::tabPaneActive(); ?>">
                    <dl class="dl-horizontal">
                        <dt>
                            <?php
                            echo JText::_('COM_AUTOTWEET_CREATED_DATE');
                            ?>
                        </dt>
                        <dd>
                            <?php
                            echo $this->item->get('created');
                            ?>

                            <?php
                            $created = $this->item->get('created_by');

                            if ($created) {
                                echo \Joomla\CMS\Factory::getUser($created)->get('username');
                            } else {
                                echo '-';
                            }
                            ?>
                        </dd>

                        <dt>
                            <?php
                            echo JText::_('COM_AUTOTWEET_MODIFIED_DATE');
                            ?>
                        </dt>
                        <dd>
                            <?php
                            $modified = $this->item->get('modified');

                            if ((int) $modified) {
                                echo $modified;
                            }
                            ?>

                            <?php
                            $modified_by = $this->item->get('modified_by');

                            if ($modified_by) {
                                echo \Joomla\CMS\Factory::getUser($modified_by)->get('username');
                            } else {
                                echo '-';
                            }
                            ?>
                        </dd>

                        <dt>
                            <?php
                            echo JText::_('COM_AUTOTWEET_RESULT_MESSAGE');
                            ?>
                        </dt>
                        <dd>
                            <?php
                            echo $alert_message ? TextUtil::autoLink($alert_message) : '-';
                            ?>
                        </dd>
                    </dl>
                </div>

                <div id="override-conditions" class="tab-pane fade">
                    <div class="control-group">
                        <label for="title" class="control-label disabled" rel="tooltip" data-original-title="<?php
            echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_MESSAGE_DESC');
            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_MESSAGE'); ?> </label>
                        <div class="controls">
                            <textarea name="xtform[title]" id="title" rows="5" cols="40" maxlength="512" readonly="readonly" class="disabled"><?php
                                echo $this->item->xtform->get('title');
                                ?></textarea>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="fulltext" class="control-label disabled" rel="tooltip" data-original-title="<?php
            echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_FULL_TEXT_DESC');
            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_FULL_TEXT'); ?> </label>
                        <div class="controls">
                            <textarea name="xtform[fulltext]" id="fulltext" rows="5" cols="40" maxlength="512" readonly="readonly" class="disabled"><?php
                                echo $this->item->xtform->get('fulltext');
                                ?></textarea>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="hashtags" class="control-label disabled" rel="tooltip" data-original-title="<?php
            echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_HASHTAGS_DESC');
            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_HASHTAGS'); ?> </label>
                        <div class="controls">
                            <input type="text" name="xtform[hashtags]" id="hashtags" class="xt-editor__hashtags disabled" value="<?php echo $this->item->xtform->get('hashtags'); ?>" maxlength="64" readonly="readonly" />
                        </div>
                    </div>
                </div>

                <div id="filterconditions" class="tab-pane fade">
                    <?php

                        if (!$isRequest) {
                            echo EHtmlSelect::yesNoControl(
                                $this->item->xtform->get('featured', 0),
                                'xtform[featured]',
                                'COM_AUTOTWEET_VIEW_MANUALMSG_FEATURED',
                                'COM_AUTOTWEET_VIEW_MANUALMSG_FEATURED_DESC'
                            );
                        }

                    ?>
                    <div class="control-group">
                        <label for="catid" class="control-label" rel="tooltip" data-original-title="<?php
            echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_CATEGORY_DESC');
            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_CATEGORY'); ?> </label>
                        <div class="controls">
                            <?php echo SelectControlHelper::category('xtform[catid]', 'com_content', $this->item->xtform->get('catid'), null, null, 1, 1, !$isManualMsg); ?>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="author" class="control-label" rel="tooltip" data-original-title="<?php
            echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_AUTHOR_DESC');
            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_AUTHOR'); ?> <span class="star">&#160;*</span> </label>
                        <div class="controls">

<?php
                        echo EHtmlSelect::userSelect($author, 'xtform[author]', 'author');
?>

                        </div>
                    </div>

                    <div class="control-group">
                        <label for="language" class="control-label" rel="tooltip" data-original-title="<?php
            echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_LANGUAGE_DESC');
            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_LANGUAGE'); ?> </label>
                        <div class="controls">
                            <?php echo SelectControlHelper::languages($this->item->xtform->get('language'), 'xtform[language]'); ?>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="language" class="control-label" rel="tooltip" data-original-title="<?php
            echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_ACCESS_DESC');
            ?>"><?php echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_ACCESS'); ?> </label>
                        <div class="controls">
                            <?php
                            echo JHTML::_('access.level', 'xtform[access]', $this->item->xtform->get('access', 1));
                            ?>
                        </div>
                    </div>

                </div>
            </div>

