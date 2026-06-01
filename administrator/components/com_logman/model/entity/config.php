<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
use Joomla\CMS\Component\ComponentHelper;

class ComLogmanModelEntityConfig extends KModelEntityAbstract implements KObjectMultiton
{
    /**
     * Joomla asset cache
     *
     * @var JTableAsset
     */
    protected static $_asset;

    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->_load();
    }

    protected function _load()
    {
        $params = ComponentHelper::getParams('com_logman')->toArray();

        if (!$params)
        {
            $params = array(
                'log_routes'        => 1,
                'log_impressions'   => 0,
                'log_login_events'  => 0,
                'log_guest_actions' => 0,
                'maximum_age'       => 90,
                'ignored_groups'    => array()
            );
        }

        $this->setProperties($params);
    }

    public function isNew()
    {
        return false;
    }

    public function isLockable()
    {
        return false;
    }

    /**
     * Copied from JForm
     *
     * @param array $rules
     * @return array
     */
    protected function _filterAccessRules($rules)
    {
        $return = array();
        foreach ((array) $rules as $action => $ids)
        {
            // Build the rules array.
            $return[$action] = array();
            foreach ($ids as $id => $p)
            {
                if ($p !== '') {
                    $return[$action][$id] = ($p == '1' || $p == 'true') ? true : false;
                }
            }
        }

        return $return;
    }

    public function save()
    {
        // System variables shoulnd't be saved
        foreach (array('csrf_token', 'option', 'action', 'format', 'layout', 'task') as $var)
        {
            unset($this->_data[$var]);
            unset($this->_modified[$var]);
        }

        if (!empty($this->rules))
        {
            $rules	= new JAccessRules($this->_filterAccessRules($this->rules));
            $asset	= JTable::getInstance('asset');

            if (!$asset->loadByName('com_logman')) {
                $root	= JTable::getInstance('asset');
                $root->loadByName('root.1');
                $asset->name = 'com_logman';
                $asset->title = 'com_logman';
                $asset->setLocation($root->id, 'last-child');
            }

            $asset->rules = (string) $rules;

            if (!($asset->check() && $asset->store()))
            {
                $translator = $this->getObject('translator');
                $this->getObject('response')->addMessage(
                    $translator->translate('Changes to the ACL rules could not be saved.'), 'warning'
                );
            }

            unset($this->_data['rules']);
        }

        // Get the jos_extensions row entry for FILEman
        $extension = $this->getObject('com:koowa.model.extensions')
                          ->type('component')->element('com_logman')->fetch();

        $extension->parameters = $this->getProperties();
        $extension->save();

        return true;
    }
}
