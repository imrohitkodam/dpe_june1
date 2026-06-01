<?php


/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace SolarEclipse\Component\SeAiChat\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;

class ChatlogsModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('cs.*, u.name as user_name, u.email as user_email')
            ->from($db->quoteName('#__seaichat_chat_sessions', 'cs'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = cs.user_id');

        // Filter by search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $searchTerm = '%' . $db->escape(trim($search), true) . '%';
            $query->where('(u.name LIKE ' . $db->quote($searchTerm) . ' OR u.email LIKE ' . $db->quote($searchTerm) . ' OR cs.messages LIKE ' . $db->quote($searchTerm) . ')');
        }

        // Filter by status
        $status = $this->getState('filter.status');
        if (!empty($status)) {
            $query->where($db->quoteName('cs.status') . ' = ' . $db->quote($status));
        }

        $query->order('cs.modified DESC');

        return $query;
    }

    protected function populateState($ordering = 'cs.modified', $direction = 'DESC')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        $status = $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status', '', 'string');
        $this->setState('filter.status', $status);

        parent::populateState($ordering, $direction);
    }

    /**
     * Get all chat sessions for CSV export (no pagination limit).
     * Respects current search and status filters.
     */
    public function getExportItems(): array
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('cs.*, u.name as user_name, u.email as user_email')
            ->from($db->quoteName('#__seaichat_chat_sessions', 'cs'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = cs.user_id');

        // Filter by search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $searchTerm = '%' . $db->escape(trim($search), true) . '%';
            $query->where('(u.name LIKE ' . $db->quote($searchTerm) . ' OR u.email LIKE ' . $db->quote($searchTerm) . ' OR cs.messages LIKE ' . $db->quote($searchTerm) . ')');
        }

        // Filter by status
        $status = $this->getState('filter.status');
        if (!empty($status)) {
            $query->where($db->quoteName('cs.status') . ' = ' . $db->quote($status));
        }

        $query->order('cs.modified DESC');

        $db->setQuery($query);
        return $db->loadObjectList() ?: [];
    }
}
