<?php
/**
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2009 -2015 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
/**
 * Methods supporting a list of Certifictes records.
 *
 * @since       1.1.0
 * @deprecated  1.3.32 Use TJCertificate certificates model instead
 */
class TjlmsModelCertificates extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JControllerLegacy
	 */
	public function __construct($config = array())
	{
		$this->techjoomlacommon = new TechjoomlaCommon;
		$this->comtjlmsHelper       = new comtjlmsHelper;

		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'cert_id', 'ct.cert_id',
				'course_title', 'cs.title',
				'grant_date', 'ct.grant_date',
				'exp_date', 'ct.exp_date',
				'time_spent','expired','time_spent',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = Factory::getApplication('administrator');

		// Set ordering.
		$orderCol = $app->getUserStateFromRequest($this->context . '.filter_order', 'filter_order');

		if (!in_array($orderCol, $this->filter_fields))
		{
			$orderCol = 'grant_date';
		}

		$this->setState('list.ordering', $orderCol);

		// Set ordering direction.
		$listOrder = $app->getUserStateFromRequest($this->context . 'filter_order_Dir', 'filter_order_Dir');

		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
		{
			$listOrder = 'DESC';
		}

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		// Filtering val_type
		$this->setState('filter.val_type', $app->getUserStateFromRequest($this->context . '.filter.val_type', 'val_type', '', 'string'));

		// Load the parameters.
		$params = ComponentHelper::getParams('com_tjlms');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('grant_date', 'DESC');
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return    JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$userid  = Factory::getUser()->id;

		// Create a new query object.
		$db      = $this->getDbo();
		$query   = $db->getQuery(true);
		$utc_now = $db->quote(Factory::getDate('now', 'UTC')->format('Y-m-d'));

		// Select the required fields from the table.
		$query->select(
			'ct.id cert_unique_id, ct.cert_id as cert_id, ct.grant_date as grant_date,
			ct.exp_date as exp_date, IF(ct.exp_date < ' . $utc_now . ' AND ct.exp_date <> "0000-00-00 00:00:00", 1, 0) as expired,ct.user_id as user_id,
			cs.id as course_id,cs.title as course_title'
		);

		// Start time spent
		$CLQuery = $db->getQuery(true);
		$CLQuery->select('ls.id')
			->from($db->quoteName('#__tjlms_lessons') . ' ls')
			->where('ls.' . $db->quoteName('course_id') . ' = cs.id');

		$ltsQuery = $db->getQuery(true);
		$ltsQuery->select('SUM( TIME_TO_SEC(time_spent))')
			->from($db->quoteName('#__tjlms_lesson_track', 'lt'))
			->where($db->quoteName('lesson_id') . ' IN(' . $CLQuery->__toString() . ')');

		if ($userid)
		{
			$ltsQuery->where('lt.user_id = ' . (int) $userid);
		}

		$query->select('(' . $ltsQuery->__toString() . ') as time_spent');

		// End time spent

		$query->from('#__tjlms_certificate AS ct');

		// Join over the Courses.
		$query->join('INNER', '#__tjlms_courses AS cs ON cs.id = ct.course_id');

		// Join over the Certificate Templates.
		$query->join('INNER', '#__tjlms_certificate_template AS ctm ON ctm.id = cs.certificate_id AND certificate_term > 0');

		// Join over the Courses Track.
		$query->join('LEFT', '#__tjlms_course_track AS ctrk ON ctrk.course_id = ct.course_id');

		// Filter completed certificates
		$query->where('UPPER(ctrk.status) = "C"');

		// Filter course type
		$query->where('ct.type = "course"');

		// Filter logged in user certificate
		if ($userid)
		{
			$query->where('ct.user_id = ' . (int) $userid);
		}
		else
		{
			$query->where('FALSE');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('ct.cert_id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('( ct.cert_id LIKE ' . $search . ' OR  cs.title LIKE ' . $search . ')');
			}
		}

		// Group by
		$query->group('user_id,course_id');

		// Add the list ordering clause.
		$query->order($this->getState('list.ordering', 'grant_date') . ' ' . $this->getState('list.direction', 'DESC'));

		return $query;
	}

	/**
	 * To get the records
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		foreach ($items as &$item)
		{
			// Date according to user's timezone
			$item->disp_grant_date = $this->techjoomlacommon->getDateInLocal($item->grant_date, true, null, 'jS F Y');
			$item->disp_exp_date = $this->techjoomlacommon->getDateInLocal($item->exp_date, true, null, 'jS F Y');
			$item->disp_time_spent = Tjlms::Utilities()->secToHours($item->time_spent);
		}

		return $items;
	}

	/**
	 * Change seconds to readable format
	 *
	 * @param   int  $init  Time in second
	 *
	 * @return  string
	 */
	public function secToHours($init)
	{
		$hours = floor($init / 3600);
		$minutes = floor(($init / 60) % 60);
		$seconds = $init % 60;

		if ($hours)
		{
			return Text::sprintf('COM_TJLMS_CERTIFICATES_HOURS_FORMAT', $hours, $minutes, $seconds);
		}
		elseif ($minutes)
		{
			return Text::sprintf('COM_TJLMS_CERTIFICATES_MINUTES_FORMAT', $minutes, $seconds);
		}
		else
		{
			return Text::sprintf('COM_TJLMS_CERTIFICATES_SECONDS_FORMAT', $seconds);
		}
	}
}
