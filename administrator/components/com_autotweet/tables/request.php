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
 * AutotweetTableRequest.
 *
 * @since       1.0
 */
class AutotweetTableRequest extends XTF0FTable
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
        parent::__construct('#__autotweet_requests', 'id', $db);

        $this->_columnAlias = [
            'enabled' => 'published',
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
        if (empty($this->image_url)) {
            // Default image: used in media mode when no image is available
            $this->image_url = EParameter::getComponentParam(CAUTOTWEETNG, 'default_image', '');
        }

        if (!empty($this->image_url)) {
            $routeHelp = RouteHelp::getInstance();
            $this->image_url = $routeHelp->getAbsoluteUrl($this->image_url, true);
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
            $this->publish_up = \Joomla\CMS\Factory::getDate()->format('Y-m-d');
            $this->plugin = 'autotweetpost';
            $this->ref_id = \Joomla\CMS\Factory::getDate()->toUnix();
            $this->url = \Joomla\CMS\Uri\Uri::root();
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

        return parent::onBeforeStore($updateNulls);
    }
}
