<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComLogmanModelConfigs extends KModelAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'state' => 'com://admin/logman.model.config.state',
        ));

        parent::_initialize($config);
    }

    protected function _actionFetch(KModelContext $context)
    {
        return $this->getObject('com://admin/logman.model.entity.config');
    }
}
