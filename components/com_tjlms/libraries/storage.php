<?php

/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.filesystem.file');

require_once JPATH_ROOT . '/components/com_tjlms/libraries/storage/s3.php';
require_once JPATH_ROOT . '/components/com_tjlms/libraries/storage/file_storage.php';


/**
 * Methods to get the class object according to storage method specified
 *
 * @since  1.0.0
 */
class Tjstorage
{
	/**
	 *  The function called to get ojject of the storage file
	 *
	 * @param   String  $type  passed to get the objet of local or s3 class.
	 *
	 * @return  Object of specified storage
	 *
	 * @since   1.0.0
	 */
	public static function getStorage($type = 'local')
	{
		// If store file is empty, it should default to 'file'
		if (empty($type))
		{
			$type = 'local';
		}

		$classname = ucfirst($type) . '_TjStorage';
		$obj = new $classname;
		$obj->_init();

		return $obj;
	}
}
