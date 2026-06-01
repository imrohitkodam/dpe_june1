<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;


jimport('joomla.application.component.modeladmin');

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since       1.0.0
 * @deprecated  1.3.32 Use TJCertificate certificate model instead
 */
class TjlmsModelCertificate extends AdminModel
{
	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   type    $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return	JTable	A database object
	 *
	 * @since	1.6
	 */
	public function getTable($type = 'Certificate', $prefix = 'TjlmsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  mixed  The user id on success, false on failure.
	 *
	 * @since  1.0
	 */
	public function save($data)
	{
		if (empty($data['course_id']))
		{
			return false;
		}

		if (empty($data['user_id']))
		{
			$data['user_id'] = Factory::getUser()->id;
		}

		$courseId = $data['course_id'];
		$userId   = $data['user_id'];

		JLoader::import('components.com_tjlms.helpers.courses', JPATH_SITE);
		$TjlmsCoursesHelper = new TjlmsCoursesHelper;
		$courseProgress = $TjlmsCoursesHelper->getCourseProgress($courseId, $userId);

		if ($courseProgress['status'] == 'C')
		{
			$result    = $this->getTable('course');
			$result->load(array('id' => (int) $courseId));

			$certTable = $this->getTable('certificate');
			$isPresent = $certTable->load(array('user_id' => (int) $userId, 'course_id' => (int) $courseId));

			if (empty($data['exp_date']))
			{
				if (empty($result->expiry))
				{
					$data['exp_date'] = '000-00-00 00:00:00';
				}
				else
				{
					$data['exp_date'] = Factory::getDate("now +" . $result->expiry . "day")->format("Y-m-d H:i:s");
				}
			}

			if (!$isPresent)
			{
				if (empty($data['cert_id']))
				{
					$certPrefix = ComponentHelper::getParams('com_tjlms')->get('certificate_prefix', 'LMS-CERT');
					$data['cert_id']    = $certPrefix . '-' . rand() . "-" . $result->certificate_id;
				}

				if (empty($data['grant_date']))
				{
					$data['grant_date'] = Factory::getDate()->toSql();
				}

				return parent::save($data);
			}
		}

		return false;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm  A JForm object on success, false on failure
	 *
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tjlms.certificate', 'certificate', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return	mixed	Object on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Do any procesing on fields here if needed
		}

		return $item;
	}

	/**
	 * Check if User has completed the course
	 *
	 * @param   Int  $userId    user ID
	 * @param   Int  $courseId  course ID
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function checkIfCourseCompleted($userId, $courseId)
	{
		JLoader::import('components.com_tjlms.helpers.courses', JPATH_SITE);
		$trackingHelper = new TjlmsCoursesHelper;

		$courseProgress = $trackingHelper->getCourseProgress($courseId, $userId);

		$progress_in_percent = 0;

		if (isset($courseProgress["completionPercent"]) && !empty($courseProgress["completionPercent"]))
		{
			$progress_in_percent = $courseProgress["completionPercent"];
		}

		if ($progress_in_percent == 100)
		{
			return true;
		}

		return false;
	}

	/**
	 * Check if Course Certificate Expired
	 *
	 * @param   Int  $userId    user ID
	 * @param   Int  $courseId  course ID
	 * @param   Int  $certid    Certificate Primary key
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function checkIfCourseCertExpired($userId, $courseId,  $certid = null)
	{
		try
		{
			$db = Factory::getDbo();
			$query   = $db->getQuery(true);
			$utc_now = $db->quote(Factory::getDate('now', 'UTC')->format('Y-m-d'));
			$query->select('id,exp_date, IF(exp_date < ' . $utc_now . ' AND exp_date <> "0000-00-00 00:00:00", 1, 0) as expired');
			$query->from($query->qn('#__tjlms_certificate'));

			if ($userId && $courseId)
			{
				$query->where($query->qn('user_id') . ' = ' . $query->q((int) $userId));
				$query->where($query->qn('course_id') . ' = ' . $query->q((int) $courseId));
			}
			elseif ($certid)
			{
				$query->where($query->qn('id') . ' = ' . $query->q((int) $certid));
			}
			else
			{
				return false;
			}

			$db->setQuery($query);
			$cert = $db->loadObject();

			if ($cert)
			{
				return ($cert->expired) ? true : false;
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}
}
