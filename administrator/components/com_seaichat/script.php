<?php

/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Factory;

class Com_seaichatInstallerScript
{
    public function preflight($type, InstallerAdapter $adapter): bool
    {
        if ($type === 'install' || $type === 'update') {
            $this->cleanStaleMenus();
        }
        return true;
    }

    public function postflight($type, InstallerAdapter $adapter): void
    {
        // Clear autoloader caches
        $cachePaths = [
            JPATH_CACHE . '/autoload_psr4.php',
            JPATH_ADMINISTRATOR . '/cache/autoload_psr4.php',
            JPATH_ROOT . '/cache/autoload_psr4.php',
        ];
        foreach ($cachePaths as $cacheFile) {
            if (file_exists($cacheFile)) {
                @unlink($cacheFile);
            }
        }
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        if ($type === 'update') {
            $this->createTables();
        }

        $this->installPlugin();
        $this->fixAdminMenus();
    }

    /**
     * Remove any orphaned/stale com_seaichat menu entries BEFORE Joomla tries to create new ones.
     * This prevents the "alias already in use" error on re-install.
     */
    private function cleanStaleMenus(): void
    {
        try {
            $db = Factory::getContainer()->get('DatabaseDriver');

            // Delete ALL existing admin menu items for com_seaichat
            // Joomla's installer will recreate the parent from the manifest,
            // and our postflight will recreate the submenu items
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__menu'))
                ->where($db->quoteName('client_id') . ' = 1')
                ->where('(' 
                    . $db->quoteName('link') . ' LIKE ' . $db->quote('%option=com_seaichat%')
                    . ' OR ' . $db->quoteName('alias') . ' LIKE ' . $db->quote('com-seaichat%')
                . ')');
            $db->setQuery($query);
            $db->execute();

            // Rebuild the admin menu tree to fix lft/rgt after deletions
            $table = new \Joomla\CMS\Table\Menu($db);
            $table->rebuild();
        } catch (\Exception $e) {
            // Non-critical — installer may still succeed
        }
    }

    public function uninstall(InstallerAdapter $adapter): void
    {
        $db = Factory::getContainer()->get('DatabaseDriver');

        // Remove the system plugin
        try {
            $query = $db->getQuery(true)
                ->select('extension_id')
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
            $db->setQuery($query);
            $pluginId = $db->loadResult();

            if ($pluginId) {
                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__extensions'))
                    ->where($db->quoteName('extension_id') . ' = ' . (int) $pluginId);
                $db->setQuery($query);
                $db->execute();
            }
        } catch (\Exception $e) {}

        $pluginDir = JPATH_PLUGINS . '/system/seaichat';
        if (is_dir($pluginDir)) {
            $files = glob($pluginDir . '/*');
            foreach ($files as $file) {
                @unlink($file);
            }
            @rmdir($pluginDir);
        }
    }

    /**
     * Ensure the admin submenu items exist in #__menu.
     */
    private function fixAdminMenus(): void
    {
        $db = Factory::getContainer()->get('DatabaseDriver');

        // Find the component's extension_id
        $query = $db->getQuery(true)
            ->select('extension_id')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        $db->setQuery($query);
        $componentId = (int) $db->loadResult();

        if (!$componentId) {
            return;
        }

        // Find the parent menu item (level 1) for this component
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('client_id') . ' = 1')
            ->where($db->quoteName('component_id') . ' = ' . $componentId)
            ->where($db->quoteName('level') . ' = 1')
            ->order('id ASC');
        $db->setQuery($query, 0, 1);
        $parentId = (int) $db->loadResult();

        if (!$parentId) {
            return;
        }

        // Define the submenu items
        $submenus = [
            ['title' => 'Dashboard',      'alias' => 'seaichat-dashboard',      'link' => 'index.php?option=com_seaichat&view=dashboard'],
            ['title' => 'Knowledge Base',  'alias' => 'seaichat-knowledgebases', 'link' => 'index.php?option=com_seaichat&view=knowledgebases'],
            ['title' => 'Call to Actions', 'alias' => 'seaichat-calltoactions',  'link' => 'index.php?option=com_seaichat&view=calltoactions'],
            ['title' => 'Chat Logs',       'alias' => 'seaichat-chatlogs',       'link' => 'index.php?option=com_seaichat&view=chatlogs'],
            ['title' => 'Settings',        'alias' => 'seaichat-settings',       'link' => 'index.php?option=com_seaichat&view=settings'],
        ];

        foreach ($submenus as $sub) {
            // Check if already exists
            $query = $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__menu'))
                ->where($db->quoteName('client_id') . ' = 1')
                ->where($db->quoteName('parent_id') . ' = ' . $parentId)
                ->where($db->quoteName('link') . ' = ' . $db->quote($sub['link']));
            $db->setQuery($query);

            if ($db->loadResult()) {
                continue;
            }

            $menuItem = new \stdClass();
            $menuItem->menutype = 'main';
            $menuItem->title = $sub['title'];
            $menuItem->alias = $sub['alias'];
            $menuItem->path = $sub['alias'];
            $menuItem->link = $sub['link'];
            $menuItem->type = 'component';
            $menuItem->published = 1;
            $menuItem->parent_id = $parentId;
            $menuItem->level = 2;
            $menuItem->component_id = $componentId;
            $menuItem->access = 1;
            $menuItem->img = '';
            $menuItem->template_style_id = 0;
            $menuItem->params = '{}';
            $menuItem->client_id = 1;
            $menuItem->language = '*';

            try {
                $db->insertObject('#__menu', $menuItem, 'id');
            } catch (\Exception $e) {
                // Alias conflict — try unique alias
                $menuItem->alias = $sub['alias'] . '-' . substr(md5((string) time()), 0, 6);
                $menuItem->path = $menuItem->alias;
                try {
                    $db->insertObject('#__menu', $menuItem, 'id');
                } catch (\Exception $e2) {}
            }
        }

        // Rebuild menu tree
        try {
            $table = new \Joomla\CMS\Table\Menu($db);
            $table->rebuild();
        } catch (\Exception $e) {}
    }

    private function installPlugin(): void
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $pluginDir = JPATH_PLUGINS . '/system/seaichat';
        $sourceDir = JPATH_ADMINISTRATOR . '/components/com_seaichat/plugins/system/seaichat';

        if (!is_dir($sourceDir)) {
            return;
        }

        if (!is_dir($pluginDir)) {
            if (!@mkdir($pluginDir, 0755, true)) {
                \Joomla\CMS\Filesystem\Folder::create($pluginDir);
            }
        }

        $files = glob($sourceDir . '/*');
        foreach ($files as $file) {
            $dest = $pluginDir . '/' . basename($file);
            if (!@copy($file, $dest)) {
                \Joomla\CMS\Filesystem\File::copy($file, $dest);
            }
        }

        // Check if plugin already registered
        $query = $db->getQuery(true)
            ->select('extension_id')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('seaichat'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
        $db->setQuery($query);
        $existing = $db->loadObject();

        if ($existing) {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('extension_id') . ' = ' . (int) $existing->extension_id);
            $db->setQuery($query);
            $db->execute();
        } else {
            // Dynamically discover columns to avoid NOT NULL constraint errors
            try {
                $columns = $db->getTableColumns('#__extensions', false);
                $colNames = array_keys($columns);
                $manifestCache = json_encode([
                    'name' => 'System - SE AI Chatbot Widget', 'type' => 'plugin',
                    'version' => '2.0.4', 'group' => 'system',
                ]);

                $values = [];
                foreach ($colNames as $col) {
                    switch ($col) {
                        case 'extension_id': continue 2;
                        case 'name':           $values[$col] = $db->quote('System - SE AI Chatbot Widget'); break;
                        case 'type':           $values[$col] = $db->quote('plugin'); break;
                        case 'element':        $values[$col] = $db->quote('seaichat'); break;
                        case 'folder':         $values[$col] = $db->quote('system'); break;
                        case 'client_id':      $values[$col] = '0'; break;
                        case 'enabled':        $values[$col] = '1'; break;
                        case 'access':         $values[$col] = '1'; break;
                        case 'protected':      $values[$col] = '0'; break;
                        case 'locked':         $values[$col] = '0'; break;
                        case 'manifest_cache': $values[$col] = $db->quote($manifestCache); break;
                        case 'params':         $values[$col] = $db->quote('{}'); break;
                        case 'ordering':       $values[$col] = '0'; break;
                        case 'state':          $values[$col] = '0'; break;
                        case 'package_id':     $values[$col] = '0'; break;
                        case 'custom_data':    $values[$col] = $db->quote(''); break;
                        case 'note':           $values[$col] = $db->quote(''); break;
                        case 'changelogurl':   $values[$col] = $db->quote(''); break;
                        case 'checked_out':    $values[$col] = 'NULL'; break;
                        case 'checked_out_time': $values[$col] = 'NULL'; break;
                        default:
                            $colType = strtolower($columns[$col]->Type ?? '');
                            if (strpos($colType, 'int') !== false) $values[$col] = '0';
                            elseif (strpos($colType, 'datetime') !== false) $values[$col] = 'NULL';
                            else $values[$col] = $db->quote('');
                            break;
                    }
                }

                $sql = 'INSERT INTO ' . $db->quoteName('#__extensions')
                    . ' (' . implode(', ', array_map([$db, 'quoteName'], array_keys($values))) . ')'
                    . ' VALUES (' . implode(', ', $values) . ')';
                $db->setQuery($sql);
                $db->execute();
            } catch (\Exception $e) {
                // Silent fail
            }
        }
    }

        private function createTables(): void
    {
        $db = Factory::getContainer()->get('DatabaseDriver');

        $tables = [
            "CREATE TABLE IF NOT EXISTS `#__seaichat_kb_sources` (
                `id` int(11) NOT NULL AUTO_INCREMENT, `title` varchar(255) NOT NULL DEFAULT '',
                `source_type` varchar(10) NOT NULL DEFAULT 'url', `url` varchar(1000) NOT NULL DEFAULT '',
                `content` mediumtext NULL, `published` tinyint(3) NOT NULL DEFAULT 1,
                `last_crawled` datetime NULL DEFAULT NULL, `page_count` int(11) NOT NULL DEFAULT 0,
                `chunk_count` int(11) NOT NULL DEFAULT 0, `crawl_status` varchar(20) NOT NULL DEFAULT 'pending',
                `crawl_error` text NULL, `created` datetime NOT NULL, `ordering` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`), KEY `idx_published` (`published`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `#__seaichat_kb_pages` (
                `id` int(11) NOT NULL AUTO_INCREMENT, `source_id` int(11) NOT NULL,
                `url` varchar(1000) NOT NULL DEFAULT '', `title` varchar(500) NOT NULL DEFAULT '',
                `content_hash` varchar(64) NOT NULL DEFAULT '', `crawled` datetime NOT NULL,
                PRIMARY KEY (`id`), KEY `idx_source_id` (`source_id`), KEY `idx_url` (`url`(191))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `#__seaichat_kb_chunks` (
                `id` int(11) NOT NULL AUTO_INCREMENT, `source_id` int(11) NOT NULL,
                `page_id` int(11) NOT NULL, `page_title` varchar(500) NOT NULL DEFAULT '',
                `page_url` varchar(1000) NOT NULL DEFAULT '', `content` mediumtext NOT NULL,
                `keywords` text NOT NULL, `chunk_index` int(11) NOT NULL DEFAULT 0,
                `token_count` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`), KEY `idx_source_id` (`source_id`), KEY `idx_page_id` (`page_id`),
                FULLTEXT KEY `ft_content` (`content`, `keywords`, `page_title`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `#__seaichat_chat_sessions` (
                `id` int(11) NOT NULL AUTO_INCREMENT, `session_key` varchar(64) NOT NULL,
                `user_id` int(11) NOT NULL DEFAULT 0, `messages` mediumtext NOT NULL,
                `status` varchar(20) NOT NULL DEFAULT 'active', `created` datetime NOT NULL,
                `modified` datetime NOT NULL,
                PRIMARY KEY (`id`), UNIQUE KEY `idx_session_key` (`session_key`),
                KEY `idx_user_id` (`user_id`), KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        foreach ($tables as $sql) {
            try { $db->setQuery($sql); $db->execute(); } catch (\Exception $e) {}
        }

        // Widen source_type column for SP Page Builder
        try {
            $db->setQuery("ALTER TABLE `#__seaichat_kb_sources` MODIFY `source_type` varchar(20) NOT NULL DEFAULT 'url'");
            $db->execute();
        } catch (\Exception $e) {}

        // Add categories column for article source type (upgrade from earlier versions)
        try {
            $db->setQuery("ALTER TABLE `#__seaichat_kb_sources` ADD COLUMN `categories` varchar(1000) NOT NULL DEFAULT '' AFTER `content`");
            $db->execute();
        } catch (\Exception $e) {}

        // Create Call to Actions table (v2.1+)
        try {
            $db->setQuery("CREATE TABLE IF NOT EXISTS `#__seaichat_ctas` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL DEFAULT '',
                `keywords` varchar(1000) NOT NULL DEFAULT '',
                `button_label` varchar(255) NOT NULL DEFAULT '',
                `button_url` varchar(1000) NOT NULL DEFAULT '',
                `button_icon` varchar(100) NOT NULL DEFAULT 'fa-arrow-up-right-from-square',
                `button_target` varchar(10) NOT NULL DEFAULT '_self',
                `published` tinyint(3) NOT NULL DEFAULT 1,
                `ordering` int(11) NOT NULL DEFAULT 0,
                `created` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_published` (`published`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $db->execute();
        } catch (\Exception $e) {}
    }
}
