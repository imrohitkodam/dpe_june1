<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Category/DOCman Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanDocmanActivityCategory extends ComLogmanModelEntityActivity
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'format'        => '{actor} {action} {object.subtype} {object.type} title {object}',
            'object_table'  => 'docman_categories',
            'object_column' => 'docman_category_id'
        ));

        parent::_initialize($config);
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        $model = $this->getObject('com://admin/docman.model.categories')->id($this->row)->page('all');

        if ($levels = $this->getViewLevels()) {
            $model->access($levels);
        }

        $category = $model->fetch();

        if (!$category->isNew() && $category->itemid && $category->enabled)
        {
            $url = sprintf('option=com_docman&view=category&slug=%s&Itemid=%s', $category->slug, $category->itemid);
            $config->append(array('url' => array('site' => $url)));
        }

        $uuid = null;

        if ($this->_findObject('object'))
        {
            $model->getState()->reset();

            $category = $model->id($this->row)->fetch();

            if (!$category->isNew() && $category->isIdentifiable()) {
                $uuid = $category->uuid;
            }
        }

        $config->append(array(
            'uuid'    => $uuid,
            'subtype' => array('objectName' => 'DOCman', 'object' => true),
            'url'     => array('admin' => 'option=com_docman&view=category&id=' . $this->row)
        ));

        parent::_objectConfig($config);
    }
}