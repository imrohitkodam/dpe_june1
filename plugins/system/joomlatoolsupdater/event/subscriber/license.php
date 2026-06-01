<?php
/**
 * @package     JoomlatoolsUpdater
 * @copyright   Copyright (C) 2021 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class PlgSystemJoomlatoolsupdaterEventSubscriberLicense extends KEventSubscriberAbstract
{
    protected $_error_added = false;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => KEvent::PRIORITY_HIGH
        ));

        parent::_initialize($config);
    }

    public function onBeforeDispatcherDispatch(KEventInterface $event)
    {
        try {
            if (substr($event->getTarget()->getIdentifier()->getPackage(), -3) === 'man') {
                /** @var PlgSystemJoomlatoolsupdaterLicense $license */
                $license = $this->getObject('license');
                $license->load();
            }
        } catch (Exception $e) {
            if (JDEBUG) throw $e;
        }
    }
}