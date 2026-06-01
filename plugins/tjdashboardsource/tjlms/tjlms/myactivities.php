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
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_tjlms', JPATH_ADMINISTRATOR);

/**
 * TjLms plugin for shika
 *
 * @since  1.0.0
 */

class TjlmsMyactivitiesDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_MY_ACTIVITIES";

	public $coursesItemid;
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
			$techjoomlacommon = new TechjoomlaCommon;
			$comtjlmstrackingHelper = new comtjlmstrackingHelper;
			$lmsparams   = ComponentHelper::getParams('com_tjlms');
			$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

			$user = Factory::getUser();
			$noOfActivities = 5;

			$coursesLink = 'index.php?option=com_tjlms&view=courses';

			$db    = Factory::getDBO();
			$query  = $db->getQuery(true);
			$query->select('a.*, c.title');
			$query->from($db->qn('#__tjlms_activities', 'a'));

			$query->join('INNER',
								$db->qn('#__tjlms_courses', 'c') . 'ON ( ' . $db->qn('c.id') . '=' . $db->qn('a.parent_id') . ')'
						);

			$query->where('c.state=1');
			$query->where('a.actor_id=' . $user->id . ' AND a.parent_id<>0 ORDER BY id DESC');

			$db->setQuery($query);
			$db->execute();

			// Get total number of rows
			$totalRows = $db->getNumRows();

			$query->setLimit($noOfActivities);

			// Set the query for execution.
			$db->setQuery($query);
			$yourActivitiesList = $db->loadObjectlist();

			$comtjlmsHelperPath = JPATH_ROOT . '/components/com_tjlms/helpers/main.php';

			if (!class_exists('comtjlmsHelper'))
			{
				JLoader::register('comtjlmsHelper', $comtjlmsHelperPath);
				JLoader::load('comtjlmsHelper');
			}

			$comtjlmsHelper = new comtjlmsHelper;

			$record = array();
			$record['link'] = '';

			if ($totalRows > $noOfActivities)
			{
				$comtjlmsHelper = new comtjlmsHelper;
				$myActivitiesLink = 'index.php?option=com_tjlms&view=activities&layout=default';
				$record['link'] = $comtjlmsHelper->tjlmsRoute($myActivitiesLink, false);
			}

			if (Factory::getUser()->id == $user->id)
			{
				$user_name = Text::_("PLG_TJDASHBOARDSOURCE_TJLMS_MY_ACTIVITIES_USER_TEXT");
			}
			else
			{
				$user_name = Factory::getUser()->name;
			}

			$text_to_show = '';

			foreach ($yourActivitiesList as $index => $activity)
			{
				switch ($activity->action)
				{
					case "ENROLL":
						$course_link  = "<a href='" . $comtjlmsHelper->tjlmsRoute($activity->element_url) . "'>" . $activity->element . "</a>";
						$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ENROLL', $user_name, $course_link);
						break;
					case "ATTEMPT":
						$lesson_link = "<strong>" . $activity->element . "</strong>";

						$course_url  = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $activity->parent_id);

						$course_link = "<a href='" . $course_url . "'>" . $activity->title . "</a>";

						$params       = json_decode($activity->params);
						$attempt      = $params->attempt;
						$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ATTEMPT', $user_name, $attempt, $lesson_link, $course_link);

						break;
					case "ATTEMPT_END":
						$lesson_link = "<strong>" . $activity->element . "</strong>";

						$course_url  = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $activity->parent_id);
						$course_link = "<a href='" . $course_url . "'>" . $activity->title . "</a>";

						$params       = json_decode($activity->params);
						$attempt      = $params->attempt;
						$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ATTEMPT_END', $user_name, $attempt, $lesson_link, $course_link);

						break;
					case "COURSE_CREATED":
							$course_link  = "<a href='" . $comtjlmsHelper->tjlmsRoute($activity->element_url) . "'>" . $activity->element . "</a>";
							$text_to_show = Text::sprintf('COM_TJLMS_COURSE_CREATED_STREAM', $user_name, $course_link);
						break;
					case "COURSE_RECOMMENDED":
							$params = json_decode($activity->params);
							$text_to_show = '';
							$targetUserName = Text::_('COM_TJLMS_BLOCKED_USER');

							if (User::getTable()->load($params->target_id))
							{
								$targetUser = Factory::getUser($params->target_id);

								if ($targetUser->block == 0 )
								{
									$targetUserName = Factory::getUser($params->target_id)->name;
								}
							}

							if (isset($params->target_id))
							{
								$course_link  = "<a href='" . $comtjlmsHelper->tjlmsRoute($activity->element_url) . "'>" . $activity->element . "</a>";
								$text_to_show = Text::sprintf('COM_TJLMS_ON_RECOMMEND_COURSE_AS_LMS', $user_name, $course_link, $targetUserName);
							}
						break;
					case "COURSE_COMPLETED":
							$course_link  = "<a href='" . $comtjlmsHelper->tjlmsRoute($activity->element_url) . "'>" . $activity->element . "</a>";
							$text_to_show = Text::sprintf('COM_TJLMS_COURSE_COMPLETED_STREAM', $user_name, $course_link);
						break;
					default:
						$text_to_show = '';
						break;
				}

				$localTime = $techjoomlacommon->getDateInLocal($activity->added_time, 0, $date_format_show);
				$timeElapsedString = $comtjlmstrackingHelper->time_elapsed_string($activity->added_time, true);
				$text_to_show = "<span>" . $text_to_show . "</span>
										- <small title=\"" . $localTime . "\"><em>" . $timeElapsedString . "</em></small>
								</span>";

				$yourActivitiesList[$index]->activityText = $text_to_show;
			}

			$record['data'] = $yourActivitiesList;
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

		$data  = $this->getData();

		$items['data'] = (!empty($data['data'])?$data['data']:'');
		$items['columns'] = array(
								array("title" => 'Activity', "field" => 'activityText',"formatter" => 'html'),
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
