<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

use XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\ScriptHelper;

JHtml::_('bootstrap.modal');

?>
<ul class="nav">
    <li class="divider"></li>
    <li>
        <a onclick="return false;"
            title="<?php echo JText::_('MOD_JOOCIAL_MENU_BUTTON'); ?>"
            href="#joocial_menu_modal"
            role="button"
            data-toggle="modal"
            class="btn btn-small btn-primary visible-desktop visible-tablet">

            <img style="width: 0.8em" src="../media/com_autotweet/images/perfectpub-logo-white.svg">
            <?php echo JText::_('MOD_JOOCIAL_MENU_BUTTON'); ?>
        </a>
    </li>
</ul>
<?php

    ScriptHelper::addScriptDeclaration("
    // PerfectPublisher PRO menu
    if (!window.xtModalClose) {
        window.xtModalClose = function() {
                jQuery('div.modal').modal('hide');
        };
    };
    ");

    $modal_dialog = JHtmlBootstrap::renderModal(
        'joocial_menu_modal',
        [
            'url' => $link,
            'title' => JText::_('MOD_JOOCIAL_MENU_BUTTON'),
            'height' => '400px',
            'width' => '500px',
            'class' => 'joocial_menu_modal',
        ]
    );
    echo $modal_dialog;

    ScriptHelper::addScriptDeclaration("
    jQuery(window).ready(function() {
        jQuery('#joocial_menu_modal').appendTo(\"body\");
    });
    ");
