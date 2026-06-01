<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * LOGman Plugin Interface
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
interface ComLogmanPluginInterface
{
    /**
     * Get the a parameter
     *
     * @param  string $name    The name of the setting parameter.
     * @param null    $default The default value.
     * @return mixed The parameter or default value.
     */
    public function getParameter($name, $default = null);

    /**
     * Name getter.
     *
     * @return string The plugin name.
     */
    public function getName();

    /**
     * Tells if the plugin is a logger.
     *
     * @return bool True if the plugin type is logger, false otherwise.
     */
    public function isLogger();

    /**
     * Tells if the plugin is enabled
     *
     * @return bool True if enabled, false otherwise
     */
    public function isEnabled();
}