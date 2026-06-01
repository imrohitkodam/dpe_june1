<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Listbox Template Helper
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanTemplateHelperListbox extends ComKoowaTemplateHelperListbox
{
    public function usergroups($config = array())
    {
        $config = new KObjectConfigJson($config);

        $adapter = $this->getObject('lib:database.adapter.mysqli');
        $query = $this->getObject('lib:database.query.select')->table('usergroups')->columns(array('id','title'));

        $groups = $adapter->select($query, KDatabase::FETCH_OBJECT_LIST);

        $options = array();

        foreach($groups as $group) {
            $options[] = $this->option(array('value' => $group->id, 'label' => $group->title));
        }

        $translator = $this->getObject('translator');

        $config->append(array(
            'name'     => 'usergroup',
            'select2'  => true,
            'attribs'  => array('multiple' => true),
            'deselect' => true,
            'options'  => $options,
            'prompt' => $translator->translate('Select user groups')
        ));

        return $this->optionlist($config);
    }

    public function impressions_packages($config = array())
    {
        $config = new KObjectConfig($config);

        $translator = $this->getObject('translator');

        $config->append(array(
            'name'     => 'package',
            'select2'  => true,
            'attribs'  => array('multiple' => true),
            'deselect' => true,
            'prompt' => $translator->translate('Select components'),
            'options' => array()
        ));

        $query = $this->getObject('lib:database.query.select');

        $query->table('logman_impressions')->distinct()->columns(array('package'));

        $packages = $this->getObject('lib:database.adapter.mysqli')->select($query, KDatabase::FETCH_ARRAY_LIST);

        if ($packages)
        {
            $options = array();

            foreach($packages as $package) {
                $options[] = $this->option(array('value' => $package['package'], 'label' => ucfirst($translator->translate($package['package']))));
            }

            $config->options = $options;
        }

        return $this->optionlist($config);
    }

    public function packages($config = array())
    {
        $config = new KObjectConfig($config);

        $config->append(array('select2' => true, 'installed' => false, 'api' => false));

        $options = array();
        $translator = $this->getObject('translator');

        $plugins = $this->getObject('com://admin/logman.model.plugins')->logger(true)->api($config->api)->fetch();

        foreach ($plugins as $plugin)
        {
            $package = $plugin->getPackage();

            if (!isset($options[$package]))
            {
                $component = 'com_' . $package;

                // Check if only installed components should be added to the list.
                if (!$config->installed || $this->_componentInstalled($component))
                {
                    // Load component translations.
                    $lang      = JFactory::getLanguage();
                    $lang->load($component . '.sys', JPATH_BASE, null, false, false)
                    || $lang->load($component . '.sys', JPATH_ADMINISTRATOR . '/components/' . $component, null, false, false)
                    || $lang->load($component . '.sys', JPATH_BASE, $lang->getDefault(), false, false)
                    || $lang->load($component . '.sys', JPATH_ADMINISTRATOR . '/components/' . $component, $lang->getDefault(),
                        false, false);

                    $options[$package] = $this->option(array(
                            'label' => $translator->translate('com_' . $package),
                            'value' => $package
                        )
                    );
                }
            }
        }

        $config->options = array_values($options);

        return parent::optionlist($config);
    }

    /**
     * Provides a LOGman Linker select box.
     *
     * @param  array|KObjectConfig $config An optional configuration array.
     * @return string The autocomplete users select box.
     */
    public function linker($config = array())
    {
        $translator = $this->getObject('translator');

        $config = new KObjectConfigJson($config);

        $config->append(array(
            'model'    => 'resources',
            'value'    => 'url',
            'label'    => 'title',
            'text'     => 'text',
            'sort'     => 'title',
            'attribs'  => array('id' => 'logman_linker'),
            'validate' => false,
            'prompt'   => $translator->translate('Search an item by title'),
            'filter'   => array('view' => 'linker', 'component' => 'logman', 'itemless' => 1),
            'options'  => array('queryVarName' => 'title')
        ));

        return $this->_autocomplete($config);
    }

    /**
     * Checks if a component is installed.
     *
     * @param string $component The component name.
     *
     * @result bool True if installed, false otherwise.
     */
    protected function _componentInstalled($component)
    {
        $query = $this->getObject('lib:database.query.select')
                      ->table('extensions')
                      ->columns('COUNT(*)')
                      ->where('element = :element')
                      ->bind(array('element' => $component));


        return (bool) $this->getObject('lib:database.adapter.mysqli')->select($query, KDatabase::FETCH_FIELD);
    }
}