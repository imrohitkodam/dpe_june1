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

use ContentHelperRoute;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\File as RL_File;
use RegularLabs\Library\Html as RL_Html;
use RegularLabs\Library\ObjectHelper as RL_Object;
use RegularLabs\Library\PluginTag as RL_PluginTag;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;

class Link
{
    public static function build($settings, $content = '')
    {
        $params = Params::get();

        if (isset($settings->theme) && $settings->theme == 'classic'
            || empty($settings->theme) && $params->theme == 'classic'
        )
        {
            $settings->legacy = true;
        }

        if (isset($settings->gallery) && strpos($settings->gallery, '/') !== false)
        {
            return Gallery::buildGallery($settings, $content);
        }

        if (isset($settings->image))
        {
            Params::fillMediaSettings($settings);

            $image = new Image($settings->image, $settings);

            $settings->image       = 'true';
            $settings->title       = $image->getTitle(true);
            $settings->alt         = $image->getAlt(true);
            $settings->description = $image->getDescription();
            $settings->href        = $image->getOutputFile();

            if ($settings->{'resize-use-retina'} ?? true)
            {
                $settings->srcset = $image->getSrcSet();
            }

            if ( ! $content)
            {
                $thumbnail = new Thumbnail($image, $settings);

                $content        = $thumbnail->render();
                $settings->type = 'image';

                if ($settings->{'thumbnail-add-itemprop'} ?? false)
                {
                    $settings->itemscope = '<empty>';
                }
            }
        }

        self::setVideoUrl($settings);

        if (empty($settings->href))
        {
            return '';
        }

        $isexternal = RL_File::isExternal($settings->href);
        $ismedia    = isset($settings->image) || RL_File::isMedia($settings->href, $params->mediafiles);
        $isvideo    = ! empty($settings->video) || File::isVideo($settings->href, $settings);
        $fullpage   = (empty($settings->fullpage) || $isexternal) ? false : (bool) $settings->fullpage;
        $isinline   = ($settings->href && $settings->href[0] == '#') || $ismedia;

        $settings->class          = Helper::removeClassname($settings->class ?? '', 'modal');
        $settings->{'data-class'} = $settings->classname ?? '';

        if (empty($settings->group) && $params->auto_group && RL_RegEx::match($params->auto_group_filter, $settings->href, $match, ''))
        {
            $settings->group = $params->auto_group_id;
        }

        if ($ismedia && ! isset($settings->title))
        {
            $auto_titles = $settings->{'auto-titles'} ?? $params->auto_titles;
            $title_case  = $settings->{'title_case'} ?? $params->title_case;
            if ($auto_titles)
            {
                $settings->title = File::getTitle($settings->href, $title_case);
            }
        }

        if ($settings->href && $settings->href[0] != '#' && ! $isexternal && ! $ismedia && ! $isvideo)
        {
            $settings->href = Document::addUrlAttributes($settings->href, $fullpage, ! empty($settings->print));
        }

        // Set open value based on sessions with openMin / openMax
        Data::setDataOpen($settings);

        
        if ( ! empty($settings->autoclose))
        {
            $autoclose           = (int) $settings->autoclose;
            $settings->autoclose = $autoclose > 500 ? $autoclose : 5000;

            $settings->showcountdown = $settings->showcountdown ?? $params->showcountdown;
            $settings->showcountdown = $settings->showcountdown ?: null;
        }

        if ( ! empty($settings->autoclose) && ! empty($settings->showcountdown))
        {
            $settings->{'showcountdown'} = true;
        }

        if ( ! empty($settings->pagination))
        {
            $settings->pagination = $settings->pagination === true
                ? $params->pagination
                : ($settings->pagination ?: 'none');
        }

        if ( ! empty($settings->theme))
        {
            $settings->theme = RL_String::toDashCase($settings->theme, true);
        }

        // Add aria label for empty links for accessibility
        if (empty($content))
        {
            $label = isset($settings->title)
                ? self::cleanTitle($settings->title)
                : '';

            $settings->{'aria-label'} = $label ?: 'Popup link';
        }

        if (empty($settings->width))
        {
            $settings->width = $isinline ? $params->width_content : $params->width_urls;
        }

        if (empty($settings->height))
        {
            $settings->height = $isinline ? $params->height_content : $params->height_urls;
        }

        return
            '<a '
            . Data::flattenMixedAttributeList($settings)
            . '>'
            . $content;
    }

    public static function get($string, $link = '', $content = '')
    {
        [$settings, $extra_html] = self::getData($string, $link);

        $link = self::build($settings, $content);
        $link .= $link ? '</a>' : '';

        return [$link, $extra_html];
    }

    public static function getAttributeList($string)
    {
        $attributes = (object) [];

        if ( ! $string)
        {
            return $attributes;
        }

        $attribute_string = RL_RegEx::replace('^<[a-z]+ (.*)>', '\1', trim($string));

        return RL_PluginTag::getAttributesFromString($attribute_string);
    }

    public static function getData($string, $link = '')
    {
        $params = Params::get();

        $link_settings = self::getAttributeList(trim($link));

        // Get the values from the tag
        $settings = RL_PluginTag::getAttributesFromString(
            $string,
            'url',
            $params->booleans,
            'dash'
        );

        $settings = RL_Object::replaceKeys($settings, $params->key_aliases);

        $settings->href  = $settings->href ?? $link_settings->href ?? '';
        $settings->class = $settings->class ?? $link_settings->class ?? '';

        foreach ($link_settings as $key => $value)
        {
            if ($key == 'class')
            {
                $settings->class .= ' ' . $value;
                continue;
            }

            if ( ! isset($settings->{$key}))
            {
                $settings->{$key} = $value;
            }
        }

        if ( ! empty($settings->url))
        {
            $settings->href = self::cleanUrl($settings->url);
        }

        if ( ! empty($settings->target))
        {
            $settings->target = $settings->target;
        }

        $extra_html = '';

        // Handle the different tag attributes
        switch (true)
        {
            case ( ! empty($settings->article)):
                $id = $settings->article;

                $db    = JFactory::getDbo();
                $query = $db->getQuery(true)
                    ->select('a.id, a.catid')
                    ->from('#__content AS a');
                $where = 'a.title = ' . $db->quote(RL_String::html_entity_decoder($id));
                $where .= ' OR a.alias = ' . $db->quote(RL_String::html_entity_decoder($id));
                if (is_numeric($id))
                {
                    $where .= ' OR a.id = ' . (int) $id;
                }
                $query->where('(' . $where . ')');
                $db->setQuery($query);
                $article = $db->loadObject();

                if ( ! $article)
                {
                    $settings->href = '#';
                    break;
                }

                if ( ! class_exists('ContentHelperRoute'))
                {
                    require_once JPATH_SITE . '/components/com_content/helpers/route.php';
                }

                $settings->href = ContentHelperRoute::getArticleRoute($article->id, $article->catid);

                // Replace current active menu id with the default menu id
                $language     = JFactory::getLanguage()->getTag();
                $default_menu = JFactory::getApplication()->getMenu('site')->getDefault($language);
                $active_menu  = JFactory::getApplication()->getMenu('site')->getActive();

                if (isset($active_menu->id))
                {
                    $settings->href = RL_RegEx::replace('&Itemid=' . $active_menu->id . '$', '&Itemid=' . $default_menu->id, $settings->href, '');
                }

                break;

            case ( ! empty($settings->html)):
                [$extra_html, $id] = Helper::createInlineContentBlock(
                    RL_Html::convertWysiwygToPlainText($settings->html)
                );
                $settings->href = '#' . $id;
                break;

            case ( ! empty($settings->text)):
                [$extra_html, $id] = Helper::createInlineContentBlock(
                    $settings->text
                );
                $settings->href = '#' . $id;
                break;

            case ( ! empty($settings->content)):
                $content_id     = trim(str_replace(['"', "'", '#'], '', $settings->content));
                $settings->href = '#' . $content_id;
                break;

            case ( ! empty($settings->gallery)):
                $settings->href = '#';
                break;

            default:
                break;
        }

        if ( ! empty($settings->title))
        {
            $settings->title = self::translateString($settings->title);
            $settings->title = RL_String::removeHtml($settings->title);
        }

        if ( ! empty($settings->description))
        {
            $settings->description = self::translateString($settings->description);
        }

        return [$settings, $extra_html];
    }

    private static function addUrlParameter($url, $key, $value = '')
    {
        if (empty($key))
        {
            return $url;
        }

        $key = ltrim($key, '?&');

        if (RL_RegEx::match('[\?&]' . $key . '=', $url))
        {
            return $url;
        }

        $query = $key;

        if ($value)
        {
            $query .= '=' . $value;
        }

        return $url . (strpos($url, '?') === false ? '?' : '&') . $query;
    }

    private static function cleanTitle($string)
    {
        $string = str_replace('<div class="modals_description">', ' - ', $string);

        return RL_String::removeHtml($string);
    }

    private static function cleanUrl($url)
    {
        return RL_RegEx::replace('<a[^>]*>(.*?)</a>', '\1', $url);
    }

    private static function fixUrlVimeo($url)
    {
        $regex = '(?:^vimeo=|vimeo\.com/(?:video/)?)(?<id>[0-9]+)(?<query>.*)$';

        if ( ! RL_RegEx::match($regex, trim($url), $match))
        {
            return $url;
        }

        $url = 'https://player.vimeo.com/video/' . $match['id'];

        $url = self::addUrlParameter($url, $match['query']);

        return $url;
    }

    private static function fixUrlYoutube($url)
    {
        $regex = '(?:^youtube=|youtu\.be/?|youtube\.com/embed/?|youtube\.com\/watch\?v=)(?<id>[^/&\?]+)(?:\?|&amp;|&)?(?<query>.*)$';

        if ( ! RL_RegEx::match($regex, trim($url), $match))
        {
            return $url;
        }

        $url = 'https://www.youtube.com/embed/' . $match['id'];

        $url = self::addUrlParameter($url, $match['query']);
        $url = self::addUrlParameter($url, 'wmode', 'transparent');

        return $url;
    }

    private static function fixVideoUrl($url, &$setting)
    {
        switch (true)
        {
            case(
                strpos($url, 'youtu.be') !== false
                || strpos($url, 'youtube.com') !== false
                || strpos($url, 'youtube=') !== false
            ) :
                $setting->video = 'true';

                return self::fixUrlYoutube($url);

            case(
                strpos($url, 'vimeo.com') !== false
                || strpos($url, 'vimeo=') !== false
            ) :
                $setting->video = 'true';

                return self::fixUrlVimeo($url);

            default:
                return $url;
        }
    }

    private static function setVideoUrl(&$settings)
    {
        if (isset($settings->youtube))
        {
            $settings->href = self::fixUrlYoutube('youtube=' . $settings->youtube);

            if ( ! empty($settings->autoplay))
            {
                $settings->href = self::addUrlParameter($settings->href, 'autoplay', '1');
            }

            $settings->video = 'true';

            return;
        }

        if (isset($settings->vimeo))
        {
            $settings->href = self::fixUrlVimeo('vimeo=' . $settings->vimeo);

            if ( ! empty($settings->autoplay))
            {
                $settings->href = self::addUrlParameter($settings->href, 'autoplay', '1');
            }

            $settings->video = 'true';

            return;
        }

        $settings->href = self::fixVideoUrl($settings->href, $settings);
    }

    private static function translateString($string = '')
    {
        if (empty($string) || ! RL_RegEx::match('^[A-Z][A-Z0-9_]+$', $string))
        {
            return $string;
        }

        return JText::_($string);
    }
}
