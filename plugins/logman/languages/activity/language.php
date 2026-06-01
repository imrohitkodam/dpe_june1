<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Language Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanLanguagesActivityLanguage extends ComLogmanModelEntityActivity
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
                'format'        => '{actor} {action} {object.subtype} {object.type} title {object}',
                'object_table'  => 'languages',
                'object_column' => 'lang_id')
        );

        parent::_initialize($config);
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        $config->append(array(
            'subtype' => array('objectName' => 'content', 'object' => true),
            'url'     => array('admin' => 'option=com_languages&view=language&layout=edit&lang_id=' . $this->row)
        ));

        parent::_objectConfig($config);
    }
}