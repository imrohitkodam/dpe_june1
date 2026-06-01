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
 * AutotweetTableRule.
 *
 * @since       1.0
 */
class AutotweetTableRule extends XTF0FTable
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
        parent::__construct('#__autotweet_rules', 'id', $db);

        $this->_columnAlias = [
            'enabled' => 'published',
            'created_on' => 'created',
            'modified_on' => 'modified',
            'locked_on' => 'checked_out_time',
            'locked_by' => 'checked_out',
        ];
    }

    /**
     * onAfterLoad.
     *
     * @param bool &$result Param
     */
    protected function onAfterLoad(&$result)
    {
        if (!(bool) $this->id) {
            $this->autopublish = 'on';
            $this->show_url = 'end_of_message';
            $this->show_static_text = 'off';
        }
    }
}
