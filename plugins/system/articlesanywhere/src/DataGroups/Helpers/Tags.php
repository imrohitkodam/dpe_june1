<?php
/**
 * @package         Articles Anywhere
 * @version         17.2.10PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers;

defined('_JEXEC') or die;



use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Layout\LayoutHelper as JLayout;
use Joomla\CMS\Router\Route as JRoute;
use Joomla\Component\Tags\Site\Helper\RouteHelper as JTagsRouteHelper;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class Tags
{
    public static function getOutput(
        $tags,
        $separator = ', ',
        $last_separator = null,
        $add_links = true
    )
    {
        $access_levels = Params::getAuthorisedViewLevels();
        $array         = [];

        $add_links ??= true;

        foreach ($tags as $tag)
        {
            if ( ! in_array($tag->access, $access_levels))
            {
                continue;
            }

            $text = htmlspecialchars($tag->title, ENT_COMPAT, 'UTF-8');

            if ( ! $add_links)
            {
                $array[] = $text;
                continue;
            }

            $attribs = [
                'class' => $tag->tag_link_class ?? '',
            ];

            $array[] = JHtml::_(
                'link',
                self::getTagUrl($tag),
                $text,
                $attribs
            );
        }

        return RL_Array::implode(
            $array,
            $separator,
            $last_separator
        );
    }

    public static function getOutputViaLayout($tags, $layout = null)
    {
        $layout_id = Layout::getId($layout, 'joomla.content.tags');

        return JLayout::render($layout_id, $tags);
    }

    public static function getTagAliases($tags)
    {
        if (empty($tags))
        {
            return [];
        }

        $access_levels = Params::getAuthorisedViewLevels();

        $titles = [];

        foreach ($tags as $tag)
        {
            if ( ! in_array($tag->access, $access_levels))
            {
                continue;
            }

            $titles[] = $tag->alias;
        }

        return $titles;
    }

    public static function getTagTitles($tags)
    {
        if (empty($tags))
        {
            return [];
        }

        $access_levels = Params::getAuthorisedViewLevels();

        $titles = [];

        foreach ($tags as $tag)
        {
            if ( ! in_array($tag->access, $access_levels))
            {
                continue;
            }

            $titles[] = htmlspecialchars($tag->title, ENT_COMPAT, 'UTF-8');
        }

        return $titles;
    }

    private static function getTagUrl($tag)
    {
        return JRoute::link('site', JTagsRouteHelper::getTagRoute($tag->tag_id . ':' . $tag->alias));
    }
}
