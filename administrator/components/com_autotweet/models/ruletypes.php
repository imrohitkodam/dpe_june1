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
 * AutotweetModelRuleTypes.
 *
 * @since       1.0
 */
class AutotweetModelRuleTypes extends XTF0FModel
{
    public const CATEGORY_IN = 1;
    public const CATEGORY_NOTIN = 2;
    public const TERM_OR = 3;
    public const TERM_AND = 4;
    public const CATCH_ALL_NOTFITS = 5;
    public const WORDTERM_OR = 6;
    public const WORDTERM_AND = 7;
    public const REG_EXPR = 8;
    public const TERM_NOTIN = 9;
    public const WORDTERM_NOTIN = 10;
    public const AUTHOR_IN = 11;
    public const AUTHOR_NOTIN = 12;
    public const CATCH_ALL = 13;
    public const LANGUAGE_IN = 14;
    public const LANGUAGE_NOTIN = 15;
    public const ACCESS_IN = 16;
    public const ACCESS_NOTIN = 17;

    /**
     * buildQuery.
     *
     * @param bool $overrideLimits Param
     *
     * @return XTF0FQuery
     */
    public function buildQuery($overrideLimits = false)
    {
        $db = $this->getDBO();
        $query = parent::buildQuery($overrideLimits);
        $query->order($db->qn('name'));

        return $query;
    }
}
