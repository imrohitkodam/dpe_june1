<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanViewSearchJson extends ComDocmanViewJson
{
    /**
     * Override to get new document model instead from context. Search view doesn't depends on menu item (page)
     *
     * @return  KModelInterface
     */
    public function getModel()
    {
        $is_admin = JFactory::getApplication()->isClient('administrator');

        if ($is_admin) {
            $state = $this->_url->query;
        } else {
            $state = $this->_model->getState()->getValues();
        }

        // Search globally
        if (isset($state['search']) && !empty($state['search'])) {
            unset($state['category']);
        }

        $identifier = 'com://admin/docman.model.documents';
        $model = $this->getObject($identifier)->setState($state);

        return $model;
    }
}
