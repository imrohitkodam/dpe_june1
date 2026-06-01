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


use Joomla\CMS\Filesystem\Folder as JFolder;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\File as RL_File;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;

class Gallery
{
    public static function buildGallery(object $settings, string $content)
    {
        $folder = RL_File::trimFolder($settings->gallery);

        if ( ! JFolder::exists(JPATH_SITE . '/' . $folder))
        {
            return '<a href="#">';
        }

        unset($settings->gallery);
        unset($settings->inline);

        $settings->group = uniqid('gallery_') . rand(1000, 9999);

        Params::fillMediaSettings($settings);

        $images = self::getGalleryImageList($folder, $settings);

        $settings->first_id = self::getFirstID($settings->first, $images);

        $thumbnail_ids = [$settings->first_id];
        if ($content === '')
        {
            $thumbnail_ids = ! empty($settings->first)
                ? [$settings->first_id]
                : self::getThumbnailsIds($settings->thumbnails, $images);
        }

        $separator = str_replace('{none}', '', $settings->{'gallery-separator'});

        $html           = [];
        $last_separator = null;

        foreach ($images as $id => $image)
        {
            $image_settings = clone $settings;
            $show           = in_array($id, $thumbnail_ids);

            if ( ! $show)
            {
                // Add hidden class to images that don't show the thumbnail
                $image_settings->class = ! empty($image_settings->class) ? $image_settings->class . ' hidden' : 'hidden';
                $image_settings->id    = '';
            }

            $html[] = self::getGalleryImageLink($image, $image_settings, $content, $id, $show);

            if ($show)
            {
                $html[]         = $separator;
                $last_separator = count($html) - 1;
            }
        }

        if ($last_separator !== null)
        {
            unset($html[$last_separator]);
        }

        return implode('', $html);
    }

    private static function filterImageList($show, &$images)
    {
        // Default to all images in natural order
        if (empty($images) || empty($show) || $show == 'all')
        {
            return;
        }

        // Randomize image order
        if ($show == 'random')
        {
            shuffle($images);

            return;
        }

        // Convert single number to a range starting at 1
        if (is_numeric($show))
        {
            $show = '1-' . $show;
        }

        // Get a range of images numbers
        if (RL_RegEx::match('^([0-9]+)-([0-9]+)$', $show, $show_range))
        {
            $range = [];

            for ($i = $show_range[1]; $i <= $show_range[2]; $i++)
            {
                $range[] = $images[$i - 1];
            }

            $images = $range;

            return;
        }

        // Find images from a list of names or numbers
        // Also works for single values
        $show = RL_Array::toArray($show);

        $range = [];

        foreach ($show as $name)
        {
            foreach ($images as $id => $image)
            {
                /* @var Image $image */
                if (in_array(strtolower($name), [
                    $id + 1,
                    $image->getFileName(),
                    $image->getFileStem(),
                ]))
                {
                    $range[] = $image;
                    unset($images[$id]);
                }
            }
        }

        $images = $range;
    }

    private static function getFirstID($first, $images)
    {
        // Default to first image if stuff is empty
        if (empty($images) || empty($first))
        {
            return 0;
        }

        // Look up image by number
        if (is_numeric($first))
        {
            return isset($images[$first - 1]) ? $first - 1 : 0;
        }

        // Find a random image (number)
        if ($first == 'random')
        {
            return array_rand($images, 1);
        }

        // Find image by name or number
        foreach ($images as $id => $image)
        {
            /* @var Image $image */
            if ($first == $image->getFileName() || $first == $image->getFileStem())
            {
                return $id;
            }
        }

        // Default to first image
        return 0;
    }

    private static function getGalleryImageLink(Image $image, $settings, $content, &$id, $show = true)
    {
        $is_first = $id == $settings->first_id;

        if ( ! $is_first)
        {
            unset($settings->open);
        }

        $settings->href        = $image->getOutputFile();
        $settings->title       = $image->getTitle(true);
        $settings->alt         = $image->getAlt(true);
        $settings->description = $image->getDescription();

        if ($settings->{'resize-use-retina'} ?? true)
        {
            $settings->srcset = $image->getSrcSet();
        }

        if ($content == '')
        {
            $settings->type = 'image';
        }

        $settings->{'thumbnail-add-itemprop'} = $settings->{'thumbnail-add-itemprop'} ?? false;

        if ( ! $show || ($settings->{'thumbnail-add-itemprop'} === 'first' && ! $is_first))
        {
            $settings->{'thumbnail-add-itemprop'} = false;
        }

        if ($settings->{'thumbnail-add-itemprop'} ?? false)
        {
            $settings->itemscope = '<empty>';
        }

        $link = Link::build($settings);

        if ($show)
        {
            $link = str_replace(' hidden', '', $link);
        }

        if ($show && $content == '')
        {
            $thumbnail = new Thumbnail($image, $settings);

            return $link . $thumbnail->render() . '</a>';
        }

        if ( ! $is_first)
        {
            return $link . '</a>';
        }

        $link = str_replace(' hidden', '', $link);

        return $link . $content . '</a>';
    }

    private static function getGalleryImageList($folder, &$settings)
    {
        $folder = RL_File::trimFolder($folder);
        $filter = $settings->{'gallery-filter'};

        if (RL_RegEx::match('(.*?\()([^\)]*)(\).*?)', $filter, $match))
        {
            $filter = $match[1] . $match[2] . '|' . strtoupper($match[2]) . $match[3];
        }

        $files = JFolder::files(JPATH_SITE . '/' . $folder, $filter, ! empty($settings->{'gallery-include-subfolders'}), true);

        $images = [];
        foreach ($files as $file)
        {
            $image = new Image($file, $settings);

            if ($image->isResized())
            {
                continue;
            }

            $images[] = $image;
        }

        self::filterImageList($settings->images, $images);

        return $images;
    }

    private static function getThumbnailsIds($show, $images)
    {
        // Default to first image if stuff is empty
        if (empty($images) || empty($show) || $show == 'first')
        {
            return [0];
        }

        // Add all images to the list of thumbnails
        if ($show == 'all')
        {
            return array_keys($images);
        }

        // Find a random image (number)
        if ($show == 'random')
        {
            return [array_rand($images, 1)];
        }

        // Convert single number to a range starting at 1
        if (is_numeric($show))
        {
            $show = '1-' . $show;
        }

        // Get a range of images numbers
        if (RL_RegEx::match('^([0-9]+)-([0-9]+)$', $show, $show_range))
        {
            $range = [];

            for ($i = $show_range[1]; $i <= $show_range[2]; $i++)
            {
                $range[] = $i - 1;
            }

            // Default to first image if nothing is found
            return ($range ?? null) ?: [0];
        }

        // Find images from a list of names or numbers
        // Also works for single values
        $show = RL_Array::toArray($show);

        $range = [];

        foreach ($show as $name)
        {
            $name = RL_String::normalize($name, true);

            foreach ($images as $id => $image)
            {
                /* @var Image $image */

                $filename = $image->getFileName();
                $basename = $image->getFileStem();

                if (in_array($name, [$id + 1, $filename, $basename,]))
                {
                    $range[] = $id;
                }
            }
        }

        // Default to first image if nothing is found
        return ($range ?? null) ?: [0];
    }
}
