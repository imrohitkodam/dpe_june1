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

?>
<div class="extly">
    <div class="xt-body">
        <div class="xt-grid">
            <div class="xt-col-span-12">

                <form name="adminForm" id="adminForm" action="index.php" method="post">

                    <input type="hidden" name="option" id="option" value="com_autotweet" />
                    <input type="hidden" name="view" id="view" value="channels" />
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
                                <th width="20px"><?php echo JHtml::_('grid.sort', '<i class="xticon fas fa-bars"></i>', 'ordering', $this->lists->order_Dir, $this->lists->order, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
                                </th>
                                <?php
                                }
                                ?>
                                <th width="20"><input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
                                </th>
                                <th><?php echo JHTML::_('grid.sort', 'COM_AUTOTWEET_CHANNELS_FIELD_NAME', 'name', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>
                                <th width="160"><?php echo JHTML::_('grid.sort', 'LBL_CHANNELS_CHANNELTYPE', 'channeltype_id', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>
                                <?php
                                if (false === (bool) $hasAjaxOrderingSupport) {
                                    ?>
                                <th class="order"><?php echo JHTML::_('grid.sort', 'JFIELD_ORDERING_LABEL', 'ordering', $this->lists->order_Dir, $this->lists->order, 'browse'); ?> <?php
                                echo JHTML::_('grid.order', $this->items); ?>
                                </th>
                                <?php
                                }
                                ?>

                                <th width="80"><?php echo JHTML::_('grid.sort', 'JPUBLISHED', 'published', $this->lists->order_Dir, $this->lists->order, 'browse'); ?></th>

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
                                            placeholder="<?php echo JText::_('COM_AUTOTWEET_CHANNELS_FIELD_NAME'); ?>" />
                                        <button class="btn" onclick="this.form.submit();">
                                            <?php echo JText::_('COM_AUTOTWEET_FILTER_SUBMIT'); ?>
                                        </button>
                                    </div>

                                    <a class="xtd-btn-reset"><small><?php echo JText::_('COM_AUTOTWEET_RESET'); ?></small></a>
                                </td>
                                <td><?php echo SelectControlHelper::channeltypes($this->getModel()->getState('channeltype'), 'channeltype', ['onchange' => 'this.form.submit();', 'class' => 'input-medium']); ?>
                                </td>
                                <?php

                                if (false === (bool) $hasAjaxOrderingSupport) {
                                    echo '<td></td>';
                                }

                                echo '<td>'.EHtmlSelect::yesNo($this->getModel()->getState('published', 1), 'published', ['onchange-submit' => 'true', 'class' => 'btn-mini']).'</td>';

                                ?>
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
                                ?>
                            <?php
                            $i = 0;
                                $m = 1;

                                foreach ($this->items as $item) {
                                    $m = 1 - $m;

                                    $checkedout = (bool) $item->checked_out; ?>
                            <tr class="row<?php echo $m; ?>">
                                <?php
                                if (!$hasAjaxOrderingSupport) {
                                    ?>
                                <td class="order nowrap center hidden-phone"><?php
                                if ($this->perms->editstate) {
                                    $disableClassName = '';
                                    $disabledLabel = '';

                                    if (!$hasAjaxOrderingSupport['saveOrder']) {
                                        $disabledLabel = JText::_('JORDERINGDISABLED');
                                        $disableClassName = 'inactive tip-top';
                                    } ?> <span class="sortable-handler <?php echo $disableClassName; ?>" title="<?php echo $disabledLabel; ?>" rel="tooltip"> <i class="xticon fas fa-bars"></i>
                                </span> <input type="text" style="display: none" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="input-mini text-area-order " /> <?php
                                } else {
                                    ?> <span class="sortable-handler inactive"> <i class="xticon fas fa-bars"></i>
                                </span> <?php
                                } ?>
                                </td>
                                <?php
                                } ?>
                                <td><?php echo JHTML::_('grid.id', $i, $item->id, $checkedout); ?>
                                </td>
                                <td><?php

                                echo EHtmlGrid::lockedWithIcons($checkedout); ?> <a href="<?php

                                echo JRoute::_('index.php?option=com_autotweet&view=channels&task=edit&id='.(int) $item->id); ?>"> <?php
                                echo htmlentities($item->name, \ENT_COMPAT, 'UTF-8'); ?>
                                </a>
                                </td>
                                <td><span class="channel-type-<?php echo $item->channeltype_id; ?>"></span> <?php echo $item->channeltype_id ? SelectControlHelper::getChanneltypeName($item->channeltype_id) : '&mdash;'; ?>
                                </td>
                                <?php
                                if (false === (bool) $hasAjaxOrderingSupport) {
                                    ?>
                                <td class="order"><span class="order-arrow"><?php
                                echo $this->pagination->orderUpIcon($i, true, 'orderup', 'Move Up', $ordering); ?> </span> <span class="order-arrow"><?php echo $this->pagination->orderDownIcon($i, $count, true, 'orderdown', 'Move Down', $ordering); ?> </span> <?php
                                $disabled = $ordering ? '' : 'disabled="disabled"'; ?> <input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>" <?php echo $disabled; ?> class="input-ordering" style="text-align: center" />
                                </td>
                                <?php
                                } ?>
                                <?php

                                echo '<td>'.EHtmlGrid::publishedWithIcons($item, $i, $this->perms->editstate).'</td>';
                                    echo '<td>'.$item->id.'</td>'; ?>
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
