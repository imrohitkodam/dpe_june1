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



use RegularLabs\Library\ObjectHelper as RL_Object;
use RegularLabs\Library\RegEx as RL_RegEx;

class Video
{
    private static array $text_videos = [];

    /**
     * @param string $text
     * @param string $id
     * @param string $platform
     *
     * @return int
     */
    public static function getVideoCount($text, $platform = 'video')
    {
        if (empty($text))
        {
            return 0;
        }

        $cache_id = md5($text);

        self::cacheAllVideosFromText($text);

        if (empty(self::$text_videos[$cache_id]->{$platform}))
        {
            return 0;
        }

        return count(self::$text_videos[$cache_id]->{$platform});
    }

    /**
     * @param string $text
     * @param string $id
     * @param string $platform
     *
     * @return VideoObject|null
     */
    public static function getVideoObjectFromText($text, $id, $platform = 'video')
    {
        if (empty($text))
        {
            return null;
        }

        $cache_id = md5($text);

        self::cacheAllVideosFromText($text);

        if (empty(self::$text_videos[$cache_id]->{$platform}))
        {
            return null;
        }

        if ($id === 'random')
        {
            $id = rand(1, count(self::$text_videos[$cache_id]->{$platform}));
        }

        /* @var VideoObject $video */
        $video = self::$text_videos[$cache_id]->{$platform}[$id - 1] ?? null;

        if ( ! $video)
        {
            return null;
        }

        /* @var VideoObject $video */
        $video = RL_Object::clone($video);

        return $video;
    }

    private static function cacheAllVideosFromText($text)
    {
        $cache_id = md5($text);

        if (isset(self::$text_videos[$cache_id]))
        {
            return;
        }

        $iframe_regex = '(?<tag><iframe\s[^>]*src=([\'"])(?<url>[^\'"]*)\2.*?</iframe>)';

        RL_RegEx::matchAll($iframe_regex, $text, $iframes);

        self::$text_videos[$cache_id] = (object) [
            'video'   => [],
            'youtube' => [],
            'vimeo'   => [],
        ];

        foreach ($iframes as $i => $iframe)
        {
            if (VideoYoutube::match($iframe['url']))
            {
                $video                                   = new VideoYoutube($iframe[0], $iframe['url']);
                self::$text_videos[$cache_id]->youtube[] = $video;
                self::$text_videos[$cache_id]->video[]   = $video;
                continue;
            }

            if (VideoVimeo::match($iframe['url']))
            {
                $video                                 = new VideoVimeo($iframe[0], $iframe['url']);
                self::$text_videos[$cache_id]->vimeo[] = $video;
                self::$text_videos[$cache_id]->video[] = $video;
            }
        }
    }
}
