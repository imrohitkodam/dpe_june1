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
     * Override to get new document model instead from the context to reset the current model states.
     *
     * @return  KModelInterface
     */
    public function getModel()
    {
        $query = $this->_url->query;

        // Search globally
        if (isset($query['search']) && !empty($query['search'])) {
            unset($query['category']);
        }

        $identifier = 'com://admin/docman.model.documents';
        $model = $this->getObject($identifier);
        $model->setState($query);

        return $model;
    }
}
