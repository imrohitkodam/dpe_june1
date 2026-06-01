<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComLogmanViewLinkerHtml extends ComKoowaViewHtml
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('decorator' => 'koowa'));

        parent::_initialize($config);
    }
}