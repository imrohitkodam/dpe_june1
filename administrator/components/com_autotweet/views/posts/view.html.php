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
 * AutotweetViewPosts.
 *
 * @since       1.0
 */
class AutotweetViewPosts extends AutoTweetDefaultView
{
    protected $isModule = false;

    /**
     * Class constructor.
     *
     * @param array $config Configuration parameters
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $layout = $this->input->get('layout', null, 'cmd');
        $this->isModule = ('module' === $layout);
    }

    /**
     * onBrowse.
     *
     * @param string $tpl Param
     */
    protected function onBrowse($tpl = null)
    {
        Extly::initApp(CAUTOTWEETNG_VERSION);
        Extly::loadAwesome();

        if (!$this->isModule) {
            // When in interactive browsing mode, save the state to the session
            $this->getModel()->savestate(1);
        }

        return $this->onDisplay($tpl);
    }
}
