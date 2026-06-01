<?php

/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activities Model Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanModelEntityActivities extends ComActivitiesModelEntityActivities
{
    public function create(array $properties = array(), $status = null)
    {
        $config = $this->getConfig();

        if ($config->context && !isset($properties['context'])) {
            $properties['context'] = $config->context;
        }

        if ($config->levels && !isset($properties['levels'])) {
            $properties['levels'] = $config->levels;
        }

        return parent::create($properties, $status);
    }
}