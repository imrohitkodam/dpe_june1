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
 * AutotweetTableAdvancedAttr.
 *
 * @since       1.0
 */
class AutotweetTableAdvancedAttr extends XTF0FTable
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
        parent::__construct('#__autotweet_advanced_attrs', 'id', $db);
    }

    /**
     * The event which runs before storing (saving) data to the database.
     *
     * @param bool $updateNulls Should nulls be saved as nulls (true) or just skipped over (false)?
     *
     * @return bool True to allow saving
     */
    protected function onBeforeStore($updateNulls)
    {
        $result = parent::onBeforeStore($updateNulls);

        if ($result) {
            $params = json_decode($this->params);

            if (isset($params->evergreen)) {
                $this->evergreentype_id = $params->evergreen;
            }
        }

        return $result;
    }
}
