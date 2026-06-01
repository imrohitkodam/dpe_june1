<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Plugins Model.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanModelPlugins extends KModelAbstract
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->getState()
             ->insert('api', 'boolean')
             ->insert('logger', 'boolean')
             ->insert('name', 'cmd')
             ->insert('package', 'cmd');
    }

    protected function _actionFetch(KModelContext $context)
    {
        //$iterator = new DirectoryIterator(JPATH_PLUGINS . '/logman');

        $state = $this->getState();

        $result = array();

        $adapter = $this->getObject('lib:database.adapter.mysqli');

        $query = $this->getObject('lib:database.query.select');

        $query->table('extensions')
              ->columns(array('name' => 'element', 'type' => 'folder', 'params'))
              ->where('type = :type')
              ->where('state IN :state')
              ->where('folder = :folder')
              ->bind(array('type' => 'plugin', 'state' => array(0, 1), 'folder' => 'logman'));

        if ($name = $state->name) {
            $query->where('element = :element')->bind(array('element' => $name));
        }

        $plugins = $adapter->select($query, KDatabase::FETCH_OBJECT_LIST);

        foreach ($plugins as $plugin)
        {
            // We cannot rely on the plugin class locator. It doesn't work for Joomla plugins.
            // We need to load classes on our own.
            $path = JPATH_PLUGINS . sprintf('/logman/%1$s/%1$s.php', $plugin->name);

            if (file_exists($path))
            {
                require_once $path;

                $class_name = sprintf('PlgLogman%s', ucfirst($plugin->name));

                if (version_compare(JVERSION, '4', '<')) {
                    $dispatcher = JEventDispatcher::getInstance();
                } else {
                    $dispatcher = JFactory::getApplication()->getDispatcher();
                }

				$instance = new $class_name($dispatcher, array(
					'name'         => $plugin->name,
					'type'         => $plugin->type,
					'params'       => $plugin->params,
					'auto_connect' => false));

                if ($state->logger && !$instance->isLogger()) continue;

                if ($instance instanceof ComLogmanPluginLoggerInterface) {
                    if ($state->package && !in_array($instance->getPackage(), (array) $state->package)) continue;
                }

                $result[] = $instance;
            }

        }

        if ($state->api && ($plugins = $this->_getApiPlugins($context))) {
            $result = array_merge($result, $plugins);
        }

        return $result;
    }

    protected function _getApiPlugins(KModelContextInterface $context)
    {
        $state = $context->getState();

        $plugins = array();

        $config = $this->getObject('com://admin/logman.model.configs')->fetch();

        if (isset($config['_logman']['api_actions']))
        {
            $api_actions = $config['_logman']['api_actions'];

            foreach ($api_actions as $package => $actions)
            {
                if ($state->package && !in_array($package, (array) $state->package)) continue;

                $plugins[] = $this->getObject('com://admin/logman.plugin.api', array('actions' => $actions, 'package' => $package));
            }
        }

        return $plugins;
    }

    protected function _beforeFetch(KModelContextInterface $context)
    {
        $state = $this->getState();

        if ($state->package) {
            $state->logger = true;
        }
    }

    protected function _actionCreate(KModelContext  $context)
    {
        return null;
    }
}
