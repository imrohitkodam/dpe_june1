<?php
/**
 * Droptables
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and to customize.
 * Otherwise, please feel free to contact us at contact@joomunited.com *
 *
 * @package   Droptables
 * @copyright Copyright (C) 2014 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @copyright Copyright (C) 2014 Damien Barrère (http://www.crac-design.com). All rights reserved.
 * @license   GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') || die;

/**
 * This is a file to add template specific chrome to module rendering.  To use it you would
 * set the style attribute for the given module(s) include in your template to use the style
 * for each given modChrome function.
 *
 * Eg. To render a module mod_test in the submenu style, you would use the following include:
 * <jdoc:include type="module" name="test" style="submenu" />
 *
 * This gives template designers ultimate control over how modules are rendered.
 *
 * NOTICE: All chrome wrapping methods should be named: modChrome_{STYLE} and take the same
 * two arguments.
 *
 * @param object $module  Module
 * @param object $params  Params
 * @param array  $attribs Attributes
 *
 * @return void
 */
function modChrome_no($module, &$params, &$attribs)
{
    if ($module->content) {
        echo $module->content;
    }
}

/**
 * Mod Chrome
 *
 * @param object $module  Module
 * @param object $params  Params
 * @param array  $attribs Attributes
 *
 * @return void
 * @since  version
 */
function modChrome_well($module, &$params, &$attribs)
{
    $moduleTag     = $params->get('module_tag', 'div');
    $bootstrapSize = (int) $params->get('bootstrap_size', 0);
    $moduleClass   = $bootstrapSize !== 0 ? ' span' . $bootstrapSize : '';
    //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- Not support php version 5.4 or earlier
    $headerTag     = htmlspecialchars($params->get('header_tag', 'h3'), ENT_COMPAT | ENT_HTML401, 'UTF-8');
    //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- Not support php version 5.4 or earlier
    $headerClass   = htmlspecialchars($params->get('header_class', 'page-header'), ENT_COMPAT | ENT_HTML401, 'UTF-8');

    if ($module->content) {
        //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- Not support php version 5.4 or earlier
        echo '<' . $moduleTag . ' class="well ' . htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT | ENT_HTML401, 'UTF-8') . $moduleClass . '">';

        if ($module->showtitle) {
            echo '<' . $headerTag . ' class="' . $headerClass . '">' . $module->title . '</' . $headerTag . '>';
        }

        echo $module->content;
        echo '</' . $moduleTag . '>';
    }
}
