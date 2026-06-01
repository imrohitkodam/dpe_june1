<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelRsticketspro extends JModelList
{
	protected $params = null;
	protected $_permissions = array();

	public function __construct($config = array()) {
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'date', 'last_reply', 'flagged', 'code', 'subject', 'customer', 'priority', 'status', 'staff'
			);

			if (RSTicketsProHelper::getConfig('enable_time_spent')) {
				$config['filter_fields'][] = 'time_spent';
			}
		}

		parent::__construct($config);

		$app = JFactory::getApplication();
		$this->params = $app->isClient('site') ? $app->getParams('com_rsticketspro') : new JRegistry();
		$this->setPermissions();
	}

	protected function setPermissions() {
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

		return $search != '' || $flagged || $priority_id || $status_id || $department_id || $customer != '' || $staff != '';
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
	}

	public function getPredefinedSearch() {
		$app = JFactory::getApplication();
		return $app->getUserState($this->context.'.filter.predefined_search', 0);
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
			'direction' => 'desc'
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
			'direction' => 'desc'
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

		switch (RSTicketsProHelper::getConfig('show_user_info')) {
			case 'username':
				$query->select($db->qn('c.username', 'customer'))
					  ->select($db->qn('s.username', 'staff'));
			break;

			case 'name':
				$query->select($db->qn('c.name', 'customer'))
					  ->select($db->qn('s.name', 'staff'));
			break;

			case 'email':
				$query->select($db->qn('c.email', 'customer'))
					  ->select($db->qn('s.email', 'staff'));
			break;
		}

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
			$search = $db->quote('%'.str_replace(' ', '%', $db->escape($search, true)).'%', false);

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
				$customer = $db->quote('%'.str_replace(' ', '%', $db->escape($customer, true)).'%', false);

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
				$staff = $db->quote('%'.str_replace(' ', '%', $db->escape($staff, true)).'%', false);

				$query->where('('.$db->qn('s.username').' LIKE '.$staff.' OR '.$db->qn('s.name').' LIKE '.$staff.' OR '.$db->qn('s.email').' LIKE '.$staff.')');
			}
		}

		if ($department_id) {
			$query->where($db->qn('department_id').' IN ('.$this->quoteImplode($department_id).')');
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
			$array[$k] = $db->quote($v);
		}

		return implode(',', $array);
	}

	public function getUserStateFromRequest($key, $request, $default = null, $type = 'none', $resetPage = true)
	{
		$app       = JFactory::getApplication();
		$input     = $app->input;
		$old_state = $app->getUserState($key);
		$cur_state = (!is_null($old_state)) ? $old_state : $default;
		$new_state = $input->get($request, null, $type);

		if (($cur_state != $new_state) && !is_null($new_state) && ($resetPage))
		{
			$input->set('limitstart', 0);
		}

		// Save the new value only if it is set in this request.
		if ($new_state !== null)
		{
			$app->setUserState($key, $new_state);
		}
		else
		{
			$new_state = $cur_state;
		}

		return $new_state;
	}

	protected function populateState($ordering = null, $direction = null) {
		$app 	= JFactory::getApplication();
		$input 	= $app->input;

		// Status ID
		// Do we have a quick filter set?
		$quick_status_id = $input->get('quick_status_id', null, 'none');
		if (!empty($quick_status_id)) {
			$input->set('status_id', (array) $quick_status_id);
		}
		// Get the request
		$status_id = $input->get('status_id', null, 'array');
		// Fix the array
		if (is_array($status_id)) {
			foreach ($status_id as $k => $v) {
				if (!is_numeric($v)) {
					unset($status_id[$k]);
				}
			}
			$input->set('status_id', (array) $status_id);
		}
		$this->setState('filter.status_id', $this->getUserStateFromRequest($this->context.'.filter.status_id', 'status_id', array(), 'array', false));

		// Department ID
		// Do we have a quick filter set?
		$quick_department_id = $input->get('quick_department_id', null, 'none');
		if (!empty($quick_department_id)) {
			$input->set('department_id', $quick_department_id);
		}
		// Get the request
		$department_id = $input->get('department_id', null, 'array');
		// Fix the array
		if (is_array($department_id)) {
			foreach ($department_id as $k => $v) {
				if (!is_numeric($v)) {
					unset($department_id[$k]);
				}
			}
			$input->set('department_id', (array) $department_id);
		}
		$this->setState('filter.department_id', $this->getUserStateFromRequest($this->context.'.filter.department_id', 'department_id', array(), 'array', false));

		// Priority ID
		// Do we have a quick filter set?
		$quick_priority_id = $input->get('quick_priority_id', null, 'none');
		if (!empty($quick_priority_id)) {
			$input->set('priority_id', $quick_priority_id);
		}
		// Get the request
		$priority_id = $input->get('priority_id', null, 'array');
		// Fix the array
		if (is_array($priority_id)) {
			foreach ($priority_id as $k => $v) {
				if (!is_numeric($v)) {
					unset($priority_id[$k]);
				}
			}
			$input->set('priority_id', (array) $priority_id);
		}
		$this->setState('filter.priority_id', $this->getUserStateFromRequest($this->context.'.filter.priority_id',	'priority_id', array(), 'array', false));

		// Flagged fix
		$flagged = $input->get('flagged', null, 'none');
		if (!$flagged) {
			$input->set('flagged', 0);
		}
		$this->setState('filter.flagged', $this->getUserStateFromRequest($this->context.'.filter.flagged', 'flagged', 0, 'none', true));

		// Search keyword
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search', '', 'none', true));

		// Customer
		$this->setState('filter.customer', $this->getUserStateFromRequest($this->context.'.filter.customer', 'customer', '', 'none', true));

		// Staff
		$this->setState('filter.staff', $this->getUserStateFromRequest($this->context.'.filter.staff', 'staff', '', 'none', true));

		// List state information.
		$column = $this->params->get('orderby', 'date');
		$dir	= $this->params->get('direction', 'desc');

		parent::populateState($column, $dir);
	}

	public function getFilterBar() {
		require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/adapters/filterbar.php';

		$options = array();
		$options['search'] = array(
			'label' => JText::_('JSEARCH_FILTER'),
			'value' => $this->getState('filter.search')
		);
		$options['limitBox']  = $this->getPagination()->getLimitBox();
		$options['listDirn']  = $this->getState('list.direction', 'desc');
		$options['listOrder'] = $this->getState('list.ordering', 'date');
		$options['sortFields'] = array(
			JHtml::_('select.option', 'date', JText::_('RST_TICKET_DATE')),
			JHtml::_('select.option', 'last_reply', JText::_('RST_TICKET_LAST_REPLY')),
			JHtml::_('select.option', 'flagged', JText::_('RST_FLAGGED')),
			JHtml::_('select.option', 'code', JText::_('RST_TICKET_CODE')),
			JHtml::_('select.option', 'subject', JText::_('RST_TICKET_SUBJECT')),
			JHtml::_('select.option', 'customer', JText::_('RST_TICKET_CUSTOMER')),
			JHtml::_('select.option', 'priority', JText::_('RST_TICKET_PRIORITY')),
			JHtml::_('select.option', 'status', JText::_('RST_TICKET_STATUS')),
			JHtml::_('select.option', 'staff', JText::_('RST_TICKET_STAFF')),
			JHtml::_('select.option', 'time_spent', JText::_('RST_TIME_SPENT'))
		);
		$options['leftItems'] = array(
			'<button id="rst_advanced_search" type="button" class="btn" onclick="document.location=\''.JRoute::_('index.php?option=com_rsticketspro&view=search&advanced=1').'\'">'.JText::_('RST_OPEN_ADVANCED_SEARCH').'</button>'
		);

		$options['rightItems'] = array(
			array(
				'input' => '<select name="quick_department_id" class="inputbox" onchange="this.form.submit()">'."\n"
						   .'<option value="">'.JText::_('RST_SELECT_DEPARTMENT').'</option>'."\n"
						   .JHtml::_('select.options', RSTicketsProHelper::getDepartments(), 'value', 'text', null, true)."\n"
						   .'</select>'
			),
			array(
				'input' => '<select name="quick_priority_id" class="inputbox" onchange="this.form.submit()">'."\n"
						   .'<option value="">'.JText::_('RST_SELECT_PRIORITY').'</option>'."\n"
						   .JHtml::_('select.options', RSTicketsProHelper::getPriorities(), 'value', 'text', null, true)."\n"
						   .'</select>'
			),
			array(
				'input' => '<select name="quick_status_id" class="inputbox" onchange="this.form.submit()">'."\n"
						   .'<option value="">'.JText::_('RST_SELECT_STATUS').'</option>'."\n"
						   .JHtml::_('select.options', RSTicketsProHelper::getStatuses(), 'value', 'text', null, true)."\n"
						   .'</select>'
			)
		);


		$bar = new RSFilterBar($options);

		return $bar;
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