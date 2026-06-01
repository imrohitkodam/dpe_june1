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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text as JText;

class Boolean
{
    public static function process($string, $key, $attributes)
    {
        if ( ! is_bool($string))
        {
            return $string;
        }


        return $string ? JText::_('JYES') : JText::_('JNO');
    }
}
