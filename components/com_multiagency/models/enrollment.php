<?php
/**
 * @package    Agency
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Data\DataObject;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\PluginHelper;

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of users.
 *
 * @since  1.0
 */
class MultiagencyModelEnrollment extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
			'title', 'b.title','subuserfilter',
			'total_seats', 'a.total_seats'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since    1.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app  = Factory::getApplication();
		$list = $app->getUserState($this->context . '.list');

		$ordering  = isset($list['filter_order'])     ? $list['filter_order']     : null;
		$direction = isset($list['filter_order_Dir']) ? $list['filter_order_Dir'] : null;

		$list['limit']     = (int) Factory::getConfig()->get('list_limit', 20);
		$list['start']     = $app->input->getInt('start', 0);
		$list['ordering']  = $ordering;
		$list['direction'] = $direction;

		$app->setUserState($this->context . '.list', $list);
		$app->input->set('list', null);

		// List state information.
		parent::populateState($ordering, $direction);
		$ordering  = $app->getUserStateFromRequest($this->context . '.ordercol', 'filter_order', $ordering);
		$direction = $app->getUserStateFromRequest($this->context . '.orderdirn', 'filter_order_Dir', $ordering);

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);
		$this->setState('list.ordering', $ordering);
		$this->setState('list.direction', $direction);

		$start = $app->getUserStateFromRequest($this->context . '.limitstart', 'limitstart', 0, 'int');
		$limit = $app->getUserStateFromRequest($this->context . '.limit', 'limit', 0, 'int');

		if ($limit == 0)
		{
			$limit = $app->get('list_limit', 0);
		}

		$this->setState('list.limit', $limit);
		$this->setState('list.start', $start);

		$courseFilter = $app->getUserStateFromRequest($this->context . '.filter.selectedcourse', 'selectedcourse');

		if (!empty($courseFilter))
		{
			$this->setState('filter.selectedcourse', $courseFilter[0]);
		}

		// Pre-selected default option to agency filter
		$agenciesFilter = $app->getUserStateFromRequest($this->context . '.filter.agencies', 'agencies');
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$multiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel');
		$agencies = $multiagencyModel->getAllocatedAgencies();

		// If yet filter is not set and agency array is not empty then we are setting default agency value to filter
		if (empty($agenciesFilter) && count($agencies) > 0)
		{
			$agenciesFilter[0] = $agencies[0]->id;
		}

		$this->setState('filter.agencies', $agenciesFilter[0]);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since    1.0
	 */
	protected function getListQuery()
	{
		$input    = Factory::getApplication()->input;
		$licenseId = $input->get('license', '0', 'INT');

		// Get agency data
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models/licenceform.php');
		$licenseModel = BaseDatabaseModel::getInstance('LicenceForm', 'MultiagencyModel');
		$licenseData = $licenseModel->getData($licenseId);
		$agencyId = $licenseData->multiagency_id;

		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select($this->getState('list.select', 'distinct(subusers.user_id), u.name, u.email,u.block, role.name as rolename'));
		$query->from($db->quoteName('#__tjsu_users', 'subusers'));
		$query->join('left', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('u.id') . ' = ' . $db->qn('subusers.user_id') . ')');
		$query->join('left', $db->quoteName('#__tjsu_roles', 'role') . ' ON (' . $db->quoteName('role.id') . ' = ' . $db->qn('subusers.role_id') . ')');
		$query->where($db->qn('u.block') . ' = 0');
		$query->where($db->qn('subusers.client') . " = 'com_multiagency'");

		JLoader::register('TjlmsModelenrolment', JPATH_SITE . '/components/com_tjlms/models/enrolment.php');
		$model = new TjlmsModelenrolment;

		$licenseType = $licenseData->type;

		if ($licenseType == 'all')
		{
			$model->setState('com_tjlms' . '.filter.course_type', 1);
			$courseIds = $this->getState('filter.selectedcourse');
		}
		else
		{
			$courseIds = $licenseData->course_id;
		}

		$enrolledUsers = $model->getCourseEnrolledUsers($courseIds);

		if (!empty($enrolledUsers))
		{
			$query->where($db->qn('subusers.user_id') . 'NOT IN (' . implode(',', $db->q($enrolledUsers)) . ')');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where($db->quoteName('u.name') . ' LIKE ' . $search);
				$query->where('(u.name LIKE ' . $search . ' OR ' . ' u.email LIKE ' . $search . ')');
			}
		}

		$agencies = $this->getState('filter.agencies');

		if (!empty($agencyId))
		{
			$query->where($db->qn('subusers.client_id') . ' = ' . $db->quote($agencyId));
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Overrides the default function to check Date fields format, identified by
	 * "_dateformat" suffix, and erases the field if it's not correct.
	 *
	 * @return void
	 */
	protected function loadFormData()
	{
		$app              = Factory::getApplication();
		$filters          = $app->getUserState($this->context . '.filter', array());
		$error_dateformat = false;

		foreach ($filters as $key => $value)
		{
			if (strpos($key, '_dateformat') && !empty($value) && $this->isValidDate($value) == null)
			{
				$filters[$key]    = '';
				$error_dateformat = true;
			}
		}

		if ($error_dateformat)
		{
			$app->enqueueMessage(Text::_("COM_MULTIAGENCY_SEARCH_FILTER_DATE_FORMAT"), "warning");
			$app->setUserState($this->context . '.filter', $filters);
		}

		return parent::loadFormData();
	}

	/**
	 * Checks if a given date is valid and in a specified format (YYYY-MM-DD)
	 *
	 * @param   string  $date  Date to be checked
	 *
	 * @return bool
	 */
	private function isValidDate($date)
	{
		$date = str_replace('/', '-', $date);

		return (date_create($date)) ? Factory::getDate($date)->format(Text::_("COM_MULTIAGENCY_ENROLLMENT_DATE_FORMAT")) : null;
	}

	/**
	 * Method to enroll to users
	 *
	 * @param   array  $selectedCourse  courses ids
	 * @param   array  $uId             Users ids
	 *
	 * @return mixed
	 */
	public function userEnrollment($selectedCourse, $uId)
	{
		// Check course is not empty
		if (!empty($selectedCourse))
		{
			$app = Factory::getApplication();
			$this->license = $app->input->get('license', '0', 'INT');

			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models/licenceform.php');
			$licenseModel = BaseDatabaseModel::getInstance('LicenceForm', 'MultiagencyModel');
			$licenseData = $licenseModel->getData($this->license);
			$licenseType = $licenseData->type;
			$agencyId = $licenseData->multiagency_id;

			JLoader::register('TjlmsCoursesHelper', JPATH_SITE . '/components/com_tjlms/helpers/courses.php');
			$lmscourseHelper = new TjlmsCoursesHelper;
			JLoader::register('ComtjlmsHelper', JPATH_SITE . '/components/com_tjlms/helpers/main.php');
			$lmsmainhelper = new ComtjlmsHelper;
			JLoader::register('TjlmsModelenrolment', JPATH_SITE . '/components/com_tjlms/models/enrolment.php');
			$tjlmsEnrolmentModel = new TjlmsModelenrolment;

			// Load agency helper file
			$path = JPATH_SITE . '/components/com_multiagency/helpers/multiagency.php';

			if (!class_exists('MultiagencyFrontendHelpers'))
			{
				// Require_once $path;
				JLoader::register('MultiagencyFrontendHelpers', $path);
				JLoader::load('MultiagencyFrontendHelpers');
			}

			$MultiagencyFrontendHelpers = new MultiagencyFrontendHelpers;
			BaseDatabaseModel::addIncludePath(JPATH_BASE . '/components/com_multiagency/model');
			$licencesModel = BaseDatabaseModel::getInstance('Licences', 'MultiagencyModel');

			$type = 'success';
			$success = $failed = 0;
			$result['success'] = 0;
			$result['failed'] = 0;

			// Enrollment from manage enrollment view.
			foreach ($selectedCourse as $key => $courseId)
			{
				$courseId = (int) $courseId;

				// Get non enrollment users
				$nonEnrolledUsers = $tjlmsEnrolmentModel->getNonEnrolledUsers($uId, $courseId);

				foreach ($nonEnrolledUsers as $userId)
				{
					$data = array();
					$data['user_id'] = (int) $userId;
					$data['course_id'] = (int) $courseId;
					$params = ComponentHelper::getParams('com_tjlms');
					$data['notify_user'] = $params->get('after_course_enroll_user', '0', 'INT');

					// Check license active or not
					if (!$licencesModel->isValidLicense($this->license))
					{
						$msg = Text::sprintf('JERROR_ALERTNOAUTHOR', $failed);
						$this->setError($msg);

						return false;
					}

					if (!$MultiagencyFrontendHelpers->checkMultiagencyUser($userId, $agencyId))
					{
						$msg = Text::sprintf('JERROR_ALERTNOAUTHOR', $failed);
						$this->setError($msg);

						return false;
					}

					if (!$tjlmsEnrolmentModel->userEnrollment($data))
					{
						$failed ++;
					}
					else
					{
						// Update end_time of tjlms_enrollment
						$lmscourseHelper->updateCourseEnrolledParams((int) $courseId, $userId, $licenseData->end_date, 'end_time');

						// Get enrollmentid;
						$enrollmentData = $lmsmainhelper->getEnrollmentDetails((int) $courseId, $userId);

						// Get orderid
						$orderData = $lmscourseHelper->getCourseOrderDetails((int) $courseId, $userId, 'userId', $enrollmentData->enrollment_id);

						Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
						$enrollmentTable = Table::getInstance('Enrollment', 'MultiagencyTable');

						// Save entry in agency_enrollment
						$agencyEnrollment = new stdClass;
						$agencyEnrollment->license_id = (int) $this->license;
						$agencyEnrollment->course_id = (int) $courseId;
						$agencyEnrollment->order_id = (int) $orderData->id;
						$agencyEnrollment->user_id = $userId;
						$agencyEnrollment->client = 'com_multiagency';
						$enrollmentTable->save($agencyEnrollment);

						$success ++;
					}
				}

				// Update course count
				
				PluginHelper::importPlugin('system');

				if ($licenseType == 'all')
				{
					$course = 0;
				}
				else
				{
					$course = $courseId;
				}

				Factory::getApplication()->triggerEvent('onAfterAgencyEnrol', array(
															$userid,
															$course,
															$this->license,
															$licenseData->multiagency_id,
															$success
														)
							);
			}

			$result['success'] = $success;
			$result['failed'] = $failed;

			return $result;
		}
		else
		{
			$msg = Text::_('COM_MULTIAGENCY_NO_COURSE_SELECTED');
			$this->setError($msg);

			return false;
		}
	}
}
