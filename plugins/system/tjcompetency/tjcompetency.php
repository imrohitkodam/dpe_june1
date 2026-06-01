<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

// Load language file for plugin.
$lang = Factory::getLanguage();
$lang->load('com_tjcompetency', JPATH_ADMINISTRATOR);

JLoader::import('components.com_tjcompetency.includes.tjcompetency', JPATH_ADMINISTRATOR);

/**
 * Methods supporting a list of TjCompetency action.
 *
 * @since  1.0.0
 */
class PlgSystemTjCompetency extends JPlugin
{
	/**
	 * Constructor - Function used as a contructor
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An array that holds the plugin configuration
	 *
	 * @retunr  class object
	 *
	 * @since  1.0.0
	 */
	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);
	}

	/**
	 * Function used as a trigger after user complete a course.
	 *
	 * @param   INT  $userId    User to completed the course
	 * @param   INT  $courseId  Course ID
	 * @param   INT  $lessonId  Lesson ID
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterCourseCompletion($userId, $courseId, $lessonId = 0)
	{
		if (!empty($userId) && !empty($courseId))
		{
			$this->skillAllotment('course', $courseId, $userId);
		}
	}

	/**
	 * Call this function after checkin
	 *
	 * @param   ARRAY  $data  checkin array
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAfterEventCheckin($data)
	{
		$userId  = $data['owner_id'];
		$eventId = $data['eventid'];

		if (!empty($data['checkin']) && !empty($eventId) && !empty($userId))
		{
			if (Factory::getUser($userId)->id)
			{
				$this->skillAllotment('event', $eventId, $userId);
			}
		}
	}

	/**
	 * Function used to allot skills to users
	 *
	 * @param   INT  $client    Client
	 * @param   INT  $clientId  Client ID
	 * @param   INT  $userId    User ID
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function skillAllotment($client, $clientId, $userId)
	{
		$skillContentMapsModel = TjCompetency::model('SkillContentMaps', array('ignore_request' => true));
		$skillContentMapsModel->setState('filter.client', $client);
		$skillContentMapsModel->setState('filter.client_id', $clientId);
		$skillContentMapsModel->setState('filter.state', 1);

		$items = $skillContentMapsModel->getItems();

		if (!empty($items))
		{
			foreach ($items as $key => $value)
			{
				if (!empty($value->outcome_rule))
				{
					$model = TjCompetency::model('SkillContentUserMap', array('ignore_request' => true));
					$data  = array ();

					$data['user_id']   = $userId;
					$data['skill_id']  = $value->skill_id;
					$data['scale_id']  = $value->scale_id;
					$data['client']    = $value->client;
					$data['client_id'] = $value->client_id;

					$data['state'] = 0;

					if ($value->outcome_rule == 1)
					{
						// Award the skill
						$data['state'] = 1;
					}
					elseif ($value->outcome_rule == 2)
					{
						// Send for review
						$data['state'] = 3;
					}

					$model->save($data);
				}
			}
		}
	}
}
