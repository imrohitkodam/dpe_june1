<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Persistable Controller Behavior
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanControllerBehaviorPersistable extends ComKoowaControllerBehaviorPersistable
{
    /**
     * Overridden so that persistency is not layout dependent.
     */
    protected function _getStateKey(KControllerContextInterface $context)
    {
        $view   = $this->getView()->getIdentifier();
        $model  = $this->getModel()->getIdentifier();

        return $view.'.'.$model.'.'.$context->action;
    }
}