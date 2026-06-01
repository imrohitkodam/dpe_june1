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
 * AutotweetControllerDefault.
 *
 * @since       1.0
 */
class AutotweetControllerDefault extends ExtlyController
{
    /**
     * Method to get a reference to the current view and load it if necessary.
     *
     * @param string $name   The view name. Optional, defaults to the controller name.
     * @param string $type   The view type. Optional.
     * @param string $prefix The class prefix. Optional.
     * @param array  $config Configuration array for view. Optional.
     *
     * @return XTF0FView reference to the view or an error
     */
    public function getView($name = '', $type = '', $prefix = '', $config = [])
    {
        $config['linkbar_style'] = 'classic';

        return parent::getView($name, $type, $prefix, $config);
    }

    /**
     * Returns true if there is a redirect set in the controller.
     *
     * @return bool
     */
    public function hasRedirect()
    {
        return (!empty($this->redirect)) && ('json' !== $this->input->getCmd('format', 'html'));
    }

    /**
     * Redirects the browser or returns false if no redirect is set.
     *
     * @return bool false if no redirect exists
     */
    public function redirect()
    {
        if ($this->redirect) {
            $app = \Joomla\CMS\Factory::getApplication();
            $app->enqueueMessage($this->message, $this->messageType);

            // Fix for Joomla 3.7
            XTF0FPlatform::getInstance()->setHeader('Status', '303 See other', true);
            $app->redirect($this->redirect);

            return true;
        }

        return false;
    }

    protected function onBeforeBatch()
    {
        return $this->onBeforeAccessspecial();
    }

    protected function onBeforePurge()
    {
        return $this->onBeforeRemove();
    }

    protected function onBeforePublishAjaxAction()
    {
        return $this->onBeforePublish();
    }

    protected function onBeforeCancelAjaxAction()
    {
        return $this->onBeforeUnpublish();
    }

    protected function onBeforeProcess()
    {
        return $this->onBeforePublish();
    }
}
