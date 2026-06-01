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
 * FeedImage.
 *
 * @since       1.0
 */
class FeedImage
{
    public $src;

    public $original_src;

    public $title;

    public $alt;

    public $class;

    public $style;

    public $align;

    public $border;

    public $width;

    public $height;

    /**
     * generateTag.
     *
     * @return string
     */
    public function generateTag()
    {
        $tag = [];
        $tag[] = '<img src="';
        $tag[] = $this->src;
        $tag[] = '"';

        if ($this->title) {
            $tag[] = ' title="';
            $tag[] = $this->title.'"';
        }

        if ($this->alt) {
            $tag[] = ' alt="';
            $tag[] = $this->alt.'"';
        }

        if ($this->class) {
            $tag[] = ' class="';
            $tag[] = $this->class.'"';
        }

        if ($this->style) {
            $tag[] = ' style="';
            $tag[] = $this->style.'"';
        }

        if ($this->align) {
            $tag[] = ' align="';
            $tag[] = $this->align.'"';
        }

        if ($this->border) {
            $tag[] = ' border="';
            $tag[] = $this->border.'"';
        }

        if ($this->width) {
            $tag[] = ' width="';
            $tag[] = $this->width.'"';
        }

        if ($this->height) {
            $tag[] = ' height="';
            $tag[] = $this->height.'"';
        }

        $tag[] = '/>';

        return implode('', $tag);
    }

    /**
     * download.
     *
     * @param object $params Params
     *
     * @return bool
     */
    public function download($params)
    {
        $logger = AutotweetLogger::getInstance();
        $rel_src = $params->get('rel_src');
        $img_folder = $params->get('img_folder');
        $sub_folder = $params->get('sub_folder');
        $img_name_type = $params->get('img_name_type');
        $imageHelper = ImageUtil::getInstance();

        $filename = $imageHelper->downloadImage($this->src);

        if ((!$filename) || (!file_exists($filename))) {
            $logger->log(\Joomla\CMS\Log\Log::ERROR, 'download: failed '.$this->src);

            return false;
        }

        // Main folder
        $path = JPATH_ROOT.\DIRECTORY_SEPARATOR.$img_folder;

        // Sub folder
        $path_subfolder = $this->processGetSubfolder($path, $sub_folder);

        if (!JFolder::exists($path_subfolder)) {
            $result = JFolder::create($path_subfolder);

            if (!$result) {
                $imageHelper->releaseImage($filename);
                $logger->log(\Joomla\CMS\Log\Log::ERROR, 'download: JFolder::create subfolder '.$path_subfolder);

                return false;
            }
        }

        $img_filename = $this->processGetImgFilename($filename, $img_name_type);
        $final_filename = $path_subfolder.\DIRECTORY_SEPARATOR.$img_filename;
        $result = rename($filename, $final_filename);

        if (!$result) {
            $imageHelper->releaseImage($filename);
            $logger->log(\Joomla\CMS\Log\Log::ERROR, 'download: rename '.$filename.' - '.$final_filename);

            return false;
        }

        $imgurl = str_replace(JPATH_ROOT.\DIRECTORY_SEPARATOR, '', $final_filename);
        $this->original_src = $this->src;

        if ($rel_src) {
            $this->src = $imgurl;
        } else {
            $this->src = RouteHelp::getInstance()->getRoot().$imgurl;
        }

        return true;
    }

    /**
     * processGetSubfolder.
     *
     * @param string $rootpath   Params
     * @param int    $sub_folder Params
     *
     * @return string
     */
    private function processGetSubfolder($rootpath, $sub_folder)
    {
        $path = [];

        $time = \Joomla\CMS\Factory::getDate()->toUnix();

        // Year
        $path[] = date('Y', $time);

        // Month
        if (3 === (int) $sub_folder) {
            $path[] = date('m', $time);
        }

        // Week
        if (2 === (int) $sub_folder) {
            $path[] = date('W', $time);
        }

        // Day
        if (1 === (int) $sub_folder) {
            $path[] = date('m', $time);
            $path[] = date('d', $time);
        }

        $subpath = implode(\DIRECTORY_SEPARATOR, $path);

        return $rootpath.\DIRECTORY_SEPARATOR.$subpath;
    }

    /**
     * processGetImgFilename.
     *
     * @param string $filename Params
     * @param int    $type     Params
     *
     * @return string
     */
    private function processGetImgFilename($filename, $type)
    {
        $filename = basename($filename);

        // Use Image Title/Alt
        if (0 === (int) $type) {
            $ext = pathinfo($filename, \PATHINFO_EXTENSION);

            if (!empty($this->title)) {
                $filename = $this->title;
            } elseif (!empty($this->alt)) {
                $filename = $this->alt;
            } else {
                return $filename;
            }

            $filename = TextUtil::convertUrlSafe($filename);

            return $filename.'.'.$ext;
        }

        // Use Original Filename
        if (1 === (int) $type) {
            return $filename;
        }

        // Use md5 hash
        if (2 === (int) $type) {
            $ext = pathinfo($filename, \PATHINFO_EXTENSION);
            $filename = md5($filename);

            return $filename.'.'.$ext;
        }

        return $filename;
    }
}
