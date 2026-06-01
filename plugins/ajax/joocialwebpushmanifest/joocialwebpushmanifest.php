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

// Import library dependencies
jimport('joomla.plugin.plugin');

/**
 * plgAjaxJoocialWebpushManifest class.
 *
 * @since       1.0
 */
class PlgAjaxJoocialWebpushManifest extends \Joomla\CMS\Plugin\CMSPlugin
{
    /**
     * onAjaxJoocialWebpushManifest.
     */
    public function onAjaxJoocialWebpushManifest()
    {
        $this->params->get('onesignal_custom_notify_button');
        $manifest = [];

        $sitename = \Joomla\CMS\Factory::getConfig()->get('sitename');
        $metaDesc = \Joomla\CMS\Factory::getConfig()->get('MetaDesc');

        if (empty($metaDesc)) {
            $metaDesc = $sitename;
        }

        $manifest['name'] = $this->params->get('name', $metaDesc);
        $manifest['short_name'] = $this->params->get('short_name', $sitename);

        $pushservice = $this->params->get('pushservice');

        switch ($pushservice) {
            case 'onesignal':
                $manifest['start_url'] = '/';
                $manifest['gcm_sender_id'] = '482941778795';
                $manifest['DO_NOT_CHANGE_GCM_SENDER_ID'] = 'Do not change the GCM Sender ID';

                break;
            case 'pushwoosh':
                $manifest['gcm_sender_id'] = $this->params->get('pushwoosh_gcm_sender_id');
                $manifest['gcm_user_visible_only'] = true;

                break;
        }

        $manifest['display'] = 'standalone';

        return json_encode($manifest);
    }
}
