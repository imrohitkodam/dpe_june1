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

class TjlmsRecommendedcoursesDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_RECOMMENDED_COURSES";

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
			$user_id = $user->id;
			$comtjlmsHelper = new comtjlmsHelper;

			// Trigger to jlike...to get resommend courses.
			if (ComponentHelper::isEnabled('com_jlike', true))
			{
				$path = JPATH_SITE . '/components/com_jlike/helpers/' . 'main.php';

				if (!class_exists('ComjlikeMainHelper'))
				{
					// Require_once $path;
					JLoader::register('ComjlikeMainHelper', $path);
					JLoader::load('ComjlikeMainHelper');
				}

				$ComjlikeMainHelper = new ComjlikeMainHelper;

				$recommendedcourse = $ComjlikeMainHelper->getElements('recommendToMe', $user_id, 'com_tjlms.course', $noOfCourses);

				$record = array();
				$record['link'] = '';

				if (!empty($recommendedcourse['totalCount']) && $recommendedcourse['totalCount'] > $noOfCourses)
				{
					$viewAll = 'index.php?option=com_tjlms&view=courses&courses_to_show=recommended';
					$record['link'] = $comtjlmsHelper->tjlmsRoute($viewAll, false);
				}

				if (!empty($recommendedcourse['data']))
				{
					foreach ($recommendedcourse['data'] as $eachrecord)
					{
						$eachrecord->content_url = $comtjlmsHelper->tjlmsRoute($eachrecord->content_url, false);
						$userWhoRecommend = Factory::getUser($eachrecord->assigned_by);
						$eachrecord->content_title = "<a href='" . $eachrecord->content_url . "'>" . $eachrecord->content_title . "</a>";
						$eachrecord->name = $userWhoRecommend->name;
						$eachrecord->username = $userWhoRecommend->username;
						$lmsparams = ComponentHelper::getParams('com_tjlms');
						$showNameOrUsername = $lmsparams->get('show_user_or_username', 'name');
						$eachrecord->name = $userName = ($showNameOrUsername == 'name')?$eachrecord->name:$eachrecord->username;

						$profileUrl = $comtjlmsHelper->sociallibraryobj->getProfileUrl($userWhoRecommend);
						$imageToUse = $comtjlmsHelper->sociallibraryobj->getAvatar($userWhoRecommend);
						$profileImage = "<img class=\"media-object img-circle smallcircularimages\" src=\"" . $imageToUse . "\" alt=\"" . $eachrecord->name . "\">";

						if (!empty($profileUrl))
						{
							$userName = "<a class=\"media-left media-middle\" href=" . $profileUrl . " style=\"cursor:pointer\">"
											. $eachrecord->name .
										"</a>";
							$profileImage =	"<a class=\"media-left media-middle\" href=" . $profileUrl . " style=\"cursor:pointer\">
												<img class=\"media-object img-circle smallcircularimages\" src=\"" . $imageToUse . "\" alt=\"" . $eachrecord->name . "\">
											 </a>";
						}

						$link = "<div class=\"\">
									<div class=\"media  row\">
										<div class=\"pull-left col-xs-12\">"
												. $profileImage .
										"</div>
										<div class=\"col-xs-12 \" title=\"" . $eachrecord->name . "\">"
												. $userName . "
										</div>
									</div>
								</div>
							";

						$eachrecord->userWhoRecommendavatar = $link;
					}

					$record['data'] = $recommendedcourse['data'];
				}

				return $record;
			}
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}
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

		$items['data'] = (!empty($data['data'])?$data['data']:'');
		$items['columns'] = array(
								array("title" => 'Course Title', "field" => 'content_title',"formatter" => 'html',"headerSort" => false),
								array("title" => 'Recommended By', "field" => 'userWhoRecommendavatar' ,"formatter" => 'html',"headerSort" => false)
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
