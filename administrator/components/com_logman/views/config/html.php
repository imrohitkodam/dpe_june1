<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComLogmanViewConfigHtml extends ComKoowaViewHtml
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('decorator' => 'koowa'));

        parent::_initialize($config);
    }

    protected function _fetchData(KViewContext $context)
    {
        $plugins = $this->getObject('com://admin/logman.model.plugins')->logger(true)->fetch();

        foreach ($plugins as $plugin)
        {
            $identifier = sprintf('plg_logman_%s', $plugin->getName());

            ComLogmanActivityTranslator::loadSysIni($identifier);

            $plugin->identifier = $identifier;
        }

        $context->data->plugins = $plugins;
        $context->data->token = $this->getObject('user')->getSession()->getToken();

        $context->data->sef_on = JFactory::getConfig()->get('sef');

        try {
            $context->data->license = $this->getObject('license');

            if ($context->data->license->load()) {
                $context->data->has_connect = $context->data->license->hasFeature('connect');
                $context->data->license_error = null;
                $context->data->license_claims = $context->data->license->getToken() ? json_encode($context->data->license->getToken()->getClaims(), JSON_PRETTY_PRINT) : 'error';
            } else {
                $context->data->has_connect = false;
                $context->data->license_error = $context->data->license->getError();
                $context->data->license_claims = 'error';
            }

        } catch (Exception $e) {
            $context->data->license_error = $e->getMessage();
            $context->data->license_claims = 'error';
        }
        
        parent::_fetchData($context);
    }

}
