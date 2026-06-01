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
 * Advancedattrs.
 *
 * @since       1.0
 */
final class Advancedattrs
{
    public $description = '';

    public $hashtags = '';

    public $fulltext = '';

    public $postthis;

    public $evergreen;

    public $agenda = [];

    public $unix_mhdmd = '';

    public $repeat_until = '';

    public $image = '';

    public $image_url = '';

    public $channels = '';

    public $channels_text = '';

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->postthis = EParameter::getComponentParam(CAUTOTWEETNG, 'joocial_postthis', PostShareManager::POSTTHIS_DEFAULT);

        $this->evergreen = PostShareManager::POSTTHIS_NO;
    }
}

/**
 * AutotweetModelAdvancedattrs.
 *
 * @since       1.0
 */
class AutotweetModelAdvancedattrs extends XTF0FModel
{
    /**
     * getAdvancedattrs.
     *
     * @return object
     */
    public function getAdvancedattrs()
    {
        return new Advancedattrs();
    }

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
        $browse = $this->getState('browse', false);

        $query = XTF0FQueryAbstract::getNew($db)
            ->from($db->quoteName('#__autotweet_advanced_attrs').'  a');

        if ($browse) {
            $query->select('r.*, a.client_id, a.option, a.id as attr_id, a.ref_id attr_ref_id');
        } else {
            $query->select('a.*');
        }

        $fltOption = $this->getState('option-filter', null, 'cmd');

        if ($fltOption) {
            if ('com_flexicontent' === $fltOption) {
                $fltOption = 'com_content';
            }

            $query->where($db->qn('a.option').' = '.$db->q($fltOption));
        }

        $fltRefId = $this->getState('ref_id');

        if ($fltRefId) {
            $query->where($db->qn('a.ref_id').' = '.$db->q($fltRefId));
        }

        $fltRequestId = $this->getState('request_id', null, 'int');

        if ($fltRequestId) {
            $query->where($db->qn('a.request_id').' = '.$db->q($fltRequestId));
        }

        $fltRequestIds = $this->getState('request_ids', null, 'array');

        if (($fltRequestIds) && (!empty($fltRequestIds))) {
            $ids = implode(',', $fltRequestIds);
            $query->where($db->qn('a.request_id').' in ('.$ids.')');
        }

        $fltEvergreen = $this->getState('evergreentype_id');

        if ($fltEvergreen) {
            if (PostShareManager::POSTTHIS_YES_ALL === (int) $fltEvergreen) {
                $query->where($db->qn('a.evergreentype_id').' = '.$db->q(PostShareManager::POSTTHIS_YES));
            } else {
                $query->where($db->qn('a.evergreentype_id').' = '.$db->q($fltEvergreen));
            }

            if (PostShareManager::POSTTHIS_YES === (int) $fltEvergreen) {
                if ($browse) {
                    $query->leftJoin($db->quoteName('#__autotweet_requests').' r ON (a.request_id = r.id)');
                } else {
                    $query->from($db->quoteName('#__autotweet_requests').' r');
                    $query->where('a.request_id = r.id');
                    $query->where('r.published = 1');
                }
            }
        }

        $fltPostThis = $this->getState('postthis');

        if ($fltPostThis) {
            $value = ['postthis' => ''.$fltPostThis];
            $json_value = json_encode($value);
            $json_value = str_replace('{', '', $json_value);
            $json_value = str_replace('}', '', $json_value);
            $json_value = '%'.$json_value.'%';

            $query->where($db->qn('a.params').' LIKE \''.$json_value.'\'');
        }

        $fltNextseq = $this->getState('nextseq');

        if ($fltNextseq) {
            $query->where($db->qn('a.id').' < '.$db->q($fltNextseq));
        }

        $fltPublishup = $this->getState('publish_up', null, 'date');

        if ($fltPublishup) {
            $fltPublishup = $fltPublishup.'%';
            $query->where($db->qn('r.publish_up').' LIKE '.$db->q($fltPublishup));
        }

        $search = $this->getState('search', null);

        if ($search) {
            $search = '%'.$search.'%';
            $query->where('('.$db->qn('r.id').' LIKE '.$db->quote($search)
                    .' OR '.$db->qn('r.ref_id').' LIKE '.$db->quote($search)
                    .' OR '.$db->qn('r.description').' LIKE '.$db->quote($search)
                    .' OR '.$db->qn('r.url').' LIKE '.$db->quote($search).')');
        }

        $fltPlugin = $this->getState('plugin', null, 'string');

        if ($fltPlugin) {
            $query->where($db->qn('r.plugin').' = '.$db->q($fltPlugin));
        }

        $order = $this->getState('filter_order', 'a.id', 'cmd');
        $dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
        $query->order($order.' '.$dir);

        return $query;
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

            if (!AdvancedAttributesHelper::moveToEvergeen($this->id_list, $userid, $evergreen)) {
                $this->setError('Advancedattrs::moveToEvergeen failed');

                return false;
            }
        }

        return true;
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
}
