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
 * ImageUtil.
 *
 * @since       1.0
 */
class ImageUtil
{
    protected $has_tmp_file = false;

    /**
     * getInstance.
     *
     * @return object
     */
    public static function getInstance()
    {
        static $helper = null;

        if (!$helper) {
            $helper = new self();
        }

        return $helper;
    }

    /**
     * isValidImageFile.
     *
     * @param string $imagefile Param
     *
     * @return bool
     */
    public static function isValidImageFile($imagefile)
    {
        [$width, $height, $type, $attr] = getimagesize($imagefile);

        $logger = AutotweetLogger::getInstance();
        $logger->log(\Joomla\CMS\Log\Log::INFO, "isValidImage (${width}, ${height}, ${type}, ${attr})");

        if (!in_array($type, [\IMAGETYPE_GIF, \IMAGETYPE_JPEG, \IMAGETYPE_PNG], true)) {
            return false;
        }

        $image_minx = EParameter::getComponentParam(CAUTOTWEETNG, 'image_minx', 100);

        if ($width < $image_minx) {
            return false;
        }

        $image_miny = EParameter::getComponentParam(CAUTOTWEETNG, 'image_miny', 0);

        if ($height < $image_miny) {
            return false;
        }

        return true;
    }

    /**
     * isImage.
     *
     * @param object $imagefile param
     *
     * @return string
     */
    public static function isImage($imagefile)
    {
        return self::getInstance()->isValidImageFile($imagefile);
    }

    /**
     * isValidImage.
     *
     * @param string $imageUrl Param
     *
     * @return bool
     */
    public function isValidImageUrl($imageUrl)
    {
        $validate_url = EParameter::getComponentParam(CAUTOTWEETNG, 'validate_url', 1);

        // Not validate
        if (!$validate_url) {
            return true;
        }

        $file = self::downloadImage($imageUrl);

        if (!$file) {
            return false;
        }

        if ($this->has_tmp_file) {
            $this->releaseImage($file);
        }

        return true;
    }

    /**
     * loadImage.
     *
     * @param string $imageUrl Param
     *
     * @return string
     */
    public function downloadImage($imageUrl)
    {
        $router = RouteHelp::getInstance();
        $this->has_tmp_file = false;
        $imagefile = $imageUrl;

        // Is Url?
        if ($router->isAbsoluteUrl($imageUrl)) {
            $imagefile = str_replace($router->getRoot(), JPATH_ROOT.\DIRECTORY_SEPARATOR, $imageUrl);

            // Is still Url ?
            if ($router->isAbsoluteUrl($imagefile)) {
                // Download it in a tmp file
                $imagefile = JInstallerHelper::downloadPackage($imageUrl);

                if ($imagefile) {
                    $this->has_tmp_file = true;
                    $imagefile = \Joomla\CMS\Factory::getConfig()->get('tmp_path').\DIRECTORY_SEPARATOR.$imagefile;
                }
            }

            // $imagefile is an absolute file
        } else {
            // Is relative file?
            if (strpos($imageUrl, \DIRECTORY_SEPARATOR) > 0) {
                $imagefile = JPATH_ROOT.\DIRECTORY_SEPARATOR.$imageUrl;
            }

            // $imagefile is an absolute file
        }

        // External Image? Download it into a tmp file, just to post it
        if (!is_file($imagefile)) {
            return null;
        }

        $logger = AutotweetLogger::getInstance();
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'downloadImage: '.$imagefile);

        if (!$this->isValidImageFile($imagefile)) {
            if ($this->has_tmp_file) {
                $this->releaseImage($imagefile);
            }

            return null;
        }

        return $imagefile;
    }

    /**
     * releaseImage.
     *
     * @param string $imagefile Param
     */
    public function releaseImage($imagefile)
    {
        // Double check
        if (($this->has_tmp_file) && ($imagefile)
            && (0 === strpos($imagefile, \Joomla\CMS\Factory::getConfig()->get('tmp_path')))) {
            unlink($imagefile);
            $this->has_tmp_file = false;
        }
    }
}
