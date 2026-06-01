<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activity Logger
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanActivityLogger extends ComActivitiesActivityLogger
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'controller' => 'com://admin/logman.controller.activity'
        ));

        parent::_initialize($config);
    }

    public function log($action, KModelEntityInterface $object, KObjectIdentifierInterface $subject)
    {
        try
        {
            parent::log($action, $object, $subject);
        }
        catch (Exception $e)
        {
            if (JDEBUG) {
                throw $e;
            }
        }

        return $this;
    }
}