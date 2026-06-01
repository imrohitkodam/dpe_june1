<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

/**
 * Extly view renderer class.
 *
 * @since       1.0
 */
class AutotweetRenderBack3 extends XTF0FRenderJoomla3
{
    /**
     * Renders the submenu (link bar) in XTF0F's classic style, using a Bootstrapped
     * tab bar.
     *
     * @param string $view   The active view name
     * @param string $task   The current task
     * @param JInput $input  The input object
     * @param array  $config Extra configuration variables for the toolbar
     */
    protected function renderLinkbar_classic($view, $task, $input, $config = [])
    {
        if (XTF0FPlatform::getInstance()->isCli()) {
            return;
        }

        // Do not render a submenu unless we are in the the admin area
        $toolbar = XTF0FToolbar::getAnInstance($input->getCmd('option', 'com_foobar'), $config);
        $renderFrontendSubmenu = $toolbar->getRenderFrontendSubmenu();

        if (!XTF0FPlatform::getInstance()->isBackend() && !$renderFrontendSubmenu) {
            return;
        }

        $links = $toolbar->getLinks();

        if (!empty($links)) {
            echo "<ul class=\"xt-nav\">\n";

            foreach ($links as $link) {
                $dropdown = false;

                if (array_key_exists('dropdown', $link)) {
                    $dropdown = $link['dropdown'];
                }

                if ($dropdown) {
                    echo '<li';
                    $class = 'dropdown';

                    if ($link['active']) {
                        $class .= ' active';
                    }

                    echo ' class="'.$class.'">';

                    echo '<a class="dropdown-toggle" data-bs-toggle="dropdown" data-toggle="dropdown" href="#">';

                    if ($link['icon']) {
                        echo '<i class="'.$link['icon'].'"></i>&nbsp;';
                    }

                    echo $link['name'];
                    echo '<b class="caret"></b>';
                    echo '</a>';

                    echo "\n<ul class=\"dropdown-menu\">";

                    foreach ($link['items'] as $item) {
                        echo '<li';

                        if ($item['active']) {
                            echo ' class="active"';
                        }

                        echo '>';

                        if ($item['link']) {
                            echo '<a tabindex="-1" href="'.$item['link'].'">'.
                                ($item['icon'] ? '<i class="'.$item['icon'].'"></i>' : '').
                                $item['name'].'</a>';
                        } else {
                            if ($item['icon']) {
                                echo '<i class="'.$item['icon'].'"></i>';
                            }

                            echo $item['name'];
                        }

                        echo '</li>';
                    }

                    echo "</ul>\n";
                } else {
                    echo '<li';

                    if ($link['active']) {
                        echo ' class="active"';
                    }

                    echo '>';

                    if ($link['link']) {
                        echo '<a href="'.$link['link'].'">';

                        if ($link['icon']) {
                            echo '<i class="'.$link['icon'].'"></i>&nbsp;';
                        }

                        echo $link['name'].'</a>';
                    } else {
                        if ($link['icon']) {
                            echo '<i class="'.$link['icon'].'"></i>&nbsp;';
                        }

                        echo $link['name'];
                    }
                }

                echo "</li>\n";
            }

            echo "</ul>\n";
        }
    }
}

/**
 * Autotweet view renderer class.
 *
 * @since       1.0
 */
class AutotweetRenderFront3 extends XTF0FRenderJoomla3
{
    /**
     * Echoes any HTML to show before the view template.
     *
     * @param string $view   The current view
     * @param string $task   The current task
     * @param JInput $input  The input array (request parameters)
     * @param array  $config The view configuration array
     */
    public function preRender($view, $task, $input, $config = [])
    {
        $format = $input->getCmd('format', 'html');

        if (empty($format)) {
            $format = 'html';
        }

        if ('html' !== $format) {
            return;
        }

        // Render the submenu and toolbar
        if ($input->getBool('render_toolbar', true)) {
            $this->renderLinkbar($view, $task, $input, $config);
            $this->renderButtons($view, $task, $input, $config);
        }
    }

    /**
     * Echoes any HTML to show after the view template.
     *
     * @param string $view   The current view
     * @param string $task   The current task
     * @param JInput $input  The input array (request parameters)
     * @param array  $config The view configuration array
     */
    public function postRender($view, $task, $input, $config = [])
    {
        $format = $input->getCmd('format', 'html');

        if (empty($format)) {
            $format = 'html';
        }

        if ('html' !== $format) {
            return;
        }

        // Closing tag only if we're not in CLI
        if (XTF0FPlatform::getInstance()->isCli()) {
            return;
        }
    }

    /**
     * Renders the submenu (link bar) in XTF0F's classic style, using a Bootstrapped
     * tab bar.
     *
     * @param string $view   The active view name
     * @param string $task   The current task
     * @param JInput $input  The input object
     * @param array  $config Extra configuration variables for the toolbar
     */
    protected function renderLinkbar_classic($view, $task, $input, $config = [])
    {
        if (XTF0FPlatform::getInstance()->isCli()) {
            return;
        }

        if (isset(\Joomla\CMS\Factory::getApplication()->JComponentTitle)) {
            $title = \Joomla\CMS\Factory::getApplication()->JComponentTitle;
        } else {
            $title = JText::_('COM_AUTOTWEET');
        }

        $title = strip_tags($title);

        echo '<h1 class="page-title">'.$title.'</h1>';

        // Do not render a submenu unless we are in the the admin area
        $toolbar = XTF0FToolbar::getAnInstance($input->getCmd('option', 'com_foobar'), $config);
        $renderFrontendSubmenu = $toolbar->getRenderFrontendSubmenu();

        if (!XTF0FPlatform::getInstance()->isBackend() && !$renderFrontendSubmenu) {
            return;
        }

        $links = $toolbar->getLinks();

        if (!empty($links)) {
            echo "<p></p><div class=\"navbar\"><div class=\"navbar-inner\"><ul class=\"xt-nav xt-nav-tabs\">\n";

            foreach ($links as $link) {
                $dropdown = false;

                if (array_key_exists('dropdown', $link)) {
                    $dropdown = $link['dropdown'];
                }

                if ($dropdown) {
                    echo '<li';
                    $class = 'dropdown';

                    if ($link['active']) {
                        $class .= ' active';
                    }

                    echo ' class="'.$class.'">';

                    echo '<a class="dropdown-toggle" data-bs-toggle="dropdown" data-toggle="dropdown" href="#">';

                    if ($link['icon']) {
                        echo '<i class="'.$link['icon'].'"></i>&nbsp;';
                    }

                    echo $link['name'];
                    echo '<b class="caret"></b>';
                    echo '</a>';

                    echo "\n<ul class=\"dropdown-menu\">";

                    foreach ($link['items'] as $item) {
                        echo '<li';

                        if ($item['active']) {
                            echo ' class="active"';
                        }

                        echo '>';

                        if ($item['link']) {
                            echo '<a tabindex="-1" href="'.$item['link'].'">'.
                                '<i class="'.$item['icon'].'"></i>'.
                                $item['name'].'</a>';
                        } else {
                            if ($item['icon']) {
                                echo '<i class="'.$item['icon'].'"></i>';
                            }

                            echo $item['name'];
                        }

                        echo '</li>';
                    }

                    echo "</ul>\n";
                } else {
                    echo '<li';

                    if ($link['active']) {
                        echo ' class="active"';
                    }

                    echo '>';

                    if ($link['link']) {
                        echo '<a href="'.$link['link'].'">';

                        if ($link['icon']) {
                            echo '<i class="'.$link['icon'].'"></i>&nbsp;';
                        }

                        echo $link['name'].'</a>';
                    } else {
                        if ($link['icon']) {
                            echo '<i class="'.$link['icon'].'"></i>&nbsp;';
                        }

                        echo $link['name'];
                    }
                }

                echo "</li>\n";
            }

            echo "</ul></div></div>\n";
        }
    }

    /**
     * Renders the toolbar buttons.
     *
     * @param string $view   The active view name
     * @param string $task   The current task
     * @param JInput $input  The input object
     * @param array  $config Extra configuration variables for the toolbar
     */
    protected function renderButtons($view, $task, $input, $config = [])
    {
        if (XTF0FPlatform::getInstance()->isCli()) {
            return;
        }

        // Do not render buttons unless we are in the the frontend area and we are asked to do so
        $toolbar = XTF0FToolbar::getAnInstance($input->getCmd('option', 'com_foobar'), $config);
        $renderFrontendButtons = $toolbar->getRenderFrontendButtons();

        // Load main backend language, in order to display toolbar strings
        // (JTOOLBAR_BACK, JTOOLBAR_PUBLISH etc etc)
        XTF0FPlatform::getInstance()->loadTranslations('joomla');

        if (XTF0FPlatform::getInstance()->isBackend() || !$renderFrontendButtons) {
            return;
        }

        $bar = JToolBar::getInstance('toolbar');
        $items = $bar->getItems();

        $substitutions = [
            'icon-new' => 'xticon fas fa-plus',
            'icon-white' => 'xticon fas fa-chalkboard',
            'icon-publish' => 'xticon fas fa-check-sign',
            'icon-unpublish' => 'xticon fas fa-times',
            'icon-delete' => 'xticon fas fa-times',
            'icon-edit' => 'xticon fas fa-pencil-alt',
            'icon-copy' => 'xticon far fa-copy',
            'icon-cancel' => 'xticon fas fa-times',
            'icon-back' => 'xticon fas fa-times',
            'icon-apply' => 'xticon far fa-save',
            'icon-save' => 'xticon fas fa-pencil-alt',
            'icon-save-new' => 'xticon fas fa-plus',
            'icon-process' => 'xticon fas fa-cog',
        ];

        $html = [];
        $actions = [];

        $html[] = '<div id="XTF0FHeaderHolder" class="xt-grid"><div class="xt-col-span-12">';
        $html[] = '<div class="buttonsHolder btn-toolbar xt-float-right">';

        foreach ($items as $node) {
            $type = $node[0];
            $button = $bar->loadButtonType($type);

            if (false !== $button) {
                if (method_exists($button, 'fetchId')) {
                    $id = call_user_func_array([&$button, 'fetchId'], $node);
                } else {
                    $id = null;
                }

                $action = call_user_func_array([&$button, 'fetchButton'], $node);
                $action = str_replace('class="toolbar"', 'class="toolbar btn"', $action);
                $action = str_replace('<span ', '<i ', $action);
                $action = str_replace('</span>', '</i>', $action);
                $action = str_replace(array_keys($substitutions), array_values($substitutions), $action);
                $actions[] = $action;
            }
        }

        $html = array_merge($html, $actions);
        $html[] = '</div>';
        $html[] = '</div></div>';

        echo implode("\n", $html);
    }
}
