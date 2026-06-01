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

// No direct access
defined('_JEXEC') || exit('Restricted access');

/**
 * SimpleScriptManager.
 *
 * @since       1.0
 */
class SimpleScriptManager
{
    /**
     * initApp.
     *
     * @param string $version         Param
     * @param string $extensionmainjs Param
     * @param array  $dependencies    {key2 => {key1, keyi}}
     * @param array  $paths           {key1 => pathjs1, key2 => pathjs2}
     */
    public function initApp($version = null, $extensionmainjs = null, $dependencies = [], $paths = [])
    {
        JHtml::_('jquery.framework');
        JHtml::_('bootstrap.framework');
        $this->_addScript($extensionmainjs.'?'.$version);
    }

    /**
     * _addScript.
     *
     * @param string $file Param
     */
    private function _addScript($file)
    {
        static $add_postRequireHook = true;

        if (preg_match('#^media/([^/]+)/js/([^\?]+)(\?[0-9]\.[0-9]\.[0-9])?#', $file, $matches)) {
            $extension = $matches[1];
            $localfile = $matches[2];
            $version = null;

            if (4 === count($matches)) {
                $version = $matches[3];
            }

            $include = JHtml::_('script', $extension.'/'.$localfile, false, true, true);

            if ($include) {
                JFactory::getDocument()->addScript($include.$version, 'text/javascript', false, false);
            } else {
                JFactory::getDocument()->addScript($file);
            }
        } else {
            JFactory::getDocument()->addScript($file);
        }

        if ($add_postRequireHook) {
            $add_postRequireHook = false;
            JFactory::getDocument()->addScriptDeclaration(
                'if ((window.postRequireHook) && (!window.run_postRequireHook)) {window.run_postRequireHook = true;jQuery(document).ready(window.postRequireHook);}'
            );
        }
    }
}
