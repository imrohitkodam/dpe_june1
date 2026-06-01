<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
/**
 * Abstract LOGman Plugin
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
#[\AllowDynamicProperties]
abstract class ComLogmanPluginAbstract extends PlgKoowaAbstract implements ComLogmanPluginInterface, KObjectMultiton
{
    /**
     * LOGman component settings
     *
     * @var Registry
     */
    static private $_logman_params = null;

    /**
     * The plugin type
     *
     * @var string
     */
    protected $_plugin_type;

    /**
     * Constructor.
     *
     * @param   object             $dispatcher Event dispatcher
     * @param array|\KObjectConfig $config     Configuration options
     */
    public function __construct(&$dispatcher, $config = array())
    {
        // Make params available as a Registry object to _initialize.
        if (isset($config['params']))
        {
            $params = new Registry;
            $params->loadString($config['params']);
            $config['params'] = $params;
        }

        parent::__construct($dispatcher, $config);

        if (is_null(self::$_logman_params))
        {
            $params = ComponentHelper::getParams('com_logman');

            self::$_logman_params = array();

            foreach ($params->toArray() as $name => $value)
            {
                self::$_logman_params['logman_' . $name] = $value; // Namespace LOGman parameters.
            }

            self::$_logman_params = new Registry(self::$_logman_params);
        }

        // Merge plugin and LOGman parameters.
        $this->params->merge(self::$_logman_params);
    }

    /**
     * Load language plugins after the request is routed. At this point Joomla knows what language
     * the site is set (multilingual)
     */
    public function onAfterRoute()
    {
        // Load plugin translations.
        $this->getObject('translator')->load($this->getIdentifier());
    }

    /**
     * Get a plugin parameter
     *
     * @param string $name      The parameter name
     * @param null   $default   The default value if the parameter doesn't exist.
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        return $this->params->get($name, $default);
    }

    public function getName()
    {
        return $this->_name;
    }

    public function isLogger()
    {
        return $this instanceof ComLogmanPluginLoggerInterface;
    }

    /**
     * Tells if the plugin is enabled
     *
     * @return bool True if enabled, false otherwise
     */
    public function isEnabled()
    {
        $adapter = $this->getObject('lib:database.adapter.mysqli');

        $query = $this->getObject('lib:database.query.select')
                      ->columns('enabled')
                      ->table('extensions')
                      ->where('name = :name')
                      ->bind(array('name' => sprintf('plg_logman_%s', $this->getName())));

        return (bool) $adapter->select($query, KDatabase::FETCH_FIELD);
    }
}