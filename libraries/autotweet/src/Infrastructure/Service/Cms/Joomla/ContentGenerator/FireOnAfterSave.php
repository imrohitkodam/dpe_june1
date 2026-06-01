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

namespace PerfectPublisher\Infrastructure\Service\Cms\Joomla\ContentGenerator;

trait FireOnAfterSave
{
    private function onAfterSave($id, $context)
    {
        $article = \JTable::getInstance('content');
        $article->load($id);

        $plugin = \XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\DispatcherHelper::getPlugin(
            'system',
            'autotweetcontent'
        );

        if (empty($plugin)) {
            return;
        }

        $plugin->onContentAfterSave(
            $context,
            $article,
            true
        );
    }
}
