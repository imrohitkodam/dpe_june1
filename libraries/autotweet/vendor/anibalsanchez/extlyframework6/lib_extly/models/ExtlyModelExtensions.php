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

// No direct access
defined('_JEXEC') || exit('Restricted access');

/**
 * ExtlyModelExtensions.
 *
 * @since       1.0
 */
class ExtlyModelExtensions extends XTF0FModel
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

        $query = $db->getQuery(true)->select('*')->from($db->quoteName('#__extensions'));

        $fltName = $this->getState('name', null, 'string');

        if ($fltName) {
            $fltName = "%${fltName}%";
            $query->where($db->qn('name').' LIKE '.$db->q($fltName));
        }

        $fltEnabled = $this->getState('enabled', null, 'cmd');

        if (!empty($fltEnabled)) {
            $query->where($db->qn('enabled').' = '.$db->q($fltEnabled));
        }

        $fltType = $this->getState('type', null, 'cmd');

        if ($fltType) {
            $query->where($db->qn('type').' = '.$db->q($fltType));
        }

        $fltFolder = $this->getState('folder', null, 'cmd');

        if ($fltFolder) {
            $query->where($db->qn('folder').' = '.$db->q($fltFolder));
        }

        $fltElement = $this->getState('element', null, 'cmd');

        if ($fltElement) {
            $query->where($db->qn('element').' = '.$db->q($fltElement));
        }

        $order = $this->getState('filter_order', 'extension_id', 'cmd');

        if (!in_array($order, array_keys($this->getTable()->getData()), true)) {
            $order = 'extension_id';
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
