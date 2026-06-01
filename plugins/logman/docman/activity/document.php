<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Document/DOCman Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanDocmanActivityDocument extends ComLogmanModelEntityActivity
{
    protected function _initialize(KObjectConfig $config)
    {
        if (isset($config->data->metadata) && $config->data->metadata->multidownload) {
            $format = '{actor} multi download {object.subtype} {object.type} title {object}';
        } else {
            $format = '{actor} {action} {object.subtype} {object.type} title {object}';
        }

        $config->append(array(
            'format'        => $format,
            'object_table'  => 'docman_documents',
            'object_column' => 'docman_document_id'
        ));

        parent::_initialize($config);
    }

    public function getPropertyImage()
    {
        if ($this->verb == 'download') {
            $image = 'k-icon-data-transfer-download';
        } else {
            $image = parent::getPropertyImage();
        }

        return $image;
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        $model = $this->getObject('com://admin/docman.model.documents')->id($this->row)->page('all');

        if ($levels = $this->getViewLevels()) {
            $model->access($levels);
        }

        $document = $model->fetch();

        if (!$document->isNew() && $document->itemid && $document->enabled)
        {
            $url = sprintf('option=com_docman&view=document&slug=%s&Itemid=%s', $document->slug, $document->itemid);
            $config->append(array('url' => array('site' => $url)));
        }

        $uuid = null;

        if ($this->_findObject('object'))
        {
            $model->getState()->reset();

            $document = $model->id($this->row)->fetch();

            if (!$document->isNew() && $document->isIdentifiable()) {
                $uuid = $document->uuid;
            }
        }

        $config->append(array(
            'uuid'    => $uuid,
            'url'     => array('admin' => 'option=com_docman&view=document&id=' . $this->row),
            'subtype' => array('objectName' => 'DOCman', 'object' => true)
        ));

        parent::_objectConfig($config);
    }
}