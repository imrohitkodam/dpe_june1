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

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Factory as JFactory;

if ( ! class_exists('RegularLabsInstallerScript'))
{
    class RegularLabsInstallerScript
    {
        public function postflight($install_type, $adapter)
        {
            if ( ! in_array($install_type, ['install', 'update']))
            {
                return true;
            }

            /** @var CallbackController $cache */
            $cache = JFactory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController('callback', ['defaultgroup' => '_system']);
            $cache->clean();

            return true;
        }
    }
}
