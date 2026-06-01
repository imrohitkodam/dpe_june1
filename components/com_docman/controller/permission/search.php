<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Flat controller permissions
 */

class ComDocmanControllerPermissionSearch extends ComDocmanControllerPermissionAbstract
{
    public function canAdd()
    {
        return false;
    }

    public function canDelete()
    {
        return false;
    }

    public function canEdit()
    {
        return false;
    }
}