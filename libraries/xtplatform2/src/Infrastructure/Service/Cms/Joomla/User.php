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

use XTP_BUILD\Extly\Infrastructure\Creator\CreatorTrait;
use XTP_BUILD\Extly\Infrastructure\Service\Cms\Contracts\NamedObjectInterface;
use XTP_BUILD\Extly\Infrastructure\Service\Cms\Contracts\UserInterface;
use XTP_BUILD\Extly\Infrastructure\Service\Facades\Cms;
use Joomla\CMS\Factory as CMSFactory;
use Joomla\CMS\User\User as CMSUser;

class User implements UserInterface, NamedObjectInterface
{
    use CreatorTrait;

    protected $user;

    public function __construct($id = null)
    {
        // $this->user = CMSUser::getInstance($id);
        $this->user = CMSFactory::getUser($id);
    }

    public function getId()
    {
        return $this->user->id;
    }

    public function getUsername()
    {
        return $this->user->username;
    }

    public function getName()
    {
        return $this->user->name;
    }

    public function isGuest()
    {
        return $this->user->guest;
    }

    public function isClient('administrator')
    {
        if ($this->user->guest) {
            return false;
        }

        return $this->user->authorise('core.manage', 'com_users');
    }

    public function getTimezone()
    {
        $timezone = $this->user->getParam('timezone');

        if (!empty($timezone)) {
            return $timezone;
        }

        return CMSFactory::getConfig()->get('offset');
    }

    public function getLanguage()
    {
        if (Cms::isClient('administrator')) {
            $language = $this->user->getParam('admin_language');
        } else {
            $language = $this->user->getParam('language');
        }

        if (!empty($language)) {
            return $language;
        }

        return Cms::getDefaultLanguageCode();
    }
}
