<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Filtered Activity Controller
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanControllerFiltered extends ComKoowaControllerModel
{
    public function __construct(KObjectConfig $config)
    {
        $this->__setAliases($config);

        parent::__construct($config);

        $this->getObject('translator')->load('com://admin/logman');
    }

    private function __setAliases(KObjectConfig $config)
    {
        $package = $config->object_identifier->getPackage();

        $view = KStringInflector::pluralize($config->object_identifier->getName());

        $manager = $config->object_manager;

        $domain = $config->object_identifier->getDomain();

        // Layouts

        $alias = sprintf('com://%s/%s.%s.default', $domain, $package, $view);

        if (!$manager->getObject('template.locator.factory')->locate($alias . '.html')) {
            $manager->registerAlias('com://site/logman.filtered.default', $alias); // Create alias as override not found
        }

        $identifier = $config->object_identifier->toArray();

        // Permissions
        $identifier['path'] = array('controller', 'permission');

        if (!$manager->getClass($identifier, false)) {
            $manager->registerAlias('com://admin/logman.controller.permission.activity', $identifier);
        }

        // Toolbar
        $identifier['path'] = array('controller', 'toolbar');

        if (!$manager->getClass($identifier, false)) {
            $manager->registerAlias('com://site/logman.controller.toolbar.filtered', $identifier);
        }
    }

    public function getRequest()
    {
        $request = parent::getRequest();

        $request->getQuery()->package = $this->getIdentifier()->getPackage();

        return $request;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'formats'   => array('csv', 'json'),
            'model'     => 'com://admin/logman.model.activities',
            'behaviors' => array(
                'com:activities.controller.behavior.purgeable',
                'com://admin/logman.controller.behavior.exportable.csv'
            )
        ));

        parent::_initialize($config);
    }

    public function getView()
    {
        if (!$this->_view instanceof KViewInterface)
        {
            $manager = $this->getObject('manager');

            $format = $this->getRequest()->getQuery()->format ?: 'html';

            $domain = $this->getIdentifier()->getDomain();

            $identifier = sprintf('com://%s/%s.view.activities.%s', $domain, $this->getIdentifier()->getPackage(), $format);

            if (!$manager->getClass($identifier, false)) {
                $manager->registerAlias(sprintf('com://site/logman.view.activities.%s', $format), $identifier);
            }
        }

        return parent::getView();
    }

    protected function _getPluginId()
    {
        $db = JFactory::getDbo();
        $query = /** @lang text */"SELECT extension_id FROM #__extensions
            WHERE type = 'plugin' AND element = 'logman' AND folder = 'koowa'
            LIMIT 1
        ";

        $db->setQuery($query);

        return $db->loadResult();
    }

    public function pluginEnabled()
    {
        $query = 'SELECT enabled FROM #__extensions WHERE extension_id = %d';

        $db = JFactory::getDBO();
        $db->setQuery(sprintf($query, $this->_getPluginId()));

        return !!$db->loadResult();
    }
}

