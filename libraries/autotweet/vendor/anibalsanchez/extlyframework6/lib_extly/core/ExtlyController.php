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
 * ExtlyController.
 *
 * @since       1.0
 */
class ExtlyController extends XTF0FController
{
    /**
     * Redirects the browser or returns false if no redirect is set.
     *
     * @return bool false if no redirect exists
     */
    public function redirect()
    {
        if ($this->redirect) {
            $app = JFactory::getApplication();
            $app->enqueueMessage($this->message, $this->messageType);

            // Fix for Joomla 3.7
            XTF0FPlatform::getInstance()->setHeader('Status', '303 See other', true);
            $app->redirect($this->redirect);

            return true;
        }

        return false;
    }
}
