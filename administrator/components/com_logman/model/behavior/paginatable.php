<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Paginatable Model Behavior
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanModelBehaviorPaginatable extends KModelBehaviorPaginatable
{
    protected $_fix_offset;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_fix_offset = $config->fix_offset;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('fix_offset' => true));
    }

    public function fixOffset($status = true)
    {
        $this->_fix_offset = (bool) $status;
        return $this;
    }

    /**
     * Overridden to bypass offset fix.
     */
    protected function _beforeFetch(KModelContextInterface $context)
    {
        if (!$this->_fix_offset)
        {
            $state = $context->state;

            if (!$state->isUnique()) {
                $context->query->limit($state->limit, $state->offset);
            }
        }
        else parent::_beforeFetch($context);
    }

    /**
     * Overridden to bypass offset fix.
     */
    protected function _afterReset(KModelContextInterface $context)
    {
        if ($this->_fix_offset) {
            parent::_afterReset($context);
        }
    }
}