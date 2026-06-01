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
use Joomla\CMS\Filesystem\File;
use Joomla\String\StringHelper;

if (!class_exists('S3'))
{
	include_once JPATH_ROOT . '/components/com_tjlms/libraries/storage/s3_lib.php';
}

/**
 * Methods for handling the files for specified storage
 *
 * @since  1.0.0
 */
class Local_TjStorage
{
	/**
	 * Method acts as a consturctor
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */

	public function _init()
	{
	}

	/**
	 * Check if the given storage id exist. We perform local check via db since
	 * checking remotely is time consuming
	 *
	 * @param   VAR  $storageid    the file path
	 *
	 * @param   VAR  $checkRemote  Optional field
	 *
	 * @return true is file exits
	 *
	 * @since   1.0.0
	 *
	 * */
	public function exists($storageid, $checkRemote = false)
	{
		return File::exists(JPATH_ROOT . '/' . $storageid);
	}

	/**
	 * Put the file into remote storage,
	 *
	 * @param   VAR  $storageid  The unique file we want to retrive
	 *
	 * @param   VAR  $file       Filename where we want to save the file
	 *
	 * @return true if successful
	 *
	 * @since   1.0.0
	 */
	public function put($storageid, $file)
	{
		$storageid = JPATH_ROOT . '/' . $storageid;
		File::copy($file, $storageid);

		return true;
	}

	/**
	 * Retrive the file from remote location and store it locally
	 *
	 * @param   String  $storageid  The unique file we want to retrive
	 *
	 * @param   String  $file 	     Filename where we want to save the file
	 *
	 * @return true if successful
	 *
	 * @since   1.0.0
	 */
	public function get($storageid, $file)
	{
		$storageid = JPATH_ROOT . '/' . $storageid;
		File::copy($storageid, $file);

		return true;
	}

	/**
	 * Return the absolute URI path to the resource
	 *
	 * @param   String  $storageId  The unique file we want to retrive
	 *
	 * @return  URL of the storageid
	 *
	 * @since   1.0.0
	 */
	public function getURI($storageId)
	{
		$root = StringHelper::rtrim(JURI::root(), '/');
		$storageId = StringHelper::ltrim($storageId, '/');

		return $root . '/' . $storageId;
	}

	/**
	 * Storage file delete
	 *
	 * @param   string  $storageid  The unique file we want to retrive
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function delete($storageid)
	{
		$storageid = JPATH_ROOT . '/' . $storageid;

		if (File::exists($storageid))
		{
			return File::delete($storageid);
		}
		else
		{
			return false;
		}
	}
}
