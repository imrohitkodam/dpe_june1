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
use Joomla\CMS\Component\ComponentHelper;
$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_tjlms', JPATH_ADMINISTRATOR);

/**
 * TjLms plugin for shika
 *
 * @since  1.0.0
 */

class TjlmsPopularstudentsDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_POPULAR_STUDENTS";
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
		$olUserid = $user->id;
		$isroot = (in_array(1, Access::getGroupsByUser($olUserid)))?1:'';
		$helper = new ComtjlmsHelper;

		try
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('eu.user_id,COUNT(*) as enrolledIn, u.name, u.username');
			$query->from($db->quoteName('#__tjlms_enrolled_users', 'eu'));
			$query->join('INNER',  $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('u.id') . ' = ' . $db->quoteName('eu.user_id') . ')');
			$query->join('INNER',  $db->quoteName('#__tjlms_courses', 'c') . ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('eu.course_id') . ')');
			$query->join('INNER', $db->quoteName('#__categories', 'cat') . ' ON (' . $db->quoteName('cat.id') . ' = ' . $db->quoteName('c.catid') . ')');
			$query->where($db->quoteName('eu.state') . ' = 1 AND' . $db->quoteName('c.state') . ' =1 AND' . $db->quoteName('cat.published') . '=1');

			if (!$isroot)
			{
				$query->where($db->quoteName('c.created_by') . ' = ' . (int) $olUserid);
			}

			$query->group($db->quoteName('eu.user_id') . ' ORDER BY enrolledIn DESC LIMIT 0,4');
			$db->setQuery($query);
			$popularStudent = $db->loadobjectlist();
			$record = [];

			foreach ($popularStudent as $key => $value)
			{
				$student = Factory::getUser($value->user_id);
				$profileUrl = $helper->sociallibraryobj->getProfileUrl($student);
				$imageToUse =	$helper->sociallibraryobj->getAvatar($student);
				$lmsparams = ComponentHelper::getParams('com_tjlms');
				$showNameOrUsername = $lmsparams->get('show_user_or_username', 'name');
				$value->name = $userName = ($showNameOrUsername == 'name')?$value->name:$value->username;
				$profileImage = "<img class=\"media-object img-circle smallcircularimages\" src=\"" . $imageToUse . "\" alt=\"" . $value->name . "\">";

				if (!empty($profileUrl))
				{
					$userName = "<a class=\"media-left media-middle\" href=" . $profileUrl . " style=\"cursor:pointer\">"
									. $value->name . "</a>";
					$profileImage =	"<a class=\"media-left media-middle\" href=" . $profileUrl . " style=\"cursor:pointer\">
										<img class=\"media-object img-circle smallcircularimages\" src=\"" . $imageToUse . "\" alt=\"" . $value->name . "\">
									 </a>";
				}

				$recordData = new stdclass;
				$recordData->enrollCount = $value->enrolledIn;
				$recordData->text = "<div class=\"\">
										<div class=\"media  row\">
											<div class=\"pull-left col-xs-12\">"
													. $profileImage .
											"</div>
											<div class=\"col-xs-12 \" title=\"" . $value->name . "\">"
													. $userName . "
											</div>
										</div>
									</div>";

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
								["title" => 'User', "field" => 'text',"formatter" => 'html',"headerSort" => false],
								["title" => 'Enrolled in Courses', "field" => 'enrollCount',"formatter" => 'html',"headerSort" => false]
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
