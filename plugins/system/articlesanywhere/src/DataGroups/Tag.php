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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups;

defined('_JEXEC') or die;



use Joomla\CMS\Router\Route as JRoute;
use Joomla\Component\Tags\Site\Helper\RouteHelper as JTagsHelperRoute;
use RegularLabs\Library\Input as RL_Input;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Image;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Tags as TagsHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class Tag extends DataGroup
{
    protected static $data_key_aliases = [
        'text'  => 'title',
        'value' => 'alias',
        'row'   => 'layout',
    ];
    protected static $db_prefix        = 'tag';
    protected static $default_data_key = 'layout';
    protected static $ignore_group     = 'tags';
    protected static $prefix           = 'tag';
    private          $tag;

    /**
     * @return mixed
     */
    public function getValue()
    {
        switch ($this->key)
        {
            case 'has-access':
                return $this->hasAccess();

            case 'is-published':
                return $this->isPublished();

            case 'link':
                return $this->getLink();

            case 'sefurl':
                return $this->getSefUrl();

            case 'url':
                return $this->getUrl();

            case '/link':
                return '</a>';

            default:
                break;
        }

        $image_regex = '^image-(?<id>teaser|intro|introtext|full|fulltext)$';

        if (RL_RegEx::match($image_regex, $this->key, $match))
        {
            return $this->getImageByMatch($match);
        }

        if (parent::hasValue())
        {
            return parent::getValue();
        }

        $use_links = $this->getAttribute('links', true);

        if (
            (RL_Input::getCmd('option') === 'com_finder'
                && RL_Input::getCmd('format') === 'json')
        )
        {
            // Force text output without links for finder indexing, as the Tags RouteHelper causes errors
            $use_links = false;
        }

        return TagsHelper::getOutput([$this->tag], '', null, $use_links);
    }

    public function isPublished()
    {
        if ($this->get('published') != 1)
        {
            return false;
        }

        $publish_up   = $this->get('publish-up');
        $publish_down = $this->get('publish-down');

        $nowDate  = DB::getNowDate();
        $nullDate = DB::getNullDate();

        return $publish_up <= $nowDate
            && (
                empty($publish_down)
                || $publish_down == $nullDate
                || $publish_down >= $nowDate
            );
    }

    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    protected static function getExtraFields()
    {
        return [
            'has-access', 'is-published',
            'url', 'sefurl', 'link', '/link',
        ];
    }

    protected static function getJsonKeys()
    {
        return [
            'images'   => self::getImageFields(),
            'urls'     => self::getUrlFields(),
            'params'   => self::getParamFields(),
            'metadata' => self::getMetadataFields(),
        ];
    }

    protected static function getValueKeys()
    {
        return ['*'];
    }

    protected function getImageByMatch($match)
    {
        $key = $this->subkey ?: 'tag';

        if ($key === 'tag' && $this->getAttribute('layout'))
        {
            $key = 'layout';
        }

        switch ($match['id'])
        {
            case 'full':
            case 'fulltext':
                $type       = 'fulltext';
                $image_data = (object) [
                    'type'    => 'full_image',
                    'src'     => $this->get('tag.images.image_fulltext'),
                    'float'   => $this->get('tag.images.float_fulltext'),
                    'alt'     => $this->get('tag.images.image_fulltext_alt'),
                    'caption' => $this->get('tag.images.image_fulltext_caption'),
                ];
                break;

            case 'intro':
            case 'introtext':
            case 'teaser':
            default:
                $type       = 'intro';
                $image_data = (object) [
                    'type'    => 'intro_image',
                    'src'     => $this->get('tag.images.image_intro'),
                    'float'   => $this->get('tag.images.float_intro'),
                    'alt'     => $this->get('tag.images.image_intro_alt'),
                    'caption' => $this->get('tag.images.image_intro_caption'),
                ];
                break;
        }

        return Image::getOutputByKey($key, $image_data, $this->attributes, $type);
    }

    private static function getImageFields()
    {
        return [
            'image_intro',
            'float_intro',
            'image_intro_alt',
            'image_intro_caption',
            'image_fulltext',
            'float_fulltext',
            'image_fulltext_alt',
            'image_fulltext_caption',
        ];
    }

    private static function getMetadataFields()
    {
        return [
            'robots',
            'author',
        ];
    }

    private static function getParamFields()
    {
        return [
            'tag_layout',
            'tag_link_class',
        ];
    }

    private static function getUrlFields()
    {
        return [
            'urla',
            'urlatext',
            'targeta',
            'urlb',
            'urlbtext',
            'targetb',
            'urlc',
            'urlctext',
            'targetc',
        ];
    }

    private function getLink()
    {
        return '<a href="' . $this->getSefUrl() . '">';
    }

    private function getSefUrl()
    {
        return JRoute::link('site', $this->getUrl(), true);
    }

    private function getUrl()
    {
        return JTagsHelperRoute::getTagRoute($this->get('id'));
    }

    private function hasAccess()
    {
        return in_array($this->get('access'), Params::getAuthorisedViewLevels());
    }
}
