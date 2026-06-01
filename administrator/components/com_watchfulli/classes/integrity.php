<?php

/**
 * @version     admin/classes/integrity.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */
defined('_JEXEC') or die('Restricted access');

class WatchfulliIntegrity extends WatchfulliAuditProcess
{
	/**
	 * Compare the hashes of the core files
	 *
	 * @param   int  $start
	 *
	 * @return stdClass
	 */
	public function auditJoomlaCoreIntegrity($start)
	{
		$helper  = new WatchfulliHelper();
		$data    = $this->cache->get(['WatchfulliConnection', 'getHash']);
		$current = $start;
		$file_path = null;

		$result          = new stdClass();
		$result->wrong   = []; // files with wrong checksums
		$result->missing = []; // files missing
		$result->skipped = []; // files skipped
		$result->size    = count($data);
		$result->start   = $start;

		while ($this->haveTime() && $current < count($data))
		{
			$file_path         = $data[$current][0];
			$file_hash         = null;
			$file_officialHash = $data[$current][1];
			$full_path         = JPATH_SITE . '/' . $file_path;

			if (empty($file_path))
			{
				$current++;
				continue;
			}

			$status = $this->checkIntegrityFile($full_path, $file_officialHash, $file_hash, $helper->getMemoryLimitInBytes());

			if ($status <> 'ok')
			{
				$result->$status[] = ['path' => '/' . $file_path, 'hash' => $file_hash];
			}
			$current++;
		}

		$result->lastFileChecked = $file_path;
		$result->end             = $current;

		return $result;
	}

	/**
	 * Compare known non-core files against protected core directories
	 *
	 * @param   int  $start
	 *
	 * @return object
	 */
	public function auditJoomlaProtectedCoreDirectories($start)
	{
		$structure    = $this->cache->get(['WatchfulliRecursiveListing', 'getStructure'], [JPATH_SITE]);
		$hashes       = $this->cache->get(['WatchfulliConnection', 'getHash']);
		$nonCoreFiles = $this->cache->get(['WatchfulliRecursiveListing', 'getNonCoreFiles'], [$structure, $hashes]);
		$file_path	  = null;

		$data    = $this->cache->get(['WatchfulliConnection', 'getProtectedCoreDirectories']);
		$current = $start;

		$result        = new stdClass();
		$result->extra = []; // files that should not be there
		$result->size  = count($nonCoreFiles);
		$result->start = $start;

		while ($this->haveTime() && $current < count($nonCoreFiles))
		{
			$file_path = $nonCoreFiles[$current];
			// at most (J 3.x) this list should be no longer than 175
			foreach ($data as $dir)
			{
				// if the directory is the start of the path, it's a match
				if (0 === strpos($file_path, $dir))
				{
					$result->extra[] = $file_path;
					break;
				}
			}
		}

		$result->lastFileChecked = $file_path;
		$result->end             = $current;

		return $result;
	}

	/**
	 * Compare the md5 hash of a file with a reference
	 *
	 * @param   string  $full_path
	 * @param   string  $file_officialHash
	 * @param   string  $file_hash
	 * @param   int     $memory_limit
	 *
	 * @return string status
	 */
	private function checkIntegrityFile($full_path, $file_officialHash, &$file_hash, $memory_limit)
	{
		if (!file_exists($full_path))
		{
			return 'missing';
		}

		$memory_usage = memory_get_usage();
		$file_size    = filesize($full_path);

		// let's hope the file can be read
		$memoryNeeded = $memory_usage + $file_size;
		if ($memoryNeeded > $memory_limit)
		{
			return 'skipped';
		}

		// does this file have a wrong checksum ?
		$file_hash = md5_file($full_path);
		if ($file_hash != $file_officialHash)
		{
			return 'wrong';
		}

		return 'ok';
	}
}
