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
                <div class="xt-grid">
                    <div class="xt-col-span-12">
                        <hr />

                            <div class="well">
                                <div class="alert <?php echo $this->get('alert_style'); ?>">
                                <?php echo JText::_('COM_AUTOTWEET_AUDIT_INFORMATION'); ?>
                            </div>

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
                        echo $alert_message;
                    ?>
                                </dd>
                            </dl>

                        </div>

                    </div>
                </div>
