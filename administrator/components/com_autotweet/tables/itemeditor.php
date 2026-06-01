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
 * AutotweetTableItemEditor.
 *
 * @since       1.0
 */
class AutotweetTableItemEditor extends XTF0FTable
{
    /**
     * Instantiate the table object.
     *
     * @param string    $table Param
     * @param string    $key   Param
     * @param JDatabase &$db   The Joomla! database object
     */
    public function __construct($table, $key, &$db)
    {
        parent::__construct('#__autotweet_automator', 'id', $db);

        $this->xtform = new JRegistry();
        $this->unix_mhdmd = null;
        $this->repeat_until = null;
    }
}
