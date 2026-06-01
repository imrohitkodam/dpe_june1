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

use Joomla\CMS\Http\HttpFactory as JHttpFactory;
use Joomla\Filesystem\File as JFile;
use Joomla\Registry\Registry as JRegistry;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Cache as RL_Cache;
use RegularLabs\Library\File as RL_File;
use RegularLabs\Library\HtmlTag as RL_HtmlTag;
use RegularLabs\Library\Image as RL_Image;
use RegularLabs\Library\ObjectHelper as RL_Object;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;
use RuntimeException;

class Image
{
    public static function get($attributes)
    {
        if (empty($attributes->src))
        {
            return null;
        }

        self::setImageAttributes($attributes);

        $image = (new RL_Image)
            ->setFile($attributes->src)
            ->setEnableResize($attributes->{'resize-images'} ?? true)
            ->setResizeFolder($attributes->{'resize-folder'} ?? 'resized')
            ->setResizeQuality($attributes->{'resize-quality'} ?? 'medium')
            ->setResizeMaxAge($attributes->{'resize-max-age'} ?? 0)
            ->setUseRetina($attributes->{'resize-use-retina'} ?? true)
            ->setRetinaPixelDensity($settings->{'resize-retina-pixel-density'} ?? 1.5)
            ->setDimensions($attributes->width ?? 0, $attributes->height ?? 0)
            ->setLazyLoading(($attributes->loading ?? '') == 'lazy');

        if (isset($attributes->class))
        {
            $image->setTagAttribute('class', $attributes->class);
        }

        if (isset($attributes->style))
        {
            $image->setTagAttribute('style', $attributes->style);
        }

        if (isset($attributes->title))
        {
            $image->setTitle($attributes->title);
        }

        if (isset($attributes->alt))
        {
            $image->setAlt($attributes->alt);
        }

        if ( ! empty($attributes->description))
        {
            $image->setDescription($attributes->description);
        }

        if ($attributes->{'resize-force-resize'} ?? false)
        {
            $image->forceOverwriteResized();
        }

        return $image;
    }

    public static function getAllImagesFromText($text)
    {
        $cache = new RL_Cache;

        if ($cache->exists())
        {
            return $cache->get();
        }

        RL_RegEx::matchAll(
            '<img\s[^>]*src=([\'"]).*?\1[^>]*>',
            $text,
            $matches
        );

        $images = [];

        foreach ($matches as $i => $match)
        {
            $images[$i + 1] = (object) RL_HtmlTag::getAttributes($match[0]);
        }

        return $cache->set($images);
    }

    public static function getDownloadName($file, $folder = 'images/remote')
    {
        $extension = JFile::getExt($file);
        $file_name = JFile::stripExt($file);

        $file_name = RL_RegEx::replace('^.*\/\/(?:www\.)?', '', $file_name);
        $file_name = RL_RegEx::replace('[^a-zA-Z0-9_\-\/]', '-', $file_name);

        return JPATH_SITE . '/' . trim($folder, '/') . '/' . $file_name . '.' . $extension;
    }

    public static function getImageCountFromText($text): int
    {
        if (empty($text))
        {
            return 0;
        }

        return count(self::getAllImagesFromText($text));
    }

    public static function getImageDataFromText($text, $id)
    {
        if (empty($text))
        {
            return (object) [];
        }

        $images = self::getAllImagesFromText($text);

        if (empty($images))
        {
            return (object) [];
        }

        if ($id === 'random')
        {
            $id = rand(1, count($images));
        }

        return isset($images[$id]) ? RL_Object::clone($images[$id]) : (object) [];
    }

    public static function getOutputByKey(
        $key,
        $image_data,
        $attributes = null,
        $type = 'content',
        $default_title = ''
    )
    {
        if (empty($image_data->src))
        {
            return '';
        }

        $attributes ??= (object) [];

        if (isset($attributes->suffix))
        {
            $image_data->src = RL_File::addSuffix($image_data->src, $attributes->suffix);
            unset($attributes->suffix);
        }

        if (in_array($key, ['resize', 'resized', 'thumb', 'thumbnail'], true))
        {
            $key = 'tag';

            $attributes->resize = true;
        }

        if ($key === 'tag')
        {
            $key = $image_data->key ?? $key;
        }

        self::setDimensions($image_data, $attributes);

        if (isset($attributes->width) || isset($attributes->height))
        {
            unset($image_data->width);
            unset($image_data->height);
        }

        $image_data = RL_Object::merge($image_data, $attributes);
        self::setAltAndTitle($type, $image_data, $default_title);

        $image = self::get($image_data);

        if ( ! $image)
        {
            return '';
        }

        switch ($key)
        {
            case 'tag':
                return $image->renderTag();

            case 'layout':
                $layout_id = Layout::getId($attributes->layout ?? '', 'joomla.content.' . $image_data->type);
                $type      = $image_data->type === 'intro_image' ? 'intro' : 'fulltext';

                $displayData = (object) [
                    'params' => new JRegistry,
                    'image'  => (object) [
                        'path'    => $image->getPath(),
                        'float'   => $image_data->float ?? '',
                        'alt'     => $image_data->alt ?? '',
                        'caption' => $image_data->caption ?? '',
                    ],
                    'images' => json_encode((object) [
                        'image_' . $type              => $image->getPath(),
                        'float_' . $type              => $image_data->float ?? '',
                        'image_' . $type . '_alt'     => $image_data->alt ?? '',
                        'image_' . $type . '_caption' => $image_data->caption ?? '',
                    ]),
                ];

                return Layout::render($layout_id, $displayData);

            case 'url':
            case 'src':
                return $image->getPath();

            case 'width':
                return $image->getWidth();

            case 'height':
                return $image->getHeight();

            default:
                return $image_data->{$key} ?? '';
        }
    }

    public static function setAltAndTitle($type, &$attributes, $default_title = '')
    {
        $attributes = empty($attributes) ? (object) [] : $attributes;

        self::crossFillAltAndTitle($attributes);

        $params = Params::get();

        if ( ! empty($attributes->alt) && ! empty($attributes->title))
        {
            return;
        }

        $title_type = $attributes->auto_titles ?? '';

        if ( ! $title_type && isset($params->{'image_titles_' . $type}))
        {
            $title_type = $params->{'image_titles_' . $type};
        }

        switch ($title_type)
        {
            case 'file':
                if ( ! isset($attributes->src))
                {
                    break;
                }

                $default_title = self::getTitleFromFile($attributes->src, $attributes);
                break;

            case 'none':
                $default_title = '';
                break;

            default:
                break;
        }

        $attributes->alt   ??= $default_title;
        $attributes->title ??= $default_title;
    }

    private static function crossFillAltAndTitle(&$attributes)
    {
        $attributes = empty($attributes) ? (object) [] : $attributes;

        $params = Params::get();

        if ( ! $params->image_titles_cross_fill)
        {
            return;
        }

        if (empty($attributes->alt) && ! empty($attributes->title))
        {
            $attributes->alt = $attributes->title;
        }

        if (empty($attributes->title) && ! empty($attributes->alt))
        {
            $attributes->title = $attributes->alt;
        }
    }

    private static function getCleanFileName($url)
    {
        $title = RL_File::getFileName($url);

        // Remove trailing dimensions
        $title = RL_RegEx::replace('[_-][0-9]+x[0-9]+?$', '', $title);

        return $title;
    }

    private static function getCleanTitle($url)
    {
        $title = self::getCleanFileName($url);

        // Replace dashes with spaces
        return str_replace(['-', '_'], ' ', $title);
    }

    private static function getTitleFromFile($url, $attributes)
    {
        $params = Params::get();

        $title = self::getCleanTitle($url);

        $case_type = isset($attributes->auto_titles_case)
            ? $attributes->auto_titles_case
            : $params->image_titles_case;

        switch ($case_type)
        {
            case 'lowercase':
                return RL_String::strtolower($title);

            case 'uppercase':
                return RL_String::strtoupper($title);

            case 'uppercasefirst':
                return RL_String::strtoupper(RL_String::substr($title, 0, 1))
                    . RL_String::strtolower(RL_String::substr($title, 1));

            case 'titlecase':
                return function_exists('mb_convert_case')
                    ? mb_convert_case(RL_String::strtolower($title), MB_CASE_TITLE)
                    : ucwords(strtolower($title));

            case 'titlecase_smart':
                $title           = function_exists('mb_convert_case')
                    ? mb_convert_case(RL_String::strtolower($title), MB_CASE_TITLE)
                    : ucwords(strtolower($title));
                $lowercase_words = explode(',', ' ' . str_replace(',', ' , ', RL_String::strtolower($params->image_titles_lowercase_words)) . ' ');

                return str_ireplace($lowercase_words, $lowercase_words, $title);

            default:
                return $title;
        }
    }

    private static function isEnabledResizeFiletype($image_url)
    {
        $file_types = RL_Array::toArray(Params::get()->resize_filetypes);

        $extension = RL_File::getExtension($image_url);
        $extension = str_replace(
            ['jpeg'],
            ['jpg'],
            strtolower($extension)
        );

        return in_array($extension, $file_types, true);
    }

    private static function setDimensions($image_data, &$attributes)
    {
        $params = Params::get();

        // resize attribute is not found and resize_images setting is off
        if (empty($attributes->resize) && ! $params->resize_images)
        {
            if (empty($attributes->width) && empty($attributes->height))
            {
                $attributes->width  = $image_data->width ?? null;
                $attributes->height = $image_data->height ?? null;
            }

            $attributes->resize = false;

            return;
        }

        //  width or height is found in the attributes, so force resizing
        if ( ! empty($attributes->width) || ! empty($attributes->height))
        {
            $attributes->resize = true;

            return;
        }

        $attributes->width  ??= $image_data->width ?? null;
        $attributes->height ??= $image_data->height ?? null;

        //   width or height is found in the $image_data either, so ignore default settings
        //        if ( ! empty($attributes->width) || ! empty($attributes->height))
        //        {
        //            return;
        //        }

        // resize attribute is not found
        if (empty($attributes->resize) && $params->resize_images !== '1')
        {
            return;
        }

        // image filetype is not supported or enabled
        if ( ! self::isEnabledResizeFiletype($image_data->src))
        {
            return;
        }

        $resize_type        = $attributes->resize_type ?? $params->resize_type;
        $attributes->resize = true;

        switch ($resize_type)
        {
            case 'crop':
                $attributes->width  = $params->resize_width;
                $attributes->height = $params->resize_height;

                return;

            case 'scale':
            default:
                $resize_using = $attributes->resize_using ?? $params->resize_using;

                $attributes->width  = $resize_using === 'width' ? $params->resize_width : null;
                $attributes->height = $resize_using === 'height' ? $params->resize_height : null;

                return;
        }
    }

    private static function setImageAttributes(&$attributes)
    {
        $params            = Params::get();
        $attributes->class = trim(($attributes->class ?? '') . ' ' . ($attributes->float ?? ''));

        $attributes->{'resize-folder'}               ??= $attributes->resize_folder ?? $params->resize_folder;
        $attributes->{'resize-quality'}              ??= $attributes->resize_quality ?? $params->resize_quality;
        $attributes->{'resize-force-resize'}         ??= $attributes->resize_force_resize ?? $params->resize_force_resize;
        $attributes->{'resize-max-age'}              ??= $attributes->resize_max_age ?? $params->resize_max_age;
        $attributes->{'resize-use-retina'}           ??= $attributes->resize_use_retina ?? $params->resize_use_retina;
        $attributes->{'resize-retina-pixel-density'} ??= $attributes->resize_retina_pixel_density ?? $params->resize_retina_pixel_density;

        if (RL_File::isInternal($attributes->src))
        {
            return;
        }

        if ( ! $params->download_external_images)
        {
            return;
        }

        $destination = self::getDownloadName($attributes->src, $params->download_external_images_folder);

        if (JFile::exists($destination))
        {
            $attributes->src = $destination;

            return;
        }

        try
        {
            $response = JHttpFactory::getHttp()->get($attributes->src);
        }
        catch (RuntimeException $exception)
        {
            return;
        }

        if ( ! $response->code === 200)
        {
            return;
        }

        JFile::write($destination, $response->body);

        $attributes->src = $destination;
    }
}
