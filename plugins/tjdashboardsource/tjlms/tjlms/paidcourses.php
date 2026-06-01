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

class TjlmsPaidcoursesDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_PAID_COURSES";

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
			// Get course Data
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('COUNT(c.id) as total_course, COUNT(IF(c.type="1", 1, NULL)) as paid_courses , COUNT(IF(c.type="0", 1, NULL)) as free_course');
			$query->from($db->quoteName('#__tjlms_courses', 'c'));
			$query->JOIN('INNER', $db->quoteName('#__categories', 'cat') . ' ON (' . $db->quoteName('cat.id') . ' = ' . $db->quoteName('c.catid') . ')');
			$query->where($db->quoteName('cat.published') . ' <> -2');
			$query->where($db->quoteName('c.state') . ' = 1');

			$db->setQuery($query);
			$courseData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$totalPaidcourse = $courseData['paid_courses'];

		return $totalPaidcourse;
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
		$items['data'] = ['count' => $this->getData(),'title' => '','icon' => 'fa  fa-4x fa-credit-card'];

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
		$items['data'] = ['count' => $this->getData(),'title' => ''];

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
