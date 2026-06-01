<?php
/**
 * @package     Multiagency
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;
use Joomla\Data\DataObject;

jimport('joomla.application.component.modellist');
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Methods supporting a list of Multiagency records.
 *
 * @since  __DEPLOY__VERSION__
 */
class MultiagencyModelLicences extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      __DEPLOY__VERSION__
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'title','multiagency.title',
				'type','a.type',
				'course_id', 'course.title',
				'total_seats', 'a.total_seats',
				'used_seats', 'a.used_seats',
				'start_date', 'a.start_date',
				'ordering', 'a.ordering',
				'end_date', 'a.end_date',
				'comment', 'a.comment',
				'multiagency_id', 'a.multiagency_id'
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
	 * @since    __DEPLOY__VERSION__
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app  = Factory::getApplication();
		$list = $app->getUserState($this->context . '.list');

		$list['limit']     = (int) Factory::getConfig()->get('list_limit', 5);
		$list['start']     = $app->input->getInt('start', 0);

		$app->setUserState($this->context . '.list', $list);
		$app->input->set('list', null);

		// List state information.
		parent::populateState($ordering, $direction);

		$orderCol = $app->input->get('filter_order', 'a.id');

		if (!in_array($orderCol, $this->filter_fields))
		{
			$orderCol = 'a.ordering';
		}

		$this->setState('list.ordering', $orderCol);

		$listOrder = $app->input->get('filter_order_Dir', 'DESC');

		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
		{
			$listOrder = 'ASC';
		}

		$this->setState('list.direction', $listOrder);

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$start = $app->getUserStateFromRequest($this->context . '.limitstart', 'limitstart', 0, 'int');
		$limit = $app->getUserStateFromRequest($this->context . '.limit', 'limit', 0, 'int');

		if ($limit == 0)
		{
			$limit = $app->get('list_limit', 0);
		}

		$this->setState('list.limit', $limit);
		$this->setState('list.start', $start);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	protected function getListQuery()
	{
		$app  = Factory::getApplication();
		$input = Factory::getApplication()->input;

		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query
			->select(
				$this->getState(
					'list.select', 'a.*, multiagency.title as multiagencyname'
				)
			);

		$query->from('`#__tjmultiagency_licences` AS a');

		// Join over the users for the checked out user.
		$query->select('uc.name AS uEditor');
		$query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');

		// Join over the created by field 'created_by'
		$query->join('LEFT', '#__users AS created_by ON created_by.id = a.created_by');

		// Join over the created by field 'modified_by'
		$query->join('LEFT', '#__users AS modified_by ON modified_by.id = a.modified_by');
		$query->join('INNER', '#__tjmultiagency_multiagency AS multiagency ON multiagency.id = a.multiagency_id');

		$manager_id = $app->getUserState('user_id');

		// Get com_tjlms component status
		if (ComponentHelper::getComponent('com_tjlms', true)->enabled)
		{
			$cour_id = $app->getUserState('course_id');
			$query->select('course.title');
			$query->join('LEFT', '#__tjlms_courses AS course ON course.id = a.course_id');

			if ($cour_id)
			{
				$query->where("a.course_id = '" . $db->escape($cour_id) . "'");
			}
		}

		if ($manager_id)
		{
			$query->WHERE($db->quoteName('a.multiagency_id') . '= (' . ("select id from #__tjmultiagency_multiagency where manager_id =" . $manager_id) . ')');
		}

		$stateFilter = $this->getState('filter.state');

		if (!empty($stateFilter))
		{
			$query->where("a.state  IN ('" . implode("','", $stateFilter) . "')");
		}
		else
		{
			$query->where('a.state = 1');
		}

		// Filter by search in title
		$agencyFilter = $this->getState('filter.multiagency_id');

		if (!empty($agencyFilter))
		{
			$query->WHERE($db->quoteName('a.multiagency_id') . ' = ' . (int) $agencyFilter);
		}

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
				$query->where('(' . $db->quoteName('a.total_seats') . ' LIKE '
									. $search . 'OR'
									. $db->quoteName('a.used_seats') . ' LIKE '
									. $search . 'OR'
									. $db->quoteName('multiagency.title') . ' LIKE '
									. $search . 'OR'
									. $db->quoteName('course.title') . ' LIKE '
									. $search . ')');
			}
		}

		// Add the list ordering clause.
		$orderCol  = $this->getState('list.ordering', 'a.ordering');
		$orderDirn = $this->getState('list.direction', 'ASC');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Method to get an array of data items
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();

		foreach ($items as $item)
		{
			if (isset($item->course_id))
			{
				$values = explode(',', $item->course_id);

				$textValue = array();
				$usedSeats = $item->used_seats;
				$totalSeats = $item->total_seats;
				$item->availableSeats = $totalSeats - $usedSeats;
			}
		}

		return $items;
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

		return (date_create($date)) ? Factory::getDate($date)->format("Y-m-d") : null;
	}

	/**
	 * Method to get an array of data items
	 *
	 * @param   string  $multiagencyId  multiagencyId to be checked
	 *
	 * @param   string  $courseId       courseId to be checked
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function getLicenceId($multiagencyId, $courseId)
	{
		$db = Factory::getDbo();
		$query = "select id from #__tjmultiagency_licences where multiagency_id=" . $multiagencyId . " and course_id=" . $courseId;
		$db->setQuery($query);
		$results = $db->loadObject();

		return $results;
	}

	/**
	 * Method to check license condition on start-end date, count and role base acl
	 *
	 * @param   int  $licenseId  licenseId to be checked
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function isValidLicense($licenseId)
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models/licenceform.php');
		$licenseModel = BaseDatabaseModel::getInstance('LicenceForm', 'MultiagencyModel');
		$licenseData = $licenseModel->getData($licenseId);
		$subuserAuth = RBACL::authorise(Factory::getUser()->id, 'com_multiagency', 'core.manageenrollment', 'com_multiagency');

		if ($this->isValidSubscription($licenseData->id, $licenseData->total_seats) && $subuserAuth)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Check licnese is active or expired
	 *
	 * @param   INT  $licenseId   license id
	 * @param   INT  $totalCount  license total count
	 *
	 * @return boolean
	 *
	 * @since __DEPLOY__VERSION__
	 */
	public function isValidSubscription($licenseId, $totalCount = 0)
	{
		if ($licenseId)
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('id')));
			$query->from($db->quoteName('#__tjmultiagency_licences'));
			$query->where($db->quoteName('id') . ' = ' . $licenseId);

			// Check available seats
			if ($totalCount > 0 )
			{
				$query->where($db->quoteName('total_seats') . ' > ' . $db->quoteName('used_seats'));
			}

			// Check on expiration date get current date from php to avoid server timezone issue
			$currentdate = Factory::getDate('now', 'UTC');
			$query->where($db->quoteName('end_date') . ' >= ' . $db->quote($currentdate));
			$query->where($db->quoteName('start_date') . ' <= ' . $db->quote($currentdate));
			$db->setQuery($query);

			return ($db->loadResult()) ? true : false;
		}
	}
}
