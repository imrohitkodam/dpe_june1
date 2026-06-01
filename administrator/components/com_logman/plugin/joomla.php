<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Abstract Joomla LOGman Plugin
 *
 * Provides support for content and extension core events.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
abstract class ComLogmanPluginJoomla extends ComLogmanPluginLogger
{
    /**
     * Activities logging configuration
     *
     * @var KObjectConfigInterface
     */
    protected $_activities;

    /**
     * Routes logging configuration
     *
     * @var KObjectConfigInterface
     */
    protected $_routes;

    /**
     * Impressions logging configuration
     *
     * @var KObjectConfigInterface
     */
    protected $_impressions;

    /**
     * The current request query
     *
     * @var array
     */
    static protected $_query;

    /**
     * LOGman settings
     *
     * @var ComLogmanModelEntityConfig
     */
    static protected $_settings;

    /**
     * Constructor.
     *
     * @param   KObjectConfig $config Configuration options
     */
    public function __construct(&$subject, $config = array())
    {
        parent::__construct($subject, $config);

        $activities = $this->getConfig()->activities;

        if (!$activities->contexts->count())
        {
            $contexts = array();

            foreach ($this->getResources() as $resource) {
                $contexts[] = 'com_' . $this->_package . '.' . $resource;
            }

            $activities->contexts = $contexts;
        }

        if (!isset(self::$_settings))
        {
            $model = $this->getObject('com://admin/logman.model.configs');

            // Avoid loading plugins when publishing model events
            $model->getCommandChain()->disable();

            self::$_settings = $model->fetch();
        }

        $settings = self::$_settings;

        if (!$settings->log_impressions) {
            $this->getConfig()->impressions->enabled = false;
        }

        if (!$settings->log_routes) {
            $this->getConfig()->routes->enabled = false;
        }

        // Set option condition for both impressions and routes logging

        foreach (array('impressions', 'routes') as $property) {
            $this->getConfig()->{$property}->append(array('conditions' => array('option' => sprintf('com_%s', $this->getPackage()))));
        }

        $this->_activities  = $activities;
        $this->_routes      = $this->getConfig()->routes;
        $this->_impressions = $this->getConfig()->impressions;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'activities'  => array('contexts' => array(), 'context_map' => array()),
            'routes'      => array('enabled' => false, 'conditions' => array('option', 'Itemid', 'view', 'id')),
            'impressions' => array(
                'enabled'        => false,
                'conditions'     => array('option', 'view', 'id'),
                'container_hash' => 'logman.impressions'
            )
        ));

        parent::_initialize($config);
    }

    /**
     * Before log activity event handler.
     *
     * Set the activity data based on the passed config object, and set default based on the context we are logging in.
     *
     * @param KObjectConfig $config The configuration object.
     * @return bool Return false for preventing an activity from being logged.
     */
    final protected function _beforeLogActivity(KObjectConfig $config)
    {
        if ($context = $config->context)
        {
            $result = false;

            if (isset($this->_activities->context_map[$context])) {
                $context = $this->_activities->context_map[$context];
            }

            if (in_array($context, KObjectConfig::unbox($this->_activities->contexts)))
            {
                $parts = explode('.', $context);

                $type    = substr($parts[0], 0, strpos($parts[0], '_'));
                $package = substr($parts[0], strlen($type) + 1);

                $method = '_get' . ucfirst($parts[1]) . 'ObjectData';

                if (method_exists($this, $method)) {
                    $data = call_user_func_array(array($this, $method), array($config->data, $config->event));
                } else {
                    $data = array('id' => $config->data->id, 'name' => $config->data->title);
                }

                $data['type']    = isset($data['type']) ? $data['type'] : $parts[1];
                $data['package'] = isset($data['package']) ? $data['package'] : $package;

                $config->extension = $type;
                $config->object    = $data;

                $result = parent::_beforeLogActivity($config);
            }
        }
        else $result = parent::_beforeLogActivity($config);

        return $result;
    }

    /**
     * Checks for the validity of a query given a set of conditions.
     *
     * @param array $query The query to validate.
     * @param array $conditions A set of conditions to be safisfied by the query.
     *
     * @return bool True if query is valid, false otherwise.
     */
    protected function _isValid($query, $conditions)
    {
        $result = true;

        if (!empty($conditions))
        {
            foreach ($conditions as $key => $value)
            {
                if (!is_numeric($key))
                {
                    if (!isset($query[$key]) || $query[$key] != $value) {
                        $result = false;
                    }
                }
                else $result = isset($query[$value]);

                if (!$result) break;
            }
        }
        else $result = false;

        return $result;
    }

    protected function _isSite()
    {
        return JFactory::getApplication()->isClient('site');
    }

    protected function _isSefEnabled()
    {
        return JFactory::getConfig()->get('sef');
    }

    /**
     * Query getter
     *
     * @return array The query part of the current parsed route
     */
    protected function _getQuery()
    {
        if (!self::$_query)
        {
            $vars = JFactory::getApplication()->getRouter()->getVars();

            if (count($vars) == 2 && isset($vars['Itemid']) && isset($vars['option']))
            {
                // Grab query from menu item

                $query = $this->getObject('lib:database.query.select')
                              ->table('menu')
                              ->columns(array('link'))
                              ->where('id = :id')
                              ->bind(array('id' => $vars['Itemid']));

                $adapter = $this->getObject('lib:database.adapter.mysqli');

                if ($link = $adapter->select($query, KDatabase::FETCH_FIELD))
                {
                    $result = parse_url($link);

                    if (isset($result['query']))
                    {
                        parse_str($result['query'], $query);
                        $query['Itemid'] = $vars['Itemid'];
                    }
                    else $query = $vars;
                }
            }
            else $query = $vars;

            self::$_query = $query;
        }

        return self::$_query;
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

        $query = $data;

        $view = $query['view'];

        list($type, $package) = explode('_', $query['option']);
        $method = '_get' . ucfirst($view) . 'RouteData';

        if (method_exists($this, $method)) {
            $data = call_user_func_array(array($this, $method), array($query));
        } else {
            $data = array('row' => $query['id'], 'name' => $view);
        }

        $data['package'] = isset($data['package']) ? $data['package'] : $package;
        $data['page']    = isset($data['page']) ? $data['page'] : $query['Itemid'];

        try {
            $result = parent::logRoute($data);
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

        $query = $data;

        $view = $query['view'];

        list($type, $package) = explode('_', $query['option']);
        $method = '_get' . ucfirst($view) . 'ImpressionData';

        if (method_exists($this, $method)) {
            $data = call_user_func_array(array($this, $method), array($query));
        } else {
            $data = $this->_getImpressionData($query);
        }

        $data['package'] = isset($data['package']) ? $data['package'] : $package;

        $user = $this->getObject('user');

        $hash = $this->getConfig()->impressions->container_hash;

        $key = sprintf('%s.%s.%s', $data['package'], $data['name'], $data['row']);

        $container = $user->get($hash);

        if (!isset($container)) {
            $container = array();
        }

        // Check if the impression has already been logged during this session

        if (!isset($container[$key]))
        {
            try
            {
                $result = parent::logImpression($data);

                $container[$key] = true;

                $user->set($hash, $container);
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

    protected function _getImpressionData($query)
    {
        return array('row' => $query['id'], 'name' => $query['view']);
    }

    /**
     * After content save event handler.
     *
     * @param string $context The event context.
     * @param mixed  $content The event content, aka data.
     * @param int    $isNew   Whether or not the content is new.
     */
    public function onContentAfterSave($context, $content, $isNew)
    {
        $this->logActivity(array(
            'context' => $context,
            'data'    => $content,
            'verb'    => $isNew ? 'add' : 'edit',
            'event'   => 'onContentAfterSave'
        ));
    }

    /**
     * After content delete event handler.
     *
     * @param string $context The event context.
     * @param mixed  $content The event content, aka data.
     */
    public function onContentAfterDelete($context, $content)
    {
        $this->logActivity(array(
            'context' => $context,
            'data'    => $content,
            'verb'    => 'delete',
            'event'   => 'onContentAfterDelete'
        ));
    }

    /**
     * After extension save event handler.
     *
     * @param $context
     * @param $data
     * @param $isNew
     */
    public function onExtensionAfterSave($context, $data, $isNew)
    {
        $this->logActivity(array(
            'context' => $context,
            'data'    => $data,
            'verb'    => $isNew ? 'add' : 'edit',
            'event'   => 'onExtensionAfterSave'
        ));
    }

    /**
     * After extension delete event handler.
     *
     * @param $context
     * @param $data
     */
    public function onExtensionAfterDelete($context, $data)
    {
        $this->logActivity(array(
            'context' => $context,
            'data'    => $data,
            'verb'    => 'delete',
            'event'   => 'onExtensionAfterDelete'
        ));
    }

    /**
     * Content change event handler.
     *
     * @param string $context The event context.
     * @param array  $pks     A list of the primary keys to change
     * @param int    $state   The state that was set.
     */
    public function onContentChangeState($context, $pks, $state)
    {
        if (in_array($context, KObjectConfig::unbox($this->_activities->contexts)))
        {
            $config = new KObjectConfig(array('context' => $context));

            $parts = explode('.', $context);

            if (is_array($parts) && count($parts) === 2) {
                $config->append(array('type' => $parts[1]));
            }

            $items = $this->_getItems($pks, $config);

            foreach ($items as $item)
            {
                switch ($state)
                {
                    case -2:
                        $verb   = 'trash';
                        $result = 'trashed';
                        break;
                    case 0:
                        $verb   = 'unpublish';
                        $result = 'unpublished';
                        break;
                    case 1:
                        $verb   = 'publish';
                        $result = 'published';
                        break;
                    case 2:
                        $verb   = 'archive';
                        $result = 'archived';
                        break;
                    default: // Unknown state. Ignore event.
                        return;
                        break;
                }

                $this->logActivity(
                    array(
                        'context' => $context,
                        'result'  => $result,
                        'data'    => $item,
                        'verb'    => $verb,
                        'event'   => 'onContentChangeState'
                    ));
            }
        }
    }

    /**
     * Handles the logging of routes
     */
    public function onAfterDispatch()
    {
        $query = $this->_getQuery();

        if ($this->_canLogRoute($query)) {
            $this->logRoute($query);
        }

        if ($this->_canLogImpression($query)) {
            $this->logImpression($query);
        };
    }

    protected function _canLogRoute($query)
    {
        $config = $this->_routes;

        return $config->enabled && $this->_isSite() && $this->_isSefEnabled() && $this->_isValid($query, $config->conditions);
    }

    protected function _canLogImpression($query)
    {
        $config = $this->_impressions;

        $guest_condition = true;

        if (!self::$_settings->log_guest_actions) {
            $guest_condition = $this->getObject('user')->isAuthentic();
        }

        return $config->enabled &&
               $guest_condition &&
               $this->_hasReferrer($this->getObject('request')) &&
               $this->_isSite() &&
               $this->_isSefEnabled() &&
               $this->_isValid($query, $config->conditions);
    }

    protected function _hasReferrer(KControllerRequestInterface $request)
    {
        $referrer = $request->getReferrer(false);

        $referrer = is_null($referrer) ? '' : $referrer;

        return !empty(trim($referrer));
    }

    public function onUserAfterLogin($options)
    {
        $user = $this->getObject('user');

        $container = $this->getConfig()->impressions->container_hash;

        if ($user->get($container)) {
            $user->set($container, null);
        }
    }

    /**
     * Items getter.
     *
     * @param int           $ids    The identifier of the items to get.
     * @param KObjectConfig $config The configuration object.
     *
     * @return array An array of items.
     */
    protected function _getItems($ids, KObjectConfig $config)
    {
        $items = array();
        $ids   = (array) $ids;

        $config->append(
            array(
                'prefix' => 'JTable',
                'config' => array(),
                'path'   => JPATH_ADMINISTRATOR . '/components/' . $this->_package . '/tables',
                'type'   => KStringInflector::singularize($this->_name)
            )
        );

        foreach ($ids as $id)
        {
            JTable::addIncludePath($config->path);

            if ($table = JTable::getInstance($config->type, $config->prefix, $config->config))
            {
               if ($table->load($id)) {
                   $items[] = $table;
               }
            }
        }

        return $items;
    }
}
