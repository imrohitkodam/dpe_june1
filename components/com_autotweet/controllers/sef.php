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
 * AutotweetControllerSef.
 *
 * @since       1.0
 */
class AutotweetControllerSef extends ExtlyController
{
    /**
     * route.
     */
    public function route()
    {
        header('Content-type: text/plain');

        $url = base64_decode($this->input->get('url', 'index.php', 'BASE64'), true);

        @ob_end_clean();

        $routed_url = JRoute::_($url, false);

        if (RouteHelp::isMultilingual()) {
            $routed_url = str_replace('/component/autotweet/', '/', $routed_url);
        }

        echo base64_encode($routed_url);
        flush();

        $logger = AutotweetLogger::getInstance();
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'AutotweetControllerSef route: '.$url.' = '.$routed_url);

        \Joomla\CMS\Factory::getApplication()->close();
    }
}
