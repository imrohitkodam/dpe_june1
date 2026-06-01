<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

$rootUri = \Joomla\CMS\Uri\Uri::root();

?>
    <hr/>

    <h1><?php echo VersionHelper::getFlavourName(); ?></h1>
    <p class="lead">Publish your content easily and engage your audience.</p>

    <p>
        <i class="xticon fas fa-desktop"></i> Tutorials: <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')">
                How to publish from Joomla</a>
    </p>

    <p>
        <i class="xticon fas fa-info"></i> For more information: <a
            href="https://www.extly.com/docs/perfect_publisher/"
            target="_blank">Documentation</a>
    </p>

    <p><i class="xticon far fa-question-circle"></i>
        <?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SUPPORT_TITLE'); ?>

        <a href="https://support.extly.com" target="_blank">https://support.extly.com</a>
    </p>
    <p>&nbsp;</p>
    <p class="customsocialicons">
        <?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SUPPORT_TWITTERFOLLOW'); ?>:

        <a target="_blank" href="https://www.facebook.com/extly"><i
            class="xticon fab fa-facebook-f"></i>
        </a>

        <a target="_blank" href="https://twitter.com/extly"><i
            class="xticon fab fa-twitter"></i>
        </a>

        <a target="_blank"
            href="https://www.linkedin.com/company/extly-com---joomla-extensions?trk=hb_tab_compy_id_2890809"><i
            class="xticon fab fa-linkedin-in"></i>
        </a>

        <a target="_blank" href="https://www.instagram.com/extlyextensions"><i
            class="xticon fab fa-instagram"></i>
        </a>

        <a target="_blank" href="https://extly.tumblr.com/"><i
            class="xticon fab fa-tumblr"></i>
        </a>

        <a target="_blank" href="https://github.com/anibalsanchez"> <i
            class="xticon fab fa-github"></i>
        </a>
    </p>
    <p>
      A PHP project built with <a href="https://php-prefixer.com" target="_blank"
        rel="noopener noreferrer">PHP-Prefixer</a>.
    </p>
