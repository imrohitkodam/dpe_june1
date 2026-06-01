<?php
/**
 * @package         Articles Anywhere
 * @version         17.2.10PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use RegularLabs\Plugin\EditorButton\ArticlesAnywhere\Extension\ArticlesAnywhere;
use RegularLabs\Plugin\EditorButton\ArticlesAnywhere\Extension\ArticlesAnywhereJ4;

defined('_JEXEC') or die;

if (version_compare(JVERSION, 4, '<') || version_compare(JVERSION, 7, '>='))
{
    return;
}

if ( ! is_file(JPATH_LIBRARIES . '/regularlabs/regularlabs.xml')
    || ! class_exists('RegularLabs\Library\Plugin\EditorButton')
)
{
    return;
}

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $class      = JVERSION < 5 ? ArticlesAnywhereJ4::class : ArticlesAnywhere::class;
                $dispatcher = $container->get(DispatcherInterface::class);

                $plugin = new $class(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('editors-xtd', 'articlesanywhere')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
