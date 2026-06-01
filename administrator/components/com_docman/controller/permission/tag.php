<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanControllerPermissionTag extends ComKoowaControllerPermissionAbstract
{
    public function canAdd()
    {
        return $this->getObject('com:docman.controller.document')->canAdd();
    }
}