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
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use RegularLabs\Plugin\System\ArticlesAnywhere\Extension\ArticlesAnywhere;

defined('_JEXEC') or die;

if (version_compare(JVERSION, 4, '<') || version_compare(JVERSION, 7, '>='))
{
    return;
}

// Do not instantiate plugin on install pages
// to prevent installation/update breaking because of potential breaking changes
if (
    in_array(JFactory::getApplication()->input->getCmd('option'), ['com_installer', 'com_regularlabsmanager'])
    && JFactory::getApplication()->input->getCmd('action') != ''
)
{
    return;
}

if ( ! is_file(JPATH_LIBRARIES . '/regularlabs/regularlabs.xml')
    || ! class_exists('RegularLabs\Library\Plugin\System')
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
                $plugin = new ArticlesAnywhere(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('system', 'articlesanywhere')
                );
                $plugin->setApplication(JFactory::getApplication());

                return $plugin;
            }
        );
    }
};
