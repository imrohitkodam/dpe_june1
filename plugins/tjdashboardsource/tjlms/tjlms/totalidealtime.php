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
$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_tjlms', JPATH_ADMINISTRATOR);

/**
 * TjLms plugin for shika
 *
 * @since  1.0.0
 */

class TjlmsTotalidealtimeDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_TOTAL_IDEAL_TIME";
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
			$user = Factory::getUser();

			// Create object of ComtjlmstrackingHelper class.
			$tjlmsTrackingHelperObj = new ComtjlmstrackingHelper;

			// Call getTotalIdealTime function from ComtjlmstrackingHelper class and get the total spent time.
			$totalIdealTime = $tjlmsTrackingHelperObj->getTotalIdealTime($user->id);
			$totalIdealTime = (!empty($totalIdealTime)?$totalIdealTime:'00:00:00');

			return $totalIdealTime;
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
	public function getDataCountboxTjdashcount()
	{
		$items = [];
		$items['data'] = ['count' => $this->getData(),'title' => ''];

		return json_encode($items);
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
		$items['data'] = ['count' => $this->getData(),'title' => '',
		'icon' => 'fa-clock-o'
		];

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
