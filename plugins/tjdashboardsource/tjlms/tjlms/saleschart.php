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
use Joomla\CMS\Language\Text;
$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_tjlms', JPATH_ADMINISTRATOR);

/**
 * TjLms plugin for shika
 *
 * @since  1.0.0
 */

class TjlmsSaleschartDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_SALES_CHART";
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
			$data = array();
			$startDate = date("Y-m-d", strtotime('now -1 month'));
			$endDate = date("Y-m-d", time());

			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('SUM(o.amount) as amount, DATE(o.cdate) as date');
			$query->from($db->qn('#__tjlms_orders', 'o'));
			$query->join('LEFT', '#__tjlms_courses as c ON c.id = o.course_id');
			$query->where('o.status="C"');

			if (isset($data['course_id']) && $data['course_id'] != '')
			{
				$query->where($db->qn('o.course_id') . '=' . $db->q((int) $data['course_id']));
			}

			if (isset($startDate) && $startDate != '' && isset($data['end']) && $endDate != '')
			{
				$query->where("( o.cdate BETWEEN " . $db->quote($startDate) . " AND " . $db->quote($endDate) . " )");
			}

			$query->group('DATE(o.cdate)');

			$db->setQuery($query);

			$revenueData = $db->loadObjectlist();

			$option1 = [];

			foreach ($revenueData as $key => $value)
			{
				$data['labels'][] = $value->date;
				$option1[] = $value->amount;
			}

			$data['datasets'][] = ['data' => $option1,'label' => 'Sale','borderColor' => "#3e22cd", 'fill' => 'false'];

			$revenueData['type'] = 'line';
			$revenueData['data'] = $data;

			return $revenueData;
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Get Data for Plain Html bar
	 *
	 * @return string dataArray
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
