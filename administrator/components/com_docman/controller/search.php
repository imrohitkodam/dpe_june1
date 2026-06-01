<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanControllerSearch extends ComKoowaControllerModel
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'formats' => ['json'],
            'model'  => 'documents'
        ));

        parent::_initialize($config);
    }
}