<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2020 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanEventSubscriberRedirect extends KEventSubscriberAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => KEvent::PRIORITY_HIGH
        ));

        parent::_initialize($config);
    }

    /**
     * Re-directs DOCman com_config requests to DOCman settings page
     * 
     * @param KEventInterface $event
     * @return void
     */
    public function onAfterApplicationInitialise(KEventInterface $event)
    {
        $request = $this->getObject('request');

        $query = $request->getQuery();

        if ($query->option == 'com_config' && $query->component == 'com_docman')
        {
            $url = $request->getUrl()->setQuery('option=com_docman&view=config');

            $this->getObject('dispatcher')->getResponse()->setRedirect($url)->send();
        }
    }
}