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

class CalltoactionModel extends AdminModel
{
    protected $text_prefix = 'COM_SEAICHAT';

    public function getTable($name = 'Calltoaction', $prefix = 'Administrator', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_seaichat.calltoaction', 'calltoaction', ['control' => 'jform', 'load_data' => $loadData]);
        if (empty($form)) {
            return false;
        }
        return $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_seaichat.edit.calltoaction.data', []);
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
        }

        // Clean up keywords — trim whitespace around each keyword
        if (!empty($data['keywords'])) {
            $keywords = array_map('trim', explode(',', $data['keywords']));
            $keywords = array_filter($keywords, function ($k) { return $k !== ''; });
            $data['keywords'] = implode(', ', $keywords);
        }

        return parent::save($data);
    }
}
