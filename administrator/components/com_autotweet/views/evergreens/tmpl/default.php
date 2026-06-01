<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

XTF0FModel::getTmpInstance('Plugins', 'AutoTweetModel');

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
                    <input type="hidden" name="view" id="view" value="evergreen" />
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

                                <th width="20"><input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
                                </th>

                                <th><?php echo JHTML::_('grid.sort', 'COM_AUTOTWEET_VIEW_MSGLOG_POSTDATE_TITLE', 'r.publish_up', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>

                                <th><?php echo JHTML::_('grid.sort', 'COM_AUTOTWEET_TITLE_EVERGREEN_EDIT', 'r.description', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>

                                <th width="80"><?php echo JHTML::_('grid.sort', 'COM_AUTOTWEET_VIEW_SOURCE_TITLE', 'r.plugin', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>

                                <th width="80"><?php echo JHTML::_('grid.sort', 'JGLOBAL_FIELD_ID_LABEL', 'r.id', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
                                </th>
                            </tr>
                            <tr>

                                <td></td>

                                <td class="form-inline"><?php echo JHTML::_('calendar', $this->getModel()->getState('publish_up'), 'publish_up', 'publish_up', JText::_('COM_AUTOTWEET_DATE_VIEW_FORMAT'), ['class' => 'input-small']); ?>
                                </td>

                                <td class="form-inline nowrap">
                                    <div class="xt-input-append">
                                        <input type="text" name="search" id="search" value="<?php echo $this->escape($this->getModel()->getState('search')); ?>" class="input-medium" onchange="document.adminForm.submit();"
                                            placeholder="<?php echo JText::_('COM_AUTOTWEET_REQUESTS_FIELD_MESSAGE'); ?>" />
                                        <button class="btn" onclick="this.form.submit();">
                                            <?php echo JText::_('COM_AUTOTWEET_FILTER_SUBMIT'); ?>
                                        </button>
                                    </div>

                                    <a class="xtd-btn-reset"><small><?php echo JText::_('COM_AUTOTWEET_RESET'); ?></small></a>
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
                                    $checkedout = (bool) $item->checked_out;
                                    $ordering = 'ordering' === $this->lists->order;
                                    $native_object = TextUtil::json_decode($item->native_object);
                                    $has_error = false;

                                    $link = AdvancedAttributesHelper::getEditLink($item->client_id, $item->option, $item->attr_ref_id, $item->id);

                                    if ((isset($native_object->error)) && ($native_object->error)) {
                                        $has_error = true;
                                        $alert_style = 'alert-error';
                                        $alert_message = JText::_($native_object->error_message);
                                    } ?>
                            <tr class="row<?php echo $m; ?> <?php
                                if ($has_error) {
                                    echo 'error';
                                } ?>">

                                <td><?php echo JHTML::_('grid.id', $i, $item->attr_id, $checkedout); ?>
                                </td>

                                <td><?php

                                if (empty($item->publish_up)) {
                                    echo '<span class="alert-error error"><i class="xticon fas fa-exclamation-circle"></i></span>';
                                } else {
                                    echo JHtml::_('date', $item->publish_up, JText::_('COM_AUTOTWEET_DATE_FORMAT'));
                                } ?>
                                </td>

                                <td><?php

                                $locked = EHtmlGrid::lockedWithIcons($checkedout);

                                    if (!empty($locked)) {
                                        echo $locked.' ';
                                    }

                                    $description = TextUtil::truncString($item->description, AutoTweetDefaultView::MAX_CHARS_TITLE_SCREEN, true);
                                    $description = htmlentities($description, \ENT_COMPAT, 'UTF-8');

                                    if (empty($description)) {
                                        echo '<span class="alert-error error"><i class="xticon fas fa-exclamation-circle"></i> ';
                                        echo JText::_('COM_AUTOTWEET_EVERGREEN_REQ_NEVER_PUBLISHED').'</span>';
                                    } else {
                                        echo $description;
                                    }

                                    if (!empty($item->url)) {
                                        echo ' <a href="'.TextUtil::renderUrl($item->url).'" target="_blank"><i class="xticon fas fa-globe"></i></a>';
                                    }

                                    if (!empty($item->image_url)) {
                                        echo ' <a href="'.TextUtil::renderUrl($item->image_url).'" target="_blank"><i class="xticon far fa-image"></i></a>';
                                    } ?>
                                </td>

                                <td><?php

                                $simple_name = AutoTweetModelPlugins::getSimpleName($item->plugin);

                                    if (empty($simple_name)) {
                                        echo $item->option;
                                    } else {
                                        echo $simple_name;
                                    }

                                    echo ' ';

                                    if ($link) {
                                        echo '<a href="'.$link.'" target="_blank">';
                                    }

                                    echo '<i class="xticon fas fa-leaf"></i>';

                                    if ($link) {
                                        echo '</a>';
                                    } ?>
                                </td>

                                <td><?php

                                echo $item->attr_ref_id;
                                    echo ' ('.$item->attr_id.')'; ?>
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

                </div>
            </div>

            <div class="hidden xt-grid">
                <div class="xt-col-span-12">
                    <div class="text-center muted"><em>
<?php
                            echo JText::_('COM_AUTOTWEET_PROCESSING_MODES_INFO');
?>
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
                'title' => JText::_('COM_AUTOTWEET_BATCH_REQS_TITLE'),
                'footer' => $this->loadTemplate('batch_footer'),
            ],
            $this->loadTemplate('batch_body')
        );
    }
?>

        </form>
    </div>
</div>
