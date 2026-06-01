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
 * AutotweetTableChannel.
 *
 * @since       1.0
 */
class AutotweetTableChannel extends XTF0FTable
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
        parent::__construct('#__autotweet_channels', 'id', $db);

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
        // If the name is missing, throw an error
        if (empty($this->name)) {
            $this->setError(JText::_('COM_AUTOTWEET_CHANNEL_ERR_NEEDS_TITLE'));

            return false;
        }

        // If the catid is missing, throw an error
        if (empty($this->channeltype_id)) {
            $this->setError(JText::_('COM_AUTOTWEET_CHANNEL_ERR_NEEDS_TYPE'));

            return false;
        }

        return true;
    }

    /**
     * setToken.
     *
     * @param int    $id          Param
     * @param string $token_field Param
     * @param string $token       Param
     */
    public function setToken($id, $token_field, $token)
    {
        if ($id) {
            $result = $this->load($id);

            if (!$result) {
                throw new Exception(JText::_('COM_AUTOTWEET_CHANNEL_NOTLOADED').' - (setToken)!');
            }

            $params = $this->params;
            $registry = new JRegistry();
            $registry->loadString($params);
            $registry->set($token_field, $token);
            $this->bind(['params' => (string) $registry]);
            $this->store();
        } else {
            throw new Exception(JText::_('COM_AUTOTWEET_CHANNEL_NOTLOADED').' - 0 (setToken)!');
        }
    }

    /**
     * onAfterLoad.
     *
     * @param bool &$result Param
     */
    protected function onAfterLoad(&$result)
    {
        if (!(bool) $this->id) {
            $this->autopublish = true;
            $this->media_mode = SelectControlHelper::MEDIA_MODE_POST_WITH_IMAGE;
        }
    }
}
