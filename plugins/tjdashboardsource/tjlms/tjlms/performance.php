<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

$lang = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_tjlms', JPATH_ADMINISTRATOR);

/**
 * TjLms plugin for shika
 *
 * @since  1.3.30
 */

class TjlmsPerformanceDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_PERFORMANCE";

	/**
	 * Function to get data of the whole block
	 *
	 * @return Array data.
	 *
	 * @since 1.0.0
	 */
	public function getData()
	{
		try
		{
			$loggedInUser = Factory::getUser();
			$startDate    = new Date('now -6 month');
			$now          = new Date('now');
			$endDate      = $now->format(Text::_('DATE_FORMAT_LC4'));

			$start     = new Date($startDate);
			$start->modify('first day of this month');
			$startDate = $start->format('Y-m-d');

			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select('COUNT(IF(actor_id = "' . $loggedInUser->id . '", 1, NULL)) as user_activity');
			$query->select('COUNT(IF(actor_id <> "' . $loggedInUser->id . '", 1, NULL)) as all_users_activity');
			$query->select('DATE(added_time) as dates');
			$query->select("COUNT(DISTINCT actor_id) as cnt_actor");
			$query->from($db->qn('#__tjlms_activities'));
			$query->where("DATE(added_time) BETWEEN DATE(" . $db->quote($startDate) . ") AND DATE(" . $db->quote($endDate) . " )");
			$query->group('YEAR(`added_time`), MONTH(`added_time`)');

			$db->setQuery($query);

			$spentTimeData = $db->loadObjectlist();

			$topPerformersMessage = '';

			if ($loggedInUser->id)
			{
				$startDate = new Date('now -1 month');
				$now       = new Date('now');
				$endDate   = $now->format(Text::_('DATE_FORMAT_LC4'));

				$start     = new Date($startDate);
				$start->modify('first day of this month');
				$startDate = $start->format('Y-m-d');

				JLoader::import('components.com_users.models.users', JPATH_ADMINISTRATOR);
				$usersModel = BaseDatabaseModel::getInstance('Users', 'UsersModel', array('ignore_request' => true));

				// Filter state is used to check user block or not
				$usersModel->setState('filter.state', 0);
				$userCount = $usersModel->getTotal();

				$userLimit = round(($userCount / 100) * 10);

				$db       = Factory::getDbo();
				$subQuery = $db->getQuery(true);
				$subQuery->select('COUNT(*) as user_activity');
				$subQuery->select('actor_id');
				$subQuery->from($db->qn('#__tjlms_activities'));
				$subQuery->where("DATE(added_time) BETWEEN DATE(" . $db->quote($startDate) . ") AND DATE(" . $db->quote($endDate) . " )");
				$subQuery->group('actor_id');
				$subQuery->order(('user_activity DESC'));
				$db->setQuery($subQuery);
				$subQuery->setLimit($userLimit);

				$topPerformersData = $db->loadAssocList();

				if (!empty($topPerformersData) && in_array($loggedInUser->id, array_column($topPerformersData, 'actor_id')))
				{
					$topPerformersMessage = 'You are one of our Top Performers';
				}
			}

			$data     = [];
			$option1  = [];
			$option2  = [];

			foreach ($spentTimeData as $value)
			{
				$data['labels'][] = date("M", strtotime($value->dates));
				$option1[]        = $value->user_activity;
				$option2[]        = number_format((float) ($value->all_users_activity / $value->cnt_actor), 1, '.', '');
			}
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$data['datasets'][] = ['data' => $option1,'label' => 'User','borderColor' => "#3FBFD5", 'backgroundColor' => '#3FBFD5'];
		$data['datasets'][] = ['data' => $option2,'label' => 'Organization','borderColor' => "#75779D", 'backgroundColor' => '#75779D'];

		$activity = [];
		$activity['options']['title'] = ['display' => 'true', 'text' => ['Activities in last 6 months', $topPerformersMessage]];

		$activity['type'] = 'line';
		$activity['data'] = $data;

		return $activity;
	}

	/**
	 * Get Data for Plain Html bar
	 *
	 * @return string dataArray json object
	 *
	 * @since   1.0
	 * */
	public function getDataChartjsTjdashgraph()
	{
		$items = [];
		$items['data'] = $this->getData();

		return json_encode($items);
	}

	/**
	 * Get supported Renderers List
	 *
	 * @return array supported renderers for this data source
	 *
	 * @since   1.0
	 * */
	public function getSupportedRenderers()
	{
		return array('chartjs.tjdashgraph' => Text::_('PLG_TJDASHBOARDRENDERER_CHARTS'));
	}
}
