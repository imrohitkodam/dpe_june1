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

use XTP_BUILD\Stringy\Stringy;

defined('_JEXEC') || exit;

/**
 * AutotweetTablePost.
 *
 * @since       1.0
 */
class AutotweetTablePost extends XTF0FTable
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
        parent::__construct('#__autotweet_posts', 'id', $db);

        $this->_columnAlias = [
            // 'enabled' => 'published',

            'created_on' => 'created',
            'modified_on' => 'modified',
            'locked_on' => 'checked_out_time',
            'locked_by' => 'checked_out',
        ];

        $this->_trackAssets = true;
    }

    /**
     * Checks the record for validity.
     *
     * @return int True if the record is valid
     */
    public function check()
    {
        // If the name is missing, throw an error
        if (!$this->title) {
            $this->setError(JText::_('COM_AUTOTWEET_POST_ERR_NEEDS_TITLE'));

            return false;
        }

        // If the catid is missing, throw an error
        if (!$this->channel_id) {
            $this->setError(JText::_('COM_AUTOTWEET_POST_ERR_NEEDS_CHANNEL'));

            return false;
        }

        return true;
    }

    /**
     * purge.
     *
     * @return bool True on success
     */
    public function purge()
    {
        $query = 'DELETE FROM '.$this->_db->qn($this->_tbl);
        $this->_db->setQuery($query);

        return $this->_db->execute();
    }

    /**
     * onAfterLoad.
     *
     * @param bool &$result Param
     */
    protected function onAfterLoad(&$result)
    {
        if (!(bool) $this->id) {
            $this->postdate = \Joomla\CMS\Factory::getDate()->format('Y-m-d');
            $this->ref_id = \Joomla\CMS\Factory::getDate()->toUnix();

            $this->url = \Joomla\CMS\Uri\Uri::root();
            $this->org_url = $this->url;

            $this->plugin = 'autotweetpost';
            $this->show_url = 'end_of_message';
        }
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
        // BLOB/TEXT default value
        if (!isset($this->params)) {
            $this->params = '';
        }

        $message = $this->message;

        if (strlen($message) > 512) {
            $this->message = (string) Stringy::create($message)->safeTruncate(512);
        }

        return parent::onBeforeStore($updateNulls);
    }
}
