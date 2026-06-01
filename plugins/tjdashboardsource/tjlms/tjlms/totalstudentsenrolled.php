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

class TjlmsTotalstudentsenrolledDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_TOTAL_STUDENTS_ENROLLED";

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
			$db    = Factory::getDBO();

			// Get Enrollment Data
			$query = $db->getQuery(true);
			$query->select('COUNT(DISTINCT eu.user_id) as enrolled_student, COUNT(IF(eu.state="0", 1, NULL)) as pending_enrollment');
			$query->from($db->quoteName('#__tjlms_enrolled_users', 'eu'));
			$query->JOIN('LEFT', $db->quoteName('#__tjlms_courses', 'c') . ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('eu.course_id') . ')');
			$query->where($db->quoteName('c.state') . ' = 1 and ' . $db->quoteName('eu.state') . ' = 1');

			$db->setQuery($query);
			$EnrollmentData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$totalStudents = $EnrollmentData['enrolled_student'];

		return $totalStudents;
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
		'title' => '',
		'icon' => 'fa-user'
		];

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
