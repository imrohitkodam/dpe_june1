<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ModDocman_searchHtml extends ModKoowaHtml
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'auto_fetch' => false
        ));

        parent::_initialize($config);
    }

    /**
     * Load the controller translations
     *
     * @param KViewContext $context
     * @return void
     */
    protected function _loadTranslations(KViewContext $context)
    {
        parent::_loadTranslations($context);

        $this->getObject('translator')->load('com://site/docman');
    }

    protected function _fetchData(KViewContext $context)
    {
        parent::_fetchData($context);

        $context->data->params = $this->module->params; 
    }
}
