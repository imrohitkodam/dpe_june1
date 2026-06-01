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


use RegularLabs\Library\Image as RL_Image;

class Image
{
    private $image;
    private $settings;

    public function __construct($file, $settings = null)
    {
        $params         = Params::get();
        $this->settings = $settings;

        $this->image = (new RL_Image)->setFile($file)
            ->setEnableResize($this->settings->{'resize-images'} ?? true)
            ->setResizeFolder($this->settings->{'resize-folder'} ?? 'resized')
            ->setResizeQuality($this->settings->{'resize-quality'} ?? 'medium')
            ->setResizeMaxAge($this->settings->{'resize-max-age'} ?? 0)
            ->setUseRetina($this->settings->{'resize-use-retina'} ?? true)
            ->setRetinaPixelDensity($settings->{'resize-retina-pixel-density'} ?? 1.5)
            ->setDimensions($this->settings->width ?? 0, $this->settings->height ?? 0)
            ->setAutoTitles(
                $this->settings->{'auto-titles'} ?? $params->auto_titles,
                $params->title_case,
                $params->lowercase_words,
            )
            ->setLazyLoading($this->settings->{'image-lazy-loading'} ?? false)
            ->setItemProp($this->settings->{'thumbnail-add-itemprop'} ?? false);

        if ( ! empty($this->settings->title))
        {
            $this->image->setTitle($this->settings->title);
        }

        if ( ! empty($this->settings->alt))
        {
            $this->image->setTitle($this->settings->title);
        }

        if ( ! empty($this->settings->description))
        {
            $this->image->setDescription($this->settings->description);
        }
    }

    public function getAlt($modal = false)
    {
        if ( ! $modal)
        {
            return $this->image->getAlt();
        }

        if ( ! empty($this->settings->alt))
        {
            return $this->settings->alt;
        }

        if ( ! empty($this->settings->title))
        {
            return $this->settings->title;
        }

        return $this->image->getDataFileDataByType('modal-alt')
            ?: $this->image->getAlt();
    }

    public function getDescription()
    {
        if ( ! empty($this->settings->description))
        {
            return $this->settings->description;
        }

        return $this->image->getDataFileDataByType('modal-description')
            ?: $this->image->getDescription();
    }

    public function getFileName()
    {
        return $this->image->getFileName();
    }

    public function getFileStem()
    {
        return $this->image->getFileStem();
    }

    public function getOutputFile()
    {
        return $this->image->getOutputFile();
    }

    public function getSrcSet($pixel_density = null)
    {
        return $this->image->getSrcSet($pixel_density);
    }

    public function getTitle($modal = false)
    {
        if ( ! $modal)
        {
            return $this->image->getTitle();
        }

        if ( ! empty($this->settings->title))
        {
            return $this->settings->title;
        }

        if ( ! empty($this->settings->alt))
        {
            return $this->settings->alt;
        }

        return $this->image->getDataFileDataByType('modal-title')
            ?: $this->image->getTitle();
    }

    public function isResized()
    {
        return $this->image->isResized();
    }

    public function render()
    {
        return $this->image->render();
    }

    public function setDimensions($width, $height)
    {
        $this->image->setDimensions($width, $height);

        return $this;
    }

    public function setEnableResize($enabled)
    {
        $this->image->setEnableResize($enabled);

        return $this;
    }

    public function setItemProp($itemprop)
    {
        $this->image->setItemProp($itemprop);

        return $this;
    }

}
