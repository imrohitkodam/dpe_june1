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
 * AutotweetTableFeed.
 *
 * @since       1.0
 */
class AutotweetTableFeed extends XTF0FTable
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
        parent::__construct('#__autotweet_feeds', 'id', $db);

        $this->_columnAlias = [
            'enabled' => 'published',
            'created_on' => 'created',
            'modified_on' => 'modified',
            'locked_on' => 'checked_out_time',
            'locked_by' => 'checked_out',
        ];
    }

    /**
     * Checks the record for validity.
     *
     * @return int True if the record is valid
     */
    public function check()
    {
        $registry = new JRegistry();
        $registry->loadString($this->params);
        $url = $registry->get('url');

        if (false === filter_var($url, \FILTER_VALIDATE_URL)) {
            $this->setError(JText::sprintf('COM_AUTOTWEET_FEED_INVALID_URL', $url));

            return false;
        }

        return true;
    }
}
