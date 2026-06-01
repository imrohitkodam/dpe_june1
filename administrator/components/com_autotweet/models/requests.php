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
 * AutotweetModelRequests.
 *
 * @since       1.0
 */
class AutotweetModelRequests extends XTF0FModel
{
    protected $advanced_attrs;

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

        $query = XTF0FQueryAbstract::getNew($db)->select('*')->from($db->quoteName('#__autotweet_requests'));

        $fltPublishup = $this->getState('publish_up', null, 'date');

        if ($fltPublishup) {
            $fltPublishup = $fltPublishup.'%';
            $query->where($db->qn('publish_up').' LIKE '.$db->q($fltPublishup));
        }

        $fltUntilDate = $this->getState('until_date', null, 'date');

        if ($fltUntilDate) {
            $query->where($db->qn('publish_up').' <= '.$db->q($fltUntilDate));
        }

        $input = new \Joomla\CMS\Input\Input($_REQUEST);
        $start = $input->get('xtstart');

        if ($start) {
            $date = new JDate($start);
            $query->where($db->qn('publish_up').' >= '.$db->q($date->toSql()));
        }

        $end = $input->get('xtend');

        if ($end) {
            $date = new JDate($end);
            $query->where($db->qn('publish_up').' <= '.$db->q($date->toSql()));
        }

        $fltPlugin = $this->getState('plugin', null, 'string');

        if ($fltPlugin) {
            $query->where($db->qn('plugin').' = '.$db->q($fltPlugin));
        }

        $fltRefId = $this->getState('ref_id', null, 'string');

        if ($fltRefId) {
            $query->where($db->qn('ref_id').' = '.$db->q($fltRefId));
        }

        $fltRids = $this->getState('rids', null);

        if (!empty($fltRids)) {
            if (is_string($fltRids)) {
                $fltRids = TextUtil::listToArray($fltRids);
            }

            $list = [];

            foreach ($fltRids as $p) {
                $list[] = $db->q($p);
            }

            $fltRids = implode(',', $list);
            $query->where($db->qn('id').' IN ('.$fltRids.')');
        }

        $fltTypeinfo = $this->getState('typeinfo', null, 'string');

        if ($fltTypeinfo) {
            $query->where($db->qn('typeinfo').' = '.$db->q($fltTypeinfo));
        }

        $fltPublished = $this->getState('published', 0, 'int');
        $query->where($db->qn('published').' = '.$db->q($fltPublished));

        $search = $this->getState('search', null);

        if ($search) {
            $search = '%'.$search.'%';
            $query->where('('.$db->qn('id').' LIKE '.$db->quote($search).' OR '.$db->qn('ref_id').' LIKE '.$db->quote($search).' OR '.$db->qn('description').' LIKE '.$db->quote($search).' OR '.$db->qn('url').' LIKE '.$db->quote($search).')');
        }

        $fltCreatedBy = $this->getState('created_by', null, 'int');

        if ($fltCreatedBy) {
            $query->where($db->qn('created_by').' = '.$db->q($fltCreatedBy));
        }

        $order = $this->getState('filter_order', 'publish_up', 'cmd');

        if (!in_array($order, array_keys($this->getTable()->getData()), true)) {
            $order = 'publish_up';
        }

        $dir = $this->getState('filter_order_Dir', 'ASC', 'cmd');
        $query->order($order.' '.$dir);

        return $query;
    }

    /**
     * Overwrites original method for AT specific handling.
     *
     * @return string
     */
    public function process()
    {
        if (is_array($this->id_list) && !empty($this->id_list)) {
            if (empty($user)) {
                $oUser = \Joomla\CMS\Factory::getUser();
                $userid = $oUser->id;
            }

            if (!RequestHelper::processRequests($this->id_list)) {
                $this->setError('processRequests failed');

                return false;
            }
        }

        return true;
    }

    /**
     * moveToState.
     *
     * @param int $published Param
     *
     * @return string
     */
    public function moveToState($published)
    {
        if (is_array($this->id_list) && !empty($this->id_list)) {
            if (empty($user)) {
                $oUser = \Joomla\CMS\Factory::getUser();
                $userid = $oUser->id;
            }

            if (!RequestHelper::moveToState($this->id_list, $userid, $published)) {
                $this->setError('Requests::moveToState failed');

                return false;
            }
        }

        return true;
    }

    /**
     * moveToEvergeen.
     *
     * @param int $evergreen Param
     *
     * @return string
     */
    public function moveToEvergeen($evergreen)
    {
        if (is_array($this->id_list) && !empty($this->id_list)) {
            if (empty($user)) {
                $oUser = \Joomla\CMS\Factory::getUser();
                $userid = $oUser->id;
            }

            if (!RequestHelper::moveToEvergeen($this->id_list, $userid, $evergreen)) {
                $this->setError('Requests::moveToEvergeen failed');

                return false;
            }
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
        if (PERFECT_PUB_PRO) {
            $db = \Joomla\CMS\Factory::getDBO();
            $query = 'DELETE r FROM #__autotweet_requests r LEFT OUTER JOIN #__autotweet_advanced_attrs a ON r.id = a.request_id WHERE a.request_id IS NULL';
            $db->setQuery($query);
            $db->execute();
        } else {
            $table = $this->getTable($this->table);
            $table->purge();
        }

        return true;
    }

    /**
     * getUserDayPosts.
     *
     * @param int $userId Param
     *
     * @return int
     */
    public function getUserDayPosts($userId)
    {
        $this->set('published', 1);

        $now = \Joomla\CMS\Factory::getDate();
        $date = $now->toSql();
        $date = explode(' ', $date);
        $date = $date[0];

        $this->set('publish_up', $date);
        $this->set('created_by', $userId);

        return $this->getTotal();
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
        $data['params'] = EForm::paramsToString($data);

        if (array_key_exists('publish_up', $data)) {
            $data['publish_up'] = EParameter::convertLocalUTC($data['publish_up']);
        } else {
            $data['publish_up'] = \Joomla\CMS\Factory::getDate()->toSql();
        }

        // Cleaning annoying spaces
        $data = array_map('trim', $data);

        if (array_key_exists('autotweet_advanced_attrs', $data)) {
            $this->advanced_attrs = AdvancedAttributesHelper::fromQueryParams($data['autotweet_advanced_attrs']);
        }

        return parent::onBeforeSave($data, $table);
    }

    /**
     * This method runs after the data is saved to the $table.
     *
     * @param XTF0FTable &$table The table which was saved
     *
     * @return bool
     */
    protected function onAfterSave(&$table)
    {
        $result = parent::onAfterSave($table);

        if (isset($this->advanced_attrs)) {
            $this->advanced_attrs->ref_id = $table->ref_id;
            AdvancedAttributesHelper::save($this->advanced_attrs, $table->ref_id);
        }

        return $result;
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
