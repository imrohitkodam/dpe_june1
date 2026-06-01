<?php
/**
* @package Joomla Module for byebye password
* @copyright Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
* @license GNU General Public License version 2 or later; see LICENSE
* @author Rimjhim
*/

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 * Helper for mod_byebyepassword
 */

class ModByeByePasswordHelper
{
	public static function getReturnURL($params, $type)
	{
		$app	= Factory::getApplication();
		$router = $app->getRouter();
		$url = null;
		$config = Factory::getConfig();
		$sefData = $config->get('sef');
		if ($itemid = $params->get($type))
		{
			$db		= JFactory::getDbo();
			$query	= $db->getQuery(true)
				->select($db->quoteName('link'))
				->from($db->quoteName('#__menu'))
				->where($db->quoteName('published') . '=1')
				->where($db->quoteName('id') . '=' . (int) $itemid);

			$db->setQuery($query);
			if ($link = $db->loadResult())
			{
				

				if ($sefData)
				{
					$url = 'index.php?Itemid='.$itemid;
				}
				else {
					$url = $link.'&Itemid='.$itemid;
				}
			}
		}
		if (!$url)
		{
			// Stay on the same page
			$uri = clone Uri::getInstance();
			$vars = $router->parse($uri);
			unset($vars['lang']);
			if ($sefData)
			{
				if (isset($vars['Itemid']))
				{
					$itemid = $vars['Itemid'];
					$menu = $app->getMenu();
					$item = $menu->getItem($itemid);
					unset($vars['Itemid']);
					if (isset($item) && $vars == $item->query)
					{
						$url = 'index.php?Itemid='.$itemid;
					}
					else {
						$url = 'index.php?'.Uri::buildQuery($vars).'&Itemid='.$itemid;
					}
				}
				else
				{
					$url = 'index.php?'.Uri::buildQuery($vars);
				}
			}
			else
			{
				$url = 'index.php?'.Uri::buildQuery($vars);
			}
			
		}

		return base64_encode(Uri::getInstance($url)->toString());
	}

	public static function getType()
	{
		$user = Factory::getUser();
		return (!$user->get('guest')) ? 'logout' : 'login';
	}
}