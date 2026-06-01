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

// R require_once JPATH_COMPONENT . '/helpers/advsearch.php';
// JLoader::import('AdvsearchHelper', JPATH_ROOT . '/components/com_advsearch/helpers/advsearch.php');
JLoader::import('components.com_advsearch.helpers.advsearch', JPATH_ADMINISTRATOR);

/**
 * Class Createsearchmodelindexer model class.
 *
 * @since  1.6
 */
class AdvsearchModelcreateindexer extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  config
	 *
	 * @see        JController
	 * @since    1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id',
				'a.id',
				'client',
				'a.client',
				'content_type',
				'a.content_type',
				'field_code',
				'a.field_code',
				'field_type',
				'a.field_type',
				'ordering',
				'a.ordering',
				'map_table',
				'a.map_table',
				'mapping_field',
				'a.mapping_field',
				'mapping_label',
				'a.mapping_label',
				'options',
				'a.options',
				'category',
				'a.category',
				'published',
				'a.published'

			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   array  $ordering   ordering.
	 * @param   array  $direction  direction.
	 *
	 * @return  model
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
	 * Method to get a store id based on model configuration state
	 *
	 * @param   string  $id  id
	 *
	 * @return  string
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
	 * Method  Build an SQL query to load the list data
	 *
	 * @return  jobject
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*'));
		$query->from('`#__advanced_search_index` AS a');

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
				$db->Quote('%' . $db->escape($search, true) . '%');
			}
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
	 * Method getFields
	 *
	 * @return  jobject
	 *
	 * @since   1.6
	 */
	public function getFields()
	{
		$AdvsearchHelper = new AdvsearchHelper;
		$jinput          = JFactory::getApplication()->input;
		$type            = $jinput->get('type');
		$client          = $jinput->get('client');
		$table           = $client . '_' . $type;
		$db              = JFactory::getDBO();

		$adv_search_table_name = $type;
		$table_string          = $AdvsearchHelper->getTableName($client . '_' . $adv_search_table_name);

		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__advanced_search_indexer');
		$query->where("mapped_table = '$table_string'");

		$db->setQuery($query);
		$result = $db->loadResult();

		return $result;
	}

	/**
	 * Method getPlugins
	 *
	 * @return  string
	 *
	 * @since   1.6
	 */
	public function getPlugins()
	{
		$plg_name = '';
		$disabled = '';
		$jinput   = JFactory::getApplication()->input;

		if ($jinput->get('id'))
		{
			$db    = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->select('client');
			$query->from('#__advanced_search_indexer');
			$query->where('id=' . $jinput->get('id'));
			$db->setQuery($query);
			$plg_name = $db->loadResult();
			$disabled = 'disabled="disabled"';
		}

		$plug_types = JPluginHelper::getPlugin('advsearch');
		$options    = array();
		$options[]  = JHTML::_('select.option', 'Select Client', '0');

		foreach ($plug_types as $key => $val)
		{
			$options[] = JHTML::_('select.option', $val->name, $val->name);
		}

		$dropdown  = JHTML::_('select.genericlist', $options, 'client_name', 'class="required" ' . $disabled . '', 'text', 'value', $plg_name);
		$dropdown1 = '<span style="display:none">' .
		JHTML::_('select.genericlist', $options, 'client_name', 'class="required"', 'text', 'value', $plg_name) . '</span>';
		$jinput    = JFactory::getApplication()->input;

		if ($jinput->get('id'))
		{
			return $dropdown . $dropdown1;
		}
		else
		{
			return $dropdown;
		}
	}

	/**
	 * Method getIndexer
	 *
	 * @param   string  $id  id.
	 *
	 * @return  object
	 *
	 * @since   1.6
	 */
	public function getIndexer($id)
	{
		$db    = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__advanced_search_indexer');
		$query->where('id=' . $id);
		$db->setQuery($query);
		$plg_name = $db->loadObject();

		return $plg_name;
	}

	/**
	 * Method getIndexerFields
	 *
	 * @param   string  $id  id.
	 *
	 * @return  jobject
	 *
	 * @since   1.6
	 */
	public function getIndexerFields($id)
	{
		$db    = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__advanced_search_indexer_fields');
		$query->where('indexer_id=' . $id);
		$db->setQuery($query);
		$plg_name = $db->loadObjectList();

		return $plg_name;
	}

	/**
	 * Method to saveIndexer data
	 *
	 * @return  object
	 *
	 * @since   1.6
	 */
	public function saveData()
	{
		$AdvsearchHelper      = new AdvsearchHelper;
		$jinput               = JFactory::getApplication()->input;
		$post                 = $jinput->getArray();
		$db                   = JFactory::getDBO();
		$componentParams      = JComponentHelper::getParams('com_advsearch');
		$adaptorName          = $componentParams->get('adaptor');
		$Mapping_Extra_Fields = '';

		$field_type = " ";

		$content_type = $post['select_types'];

		// $grid_filter     = $post['grid_filter'];
		// $landing_page    = $post['landing_page'];
		$date = JHtml::date($now = 'UTC', 'Y-m-d H:i:s', false);

		// $category_search = $post['category_search'];

		$Indexer_id = $jinput->get('id');

		if ($Indexer_id)
		{
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__advanced_search_indexer'));
			$query->where($db->quoteName('id') . '=' . $Indexer_id);
			$db->setQuery($query);

			// Delete search indexer's fields
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__advanced_search_indexer'));
			$query->where($db->quoteName('id') . '=' . $Indexer_id);
			$db->setQuery($query);
			$db->query();

			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__advanced_search_indexer_fields'));
			$query->where($db->quoteName('indexer_id') . '=' . $Indexer_id);
			$db->setQuery($query);
			$db->query();
		}

		// Code to insert extra fields into the indexer table starts here
		foreach ($post['field_type'] as $key => $val)
		{
			if ($post['mapping_field'][$key] > 1)
			{
				$client = $post['client_name'];
				JPluginHelper::importPlugin('advsearch', $client);
				$dispatcher           = JDispatcher::getInstance();
				$Mapping_Extra_Fields = $dispatcher->trigger('getMapping' . $post['field_type'][$key], array($post['field_code'][$key],$post['select_types']));
			}

			if ($Mapping_Extra_Fields)
			{
				$Extra_fields = $Mapping_Extra_Fields[0];
			}
		}

		// Data insertion into the advanced_search_indexer starts

		$Indexer               = new stdclass;
		$Indexer->client       = $post['client_name'];
		$Indexer->name         = $post['name-for-type'];
		$Indexer->created_date = $date;
		$Indexer->state        = 1;
		$Indexer->type_name    = $content_type;
		$adv_search_table_name = $content_type;
		$table_string          = $AdvsearchHelper->getTableName($post['client_name'] . '_' . $adv_search_table_name);
		$Indexer->mapped_table = $table_string;
		$Indexer->batch_size   = $post['batch_size'];

		$orderQuery = $db->getQuery(true);
		$orderQuery->select('ordering');
		$orderQuery->from('#__advanced_search_indexer');
		$orderQuery->order('id DESC');
		$db->setQuery($orderQuery);
		$old_order = $db->loadResult();

		$Indexer->ordering = $old_order + 1;

		if (!($db->insertObject('#__advanced_search_indexer', $Indexer, 'id')))
		{
			echo $db->getErrorMsg(false);
		}

		$indexer_id = $db->insertid();

		if (!(empty($Extra_fields)))
		{
			// Data insertion into the advanced_search_indexer  at ends

			foreach ($Extra_fields as $key => $val)
			{
				$Extra_Data                = new stdclass;
				$Extra_Data->field_code    = $key;
				$Extra_Data->field_name    = $val['name'];
				$Extra_Data->field_order   = 1;
				$Extra_Data->mapping_field = 2;
				$Extra_Data->mapping_label = $val['name'];
				$Extra_Data->options       = '';

				$query = "SELECT id FROM #__zoo_category WHERE alias = '$content_type'";
				$db->setQuery($query);
				$get_cat_id = $db->loadResult();

				$Extra_Data->category   = $get_cat_id;
				$Extra_Data->published  = 1;
				$Extra_Data->indexer_id = $indexer_id;

				if ($post['search_term'][$key])
				{
					// Field for Search Term
					$Extra_Data->search_term = 1;
				}

				$extra_fields_array[] = '`' . $key . "` text  NOT NULL";
				$Extra_Data->useas    = $get_cat_id;

				if (!($db->insertObject('#__advanced_search_indexer_fields', $Extra_Data, 'id')))
				{
					echo $db->stderr();
				}
			}
		}

		foreach ($post['mapping_field'] as $key => $val)
		{
			if ($val != 1)
			{
				$Data = new stdclass;

				$Data->field_code    = $post['field_code'][$key];
				$Data->field_name    = $post['field_name'][$key];
				$Data->field_order   = $post['order_term'][$key];
				$Data->mapping_field = $post['mapping_field'][$key];
				$Data->mapping_label = $post['mapping_label'][$key];
				$Data->options       = $post['field_options'][$key];
				$Data->indexer_id    = $indexer_id;
				$Data->useas         = $post['useas'][$key];
				$Data->published     = 1;

				$table_name = $post['field_code'][$key];
				$field_type = AdvsearchHelper::getFieldType($post['mapping_field'][$key]);

				if ($post['basic_search'][$key])
				{
					$tableCols .= $db->quoteName($post['field_name'][$key]) . ' ' . AdvsearchHelper::getFieldType($post['mapping_field'][$key]) . ', ';
					$Data->basic_search = 1;
				}

				if ($post['search_term'][$key])
				{
					$Data->search_term = 1;
				}

				if ($post['grid_filter'][$key])
				{
					$Data->grid_filter = 1;
				}

				if ($post['landing_page'][$key])
				{
					$Data->landing_page = 1;
				}

				if ($post['category_search'][$key])
				{
					$Data->category_search = 1;
				}

				if ($post['display_search'][$key])
				{
					$Data->display_search = 1;
				}

				if ($field_type)
				{
					$new_fields_array[] = '`' . $table_name . "` $field_type  NOT NULL";
				}

				if (!($db->insertObject('#__advanced_search_indexer_fields', $Data, 'id')))
				{
					echo $db->stderr();
				}
			}
		}

			$createTable = "";
			$tableName   = '#__' . AdvsearchHelper::getTableName($post['client_name'] . '_' . $adv_search_table_name);
			$mappedField = substr($tableCols, 0, -2);
			try
			{
				$query = "CREATE TABLE " . $tableName . " (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
						" . $mappedField . ")ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

				$db->setQuery($query);
				$db->query();
			}
			catch(Exception $e)
			{
				$config = array(
					'text_file' => 'createindexer.log'
				);
				jimport('joomla.log.logger.formattedtext');
				$logger = new JLogLoggerFormattedtext($config);
				$msg = JText::sprintf('ERROR_CREATE_TABLE' ,  $e->getMessage());
				$entry = new JLogEntry($msg, JLog::ALL);
				$logger->addEntry($entry);

				JErrorPage::render($e);
			}

		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__advanced_search_indexer_fields'));
		$query->where('grid_filter = 1');
		$db->setQuery($query);
		$gridFilterData = $db->loadObjectList();

		// Define OnAfterIndexSave trigger.
		JPluginHelper::importPlugin('advsearch', $client);
		$dispatcher = JDispatcher::getInstance();
		$dataArray  = $dispatcher->trigger('onAfterIndexSave', array($gridFilterData));

		return;
	}

	/**
	 * Method deleteIndexer
	 *
	 * @return  model
	 *
	 * @since   1.6
	 */
	public function deleteIndexer()
	{
		$input  = JFactory::getApplication()->input;
		$post   = $input->post->getArray();
		$db     = JFactory::getDBO();
		$app    = JFactory::getApplication();
		$prefix = $app->getCfg('dbprefix');

		foreach ($post['cid'] as $key => $val)
		{
			$query = $db->getQuery(true);
			$query->select('mapped_table, id, type_name');
			$query->from($db->quoteName('#__advanced_search_indexer'));
			$query->where($db->quoteName('id') . ' = ' . $val);
			$db->setQuery($query);
			$indexer = $db->loadObject();

			// Delete cronjob entry
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__advanced_search_cronjob'));
			$query->where($db->quoteName('type') . '=' . $db->quote($indexer->type_name));
			$db->setQuery($query);
			$db->execute();

			if (in_array($prefix . $indexer->mapped_table, $db->getTableList()))
			{
				// Drop search indexer's table
				$query = "DROP TABLE " . $db->quoteName('#__' . $indexer->mapped_table);
				$db->setQuery($query);
				$db->query();
			}

			// Delete search indexer's fields
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__advanced_search_indexer_fields'));
			$query->where($db->quoteName('indexer_id') . '=' . $db->quote($indexer->id));
			$db->setQuery($query);
			$db->query();
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__advanced_search_indexer'));
			$query->where($db->quoteName('id') . '=' . $db->quote($indexer->id));
			$db->setQuery($query);
			$db->query();
		}

		return;
	}
}
