<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_tjlms', JPATH_ADMINISTRATOR);

/**
 * TjLms plugin for shika
 *
 * @since  1.0.0
 */

class TjlmsActivitiesDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_ACTIVITIES";

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
			$path = JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';

			if (!class_exists('comtjlmstrackingHelper'))
			{
				JLoader::register('comtjlmstrackingHelper', $path);
				JLoader::load('comtjlmstrackingHelper');
			}

			$comtjlmstrackingHelper = new comtjlmstrackingHelper;
			$activityData = [];
			$startDate = new Date('now -1 month');
			$endDate = new Date('now');
			$activityData['start'] = $startDate;
			$activityData['end'] = $endDate;
			$yourActivities      = $comtjlmstrackingHelper->getactivity($activityData);
			$data = [];
			$option1 = [];
			$option2 = [];

			foreach ($yourActivities as $key => $value)
			{
				$data['labels'][] = $value->time;
				$option1[] = $value->session_count;
				$option2[] = $value->activity_count;
			}
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$activity = [];
		$data['datasets'][] = ['data' => $option1,'label' => 'Sessions','borderColor' => "#3e22cd",'fill' => 'false'];
		$data['datasets'][] = ['data' => $option2,'label' => 'Activities','borderColor' => "#3e95cd", 'fill' => 'false'];
		$activity['options']['title'] = [
							'display' => 'true',
							'text' => ''
							];

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
	 * @return array supported renderes for this data source
	 *
	 * @since   1.0
	 * */
	public function getSupportedRenderers()
	{
		return array('chartjs.tjdashgraph' => Text::_('PLG_TJDASHBOARDRENDERER_CHARTS'));
	}
}
