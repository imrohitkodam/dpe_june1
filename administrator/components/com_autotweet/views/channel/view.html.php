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

use XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\ScriptHelper;

/**
 * AutotweetViewChannel.
 *
 * @since       1.0
 */
class AutotweetViewChannel extends AutoTweetDefaultView
{
    /**
     * onAdd.
     *
     * @param string $tpl Param
     */
    public function onAdd($tpl = null)
    {
        $result = parent::onAdd($tpl);

        Extly::loadAwesome();

        $file = EHtml::getRelativeFile('js', 'com_autotweet/channel.min.js');

        if ($file) {
            ScriptHelper::addScriptVersion(\Joomla\CMS\Uri\Uri::root().'media/com_autotweet/js/cryptojslib/core-min.js');
            ScriptHelper::addScriptVersion(\Joomla\CMS\Uri\Uri::root().'media/com_autotweet/js/cryptojslib/enc-base64-min.js');

            $dependencies = [];
            $dependencies['channel'] = ['extlycore'];
            $this->assign('channeljs', $file);
            Extly::initApp(CAUTOTWEETNG_VERSION, $file, $dependencies);
        }

        $postsModel = XTF0FModel::getTmpInstance('Posts', 'AutoTweetModel');
        $postsModel->set(
            'pubstate',
            [
                PostShareManager::POST_SUCCESS,
                PostShareManager::POST_ERROR,
            ]
        );

        $postsModel->set('channel', $this->item->id);
        $postsModel->set('filter_order', 'id');
        $postsModel->set('filter_order_Dir', 'DESC');
        $postsModel->set('limit', 1);
        $posts = $postsModel->getItemList();

        $alert_message = '';
        $alert_style = 'alert-info';

        if (count($posts) > 0) {
            $lastpost = $posts[0];

            if (PostShareManager::POST_ERROR === $lastpost->pubstate) {
                $alert_style = 'alert-error';
            }

            $alert_message = $lastpost->postdate.' - '.JText::_($lastpost->resultmsg);
        }

        $this->assign('alert_message', $alert_message);
        $this->assign('alert_style', $alert_style);

        return $result;
    }
}
