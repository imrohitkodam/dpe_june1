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



use RegularLabs\Plugin\System\ArticlesAnywhere\Numbers\Numbers as MainNumbers;

class Numbers extends DataGroup
{
    protected static $data_key_aliases = [];
    protected static $db_prefix        = 'numbers';
    protected static $main_table       = '';
    protected static $prefix           = '';

    public function getQueryKeys()
    {
        return [];
    }

    public function getRequiredQueryKeys()
    {
        return [];
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        if ( ! ($this->numbers) instanceof MainNumbers)
        {
            return '';
        }

        return $this->numbers->get($this->key);
    }

    protected static function getPossiblePlainKeys($database_name = '')
    {
        return [
            ...array_keys(MainNumbers::getDefaultNumbers()),
            ...array_keys(MainNumbers::getAliases()),
        ];
    }

    protected static function getPossibleRegexKeys()
    {
        return [
            '^every-[0-9]+',
            '^is-[0-9]+-of-[0-9]+',
        ];
    }
}
