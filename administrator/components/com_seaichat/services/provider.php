<?php

/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use SolarEclipse\Component\SeAiChat\Administrator\Extension\SeAiChatComponent;

return new class implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $namespace = '\\SolarEclipse\\Component\\SeAiChat';
        $container->registerServiceProvider(new ComponentDispatcherFactory($namespace));
        $container->registerServiceProvider(new MVCFactory($namespace));
        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new SeAiChatComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                return $component;
            }
        );
    }
};
