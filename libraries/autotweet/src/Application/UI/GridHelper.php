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
 * GridHelper.
 *
 * @since       1.0
 */
class GridHelper
{
    /**
     * Method to create a clickable icon to change the state of an item.
     *
     * @param mixed $value    Either the scalar value or an object (for backward compatibility, deprecated)
     * @param int   $i        The index
     * @param bool  $isModule Param
     *
     * @return string
     */
    public static function pubstates($value, $i, $isModule = false)
    {
        if (is_object($value)) {
            $value = $value->pubstate;
        }

        return SelectControlHelper::getTextForEnum($value, true, $isModule);
    }

    /**
     * loadComponentInfo.
     *
     * @param object $registry Param
     */
    public static function loadComponentInfo($registry)
    {
        // Load the model
        $info = XTF0FModel::getTmpInstance('Update', 'AutoTweetModel');

        $registry->set('comp', $info->getComponentInfo());
        $registry->set('plugins', $info->getPluginInfo());
        $registry->set('thirdparty', $info->getThirdpartyInfo());
        $registry->set('sysinfo', $info->getSystemInfo());
    }

    /**
     * loadStats.
     *
     * @param object $registry Param
     */
    public static function loadStats($registry)
    {
        // 30 days = 30 * 24 * 60 * 60
        $time_intval = 2592000;

        // Calculate date for interval
        $now = \Joomla\CMS\Factory::getDate();
        $check_date = $now->toUnix();
        $check_date = $check_date - $time_intval;
        $check_date = \Joomla\CMS\Factory::getDate($check_date);

        $postsModel = XTF0FModel::getTmpInstance('Posts', 'AutoTweetModel');
        $postsModel->set('after_date', $check_date->toSql());
        $stats = $postsModel->getStatsTotal();

        $success = $stats['success']->count ?? 0;
        $error = $stats['error']->count ?? 0;
        $approve = $stats['approve']->count ?? 0;
        $cronjob = $stats['cronjob']->count ?? 0;
        $cancelled = $stats['cancelled']->count ?? 0;

        $postsTotal = $success +
            $error +
            $approve +
            $cronjob +
            $cancelled;

        $registry->set('success', $success);
        $registry->set('error', $error);
        $registry->set('approve', $approve);
        $registry->set('cronjob', $cronjob);
        $registry->set('cancelled', $cancelled);
        $registry->set('total', $postsTotal);

        if ($postsTotal) {
            $registry->set('p_success', round($success / $postsTotal * 100));
            $registry->set('p_error', round($error / $postsTotal * 100));
            $registry->set('p_approve', round($approve / $postsTotal * 100));
            $registry->set('p_cronjob', round($cronjob / $postsTotal * 100));
            $registry->set('p_cancelled', round($cancelled / $postsTotal * 100));
            $registry->set('p_total', $postsTotal);
        } else {
            $registry->set('p_success', 0);
            $registry->set('p_error', 0);
            $registry->set('p_approve', 0);
            $registry->set('p_cronjob', 0);
            $registry->set('p_cancelled', 0);
            $registry->set('p_total', 0);
        }

        $requestModel = XTF0FModel::getTmpInstance('Requests', 'AutoTweetModel');
        $requestModel->savestate(false);
        $requestModel->set('after_date', $check_date->toSql());
        $requestsTotal = $requestModel->getTotal();

        $total = $postsTotal + $requestsTotal;

        $registry->set('requests', $requestsTotal);
        $registry->set('posts', $postsTotal);

        if ($total) {
            $registry->set('p_requests', round($requestsTotal / $total * 100));
            $registry->set('p_posts', round($postsTotal / $total * 100));
        } else {
            $registry->set('p_requests', 0);
            $registry->set('p_posts', 0);
        }
    }

    /**
     * loadStatsTimeline.
     *
     * @param object $registry Param
     */
    public static function loadStatsTimeline($registry)
    {
        // 30 days = 30 * 24 * 60 * 60
        $time_intval = 2592000;

        // Calculate date for interval
        $now = \Joomla\CMS\Factory::getDate();
        $check_date = $now->toUnix();
        $check_date = $check_date - $time_intval;
        $check_date = \Joomla\CMS\Factory::getDate($check_date);

        $postsModel = XTF0FModel::getTmpInstance('Posts', 'AutoTweetModel');
        $postsModel->set('after_date', $check_date->toSql());
        $stats = $postsModel->getStatsTimeline();

        $timeline = self::generateTimeline($stats);
        $registry->set('timeline', $timeline);
    }

    /**
     * generateTimeline.
     *
     * @param array $timeline Param
     *
     * @return string
     */
    private static function generateTimeline($timeline)
    {
        $values = [];
        $states = ['success', 'cronjob', 'approve', 'cancelled', 'error'];

        foreach ($states as $state) {
            $values[$state] = [];
        }

        foreach ($timeline as $row) {
            $date = $row->postdate;
            $pubstate = $row->pubstate;
            $counter = $row->counter;

            $values[$pubstate][$date] = $counter;

            $others = array_diff($states, [$pubstate]);

            foreach ($others as $state) {
                if ((!isset($values[$state][$date]))) {
                    $values[$state][$date] = 0;
                }
            }
        }

        $state_success = new StdClass();
        $state_success->key = SelectControlHelper::getTextForEnum('success');
        $state_success->values = self::_listOfObjects($values['success']);

        $state_cronjob = new StdClass();
        $state_cronjob->key = SelectControlHelper::getTextForEnum('cronjob');
        $state_cronjob->values = self::_listOfObjects($values['cronjob']);

        $state_approve = new StdClass();
        $state_approve->key = SelectControlHelper::getTextForEnum('approve');
        $state_approve->values = self::_listOfObjects($values['approve']);

        $state_cancelled = new StdClass();
        $state_cancelled->key = SelectControlHelper::getTextForEnum('cancelled');
        $state_cancelled->values = self::_listOfObjects($values['cancelled']);

        $state_error = new StdClass();
        $state_error->key = SelectControlHelper::getTextForEnum('error');
        $state_error->values = self::_listOfObjects($values['error']);

        $result = [$state_success, $state_cronjob, $state_approve, $state_cancelled, $state_error];

        return $result;
    }

    /**
     * _listOfObjects.
     *
     * @param array $values Param
     *
     * @return array
     */
    private static function _listOfObjects($values)
    {
        $result = [];

        foreach ($values as $key => $value) {
            $o = [
                'x' => (int) \Joomla\CMS\Factory::getDate($key)->toUnix(),
                'y' => (int) $value,
            ];

            $result[] = (object) $o;
        }

        return $result;
    }
}
