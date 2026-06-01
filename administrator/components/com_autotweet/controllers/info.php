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

require_once 'default.php';

/**
 * AutoTweetControllerInfo - The Control Panel controller class.
 *
 * @since       1.0
 */
class AutoTweetControllerInfo extends AutotweetControllerDefault
{
    /**
     * onBeforeBrowse.
     */
    public function onBeforeBrowse()
    {
        $result = parent::onBeforeBrowse();

        if ($result) {
            // Run the automatic update site refresh
            $updateModel = XTF0FModel::getTmpInstance('LiveUpdates', 'AutoTweetModel');
            $updateModel->refreshUpdateSite();
        }

        return $result;
    }
}
