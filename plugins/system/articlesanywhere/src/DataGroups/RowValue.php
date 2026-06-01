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



class RowValue extends DataGroup
{
    protected static $data_key_aliases = [
        'row' => 'text',
    ];
    protected static $db_prefix        = 'row';
    protected static $default_data_key = 'text';
    protected static $prefix           = '';

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->values[$this->key] ?? '';
    }

    protected static function getPossiblePlainKeys($database_name = '')
    {
        return ['row', 'value', 'text'];
    }

    protected static function getValueKeys()
    {
        return ['value', 'text'];
    }
}
