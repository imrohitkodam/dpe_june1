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
use Joomla\Database\ParameterType;

class CalltoactionsModel extends ListModel
{
    protected $filter_fields = [
        'id', 'a.id',
        'title', 'a.title',
        'published', 'a.published',
        'ordering', 'a.ordering',
    ];

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('a.*')
            ->from($db->quoteName('#__seaichat_ctas', 'a'));

        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        }

        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = '%' . trim($search) . '%';
            $query->where(
                '(' . $db->quoteName('a.title') . ' LIKE :search1'
                . ' OR ' . $db->quoteName('a.keywords') . ' LIKE :search2'
                . ' OR ' . $db->quoteName('a.button_label') . ' LIKE :search3)'
            )
            ->bind(':search1', $search)
            ->bind(':search2', $search)
            ->bind(':search3', $search);
        }

        $orderCol = $this->state->get('list.ordering', 'a.ordering');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
    }
}
