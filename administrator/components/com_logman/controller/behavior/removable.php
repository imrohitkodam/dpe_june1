<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Removable Controller Behavior.
 *
 * Removes resources entries when moving or copying nodes.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */

class ComLogmanControllerBehaviorRemovable extends KControllerBehaviorAbstract
{
    protected function _beforeMove(KControllerContextInterface $context)
    {
        $node = $context->getSubject()->getModel()->fetch();

        if (!$node->isNew())
        {
            if ($container = $node->getContainer())
            {
                $id = sprintf('%s:%s', $container->slug, trim(sprintf('%s/%s', $node->folder, $node->name), '/'));

                $resource = $this->getObject('com:activities.model.resources')->resource_id($id)->fetch();

                if (!$resource->isNew()) {
                    $resource->delete();
                }
            }

        }
    }

    protected function _beforeCopy(KControllerContextInterface $context)
    {
        // Same as move.
        $this->_beforeMove($context);
    }
}