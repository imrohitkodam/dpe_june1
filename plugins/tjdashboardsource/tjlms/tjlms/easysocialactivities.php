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

use Joomla\CMS\Factory;

$lang = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_tjlms', JPATH_ADMINISTRATOR);

/**
 * TjLms plugin for shika
 *
 * @since  1.3.30
 */

class TjlmsEasysocialActivitiesDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_ES_ACTIVITIES";

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

			// Get Badges
			$badgesModel = ES::model('Badges');
			$badges      = $badgesModel->getBadges($loggedInUser->id);

			// Generate badges HTML
			$badgeHtml = '';

			if (!empty($badges))
			{
				$badgeHtml .= '<div class="row mb-10">';

				foreach ($badges as $badge)
				{
					$badgeHtml .= '<div class="col-xs-3 mb-15"><img class="media-object" alt="' . $badge->title . '"
					src="' . $badge->avatar . '">

					</div>
					';
				}

				$badgeHtml .= '</div>';

				$badgeHtml .= '<div class="clearfix"></div>';
			}

			// Get Points
			$pointsModel = ES::model('Points');
			$points      = $pointsModel->getPoints($loggedInUser->id);

			// Generate points HTML
			$pointsHtml = '';

			if (!empty($points))
			{
				$pointsHtml = '<span class="font-bold"> ' . $points . ' Points</span>';
				$pointsHtml .= '<br>';
			}

			// Get completed courses
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);

			$query->select('COUNT(ct.id) as total_course, COUNT(IF(ct.status="C", 1, NULL)) as completed_courses');
			$query->from($db->qn('#__tjlms_enrolled_users', 'eu'));
			$query->join('INNER', $db->qn('#__tjlms_courses', 'c') . ' ON (
			' . $db->qn('c.id') . ' = ' . $db->qn('eu.course_id') . ')');
			$query->join('INNER', $db->qn('#__tjlms_course_track', 'ct') . ' ON (
			' . $db->qn('ct.course_id') . ' = ' . $db->qn('eu.course_id') .
			'AND' . $db->qn('ct.user_id') . ' = ' . $db->qn('eu.user_id') . ')');
			$query->where($db->quoteName('ct.user_id') . ' = ' . (int) $loggedInUser->id);
			$query->where($db->quoteName('c.state') . ' = 1');
			$query->where($db->quoteName('eu.state') . ' = 1');
			$db->setQuery($query);
			$courseData = $db->loadAssoc();

			// Generate courses HTML
			$coursesHTML = '';

			if (!empty($courseData['total_course']))
			{
				$coursesHTML .= '<i class="fa fa-graduation-cap" aria-hidden="true"></i>

<span class="font-bold"> ' . $courseData['completed_courses'] . '/' . $courseData['total_course'] . ' Courses </span>completed';
			}
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$returnData = $badgeHtml . $pointsHtml . $coursesHTML;

		if (empty($badgeHtml) && empty($pointsHtml) && empty($coursesHTML))
		{
			$returnData = '<span class="font-bold"> Nothing to display yet.. Start your learning journey & we will show you some cool stats here !</span>';
		}

		return $returnData;
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
		$items['data'] = ['count' => $this->getData()];

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
		return array(
			'countbox.tjdashcount' => "PLG_TJDASHBOARDRENDERER_COUNTBOX"
		);
	}
}
