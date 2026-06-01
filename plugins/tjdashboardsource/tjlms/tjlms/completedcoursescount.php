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

class TjlmsCompletedcoursescountDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_COMPLETED_COURSES_COUNT";
	/**
	 * Function to get data of the whole block
	 *
	 * @return Array data.
	 *
	 * @since 1.0.0
	 */
	public function getData()
	{
		$totalCompletedCourses = 0;

		try
		{
			$db = Factory::getDbo();
			$user = Factory::getUser();

			// Create a new query object.
			$query = $db->getQuery(true);

			// Select all records from the user profile table where key begins with "custom.".
			// Order it by the ordering field.
			$query->select('COUNT(ct.id) as totalCompletedCourses');
			$query->from($db->quoteName('#__tjlms_course_track') . ' as ct');
			$query->join('INNER', $db->quoteName('#__tjlms_courses') . ' as c ON c.id=ct.course_id');
			$query->join('INNER', $db->quoteName('#__tjlms_enrolled_users', 'eu') . ' ON ((' . $db->qn('ct.course_id') . ' = ' . $db->qn('eu.course_id') . '
						) AND  (' . $db->qn('ct.user_id') . ' = ' . $db->qn('eu.user_id') . '))');
			$query->join('INNER', $db->qn('#__categories', 'cat') . ' ON (' . $db->qn('cat.id') . ' = ' . $db->qn('c.catid') . ')');
			$query->where($db->qn('cat.published') . ' = 1 ');
			$query->where($db->quoteName('ct.user_id') . ' = ' . $user->id);
			$query->where($db->quoteName('ct.status') . ' = "C"');
			$query->where($db->quoteName('c.state') . ' = 1');
			$query->where($db->qn('eu.state') . '=1');

			// Reset the query using our newly populated query object.
			$db->setQuery($query);

			// Load the results as a list of stdClass objects (see later for more options on retrieving data).
			$totalCompletedCourses = $db->loadresult();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $totalCompletedCourses;
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
		return array('countbox.tjdashcount' => "PLG_TJDASHBOARDRENDERER_COUNTBOX");
	}
}
