<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * API LOGman Plugin
 *
 * Provides an interface to wrap API endpoints around plugins. Its main use is to be
 * able to query API data through the plugins model.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanPluginApi extends KObject implements ComLogmanPluginLoggerInterface
{
    /**
     * Package handled by the plugin.
     *
     * @var array
     */
    protected $_package;

    /**
     * Actions per resource logged by the plugin
     *
     * @var array
     */
    protected $_actions;

    /**
     * The plugin name
     *
     * @var string
     */
    protected $_name;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_actions = KObjectConfig::unbox($config->actions);
        $this->_package = $config->package;

        if (!$this->_package) {
            throw new RuntimeException('Package not set');
        }

        $translator = $this->getObject('translator');

        $this->_name = $translator->translate('LOGMAN PACKAGE API', array('package' => $translator->translate($this->_package)));
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('actions' => array()));
        parent::_initialize($config);
    }

    /**
     * Adds/logs an activity row.
     *
     * @param array $data The activity data.

     * @return object The activity row.
     */
    public function log($data = array())
    {
        // Do nothing.
    }

    /**
     * Actions getter.
     *
     * @return array A list of actions logged by the plugin
     */
    public function getActions()
    {
        return $this->_actions;
    }

    /**
     * Resources getter.
     *
     * @return array A list of resources handled by the plugin.
     */
    public function getResources()
    {
        $resources = array();
        $actions   = $this->getActions();

        if (!empty($actions)) {
            $resources = array_keys($actions);
        }

        return $resources;
    }

    /**
     * Package getter.
     *
     * @return string The name of the package handled by the plugin.
     */
    public function getPackage()
    {
        return $this->_package;
    }

    /**
     * Tells if the plugin is a logger.
     *
     * @return bool True if the plugin type is logger, false otherwise.
     */
    public function isLogger()
    {
        return true;
    }

    /**
     * Get the a parameter
     *
     * @param  string $name    The name of the setting parameter.
     * @param null    $default The default value.
     * @return mixed The parameter or default value.
     */
    public function getParameter($name, $default = null)
    {
        return $default;
    }

    /**
     * Name getter.
     *
     * @return string The plugin name.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Tells if the plugin is enabled
     *
     * @return bool True if enabled, false otherwise
     */
    public function isEnabled()
    {
        return true;
    }
}