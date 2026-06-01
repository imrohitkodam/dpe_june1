<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Weblinks LOGman Plugin.
 *
 * Provides event handlers for dealing with com_weblinks events.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanWeblinks extends ComLogmanPluginJoomla
{
    /**
     * Context mapping.
     *
     * @var array
     */
    protected $_aliases = array(
        'com_weblinks.form' => 'com_weblinks.weblink'
    );

    public function onContentAfterSave($context, $content, $isNew)
    {
        // Map inconsistent contexts.
        if (isset($this->_aliases[$context])) {
            $context = $this->_aliases[$context];
        }

        parent::onContentAfterSave($context, $content, $isNew);
    }

    protected function _getItems($ids, KObjectConfig $config)
    {
        $config->append(array('prefix' => 'WeblinksTable'));

        return parent::_getItems($ids, $config);
    }
}