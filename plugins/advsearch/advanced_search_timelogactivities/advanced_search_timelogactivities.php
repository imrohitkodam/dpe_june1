<?php
/**
 * @package     DPE
 * @subpackage  PlgAdvsearchAdvanced_Search_User
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');
Use Joomla\CMS\Plugin\CMSPlugin;
Use Joomla\CMS\Factory;
Use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Class PlgAdvanced_search_timelogactivities.
 *
 * @since  3.3
 */
class PlgAdvsearchAdvanced_Search_Timelogactivities extends CMSPlugin
{
	/**
	 * Get most of the info of params
	 *
	 * @param   object  &$subject  Type of Indexer.
	 * @param   array   $config    Mapped fields array of indexer.
	 *
	 * @since   1.0.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->_plugin = PluginHelper::getPlugin('advsearch', 'advanced_search_timelogactivities');
		$this->supported_fields = array('text','radio', 'itemname', 'select','textarea', 'preprogram', 'date');
	}

	/**
	 * Method to list of 'types' for this client
	 *
	 * @return array
	 */
	public function getTypeAdv()
	{
		// Get all types.
		$types = array();

		$type        = new stdclass;
		$type->name  = "timelogactivities";
		$type->alias = "timelogactivities";

		$types[] = $type;

		return $types;
	}

	/**
	 * Method to list of actual form fields of  types requested.
	 *
	 * @param   String  $type  Name of  type.
	 *
	 * @return  Array   containing fields name, type, option.
	 */

	public function getFields($type)
	{
		$fieldsArray = array();

		if ($type == '' || $type == '0' || $type == 'undefined')
		{
			return;
		}

		if ($type == "timelogactivities")
		{
			$elements = $this->getTableColumns($type);

			foreach ($elements as $k => $ele)
			{
				$fieldsArray[$k] = array('name' => $ele->name, 'type' => $ele->type, 'label' => $ele->label, 'option' => '');
			}
		}

		return $fieldsArray;
	}

	/**
	 * Method to get param table names
	 *
	 * @param   string  $types  table name
	 *
	 * @return  array
	 *
	 * @since  1.6
	 */
	private static function getTableColumns($types)
	{
		$fieldDataValue = array();

		$fieldDataValue[0]->name = "state";
		$fieldDataValue[0]->label = "state";
		$fieldDataValue[0]->type = "text";
		$fieldDataValue[0]->value = "";

		$fieldDataValue[1]->name = "spent_time";
		$fieldDataValue[1]->label = "Spent Time";
		$fieldDataValue[1]->type = "text";
		$fieldDataValue[1]->value = "";

		$fieldDataValue[2]->name = "start_timelog_date";
		$fieldDataValue[2]->label = "Start Timelog Date";
		$fieldDataValue[2]->type = "text";
		$fieldDataValue[2]->value = "";

		$fieldDataValue[3]->name = "latest_timelog_date";
		$fieldDataValue[3]->label = "Latest Timelog Date";
		$fieldDataValue[3]->type = "text";
		$fieldDataValue[3]->value = "";

		return $fieldDataValue;
	}

	/**
	 * Method to returns records information to the cronjob based on passed parameters
	 *
	 * @param   String   $indexerType     Type of Indexer.
	 * @param   Array    $fieldArray      Mapped fields array of indexer.
	 * @param   Date     $endDate         End date of records added.
	 * @param   Integer  $limit           Start limit of the records
	 * @param   Integer  $paramCronLimit  Param limit of the indexers.
	 * @param   Integer  $recordId        Hold recordId of user data.
	 * @param   Array    $validData       Array of updated users data.
	 *
	 * @return  Array containing record-id and element-id of last related item
	 *
	 * @since   1.0.0
	 */
	public function getData($indexerType, $fieldArray, $endDate, $limit, $paramCronLimit, $recordId, $validData)
	{
		$fieldDataValue = array();
		$fieldDatavalues = "";

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$subQuery = $db->getQuery(true);
		$subQuery->select('TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(timelog))), "%H hr %i min" )');
		$subQuery->from($db->quoteName('#__timelog_activities', 'tl'));
		$subQuery->join('INNER', $db->qn('#__tj_sla_activities', 'sa') . ' ON (' . $db->qn('tl.client_id') . ' = ' . $db->qn('sa.id') . ')');
		$subQuery->where($db->quoteName('sa.cluster_id') . ' = ' . $db->qn('cl.id'));

		// Get Start date of log time by using min
		$subQuery1 = $db->getQuery(true);
		$subQuery1->select('MIN(tl.created_date)');
		$subQuery1->from($db->quoteName('#__timelog_activities', 'tl'));
		$subQuery1->join('INNER', $db->qn('#__tj_sla_activities', 'sa') . ' ON (' . $db->qn('tl.client_id') . ' = ' . $db->qn('sa.id') . ')');
		$subQuery1->where($db->quoteName('sa.cluster_id') . ' = ' . $db->qn('cl.id'));

		// Get latest date of log time by using max
		$subQuery2 = $db->getQuery(true);
		$subQuery2->select('MAX(tl.created_date)');
		$subQuery2->from($db->quoteName('#__timelog_activities', 'tl'));
		$subQuery2->join('INNER', $db->qn('#__tj_sla_activities', 'sa') . ' ON (' . $db->qn('tl.client_id') . ' = ' . $db->qn('sa.id') . ')');
		$subQuery2->where($db->quoteName('sa.cluster_id') . ' = ' . $db->qn('cl.id'));

		$query->select(array('a.id, a.state'));
		$query->select('(' . $subQuery . ') AS spent_time');
		$query->select('(' . $subQuery1 . ') AS start_timelog_date');
		$query->select('(' . $subQuery2 . ') AS latest_timelog_date');

		$query->from($db->quoteName('#__tjmultiagency_multiagency', 'a'));
		$query->join('LEFT', $db->qn('#__tj_clusters', 'cl') . ' ON (' . $db->qn('cl.client_id') . ' = ' . $db->qn('a.id') . ')');
		$query->join('LEFT', $db->qn('#__tjmultiagency_licences', 'l') . ' ON (' . $db->qn('l.multiagency_id') . ' = ' . $db->qn('a.id') . ')');

		if ($recordId)
		{
			$query->where($db->quoteName('a.id') . ' = ' . (int) $recordId);
		}
		else
		{
			$query->order($db->quoteName('a.id') . " LIMIT " . (int) $limit . "," . (int) $paramCronLimit);
		}

		$db->setQuery($query);
		$fieldDatavalues = $db->loadObjectlist();

		return $fieldDatavalues;
	}
}
