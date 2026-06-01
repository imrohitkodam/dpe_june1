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

class Helper
{
    public static function addClassName($class, $class_name)
    {
        if (empty($class_name))
        {
            return $class;
        }

        $classes     = explode(' ', $class);
        $class_names = explode(' ', $class_name);

        foreach ($class_names as $class_name)
        {
            if (empty($class_name) || in_array($class_name, $classes))
            {
                continue;
            }

            $classes[] = $class_name;
        }

        return implode(' ', $classes);
    }

    public static function createInlineContentBlock($content, $id = null)
    {
        $id = $id ?: uniqid('modal_') . rand(1000, 9999);

        return [
            '<div style="display:none;">'
            . '<div id="' . $id . '">'
            . $content
            . '</div></div>',
            $id,
        ];
    }

    public static function removeClassname($class, $class_name)
    {
        if (empty($class_name))
        {
            return $class;
        }

        $classes     = explode(' ', $class);
        $class_names = explode(' ', $class_name);

        $classes = array_diff($classes, $class_names);

        return implode(' ', $classes);
    }
}
