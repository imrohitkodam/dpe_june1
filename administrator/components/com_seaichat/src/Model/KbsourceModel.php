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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\ParameterType;

class KbsourceModel extends AdminModel
{
    protected $text_prefix = 'COM_SEAICHAT';

    public function getTable($name = 'Kbsource', $prefix = 'Administrator', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_seaichat.kbsource', 'kbsource', ['control' => 'jform', 'load_data' => $loadData]);
        if (empty($form)) {
            return false;
        }
        return $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_seaichat.edit.kbsource.data', []);
        if (empty($data)) {
            $data = $this->getItem();
        }
        return $data;
    }

    public function save($data)
    {
        $isNew = empty($data['id']);
        if ($isNew) {
            $data['created'] = Factory::getDate()->toSql();
            $data['crawl_status'] = 'pending';
        }

        return parent::save($data);
    }

    public function delete(&$pks)
    {
        $db = $this->getDatabase();

        foreach ($pks as $pk) {
            $pk = (int) $pk;

            // Delete chunks
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__seaichat_kb_chunks'))
                ->where($db->quoteName('source_id') . ' = :sid')
                ->bind(':sid', $pk, ParameterType::INTEGER);
            $db->setQuery($query);
            $db->execute();

            // Delete pages
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__seaichat_kb_pages'))
                ->where($db->quoteName('source_id') . ' = :sid')
                ->bind(':sid', $pk, ParameterType::INTEGER);
            $db->setQuery($query);
            $db->execute();
        }

        return parent::delete($pks);
    }
}
