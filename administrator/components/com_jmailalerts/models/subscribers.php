<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\ListModel;

/**
 * Methods supporting a list of Jmailalerts records.
 *
 * @since  2.6.1
 */
class JmailalertsModelsubscribers extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   3.2
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'ordering', 'a.ordering',
				'state', 'a.state',
				'user_id', 'a.user_id',
				'alert_id', 'a.alert_id',
				'name', 'a.name',
				'email_id', 'a.email_id',
				'frequency', 'a.frequency',
				'date', 'a.date',
				'plugins_subscribed_to', 'a.plugins_subscribed_to',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState($ordering = 'id', $direction = 'desc')
	{
		// Load filter state
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Load filter state
		$alertName = $this->getUserStateFromRequest($this->context . '.filter.alertdetails', 'alertdetails');
		$this->setState('filter.alertdetails', $alertName);

		// Split context into component and optional section

		/*$parts = FieldsHelper::extract($search);

		if ($parts)
		{
			$this->setstate('filter.component', $parts[0]);
			$this->setstate('filter.section', $parts[1]);
		}*/

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  An identifier string to generate the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since   1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	DataObjectbaseQuery
	 *
	 * @since	1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db		   = $this->getDbo();
		$query	 = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*') . ', subOrg.org_name as orgName');
		$query->from('`#__jma_subscribers` AS a');

		// Get the frequency name by frequency id
		$query->select('freq.name AS frequencyname,freq.id freqid');
		$query->join('LEFT', '`#__jma_frequencies` AS freq ON freq.id=a.frequency');

		// Get the alert name by alert id
		$query->select('alert.title AS alert_name');
		$query->join('LEFT', '`#__jma_alerts` AS alert ON alert.id=a.alert_id');

		// DPE hack to show the organisation name for the guest users
		$query->join('LEFT', '`#__jma_subscriber_org_xref` AS subOrg ON subOrg.jma_sub_id=a.id');



		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where('a.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.state IN (0, 1))');
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
				$query->where('( a.user_id LIKE ' . $search . '  OR  a.name LIKE ' . $search . ' )');
			}
		}

		// Filtering alert_id
		$filterAlertId = $this->state->get("filter.alert_id");

		if ($filterAlertId)
		{
			$query->where("a.alert_id = '" . $db->escape($filterAlertId) . "'");
		}

		// Filtering by Alert name
		$alertList = $this->getState('filter.alertdetails');

		if ($alertList)
		{
			$query->where($db->quoteName('a.alert_id') . '=' . $db->quote($alertList));
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
	 * Get alerts list for filter
	 *
	 * @return  array
	 */
	public function getFilterOptionsAlert()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('a.id, a.title')
			->from('`#__jma_alerts` AS a')
			->where('a.state=1');
		$db->setQuery($query);

		return $db->loadobjectList();
	}
}
