<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

/**
 * AutotweetModelPlugins.
 *
 * Factory to create AutoTweet plugin classes.
 * This is the central point to get and handle plugin classes. Also all needed files are included here.
 *
 * @since       1.0
 */
class AutotweetModelPlugins extends XTF0FModel
{
    /**
     * buildQuery / Get all plugins.
     *
     * @param bool $overrideLimits Param
     *
     * @return XTF0FQuery
     */
    public function buildQuery($overrideLimits = false)
    {
        $db = $this->getDbo();

        $element_id = $this->getState('element_id', null, 'string');
        $extension_plugins_only = $this->getState('extension_plugins_only', false, 'int');
        $published_only = $this->getState('published_only', false, 'int');

        // Plugins QUERY
        $query = XTF0FQueryAbstract::getNew($db)->select($db->quoteName('extension_id').' as '.$db->quoteName('id'))
            ->select($db->quoteName('name'))
            ->select($db->quoteName('element'))
            ->select($db->quoteName('folder'))
            ->from($db->quoteName('#__extensions'))
            ->where(
                '('.$db->quoteName('element').' like '.$db->Quote('%autotweet%').' OR '.
                $db->quoteName('element').' like '.$db->Quote('%joocial%').')'
            )
            ->where($db->quoteName('type').' = '.$db->Quote('plugin'));

        if ($extension_plugins_only) {
            $excluded = [
                $db->Quote('autotweet'),
                $db->Quote('autotweetautomator'),
                $db->Quote('autotweetopengraph'),
                $db->Quote('autotweetsocialprofile'),
                $db->Quote('autotweettwittercard'),
                $db->Quote('joocialeditor'),
                $db->Quote('joocialgap'),
                $db->Quote('joocialwebpush'),
                $db->Quote('joocialwebpushmanifest'),
            ];

            $query->where($db->quoteName('element').' NOT IN ('.implode(',', $excluded).')');
        }

        if ($published_only) {
            $query->where($db->quoteName('enabled').' = '.$db->Quote('1'));
        }

        if ($element_id) {
            $query->where($db->quoteName('element').' = '.$db->Quote($element_id));
        }

        $query->order($db->quoteName('element').' ASC');

        return $query;
    }

    /**
     * createPlugin
     * typeinfo:	only needed when 2 different types of messages are returned (see Kunena plugin).
     *
     * @param string $type Param
     *
     * @return object
     */
    public function createPlugin($name)
    {
        $this->set('element_id', $name);
        $items = $this->getList();

        if (empty($items)) {
            return null;
        }

        $plugin = $items[0];

        return \XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\DispatcherHelper::getPlugin(
            $plugin->folder,
            $name
        );
    }

    /**
     * Additional service functions.
     *
     * @param array &$plugins Param
     */
    public function loadLanguages(&$plugins)
    {
        $jlang = \Joomla\CMS\Factory::getLanguage();

        foreach ($plugins as $plugin) {
            $jlang->load($plugin['name']);
        }
    }

    /**
     * getSimpleName.
     *
     * @param string $element Param
     *
     * @return object
     */
    public static function getSimpleName($element)
    {
        $label = 'COM_AUTOTWEET_PLUGIN_'.strtoupper($element);
        $value = JText::_($label);

        if ($label === $value) {
            return ucfirst(str_replace('autotweet', '', $element));
        }

        return $value;
    }
}
