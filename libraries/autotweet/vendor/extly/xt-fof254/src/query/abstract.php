<?php
/**
 * @package     XT Transitional Package from FrameworkOnFramework
 * @subpackage  query
 *  @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved. / Based on FrameworkOnFramework of Akeeba
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('XTF0F_INCLUDED') or die;

/**
 * FrameworkOnFramework query base class; for compatibility purposes
 *
 * @package     XT Transitional Package from FrameworkOnFramework
 * @since       2.1
 * @deprecated  2.1
 */
abstract class XTF0FQueryAbstract
{
	/**
	 * Returns a new database query class
	 *
	 * @param   XTF0FDatabaseDriver  $db  The DB driver which will provide us with a query object
	 *
	 * @return XTF0FQueryAbstract
	 */
	public static function &getNew($db = null)
	{
		XTF0FPlatform::getInstance()->logDeprecated('XTF0FQueryAbstract is deprecated. Use XTF0FDatabaseQuery instead.');

		if (is_null($db))
		{
			$ret = XTF0FPlatform::getInstance()->getDbo()->getQuery(true);
		}
		else
		{
			$ret = $db->getQuery(true);
		}

		return $ret;
	}
}
