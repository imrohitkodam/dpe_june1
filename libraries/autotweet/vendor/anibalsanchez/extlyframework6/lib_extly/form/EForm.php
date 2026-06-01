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
 * Form Class for the Extly Library.
 *
 * @since       11.1
 */
class EForm
{
    /**
     * onBeforeSaveWithParams.
     *
     * @param array|XTF0FTable|object &$data Param
     */
    public static function onBeforeSaveWithParams(&$data)
    {
        if ($data instanceof XTF0FTable) {
            $allData = $data->getData();
            $params = self::paramsToString($allData);
            $data->params = $params;
        } elseif (is_object($data)) {
            $allData = (array) $data;
            $params = self::paramsToString($allData);
            $data->params = $params;
        } else {
            $data['params'] = self::paramsToString($data);
        }
    }

    /**
     * paramsToString.
     *
     * @param array  &$data Param
     * @param string $key   Param
     */
    public static function paramsToString(&$data, $key = 'xtform')
    {
        $params = null;

        if ((is_array($data)) && (array_key_exists($key, $data))) {
            $params = $data[$key];
            unset($data[$key]);
        }

        if ($params instanceof JRegistry) {
            $params = (string) $params;
        } else {
            $registry = new JRegistry();

            if ($params) {
                $registry->loadArray($params);
            }

            $params = (string) $registry;
        }

        return $params;
    }

    /**
     * paramsToRegistry.
     *
     * @param array  &$record Param
     * @param string $key     Param
     */
    public static function paramsToRegistry(&$record, $key = 'params')
    {
        $params = null;

        if (isset($record->{$key})) {
            $params = $record->{$key};
            unset($record->{$key});
        }

        $registry = new JRegistry();

        if ($params) {
            $registry->loadString($params);
        }

        return $registry;
    }
}
