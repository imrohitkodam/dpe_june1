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

defined('_JEXEC') or die;

use Joomla\CMS\Filesystem\File as JFile;
use Joomla\CMS\Filesystem\Folder as JFolder;

class PlgSystemModalsInstallerScript
{
    public function postflight($install_type, $adapter)
    {
        if ( ! in_array($install_type, ['install', 'update']))
        {
            return true;
        }

        self::deleteJoomla3Files();

        return true;
    }

    private static function delete($files = [])
    {
        foreach ($files as $file)
        {
            if (is_dir($file))
            {
                JFolder::delete($file);
            }

            if (is_file($file))
            {
                JFile::delete($file);
            }
        }
    }

    private static function deleteJoomla3Files()
    {
        self::delete(
            [
                JPATH_SITE . '/media/modals/css/bootstrap.css',
                JPATH_SITE . '/media/modals/css/bootstrap.min.css',
                JPATH_SITE . '/media/modals/css/colorbox1.css',
                JPATH_SITE . '/media/modals/css/colorbox1.min.css',
                JPATH_SITE . '/media/modals/css/colorbox2.css',
                JPATH_SITE . '/media/modals/css/colorbox2.min.css',
                JPATH_SITE . '/media/modals/css/colorbox3.css',
                JPATH_SITE . '/media/modals/css/colorbox3.min.css',
                JPATH_SITE . '/media/modals/css/colorbox4.css',
                JPATH_SITE . '/media/modals/css/colorbox4.min.css',
                JPATH_SITE . '/media/modals/css/colorbox5.css',
                JPATH_SITE . '/media/modals/css/colorbox5.min.css',
                JPATH_SITE . '/media/modals/images',
                JPATH_SITE . '/media/modals/js/jquery.modals.js',
                JPATH_SITE . '/media/modals/js/jquery.modals.min.js',
                JPATH_SITE . '/media/modals/js/jquery.touchSwipe.js',
                JPATH_SITE . '/media/modals/js/jquery.touchSwipe.min.js',
                JPATH_SITE . '/media/modals/less',
                JPATH_SITE . '/plugins/system/modals/vendor',
            ]
        );
    }
}
