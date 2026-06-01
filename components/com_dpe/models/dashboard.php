<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Data\DataObject;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Registry\Registry;
use DateTime;
use stdClass;
jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Subusers records.
 *
 * @since  1.6
 */
class DpeModelDashboard extends ListModel
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
		$this->common  = '';

		parent::__construct($config);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   string  $client     Date to be checked
	 * @param   string  $contentId  Date to be checked
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since   1.6
	 */
	public function getBarData($client, $contentId)
	{
		$db    = $this->getDbo();
		$query    = $db->getQuery(true);
		$subquery    = $db->getQuery(true);
		$params = ComponentHelper::getParams('com_dpe');
		$checklistSchool = $params->get('checklistSchool', '0', 'INT');

		$subquery->select('count(*)')
			->from($db->quoteName('#__tjfields_fields'))
			->where($db->quoteName('client') . ' = ' . $db->q($client))
			->where($db->quoteName('type') . ' != "cluster"')
			->where($db->quoteName('state') . ' = ' . $db->q('1'));

		$query->select('sum(case when value = "todo" then 1 else 0 end) todo,
			sum(case when value = "done" then 1 else 0 end) done,
			sum(case when value = "inprogress" then 1 else 0 end) inprogress,(' . $subquery . ') as total')
			->from($db->quoteName('#__tjfields_fields_value'))
			->where($db->quoteName('client') . ' = ' . $db->q($client));

		if ($contentId)
		{
			$query->where($db->quoteName('content_id') . ' = ' . $db->q($contentId));
		}

		$db->setQuery($query);

		$result = $db->loadObject();

		return $result;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   INT  $id  Date to be checked
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since   1.6
	 */
	public function isChecklist($id)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select('t.id')
			->from($db->quoteName('#__tj_ucm_data', 'd'))
			->join('LEFT', $db->quoteName('#__tj_ucm_types', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('d.type_id'));
		$query->where($db->quoteName('t.params') . ' LIKE "%dpe_checklist=1%"')
			->where($db->quoteName('t.state') . ' = ' . $db->q('1'))
			->where($db->quoteName('d.id') . ' = ' . $db->q($id));

		$db->setQuery($query);

		$result = $db->loadObject();

		return $result;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   INT  $client    Date to be checked
	 * @param   INT  $schoolId  Date to be checked
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since   1.6
	 */
	public function checkAllowCount($client, $schoolId)
	{
		$params = ComponentHelper::getParams('com_dpe');

		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select('*')
			->from($db->quoteName('#__tj_ucm_data'));

		if ($client)
		{
			$query->where($db->quoteName('client') . ' = ' . $db->q($client));
		}

		$query->where($db->quoteName('cluster_id') . ' = ' . $db->q($schoolId));

		$db->setQuery($query);

		$result = $db->loadObject();

		return $result;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since   1.6
	 */
	public function getlistData()
	{
		$db    = $this->getDbo();
		$subQuery = $db->getQuery(true);
		$query    = $db->getQuery(true);
		$clusterId = $this->getState('filter.cluster_id');
		$params = ComponentHelper::getParams('com_dpe');
		$checklistSchool = $params->get('checklistSchool', '0', 'INT');

		$subQuery->select('ucm.id')
			->from($db->quoteName('#__tj_ucm_data', 'ucm'))
			->where($db->quoteName('ucm.cluster_id') . ' = ' . $db->q($clusterId));

		$query->select(array('t.id as type_id', 't.title', 'd.modified_date', 'd.client', 'd.id', 't.unique_identifier'))
			->from($db->quoteName('#__tj_ucm_types', 't'))
			->join('LEFT', $db->quoteName('#__tj_ucm_data', 'd') . ' ON ' . $db->quoteName('d.type_id') . ' = ' . $db->quoteName('t.id')
			. ' AND d.id IN (' . $subQuery . ')')
			->where($db->quoteName('t.params') . ' LIKE "%dpe_checklist=1%"')
			->where($db->quoteName('t.state') . ' = ' . $db->q('1'))
			->group($db->quoteName('t.id'))->order('t.ordering ASC');

		$db->setQuery($query);
		$result = $db->loadObjectList();

		return $result;
	}

	/**
	 * Overrides the default function to check Date fields format, identified by
	 * "_dateformat" suffix, and erases the field if it's not correct.
	 *
	 * @return void
	 */
	protected function loadFormData()
	{
		$app              = Factory::getApplication();
		$filters          = $app->getUserState($this->context . '.filter', array());
		$error_dateformat = false;

		foreach ($filters as $key => $value)
		{
			if (strpos($key, '_dateformat') && !empty($value) && $this->isValidDate($value) == null)
			{
				$filters[$key]    = '';
				$error_dateformat = true;
			}
		}

		if ($error_dateformat)
		{
			$app->enqueueMessage(Text::_("COM_MULTIAGENCY_SEARCH_FILTER_DATE_FORMAT"), "warning");
			$app->setUserState($this->context . '.filter', $filters);
		}

		return parent::loadFormData();
	}

	/**
	 * Checks if a given date is valid and in a specified format (YYYY-MM-DD)
	 *
	 * @param   string  $date  Date to be checked
	 *
	 * @return bool
	 */
	private function isValidDate($date)
	{
		$date = str_replace('/', '-', $date);

		return (date_create($date)) ? Factory::getDate($date)->format("Y-m-d") : null;
	}

	/**
	 * Fetch list of clusters from Given Tag Ids
	 *
	 * @param   array  $tagId  Tags id  used to get cluster id.
	 *
	 * @return  array 
	 */
	public function getClusterIdsByTags($tagId)
	{

		if (empty($tagId))
		{
			return false;
		}

		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select('Distinct cluster.id');
		$query->from('`#__tj_clusters` AS cluster');
		$query->join('LEFT', $db->qn('#__contentitem_tag_map', 'tagsMap') . ' ON (' . $db->qn('tagsMap.content_item_id') . ' = ' . $db->qn('cluster.client_id') . ')');
		$query->where($db->quoteName('tagsMap.tag_id') . " IN ( " . implode(',', $tagId ) .')');
		$query->where($db->quoteName('tagsMap.type_alias') . " = 'com_multiagency.multiagency' " );
		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Fetch list of dashboard id 
	 * 	 
	 * @return  object 
	 */
	public  function getDashboardData()
	{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('id');
			$query->from($db->quoteName('#__tjlms_dashboard'));
			$query->order('ordering ASC');
			$db->setQuery($query);
			
			return $db->loadColumn();
	}

	/**
	 * Method to process queued SLA data and save licences.
	 *
	 * @param   array  $data  The queued data.
	 *
	 * @return  void
	 */
	public function saveSlaFromQueue($data)
	{

		// Set the user identity if provided (important for CLI/Queue processing)
		if (!empty($data['user_id']))
		{
			$user = Factory::getUser((int) $data['user_id']);
			Factory::getApplication()->loadIdentity($user);
			Factory::getApplication()->getSession()->set('user', $user);

		}
		$return = true;

		if (empty($data) || empty($data['cluster_ids']))
		{
			return;
		}

		$clusterIds = $data['cluster_ids'];

		// Date calculations preparation
		$tz = Factory::getUser()->getTimezone();
		$dateNow = Factory::getDate('now');
		$currentDate = $dateNow->setTimezone($tz)->format('Y-m-d 00:00:00');

		foreach ($clusterIds as $clusterId)
		{
			// Load a fresh Multiagency LicenceForm Model for each iteration to avoid state pollution
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models', 'MultiagencyModel');
			$model = BaseDatabaseModel::getInstance('LicenceForm', 'MultiagencyModel', array('ignore_request' => true));

			if (!$model)
			{
				continue;
			}

			$itemData = $data;
			$itemData['multiagency_id'] = $clusterId;
			$itemData['created_by'] = $data['user_id'];
			$itemData['modified_by'] = $data['user_id'];

			unset($itemData['tags']);
			unset($itemData['use_tags']);
			unset($itemData['cluster_ids']);
			unset($itemData['user_id']);

			

		

			// Check for existing licences for this cluster
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('end_date'))
				->from($db->quoteName('#__tjmultiagency_licences'))
				->where($db->quoteName('multiagency_id') . ' = ' . (int) $clusterId)
				->where($db->quoteName('end_date') . ' >= ' . $db->quote($currentDate))
				->order($db->quoteName('end_date') . ' DESC')
				->setLimit(1);
			$db->setQuery($query);
			$latestEndDate = $db->loadResult();

			if ($latestEndDate)
			{
				// Calculate duration of the form entry
				$formStart = new DateTime($data['start_date']);
				$formEnd = new DateTime($data['end_date']);
				$diff = $formStart->diff($formEnd);
				
				// New start date is one day after the latest existing licence
				$newStart = new DateTime($latestEndDate);
				$newStart->modify('+1 day');
				$itemData['start_date'] = $newStart->format('d-m-Y');
				
				// New end date maintains the same duration
				$newEnd = clone $newStart;
				$newEnd->add($diff);
				$itemData['end_date'] = $newEnd->format('d-m-Y');
				
				// Compare dates in Y-m-d for reliability
				if ($newStart->format('Y-m-d') > $dateNow->format('Y-m-d'))
				{
					$itemData['state'] = 3; // Upcoming
				}
				else
				{
					$itemData['state'] = 1; // Active
				}
			}
			else
			{
				// Original logic to check start date with current date
				$startDate = Factory::getDate($itemData['start_date'])->toSql();

				if ($startDate > $currentDate)
				{
					$itemData['state'] = 3;
				}
				else
				{
					$itemData['state'] = 1;
				}
			}

			if (!empty($itemData['multiyearlicence']) && !empty($itemData['multiple_count']))
			{
				// Multi-year logic
				if ($itemData['time_measure'] === "week")
				{
					// Convert week into 7 days
					$duration = 'now +' . ($itemData['duration'] * 7) . ' day';
				}
				elseif ($itemData['time_measure'] === "month")
				{
					// Convert into 30 days
					$duration = 'now +' . ($itemData['duration'] * 30) . ' day';
				}
				else
				{
					$duration = 'now +' . $itemData['duration'] . ' ' . $itemData['time_measure'];
				}

				$date = new DateTime($itemData['start_date']);

				// Subtract 1 day from the DateTime object
				$date->modify('-1 day');

				// Format the date to match the format of the datetimeoffset field
				$endDate = $date->format('d-m-Y');

				$licenceLimit = ComponentHelper::getParams('com_multiagency')->get('multliyear_licence_limit', 10);
				$itemData['end_date'] = HTMLHelper::date($endDate . $duration, 'd-m-Y');
				$itemData['multiple_count'] = ($itemData['multiple_count'] > $licenceLimit) ? $licenceLimit : $itemData['multiple_count'];
				
				$licenceIds = array();

				for ($i = 1; $i <= $itemData['multiple_count']; $i++)
				{
					// Save the licence
					$licenceId = $model->save($itemData);
					$licenceIds[] = $licenceId;

					if ($licenceId)
					{
						$licenceTable = $model->getTable();
						$licenceTable->load(array('id' => $licenceId));

						if (property_exists($licenceTable, 'end_date'))
						{
							 $itemData['start_date'] = HTMLHelper::date($licenceTable->end_date . '+1 day', 'd-m-Y');
							 $itemData['end_date']   = HTMLHelper::date($licenceTable->end_date . $duration . '+0 day', 'd-m-Y');
						}

						// State 3 is used for upcoming licence
						$itemData['state'] = 3;

						// First licence is active licence then set first licence id as a parent id of upcoming licences
						$itemData['parent_id'] = $licenceIds[0];
					}
				}
			}
			else
			{
				// Single licence save
				$return = $model->save($itemData);
			}
		}

		// Send notification after batch processing
		if (!empty($data['user_id']))
		{
			// Get Cluster Names for the email table
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('name'))
				->from($db->quoteName('#__tj_clusters'))
				->where($db->quoteName('client_id') . ' IN (' . implode(',', array_map('intval', $data['cluster_ids'])) . ')');
			$db->setQuery($query);
			$clusterNames = $db->loadColumn();

			$clustersTable = '<table border="1" cellpadding="5" style="border-collapse:collapse; width:50%; font-family: sans-serif;">';
			$clustersTable .= '<tr style="background-color: #f2f2f2;"><th>#</th><th>Agency Name</th></tr>';
			foreach ($clusterNames as $index => $name)
			{
				$clustersTable .= '<tr><td>' . ($index + 1) . '</td><td>' . $name . '</td></tr>';
			}
			$clustersTable .= '</table>';

			if ($user->id)
			{
				$recipients = [
					"email" => [
						"to" => [$user->email],
					],
				];

				$key = "notifySlaAssignedByTags";

				$options = new Registry();
				$replacements = new stdClass();
				$replacements->user = new stdClass();
				$replacements->user->name = $user->name;
				$replacements->user->id   = $user->id;
				$replacements->user->clusters_table = $clustersTable;

				jimport('techjoomla.tjnotifications.tjnotifications');

				/** @scrutinizer ignore-call */
				Tjnotifications::send(
					"com_dpe",
					$key,
					$recipients,
					$replacements,
					$options
				);
			}
		}

		return $return;
	}
}
