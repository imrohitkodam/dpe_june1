<?php
/**
 * @package         Better Preview
 * @version         6.7.1PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2022 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Filesystem\Folder as JFolder;

class PlgSystemBetterPreviewInstallerScript
{
    public function postflight($install_type, $adapter)
    {
        if ( ! in_array($install_type, ['install', 'update']))
        {
            return true;
        }

        self::createTable();

        self::convertOldSettings();
        self::fixSystemPluginOrdering();

        JFactory::getCache()->clean('_system');

        self::deleteOldModule();

        return true;
    }

    public function uninstall($adapter)
    {
        self::dropTable();
    }

    private static function convertOldSettings()
    {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true)
            ->select('params')
            ->from('#__extensions')
            ->where($db->quoteName('element') . ' = ' . $db->quote('betterpreview'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
        $db->setQuery($query);

        $params = $db->loadResult();

        if (strpos($params, 'default_menu_id') !== false)
        {
            return;
        }

        $params = str_replace('"use_home_menu_id":"0"', '"default_menu_id":"-1"', $params);

        $query = $db->getQuery(true)
            ->update('#__extensions')
            ->set($db->quoteName('params') . ' = ' . $db->quote($params))
            ->where($db->quoteName('element') . ' = ' . $db->quote('betterpreview'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
        $db->setQuery($query);
        $db->execute();
    }

    private static function createTable()
    {
        $db = JFactory::getDbo();

        $query = "CREATE TABLE IF NOT EXISTS `#__betterpreview_sefs` (
            `url` char(255) NOT NULL,
            `sef` char(255) NOT NULL,
            `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            KEY  (`url`(50))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $db->setQuery($query);
        $db->execute();

        $query = 'SHOW INDEX FROM ' . $db->quoteName('#__betterpreview_sefs');
        $db->setQuery($query);
        $index = $db->loadObject();

        if ( ! empty($index->Key_name) && $index->Key_name == 'PRIMARY')
        {
            $query = 'ALTER TABLE ' . $db->quoteName('#__betterpreview_sefs')
                . ' DROP INDEX ' . $db->quoteName($index->Key_name) . ','
                . ' ADD INDEX ' . $db->quoteName('url') . ' (' . $db->quoteName('url') . '(50));';
            $db->setQuery($query);
            $db->execute();
        }

        // delete all cached sef urls
        $db->truncateTable('#__betterpreview_sefs');
    }

    private static function deleteOldModule()
    {
        $db = JFactory::getDbo();

        // delete old module
        $query = $db->getQuery(true)
            ->delete('#__extensions')
            ->where($db->quoteName('element') . ' = ' . $db->quote('mod_betterpreview'));
        $db->setQuery($query);
        $db->execute();

        $query->clear()
            ->delete('#__modules')
            ->where($db->quoteName('module') . ' = ' . $db->quote('mod_betterpreview'));
        $db->setQuery($query);
        $db->execute();

        $folder = JPATH_ADMINISTRATOR . '/modules/mod_betterpreview';
        if (JFolder::exists($folder))
        {
            JFolder::delete($folder);
        }

        JFactory::getCache()->clean('_system');
    }

    private static function dropTable()
    {
        $db = JFactory::getDbo();

        $db->dropTable('#__betterpreview_sefs');
    }

    private static function fixSystemPluginOrdering()
    {
        $db = JFactory::getDbo();

        // force system plugin ordering
        $query = $db->getQuery(true)
            ->update('#__extensions')
            ->set($db->quoteName('ordering') . ' = -1')
            ->where($db->quoteName('element') . ' = ' . $db->quote('betterpreview'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
        $db->setQuery($query);
        $db->execute();
    }
}
