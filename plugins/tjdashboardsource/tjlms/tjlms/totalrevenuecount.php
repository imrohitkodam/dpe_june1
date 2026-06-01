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
use Joomla\CMS\Component\ComponentHelper;
$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_tjlms', JPATH_ADMINISTRATOR);

/**
 * TjLms plugin for shika
 *
 * @since  1.0.0
 */

class TjlmsTotalrevenuecountDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_TOTAL_REVENUE_COUNT";

	/**
	 * Function to get data of the whole block
	 *
	 * @return string data.
	 *
	 * @since 1.0.0
	 */
	public function getData()
	{
		try
		{
			// Get order and revenue data
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('COUNT(o.id) as orders, SUM(o.amount) as amount');
			$query->from($db->quoteName('#__tjlms_orders', 'o'));
			$query->join('LEFT', $db->quoteName('#__tjlms_courses', 'c') . ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('o.course_id') . ')');
			$query->where($db->quoteName('o.status') . '="C"');

			$db->setQuery($query);
			$OrderData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$totalRevenueAmount = ($OrderData['amount'])?$OrderData['amount']:0;

		if ($totalRevenueAmount < 1000000)
		{
			// Anything less than a million
			$totalRevenueAmount = $totalRevenueAmount;
		}
		elseif ($totalRevenueAmount < 1000000000)
		{
			// Anything less than a billion
			$totalRevenueAmount = number_format($totalRevenueAmount / 1000000, 2) . 'M';
		}
		else
		{
			// At least a billion
			$totalRevenueAmount = number_format($totalRevenueAmount / 1000000000, 2) . 'B';
		}

		$params = ComponentHelper::getParams('com_tjlms');
		$currency  = $params->get('currency_symbol');

		return $currency . $totalRevenueAmount;
	}

	/**
	 * Get Data for Tabulator Table
	 *
	 * @return string dataArray
	 *
	 * @since   1.0
	 * */
	public function getDataNumbercardboxTjdashnumbercardbox()
	{
		$items = [];
		$items['data'] = ['count' => $this->getData(),
		'title' => '','icon' => 'fa-money'];

		return json_encode($items);
	}

	/**
	 * Get Data for Tabulator Table
	 *
	 * @return string dataArray
	 *
	 * @since   1.0
	 * */
	public function getDataCountboxTjdashcount()
	{
		$items = [];
		$items['data'] = ['count' => $this->getData(),
		'title' => ''];

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
		return array('countbox.tjdashcount' => "PLG_TJDASHBOARDRENDERER_COUNTBOX",
			'numbercardbox.tjdashnumbercardbox' => "PLG_TJDASHBOARDRENDERER_NUMBERCARDBOX");
	}
}
