<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;
/**
 * LOGman Koowa Plugin.
 *
 * Loads LOGman plugin group and its dependencies.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\Koowa
 */
class PlgKoowaLogman extends PlgKoowaAbstract
{
	protected $_exception_handler = null;

    function __construct($dispatcher, $config = array())
    {
        if (version_compare($this->_getLogmanVersion(), '3.0.0-beta1', '>='))
        {
            $classes = array(
                'administrator/components/com_logman/activity/interface.php',
                'administrator/components/com_logman/plugin/interface.php',
                'administrator/components/com_logman/plugin/logger/interface.php',
                'administrator/components/com_logman/plugin/abstract.php',
                'administrator/components/com_logman/plugin/logger.php',
                'administrator/components/com_logman/plugin/joomla.php',
                'administrator/components/com_logman/plugin/koowa.php',
                'administrator/components/com_logman/plugin/notifier.php',
                'administrator/components/com_logman/activity/notifier/interface.php',
                'administrator/components/com_logman/activity/notifier/abstract.php',
                'administrator/components/com_logman/activity/notifier/email.php',
                'administrator/components/com_logman/plugin/notifier.php',
                'administrator/components/com_logman/controller/behavior/loggable.php',
                'administrator/components/com_logman/model/entity/activity.php',
                'administrator/components/com_logman/activity/logger/logger.php',
                'administrator/components/com_logman/activity/translator.php'
            );

            $path = defined('JOOMLATOOLS_PLATFORM') ? JPATH_APP : JPATH_ROOT;

            // Make sure files exist as otherwise whole site will go down with a fatal error
            $files_exist = true;
            foreach ($classes as $class)
            {
                if (!file_exists($path . '/' . $class))
                {
                    $files_exist = false;
                    break;
                }
            }

            if ($files_exist)
            {
                // Load LOGman base plugin classes.
                foreach ($classes as $class) {
                    require_once $path . '/' . $class;
                }

                // Load LOGman plugin group.
                PluginHelper::importPlugin('logman');
            }
        }

        if (version_compare(JVERSION, '4', '>=')) {
            $dispatcher->addListener('onError', array($this, 'onJ4Error'), Joomla\Event\Priority::ABOVE_NORMAL);
        } else {
            $this->_exception_handler = set_exception_handler(array($this, 'onJ3Exception'));
        }

        parent::__construct($dispatcher, $config);

        $this->getObject('event.publisher')->addListener('onException', array($this, 'onException'), KEvent::PRIORITY_NORMAL);
    }

    public function onJ4Error($event)
    {
        if ($event->getError() instanceof Joomla\CMS\Router\Exception\RouteNotFoundException) {
            $this->_handlePageNotFound();
        }
    }

    public function onJ3Exception($exception)
    {
        $handled = false;

        if ($exception instanceof Joomla\CMS\Router\Exception\RouteNotFoundException) {
            $handled = $this->_handlePageNotFound();
        }

        if (!$handled)
        {
            if ($this->_exception_handler) {
                call_user_func_array($this->_exception_handler, array($exception));
            } else {
                JErrorPage::render($exception);
            }
        }
    }

    public function onException(KEvent $event)
    {
        if ($event->getName() == 'onException' && $event->exception instanceof Joomla\CMS\Router\Exception\RouteNotFoundException) {
            $this->_handlePageNotFound();
        }
    }

    protected function _handlePageNotFound()
    {
        return $this->getObject('com://admin/logman.controller.route')->redirect();
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   KObjectConfig $config Configuration options.
     * @return  void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'auto_connect' => false
        ));
    }

    public function handleError(Exception $je)
    {
        if ($je->getCode() == 404)
		{
		    if (!$this->getObject('com://admin/logman.controller.route')->redirect()) {
                $this->_handleError($je);
            }
        }
        else $this->_handleError($je);
	}

    /**
     * LOGman version getter.
     *
     * @return string|null The extension version, null if couldn't be determined.
     */
    protected function _getLogmanVersion()
    {
        $version = null;

        $query = "SELECT manifest_cache FROM #__extensions WHERE element = 'com_logman'";
        if ($result = Factory::getDBO()->setQuery($query)->loadResult())
        {
            $manifest = new Registry($result);
            $version  = $manifest->get('version', null);
        }

        return $version;
    }
}

