<?php
/**
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2023 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die();
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Methods supporting a list of countries records.
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsModelConditions extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.6
	 * @see     JController
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'state', 'state',
				'show', 'a.show',
				'field_to_show', 'a.field_to_show',
				'condition_match', 'a.condition_match',
				'condition', 'a.condition'
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
	 * @since   1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = Factory::getApplication('administrator');

		// Load the filter search
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Load the filter state
		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_tjfields');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.id', 'asc');
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
	 * @return  JDatabaseQuery
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$client = Factory::getApplication()->input->get('client', '', 'STRING');

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 'a.*'
			)
		);
		$query->from('`#__tjfields_fields_conditions` AS a');

		if (!empty($client))
		{
			$query->where('a.client = "'. $client . '"');
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}
	
	/**
	 * Get conditional fields.
	 * 
	 * @return array of option for the particular field
	 */
	public function getConditionalFields()
	{
		$db = Factory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('field_to_show FROM #__tjfields_fields_conditions');
		$query->where('state = 1');
		$db->setQuery($query);
		$conditionalFields = $db->loadColumn();

		return $conditionalFields;
	}

	/**
	 * Get conditions.
	 *
	 * @param   int  $id  id.
	 * 
	 * @return array of option for the particular field
	 */
	public function getConditions($id)
	{
		$db = Factory::getDbo();
		$query	= $db->getQuery(true);
		$query->select($db->qn(array('condition','condition_match','show')));
		$query->from('#__tjfields_fields_conditions');
		$query->where('field_to_show=' . $id);
		$query->where($db->quoteName('state') . ' = ' . $db->quote('1'));
		$db->setQuery($query);
		$conditions = $db->loadObjectList();

		return $conditions;
	}
	
	/**
	 * Get conditional fields data.
	 *
	 * @param   string  $client  client.
	 * 
	 * @return array of option for the particular field
	 */
	public function getConditionalFieldsData($client)
	{
		$db = Factory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('tfc.*,tf.name');
		$query->from('`#__tjfields_fields_conditions` AS tfc');
		$query->join('LEFT', '`#__tjfields_fields` AS tf ON tf.id=tfc.field_to_show');
		$query->where($db->quoteName('tfc.state') . ' = ' . $db->quote('1'));
		$query->where($db->quoteName('tfc.client') . ' = ' . $db->quote($client));
		
		$db->setQuery($query);
		$conditionalFields = $db->loadColumn();

		return $conditionalFields;
	}
}
