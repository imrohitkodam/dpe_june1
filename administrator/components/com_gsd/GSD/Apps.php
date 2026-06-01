<?php

/**
 * @package         Google Structured Data
 * @version         6.0.1 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2021 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

namespace GSD;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');

class Apps
{
	public static function getApp($name, $data = null)
	{
		if (!$plugin = PluginHelper::getPlugin('gsd', $name))
		{
			throw new \RuntimeException(Text::sprintf('GSD_PLUGIN_NOT_FOUND', $name));
		}

		$app = Factory::getApplication()->bootPlugin($plugin->name, $plugin->type);

		return $app;
	}
}