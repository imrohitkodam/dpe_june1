<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanTemplateHelperAccess extends ComKoowaTemplateHelperAccess
{
    public function access_box($config = array())
    {
        $config = new KObjectConfigJson($config);

        $entity   = $config->entity;

        $model    = $this->getObject('com://admin/docman.model.viewlevels');
        $entities = $model->fetch();

        $viewlevels = $entities->toArray();

        $default_access = $entities->find((int) (JFactory::getConfig()->get('access') || 1)) ?: $entities->create();
        $type           = KStringInflector::singularize($entity->getIdentifier()->name);

        return $this->getTemplate()->loadFile('com://admin/docman.document.access.html', 'php')
            ->render(array(
                'type'       => $type,
                'entity'     => $entity,
                'viewlevels' => $viewlevels,
                'default_access' => $default_access
            ));
    }
}
