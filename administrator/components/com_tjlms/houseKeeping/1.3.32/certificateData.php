<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Migration file for com_tjlms
 *
 * @since  1.3.32
 */
class TjHouseKeepingCertificateData extends TjModelHouseKeeping
{
	public $title       = "Migrate certificate data";

	public $description = "Add tjlms certificate information into TJCertificate";

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

			JLoader::import('components.com_tjlms.models.courses', JPATH_ADMINISTRATOR);

			$fileData   = file($migrationfilePath);
			$limitstart = 0;

			if (strpos($fileData[0], 'limitstart') !== false)
			{
				$limitstart = explode(":", $fileData[0])[1];
			}

			$db  = Factory::getDbo();
			$sql = $db->getQuery(true)->select('*')
				->from($db->qn('#__tjlms_certificate_template'));
			$db->setQuery($sql, (int) $limitstart, 20);
			$certificateTemplates = $db->loadObjectList();

			if (!empty($certificateTemplates))
			{
				foreach ($certificateTemplates as $template)
				{
					// Migrate all tjlms certificate template in TJCertificate template
					$lastCertificateId = $this->migrateTemplate($template);

					// Set state for certificate_id in courses model for get courses.
					$tjlmsCoursesModel = BaseDatabaseModel::getInstance('Courses', 'TjlmsModel', array('ignore_request' => true));
					$tjlmsCoursesModel->setState("certificate_id", $template->id);
					$tjlmsCoursesData = $tjlmsCoursesModel->getItems();

					if (!empty($tjlmsCoursesData))
					{
						foreach ($tjlmsCoursesData as $course)
						{
							// Update new template id of TJcertificate template in course
							$result = $this->updateTemplateId($course->id, $lastCertificateId);
						}
					}
				}

				// Update limitstart and limitend value
				$migrateFilePath = fopen($migrationfilePath, 'w+');

				if ($migrateFilePath !== false)
				{
					$newContents = "limitstart:" . ((int) $limitstart + 20);
					fwrite($migrateFilePath, $newContents);
					fclose($migrateFilePath);
				}
			}

			$result = array();

			if (empty($certificateTemplates))
			{
				// Update limitstart and limitend value
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
	 * @param   object  $template  A template object
	 *
	 * @return  INT $lastCertificateId
	 *
	 * @since   1.3.32
	 */
	public function migrateTemplate($template)
	{
		$tjlmsTempBody  = $template->body;
		$tjlmsTags      = ["[STUDENTNAME]", "[STUDENTUSERNAME]", "[COURSE]", "[GRANTED_DATE]", "[EXPIRY_DATE]", "[TOTAL_TIME]", "[CERT_ID]"];
		$tjCertTags     = ["{enroll.studentname}", "{enroll.studentusername}", "{course.title}", "{certificate.granted_date}",
			"{certificate.expiry_date}", "{course.total_time}", "{certificate.cert_id}"];
		$tjCertTempBody = str_replace($tjlmsTags, $tjCertTags, $tjlmsTempBody);

		if (strpos($tjCertTempBody, '[esfield') !== false)
		{
			$pregMatch = '/esfield:([^-\]]*)/';
			$abc  = explode(" ", $tjCertTempBody);
			$list = array();

			foreach ($abc as $key => $value)
			{
				$found = preg_match($pregMatch, $value, $matches);

				if ($found)
				{
					$list[] = $matches[1];
				}
			}

			if (!empty($list))
			{
				foreach ($list as $key => $value)
				{
					$tjCertTempBody = str_replace('[esfield:' . $value . ']', '{esfield.' . $value . '}', $tjCertTempBody);
				}
			}
		}

		if (strpos($tjCertTempBody, '[jsfield') !== false)
		{
			$pregMatch = '/jsfield:([^-\]]*)/';

			$abc  = explode(" ", $tjCertTempBody);
			$list = array();

			foreach ($abc as $key => $value)
			{
				$found = preg_match($pregMatch, $value, $matches);

				if ($found)
				{
					$list[] = $matches[1];
				}
			}

			if (!empty($list))
			{
				foreach ($list as $key => $value)
				{
					$tjCertTempBody = str_replace('[jsfield:' . $value . ']', '{jsfield.' . $value . '}', $tjCertTempBody);
				}
			}
		}

		$tjcertObj                   = new stdclass;
		$tjcertObj->title            = $template->title;
		$tjcertObj->body             = $tjCertTempBody;
		$tjcertObj->template_css     = $template->template_css;
		$tjcertObj->client           = 'com_tjlms.course';
		$tjcertObj->state            = $template->state;
		$tjcertObj->checked_out      = $template->checked_out;
		$tjcertObj->checked_out_time = $template->checked_out_time;
		$tjcertObj->created_by       = $template->created_by;
		$tjcertObj->created_on       = $template->created_date;
		$tjcertObj->modified_by      = $template->modified_by;
		$tjcertObj->modified_on      = $template->modified_date;
		$tjcertObj->params           = $template->params;
		$tjcertObj->unique_code      = substr(md5(microtime()), rand(0, 26), 5);
		$tjcertObj->is_public        = 2;

		$db = Factory::getDBO();
		$db->insertObject('#__tj_certificate_templates', $tjcertObj, 'id');
		$lastCertificateId = $db->insertid();

		return $lastCertificateId;
	}

	/**
	 * This function migrate certificate tamplate with TJcertificate template
	 *
	 * @param   Int  $courseId            A course Id
	 *
	 * @param   Int  $lastCertificateId	  A last insert certificate id.
	 *
	 * @return  array $result
	 *
	 * @since   1.3.32
	 */
	public function updateTemplateId($courseId, $lastCertificateId)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		// Fields to update.
		$fields = array($db->quoteName('certificate_id') . ' = ' . (int) $lastCertificateId);

		// Conditions for which records should be updated.
		$conditions = array($db->quoteName('id') . ' = ' . (int) $courseId);
		$query->update($db->quoteName('#__tjlms_courses'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$result = $db->execute();

		return $result;
	}
}
