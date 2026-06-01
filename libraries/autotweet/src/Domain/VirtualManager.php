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
 * VirtualManager.
 *
 * @since       1.0
 */
final class VirtualManager
{
    public const EVERGREEN_TYPE_RANDOM = 1;
    public const EVERGREEN_TYPE_RANDOM_HITS = 2;
    public const EVERGREEN_TYPE_RANDOM_DATER = 3;
    public const EVERGREEN_TYPE_RANDOM_DATEO = 4;
    public const EVERGREEN_TYPE_SEQUENCE = 5;

    public const LAST_ADVANCED_ATTRS_SEQ = 'last_advanced_attrs_seq';

    /**
     * isWorking.
     *
     * @param JDate $now Params
     *
     * @return bool
     */
    public static function isWorking($now = null)
    {
        if (!$now) {
            $now = \Joomla\CMS\Factory::getDate();
        }

        $extension = self::loadExtension();

        if ($extension->xtform->get('works7x24', true)) {
            return true;
        }

        $dayofweek = $now->format('w');
        $working_days = $extension->xtform->get('working_days', []);

        if (false === array_search($dayofweek, $working_days, true)) {
            // Not working today
            return false;
        }

        $now_utc = $now->toSql();
        [$now_date, $now_time] = EParameter::getDateTimeParts($now_utc);

        $now_time = strtotime($now_utc);
        $start_time = $now_date.' '.$extension->xtform->get('start_time');
        $start_time = strtotime($start_time);
        $end_time = $now_date.' '.$extension->xtform->get('end_time');
        $end_time = strtotime($end_time);

        // Fix for 24hs overflow
        if ($end_time < $start_time) {
            $end_time += (24 * 3600);
        }

        $result = (($now_time >= $start_time) && ($now_time <= $end_time));

        return $result;
    }

    /**
     * getPublishUpDates.
     *
     * @param int $max Param
     *
     * @return string
     */
    public static function getPublishUpDates($max = 30)
    {
        $dates = [];
        $extension = self::loadExtension();
        $evergreenFreqMhdmd = $extension->xtform->get('evergreen_freq_mhdmd');

        if ((!$evergreenFreqMhdmd) || (empty($evergreenFreqMhdmd))) {
            return $dates;
        }

        $advancedattrs = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel');
        $advancedattrs->setState('evergreentype_id', PostShareManager::POSTTHIS_YES);
        $total = $advancedattrs->getTotal();

        if (0 === (int) $total) {
            return $dates;
        }

        $current_date = \Joomla\CMS\Factory::getDate();
        $dpcheck = EParameter::getComponentParam(CAUTOTWEETNG, 'dpcheck_time_intval', 12) * 3600;
        $current_unix = $current_date->toUnix() + $dpcheck;
        $datePublishUp = \Joomla\CMS\Factory::getDate();

        for ($i = 0; ($i < $max); ++$i) {
            $datePublishUp->setTimestamp($current_unix);
            $next = TextUtil::nextScheduledDate($evergreenFreqMhdmd, $datePublishUp);

            $dates[] = $next;
            $current_date = \Joomla\CMS\Factory::getDate($next);
            $current_unix = $current_date->toUnix();
        }

        return $dates;
    }

    /**
     * enqueueEvergreenMessage.
     *
     * @param int $max Params
     *
     * @return bool
     */
    public static function enqueueEvergreenMessage($max)
    {
        $extension = self::loadExtension();
        $evergreenType = $extension->xtform->get('evergreen_type', 1);
        $evergreenFreqMhdmd = $extension->xtform->get('evergreen_freq_mhdmd');

        $vmChannelRestriction = $extension->xtform->get('channelchooser');

        if ((!$evergreenFreqMhdmd) || (empty($evergreenFreqMhdmd))) {
            return false;
        }

        $automators = XTF0FModel::getTmpInstance('Automators', 'AutoTweetModel');
        $key = 'virtualmanager';

        if (!$automators->lastRunCheckFreqMhdmd($key, $evergreenFreqMhdmd)) {
            return false;
        }

        $advancedattrs = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel');
        $advancedattrs->setState('evergreentype_id', PostShareManager::POSTTHIS_YES);
        $n = (int) $advancedattrs->getTotal();

        if (0 === $n) {
            return false;
        }

        $result = false;

        for ($i = 0; ($i < $max); ++$i) {
            if (self::EVERGREEN_TYPE_RANDOM === (int) $evergreenType) {
                self::enqueueEvergreenRandom($n, $vmChannelRestriction);
                $result = true;
            }

            if (self::EVERGREEN_TYPE_SEQUENCE === (int) $evergreenType) {
                self::enqueueEvergreenSequence($vmChannelRestriction);
                $result = true;
            }
        }

        return $result;
    }

    /**
     * enqueueEvergreenRandom.
     *
     * @param int   $n                    Params
     * @param array $vmChannelRestriction Params
     */
    private static function enqueueEvergreenRandom($n, $vmChannelRestriction)
    {
        $i = rand(1, $n);

        $advancedattrs = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel');
        $advancedattrs->setState('evergreentype_id', PostShareManager::POSTTHIS_YES);
        $advancedattrs->setState('limitstart', $i - 1);
        $advancedattrs->setState('limit', 1);

        $result = $advancedattrs->getList();

        if (!empty($result)) {
            $advancedattrs = $result[0];
            self::activateEvergreen($advancedattrs->request_id);
            self::applyVmChannelRestriction($advancedattrs, $vmChannelRestriction);
        }
    }

    /**
     * enqueueEvergreenSequence.
     *
     * @param array $vmChannelRestriction Params
     */
    private static function enqueueEvergreenSequence($vmChannelRestriction)
    {
        $i = self::getLastAdvancedattrsSeq();

        $advancedattrs = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel');
        $advancedattrs->setState('evergreentype_id', PostShareManager::POSTTHIS_YES);
        $advancedattrs->setState('nextseq', $i);
        $advancedattrs->setState('limit', 1);
        $result = $advancedattrs->getList();

        if (!empty($result)) {
            $advancedattrs = $result[0];
            self::activateEvergreen($advancedattrs->request_id);
            self::setLastAdvancedattrsSeq($advancedattrs->id);
            self::applyVmChannelRestriction($advancedattrs, $vmChannelRestriction);
        } else {
            // Wrapped, just continue from the beginning
            $advancedattrs->setState('nextseq', 0);
            $result = $advancedattrs->getList();

            if (!empty($result)) {
                $advancedattrs = $result[0];
                self::activateEvergreen($advancedattrs->request_id);
                self::setLastAdvancedattrsSeq($advancedattrs->id);
                self::applyVmChannelRestriction($advancedattrs, $vmChannelRestriction);
            }
        }
    }

    /**
     * activateEvergreen.
     *
     * @param int $request_id Param
     */
    private static function activateEvergreen($request_id)
    {
        $model = XTF0FModel::getTmpInstance('Requests', 'AutoTweetModel');
        $request = $model->getItem($request_id);

        $now = \Joomla\CMS\Factory::getDate();
        $request->xtform->set('evergreen_generated', true);

        $request->save(
            [
                'published' => 0,
                'publish_up' => $now->toSql(),
                'params' => (string) $request->xtform,
            ]
        );
    }

    /*
     Just in case Priority
    $n = count($evergreens);

    $n = $n * $n;
    $i = rand(1, $n);
    $i = round(sqrt($arg)) + 1;
    */

    /**
     * loadExtension.
     *
     * @return Table
     */
    private static function loadExtension()
    {
        static $extension = null;

        if (!$extension) {
            $manager = EExtensionHelper::getExtensionId('system', 'autotweetautomator');

            // Load the model
            $extensions = XTF0FModel::getTmpInstance('Extensions', 'ExtlyModel');
            $extension = $extensions->getItem($manager);
        }

        return $extension;
    }

    /**
     * getLastAdvancedattrsSeq.
     *
     * @return int
     */
    private static function getLastAdvancedattrsSeq()
    {
        $extension = self::loadExtension();

        return $extension->xtform->get(self::LAST_ADVANCED_ATTRS_SEQ, 0);
    }

    /**
     * setLastAdvancedattrsSeq.
     *
     * @param int $advancedattrs_id Param
     *
     * @return string
     */
    private static function setLastAdvancedattrsSeq($advancedattrs_id)
    {
        $extension = self::loadExtension();
        $extension->xtform->set(self::LAST_ADVANCED_ATTRS_SEQ, $advancedattrs_id);

        $allData = $extension->getData();
        $allData['xtform'] = $extension->xtform;

        $extensions = XTF0FModel::getTmpInstance('Extensions', 'ExtlyModel');
        $extensions->save($allData);
    }

    /**
     * setLastAdvancedattrsSeq.
     *
     * @param object $data                 Param
     * @param array  $vmChannelRestriction Params
     *
     * @return string
     */
    private static function applyVmChannelRestriction($data, $vmChannelRestriction)
    {
        if (empty($vmChannelRestriction)) {
            return;
        }

        $data->xtform = EForm::paramsToRegistry($data);
        $postChannelRestriction = $data->xtform->get('channels');
        $hasPostChannelRestriction = !empty($postChannelRestriction);

        if ($hasPostChannelRestriction) {
            return;
        }

        $data->xtform->set('channels', $vmChannelRestriction);
        $data->xtform->set('channels_text', TextUtil::createChannelsText($vmChannelRestriction));

        $advancedattrs = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel');
        $advancedattrs->save($data);
    }
}
