<?php
/**
 * @package         Regular Labs Extension Manager
 * @version         9.2.5
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\Filesystem\File as JFile;
use Joomla\Filesystem\Folder as JFolder;

class Com_RegularLabsManagerInstallerScript
{
    public function postflight($install_type, $adapter)
    {
        if ( ! in_array($install_type, ['install', 'update']))
        {
            return true;
        }

        self::deleteOldFiles();

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

    private static function deleteOldFiles()
    {
        self::delete(
            [
                JPATH_ADMINISTRATOR . '/components/com_regularlabsmanager/controllers',
                JPATH_ADMINISTRATOR . '/components/com_regularlabsmanager/models',
                JPATH_ADMINISTRATOR . '/components/com_regularlabsmanager/views',
                JPATH_ADMINISTRATOR . '/components/com_regularlabsmanager/controller.php',
                JPATH_ADMINISTRATOR . '/components/com_regularlabsmanager/extensions.xml',
                JPATH_ADMINISTRATOR . '/components/com_regularlabsmanager/nonumbermanager.php',
                JPATH_ADMINISTRATOR . '/components/com_regularlabsmanager/regularlabsmanager.php',
                JPATH_ADMINISTRATOR . '/media/regularlabsmanager/css',
                JPATH_ADMINISTRATOR . '/media/regularlabsmanager/images',
                JPATH_ADMINISTRATOR . '/media/regularlabsmanager/less',
                JPATH_ADMINISTRATOR . '/media/regularlabsmanager/js/process.js',
                JPATH_ADMINISTRATOR . '/media/regularlabsmanager/js/process.min.js',
            ]
        );
    }
}
