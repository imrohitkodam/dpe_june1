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

class TjlmsLikedcoursesDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_LIKED_COURSES";

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
			$noOfCourses = 5;
			$user = Factory::getUser();
			$olUserid = $user->id;

			$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

			if (!class_exists('ComtjlmsHelper'))
			{
				// Require_once $path;
				JLoader::register('ComtjlmsHelper', $path);
				JLoader::load('ComtjlmsHelper');
			}

			$comtjlmsHelper = new ComtjlmsHelper;

			$db    = Factory::getDBO();
			$query  = $db->getQuery(true);
			$query->select('c.title,c.url,course.image,course.id');
			$query->from('#__jlike_content as c');
			$query->join('INNER', '#__jlike_likes as l ON l.content_id=c.id');
			$query->join('INNER', '#__tjlms_courses as course ON course.id=c.element_id');
			$query->leftjoin('`#__categories` as cat ON cat.id = course.catid');
			$query->where('cat.published=1');
			$query->where('c.element="com_tjlms.course" AND course.state=1 AND l.like=1 AND l.userid=' . $olUserid);
			$db->setQuery($query);

			$db->setQuery($query);
			$db->execute();

			// Get total number of rows
			$totalRows = $db->getNumRows();

			$query->setLimit($noOfCourses);

			// Set the query for execution.
			$db->setQuery($query);
			$yourLike = $db->loadObjectlist();

			$record = array();
			$record['link'] = '';

			if ($totalRows > $noOfCourses)
			{
				$likeCoursesLink = 'index.php?option=com_tjlms&view=courses&courses_to_show=liked';
				$record['link'] = $comtjlmsHelper->tjlmsRoute($likeCoursesLink, false);
			}

			foreach ($yourLike as $k => $like)
			{
				$yourLike[$k]->title = "<a href=" . $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $like->id, false) . ">"
										. $like->title . "</a>";
			}

			$record['data'] = $yourLike;
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $record;
	}

	/**
	 * Get Data for Tabulator Table
	 *
	 * @return string dataArray
	 *
	 * @since   1.0
	 * */
	public function getDataTabulatorTjdashtable()
	{
		$items = [];

		$data  = $this->getData();

		$items['data']    = (!empty($data['data'])?$data['data']:'');
		$items['columns'] = array(
								array("title" => 'Course Title',"field" => 'title',"formatter" => 'html'),
								);

		if (!empty($data['link']))
		{
			$items['links'][] = ["title" => 'View All', "link" => $data['link']];
		}

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
