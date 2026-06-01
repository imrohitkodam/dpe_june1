<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Abstract Koowa LOGman Plugin
 *
 * Provides support out of the box support for Koowa components that want to have their actions logged.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
abstract class ComLogmanPluginKoowa extends ComLogmanPluginLogger
{
    /**
     * Constructor.
     *
     * @param   KObjectConfig $config Configuration options
     */
    public function __construct(&$subject, $config = array())
    {
        parent::__construct($subject, $config);

        if ($this->canLog())
        {
            //Inject loggers into controllers

            $controllers = KObjectConfig::unbox($this->getConfig()->controllers);

            foreach ($controllers as $controller => $loggers)
            {
                $loggers = (array) $loggers;

                $this->getIdentifier($controller)->getConfig()->append(array(
                    'behaviors' => array('com://admin/logman.controller.behavior.loggable' => array('loggers' => $loggers))
                ));
            }
        }
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
            'auto_connect' => false,
            'controllers'  => array()
        ));
    }
}