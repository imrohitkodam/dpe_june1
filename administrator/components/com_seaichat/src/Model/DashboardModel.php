<?php


/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace SolarEclipse\Component\SeAiChat\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;

class DashboardModel extends BaseDatabaseModel
{
    public function getStats(): object
    {
        $db = $this->getDatabase();
        $stats = new \stdClass();

        try {
            $query = $db->getQuery(true)->select('COUNT(*)')->from('#__seaichat_chat_sessions');
            $db->setQuery($query); $stats->total_chats = (int) $db->loadResult();
        } catch (\Exception $e) { $stats->total_chats = 0; }

        try {
            $query = $db->getQuery(true)->select('COUNT(*)')->from('#__seaichat_chat_sessions')->where("status = 'active'");
            $db->setQuery($query); $stats->active_chats = (int) $db->loadResult();
        } catch (\Exception $e) { $stats->active_chats = 0; }

        try {
            $query = $db->getQuery(true)->select('COUNT(*)')->from('#__seaichat_kb_sources')->where('published = 1');
            $db->setQuery($query); $stats->kb_sources = (int) $db->loadResult();
        } catch (\Exception $e) { $stats->kb_sources = 0; }

        try {
            $query = $db->getQuery(true)->select('COUNT(*)')->from('#__seaichat_kb_chunks');
            $db->setQuery($query); $stats->kb_chunks = (int) $db->loadResult();
        } catch (\Exception $e) { $stats->kb_chunks = 0; }

        try {
            $today = Factory::getDate('now', 'UTC')->format('Y-m-d');
            $query = $db->getQuery(true)->select('COUNT(*)')->from('#__seaichat_chat_sessions')
                ->where($db->quoteName('created') . ' >= ' . $db->quote($today . ' 00:00:00'));
            $db->setQuery($query); $stats->chats_today = (int) $db->loadResult();
        } catch (\Exception $e) { $stats->chats_today = 0; }

        return $stats;
    }

    public function getRecentChats(int $limit = 10): array
    {
        $db = $this->getDatabase();
        try {
            $query = $db->getQuery(true)
                ->select('cs.*, u.name as user_name')
                ->from($db->quoteName('#__seaichat_chat_sessions', 'cs'))
                ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = cs.user_id')
                ->order('cs.modified DESC');
            $db->setQuery($query, 0, $limit);
            return $db->loadObjectList() ?: [];
        } catch (\Exception $e) { return []; }
    }

    public function getDiagnostics(): object
    {
        $db = $this->getDatabase();
        $diag = new \stdClass();

        // Read raw params
        $query = $db->getQuery(true)
            ->select($db->quoteName('params'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        $db->setQuery($query);
        $p = json_decode($db->loadResult(), true) ?: [];

        $diag->ai_enabled = !empty($p['aichat_enabled']);
        $diag->api_key_set = !empty(trim($p['aichat_api_key'] ?? ''));
        $diag->provider = $p['aichat_provider'] ?? 'anthropic';

        // Check plugin in extensions table
        $query = $db->getQuery(true)
            ->select('extension_id, enabled')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('seaichat'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
        $db->setQuery($query);
        $plugin = $db->loadObject();

        $diag->plugin_registered = !empty($plugin);
        $diag->plugin_enabled = !empty($plugin) && (int) $plugin->enabled === 1;
        $diag->plugin_file_exists = file_exists(JPATH_PLUGINS . '/system/seaichat/seaichat.php');

        // Menu items configured
        $menuItems = trim($p['aichat_menu_items'] ?? '');
        $diag->menu_filter = empty($menuItems) ? 'All pages' : count(array_filter(explode(',', $menuItems))) . ' pages selected';

        return $diag;
    }
}
