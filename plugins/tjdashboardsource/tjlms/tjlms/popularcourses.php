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
use Joomla\CMS\Access\Access;
use Joomla\CMS\Uri\Uri;
$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_tjlms', JPATH_ADMINISTRATOR);

/**
 * TjLms plugin for shika
 *
 * @since  1.0.0
 */

class TjlmsPopularCoursesDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_POPULAR_COURSES";
	/**
	 * Function to get data of the whole block
	 *
	 * @return Array data.
	 *
	 * @since 1.0.0
	 */
	public function getData()
	{
		// @Todo This can be come throught plugins params
		$user = Factory::getUser();
		$userId = $user->id;
		$isroot = (in_array(1, Access::getGroupsByUser($userId)))?1:'';

		try
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('c.id','c.title', 'l.like_cnt', 'c.image', 'c.storage')));
			$query->from($db->quoteName('#__tjlms_courses', 'c'));
			$query->join('INNER', $db->quoteName('#__jlike_content', 'l') . ' ON (' . $db->quoteName('l.element_id') . ' = ' . $db->quoteName('c.id') . ')');

			if (!$isroot)
			{
				$query->where($db->quoteName('c.created_by') . ' = ' . (int) $userId);
			}

			$query->where(
			$db->quoteName('l.element') . ' ="com_tjlms.course" AND ' . $db->quoteName(
			'l.like_cnt') . ' > 0 AND' . $db->quoteName('c.state') . ' =1 ORDER BY' . $db->quoteName('l.like_cnt') . ' DESC LIMIT 0,4'
			);

			$db->setQuery($query);
			$mostLikedCourses = $db->loadObjectlist();
			$record = [];

			foreach ($mostLikedCourses as $key => $value)
			{
				$courseImage = Uri::root() . "/media/com_tjlms/images/default/course.png";

				if (!empty($value->image))
				{
					$courseImage = Uri::root() . "/media/com_tjlms/images/courses/" . $value->image;
				}

				$recordData = new stdclass;
				$recordData->id = $value->id;

				$recordData->likecount = $value->like_cnt;
				$recordData->title = "<div class=\"\">
											<div class=\"media  row\">
												<div class=\"pull-left col-xs-12\">
													<a href='" . 'index.php?option=com_tjlms&view=modules&course_id=' . $value->id . "'>
															<img class=\"media-object img-circle smallcircularimages\" alt=\"" . $value->title . "\" src=\"" . $courseImage . "\">	
													</a>
												</div>
												<div class=\"col-xs-12\">
													<a class=\"media-left media-middle\"  href='" . 'index.php?option=com_tjlms&view=modules&course_id=' . $value->id . "'>"
															. $value->title .
													"</a>
												</div>
											</div>
										</div>
									";

				$record[] = $recordData;
			}

			return $record;
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
	public function getDataTabulatorTjdashtable()
	{
		$items = [];
		$items['data'] = $this->getData();
		$items['columns'] = [
								["title" => 'Courses', "field" => 'title',"formatter" => 'html',"headerSort" => false],
								["title" => 'No. of Like(s)', "field" => 'likecount',"formatter" => 'html',"headerSort" => false],
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
