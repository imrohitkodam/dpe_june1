<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Redirectable Dispatcher Behavior
 *
 * Redirects old routes to their new equivalent
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanDispatcherBehaviorRedirectable extends KControllerBehaviorAbstract
{
    protected function _beforeFail(KDispatcherContextInterface $context)
    {
        if ($exception = $context->param->exception)
        {
            if ($exception instanceof Joomla\CMS\Router\Exception\RouteNotFoundException) {
                $this->getObject('com://admin/logman.controller.route')->redirect();
            }
        }
    }
}