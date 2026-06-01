<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

// No direct access
defined('_JEXEC') || exit('Restricted access');

/**
 * EExtensionHelper.
 *
 * @since       1.0
 */
class EExtensionHelper
{
    /**
     * getComponentId.
     *
     * @param string $element Params
     *
     * @return int
     */
    public static function getComponentId($element)
    {
        $extensionsModel = XTF0FModel::getTmpInstance('Extensions', 'ExtlyModel');
        $extensionsModel->set('type', 'component');
        $extensionsModel->set('element', $element);
        $extensionsModel->set('limit', 1);
        $extensions = $extensionsModel->getItemList();

        if (1 === count($extensions)) {
            $extension = $extensions[0];

            return $extension->extension_id;
        }

        return null;
    }

    /**
     * getExtensionId.
     *
     * @param string $folder  Params
     * @param string $element Params
     *
     * @return int
     */
    public static function getExtensionId($folder, $element)
    {
        return self::getExtensionParam($folder, $element, 'extension_id');
    }

    /**
     * getExtensionParam.
     *
     * @param string $folder  Params
     * @param string $element Params
     * @param string $key     Params
     * @param string $default Params
     *
     * @return string
     */
    public static function getExtensionParam($folder, $element, $key, $default = null)
    {
        $extensionsModel = XTF0FModel::getTmpInstance('Extensions', 'ExtlyModel');
        $extensionsModel->set('folder', $folder);
        $extensionsModel->set('element', $element);
        $extensionsModel->set('limit', 1);
        $extensions = $extensionsModel->getItemList();

        if (1 === count($extensions)) {
            $extension = $extensions[0];

            if (isset($extension->{$key})) {
                return $extension->{$key};
            }

            $extension->xtform = EForm::paramsToRegistry($extension);

            return $extension->xtform->get($key, $default);
        }

        return $default;
    }

    /**
     * setExtensionParam.
     *
     * @param string $folder  Params
     * @param string $element Params
     * @param string $key     Params
     * @param string $value   Params
     *
     * @return string
     */
    public static function setExtensionParam($folder, $element, $key, $value)
    {
        $extensionsModel = XTF0FModel::getTmpInstance('Extensions', 'ExtlyModel');
        $extensionsModel->set('folder', $folder);
        $extensionsModel->set('element', $element);
        $extensionsModel->set('limit', 1);
        $extensions = $extensionsModel->getItemList();

        if (1 === count($extensions)) {
            $extension = $extensions[0];

            if (isset($extension->{$key})) {
                $extension->{$key} = $value;

                return $extensionsModel->save($extension);
            }

            $extension->xtform = EForm::paramsToRegistry($extension);
            $extension->xtform->set($key, $value);

            return $extensionsModel->save($extension);
        }

        return false;
    }

    /**
     * Custom clean cache method, plugins are cached in 2 places for different clients.
     *
     * @since   1.6
     */
    public static function cleanCache()
    {
        $config = JFactory::getConfig();
        $options = [
            'defaultgroup' => 'com_plugins',
            'cachebase' => $config->get('cache_path', JPATH_SITE.'/cache'),
        ];

        $cache = JCache::getInstance('callback', $options);
        $cache->clean();

        // Trigger the onContentCleanCache event.
        $dispatcher = null;

        if (class_exists('JEventDispatcher')) {
            $dispatcher = JEventDispatcher::getInstance();
        } elseif (class_exists('JDispatcher')) {
            $dispatcher = JDispatcher::getInstance();
        } elseif (class_exists('\Joomla\CMS\Factory')) {
            $app = \Joomla\CMS\Factory::getApplication();

            if (method_exists($app, 'getDispatcher')) {
                $dispatcher = $app->getDispatcher();
            }
        }

        if ($dispatcher) {
            return $dispatcher->trigger('onContentCleanCache', $options);
        }

        $app = JFactory::getApplication();

        if (method_exists($app, 'triggerEvent'))
        {
            return $app->triggerEvent('onContentCleanCache', $options);
        }
    }
}
