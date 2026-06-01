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
$this->loadHelper('grid');

if (version_compare(JVERSION, '3.999.999', 'le')) {
    JHtml::_('behavior.calendar');
}

$manage = XTF0FPlatform::getInstance()->authorise('core.manage', $this->input->getCmd('option', 'com_foobar'));

?>
<div class="extly">
    <div class="xt-body">

        <form name="adminForm" id="adminForm" action="index.php" method="post" class="form-horizontal">

            <div class="xt-grid">
                <div class="xt-col-span-12">

                    <input type="hidden" name="option" id="option" value="com_autotweet" />
                    <input type="hidden" name="view" id="view" value="posts" />
                    <input type="hidden" name="task" id="task" value="browse" />
                    <input type="hidden" name="boxchecked" id="boxchecked" value="0" />
                    <input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
                    <input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order; ?>" />
                    <input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir; ?>" />
                    <?php
                        echo EHtml::renderRoutingTags();
                    ?>
                    <table class="table adminlist table-striped" id="itemsList">
                        <thead>
                            <tr>
                                <?php

                                if (!$this->isModule) {
                                    ?>
                                <th width="20"><input type="checkbox" name="toggle" value=""
                                    onclick="Joomla.checkAll(this);" />
                                </th>
                                <?php
                                }
                                ?>

                                <th><?php echo JHTML::_('grid.sort', 'COM_AUTOTWEET_VIEW_MSGLOG_POSTDATE_TITLE', 'postdate', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>

                                <th><?php echo JHTML::_('grid.sort', 'COM_AUTOTWEET_POSTS_FIELD_MESSAGE', 'name', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>

                                <th width="160"><?php echo JHTML::_('grid.sort', 'LBL_POSTS_CHANNEL', 'channel_id', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>

                                <th width="80"><?php echo JHTML::_('grid.sort', 'COM_AUTOTWEET_VIEW_POSTS_PUBSTATES_SELECT', 'pubstate', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>

                                <?php

                                if (!$this->isModule) {
                                    ?>

                                <th width="80"><?php echo JHTML::_('grid.sort', 'COM_AUTOTWEET_VIEW_SOURCE_TITLE', 'plugin', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>

                                <th width="80"><?php echo JHTML::_('grid.sort', 'JGLOBAL_FIELD_ID_LABEL', 'id', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>
                                <?php
                                }

                                ?>
                            </tr>
                            <tr style="<?php

                            if ($this->isModule) {
                                echo 'display:none;';
                            }

                            ?>">
                                <td></td>

                                <td class="form-inline"><?php echo JHTML::_('calendar', $this->getModel()->getState('postdate'), 'postdate', 'postdate', JText::_('COM_AUTOTWEET_DATE_VIEW_FORMAT'), ['class' => 'input-small']); ?>
                                </td>

                                <td class="form-inline nowrap">
                                    <div class="xt-input-append">
                                        <input type="text" name="search" id="search" value="<?php echo $this->escape($this->getModel()->getState('search')); ?>" class="input-medium" onchange="document.adminForm.submit();"
                                            placeholder="<?php echo JText::_('COM_AUTOTWEET_POSTS_FIELD_MESSAGE'); ?>" />
                                        <button class="btn" onclick="this.form.submit();">
                                            <?php echo JText::_('COM_AUTOTWEET_FILTER_SUBMIT'); ?>
                                        </button>
                                    </div>

                                    <a class="xtd-btn-reset"><small><?php echo JText::_('COM_AUTOTWEET_RESET'); ?></small></a>
                                </td>
                                <td><?php echo SelectControlHelper::channels($this->getModel()->getState('channel'), 'channel', ['onchange' => 'this.form.submit();', 'class' => 'input-medium']); ?>
                                </td>
                                <td><?php echo SelectControlHelper::pubstates($this->getModel()->getState('pubstate'), 'pubstate', ['onchange' => 'this.form.submit();', 'class' => 'input-small'], null, true); ?>
                                </td>

                                <td><?php echo SelectControlHelper::plugins($this->getModel()->getState('plugin'), 'plugin', ['onchange' => 'this.form.submit();', 'class' => 'input-small']); ?>
                                </td>

                                <td></td>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <td colspan="20"><?php
                                EHtml::renderPagination($this);
                                ?>
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php
                            if ($count = count($this->items)) {
                                ?>
                            <?php
                            $i = 0;
                                $m = 1;

                                foreach ($this->items as $item) {
                                    $m = 1 - $m;
                                    $link = JRoute::_('index.php?option=com_autotweet&view=posts&task=edit&id='.(int) $item->id);
                                    $checkedout = (bool) $item->checked_out;

                                    $is_evergreen = false;
                                    $is_immediate = false;
                                    $evergreen_link = null;

                                    if (PERFECT_PUB_PRO) {
                                        try {
                                            $item->xtform = EForm::paramsToRegistry($item);

                                            if ($req_id_src = $item->xtform->get('req_id_src')) {
                                                $evergreen_item = AdvancedAttributesHelper::getEvergreen($req_id_src);

                                                if (isset($evergreen_item->id)) {
                                                    $is_evergreen = true;
                                                    $evergreen_link = AdvancedAttributesHelper::getEditLink($evergreen_item->client_id, $evergreen_item->option, $evergreen_item->ref_id, $evergreen_item->id);
                                                }
                                            } else {
                                                $is_evergreen = ($item->xtform->get('evergreen_generated'));
                                            }

                                            $is_immediate = ($item->xtform->get('is_immediate'));
                                        } catch (Exception $e) {
                                            $msg = 'Invalid Post (EForm::paramsToRegistry): '.(int) $item->id;
                                            $logger = AutotweetLogger::getInstance();
                                            $logger->log(\Joomla\CMS\Log\Log::ERROR, $msg);
                                            \Joomla\CMS\Factory::getApplication()->enqueueMessage($msg, 'error');
                                        }
                                    } ?>
                            <tr class="row<?php echo $m; ?> <?php
                                if ('error' === $item->pubstate) {
                                    echo $item->pubstate;
                                } ?>">
                                <?php
                                if (!$this->isModule) {
                                    ?>
                                <td><?php echo JHTML::_('grid.id', $i, $item->id, $checkedout); ?>
                                </td>
                                <?php
                                } ?>

                                <td><a href="<?php

                                echo $link; ?>" class="nobr"> <?php
                                echo JHtml::_('date', $item->postdate, JText::_('COM_AUTOTWEET_DATE_FORMAT')); ?>
                                </a>
                                </td>

                                <td><?php

                                echo EHtmlGrid::lockedWithIcons($checkedout); ?> <a href="<?php

                                echo $link; ?>"> <?php

                                $message = $item->message;
                                    $message = TextUtil::cleanText($message);

                                    if ($this->isModule) {
                                        $message = TextUtil::truncString($message, AutoTweetDefaultView::MAX_CHARS_TITLE_SHORT_SCREEN, true);
                                    } else {
                                        $message = TextUtil::truncString($message, AutoTweetDefaultView::MAX_CHARS_TITLE_SCREEN, true);
                                    }

                                    echo htmlentities($message, \ENT_COMPAT, 'UTF-8'); ?>
                                </a>
                                <?php

                                $url = $item->url;

                                    if (empty($url)) {
                                        $url = $item->org_url;
                                    }

                                    if (!empty($url)) {
                                        echo ' <a href="'.TextUtil::renderUrl($url).'" target="_blank"><i class="xticon fas fa-globe"></i></a>';
                                    }

                                    if (!empty($item->image_url)) {
                                        echo ' <a href="'.TextUtil::renderUrl($item->image_url).'" target="_blank"><i class="xticon far fa-image"></i></a>';
                                    } ?>
                                </td>

                                <td><span class="channel-<?php echo $item->channel_id; ?>"></span> <?php echo $item->channel_id ? SelectControlHelper::getChannelName($item->channel_id, $this->isModule) : '&mdash;'; ?>
                                </td>

                                <td>
                                    <div rel="tooltip" data-original-title="<?php

                                    $result = htmlentities(JText::_($item->resultmsg), \ENT_COMPAT, 'UTF-8');
                                    echo $result; ?>">
                                        <?php echo GridHelper::pubstates($item, $i, $this->isModule); ?>
                                    </div>
                                </td>

                                <?php
                                if (!$this->isModule) {
                                    ?>

                                <td class="nowrap"><?php

                                echo AutoTweetModelPlugins::getSimpleName($item->plugin);

                                    if ($is_evergreen) {
                                        echo ' ';

                                        if ($evergreen_link) {
                                            echo '<a href="'.$evergreen_link.'" target="_blank">';
                                        }

                                        echo '<i class="xticon fas fa-leaf"></i>';

                                        if ($evergreen_link) {
                                            echo '</a>';
                                        }
                                    }

                                    if ($is_immediate) {
                                        echo ' <i class="xticon fas fa-bolt"></i>';
                                    } ?>
                                </td>

                                <td><?php
                                echo $item->id; ?>
                                </td>

                                <?php
                                } ?>
                            </tr>
                            <?php
                                ++$i;
                                } ?>
                            <?php
                            } else {
                                ?>
                            <tr>
                                <td colspan="10" align="center"><?php echo JText::_('AUTOTWEET_COMMON_NOITEMS_LABEL'); ?></td>
                            </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
<?php

if (!$this->isModule) {
    ?>

            <div class="hidden xt-grid">
                <div class="xt-col-span-12">
                    <div class="text-center muted"><em>
<?php
                            echo JText::_('COM_AUTOTWEET_PROCESSING_MODES_INFO'); ?>
                        <a target="_blank" href="https://www.extly.com/docs/perfect_publisher/faq/troubleshooting/"><i class="xticon fas fa-link"></i></a>
                    </em></div>
                </div>
            </div>

<?php
    if ($manage) {
        echo JHtml::_(
            'bootstrap.renderModal',
            'collapseModal',
            [
                'title' => JText::_('COM_AUTOTWEET_BATCH_POSTS_TITLE'),
                'footer' => $this->loadTemplate('batch_footer'),
            ],
            $this->loadTemplate('batch_body')
        );
    }
}
?>

        </form>

    </div>
</div>
