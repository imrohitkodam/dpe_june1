<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Component/Config Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanConfigActivityComponent extends ComLogmanModelEntityActivity
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        ComLogmanActivityTranslator::loadSysIni($this->title);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'object_table'     => 'extensions',
            'object_column'    => 'extension_id',
            'format'           => '{actor} {action} {object} {object.type}'
        ));

        parent::_initialize($config);
    }

    protected function _findActivityObject()
    {
        $result = parent::_findActivityObject();

        if ($result)
        {
            // Check if an entry point file exists.
            $component = $this->title;
            $parts     = explode('_', $component);

            $result = (bool) file_exists(JPATH_ADMINISTRATOR . '/components/' .
                                         $component . '/' . $parts[1] . '.php');
        }

        return $result;
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        $config->append(array(
            'url'  => array('admin' => 'option=' . $this->title),
            'type' => array('objectName' => 'settings', 'object' => true)
        ));

        parent::_objectConfig($config);
    }
}