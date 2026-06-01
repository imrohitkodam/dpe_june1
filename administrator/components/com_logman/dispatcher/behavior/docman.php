<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * DOCman Dispatcher Behavior
 *
 * Properly sets custom levels for the current user in the request.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanDispatcherBehaviorDocman extends KControllerBehaviorAbstract
{
    public function isSupported()
    {
        $result = false;

        $identifier = $this->getIdentifier('com://admin/docman.version');

        if ($this->getObject('manager')->getClass($identifier, false)) {
            $result = true;
        }

        return $result;
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        $query = $this->getRequest()->getQuery();

        if (isset($query->levels))
        {
            $levels = $this->getObject('com://admin/docman.model.documents')
                           ->getUserLevels($context->getSubject()->getUser()->getId());

            $query->levels = array_merge($query->levels, $levels);
        }
    }
}