<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Migration file for com_tjlms
 *
 * @since  1.3.32
 */
class TjHouseKeepingIssuedCertificateData extends TjModelHouseKeeping
{
	public $title       = "Migrate issued certificate data";

	public $description = "Add tjlms issued certificate information into TJCertificate";

	/**
	 * This function migrate Certificates with limit
	 *
	 * @return  array $result
	 *
	 * @since   1.3.32
	 */
	public function migrate()
	{
		try
		{
			// Reading file here for getting limit start and end
			$migrationfilePath = JPATH_ADMINISTRATOR . '/components/com_tjlms/houseKeeping/migrationLimit.txt';

			JLoader::import('components.com_tjcertificate.includes.tjcertificate', JPATH_ADMINISTRATOR);

			$fileData   = file($migrationfilePath);
			$limitstart = 0;

			if (strpos($fileData[0], 'limitstart') !== false)
			{
				$limitstart = explode(":", $fileData[0])[1];
			}

			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from('#__tjlms_courses');
			$query->where($db->quoteName('certificate_id') . " != 0");
			$db->setQuery($query, (int) $limitstart, 20);
			$courses = $db->loadObjectList();

			// Load course model.
			JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
			$courseModel = BaseDatabaseModel::getInstance('Course', 'TjlmsModel', array('ignore_request' => true));

			if (!empty($courses))
			{
				$lmsparams  = ComponentHelper::getParams('com_tjlms');
				$dateFormat = $lmsparams->get('certificate_date_format', 'j F Y');

				foreach ($courses as $course)
				{
					$issuedCertData = $this->getIssuedCert($course->id);

					if (!empty($issuedCertData))
					{
						foreach ($issuedCertData as $issueCert)
						{
							$issuedDate = HTMLHelper::date($issueCert->grant_date, $dateFormat);
							$genratedIssuedCertId = $courseModel->addCertEntry($course->id, $issueCert->user_id, $issuedDate);

							$tjCert                        = TJCERT::Certificate($genratedIssuedCertId);
							$tjCert->unique_certificate_id = $issueCert->cert_id;
							$tjCert->issued_on             = $issueCert->grant_date;

							if (!empty($issueCert->exp_date))
							{
								$tjCert->setExpiry($issueCert->exp_date);
							}

							$tjCert->save();
						}
					}
				}

				// Update limitstart and limitend value
				$filePath = fopen($migrationfilePath, 'w+');

				if ($filePath != false)
				{
					$newContents = "limitstart:" . ((int) $limitstart + 20);
					fwrite($filePath, $newContents);
					fclose($filePath);
				}
			}

			$result = array();

			if (empty($courses))
			{
				// // Update limitstart and limitend value
				$filePath = fopen($migrationfilePath, 'w+');

				if ($filePath !== false)
				{
					$newContents = "limitstart:" . ((int) 0);
					fwrite($filePath, $newContents);
					fclose($filePath);
				}

				$result['status']   = true;
				$result['message']  = "Migration completed successfully";
			}
			else
			{
				$result['status']   = "inprogress";
				$result['message']  = "Migration is in progress";
			}
		}
		catch (Exception $e)
		{
			$result['err_code'] = '';
			$result['status']   = false;
			$result['message']  = $e->getMessage();
		}

		return $result;
	}

	/**
	 * This function migrate certificate tamplate with TJcertificate template
	 *
	 * @param   Int  $courseId  A course Id
	 *
	 * @return  mixed
	 *
	 * @since   1.3.32
	 */
	public function getIssuedCert($courseId)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__tjlms_certificate');
		$query->where($db->quoteName('course_id') . " = " . (int) $courseId);
		$db->setQuery($query);
		$issuedCertificateList = $db->loadObjectList();

		return $issuedCertificateList;
	}
}
