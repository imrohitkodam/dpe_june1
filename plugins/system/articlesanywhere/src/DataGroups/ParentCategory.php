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



use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;

class ParentCategory extends Category
{
    protected static $db_prefix   = 'parent-category';
    protected static $layout_name = 'parent_category';
    protected static $prefix      = 'parent-category';

    /**
     * @return array [table => condition]
     */
    public function getJoins()
    {
        return [
            ...parent::getJoins(),
            DB::quoteName('#__categories', self::getDBPrefix()) =>
                DB::quoteName(self::getDBPrefix() . '.id') . ' = ' . DB::quoteName('category.parent_id'),
        ];
    }
}
