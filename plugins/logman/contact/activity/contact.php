<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Contact Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanContactActivityContact extends ComLogmanModelEntityActivity
{
    protected function _initialize(KObjectConfig $config)
    {
        $objects = array();

        if ($config->data->action == 'contact')
        {
            $format = '{actor} {action} {object} {name}';
            $objects[] = 'name';
        }
        else $format = '{actor} {action} {object.type} name {object}';

        $config->append(array(
            'objects'       => $objects,
            'format'        => $format,
            'object_table'  => 'contact_details',
            'object_column' => 'id'
        ));

        parent::_initialize($config);
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        $query = $this->getObject('lib:database.query.select')
                      ->table(array('contact_details'))
                      ->columns('*')
                      ->where('id = :id')
                      ->bind(array('id' => $this->row));

        if ($levels = $this->getViewLevels()) {
            $query->where('access IN :levels')->bind(array('levels' => $this->getViewLevels()));
        }

        $contact = $this->getTable()->getAdapter()->select($query, KDatabase::FETCH_OBJECT);

        if ($contact && $contact->published)
        {
            $table = JTable::getInstance('category');

            $table->load($contact->catid);

            $parents = array();

            foreach ($table->getPath() as $parent) {
                $parents[]  = $parent->id == 1 ? '0' : $parent->id;
            }

            $template = sprintf('option=com_contact&view=contact&id=%s-%s&catid=%s&Itemid=%%s', $this->row, $contact->alias, $contact->catid);

            $config->append(array(
                'pages' => array(
                    'template'   => $template,
                    'conditions' => array()
                )
            ));

            if ($contact->featured) {
                $config->pages->conditions->append(array(array('view' => 'featured')));
            }

            $config->pages->conditions->append(array(
                array(
                    'view' => 'contact',
                    'id'   => $this->row
                ),
                array(
                    'view' => 'category',
                    'id'   => $parents
                ),
                array(
                    'view' => 'categories',
                    'id'   => $parents
                )
            ));
        }

        parent::_objectConfig($config);
    }

    public function getPropertyImage()
    {
        if ($this->verb == 'contact') {
            $image = 'k-icon-envelope-closed';
        } else {
            $image = parent::getPropertyImage();
        }

        return $image;
    }

    public function getActivityName()
    {
        $metadata = $this->getMetadata();

        $url = $this->getObject('lib:http.url', array('url' => 'mailto:' . $metadata->sender->email));

        $config   = array(
            'objectName' => $metadata->sender->name,
            'url'        => array('admin' => $url, 'site' => $url)
        );

        return $this->_getObject($config);
    }
}
