<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;

$lang = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_tjlms', JPATH_ADMINISTRATOR);

/**
 * TjLms plugin for shika
 *
 * @since  1.3.30
 */

class TjlmsHourspentDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_HOUR_SPENT";

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

			$query->select('SUM(TIME_TO_SEC(time_spent)) as spent_time, DATE(timeend) as dates');
			$query->from($db->qn('#__tjlms_lesson_track'));
			$query->where("DATE(timeend) BETWEEN DATE(" . $db->quote($startDate) . ") AND DATE(" . $db->quote($endDate) . " )");
			$query->where($db->quoteName('user_id') . ' = ' . (int) $loggedInUser->id);
			$query->group('YEAR(`timeend`), MONTH(`timeend`)');

			$db->setQuery($query);

			$spentTimeData = $db->loadObjectlist();

			$data     = [];
			$options  = [];
			$totalHrs = 0;

			foreach ($spentTimeData as $value)
			{
				$data['labels'][] = date("M", strtotime($value->dates));
				$val              = number_format((float) ($value->spent_time / 3600), 1, '.', '');
				$options[]        = $val;
				$totalHrs         = $totalHrs + $val;
			}
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$activity = [];
		$data['datasets'][] = ['data' => $options,'label' => 'Hour','borderColor' => "#5AD9BA",'fill' => 'false', 'backgroundColor' => '#5AD9BA'];
		$activity['options']['title'] = ['display' => 'true', 'text' => $totalHrs . ' hrs utilised in last 6 months'];

		$activity['type'] = 'bar';
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
