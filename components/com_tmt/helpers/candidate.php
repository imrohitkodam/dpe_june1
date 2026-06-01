<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;

/**
 * Tmt candidate helper class
 *
 * @since       1.0.0
 *
 * @deprecated  1.4.0  This class will be removed and no replacement will be provided
 *
 */

class TmtCandidateHelper
{
	/**
	 * Method to get job name
	 *
	 * @param   int  $id  job id
	 *
	 * @return  string
	 *
	 * @since    1.0
	 */
	public function getJobName($id)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('title');
		$query->from('#__ja_jobs');
		$query->where('id = ' . $id);
		$db->setQuery($query);
		$jobTitle = $db->loadResult();

		return $jobTitle;
	}

	/**
	 * Method to get candidate qualification
	 *
	 * @param   int  $id  resume id
	 *
	 * @return  string
	 *
	 * @since    1.0
	 */
	public function getCandidateQualification($id)
	{
		$db = Factory::getDBO();

		if ($id)
		{
			$query = $db->getQuery(true);
			$query->select('education');
			$query->from('#__ja_resumes');
			$query->where('user_id = ' . $id);
			$db->setQuery($query);
			$CandidateQualification = $db->loadResult();

			return $CandidateQualification;
		}
		else
		{
			return 0;
		}
	}
}
