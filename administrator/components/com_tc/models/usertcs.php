<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tc
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2016-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Tc records.
 *
 * @since  1.6
 */
class TcModelUsertcs extends JModelList
{
/**
	* Constructor.
	*
	* @param   array  $config  An optional associative array of configuration settings.
	*
	* @see        JController
	* @since      1.6
	*/
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'name', 'uc.`name`',
				'title', 'c.`title`',
				'client', 'c.`client`',
				'accepted_date', 'a.`accepted_date`',
				'version', 'c.`version`'
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
	 */
	protected function populateState($ordering = 'c.title', $direction = 'asc')
	{
		// Initialise variables.
		$app = JFactory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$client = $app->getUserStateFromRequest($this->context . '.filter.client', 'filter_client', '', 'string');
		$this->setState('filter.client', $client);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_tc');
		$this->setState('params', $params);

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
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return   string A store id.
	 *
	 * @since    1.6
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
	 * @return   JDatabaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 'a.*,c.title,uc.name AS name,c.client,c.version'
			)
		);
		$query->from($db->quoteName('#__tc_acceptance', 'a'));

		// Join over the user field 'created_by'
		$query->join('LEFT', $db->quoteName('#__tc_content', 'c') . 'ON(' . $db->quoteName('c.tc_id') . '=' . $db->quoteName('a.tc_id') . ')');

		// Join over the users for the checked out user
		$query->join("LEFT", $db->quoteName('#__users', 'uc') . 'ON (' . $db->quoteName('uc.id') . '=' . $db->quoteName('a.user_id') . ')');

		$query->where($db->quoteName('c.state') . '=' . $db->quote('1'));

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'tc_id:') === 0)
			{
				$query->where($db->quoteName('a.tc_id') . ' = ' . $db->quote((int) substr($search, 3)));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('( c.title LIKE ' . $search . '  OR  c.client LIKE ' . $search . '  OR  uc.name LIKE ' . $search . ')');
			}
		}

		$client = $this->getState('filter.client');

		if (!empty($client))
		{
			$query->where($db->quoteName('c.client') . ' = ' . $db->quote($client));
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
	 * Get an array of data items
	 *
	 * @return mixed Array of data items on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();

		return $items;
	}

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return    JTable    A database object
	 *
	 * @since    1.6
	 */
	public function getTable($type = 'usertcs', $prefix = 'TcTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Delet attempts
	 *
	 * @param   ARRAY  $cid  array of lesson track id
	 *
	 * @return  true
	 *
	 * @since   1.0.0
	 */
	/**
	public function delete($cid)
	{
		$db = JFactory::getDbo();

		if (!empty($cid))
		{
			$group_to_delet = implode(',', $cid);

			$query = $db->getQuery(true);

			// Delete all orders as selected
			$conditions = array(
				$db->quoteName('tc_id') . ' IN ( ' . $group_to_delet . ' )',
			);

			$query->delete($db->quoteName('#__tc_acceptance'));
			$query->where($conditions);

			$db->setQuery($query);

			if (!$db->execute())
			{
					$this->setError($this->_db->getErrorMsg());

					return false;
			}
		}

		return true;
	}
	*/
	/**
	 * Method to save the user T&C acceptance entry.
	 *
	 * @param   INT     $userid     user id
	 * @param   INT     $tcId       latest   client version
	 * @param   STRING  $returnURL  original loaded page url
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function save($userid, $tcId, $returnURL)
	{
		if ($userid && $tcId)
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_tc/models/content.php';

			$contentModel = JModelLegacy::getInstance('content', 'TcModel');

			$tcClient = $contentModel->getTCClient($tcId);

			$table = $this->getTable();

			// TC accepted date time
			$date = JHtml::date('now', 'Y-m-d H:i:s', true);

			$tcURLObj = array();

			$tcURLObj['tc_id'] = $tcId;
			$tcURLObj['user_id'] = $userid;
			$tcURLObj['client'] = $tcClient;
			$tcURLObj['accepted_date'] = $date;
			$tcURLObj['params'] = '';

			$tcAcceptanceEntry = $table->save($tcURLObj);

			if ($tcAcceptanceEntry == 1)
			{
				return $returnURL;
			}
			else
			{
				return false;
			}
		}
	}
}
