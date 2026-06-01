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

            <ul class="xt-nav xt-nav-tabs xt-nav-tabs-joomla4 nav nav-tabs" id="qTypeTabs">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="auditinfo-tab" data-bs-toggle="tab"
                        data-bs-target="#auditinfo" type="button" role="tab" aria-controls="auditinfo"
                        aria-selected="true">
                        <i class="xticon far fa-user"></i>
                        <?php echo JText::_('COM_AUTOTWEET_AUDIT_INFORMATION'); ?>
                    </a>
                </li>

                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="override-conditions-tab" data-bs-toggle="tab"
                        data-bs-target="#override-conditions" type="button" role="tab" aria-controls="override-conditions"
                        aria-selected="false">
                        <i class="xticon far fa-file-alt"></i>
                        <?php echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_MESSAGE_OPTIONS'); ?>
                    </a>
                </li>

                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="filterconditions-tab" data-bs-toggle="tab"
                        data-bs-target="#filterconditions" type="button" role="tab" aria-controls="filterconditions"
                        aria-selected="false">
                        <i class="xticon fas fa-filter"></i>
                        <?php echo JText::_('COM_AUTOTWEET_VIEW_MANUALMSG_FILTERS_OPTIONS'); ?>
                    </a>
                </li>
            </ul>

<?php
            require_once 'tab-content.php';
?>

        </div>
    </div>
</div>
