<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Installer LOGman Plugin.
 *
 * Provides handlers for dealing with com_installer events.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanInstaller extends ComLogmanPluginLogger
{
    /**
     * Plugin On/Off switch.
     *
     * @var bool
     */
    protected $_enabled = true;

    /**
     * @var array An associative array containing extension data to be shared among events.
     */
    protected $_extensions = array();

    /**
     * Overridden for bypassing event handling if plugin is disabled.
     */
    public function update(&$args)
    {
        $return = null;

        if ($this->_enabled) {
            $return = parent::update($args);
        }

        return $return;
    }

    /**
     * Before extension install event handler.
     *
     * @param $method
     * @param $type
     * @param $manifest
     * @param $extension
     */
    public function onExtensionBeforeInstall($method, $type, $manifest, $extension)
    {
        $extension_name = null;
        $db = $this->getObject('lib:database.adapter.mysqli');

        if ($manifest)
        {
            $extension_name = strtolower((string) $manifest->name);
            $query = $this->getObject('lib:database.query.select')->columns('COUNT(*)')->table('extensions')
                          ->where('type = :type')->where('name = :name')->bind(array(
                'type' => $type,
                'name' => $extension_name));

            $this->_extensions[$extension_name] = ($db->select($query, KDatabase::FETCH_FIELD) != 0) ? 'update' : 'install';
        }
        elseif ($extension)
        {
            $extension_id = (int) $extension;
            $query = $this->getObject('lib:database.query.select')->columns('*')->table('extensions')
                          ->where('type = :type')->where('name = :name')->bind(array('type'         => $type,
                                                                                     'extension_id' => $extension_id));

            if ($row = $db->select($query, KDatabase::FETCH_OBJECT))
            {
                $extension_name                     = strtolower($row->name);
                $this->_extensions[$extension_name] = 'install';
            }
            else $this->_enabled = false;
        }
        else $this->_enabled = false;

        // Disable plugin while installing our installer.
        if ($extension_name == 'com_joomlatools_installer') {
            $this->_enabled = false;
        }

        // LOGman is being installed/updated. Changes in the component could make the plugin
        // log actions to fail. Because of this, we are better disabling the plugin.
        if ($this->_enabled && $extension_name && ($extension_name == 'com_logman')) {
            $this->_enabled = false;
        }
    }

    /**
     * After extension install event handler.
     *
     * @param $installer
     * @param $eid
     */
    public function onExtensionAfterInstall($installer, $eid)
    {
        $extension_name = strtolower((string) $installer->manifest->name);

        if ($eid && isset($this->_extensions[$extension_name]))
        {
            $action = $this->_extensions[$extension_name];

            $this->_handleInstallerEvent(array(
                'action'    => $action,
                'installer' => $installer,
                'eid'       => $eid));
        }
    }

    /**
     * After extension update event handler.
     *
     * @param $installer
     * @param $eid
     */
    public function onExtensionAfterUpdate($installer, $eid)
    {
        if ($eid) {
            $this->_handleInstallerEvent(array('action' => 'update', 'installer' => $installer, 'eid' => $eid));
        }
    }

    /**
     * Before extension uninstall event handler.
     *
     * @param $eid
     */
    public function onExtensionBeforeUninstall($eid)
    {
        $extension = JTable::getInstance('extension');
        $extension->load($eid);
        $this->_extensions[$eid] = $extension;

        // LOGman is being un-installed, "disable" the plugin.
        if (in_array($extension->element, array('com_logman', 'com_joomlatools_installer'))) {
            $this->_enabled = false;
        }
    }

    /**
     * After extension install event handler.
     *
     * @param $installer
     * @param $eid
     * @param $result
     */
    public function onExtensionAfterUninstall($installer, $eid, $result)
    {
        if ($result && isset($this->_extensions[$eid]))
        {
            $this->_handleInstallerEvent(array(
                'extension' => $this->_extensions[$eid],
                'action'    => 'uninstall',
                'installer' => $installer,
                'eid'       => $eid));
        }
    }

    /**
     * Installer event handler.
     *
     * @param array $config
     */
    protected function _handleInstallerEvent($config = array())
    {
        $config = new KObjectConfig($config);

        if (!$extension = $config->extension)
        {
            $extension = JTable::getInstance('extension');
            $extension->load($config->eid);
        }

        $installer = $config->installer;

        switch ($action = $config->action)
        {
            case 'install':
                $result = 'installed';
                break;
            case 'uninstall':
                $result = 'uninstalled';
                break;
            case 'update':
                $result = 'upgraded';
                break;
        }

        $metadata = array();

        if (!isset($installer->manifest))
        {
            // Try grabbing the manifest from the extension object.
            if ($extension->manifest_cache) {
                $manifest = json_decode($extension->manifest_cache);
            } else {
                $manifest = new stdClass();
            }
        }
        else $manifest = $installer->manifest;

        // Store extension version (if set) in meta data.
        if (isset($manifest->version)) {
            $metadata['version'] = (string) $manifest->version;
        }

        $metadata['client']  = $extension->client_id ? 'admin' : 'site';
        $metadata['element'] = $extension->element;
        $metadata['name']    = $extension->name;
        $metadata['folder']  = $extension->folder;

        // Get the object name.
        switch ($extension->type)
        {
            case 'plugin':
                $name = 'plg_' . $extension->folder . '_' . $extension->element;
                break;
            case 'module':
            case 'component':
                $name = $extension->element;
                break;
            default:
                $name = $extension->name;
                break;
        }

        $this->logActivity(array(
            'verb'   => $action,
            'result' => $result,
            'object' => array(
                'package'  => 'installer',
                'type'     => $extension->type,
                'id'       => $config->eid,
                'name'     => $name,
                'metadata' => empty($metadata) ? null : $metadata
            )
        ));
    }
}