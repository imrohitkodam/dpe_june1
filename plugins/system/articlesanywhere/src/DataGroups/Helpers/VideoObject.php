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



use RegularLabs\Library\HtmlTag as RL_HtmlTag;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;

class VideoObject
{
    static $regex            = '';
    static $url_prefix       = '';
    static $url_prefix_embed = '';
    static $url_prefix_short = '';
    public $attributes       = [];
    public $id               = '';
    public $query            = '';
    public $tag              = '';
    public $url              = '';

    public function __construct($iframe, $url)
    {
        if ( ! RL_RegEx::match(static::$regex, $url, $match))
        {
            return;
        }

        $this->tag        = $iframe;
        $this->attributes = (object) RL_HtmlTag::getAttributes($iframe);
        $this->id         = $match['id'];
        $this->query      = $match['query'];
        $this->url        = static::$url_prefix . $this->id . $this->query;
    }

    static function match($url, &$match = null)
    {
        return RL_RegEx::match(static::$regex, $url, $match);
    }

    public function getOutput($key, $attributes)
    {
        switch ($key)
        {
            case 'url':
                return $this->getUrl();

            case 'short-url':
            case 'url-short':
                return $this->getShortUrl();

            case 'link':
                return '<a href="' . $this->getUrl() . '" target="_blank" class="video-link">';

            case '/link':
                return '</a>';

            case 'embed-url':
            case 'url-embed':
            case 'iframe-url':
            case 'url-iframe':
                return $this->getEmbedUrl();

            case 'id':
                return $this->id;

            case 'thumb-url':
                return $this->getThumbUrl();

            case 'thumb':
                return $this->getThumbTag($attributes);

            case 'tag':
                return $this->getTag($attributes);

            default:
                return $this->getAttribute($key, $attributes);
        }
    }

    protected function getAttribute($key, $attributes)
    {
        $type = 'main';

        if (str_starts_with($key, 'thumb-'))
        {
            $type = 'thumb';
            $key  = RL_String::substr($key, 6);
        }

        $video_tag = $type === 'thumb'
            ? $this->getThumbTag($attributes)
            : $this->getTag($attributes);

        $tag_attributes = (object) RL_HtmlTag::getAttributes($video_tag);

        return $tag_attributes->{$key} ?? '';
    }

    protected function getEmbedUrl()
    {
        return static::$url_prefix_embed . $this->id . $this->query;
    }

    protected function getShortUrl()
    {
        return static::$url_prefix_short . $this->id . $this->query;
    }

    protected function getTag($attributes)
    {
        return $this->getEmbedTagWithAttributes($attributes);
    }

    protected function getThumbTag($attributes)
    {
        return '';
    }

    protected function getThumbUrl()
    {
        return '';
    }

    protected function getUrl()
    {
        return $this->url;
    }

    private function getEmbedTagWithAttributes($attributes)
    {
        if (empty($attributes))
        {
            return $this->tag;
        }

        $tag_attributes = [...(array) $this->attributes, ...(array) $attributes];

        return '<iframe ' . RL_HtmlTag::flattenAttributes($tag_attributes) . '></iframe>';
    }
}
