<?
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('_JEXEC') or die; ?>


<? // Loading tree behavior ?>
<?= helper('behavior.component_tree', array(
    'packages' => $packages,
    'selected' => parameters()->package
))
?>


<!-- Sidebar -->
<div class="k-sidebar-left k-js-sidebar-left">

    <!-- Navigation -->
    <div class="k-sidebar-item">
        <ktml:toolbar type="menubar">
    </div>

    <div class="k-sidebar-item k-sidebar-item--flex">
        <div class="k-sidebar-item__header">
            <?= translate('Components'); ?>
        </div>
        <div class="k-tree k-js-component-tree">
            <div class="k-sidebar-item__content k-sidebar-item__content--horizontal">
                <?= translate('Loading') ?>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="k-sidebar-item k-js-sidebar-toggle-item">
        <div class="k-sidebar-item__header">
            <?= translate('Quick filters') ?>
        </div>
        <ul class="k-list">
            <li>
                <a href="<?= route('read=0&user=&usergroup=&package=&start_date=&end_date=&day_range=&ip='); ?>">
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
            <li class="<?= parameters()->user == '-1' ? 'k-is-active' : '' ?>">
                <a href="<?= parameters()->user == '-1' ? route('read=&user=') : route('read=&user=-1') ?>">
                    <span class="k-icon-terminal" aria-hidden="true"></span>
                    <?= translate('System activities'); ?>
                </a>
            </li>
            <li class="<?= parameters()->read == '1' ? 'k-is-active' : '' ?>">
                <a href="<?= parameters()->read == '1' ? route('read=0&user=') : route('read=1&user=') ?>">
                    <span class="k-icon-eye" aria-hidden="true"></span>
                    <?= translate('Page views'); ?>
                </a>
            </li>
        </ul>
    </div>

</div><!-- .k-sidebar -->
