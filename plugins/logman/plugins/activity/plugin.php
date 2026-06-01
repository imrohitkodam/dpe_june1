<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Plugin Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanPluginsActivityPlugin extends ComLogmanModelEntityActivity
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        ComLogmanActivityTranslator::loadSysIni($this->title);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('object_table' => 'extensions', 'object_column' => 'extension_id'));
        parent::_initialize($config);
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        $config->append(array(
            'url'       => array('admin' => 'option=com_plugins&task=plugin.edit&extension_id=' . $this->row),
            'translate' => true
        ));

        parent::_objectConfig($config);
    }
}