<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelUsers extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'name', 'username', 'email'
			);
		}

		parent::__construct($config);
	}
	
	protected function populateState($ordering = 'name', $direction = 'asc')
	{
		// Load the filter state.
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));

		// List state information.
		parent::populateState($ordering, $direction);
	}

	protected function getListQuery()
	{
		// create a new query object.
		$db	 	= $this->getDbo();
		$query 	= $db->getQuery(true);

		// get current user
		$user = JFactory::getUser();
		
		// get current permissions
		$permissions = RSTicketsProHelper::getCurrentPermissions();
		
		// select the required fields from the table.
		$query->select($db->qn('id'))
			  ->select($db->qn('name'))
			  ->select($db->qn('username'))
			  ->select($db->qn('email'))
			  ->from($db->qn('#__users'));
		
		// not allowed to add his own tickets
		if (!$permissions || !$permissions->add_ticket)
		{
			$query->where($db->qn('id').'!='.$db->q($user->get('id')));
		}
		
		// not allowed to add tickets on behalf of customers
		if (!$permissions || !$permissions->add_ticket_customers)
		{
			$subquery = $db->getQuery(true);
			
			$subquery->select($db->qn('user_id'))
					 ->from($db->qn('#__rsticketspro_staff'));
			$query->where($db->qn('id').' IN ('.(string) $subquery.')');
		}
		
		// not allowed to add tickets on behalf of other staff members
		if (!$permissions || !$permissions->add_ticket_staff)
		{
			$subquery = $db->getQuery(true);
			
			$subquery->select($db->qn('user_id'))
				  ->from($db->qn('#__rsticketspro_staff'));
			// special condition here - if the staff can submit tickets on his own we need to exclude him from the list of staff members
			if ($permissions && $permissions->add_ticket)
			{
				$subquery->where($db->qn('user_id').'!='.$db->q($user->get('id')));
			}
			
			$query->where($db->qn('id').' NOT IN ('.(string) $subquery.')');
		}
		
		// Filter the items over the search string if set.
		$search = $this->getState('filter.search', '');
		if (strlen($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->qn('id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				// Escape the search token.
				$token = $db->q('%' . str_replace(' ', '%', $db->escape($search, true)) . '%', false);

				// Compile the different search clauses.
				$searches = array();
				$searches[] = $db->qn('name').' LIKE ' . $token;
				$searches[] = $db->qn('username').' LIKE ' . $token;
				$searches[] = $db->qn('email').' LIKE ' . $token;

				// Add the clauses to the query.
				$query->where('(' . implode(' OR ', $searches) . ')');
			}
		}
		
		// Add the list ordering clause.
		$query->order($db->escape($this->getState('list.ordering', 'name')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

		return $query;
	}
	
	public function getField()
	{
		return JFactory::getApplication()->input->getCmd('field');
	}
}