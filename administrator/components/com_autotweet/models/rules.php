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
 * AutotweetModelRules.
 *
 * @since       1.0
 */
class AutotweetModelRules extends XTF0FModel
{
    /**
     * buildQuery.
     *
     * @param bool $overrideLimits Param
     *
     * @return XTF0FQuery
     */
    public function buildQuery($overrideLimits = false)
    {
        $db = $this->getDbo();

        $query = XTF0FQueryAbstract::getNew($db)->select('*')->from($db->quoteName('#__autotweet_rules'));

        $fltName = $this->getState('name', null, 'string');

        if ($fltName) {
            $fltName = "%${fltName}%";
            $query->where($db->qn('name').' LIKE '.$db->q($fltName));
        }

        $fltPublished = $this->getState('published', 1, 'cmd');
        $query->where($db->qn('published').' = '.$db->q($fltPublished));

        $fltRuletype = $this->getState('ruletype', null, 'int');

        if ($fltRuletype) {
            $query->where($db->qn('ruletype_id').' = '.$db->q($fltRuletype));
        }

        $fltChannel = $this->getState('channel', null, 'int');

        if ($fltChannel) {
            $query->where($db->qn('channel_id').' = '.$db->q($fltChannel));
        }

        $fltPlugin = $this->getState('plugin', null, 'string');

        if ($fltPlugin) {
            $query->where($db->qn('plugin').' = '.$db->q($fltPlugin));
        }

        $search = $this->getState('search', null);

        if ($search) {
            $search = '%'.$search.'%';
            $query->where($db->qn('name').' LIKE '.$db->quote($search));
        }

        $order = $this->getState('filter_order', 'id', 'cmd');

        if (!in_array($order, array_keys($this->getTable()->getData()), true)) {
            $order = 'id';
        }

        $dir = $this->getState('filter_order_Dir', 'ASC', 'cmd');
        $query->order($order.' '.$dir);

        return $query;
    }

    /**
     * This method runs before the $data is saved to the $table. Return false to
     * stop saving.
     *
     * @param array  &$data  Param
     * @param JTable &$table Param
     *
     * @return bool
     */
    protected function onBeforeSave(&$data, &$table)
    {
        EForm::onBeforeSaveWithParams($data);

        return parent::onBeforeSave($data, $table);
    }

    /**
     * This method runs after an item has been gotten from the database in a read
     * operation. You can modify it before it's returned to the MVC triad for
     * further processing.
     *
     * @param JTable &$record Param
     *
     * @return bool
     */
    protected function onAfterGetItem(&$record)
    {
        $record->xtform = EForm::paramsToRegistry($record);

        return parent::onAfterGetItem($record);
    }
}
