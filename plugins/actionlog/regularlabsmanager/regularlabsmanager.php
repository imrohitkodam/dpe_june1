<?php
/**
 * @package         Regular Labs Extension Manager
 * @version         9.2.5
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\ActionLogPlugin as RL_ActionLogPlugin;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Extension as RL_Extension;
use RegularLabs\Library\Input as RL_Input;

defined('_JEXEC') or die;

if (version_compare(JVERSION, 4, '<') || version_compare(JVERSION, 7, '>='))
{
    return;
}

if ( ! is_file(JPATH_LIBRARIES . '/regularlabs/regularlabs.xml')
    || ! class_exists('RegularLabs\Library\ActionLogPlugin')
)
{
    return;
}

if (true)
{
    class PlgActionlogRegularLabsManager extends RL_ActionLogPlugin
    {
        public $name  = 'REGULARLABSEXTENSIONMANAGER';
        public $alias = 'regularlabsmanager';

        public function onExtensionAfterInstall($installer, $eid)
        {
            // Prevent duplicate logs
            if (in_array('install_' . $eid, self::$ids, true))
            {
                return;
            }

            $context = RL_Input::getCmd('option');

            if ( ! str_contains($context, $this->option))
            {
                return;
            }

            if ( ! RL_Array::find(['*', 'install'], $this->events))
            {
                return;
            }

            $extension = RL_Extension::getById($eid);

            if (empty($extension->manifest_cache))
            {
                return;
            }

            $manifest = json_decode($extension->manifest_cache);

            if (empty($manifest->name) || empty($manifest->type))
            {
                return;
            }

            self::$ids[] = 'install_' . $eid;

            $message = [
                'action'         => 'install',
                'type'           => $this->lang_prefix_install . '_TYPE_' . strtoupper($manifest->type),
                'id'             => $eid,
                'extension_name' => JText::_($manifest->name),
            ];

            $languageKey = $this->lang_prefix_install . '_' . strtoupper($manifest->type) . '_INSTALLED';

            if ( ! JFactory::getApplication()->getLanguage()->hasKey($languageKey))
            {
                $languageKey = $this->lang_prefix_install . '_EXTENSION_INSTALLED';
            }

            $this->addLog([$message], $languageKey, 'com_regularlabsmanager');
        }
    }
}
