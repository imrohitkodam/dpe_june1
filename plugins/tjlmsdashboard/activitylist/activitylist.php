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
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjlmsdashboard_activitylist', JPATH_ADMINISTRATOR);

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjlmsdashboardActivitylist extends CMSPlugin
{
	/**
	 * Plugin that supports creating the tjlms dashboard
	 *
	 * @param   string   &$subject  The context of the content being passed to the plugin.
	 * @param   integer  $config    Optional page number. Unused. Defaults to zero.
	 *
	 * @since 1.0.0
	 */

	public function __construct(&$subject, $config)
	{
		$path = JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';

		if (!class_exists('comtjlmstrackingHelper'))
		{
			JLoader::register('comtjlmstrackingHelper', $path);
			JLoader::load('comtjlmstrackingHelper');
		}

		parent::__construct($subject, $config);
	}

	/**
	 * Function to render the whole block
	 *
	 * @param   ARRAY  $plg_data  data to be used to create whole block
	 * @param   ARRAY  $layout    Layout to be used
	 *
	 * @return  complete html.
	 *
	 * @since 1.0.0
	 */
	public function onactivitylistRenderPluginHTML($plg_data, $layout = 'default')
	{
		$comtjlmstrackingHelper = new comtjlmstrackingHelper;
		$yourActivitiesList = $this->getData($plg_data);
		JLoader::register('TjlmsModelActivities', JPATH_SITE . '/components/com_tjlms/models/activities.php');
		$tjlmsModelActivities = new TjlmsModelActivities;
		$totalActivities = $tjlmsModelActivities->getTotal();

		$comtjlmsHelper = new comtjlmsHelper;

		$myActivitiesLink = 'index.php?option=com_tjlms&view=activities&layout=default';
		$this->myActivitiesLink = $comtjlmsHelper->tjlmsRoute($myActivitiesLink);

		// Get plugin params
		$plg_data->number_of_activities = $this->params->get('number_of_activities');
		$dash_icons_path = Uri::root(true) . '/media/com_tjlms/images/default/icons/';

		// Load the layout & push variables
		ob_start();
		$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, $this->params->get('layout', $layout));
		include $layout;

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Function to get data of the whole block
	 *
	 * @param   ARRAY  $plg_data  data to be used to create whole block
	 *
	 * @return  data.
	 *
	 * @since 1.0.0
	 */
	public function getData($plg_data)
	{
		$number_of_activities = $this->params->get('number_of_activities');
		$comtjlmsHelper = new comtjlmsHelper;

		$coursesLink = 'index.php?option=com_tjlms&view=courses';
		$this->coursesItemid = $comtjlmsHelper->getitemid($coursesLink);

		$db    = Factory::getDBO();
		$query  = $db->getQuery(true);
		$query->select('a.*, c.title');
		$query->from('#__tjlms_activities as a');
		$query->join('INNER', '#__tjlms_courses as c ON c.id=a.parent_id');
		$query->where('c.state=1');
		$query->where('a.actor_id=' . $plg_data->user_id . ' AND a.parent_id<>0 ORDER BY id DESC LIMIT 0,' . $number_of_activities);

		$db->setQuery($query);

		$yourActivitiesList = $db->loadObjectlist();

		if (Factory::getUser()->id == $plg_data->user_id)
		{
			$user_name = Text::_('PLG_TJLMSDASHBOARD_ACTIVITYLIST_YOU');
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

			$yourActivitiesList[$index]->activityText = $text_to_show;
		}

		return $yourActivitiesList;
	}
}
