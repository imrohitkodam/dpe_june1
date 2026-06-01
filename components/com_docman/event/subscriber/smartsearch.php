<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2020 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanEventSubscriberSmartsearch extends KEventSubscriberAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => KEvent::PRIORITY_HIGH
        ));

        parent::_initialize($config);
    }
    
    public function onFinderResult(KEventInterface $event)
    {
        $plugin = JPluginHelper::getPlugin('finder', 'docman');
        $registry = new JRegistry($plugin->params);
        $show_author = (boolean) $registry->get('show_author', false);

        $result = $event['ResultEvent'];

        $reflection = new ReflectionClass($result);
        $property   = $reflection->getProperty('taxonomy');
        $taxonomy   = $property->getValue($result);

        if (isset($taxonomy['Author']) && !$show_author) {
            unset($taxonomy['Author']);
        }

        $property->setValue($result, $taxonomy);
    }
}