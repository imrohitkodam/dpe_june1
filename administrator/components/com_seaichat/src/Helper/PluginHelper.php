<?php


/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace SolarEclipse\Component\SeAiChat\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class PluginHelper
{
    /**
     * Ensure the system plugin is fully installed and enabled.
     */
    public static function ensurePluginInstalled(): array
    {
        $result = ['success' => false, 'message' => '', 'actions' => []];

        try {
            // Step 1: Copy plugin files
            $sourceDir = JPATH_ADMINISTRATOR . '/components/com_seaichat/plugins/system/seaichat';
            $pluginDir = JPATH_PLUGINS . '/system/seaichat';

            if (!is_dir($sourceDir)) {
                $result['message'] = 'Source plugin files not found in component directory.';
                return $result;
            }

            if (!is_dir($pluginDir)) {
                if (!@mkdir($pluginDir, 0755, true)) {
                    try {
                        \Joomla\CMS\Filesystem\Folder::create($pluginDir);
                    } catch (\Exception $e) {
                        $result['message'] = 'Cannot create plugin directory: ' . $e->getMessage();
                        return $result;
                    }
                }
                $result['actions'][] = 'Created plugin directory';
            }

            $files = glob($sourceDir . '/*');
            foreach ($files as $file) {
                $dest = $pluginDir . '/' . basename($file);
                if (@copy($file, $dest)) {
                    $result['actions'][] = 'Copied ' . basename($file);
                } else {
                    try {
                        \Joomla\CMS\Filesystem\File::copy($file, $dest);
                        $result['actions'][] = 'Copied ' . basename($file);
                    } catch (\Exception $e) {
                        $result['message'] = 'Cannot copy file: ' . $e->getMessage();
                        return $result;
                    }
                }
            }

            // Step 2: Register in #__extensions
            $db = Factory::getContainer()->get('DatabaseDriver');

            $query = $db->getQuery(true)
                ->select('extension_id, enabled')
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
            $db->setQuery($query);
            $existing = $db->loadObject();

            if ($existing) {
                if ((int) $existing->enabled !== 1) {
                    $query = $db->getQuery(true)
                        ->update($db->quoteName('#__extensions'))
                        ->set($db->quoteName('enabled') . ' = 1')
                        ->where($db->quoteName('extension_id') . ' = ' . (int) $existing->extension_id);
                    $db->setQuery($query);
                    $db->execute();
                    $result['actions'][] = 'Enabled existing plugin';
                } else {
                    $result['actions'][] = 'Plugin already registered and enabled';
                }
            } else {
                // Dynamically discover all columns in the table to build a safe INSERT
                $columns = $db->getTableColumns('#__extensions', false);
                $colNames = array_keys($columns);

                $values = [];
                foreach ($colNames as $col) {
                    switch ($col) {
                        case 'extension_id': continue 2; // skip auto-increment
                        case 'name':           $values[$col] = $db->quote('System - SE AI Chatbot Widget'); break;
                        case 'type':           $values[$col] = $db->quote('plugin'); break;
                        case 'element':        $values[$col] = $db->quote('seaichat'); break;
                        case 'folder':         $values[$col] = $db->quote('system'); break;
                        case 'client_id':      $values[$col] = '0'; break;
                        case 'enabled':        $values[$col] = '1'; break;
                        case 'access':         $values[$col] = '1'; break;
                        case 'protected':      $values[$col] = '0'; break;
                        case 'locked':         $values[$col] = '0'; break;
                        case 'manifest_cache': $values[$col] = $db->quote(json_encode([
                            'name' => 'System - SE AI Chatbot Widget',
                            'type' => 'plugin', 'version' => '1.0.0',
                            'description' => 'Displays the SE AI Chatbot widget sitewide.',
                            'group' => 'system',
                        ])); break;
                        case 'params':         $values[$col] = $db->quote('{}'); break;
                        case 'ordering':       $values[$col] = '0'; break;
                        case 'state':          $values[$col] = '0'; break;
                        case 'package_id':     $values[$col] = '0'; break;
                        // All text/varchar NOT NULL columns get empty string
                        case 'custom_data':    $values[$col] = $db->quote(''); break;
                        case 'note':           $values[$col] = $db->quote(''); break;
                        case 'changelogurl':   $values[$col] = $db->quote(''); break;
                        case 'checked_out':    $values[$col] = 'NULL'; break;
                        case 'checked_out_time': $values[$col] = 'NULL'; break;
                        default:
                            // For any unknown column, try empty string or 0
                            $colType = strtolower($columns[$col]->Type ?? '');
                            if (strpos($colType, 'int') !== false || strpos($colType, 'tinyint') !== false) {
                                $values[$col] = '0';
                            } elseif (strpos($colType, 'datetime') !== false || strpos($colType, 'timestamp') !== false) {
                                $values[$col] = 'NULL';
                            } else {
                                $values[$col] = $db->quote('');
                            }
                            break;
                    }
                }

                $sql = 'INSERT INTO ' . $db->quoteName('#__extensions')
                    . ' (' . implode(', ', array_map([$db, 'quoteName'], array_keys($values))) . ')'
                    . ' VALUES (' . implode(', ', $values) . ')';

                $db->setQuery($sql);
                $db->execute();
                $newId = $db->insertid();
                $result['actions'][] = 'Registered plugin (ID: ' . $newId . ')';
            }

            $result['success'] = true;
            $result['message'] = 'Plugin installed and enabled successfully.';
        } catch (\Exception $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Check plugin status.
     */
    public static function getStatus(): object
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $status = new \stdClass();

        try {
            $query = $db->getQuery(true)
                ->select('extension_id, enabled')
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
            $db->setQuery($query);
            $plugin = $db->loadObject();

            $status->registered = !empty($plugin);
            $status->enabled = !empty($plugin) && (int) $plugin->enabled === 1;
        } catch (\Exception $e) {
            $status->registered = false;
            $status->enabled = false;
        }

        $status->file_exists = file_exists(JPATH_PLUGINS . '/system/seaichat/seaichat.php');

        return $status;
    }
}
