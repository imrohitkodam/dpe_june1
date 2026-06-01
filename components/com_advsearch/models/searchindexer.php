<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Advsearch
 * @author     Amol Patil <example@example.com>
 * @copyright  2017 Amol Patil.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\Data\DataObject;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
jimport('joomla.application.component.modellist');

/**
 * Advance search Model searchindexer
 *
 * @since  3.3
 */
class AdvsearchModelSearchindexer extends ListModel
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * This method should only be called once per instantiation and is designed
	 * to be called on the first call to the getState() method unless the model
	 * configuration flag to ignore the request is set.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   12.2
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// List state information
		$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		$this->setState('list.limit', $limit);

		$limitstart = Factory::getApplication()->input->getInt('limitstart', 0);
		$this->setState('list.start', $limitstart);

		if (empty($ordering))
		{
			$ordering = 'a.ordering';
		}

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a DataObjectbaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DataObjectbaseQuery  A DataObjectbaseQuery object to retrieve the data set.
	 *
	 * @since   12.2
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 'a.*'
			)
		);

		$query->from($db->nameQuote('#__advanced_search_index') . ' AS a');

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
			}
		}

		return $query;
	}

	/**
	 * This method calls advsearch zoo plugin & returns the processed data
	 *
	 * @param   string   $tableName  name of the advsearch table like antq, arts.
	 * @param   Integer  $recordID   Hold recordId of user data.
	 * @param   Array    $validData  Array of updated users data.
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function cronjob($tableName = null, $recordID = 0, $validData = array())
	{
		$date 			= Factory::getDate();
		$db 			= Factory::getDBO();
		$input 			= Factory::getApplication()->input;
		$search_indexer = $this->getIndexersData($tableName);
		$fields = array();

		// Get the indexer Data based on the get passed $tableName now.
		if ($search_indexer)
		{
			// Prepare the parameters like fields, type, limit, data to call getData method
			$queryIndexer_Fields = $db->getQuery(true);
			$queryIndexer_Fields->select("*")
								->from("#__advanced_search_indexer_fields")
								->where("indexer_id = " . $search_indexer->id . " AND basic_search = 1");
			$db->setQuery($queryIndexer_Fields);
			$indexer_records = $db->loadObjectList();

			foreach ($indexer_records as $k => $v)
			{
				if ($v->field_code)
				{
					$fields[]	= $v->field_code;
				}
			}

			$type		= $search_indexer->type_name;
			$client 	= $search_indexer->client;

			$queryCron 	= $db->getQuery(true);
			$queryCron->select("*")
					->from("#__advanced_search_cronjob")
					->where("type = " . $db->quote($type))
					->order("#__advanced_search_cronjob.limit DESC");
			$db->setQuery($queryCron);
			$cron_obj 	= $db->loadObject();
			$limit 		= $cron_obj->limit ?: '0';
			$endDate = $cron_obj->end_date ?: '0000-00-00 00:00:00';

			// Get the batch size
			if ($search_indexer->batch_size)
			{
				$paramCronLimit 	= $search_indexer->batch_size;
			}
			else
			{
				$paramCronLimit 	= ComponentHelper::getParams("com_advsearch")->get("global_batch_size") ?: '200';
			}

			// Call Advsearch plugin & get the data items based on passed parameters
			PluginHelper::importPlugin('advsearch', $client);
			

			$field_data = Factory::getApplication()->triggerEvent('getData', array($type, $fields, $endDate, $limit, $paramCronLimit, $recordID, $validData));

			$queryCronLimit = $db->getQuery(true);
			$queryCronLimit->select("*")
					->from("#__advanced_search_cronjob")
					->where("type = " . $db->quote($type));
			$db->setQuery($queryCronLimit);
			$cron_obj = $db->loadObject();

			if ($cron_obj)
			{
				if (!empty($field_data[0]))
				{
					// Just update limit so that we can get next records slot
					$cron_obj->limit += count($field_data[0]);
				}

				$cron_obj->start_date = $date->toSql(true);
				$cron_obj->end_date   = $date->toSql(true);

				if (!$db->updateObject('#__advanced_search_cronjob', $cron_obj, 'id'))
				{
					echo "Cron entry didn't updated. Please check in model file!!";
				}
			}
			else
			{
				$CronData				= new stdclass;
				$CronData->limit		= $paramCronLimit;
				$CronData->type			= $type;
				$CronData->start_date	= $date->tosql(true);
				$CronData->end_date		= $date->tosql(true);

				if (!$db->insertObject('#__advanced_search_cronjob', $CronData, 'id'))
				{
					echo "Cron entry didn't inserted. Please check in model!!";
				}
			}

			// Insert data into Adv search type table.
			if ($search_indexer->mapped_table)
			{
				$recordData = $field_data[0];

				foreach ($recordData as $key => $data)
				{
					if ($data->id)
					{
						$rExists = $db->getQuery(true);
						$rExists->select("id")
								->from('#__' . $search_indexer->mapped_table)
								->where("id = " . $data->id);
						$db->setQuery($rExists);
						$indexer_records = $db->loadResult();

						if ($indexer_records)
						{
							if (!$db->updateObject('#__' . $search_indexer->mapped_table, $data, 'id'))
							{
								echo Text::_('COM_ADVSEARCH_ERROR_DB');
							}
						}
						else
						{
							if (!$db->insertObject('#__' . $search_indexer->mapped_table, $data, 'id'))
							{
								echo Text::_('COM_ADVSEARCH_ERROR_DB');
							}
						}
					}
				}
			}

			if (isset($field_data[0]))
			{
				return $field_data[0];
			}
		}

		// Plug if ends
	}

	/**
	 *  This returns the advanced search indexer information based on passed parameter.
	 *  Now its straight forward query but haven't removed old eles conditions.
	 *  We may extend this in future
	 *
	 * @param   string  $tableName  Name of databse table.
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function getIndexersData($tableName)
	{
		$db 		= Factory::getDBO();
		$input 		= Factory::getApplication()->input;

		$type = $input->get('type', '', 'STRING');
		$plunName = $input->get('plg', '', 'STRING');

		if ($tableName)
		{
			$type = $tableName;
		}

		if ($type)
		{
			$query = $db->getQuery(true);
			$query->select('ar.*');
			$query->from($db->quoteName('#__advanced_search_indexer', 'ar'));
			$query->where($db->quoteName('ar.type_name') . ' = ' . $db->quote($type));
			$db->setQuery($query);
			$search_indexer = $db->loadObject();
		}
		elseif ($plunName)
		{
			$query = $db->getQuery(true);
			$mapped_table = 'advanced_search_' . $plunName;
			$query->select('ar.*');
			$query->from($db->quoteName('#__advanced_search_indexer', 'ar'));
			$query->where($db->quoteName('ar.type_name') . ' = ' . ' LIKE \'mapped_table%\'');

			$db->setQuery($query);
			$search_indexer = $db->loadObjectList();
		}

		return $search_indexer;
	}
}
