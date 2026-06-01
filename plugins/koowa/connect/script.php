<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 - 2014 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class PlgKoowaConnectInstallerScript
{
    public function postflight($type, $installer)
    {
        $query = sprintf("SELECT extension_id FROM #__extensions WHERE type = '%s' AND element = '%s' AND folder = '%s'",
            'plugin', 'connect', 'koowa'
        );

        $extension_id = JFactory::getDbo()->setQuery($query)->loadResult();

        if ($extension_id) {
            // Enable plugin
            $query = sprintf("UPDATE #__extensions SET enabled = 1 WHERE extension_id = %d", $extension_id);
            JFactory::getDbo()->setQuery($query)->execute();

            $params_query = sprintf("SELECT params FROM #__extensions WHERE extension_id = %d", $extension_id);

            $parameters = JFactory::getDbo()->setQuery($params_query)->loadResult();
            $parameters = @json_decode($parameters, true);

            if (!$parameters) {
                $parameters = [];
            }

            // analytics was removed in 2.3.0
            $parameters['analytics'] = 0;

            // Save parameters if supplied
            $source = $installer->getParent()->getPath('source');

            if (file_exists($source.'/.token.key') && file_exists($source.'/.secret.key')) {
                $parameters = array_merge($parameters, array(
                    'api_key'    => trim(file_get_contents($source.'/.token.key')),
                    'secret_key' => trim(file_get_contents($source.'/.secret.key'))
                ));
            }

            $query = sprintf("UPDATE #__extensions SET params = '%s' WHERE extension_id = %d", json_encode($parameters), $extension_id);

            JFactory::getDbo()->setQuery($query)->execute();

            if (file_exists($source.'/.api.key')) {
                JFile::move($source.'/.api.key', JPATH_ROOT.'/plugins/koowa/connect/.api.key');
            }

            if ($type !== 'update') {
                JFactory::getApplication()->enqueueMessage(sprintf(
                    'Joomlatools Connect features will automatically start working now.'
                ));
            }

        }

        return true;
    }

}