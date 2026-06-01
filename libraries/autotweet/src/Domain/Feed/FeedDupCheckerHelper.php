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
 * FeedDupCheckerHelper class.
 *
 * @since       1.0
 */
class FeedDupCheckerHelper
{
    public const BASIC = 1;
    public const THOROUGH = 0;

    /**
     * feedContentValidate.
     *
     * @param object &$article         Param
     * @param int    $compare_existing Param
     *
     * @return bool
     */
    public static function feedContentIsDuplicated(&$article, $compare_existing = 0)
    {
        return self::processIsDuplicated($article, $compare_existing, '#__content', 'xreference', 'title', 'alias');
    }

    /**
     * feedK2IsDuplicated.
     *
     * @param object &$article         Param
     * @param int    $compare_existing Param
     *
     * @return bool
     */
    public static function feedK2IsDuplicated(&$article, $compare_existing)
    {
        return self::processIsDuplicated($article, $compare_existing, '#__k2_items', null, 'title', 'alias');
    }

    /**
     * feedZooIsDuplicated.
     *
     * @param object &$article         Param
     * @param int    $compare_existing Param
     *
     * @return bool
     */
    public static function feedZooIsDuplicated(&$article, $compare_existing)
    {
        return self::processIsDuplicated($article, $compare_existing, '#__zoo_item', null, 'name', 'alias');
    }

    /**
     * feedZooIsDuplicated.
     *
     * @param object &$article         Param
     * @param int    $compare_existing Param
     * @param string $table            Param
     * @param string $fld_xreference   Param
     * @param string $fld_title        Param
     * @param string $fld_alias        Param
     *
     * @return bool
     */
    private static function processIsDuplicated(&$article, $compare_existing, $table, $fld_xreference, $fld_title, $fld_alias)
    {
        $db = \Joomla\CMS\Factory::getDBO();

        $id = null;

        $query = XTF0FQueryAbstract::getNew($db)->select('id')->from($db->quoteName($table));
        $query->where($db->qn($fld_alias).' = '.$db->q($article->alias));
        $db->setQuery($query);
        $id = $db->loadResult();

        if (!empty($id)) {
            return true;
        }

        // Return
        if (self::BASIC === (int) $compare_existing) {
            return false;
        }

        $query = XTF0FQueryAbstract::getNew($db)->select('id')->from($db->quoteName($table));
        $query->where($db->qn($fld_title).' = '.$db->q($article->title));
        $db->setQuery($query);
        $id = $db->loadResult();

        if (!empty($id)) {
            return true;
        }

        return false;
    }
}
