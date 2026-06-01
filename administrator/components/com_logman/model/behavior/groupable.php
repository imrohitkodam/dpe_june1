<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Dateable Model Behavior
 *
 * Convinience model behavior for filtering by date ranges
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanModelBehaviorGroupable extends KModelBehaviorAbstract
{

    /**
     * Insert the model states
     *
     * @param KObjectMixable $mixer
     */
    public function onMixin(KObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        $mixer->getState()->insert('group_by', 'cmd');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('count_label' => 'total'));

        parent::_initialize($config);
    }

    protected function _beforeFetch(KModelContextInterface $context)
    {
        $query = $context->query;

        if ($group_by = (array) $context->getState()->group_by)
        {
            $query->group($group_by);

            $query->columns(array($this->getConfig()->count_label => 'COUNT(*)'));
        }
    }
}