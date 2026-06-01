<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

/*
 * @package     Extly Infrastructure Support
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @see         https://www.extly.com
 */

namespace XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla;

use XTP_BUILD\Extly\Infrastructure\Support\SupportException;
use JMenu;
use Joomla\CMS\Application\CliApplication;
use Joomla\CMS\Component\ComponentHelper as CMSComponentHelper;
use Joomla\CMS\Factory as CMSFactory;

class CliApplicationForJ3 extends CliApplication
{
    public const CLIENT_ADMINISTRATOR_ID = 1;

    public const CLIENT_ADMINISTRATOR_NAME = 'administrator';

    public function getName()
    {
        return self::CLIENT_ADMINISTRATOR_NAME;
    }

    public function getClientId()
    {
        return self::CLIENT_ADMINISTRATOR_ID;
    }

    public function getTemplate()
    {
        return (object) ['template' => 'cassiopeia', 'parent' => 'disabled-template'];
    }

    public function isClient($name)
    {
        return false;
    }

    public function getMenu($name = null, $options = [])
    {
        if (!$name) {
            $name = 'site';
        }

        return JMenu::getInstance($name, $options);
    }

    public function getUserState($key, $default = null)
    {
        return $default;
    }

    public function setUserState($key, $value)
    {
        return $value;
    }

    public function getRouter($client)
    {
        return new NoRouter();
    }

    public function isSite()
    {
        return false;
    }

    public function setHeader($name, $value, $replace = false)
    {
        return null;
    }

    public function isAdmin()
    {
        return true;
    }

    public function getUserStateFromRequest($key, $request, $default = null, $type = 'none', $resetPage = true)
    {
        if ('global.list.limit' === $key) {
            return null;
        }

        if (false !== strpos($key, '.filter')) {
            return [];
        }

        if (false !== strpos($key, '.list')) {
            return [];
        }

        return $default;
    }

    public function getCfg($path, $default = null)
    {
        return CMSFactory::getConfig()->get($path, $default);
    }

    public function allowCache($allow = null)
    {
        return false;
    }

    public function setBody($content)
    {
        throw new SupportException($content);
    }

    /**
     * Gets the parameter object for the component.
     *
     * @param string $option the option for the component
     * @param bool   $strict If set and the component does not exist, false will be returned
     *
     * @return Registry a Registry object
     */
    public static function getParams($option, $strict = false)
    {
        return CMSComponentHelper::getComponent($option, $strict)->getParams();
    }

    public function checkSession()
    {
        return false;
    }
}
