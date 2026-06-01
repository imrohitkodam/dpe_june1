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

$hasAjaxOrderingSupport = (bool) $this->hasAjaxOrderingSupport();
$ordering = ('ordering' === $this->lists->order);

if (($hasAjaxOrderingSupport) && ($ordering)) {
    EHtmlGrid::ajaxOrderingInit(CAUTOTWEETNG, $this->lists->order_Dir);
}

?>
<div class="extly">
    <div class="xt-body">
        <div class="xt-grid">
            <div class="xt-col-span-12">

                <form name="adminForm" id="adminForm" action="index.php" method="post">

                    <input type="hidden" name="option" id="option" value="com_autotweet" />
                    <input type="hidden" name="view" id="view" value="rules" />
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
                                if (!$hasAjaxOrderingSupport) {
                                    ?>
                                <th width="35px"><?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'ordering', $this->lists->order_Dir, $this->lists->order, 'browse', 'asc', 'JGRID_HEADING_ORDERING'); ?>
                                </th>
                                <?php
                                }
                                ?>

                                <th width="20"><input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
                                </th>
                                <th><?php echo JHTML::_('grid.sort', 'COM_AUTOTWEET_RULES_FIELD_NAME', 'name', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>

                                <th width="160"><?php echo JHTML::_('grid.sort', 'LBL_RULES_PLUGIN', 'plugin', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>

                                <th width="160"><?php echo JHTML::_('grid.sort', 'COM_AUTOTWEET_VIEW_TYPE_TITLE', 'ruletype_id', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>

                                <th width="160"><?php echo JHTML::_('grid.sort', 'LBL_RULES_CHANNEL', 'channel_id', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>

                                <?php
                                if (false === (bool) $hasAjaxOrderingSupport) {
                                    ?>
                                <th>
                                    <?php
                                        echo JHTML::_('grid.sort', 'JFIELD_ORDERING_LABEL', 'ordering', $this->lists->order_Dir, $this->lists->order, 'browse');
                                    echo JHTML::_('grid.order', $this->items); ?>
                                </th>
                                <?php
                                }
                                ?>

                                <th width="80"><?php echo JHTML::_('grid.sort', 'JPUBLISHED', 'published', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>

                                <th width="80"><?php echo JHTML::_('grid.sort', 'JGLOBAL_FIELD_ID_LABEL', 'id', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>
                            </tr>
                            <tr>
                                <?php
                                if (!$hasAjaxOrderingSupport) {
                                    ?>
                                <td></td>
                                <?php
                                }
                                ?>

                                <td></td>
                                <td class="form-inline nowrap">
                                    <div class="xt-input-append">
                                        <input type="text" name="name" id="name" value="<?php echo $this->escape($this->getModel()->getState('name')); ?>" class="input-medium" onchange="document.adminForm.submit();"
                                            placeholder="<?php echo JText::_('COM_AUTOTWEET_RULES_FIELD_NAME'); ?>" />
                                        <button class="btn" onclick="this.form.submit();">
                                            <?php echo JText::_('COM_AUTOTWEET_FILTER_SUBMIT'); ?>
                                        </button>
                                    </div>

                                    <a class="xtd-btn-reset"><small><?php echo JText::_('COM_AUTOTWEET_RESET'); ?></small></a>
                                </td>

                                <td><?php echo SelectControlHelper::plugins($this->getModel()->getState('plugin'), 'plugin', ['onchange' => 'this.form.submit();', 'class' => 'input-medium']); ?>
                                </td>

                                <td><?php echo SelectControlHelper::ruletypes($this->getModel()->getState('ruletype'), 'ruletype', ['onchange' => 'this.form.submit();', 'class' => 'input-medium']); ?>
                                </td>

                                <td><?php echo SelectControlHelper::channels($this->getModel()->getState('channel'), 'channel', ['onchange' => 'this.form.submit();', 'class' => 'input-medium']); ?>
                                </td>

                                <?php
                                if (false === (bool) $hasAjaxOrderingSupport) {
                                    ?>
                                <td></td>
                                <?php
                                }
                                ?>

                                <td><?php echo EHtmlSelect::yesNo($this->getModel()->getState('published', 1), 'published', ['onchange-submit' => 'true', 'class' => 'btn-mini']); ?>
                                </td>

                                <td>
                                </td>
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
                            $i = 0;
                            $m = 1;

                            foreach ($this->items as $item) {
                                $m = 1 - $m;

                                $checkedout = (bool) $item->checked_out; ?>
                            <tr class="row<?php echo $m; ?>">

                                <?php
                                if (!$hasAjaxOrderingSupport) {
                                    $editstate = (($this->perms->editstate) && ($ordering));
                                    echo EHtmlGrid::ajaxOrderingColumn($editstate, $item->ordering);
                                } ?>

                                <td><?php echo JHTML::_('grid.id', $i, $item->id, $checkedout); ?>
                                </td>
                                <td><?php

                                echo EHtmlGrid::lockedWithIcons($checkedout); ?> <a href="<?php

                                echo JRoute::_('index.php?option=com_autotweet&view=rules&task=edit&id='.(int) $item->id); ?>"> <?php
                                echo htmlentities($item->name, \ENT_COMPAT, 'UTF-8'); ?>
                                </a>
                                </td>

                                <td><?php echo AutotweetModelPlugins::getSimpleName($item->plugin); ?>
                                </td>

                                <td><span class="rule-type-<?php echo $item->ruletype_id; ?>"></span> <?php echo $item->ruletype_id ? SelectControlHelper::getRuletypeName($item->ruletype_id) : '&mdash;'; ?>
                                </td>

                                <td><span class="channel-type-<?php echo $item->channel_id; ?>"></span> <?php echo $item->channel_id ? SelectControlHelper::getChannelName($item->channel_id) : JText::_('JALL'); ?>
                                </td>

                                <?php
                                if (false === (bool) $hasAjaxOrderingSupport) {
                                    echo EHtmlGrid::basicOrderingColumn($this->pagination, $i, $count, $item->ordering);
                                } ?>

                                <td><?php

                                echo EHtmlGrid::publishedWithIcons($item, $i, $this->perms->editstate); ?>
                                </td>

                                <td>
                                <?php
                                echo $item->id; ?>
                                </td>
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

                </form>

            </div>
        </div>
    </div>
</div>
