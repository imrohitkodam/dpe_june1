<?php
/**
 * @package         Modals
 * @version         12.3.5PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2023 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace RegularLabs\Plugin\System\Modals;

defined('_JEXEC') or die;

use RegularLabs\Library\File as RL_File;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;

class File
{
    public static function getCleanFileName($url)
    {
        $title = RL_File::getFileName($url);

        // Remove trailing numbers and dimensions
        $title = RL_RegEx::replace('[_-][0-9]+(x[0-9]+)?$', '', $title);

        return $title;
    }

    public static function getCleanTitle($url)
    {
        $title = self::getCleanFileName($url);

        // Replace dashes with spaces
        return str_replace(['-', '_'], ' ', $title);
    }

    public static function getTitle($url, $case)
    {
        $params = Params::get();

        $title = self::getCleanTitle($url);

        switch ($case)
        {
            case 'lowercase':
                return RL_String::strtolower($title);

            case 'uppercase':
                return RL_String::strtoupper($title);

            case 'uppercasefirst':
                return RL_String::strtoupper(RL_String::substr($title, 0, 1))
                    . RL_String::strtolower(RL_String::substr($title, 1));

            case 'titlecase':
                return function_exists('mb_convert_case')
                    ? mb_convert_case(RL_String::strtolower($title), MB_CASE_TITLE)
                    : ucwords(strtolower($title));

            case 'titlecase_smart':
                $title           = function_exists('mb_convert_case')
                    ? mb_convert_case(RL_String::strtolower($title), MB_CASE_TITLE)
                    : ucwords(strtolower($title));
                $lowercase_words = explode(',', ' ' . str_replace(',', ' , ', RL_String::strtolower($params->lowercase_words)) . ' ');

                return str_ireplace($lowercase_words, $lowercase_words, $title);

            default:
                return $title;
        }
    }

    public static function isVideo($url, $data)
    {
        if (isset($data->video) && $data->video == 'true')
        {
            return true;
        }

        return RL_File::isExternalVideo($url) || RL_File::isVideo($url);
    }
}
