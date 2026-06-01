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
 * AutoTweetDispatcher.
 *
 * @since       1.0
 */
class AutotweetDispatcher extends XTF0FDispatcher
{
    public $defaultView = 'cpanels';

    /**
     * onBeforeDispatch.
     */
    public function onBeforeDispatch()
    {
        $result = parent::onBeforeDispatch();

        if (($result) && (!XTF0FPlatform::getInstance()->isCli()) && ($this->isHtml())) {
            $view = $this->input->getCmd('view');
            Extly::loadStyle(false, (('composer' !== $view) && ('itemeditor' !== $view)));

            JHtml::stylesheet(
                'com_autotweet/style.min.css',
                [
                    'version' => 'auto',
                    'relative' => true,
                    'detectDebug' => false,
                ]
            );
        }

        return $result;
    }

    private function isHtml()
    {
        return 'html' === \Joomla\CMS\Factory::getDocument()->getType();
    }
}
