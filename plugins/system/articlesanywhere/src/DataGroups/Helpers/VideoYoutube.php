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



use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class VideoYoutube extends VideoObject
{
    static $regex                 = '(?:youtube\.com\/watch\?v=|youtu\.be/|youtube\.com/embed/)(?<id>[0-9a-z_-]+)(?<query>.*)';
    static $url_prefix            = 'https://www.youtube.com/watch?v=';
    static $url_prefix_embed      = 'https://www.youtube.com/embed/';
    static $url_prefix_no_cookies = 'https://www.youtube-nocookie.com/embed/';
    static $url_prefix_short      = 'https://youtu.be/';

    public function __construct($iframe, $url)
    {
        $params = Params::get();

        if ($params->youtube_embed_url === 'no_cookies')
        {
            self::$url_prefix_embed = self::$url_prefix_no_cookies;
        }

        parent::__construct($iframe, $url);
    }

    protected function getEmbedUrl()
    {
        $url = parent::getEmbedUrl();

        if (str_contains($url, 'wmode=transparent'))
        {
            return $url;
        }

        $url .= (str_contains($url, '?') ? '&' : '?') . 'wmode=transparent';

        return $url;
    }

    protected function getThumbTag($attributes)
    {
        $attributes->src               = $this->getThumbUrl();
        $attributes->class             ??= 'video-thumbnail-youtube';
        $attributes->{'resize-images'} = false;

        Image::setAltAndTitle('video', $attributes);

        $image = Image::get($attributes);

        return $image->renderTag();
    }

    protected function getThumbUrl()
    {
        return 'https://img.youtube.com/vi/' . $this->id . '/mqdefault.jpg';
    }
}
