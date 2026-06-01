<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelTickets extends JModelList
{
	protected $params = null;
	protected $_permissions = array();

	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'date', 'last_reply', 'flagged', 'code', 'subject', 'customer', 'priority', 'status', 'staff', 'department_id', 'priority_id', 'status_id', 'from', 'to'
			);

			if (RSTicketsProHelper::getConfig('enable_time_spent'))
			{
				$config['filter_fields'][] = 'time_spent';
			}
		}

		parent::__construct($config);

		$app = JFactory::getApplication();
		$this->params = new JRegistry();

		if ($app->isClient('site'))
		{
			$active = JFactory::getApplication()->getMenu()->getActive();
			if ($active && $active->component === 'com_rsticketspro')
			{
				$this->params = $active->getParams();
			}
		}

		$this->setPermissions();
	}

	public function getBulkForm()
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.tickets', 'tickets', array('control' => null, 'load_data' => false));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	public function getFilterForm($data = array(), $loadData = true)
	{
		$form = parent::getFilterForm($data, $loadData);

		foreach (array('department_id', 'priority_id', 'status_id') as $key)
		{
			$$key = $this->getState('filter.' . $key, array());

			if (is_array($$key) && count($$key) > 1)
			{
				$form->setFieldAttribute($key, 'multiple', 'true', 'filter');
				$form->setFieldAttribute($key, 'class', 'advancedSelect', 'filter');
			}
		}

		return $form;
	}

	protected function setPermissions()
	{
		$this->is_staff 	= RSTicketsProHelper::isStaff();
		$this->_permissions = RSTicketsProHelper::getCurrentPermissions();
	}

	public function getIsSearching() {
		// get filtering states
		$search  	 	= $this->getState('filter.search');
		$flagged 	 	= $this->getState('filter.flagged', 0);
		$priority_id 	= $this->getState('filter.priority_id', array());
		$status_id 	 	= $this->getState('filter.status_id', array());
		$department_id 	= $this->getState('filter.department_id', array());
		$customer 	 	= $this->getState('filter.customer', '');
		$staff 	 	 	= $this->getState('filter.staff', '');
		$from 	 	 	= $this->getState('filter.from', '');
		$to 	 	 	= $this->getState('filter.to', '');

		return $search != '' || $flagged || $priority_id || $status_id || $department_id || $customer != '' || $staff != '' || $from != '' || $to != '';
	}

	protected function setSearch($values=array()) {
		$app = JFactory::getApplication();

		if (isset($values['search'])) {
			$app->setUserState($this->context.'.filter.search', $values['search']);
		}
		if (isset($values['flagged'])) {
			$app->setUserState($this->context.'.filter.flagged', $values['flagged']);
		}
		if (isset($values['priority_id'])) {
			$app->setUserState($this->context.'.filter.priority_id', $values['priority_id']);
		}
		if (isset($values['status_id'])) {
			$app->setUserState($this->context.'.filter.status_id', $values['status_id']);
		}
		if (isset($values['department_id'])) {
			$app->setUserState($this->context.'.filter.department_id', $values['department_id']);
		}
		if (isset($values['customer'])) {
			$app->setUserState($this->context.'.filter.customer', $values['customer']);
		}
		if (isset($values['staff'])) {
			$app->setUserState($this->context.'.filter.staff', $values['staff']);
		}
		if (!empty($values['ordering'])) {
			$app->setUserState($this->context.'.ordercol', $values['ordering']);
		}
		if (!empty($values['direction'])) {
			$app->setUserState($this->context.'.orderdirn', $values['direction']);
		}
		// performing a predefined search?
		if (isset($values['predefined_search'])) {
			$app->setUserState($this->context.'.filter.predefined_search', $values['predefined_search']);
		}
		if (isset($values['from']))
		{
			$app->setUserState($this->context.'.filter.from', $values['from']);
		}
		if (isset($values['to']))
		{
			$app->setUserState($this->context.'.filter.to', $values['to']);
		}
	}

	public function getPredefinedSearch()
	{
		return $this->getState('filter.predefined_search', 0);
	}

	public function getFlagged()
	{
		return $this->getState('filter.flagged', 0);
	}

	public function resetSearch() {
		$values = array(
			'search' => '',
			'flagged' => 0,
			'priority_id' => array(),
			'status_id' => array(),
			'department_id' => array(),
			'customer' => '',
			'staff' => '',
			'predefined_search' => 0,
			'ordering' => 'date',
			'direction' => 'desc',
			'from' => '',
			'to' => ''
		);
		JFactory::getApplication()->setUserState($this->context.'.limitstart', 0);
		$this->setSearch($values);
	}

	public function performSearch($table) {
		$values = array(
			'search' => '',
			'flagged' => 0,
			'priority_id' => array(),
			'status_id' => array(),
			'department_id' => array(),
			'customer' => '',
			'staff' => '',
			'predefined_search' => $table->id,
			'ordering' => 'date',
			'direction' => 'desc',
			'from' => '',
			'to' => ''
		);

		if (is_array($table->params)) {
			$values = array_merge($values, $table->params);
			// legacy
			if (isset($values['filter_word'])) {
				$values['search'] = $values['filter_word'];
			}
		}
		$this->setSearch($values);
	}

	public function getSearches() {
		$db 	= $this->getDbo();
		$query	= $db->getQuery(true);
		$user 	= JFactory::getUser();

		$query->select('*')
			->from($db->qn('#__rsticketspro_searches'))
			->where($db->qn('user_id').'='.$db->q($user->get('id')))
			->where($db->qn('published').'='.$db->q(1))
			->order($db->qn('ordering').' '.$db->escape('asc'));
		$db->setQuery($query);
		$list = $db->loadObjectList();

		$current = $this->getPredefinedSearch();
		foreach ($list as $k => $item) {
			$item->current = $current == $item->id;
			$list[$k] = $item;
		}

		return $list;
	}

	public function getPermissions() {
		$mainframe = JFactory::getApplication();
		if ($mainframe->isClient('administrator') && empty($this->_permissions))
		{
			$mainframe->enqueueMessage(JText::_('RST_PERMISSIONS_ERROR'), 'warning');
			$mainframe->redirect(RSTicketsProHelper::route('index.php?option=com_rsticketspro', false));
		}

		return @$this->_permissions;
	}

	public function writeCSV($from, $fileHash = '')
	{
		if (empty($this->_permissions->export_tickets))
		{
			throw new Exception(JText::_('RST_STAFF_CANNOT_EXPORT_TICKETS'));
		}

		require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/export.php';

		// setting the function arguments
		$query  		= $this->getListQuery();
		$totalItems  	= (int) $this->getTotal();

		$filename 		= JText::_('COM_RSTICKETSPRO_TICKETS');

		return RsticketsExport::writeCSV($query, $totalItems, $from, $fileHash, $filename);
	}

	protected function getListQuery() {
		$db 	= JFactory::getDbo();
		$app	= JFactory::getApplication();
		$query 	= $db->getQuery(true);
		$user   = JFactory::getUser();

		// get filtering states
		$search  	 	= $this->getState('filter.search');
		$flagged 	 	= $this->getState('filter.flagged', 0);
		$priority_id 	= $this->getState('filter.priority_id', array());
		$status_id 	 	= $this->getState('filter.status_id', array());
		$department_id 	= $this->getState('filter.department_id', array());
		$customer 	 	= $this->getState('filter.customer', '');
		$staff 	 	 	= $this->getState('filter.staff', '');
		$from 	 	 	= $this->getState('filter.from', '');
		$to 	 	 	= $this->getState('filter.to', '');

		// Joomla! 3 fix
		if ($from === '0000-00-00 00:00:00')
		{
			$from = '';
		}
		if ($to === '0000-00-00 00:00:00')
		{
			$to = '';
		}

		// Workarounds to accept integers and arrays
		foreach (array('department_id', 'priority_id', 'status_id') as $key)
		{
			if (!is_array($$key))
			{
				$$key = array($$key);
			}
			$$key = array_filter($$key);
		}

		$userInfo = RSTicketsProHelper::getConfig('show_user_info');
		$query->select($db->qn('c.' . $userInfo, 'customer'))
			->select($db->qn('s.' . $userInfo, 'staff'));

		$query->select($db->qn('t').'.*')
			->select($db->qn('st.name', 'status'))
			->select($db->qn('pr.name', 'priority'))
			->from($db->qn('#__rsticketspro_tickets', 't'))
			->join('left', $db->qn('#__users', 'c').' ON ('.$db->qn('t.customer_id').'='.$db->qn('c.id').')')
			->join('left', $db->qn('#__users', 's').' ON ('.$db->qn('t.staff_id').'='.$db->qn('s.id').')')
			->join('left', $db->qn('#__rsticketspro_statuses', 'st').' ON ('.$db->qn('t.status_id').'='.$db->qn('st.id').')')
			->join('left', $db->qn('#__rsticketspro_priorities', 'pr').' ON ('.$db->qn('t.priority_id').'='.$db->qn('pr.id').')');

		if ($this->is_staff) {
			$departments = RSTicketsProHelper::getCurrentDepartments();
			$show_filter = $this->params->get('show_filter','');

			if (!empty($departments)) {
				if ($show_filter != 'show_assigned' && $show_filter != 'show_unassigned') {
					$query->where('('.$db->qn('department_id').' IN ('.$this->quoteImplode($departments).') OR '.$db->qn('customer_id').'='.$db->q($user->get('id')).')');
				} else {
					$query->where($db->qn('department_id').' IN ('.$this->quoteImplode($departments).')');
				}
			}

			// do we have a filter set ?
			if ($app->isClient('site')) {
				if ($show_filter) {
					switch ($show_filter)
					{
						case 'show_assigned':
							$query->where($db->qn('staff_id').'='.$db->q($user->get('id')));
							break;

						case 'show_submitted':
							$query->where($db->qn('customer_id').'='.$db->q($user->get('id')));
							break;

						case 'show_both':
							$query->where('('.$db->qn('staff_id').'='.$db->q($user->get('id')).' OR '.$db->qn('customer_id').'='.$db->q($user->get('id')).')');
							break;

						case 'show_unassigned':
							$query->where($db->qn('staff_id').'='.$db->q(0));
							break;
					}
				}
			}

			// can't see unassigned tickets
			if (!$this->_permissions->see_unassigned_tickets) {
				$query->where($db->qn('staff_id').'>'.$db->q(0));
			}
			// can't see other (assigned) tickets
			if (!$this->_permissions->see_other_tickets) {
				$staffIds = array(
					$db->q(0),
					$db->q($user->get('id'))
				);

				$query->where($db->qn('staff_id').' IN ('.implode(', ', $staffIds).')');
			}

			// searching for flagged?
			if ($flagged) {
				$query->where($db->qn('flagged').'='.$db->q(1));
			}
		} else {
			$query->where($db->qn('customer_id').'='.$db->q($user->get('id')));
		}

		if ($app->isClient('site')) {
			// showing a specific priority?
			if ($this->params->get('default_priority') && empty($priority_id)) {
				$default_priority = $this->params->get('default_priority');

				if (is_array($default_priority)) {
					$query->where($db->qn('priority_id').' IN ('.$this->quoteImplode($default_priority).')');
				}
			}
			// showing a specific status?
			if ($this->params->get('default_status') && empty($status_id)) {
				$default_status = $this->params->get('default_status');
				if (is_array($default_status)) {
					$query->where($db->qn('status_id').' IN ('.$this->quoteImplode($default_status).')');
				}
			}
		}

		// priority search
		if (!empty($priority_id)) {
			$query->where($db->qn('priority_id').' IN ('.$this->quoteImplode($priority_id).')');
		}

		// status search
		if (!empty($status_id)) {
			$query->where($db->qn('status_id').' IN ('.$this->quoteImplode($status_id).')');
		}

		// are we searching?
		if ($search != '') {
			$search = $db->q('%'.str_replace(' ', '%', $db->escape($search, true)).'%', false);

			$subquery = $db->getQuery(true);
			$subquery->select($db->qn('ticket_id'))
				->from($db->qn('#__rsticketspro_ticket_messages'))
				->where($db->qn('user_id').'!='.$db->q('-1'))
				->where($db->qn('message').' LIKE '.$search);

			$query->where('('.$db->qn('code').' LIKE '.$search.' OR '.$db->qn('subject').' LIKE '.$search.' OR '.$db->qn('t.id').' IN ('.(string) $subquery.'))');
		}

		// specific customer?
		if ($customer) {
			// let's see if it's ID:number
			if (substr($customer, 0, strlen('ID:')) == 'ID:') {
				$parts = explode(':', $customer, 2);
				$id = (int) $parts[1];

				$query->where($db->qn('customer_id').'='.$db->q($id));
			} else {
				$customer = $db->q('%'.str_replace(' ', '%', $db->escape($customer, true)).'%', false);

				$query->where('('.$db->qn('c.username').' LIKE '.$customer.' OR '.$db->qn('c.name').' LIKE '.$customer.' OR '.$db->qn('c.email').' LIKE '.$customer.')');
			}
		}

		// specific staff member?
		if ($staff || $staff === '0') {
			// legacy
			if ($staff === '0') {
				$staff = 'ID:0';
			}
			// let's see if it's ID:number
			if (substr($staff, 0, strlen('ID:')) == 'ID:') {
				$parts = explode(':', $staff, 2);
				$id = (int) $parts[1];

				$query->where($db->qn('staff_id').'='.$db->q($id));
			} else {
				$staff = $db->q('%'.str_replace(' ', '%', $db->escape($staff, true)).'%', false);

				$query->where('('.$db->qn('s.username').' LIKE '.$staff.' OR '.$db->qn('s.name').' LIKE '.$staff.' OR '.$db->qn('s.email').' LIKE '.$staff.')');
			}
		}

		if ($department_id) {
			$query->where($db->qn('department_id').' IN ('.$this->quoteImplode($department_id).')');
		}

		if ($from || $to)
		{
			$offset = JFactory::getApplication()->get('offset');
			if ($from)
			{
				try
				{
					$query->where($db->qn('t.date') . ' >= ' . $db->q(JFactory::getDate($from . ' 00:00:00', $offset)->toSql()));
				}
				catch (Exception $e)
				{
					$app->enqueueMessage($e->getMessage());
				}
			}
			if ($to)
			{
				try
				{
					$query->where($db->qn('t.date') . ' <= ' . $db->q(JFactory::getDate($to . ' 23:59:59', $offset)->toSql()));
				}
				catch (Exception $e)
				{
					$app->enqueueMessage($e->getMessage());
				}
			}
		}

		$ordering = $this->getState('list.ordering', 'date');
		$dir	  = $this->getState('list.direction', 'desc');

		// order by
		switch ($ordering)
		{
			case 'priority':
				$values = array();
				$priorities = $this->getPriorities($dir);
				foreach ($priorities as $priority)
				{
					$values[] = $priority->name;
				}
				$query->order('FIELD(' . $db->qn($ordering) . ', ' . $this->quoteImplode($values) . ')');
				break;

			case 'status':
				$values = array();
				$statuses = $this->getStatuses($dir);
				foreach ($statuses as $status)
				{
					$values[] = $status->name;
				}
				$query->order('FIELD(' . $db->qn($ordering) . ', ' . $this->quoteImplode($values) . ')');
				break;

			default:
				$query->order($db->qn($ordering).' '.$db->escape($dir));
				break;
		}

		return $query;
	}

	protected function quoteImplode($array) {
		$db = JFactory::getDbo();
		foreach ($array as $k => $v) {
			$array[$k] = $db->q($v);
		}

		return implode(',', $array);
	}

	protected function populateState($ordering = null, $direction = null)
	{
		// Status ID
		$this->setState('filter.status_id', $this->getUserStateFromRequest($this->context.'.filter.status_id', 'status_id', array(), 'array', false));

		// Department ID
		$this->setState('filter.department_id', $this->getUserStateFromRequest($this->context.'.filter.department_id', 'department_id', array(), 'array', false));

		// Priority ID
		$this->setState('filter.priority_id', $this->getUserStateFromRequest($this->context.'.filter.priority_id',	'priority_id', array(), 'array', false));

		// Flagged
		$this->setState('filter.flagged', $this->getUserStateFromRequest($this->context.'.filter.flagged', 'flagged', 0, 'none', false));

		// Search keyword
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search', '', 'none', true));

		// Customer
		$this->setState('filter.customer', $this->getUserStateFromRequest($this->context.'.filter.customer', 'customer', '', 'none', true));

		// Staff
		$this->setState('filter.staff', $this->getUserStateFromRequest($this->context.'.filter.staff', 'staff', '', 'none', true));

		// Predefined search
		$this->setState('filter.predefined_search', $this->getUserStateFromRequest($this->context.'.filter.predefined_search', 'predefined_search', 0, 'int', false));

		// From & To
		$this->setState('filter.from', $this->getUserStateFromRequest($this->context.'.filter.from', 'from', '', 'none', false));
		$this->setState('filter.to', $this->getUserStateFromRequest($this->context.'.filter.to', 'to', '', 'none', false));

		// List state information.
		$column = $this->params->get('orderby', 'date');
		$dir	= $this->params->get('direction', 'desc');

		parent::populateState($column, $dir);
	}

	public function getPriorities($dir = 'asc') {
		$db 	= $this->getDbo();
		$query 	= $db->getQuery(true);

		$query->select('*')
			->from($db->qn('#__rsticketspro_priorities'))
			->where($db->qn('published').'='.$db->q(1))
			->order($db->qn('ordering').' '.$db->escape($dir));
		$db->setQuery($query);
		return $db->loadObjectList();
	}

	public function getStatuses($dir = 'asc') {
		$db 	= $this->getDbo();
		$query 	= $db->getQuery(true);

		$query->select('*')
			->from($db->qn('#__rsticketspro_statuses'))
			->where($db->qn('published').'='.$db->q(1))
			->order($db->qn('ordering').' '.$db->escape($dir));
		$db->setQuery($query);
		return $db->loadObjectList();
	}

	public function getStart() {
		$app = JFactory::getApplication();
		if ($app->isClient('site')) {
			return $app->input->get('limitstart', 0, 'uint');
		} else {
			return parent::getStart();
		}
	}
}