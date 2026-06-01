<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * LOGman Logger Plugin Interface
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
interface ComLogmanPluginLoggerInterface extends ComLogmanPluginInterface
{
    /**
     * Adds/logs an activity row.
     *
     * @param array $data The activity data.

     * @return object The activity row.
     */
    public function logActivity($data = array());

    /**
     * Actions getter.
     *
     * @return array A list of actions logged by the plugin
     */
    public function getActions();

    /**
     * Resources getter.
     *
     * @return array A list of resources handled by the plugin.
     */
    public function getResources();

    /**
     * Package getter.
     *
     * @return string The name of the package handled by the plugin.
     */
    public function getPackage();

    /**
     * Adds/logs a route.
     *
     * @param array $data The route data
     *
     * @return mixed The route row if success, false otherwise.
     */
    public function logRoute($data);

    /**
     * Adds/logs an impression.
     *
     * @param array $data The impression data
     *
     * @return mixed The impression row if success, false otherwise.
     */
    public function logImpression($data);

    /**
     * Determines if a plugin can log anything
     *
     * @return boolean Returns true if it can, false otherwise
     */
    public function canLog();
}