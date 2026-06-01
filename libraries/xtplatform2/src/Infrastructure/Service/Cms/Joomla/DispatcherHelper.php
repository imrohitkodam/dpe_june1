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

use Exception;
use JEventDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\Event;

class DispatcherHelper
{
    public static function getInstance()
    {
        if (class_exists('JEventDispatcher')) {
            return JEventDispatcher::getInstance();
        }

        if (class_exists('\Joomla\CMS\Factory')) {
            $app = Factory::getApplication();

            if (method_exists($app, 'getDispatcher')) {
                return $app->getDispatcher();
            }
        }

        if (class_exists('JDispatcher')) {
            return JDispatcher::getInstance();
        }

        throw new \Exception('Unable to load the Event Dispatcher');
    }

    public static function getPlugin($folder, $name)
    {
        $pluginClassName = 'Plg'.$folder.$name;

        if (!class_exists($pluginClassName)) {
            PluginHelper::importPlugin($folder, $name);
        }

        if (!class_exists($pluginClassName)) {
            return null;
        }

        $pluginLookup = PluginHelper::getPlugin($folder, strtolower($name));

        if (empty($pluginLookup)) {
            return null;
        }

        $dispatcher = self::getInstance();
        $params = (array) $pluginLookup;

        return new $pluginClassName($dispatcher, $params);
    }

    public static function trigger(string $event, array $data = []): array
    {
        // Adapted from Akeeba FOF4

        if (class_exists('JEventDispatcher')) {
            return JEventDispatcher::getInstance()->trigger($event, $data);
        }

        // If there's no JEventDispatcher try getting JApplication
        try {
            $app = Factory::getApplication();
        } catch (Exception $e) {
            // If I can't get JApplication I cannot run the plugins.
            return [];
        }

        // Joomla 3 and 4 have triggerEvent
        if (method_exists($app, 'triggerEvent')) {
            $result = $app->triggerEvent($event, $data);

            return is_array($result) ? $result : [];
        }

        // Joomla 5 (and possibly some 4.x versions) don't have triggerEvent. Go through the Events dispatcher.
        if (method_exists($app, 'getDispatcher') && class_exists('Joomla\Event\Event')) {
            try {
                $dispatcher = $app->getDispatcher();
            } catch (\UnexpectedValueException $exception) {
                return [];
            }

            if ($data instanceof Event) {
                $eventObject = $data;
            } elseif (\is_array($data)) {
                $eventObject = new Event($event, $data);
            } else {
                throw new \InvalidArgumentException('The plugin data must either be an event or an array');
            }

            $result = $dispatcher->dispatch($event, $eventObject);

            return !isset($result['result']) || null === $result['result'] ? [] : $result['result'];
        }

        // No viable way to run the plugins :(
        return [];
    }
}
