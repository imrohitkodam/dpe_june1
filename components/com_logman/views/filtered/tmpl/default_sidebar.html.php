<?
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('_JEXEC') or die; ?>

<!-- Sidebar -->
<div class="k-sidebar-left k-js-sidebar-left">

    <!-- Navigation -->
    <div class="k-sidebar-item">
        <ktml:toolbar type="menubar">
    </div>

    <!-- Filters -->
    <div class="k-sidebar-item k-js-sidebar-toggle-item">
        <div class="k-sidebar-item__header">
            <?= translate('Quick filters') ?>
        </div>
        <ul class="k-list">
            <li>
                <a href="<?= route('read=&user=&usergroup=&package=&start_date=&end_date=&day_range=&ip='); ?>">
                    <span class="k-icon-list" aria-hidden="true"></span>
                    <?= translate('All activities'); ?>
                </a>
            </li>
            <? $user_id = object('user')->getId(); ?>
            <li class="<?= parameters()->user == $user_id ? 'k-is-active' : ''; ?>">
                <a href="<?= route('read=&user='.(parameters()->user == 0 || parameters()->user != $user_id ? $user_id : '')) ?>">
                    <span class="k-icon-person" aria-hidden="true"></span>
                    <?= translate('My activities'); ?>
                </a>
            </li>
        </ul>
    </div>

</div><!-- .k-sidebar -->
