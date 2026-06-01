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
<div class="xt-col-span-6">
    <div class="xt-grid">
        <div class="xt-col-span-12">
            <ul class="xt-nav-tabs-joomla3 nav nav-tabs" id="qTypeTabs">
                <li class="active" id="auditinfo-tab"><a data-toggle="tab" href="#auditinfo">
                    <i class="xticon far fa-user"></i>
                     <?php echo JText::_('COM_AUTOTWEET_AUDIT_INFORMATION'); ?>
                </a></li>

                <li id="overrideconditions-tab"><a data-toggle="tab" href="#override-conditions">
                    <i class="xticon far fa-file-alt"></i>
                     <?php echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_MESSAGE_OPTIONS'); ?>
                </a></li>

                <li id="filterconditions-tab"><a data-toggle="tab" href="#filterconditions">
                    <i class="xticon fas fa-filter"></i>
                     <?php echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_FILTERS_OPTIONS'); ?>
                </a></li>
            </ul>
<?php
            require_once 'tab-content.php';
?>
        </div>
    </div>
</div>
