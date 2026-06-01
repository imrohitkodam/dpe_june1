<?php

/**
 * @package         Regular Labs Library
 * @version         25.11.14282
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */
namespace RegularLabs\Library;

defined('_JEXEC') or die;
use Joomla\CMS\Layout\FileLayout as JFileLayout;
class Layout
{
    static $layouts = [];
    public static function get($layout_id, $layout_path, $extension)
    {
        $key = $extension . '.' . $layout_id;
        if (isset(self::$layouts[$key])) {
            return self::$layouts[$key];
        }
        $layout = new JFileLayout($layout_id);
        $default_paths = $layout->getDefaultIncludePaths();
        $default_paths = array_reverse($default_paths);
        $layout->addIncludePath($layout_path);
        foreach ($default_paths as $path) {
            $layout->addIncludePath($path . '/' . $extension);
        }
        self::$layouts[$key] = $layout;
        return self::$layouts[$key];
    }
}
