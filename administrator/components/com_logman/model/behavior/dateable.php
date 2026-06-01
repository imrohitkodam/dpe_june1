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
class ComLogmanModelBehaviorDateable extends KModelBehaviorAbstract
{

    /**
     * Insert the model states
     *
     * @param KObjectMixable $mixer
     */
    public function onMixin(KObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        $mixer->getState()->insert('start_date', 'date')->insert('end_date', 'date');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('column' => 'created_on', 'inclusive' => true));

        parent::_initialize($config);
    }

    protected function _beforeFetch(KModelContextInterface $context)
    {
        $state = $context->getState();

        $config = $this->getConfig();

        if ($start = $state->start_date) {
            $context->query->where(sprintf('DATE(%s) %s :start', $config->column, $config->inclusive ? '>=' : '>'))
                           ->bind(array('start' => $start));
        }

        if ($end = $state->end_date) {
            $context->query->where(sprintf('DATE(%s) %s :end', $config->column, $config->inclusive ? '<=' : '<'))
                           ->bind(array('end' => $end));
        }
    }
}