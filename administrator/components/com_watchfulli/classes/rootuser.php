<?php
/**
 * @version     admin/classes/rootuser.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\User\User;

class WatchfulliRootUser extends User
{
    public function __construct()
    {
        $this->id = 0;
        $this->isRoot = true;
        parent::__construct();
    }
}
