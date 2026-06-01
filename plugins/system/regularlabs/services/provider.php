<?php
/**
 * @package         Regular Labs Library
 * @version         25.11.14282
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use RegularLabs\Library\Extension as RL_Extension;
use RegularLabs\Library\Input as RL_Input;
use RegularLabs\Plugin\System\RegularLabs\Extension\RegularLabs;

defined('_JEXEC') or die;

if ( ! is_file(JPATH_LIBRARIES . '/regularlabs/regularlabs.xml')
    || ! class_exists('RegularLabs\Library\Plugin\System')
)
{
    return;
}

$config = new JConfig;

// Deal with error reporting when loading pages we don't want to break due to php warnings
if ( ! in_array($config->error_reporting, ['none', '0'])
    && (
        (RL_Input::getCmd('option') == 'com_regularlabsmanager'
            && (RL_Input::getCmd('task') == 'update' || RL_Input::getCmd('view') == 'process')
        )
        || (RL_Input::getInt('rl_qp') == 1 && RL_Input::get('url', '') != '')
    )
)
{
    RL_Extension::orderPluginFirst('regularlabs');

    error_reporting(E_ERROR);
}

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new RegularLabs(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('system', 'regularlabs')
                );
                $plugin->setApplication(JFactory::getApplication());

                return $plugin;
            }
        );
    }
};
