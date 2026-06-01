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

XTF0FModel::getTmpInstance('Plugins', 'AutoTweetModel');

$result = [];

if ($count = count($this->items)) {
    foreach ($this->items as $item) {
        $advancedAttr = AdvancedAttributesHelper::getByRequest($item->id);
        $native_object = TextUtil::json_decode($item->native_object);
        $has_error = ((isset($native_object->error)) && ($native_object->error));

        $description = htmlentities($item->description, \ENT_COMPAT, 'UTF-8');
        $description = TextUtil::truncString($description, AutoTweetDefaultView::MAX_CHARS_TITLE_SHORT_SCREEN, true);
        $description = html_entity_decode($description);

        $elem = [
            'id' => $item->id,
            'title' => $description,
            'start' => JHtml::_('date', $item->publish_up, DateTime::RFC3339),
            'className' => ($item->published ?
                    ($has_error ? 'req-error' : 'req-success') :
                    ($has_error ? 'req-warning' : 'req-info')),
        ];

        $elem['url'] = 'index.php?option=com_autotweet&view=composer&req-id='.$item->id;

        if (!empty($item->image_url)) {
            $elem['image_url'] = TextUtil::renderUrl($item->image_url);
        }

        $result[] = $elem;

        // Agenda
        if ((isset($advancedAttr->agenda)) && (!empty($advancedAttr->agenda))) {
            foreach ($advancedAttr->agenda as $next_date) {
                if ($next_date === $item->publish_up) {
                    continue;
                }

                $elem['start'] = JHtml::_('date', $next_date, DateTime::RFC3339);
                $result[] = $elem;
            }
        }

        // Repeats
        if ((isset($advancedAttr->unix_mhdmd)) && (!empty($advancedAttr->unix_mhdmd))) {
            $repeat_until = null;

            if (isset($advancedAttr->repeat_until)) {
                $repeat_until = $advancedAttr->repeat_until;
            }

            $dates = AdvancedAttributesHelper::getRepeatPublishUpDates($advancedAttr->unix_mhdmd, $repeat_until);

            foreach ($dates as $next_date) {
                if ($next_date === $item->publish_up) {
                    continue;
                }

                $elem['start'] = JHtml::_('date', $next_date, DateTime::RFC3339);
                $result[] = $elem;
            }
        }
    }
}

// VirtualManager Evergreens
$dates = VirtualManager::getPublishUpDates();

if (!empty($dates)) {
    foreach ($dates as $next_date) {
        $elem = [
            'id' => 0,
            'title' => JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_EVERGREEN'),
            'start' => JHtml::_('date', $next_date, DateTime::RFC3339),
            'className' => 'req-info',
        ];
        $result[] = $elem;
    }
}

echo json_encode($result);
