<?php
/**
 * @package         Articles Anywhere
 * @version         17.2.10PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups;

defined('_JEXEC') or die;



use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Library\User as RL_User;

class User extends DataGroup
{
    protected static $db_prefix        = 'user';
    protected static $default_data_key = 'name';
    protected static $prefix           = 'user';

    /**
     * @return mixed
     */
    public function getValue()
    {
        if ($this->key === 'password')
        {
            return '***';
        }

        $camelcase_key = RL_String::toCamelCase($this->key);

        return RL_User::getValue($this->key, null, RL_User::getValue($camelcase_key));
    }
}
