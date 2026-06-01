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

class TjlmsLatestCoursesDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_LATEST_COURSES";
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
			// @Todo This can be come throught plugins params
			$no_of_courses = 5;

			$db    = Factory::getDBO();
			$query = $db->getQuery(true);

			$query->select(array('id', 'title', 'alias'));
			$query->from($db->qn('#__tjlms_courses'));

			$query->where($db->qn('state') . '=1');
			$query->order('id DESC');

			$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

			if (!class_exists('ComtjlmsHelper'))
			{
				JLoader::register('ComtjlmsHelper', $path);
				JLoader::load('ComtjlmsHelper');
			}

			$comTjlmsHelper = new ComtjlmsHelper;

			$db->setQuery($query);

			$query->setLimit($no_of_courses);

			// Set the query for execution.
			$db->setQuery($query);
			$courseInfo = $db->loadObjectList();
			$record = [];

			foreach ($courseInfo as $key => $value)
			{
				$record_data = new stdclass;
				$record_data->id = $value->id;
				$record_data->title = "<a href='"
				. $comTjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $value->id) . "'>" . $value->title . "</a>";

				$record[] = $record_data;
			}
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $record;
	}

	/**
	 * Get Data for Plain Html bar
	 *
	 * @return string dataArray
	 *
	 * @since   1.0
	 * */
	public function getDataTabulatorTjdashtable()
	{
		$items = [];
		$items['data'] = $this->getData();
		$items['columns'] = [
								["title" => 'Courses', "field" => 'title',"formatter" => 'html'],
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
		return array('tabulator.tjdashtable' => "PLG_TJDASHBOARDRENDERER_TABULATOR");
	}
}
