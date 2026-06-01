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
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

if (!class_exists('TjS3'))
{
	include_once JPATH_ROOT . '/components/com_tjlms/libraries/storage/s3_lib.php';
}

/**
 * Methods for handling the files for specified storage
 *
 * @since  1.0.0
 */
class S3_TjStorage
{
	public $accessKey = null;

	public $secretKey = null;

	public $s3 = null;

	public $bucket = null;

	public $useSSL = false;

	public $name = 'tjs3';

	/**
	 * Method acts as a consturctor
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function _init()
	{
		if ($this->s3 == null)
		{
			$comtjlmsHelper = new comtjlmsHelper;
			$tjlmsparams = $comtjlmsHelper->getcomponetsParams('com_tjlms');

			$this->accessKey = $tjlmsparams->get('storages3accesskey');
			$this->secretKey = $tjlmsparams->get('storages3secretkey');
			$this->bucket = $tjlmsparams->get('storages3bucket');
			$this->lifetime = $tjlmsparams->get('storages3lifetime', '15');
			$this->lifetime = $this->lifetime * 60;

			$this->s3 = new TjS3($this->accessKey, $this->secretKey, $this->useSSL);
		}
	}

	/**
	 * Check if the given storage id exist. We perform local check via db since
	 * checking remotely is time consuming
	 *
	 * @param   VAR  $storageid    The unique file we want to retrive
	 *
	 * @param   VAR  $checkRemote  Optional field
	 *
	 * @return true is file exits
	 *
	 * @since   1.0.0
	 */
	public function exists($storageid, $checkRemote = false)
	{
		// Insert into our s3 database
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$item = Table::getInstance('StorageS3', 'TjlmsTable');

		return $item->load($storageid);
	}

	/**
	 * Put the file into remote storage,
	 *
	 * @param   String  $storageid  The unique file we want to retrive
	 *
	 * @param   String  $file       filename where we want to save the file
	 *
	 * @return true if successful
	 *
	 * @since   1.0.0
	 */
	public function put($storageid, $file)
	{
		$this->_init();

		// Put our file (also with public read access)
		if ($this->s3->putObjectFile($file, $this->bucket, $storageid, TjS3::ACL_PRIVATE))
		{
			// Insert into our s3 database
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$item = Table::getInstance('Storages3', 'TjlmsTable');
			$item->storageid = $storageid;
			$item->resource_path = $storageid;

			if (!$item->store())
			{
				echo $item->getError();
			}

			return true;
		}

		return false;
	}

	/**
	 * Delete the file into remote storage,
	 *
	 * @param   String  $storageid  The unique file we want to retrive
	 *
	 * @return true if successful
	 *
	 * @since   1.0.0
	 */

	public function delete($storageid)
	{
		if (is_array($storageid))
		{
			$storageids = $storageid;
		}
		else
		{
			$storageids[] = $storageid;
		}

		$this->_init();

		foreach ($storageids as $storageid)
		{
			$this->s3->deleteObject($this->bucket, $storageid);
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$item = Table::getInstance('Storages3', 'TjlmsTable');
			$item->load($storageid);

			if ($item->storageid)
			{
				$item->delete();
			}
		}

		return true;
	}

	/**
	 * Retrive the file from remote location and store it locally
	 *
	 * @param   String  $storageid  The unique file we want to retrive
	 *
	 * @param   String  $file       filename where we want to save the file
	 *
	 * @return boolean
	 *
	 * @since   1.0.0
	 */
	public function get($storageid, $file)
	{
		$this->_init();

		// Put our file (also with public read access)
		if ($this->s3->getObject($this->bucket, $storageid, $file))
		{
			return true;
		}

		return false;
	}

	/**
	 * Return the absolute URI path to the resource
	 *
	 * @param   String  $storageId  The unique file we want to retrive
	 *
	 * @return string
	 *
	 * @since   1.0.0
	 */
	public function getURI($storageId)
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$item = Table::getInstance('Storages3', 'TjlmsTable');
		$item->load($storageId);

		if (isset($item->resource_path))
		{
			$uri = Factory::getURI();

			if ($uri->isSSL())
			{
				/*return 'https://' . $this->bucket . '.s3.amazonaws.com/' . $item->resource_path;*/
				return $this->s3->getAuthenticatedURL($this->bucket, $item->resource_path, $this->lifetime, false, true);
			}
			else
			{
				/*return 'http://' . $this->bucket . '.s3.amazonaws.com/' . $item->resource_path;*/
				return $this->s3->getAuthenticatedURL($this->bucket, $item->resource_path, $this->lifetime);
			}
		}

		return $storageId;
	}
}
