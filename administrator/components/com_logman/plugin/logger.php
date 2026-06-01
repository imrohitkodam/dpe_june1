<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Logger LOGman Plugin
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanPluginLogger extends ComLogmanPluginAbstract implements ComLogmanPluginLoggerInterface
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
     * Tells if current request has been made by a crawler
     *
     * @var boolean
     */
    static $_is_crawler;

    public function __construct(&$dispatcher, $config = array())
    {
        $name = $config['name'];

        $file = sprintf('%1$s/logman/%2$s/%2$s.xml', JPATH_PLUGINS, $name);

        if (file_exists($file))
        {
            $manifest = simplexml_load_file($file);

            if (isset($manifest->package)) {
                $this->_package = (string) $manifest->package;
            }  else {
                $this->_package = $name; // Assume plugin name.
            }

            $actions = array();

            if (isset($manifest->resources))
            {
                foreach ($manifest->resources->children() as $resource)
                {
                    $name = (string) $resource['name'];

                    $actions[$name] = array();

                    foreach ($resource->actions->children() as $action) {
                        $actions[$name][] = (string) $action;
                    }
                }
            }
            elseif (isset($manifest->actions))
            {
                $name           = KStringInflector::singularize($name);
                $actions[$name] = array();

                foreach ($manifest->actions->children() as $action) {
                    $actions[$name][] = (string) $action;
                }
            }

            $this->_actions = $actions;

            // Do not connect to dispatcher if actions is not set (legacy plugins)
            if (empty($this->_actions)) {
                $config['auto_connect'] = false;
            }
        }

        parent::__construct($dispatcher, $config);
    }

    public function update(&$args)
    {
        // Avoid dispatching events if a logger plugin cannot log activities

        if ($this->canLog()) {
            return parent::update($args);
        }
    }

    /**
     * BC method for plugins making use of older API
     *
     * @param array $data The activity data.
     * @throws Exception
     * @return object The activity row.
     */
    public function log($data = array())
    {
        // Forward call to logActivity
        return $this->logActivity($data);
    }

    /**
     * Adds/logs an activity row.
     *
     * @param array $data The activity data.
     * @throws Exception
     * @return object The activity row.
     */
    final public function logActivity($data = array())
    {
        $result = false;
        $config = new KObjectConfig($data);

        if ($this->_beforeLogActivity($config) !== false)
        {
            try {
                $result = $this->_getController()->add(KObjectConfig::unbox($config->activity));
            }
            catch (Exception $e)
            {
                if (JDEBUG) {
                    throw $e;
                }
            }
        }

        return $result;
    }

    /**
     * Activity controller getter
     *
     * @param array $config An optional configuration array for the controller.
     *
     * @return KControllerInterface The activity controller
     */
    protected function _getController($config = array())
    {
        return $this->getObject('com://admin/logman.controller.activity', $config);
    }

    /**
     * Before log activity event handler.
     *
     * Set the activity data based on the passed config object, and set default based on the context we are logging in.
     *
     * @param KObjectConfig $config The configuration object.
     * @return bool Return false for preventing an activity from being logged.
     */
    protected function _beforeLogActivity(KObjectConfig $config)
    {
        $config->append(array(
            'extension'     => 'com',
            'application' => JFactory::getApplication()->isClient('administrator') ? 'admin' : 'site'));

        $activity = array();

        $activity['type']     = $config->extension;
        $activity['package']  = $config->object->package;
        $activity['name']     = $config->object->type;
        $activity['row']      = $config->object->id;
        $activity['title']    = $config->object->name;
        $activity['metadata'] = $config->object->metadata;
        $activity['status']   = $config->result;
        $activity['action']   = $config->verb;

        if ($actor = $config->actor) {
            $activity['created_by'] = $actor;
        }

        $activity['application'] = $config->application;

        $config->activity = $activity;

        return true;
    }

    public function getActions()
    {
        return $this->_actions;
    }

    public function getResources()
    {
        $resources = array();
        $actions   = $this->getActions();

        if (!empty($actions)) {
            $resources = array_keys($actions);
        }

        return $resources;
    }

    public function getPackage()
    {
        return $this->_package;
    }

    /**
     * Adds/logs a route.
     *
     * @param array $data The route data
     *
     * @return mixed The route row if success, false otherwise.
     */
    public function logRoute($data)
    {
        $result = false;

        try {
            $result = $this->getObject('com://admin/logman.controller.route')->add($data);
        }
        catch (Exception $e)
        {
            if (JDEBUG) {
                throw $e;
            }
        }

        return $result;
    }

    /**
     * Adds/logs an impression.
     *
     * @param array $data The impression data
     *
     * @return mixed The impression row if success, false otherwise.
     */
    public function logImpression($data)
    {
        $result = false;

        try {
            $result = $this->_getImpressionController()->add($data);
        }
        catch (Exception $e)
        {
            if (JDEBUG) {
                throw $e;
            }
        }

        return $result;
    }

    /**
     * Determines if a plugin can log anything
     *
     * @return boolean Returns true if it can, false otherwise
     */
    public function canLog()
    {
        return !$this->_isCrawler();
    }

    protected function _isCrawler()
    {
        if (!isset(self::$_is_crawler))
        {
            $result = false;

            $provider = $this->getObject('com://admin/logman.plugin.crawler.provider');

            $crawlers = $provider->getCompiled('crawlers');

            $exclusions = $provider->getCompiled('exclusions');

            $headers = $this->getObject('request')->getHeaders();

            $user_agents = '';

            foreach ($provider->getData('headers') as $header)
            {
                if (strpos($header, 'HTTP_') === 0) {
                    $header = str_replace('HTTP_', '', $header);
                }

                if ($headers->has($header)) {
                    $user_agents .= $headers->get($header) . ' ';
                }
            }

            $user_agents = trim(preg_replace(
                "/{$exclusions}/i",
                '',
                $user_agents
            ));

            if ($user_agents != '')
            {
                preg_match("/{$crawlers}/i", $user_agents, $matches);

                if ($matches) {
                    $result = true;
                }
            }

            self::$_is_crawler = $result;
        }

        return self::$_is_crawler;
    }

    /**
     * Impression controller getter
     *
     * @param array $config An optional configuration array for the controller.
     *
     *  @return KControllerInterface The activity controller
     */
    protected function _getImpressionController($config = array())
    {
        return $this->getObject('com://admin/logman.controller.impression', $config);
    }
}