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

use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Parameters as RL_Parameters;
use RegularLabs\Library\PluginTag as RL_PluginTag;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;

class Params
{
    protected static $params  = null;
    protected static $regexes = null;

    public static function fillMediaSettings(&$settings)
    {
        $params = self::get();

        $defaults = [
            'images'                     => '',
            'thumbnails'                 => '',
            'first'                      => '',
            'title'                      => null,
            'alt'                        => null,
            'description'                => null,
            'width'                      => 0,
            'height'                     => 0,
            'gallery-include-subfolders' => false,
        ];

        if ($params->resize_images)
        {
            if ( ! empty($settings->width) || ! empty($settings->height))
            {
                $settings->width  = $settings->width ?? 0;
                $settings->height = $settings->height ?? 0;
            }
            else
            {
                $settings->width  = $params->image_resize_type == 'crop' || $params->image_resize_using == 'width' ? $params->image_width : 0;
                $settings->height = $params->image_resize_type == 'crop' || $params->image_resize_using == 'height' ? $params->image_height : 0;
            }
        }

        if ($params->create_thumbnails)
        {
            if ( ! empty($settings->{'thumbnail-width'}) || ! empty($settings->{'thumbnail-height'}))
            {
                $settings->{'thumbnail-width'}  = $settings->{'thumbnail-width'} ?? 0;
                $settings->{'thumbnail-height'} = $settings->{'thumbnail-height'} ?? 0;
            }
            else
            {
                $settings->{'thumbnail-width'}  = $params->thumbnail_resize_type == 'crop' || $params->thumbnail_resize_using == 'width' ? $params->thumbnail_width : 0;
                $settings->{'thumbnail-height'} = $params->thumbnail_resize_type == 'crop' || $params->thumbnail_resize_using == 'height' ? $params->thumbnail_height : 0;
            }
        }

        $settings->class = '';

        foreach ($params as $key => $value)
        {
            $key = RL_String::toDashCase($key);

            if (isset($settings->{$key}))
            {
                continue;
            }

            $settings->{$key} = $value;
        }

        foreach ($defaults as $key => $value)
        {
            if (isset($settings->{$key}))
            {
                continue;
            }

            $settings->{$key} = $value;
        }

        if ( ! empty($settings->thumbnails))
        {
            return;
        }

        $settings->thumbnails = $settings->{'gallery-showall'} ? 'all' : 1;
    }

    public static function get()
    {
        if ( ! is_null(self::$params))
        {
            return self::$params;
        }

        $params = RL_Parameters::getPlugin('modals');

        $params->tag = RL_PluginTag::clean($params->tag);
        $params->tag_content = RL_PluginTag::clean($params->tag_content);

        // array_filter will remove any empty values
        $params->classnames = $params->autoconvert_classnames ? RL_Array::toArray(str_replace(' ', ',', trim($params->classnames))) : [];
        $params->classnames_images = $params->autoconvert_classnames_images ? RL_Array::toArray(str_replace(' ', ',', trim($params->classnames_images))) : [];
        $params->filetypes         = $params->autoconvert_filetypes ? RL_Array::toArray(str_replace([' ', '.'], '', $params->filetypes)) : [];
        $params->urls              = $params->autoconvert_urls ? RL_Array::toArray(str_replace("\r", '', $params->urls), "\n") : [];
        $params->auto_group_id     = uniqid('gallery_');
        $params->mediafiles = RL_Array::toArray(strtolower($params->mediafiles));

        $params->booleans = [
            'auto-titles',
            'fullpage',
            'iframe',
            'inline',
            'open-once',
            'image-lazy-loading',
            'autoplay',
            'close-on-outside-click',
            'gallery-showall',
            'keyboard-navigation',
            'navigation',
            'show-close-button',
            'show-countdown',
            'slideshow',
        ];

        $params->key_aliases = [
            'url'                        => ['href', 'link', 'src'],
            'classname'                  => ['class-name'],
            'open-delay'                 => ['delay'],
            'auto-close'                 => ['autoclose'],
            'close-on-outside-click'     => ['overlay-close', 'overlayclose', 'closeonoutsideclick'],
            'cookie'                     => ['cookie_name', 'cookie_id'],
            'gallery'                    => ['galery'],
            'gallery-filter'             => ['filter'],
            'gallery-include-subfolders' => ['include-subfolders'],
            'gallery-separator'          => ['separator'],
            'gallery-showall'            => ['showall', 'show-all'],
            'image'                      => ['img'],
            'image-lazy-loading'         => ['lazy-loading', 'lazyloading'],
            'keyboard-navigation'        => ['keyboardnavigation'],
            'on-close'                   => ['onclose', 'on-cleanup', 'oncleanup'],
            'on-closed'                  => ['onclosed'],
            'on-load'                    => ['onload'],
            'on-loaded'                  => ['onloaded', 'on-complete', 'oncomplete'],
            'on-open'                    => ['onopen'],
            'on-opened'                  => ['onopened'],
            'show-close-button'          => ['close-button', 'closebutton', 'showclosebutton'],
            'show-countdown'             => ['countdown', 'showcountdown'],
            'thumbnail-add-itemprop'     => ['itemprop', 'add-itemprop'],
            'thumbnail-height'           => ['thumbheight', 'thumbnailheight'],
            'thumbnail-width'            => ['thumbwidth', 'thumbnailwidth'],
            'thumbnails'                 => ['thumbnail', 'thumb', 'thumbs'],
            'open-effect'                => ['effect-open'],
            'close-effect'               => ['effect-close'],
            'next-effect'                => ['effect-next'],
            'prev-effect'                => ['effect-prev'],
        ];

        $params->valid_attribute_keys = [
            'alt',
            'class',
            'href',
            'id',
            'rel',
            'title',
            'itemscope',
        ];

        $params->include_empty_attributes = [
            'alt',
            'data-title',
            'title',
        ];

        $params->valid_data_keys = [
            'data-class',
            'data-title',
            'description',
            'description-position',
            'height',
            'legacy',
            'srcset',
            'theme',
            'title',
            'title-position',
            'width',
            'type',
            'open',
            'open-delay',
            'auto-close',
            'close-on-outside-click',
            'enable-page-scrolling',
            'group',
            'keyboard-navigation',
            'navigation',
            'on-close',
            'on-closed',
            'on-load',
            'on-loaded',
            'on-open',
            'on-opened',
            'pagination',
            'position',
            'show-close-button',
            'show-countdown',
            'slideshow',
        ];

        self::$params = $params;

        return self::$params;
    }

    public static function getRegex($type = 'tag')
    {
        $regexes = self::getRegexes();

        return $regexes->{$type} ?? $regexes->tag;
    }

    public static function getSettings()
    {
        $params = self::get();

        $settings = [];

        foreach ($params as $key => $value)
        {
            $key = str_replace('_', '-', $key);

            $settings[$key] = $value;
        }

        return (object) $settings;
    }

    public static function getTagCharacters()
    {
        $params = self::get();

        if ( ! isset($params->tag_character_start))
        {
            self::setTagCharacters();
        }

        return [$params->tag_character_start, $params->tag_character_end];
    }

    public static function getTagWords()
    {
        $params = self::get();

        return [
            $params->tag,
            $params->tag_content,
        ];
    }

    public static function getTags($only_start_tags = false)
    {
        $params = self::get();

        [$tag_start, $tag_end] = self::getTagCharacters();

        $tags = [
            [
                $tag_start . $params->tag,
                $tag_start . $params->tag_content,
            ],
            [
                $tag_start . '/' . $params->tag . $tag_end,
                $tag_start . '/' . $params->tag_content . $tag_end,
            ],
        ];

        return $only_start_tags ? $tags[0] : $tags;
    }

    public static function setTagCharacters()
    {
        $params = self::get();

        [self::$params->tag_character_start, self::$params->tag_character_end] = explode('.', $params->tag_characters);
    }

    private static function getRegexes()
    {
        if ( ! is_null(self::$regexes))
        {
            return self::$regexes;
        }

        $params = self::get();

        // Tag character start and end
        [$tag_start, $tag_end] = Params::getTagCharacters();

        $pre        = RL_PluginTag::getRegexSurroundingTagsPre();
        $post       = RL_PluginTag::getRegexSurroundingTagsPost();
        $inside_tag = RL_PluginTag::getRegexInsideTag($tag_start, $tag_end);

        $tag_start = RL_RegEx::quote($tag_start);
        $tag_end   = RL_RegEx::quote($tag_end);

        $spaces      = RL_PluginTag::getRegexSpaces();
        $spaces_none = RL_PluginTag::getRegexSpaces('*');

        $a_tag        = RL_PluginTag::getRegexTags('a', false, false);
        $spans_images = RL_PluginTag::getRegexTags(['span', 'i', 'img']);
        $spans = RL_PluginTag::getRegexTags(['span', 'i']);
        $image = RL_PluginTag::getRegexTags('img', false, false, 'class');
        $any_text = '[^<>]*';

        self::$regexes = (object) [];

        self::$regexes->tag =
            '(?<start_pre>' . $pre . ')'
            . $tag_start . $params->tag . $spaces . '(?<data>' . $inside_tag . ')' . $tag_end
            . '(?<start_post>' . $post . ')'

            . '(?<pre>' . $pre . ')'
            . '(?<text>.*?)'
            . '(?<post>' . $post . ')'

            . '(?<end_pre>' . $pre . ')'
            . $tag_start . '\/' . $params->tag . $tag_end
            . '(?<end_post>' . $post . ')';

        self::$regexes->inlink =
            '(?<link_start>' . $a_tag . ')'
            . '(?<pre>' . $any_text . ')'

            . '(?<image_pre>(?:' . $spans_images . $any_text . '){0,6})'

            . $tag_start . $params->tag . $spaces_none . '(?<data>' . $inside_tag . ')' . $tag_end

            . '(?<text>.*?)'

            . $tag_start . '\/' . $params->tag . $tag_end

            . '(?<image_post>(?:' . $any_text . $spans_images . '){0,6})'

            . '(?<post>' . $any_text . ')'
            . '(?<link_end></a>)';

        self::$regexes->link = $a_tag;

        self::$regexes->image =
            '(?<link_start>(?:' . $a_tag . $any_text . '(?:' . $spans . $any_text . '){0,6})?)'
            . '(?<image>' . $image . ')'
            . '(?<link_end>(?:' . $any_text . '(?:' . $spans . $any_text . '){0,6}<\/a>)?)';

        self::$regexes->content =
            '(?<start_pre>' . $pre . ')'
            . $tag_start . $params->tag_content . '(?:\=|' . $spaces . ')+' . '(?<id>' . $inside_tag . ')' . $tag_end
            . '(?<start_post>' . $post . ')'

            . '(?<content>.*?)'

            . '(?<end_pre>' . $pre . ')'
            . $tag_start . '\/' . $params->tag_content . $tag_end
            . '(?<end_post>' . $post . ')';

        return self::$regexes;
    }
}
