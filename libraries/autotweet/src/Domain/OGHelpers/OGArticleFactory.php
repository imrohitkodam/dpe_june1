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

defined('_JEXEC') || exit;

/**
 * OGArticleFactory class.
 *
 * @since       1.0
 */
class OGArticleFactory
{
    private static $classNames = [
        'com_content.article' => 'OGJArticleHelper',
    ];

    /**
     * getHelper.
     *
     * @param string $option   Param
     * @param string $context  Param
     * @param object &$article Param
     *
     * @return string
     */
    public static function getHelper($option, $context, &$article)
    {
        if (array_key_exists($context, self::$classNames)) {
            $className = self::$classNames[$context];
            $helper = new $className($article);

            return $helper;
        }

        return null;
    }
}
