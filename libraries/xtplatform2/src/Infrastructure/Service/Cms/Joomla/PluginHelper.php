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
use XTP_BUILD\Extly\Infrastructure\Support\Estring;
use Joomla\CMS\Factory as CMSFactory;
use Joomla\Console\Application as CMSConsoleApp;

class PluginHelper
{
    use CreatorTrait;

    public const ACTIVATE_AT_BACKEND = 'activate-at-backend';

    public const EXCLURLS = 'exclurls';

    public const INCLURLS = 'inclurls';

    /**
     * isPluginEnabledUrl.
     *
     * @param mixed $params
     * @param mixed $url
     */
    public static function isPluginEnabledUrl($params, $url)
    {
        $documentType = CMSFactory::getDocument()->getType();

        if (('html' !== $documentType) && ('cli' !== $documentType)) {
            return false;
        }

        // Only In Html and CLI

        $activateAtBackend = (bool) $params->get(self::ACTIVATE_AT_BACKEND);
        $app = CMSFactory::getApplication();
        $isConsoleApp = ((!class_exists('CMSConsoleApp')) || ($app instanceof CMSConsoleApp));

        if ((!$activateAtBackend) && (!$isConsoleApp) && ($app->isClient('administrator'))) {
            return false;
        }

        $exclurls = $params->get(self::EXCLURLS);
        $exclurlsArray = EString::listOfLinesToArray($exclurls);

        $urlString = Estring::create($url);

        if ($urlString->checkListContains($exclurlsArray)) {
            return false;
        }

        $inclurls = $params->get(self::INCLURLS);
        $inclurlsArray = EString::listOfLinesToArray($inclurls);

        if ((!empty($inclurlsArray)) && (!$urlString->checkListContains($inclurlsArray))) {
            return false;
        }

        return true;
    }
}
