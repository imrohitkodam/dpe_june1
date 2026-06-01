<?php

/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activity Interface
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
interface ComLogmanActivityInterface extends ComActivitiesActivityInterface
{
    /**
     * Activity context setter.
     *
     * Defines the context of the activity allowing an activity object to conditionally behave depending
     * on its environment, e.g. activities from frontend streams may be handled differently than the ones
     * from backend streams.
     *
     * @param $name string The activity context.
     * @return ComLogmanActivityInterface
     */
    public function setContext($context);

    /**
     * Activity context getter.
     *
     * @return string The activity context.
     */
    public function getContext();

    /**
     * View levels setter.
     *
     * The view levels are used to determine how information is exposed by the activity based on these levels.
     *
     * @param $levels array An array containing view levels.
     * @return ComLogmanActivityInterface
     */
    public function setViewLevels($levels);

    /**
     * View levels getter.
     *
     * @return array The view levels.
     */
    public function getViewLevels();
}