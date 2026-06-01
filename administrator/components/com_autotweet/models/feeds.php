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
 * AutotweetModelFeeds.
 *
 * @since       1.0
 */
class AutotweetModelFeeds extends XTF0FModel
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

        $query = XTF0FQueryAbstract::getNew($db)->select('*')->from($db->quoteName('#__autotweet_feeds'));

        $fltName = $this->getState('name', null, 'string');

        if ($fltName) {
            $fltName = "%${fltName}%";
            $query->where($db->qn('name').' LIKE '.$db->q($fltName));
        }

        $fltPublished = $this->getState('published', 1, 'cmd');
        $query->where($db->qn('published').' = '.$db->q($fltPublished));

        $fltContenttype = $this->getState('contenttype', null, 'cmd');

        if ($fltContenttype) {
            $query->where($db->qn('params').' like '.$db->q('%"contenttype_id":"'.$fltContenttype.'"%'));
        }

        $fltIds = $this->getState('ids', [], 'array');

        if (!empty($fltIds)) {
            $query->where($db->qn('id').' in ('.implode(',', $fltIds).')');
        }

        $search = $this->getState('search', null);

        if ($search) {
            $search = '%'.$search.'%';
            $query->where($db->qn('name').' LIKE '.$db->quote($search));
        }

        $order = $this->getState('filter_order', 'ordering', 'cmd');

        if (!in_array($order, array_keys($this->getTable()->getData()), true)) {
            $order = 'ordering';
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
