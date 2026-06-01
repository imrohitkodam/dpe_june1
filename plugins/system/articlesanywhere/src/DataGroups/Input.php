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



use RegularLabs\Library\Input as RL_Input;

class Input extends DataGroup
{
    protected static $db_prefix = 'input';
    protected static $prefix    = 'input';

    /**
     * @return mixed
     */
    public function getValue()
    {
        return RL_Input::getString($this->key, $this->subkey);
    }
}
