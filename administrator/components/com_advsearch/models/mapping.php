<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_Advsearch
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');
/**
 * Class modelMapping
 *
 * @since  3.3
 */
class AdvsearchModelmapping extends JModelList
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
				'mapping_label',
				'a.mapping_label',
				'primary_index_id',
				'a.primary_index_id',
				'primary_field_id',
				'a.primary_field_id'
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
		$app = JFactory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_advsearch');
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
	 * @return    string        A store id.
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
	 * @return  JDatabaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*'));
		$query->from('`#__tjrecommendations_mapping` AS a');

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

				$query->where("a.mapping_name LIKE $search");
			}
		}

		$query->group($db->escape('mapping_name'));

		return $query;
	}

	/**
	 * Delete selected mapping details
	 *
	 * @return  void
	 *
	 * @since    1.6
	 */
	public function deleteMapping()
	{
		$input = JFactory::getApplication()->input;
		$post  = $input->post->getArray();
		$db    = JFactory::getDBO();

		foreach ($post['cid'] as $key => $val)
		{
			// Delete search indexer's fields
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjrecommendations_mapping'));
			$query->where($db->quoteName('id') . '=' . $db->quote($val));
			$db->setQuery($query);
			$db->query();

			// Delete search indexer's fields
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjrecommendations_mapping_xref'));
			$query->where($db->quoteName('mapping_id') . '=' . $db->quote($val));
			$db->setQuery($query);
			$db->query();
		}

		return;
	}

	/**
	 * Method array to object
	 *
	 * @param   array  $array  array of object.
	 *
	 * @return  object
	 *
	 * @since   1.6
	 */
	public function array_to_object($array)
	{
		$obj = new stdClass;

		foreach ($array as $k => $v)
		{
			if (is_array($v))
			{
				$obj->{$k} = array_to_object($v);
			}
			else
			{
				$obj->{$k} = $v;
			}
		}

		return $obj;
	}

	/**
	 * Method of cronjob
	 *
	 * @return  data
	 *
	 * @since   1.6
	 */
	public function cronjob()
	{
		$date = & JFactory::getDate();
		$db = & JFactory::getDBO();
		$type = '';
		$fields = '';
		$client = '';

		// Get search indexers list
		$query = "SELECT map_table FROM #__advanced_search_index GROUP BY map_table";
		$db->setQuery($query);
		$search_indexer = $db->loadObjectList();

		// Insert each record of each indexer one bye one
		foreach ($search_indexer as $key => $val)
		{
			$query = "SELECT * FROM #__advanced_search_index WHERE map_table = '$val->map_table'";
			$db->setQuery($query);
			$indexer_records = $db->loadObjectList();

			foreach ($indexer_records as $k => $v)
			{
				if ($v->content_type)
				{
					$type = $v->content_type;
				}

				if ($v->field_code)
				{
					$fields[] = $v->field_code;
				}

				if ($v->client)
				{
					$client = $v->client;
				}
			}

			$query = "SELECT end_date FROM #__advanced_search_cronjob WHERE type = '$type' ORDER BY id DESC";
			$db->setQuery($query);
			$end_date = $db->loadResult();

			if ($end_date == 0)
			{
				$end_date = "0000-00-00 00:00:00";
			}

			$end_date = "0000-00-00 00:00:00";

			$myquery = "SELECT * FROM #__advanced_search_cronjob WHERE `type` LIKE '$type'
							ORDER BY id DESC";
			$db->setQuery($myquery);
			$cron_obj = $db->loadObject();

			if ($cron_obj->limit)
			{
				$limit = $cron_obj->limit;
			}
			else
			{
				$limit = 0;
			}

			echo '<br />Cronj for ' . $type;
			$adv_search_table_name = $type;
			$table_name            = '#__' . $client . '_' . $adv_search_table_name;
			JPluginHelper::importPlugin('advsearch', $client);
			$dispatcher =& JDispatcher::getInstance();
			$field_data = $dispatcher->trigger('getData', array($type,$fields,$end_date,$limit));
			$mycount    = count($field_data[0]);

			// Store cronjob details code starts

			if ($limit == 0)
			{
				$cron             = new stdclass;
				$cron->start_date = $date->toFormat();
				$cron->end_date   = $date->toFormat();
				$cron->limit      = $limit;
				$cron->type       = $type;
				$db->insertObject('#__advanced_search_cronjob', $cron, 'id');
			}

			if ($end_date != "0000-00-00 00:00:00")
			{
				foreach ($field_data[0] as $key => $val)
				{
					$val['created'] = $date->toFormat();
					$val['updated'] = $date->toFormat();
					$final_array    = $this->array_to_object($val);

					$query = "SELECT id FROM `$table_name` WHERE record_id = " . $val['record_id'];
					$db->setQuery($query);
					$id = $db->loadResult();

					if ($id)
					{
						$query = "DELETE FROM `$table_name` WHERE id = " . $id;
						$db->setQuery($query);
						$db->query();
						$db->insertObject($table_name, $final_array, 'id');
					}
					else
					{
						$db->insertObject($table_name, $final_array, 'id');
					}
				}
			}
			else
			{
				foreach ($field_data[0] as $key1 => $val1)
				{
					$val1['created'] = $date->toFormat();
					$val1['updated'] = $date->toFormat();
					$final_array    = $this->array_to_object($val1);

					if (!$db->insertObject($table_name, $final_array, 'id'))
					{
						echo $db->stderr();
					}
				}
			}

			// Check the search term fields & put such fields records into the search term table
			$search_term = array();

			foreach ($indexer_records as $k => $v)
			{
				foreach ($field_data[0][0] as $key => $val)
				{
					if ($key == $v->field_code && $v->search_term == 1)
					{
						$search_term[] = $key;
					}
				}
			}

			foreach ($field_data[0] as $key => $val)
			{
				foreach ($search_term as $field_code)
				{
					$term        = new stdclass;
					$term->term  = $val[$field_code];
					$term->sound = soundex($val[$field_code]);
					$db->insertObject('#__advanced_search_term', $term, 'id');
				}
			}

			$croj             = new stdclass;
			$croj->start_date = $date->toFormat();
			$croj->end_date   = $date->toFormat();
			$croj->limit      = count($field_data[0]) + $cron_obj->limit;
			$croj->type       = $type;

			if (!$db->insertObject('#__advanced_search_cronjob', $croj, 'id'))
			{
				echo $db->stderr();
			}

			if ($limit == 50)
			{
				$limit = 0;
			}

			echo '<br />No of Records ' . $mycount;
		}
	}
}
