<?php
/**
 * @package         Modals
 * @version         12.3.5PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2023 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace RegularLabs\Plugin\System\Modals;

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Filesystem\File as JFile;
use Joomla\CMS\Filesystem\Folder as JFolder;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Router\Route as JRoute;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\Protect as RL_Protect;
use RegularLabs\Library\RegEx as RL_RegEx;

class Document
{
    static $all_themes;
    static $used_themes = [];

    public static function addPrint($url)
    {
        $url = explode('#', $url, 2);

        $url[0] .= (strpos($url[0], '?') === false) ? '?print=1' : '&amp;print=1';

        $url = implode('#', $url);

        return $url;
    }

    public static function addUrlAttributes($url, $fullpage = false, $print = false)
    {
        $url = explode('#', $url, 2);

        if (substr($url[0], 0, 4) != 'http'
            && strpos($url[0], 'index.php') === 0
            && strpos($url[0], '/') === false
        )
        {
            $url[0] = JRoute::link('site', $url[0]);
        }

        $url[0] = self::setUrlAttribute($url[0], 'ml');

        if ($fullpage)
        {
            $url[0] = self::setUrlAttribute($url[0], 'fullpage');
        }

        if ($print)
        {
            $url[0] = self::setUrlAttribute($url[0], 'print');
        }

        $url = implode('#', $url);

        return $url;
    }

    public static function loadStylesAndScripts()
    {
        // do not load scripts/styles on feeds or print pages
        if (RL_Document::isFeed() || JFactory::getApplication()->input->getInt('print', 0))
        {
            return;
        }

        $params = Params::get();

        JText::script('MDL_MODALTXT_CLOSE', true);
        JText::script('MDL_MODALTXT_PREVIOUS', true);
        JText::script('MDL_MODALTXT_NEXT', true);

        $options = [
            'theme'                  => $params->theme ?: 'dark',
            'dimensionsIncludeTitle' => (int) $params->dimensions_include_title,
            'position'               => $params->position ?: 'center',
            'showCountdown'          => (bool) ($params->showcountdown ?? false),
            'slideshow'              => (int) ($params->slideshow ?? false),
            'slideshowInterval'      => max(1000, min(30000, (int) ($params->slideshow_interval ?? 5000))),
            'slideshowResume'        => (bool) ($params->slideshow_resume ?? true),
            'slideshowResumeTimeout' => max(0, min(60000, (int) ($params->slideshow_resume_timeout ?? 10000))),
            'pagination'             => $params->pagination ?? 'buttons',
            'paginationAsImages'     => (bool) ($params->pagination_as_images ?? true),
            'paginationTextDivider'  => JText::_($params->pagination_text_divider ?? '/'),
            'openEffect'             => $params->effect_open ?? 'zoom',
            'closeEffect'            => $params->effect_close ?? 'zoom',
            'nextEffect'             => $params->effect_next ?? 'slideForward',
            'previousEffect'         => $params->effect_previous ?? 'slideBackward',
        ];

        RL_Document::scriptOptions($options, 'Modals');

        RL_Document::script('modals.script', ['defer' => true, 'type' => 'module']);
        self::loadStyles();

        if (JFactory::getApplication()->input->getInt('ml', 0) && $params->add_redirect)
        {
            self::addRedirectScript();
        }
    }

    public static function removeHeadStuff(&$html)
    {
        // Remove all scripts and styles if data-modals attribute is not found
        if ( ! RL_RegEx::match('data-modals', $html))
        {
            self::removeAllScriptsAndStyles($html);

            return;
        }

        // Otherwise only remove the unused styles
        self::removeUnusedStyles($html);
    }

    public static function setTemplate()
    {
        $params = Params::get();

        JFactory::getApplication()->input->set(
            'tmpl',
            JFactory::getApplication()->input->getWord('tmpl', $params->sub_template)
        );

        RL_Document::style('modals.modal');
        RL_Document::script('modals.modal');
    }

    public static function setUrlAttribute($url, $key)
    {
        if (strpos($url, $key . '=1') !== false)
        {
            return $url;
        }

        return $url
            . (strpos($url, '?') === false ? '?' : '&amp;')
            . $key . '=1';
    }

    private static function addRedirectScript()
    {
        // Add redirect script
        $script =
            ";if( parent.location.href === window.location.href ) {
                loc = window.location.href.replace(/(\?|&)ml=1(&iframe=1(&fullpage=1)?)?(&|$)/, '$1');
                loc = loc.replace(/(\?|&)$/, '');
                if(parent.location.href !== loc) {
                    parent.location.href = loc;
                }
            }";

        if (JFactory::getApplication()->input->getInt('iframe'))
        {
            RL_Document::scriptDeclaration($script);

            return;
        }

        $buffer = RL_Document::getComponentBuffer();
        if ( ! $buffer)
        {
            return;
        }

        $buffer =
            '<script type="text/javascript">' . $script . '</script>'
            . $buffer;

        RL_Document::setComponentBuffer($buffer);
    }

    private static function getThemes()
    {
        if ( ! is_null(self::$all_themes))
        {
            return self::$all_themes;
        }

        $folder = JPATH_SITE . '/media/modals/css';
        $files  = JFolder::files($folder, '^theme-[a-z0-9-_]+\.css$');

        $template = JFactory::getApplication()->getTemplate();
        $folder   = JPATH_SITE . '/media/templates/site/' . $template . '/css/modals';

        if (is_dir($folder))
        {
            $files_template = JFolder::files($folder, '^theme-[a-z0-9-_]+\.css$');
            $files_template = empty($files_template) ? [] : $files_template;
            $files          = array_merge($files, $files_template);
        }

        $files = array_unique($files);

        $themes = [];
        foreach ($files as $file)
        {
            $file_name = JFile::stripExt($file);
            $file_name = substr($file_name, 6);

            $themes[] = $file_name;
        }

        self::$all_themes = array_unique($themes);

        return self::$all_themes;
    }

    private static function getUsedThemes($html)
    {
        $params = Params::get();

        $themes = [$params->theme];

        if ( ! RL_RegEx::matchAll('data-modals-theme="([^"]*)"', $html, $matches, null, PREG_PATTERN_ORDER))
        {
            return $themes;
        }

        $themes = array_merge($themes, $matches[1]);
        $themes = array_unique($themes);

        return $themes;
    }

    private static function loadStyles()
    {
        $params = Params::get();

        if ( ! $params->load_stylesheet)
        {
            RL_Document::style('modals.theme-custom');

            return;
        }

        RL_Document::style('modals.style');

        $themes = self::getThemes();
        foreach ($themes as $theme)
        {
            RL_Document::style('modals.theme-' . $theme);
        }
    }

    private static function removeAllScriptsAndStyles(&$html)
    {
        // Prevent the modals.modal style and script from being removed
        RL_Protect::protectByRegex($html, '<link [^>]*href="[^"]*/(modals/css|css/modals)/modal\..*?>');
        RL_Protect::protectByRegex($html, '<script [^>]*src="[^"]*/(modals/js|js/modals)/modal\..*?>');
        // Prevent the modals.button and modals.popup script from being removed
        RL_Protect::protectByRegex($html, '<script [^>]*src="[^"]*/(modals/js|js/modals)/(button|popup)\..*?>');

        // remove style and script if no items are found
        RL_Document::removeScriptsStyles($html, 'Modals');
        RL_Document::removeScriptsOptions($html, 'Modals');

        RL_Protect::unprotect($html);
    }

    private static function removeUnusedStyles(&$html)
    {
        $all_themes    = self::getThemes();
        $used_themes   = self::getUsedThemes($html);
        $unused_themes = array_diff($all_themes, $used_themes);

        foreach ($unused_themes as $theme)
        {
            RL_Document::removeStyleTag($html, 'modals', 'theme-' . $theme);
        }
    }
}
