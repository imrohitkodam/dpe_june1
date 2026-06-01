<?php
/**
 * @package    Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;

/**
 * Timelog utility class for common methods
 *
 * @since  __DEPLOY_VERSION__
 */
class TimelogUtilities
{
	/**
	 * Hold the class instance.
	 *
	 * @var    Object
	 * @since  __DEPLOY_VERSION__
	 */
	private static $instance = null;

	/**
	 * Returns the global Timelog object
	 *
	 * @return  TimelogUtilities The object
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getInstance()
	{
		if (self::$instance == null)
		{
			self::$instance = new TimelogUtilities;
		}

		return self::$instance;
	}

	/**
	 * Get item id of url
	 *
	 * @param   string  $link  link
	 *
	 * @return  int  Itemid of the given link
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getItemId($link)
	{
		$itemid = 0;
		$app    = Factory::getApplication();
		$menu   = $app->getMenu();

		if ($app->isClient('site'))
		{
			$items = $menu->getItems('link', $link);

			if (isset($items[0]))
			{
				$itemid = $items[0]->id;
			}
		}

		if (!$itemid)
		{
			try
			{
				$db = Factory::getDBO();
				$query = $db->getQuery(true);
				$query->select($db->quoteName('id'));
				$query->from($db->quoteName('#__menu'));
				$query->where($db->quoteName('link') . ' LIKE ' . $db->Quote($link));
				$query->where($db->quoteName('published') . '=' . $db->Quote(1));
				$query->where($db->quoteName('type') . '=' . $db->Quote('component'));
				$db->setQuery($query);
				$itemid = $db->loadResult();
			}
			catch (Exception $e)
			{
				return false;
			}
		}

		if (!$itemid)
		{
			$input  = $app->input;
			$itemid = $input->getInt('Itemid', 0);
		}

		return $itemid;
	}
}
