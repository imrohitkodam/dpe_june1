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

use Joomla\CMS\Factory as JFactory;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\Uri as RL_Uri;

class Data
{
    static $count_inits = [];

    public static function createDataAttribute($key, $value)
    {
        $key = RL_RegEx::replace('^data-', '', $key);

        return 'data-modals-' . $key . '="' . $value . '"';
    }

    public static function createTagAttribute($key, $value)
    {
        if ($value === '<empty>')
        {
            return $key;
        }

        if (in_array($key, ['title', 'alt']))
        {
            $value = htmlentities(strip_tags($value));
        }

        return $key . '="' . $value . '"';
    }

    public static function flattenAttributeList($attributes)
    {
        $params = Params::get();

        $string = '';
        foreach ($attributes as $key => $value)
        {
            $key = trim($key);

            // Ignore attributes when key is empty
            if ($key == '')
            {
                continue;
            }

            $value = trim($value);

            // Ignore attributes when value is empty, but not a title or alt attribute
            if ($value == '' && ! in_array($key, ['alt', 'title']))
            {
                continue;
            }

            if (is_bool($value) && in_array($key, $params->booleans))
            {
                $value = $value ? 'true' : 'false';
            }

            $string .= ' ' . $key . '="' . $value . '"';
        }

        return $string;
    }

    public static function flattenMixedAttributeList($settings)
    {
        $params = Params::get();

        $tag_attributes  = [];
        $data_attributes = [];

        foreach ($settings as $key => $value)
        {
            $key = trim($key);

            if ( ! in_array($key, $params->valid_attribute_keys)
                && ! in_array($key, $params->valid_data_keys))
            {
                continue;
            }

            $value = self::prepareAttributeValue($key, $value);

            if (is_null($value))
            {
                continue;
            }

            if (in_array($key, $params->valid_attribute_keys))
            {
                $i = array_search($key, $params->valid_attribute_keys);

                $tag_attributes[$i] = self::createTagAttribute($key, $value);
            }

            if (in_array($key, $params->valid_data_keys))
            {
                $i = array_search($key, $params->valid_data_keys);

                $data_attributes[$i] = self::createDataAttribute($key, $value);
            }
        }

        ksort($tag_attributes);
        ksort($data_attributes);

        $attributes = array_merge(['data-modals'], $tag_attributes, $data_attributes);

        return implode(' ', array_unique($attributes));
    }

    public static function getIsOpenFromValue($value, $opentype, $cookie_id = '', $cookie_ttl = 0)
    {
        // min-max, like: open="2-10"
        if (strpos($value, '-') !== false)
        {
            [$min, $max] = explode('-', $value, 2);
            $min = (int) $min;
            $max = (int) $max;

            $count = self::getOpenCount($opentype, $cookie_id, $cookie_ttl);

            return (($max && $count <= $max) && $count >= $min);
        }

        // single value, like: open="2"
        $open = (int) $value;

        if ($open < 0)
        {
            return false;
        }

        $count = self::getOpenCount($opentype, $cookie_id, $cookie_ttl);

        return (bool) ($count == $open);
    }

    public static function isOpen($values, $opentype, $cookie_id = '', $cookie_ttl = 0)
    {
        if (in_array('true', $values))
        {
            return true;
        }

        if (in_array('false', $values))
        {
            return false;
        }

        if (in_array('once', $values))
        {
            $count = self::getOpenCount($opentype, $cookie_id, $cookie_ttl);

            return $count <= 1;
        }

        $break = false;
        foreach ($values as $value)
        {
            $open = self::getIsOpenFromValue($value, $opentype, $cookie_id, $cookie_ttl);

            if (is_array($open))
            {
                [$open, $break] = $open;
            }

            if ($open)
            {
                return true;
            }

            if ($break)
            {
                return false;
            }
        }

        return false;
    }

    public static function prepareAttributeValue($key, $value)
    {
        $params = Params::get();

        if (is_null($value))
        {
            $value = '';
        }

        if (is_bool($value) && in_array($key, $params->booleans))
        {
            $value = $value ? 'true' : 'false';
        }

        $value = trim($value);

        if ($value == '' && ! in_array($key, $params->include_empty_attributes))
        {
            return null;
        }

        return str_replace('"', '&quot;', $value);
    }

    public static function setDataAxis(&$data, $isexternal, $axis = 'width')
    {
        if ( ! empty($data->{$axis}))
        {
            return;
        }

        $params = Params::get();

        $data->{$axis} = $params->{$axis} ?: '95%';
    }

    public static function setDataOpen(&$data)
    {
        $open_by_url = JFactory::getApplication()->input->get('modal');
        if ($open_by_url)
        {
            $data->open = ! empty($data->id) && $open_by_url == $data->id;

            return;
        }

        $value = $data->open ?? '';

        if (is_bool($value))
        {
            $value = $value ? 'true' : 'false';
        }

        // explode into separate values, so that you can also do: open="1,5,10-20"
        $values = explode(',', $value);

        if ( ! empty($data->openonce))
        {
            $values[] = 'once';
        }

        if ( ! empty($data->openmin) || ! empty($data->openmax))
        {
            $min      = (int) ($data->openmin ?? 0);
            $max      = (int) ($data->openmax ?? 0);
            $values[] = $min . '-' . $max;
        }

        $opentype   = $data->opentype ?? '';
        $cookie_id  = $data->cookie ?? '';
        $cookie_ttl = $data->{'cookie-ttl'} ?? '';

        unset($data->open);
        unset($data->openonce);
        unset($data->openmin);
        unset($data->openmax);
        unset($data->opentype);
        unset($data->cookie);
        unset($data->{'cookie-ttl'});

        if (self::isOpen($values, $opentype, $cookie_id, $cookie_ttl))
        {
            $data->open = 'true';
        }
    }

    public static function setDataWidthHeight(&$data, $isexternal)
    {
        self::setDataAxis($data, $isexternal, 'width');
        self::setDataAxis($data, $isexternal, 'height');
    }

    private static function getOpenCount($type = '', $cookie_id = '', $cookie_ttl = 0)
    {
        $params = Params::get();

        $type = $type ?: $params->open_count_based_on;

        if ($type == 'session')
        {
            return JFactory::getSession()->get('session.counter', 0);
        }

        $cookie_name = 'rl_modals';
        $cookie_name .= ($type == 'page') ? '_' . md5(RL_Uri::get()) : '';
        $cookie_name .= $params->open_cookie_id ? '_' . $params->open_cookie_id : '';
        $cookie_name .= $cookie_id != '' ? '_' . $cookie_id : '';

        $count = (int) ($_COOKIE[$cookie_name] ?? 0);

        if (in_array($cookie_name, self::$count_inits))
        {
            return $count;
        }

        $count++;
        $ttl = $cookie_ttl ?: ($params->open_count_ttl ?: (365 * 24 * 60)); // default: 1 year
        $ttl = $ttl * 60;

        JFactory::getApplication()->input->cookie->set($cookie_name, $count, time() + $ttl, '/');

        self::$count_inits[] = $cookie_name;

        return $count;
    }
}
