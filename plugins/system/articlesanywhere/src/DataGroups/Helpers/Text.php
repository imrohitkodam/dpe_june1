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

use RegularLabs\Library\Text as RL_Text;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data as DataHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class Text extends RL_Text
{
    protected static string $comment_prefix = 'Articles Anywhere';

    public static function process($string, $key, $attributes)
    {
        if (isset($attributes->id) || isset($attributes->element))
        {
            $id    = $attributes->id ?? $attributes->element;
            $value = DataHelper::getValue($id);

            if ( ! empty($value))
            {
                $attributes->id = DataHelper::getValueFromValue($value);
                unset($attributes->element);
            }
        }

        return parent::process($string, $key, $attributes);
    }

    protected static function limit($string, $attributes)
    {
        $attributes->add_ellipsis = $attributes->add_ellipsis ?? Params::get()->use_ellipsis;

        return parent::limit($string, $attributes);
    }

    protected static function limitHtml($string, $attributes)
    {
        $attributes->add_ellipsis = $attributes->add_ellipsis ?? Params::get()->use_ellipsis;

        return parent::limitHtml($string, $attributes);
    }
}
