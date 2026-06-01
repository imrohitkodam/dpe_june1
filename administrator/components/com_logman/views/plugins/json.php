<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Json View
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanViewPluginsJson extends KViewJson
{
    /**
     * JSON layout.
     *
     * @var mixed
     */
    protected $_layout;

    /**
     * Constructor.
     *
     * @param KObjectConfig $config Configuration options.
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_layout = $config->layout;
    }

    protected function _renderData()
    {
        $model = $this->getModel();

        $plugins = $model->fetch();

        if ($layout = $this->_layout)
        {
            $method = '_render' . ucfirst($layout);

            if (method_exists($this, $method)) {
                return $this->$method($plugins);
            }
        }

        $data = array();

        foreach ($plugins as $plugin)
        {
            $data[$plugin->getName()] = array(
                'packages' => $this->_renderPackages($plugin),
                'actions'  => $this->_renderActions($plugin)
            );
        }

        $output = array(
            'version' => $this->_version,
            'links' => array(
                'self' => array(
                    'href' => (string) $this->_getPageUrl(),
                    'type' => $this->mimetype
                )
            ),
            'meta'     => array(),
            'entities' => $data,
            'linked'   => array()
        );

        if ($this->isCollection())
        {
            $total  = $model->count();
            $limit  = (int) $model->getState()->limit;
            $offset = (int) $model->getState()->offset;

            $output['meta'] = array(
                'offset'   => $offset,
                'limit'    => $limit,
                'total'	   => $total
            );

            if ($limit && $total-($limit + $offset) > 0)
            {
                $output['links']['next'] = array(
                    'href' => $this->_getPageUrl(array('offset' => $limit+$offset)),
                    'type' => $this->mimetype
                );
            }

            if ($limit && $offset && $offset >= $limit)
            {
                $output['links']['previous'] = array(
                    'href' => $this->_getPageUrl(array('offset' => max($offset-$limit, 0))),
                    'type' => $this->mimetype
                );
            }
        }

        return $output;
    }

    protected function _renderPackages($plugins)
    {
        $output   = array();
        $plugins  = is_array($plugins) ? $plugins : array($plugins);
        $packages = array();

        foreach ($plugins as $plugin)
        {
            if ($plugin->isLogger()) {
                $packages[] = $plugin->getPackage();
            }
        }

        // Get rid of duplicated.
        $packages = array_unique($packages);

        $translator = $this->getObject('translator');

        // Load translations.
        foreach ($packages as $package)
        {
            // Load component translations.
            $lang      = JFactory::getLanguage();
            $component = 'com_' . $package;
            $lang->load($component . '.sys', JPATH_BASE, null, false, false)
            || $lang->load($component . '.sys', JPATH_ADMINISTRATOR . '/components/' . $component, null, false, false)
            || $lang->load($component . '.sys', JPATH_BASE, $lang->getDefault(), false, false)
            || $lang->load($component . '.sys', JPATH_ADMINISTRATOR . '/components/' . $component, $lang->getDefault(),
                false, false);

            $output[] = array('id' => $package, 'label' => $translator->translate('com_' . $package));
        }

        return $output;
    }

    protected function _renderActions($plugins)
    {
        $resource_actions = array();
        $output           = array();
        $plugins          = is_array($plugins) ? $plugins : array($plugins);

        foreach ($plugins as $plugin)
        {
            if ($plugin->isLogger() && $plugin->getActions()) {
                $resource_actions = array_merge($resource_actions, $plugin->getActions());
            }
        }

        $translator = $this->getObject('translator');

        foreach ($resource_actions as $resource => $actions)
        {
            foreach ($actions as $action) {
                $output[] = array(
                    'id'    => sprintf('%s.%s', $resource, $action),
                    'label' => sprintf('%s - %s', $translator->translate($resource), $translator->translate($action))
                );
            }
        }

        return $output;
    }
}