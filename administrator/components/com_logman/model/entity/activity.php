<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activity Model Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanModelEntityActivity extends ComActivitiesModelEntityActivity implements KObjectInstantiable, ComLogmanActivityInterface
{
    /**
     * Entity map
     *
     * @var $array
     */
    static private $__entities = array();

    /**
     * The activity context.
     *
     * @see ComLogmanActivityInterface::getContext
     * @var string
     */
    protected $_context;

    /**
     * The view levels.
     *
     * @see ComLogmanActivityInterface::setViewLevels
     * @var array
     */
    protected $_levels;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        // Handle metadata overrides
        if (($metadata = $this->getMetadata()) && isset($metadata->_logman))
        {
            $metadata_mappings = array(
                'object_table'  => '_object_table',
                'object_column' => '_object_column',
                'format'        => '_format'
            );

            foreach ($metadata_mappings as $key => $property)
            {
                if (isset($metadata->_logman->{$key})) {
                    $this->{$property} = $metadata->_logman->{$key};
                }
            }
        }

        $plugins = JPluginHelper::getPlugin('logman');

        $package = $this->package;

        foreach ($plugins as $plugin)
        {
            if ($plugin->name == $package) {
                $this->getObject('translator')->load('plg:logman.' . $package);
            }
        }

        $this->setContext($config->context);
        $this->setViewLevels(KObjectConfig::unbox($config->levels));
    }

    /**
     * Instantiate the object
     *
     * @param   KObjectConfigInterface $config      Configuration options
     * @param 	KObjectManagerInterface $manager	A KObjectManagerInterface object
     * @return  KObjectInterface
     */
    public static function getInstance(KObjectConfigInterface $config, KObjectManagerInterface $manager)
    {
        $class = $manager->getClass($config->object_identifier, false);

        if (is_string($config->data->metadata)) {
            $config->data->metadata = new KObjectConfig(json_decode($config->data->metadata, true));
        }

        if ($class == get_class())
        {
            if ($entity = self::__findEntity($config->data, $manager)) {
                return $manager->getObject($entity, $config->toArray());
            }
        }

        return new $class($config);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $context = $config->data->context ?: JFactory::getApplication()->getName();
        $levels  = $config->data->levels ?: array();

        $config->append(array(
            'object_table'  => $config->data->package,
            'object_column' => 'id',
            'levels'        => $levels,
            'context'       => $context,
            'translator'    => 'com://admin/logman.activity.translator'
        ));

        parent::_initialize($config);
    }


    public function setContext($context)
    {
        $this->_context = (string) $context;
        return $this;
    }


    public function getContext()
    {
        return $this->_context;
    }

    public function setViewLevels($levels)
    {
        $this->_levels = (array) $levels;
        return $this;
    }

    public function getViewLevels()
    {
        return $this->_levels;
    }

    /**
     * Activity image getter.
     *
     * The image is a CSS class pointing to an image representing the activity.
     *
     * @return string The activity image class name.
     */
    public function getPropertyImage()
    {
        $images = array(
            'publish'   => 'k-icon-circle-check',
            'unpublish' => 'k-icon-circle-x',
            'trash'     => 'k-icon-trash',
            'add'       => 'k-icon-plus',
            'edit'      => 'k-icon-pencil',
            'delete'    => 'k-icon-x',
            'archive'   => 'k-icon-inbox'
        );

        // Default.
        $image = 'k-icon-task';

        if (in_array($this->verb, array_keys($images))) {
            $image = $images[$this->verb];
        }

        return $image;
    }

    protected function _getObject($config = array())
    {
        $config = new KObjectConfig($config);

        if ($config->pages && $config->pages->template)
        {
            $pages = $this->_findPages(array(
                'conditions' => $config->pages->conditions,
                'levels'     => $this->getViewLevels(),
                'components' => $config->pages->components ? $config->pages->components : $this->package,
                'id'         => $config->pages->id
            ));

            if ($pages)
            {
                $page = $pages[0];
                $config->append(array('url' => array('site' => sprintf($config->pages->template, $page->id))));
            }
        }

        if ($config->url instanceof KObjectConfig) {
            $config->url = $this->getContext() == 'site' ? $config->url->site : $config->url->admin;
        } else {
            unset($config->url); // Context cannot be determined, we better make the object non-linkable
        }

        return parent::_getObject($config);
    }

    /**
     * Activity scripts getter.
     *
     * @return string|null Scripts to be included with the rendered HTML activity, null if no scripts.
     */
    public function getPropertyScripts()
    {
        return null;
    }

    protected function _actorConfig(KObjectConfig $config)
    {
        if ($this->created_by != -1)
        {
            $config->append(array('url' => array('admin' => 'option=com_users&task=user.edit&id=' . $this->created_by)));
            parent::_actorConfig($config);
        }
        else $config->append(array(
            'id'          => $this->created_by,
            'type'        => array('objectName' => 'user', 'object' => true),
            'objectName' => 'System',
            'translate'   => true,
            'find'        => false,
            'url'         => null
        ));
    }


    protected function _objectConfig(KObjectConfig $config)
    {
        if (($metadata = $this->getMetadata()) && ($_logman = $metadata->_logman))
        {
            if ($url = $_logman->url)
            {
                $url = $url->toArray();

                if (isset($url['site']))
                {
                    parse_str($url['site'], $parts);

                    if (isset($parts['Itemid']))
                    {
                        $query = $this->getObject('lib:database.query.select')->table('menu')
                            ->where('id = :id')->bind(array('id' => $parts['Itemid']));

                        $menu_item = $this->getObject('lib:database.adapter.mysqli')->select($query, KDatabase::FETCH_OBJECT);
                        
                        if ($menu_item)
                        {
                            // Check for user access level.
                            $user = $this->getObject('user');

                            if (!in_array((int) $menu_item->access, $user->getRoles())) {
                                unset($url['site']);
                            }
                        }
                        else unset($url['site']);
                    }
                }

                $config->append(array('url' => $url));
            }
        }

        // Filter by page ID
        if ($page = $this->page) {
            $config->append(array('pages' => array('id' => $page)));
        }

        $config->append(array(
            'url' => array(
                'admin' => 'option=com_' . $this->package . '&task=' . $this->name . '.edit&id=' . $this->row
            )
        ));

        parent::_objectConfig($config);
    }

    protected function _generatorConfig(KObjectConfig $config)
    {
        parent::_generatorConfig($config->append(array('objectName' => 'LOGman')));
    }

    protected function _providerConfig(KObjectConfig $config)
    {
        parent::_providerConfig($config->append(array('objectName' => 'LOGman')));
    }

    /**
     * Looks up for an activity entity object given its data.
     *
     * @return mixed A string or identifier object, false if not found.
     */
    private static function __findEntity($data, KObjectManagerInterface $manager)
    {
        if (!isset(self::$__entities[$data->package])) {
            self::$__entities[$data->package] = array();
        }

        if (!isset(self::$__entities[$data->package][$data->name]))
        {
            self::$__entities[$data->package][$data->name] = false;

            $plugins = $manager->getObject('com://admin/logman.model.plugins')
                               ->logger(true)
                               ->package($data->package)
                               ->fetch();

            foreach ($plugins as $plugin)
            {
                $identifiers = array(
                    sprintf('plg:logman.%s.activity.%s', $plugin->getName(), $data->name),
                    sprintf('plg:logman.%s.activity.%s', $plugin->getName(), $data->package)
                );

                foreach ($identifiers as $identifier)
                {
                    if ($manager->getClass($identifier, false))
                    {
                        self::$__entities[$data->package][$data->name] = $identifier;
                        break;
                    }
                }

                if (isset(self::$__entities[$data->package][$data->name])) break;
            }
        }

        return self::$__entities[$data->package][$data->name];
    }

    /**
     * Overridden for using custom router route object.
     */
    protected function _getRoute($url)
    {
        if (!is_string($url)) throw new InvalidArgumentException('The URL must be a query string');

        return $this->getObject('com://admin/logman.activity.router.route', array(
            'application' => $this->getContext(),
            'url'         => array('query' => $url)
        ));
    }

    /**
     * Pages finder.
     *
     * @param array $config An optional configuration array.
     * @return array A list of found pages.
     */
    protected function _findPages($config = array())
    {
        $result = null;

        $config = new KObjectConfig($config);

        $config->append(array('conditions' => array(), 'levels' => array(), 'published' => 1));

        $query = $this->getObject('lib:database.query.select');

        $query->table(array('menu' => 'menu'))
              ->columns(array('menu.*'))
              ->where('menu.client_id = :client_id')
              ->where('menu.published = :published')
              ->bind(array('client_id' => 0, 'published' => $config->published));

        $levels = KObjectConfig::unbox($config->levels);

        if (count($levels)) {
            $query->where('menu.access IN :levels')->bind(array('levels' => $levels));
        }

        if ($components = KObjectConfig::unbox($config->components))
        {
            $components = (array) $components;

            foreach ($components as &$component)
            {
                if (strpos($component, 'com_') !== 0) {
                    $component = 'com_' . $component;
                }
            }

            $query->join('extensions AS extensions', 'extensions.extension_id = menu.component_id', 'INNER')
                  ->where('extensions.element IN :component')->bind(array('component' => $components));
        }

        if ($id = $config->id) {
            $query->where('id = :id')->bind(array('id' => $id));
        }

        $pages = $this->getObject('lib:database.adapter.mysqli')->select($query, KDatabase::FETCH_OBJECT_LIST);

        if (!$pages) $pages = array();

        $conditions = KObjectConfig::unbox($config->conditions);

        if (!empty($conditions))
        {
            $result = array();

            $conditions_set = $conditions;

            if (!is_numeric(current(array_keys($conditions)))) {
                $conditions_set = array($conditions); // wrap around an array for handling multiple set of conditions at once.
            }

            foreach ($conditions_set as $conditions)
            {
                foreach ($pages as $page)
                {
                    $link = parse_url($page->link);

                    if (isset($link['query']))
                    {
                        parse_str($link['query'], $query);

                        foreach($conditions as $property => $value) {
                            if (!isset($query[$property]) || !in_array($query[$property], (array) $value)) continue 2;
                        }

                        $result[] = $page;
                    }
                }
            }
        }
        else $result = $pages;

        return $result;
    }
}
