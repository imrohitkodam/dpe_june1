<?php
/**
 * @package    Shika
 * @author     Techjoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2019. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;

jimport('joomla.application.component.controller');

/**
 * Tjmodules list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerTagreports extends TjlmsController
{
	/**
	 * Main method
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.3.11
	 */
	public function run()
	{
		$db       = Factory::getDBO();
		$jinput   = Factory::getApplication()->input;

		// Get plugin params
		$pluginParams               = $jinput->get('pluginParams', '', 'array');
		$skipEventAsLessonFromShika = isset($pluginParams['skipEventAsLessonFromShika']) ? $pluginParams['skipEventAsLessonFromShika'] : 1;

		$tracking_data_user = $tracking_data_group = array();
		$tags_helper = new TagsHelper;

		$this->truncate_tag_reports();

		// Get all the tracking data from Shika
		$query = "SELECT lesson_id, user_id, time_spent
		FROM #__tjlms_lesson_track";

		// Skip event as lessson from shika time calculations?
		if ($skipEventAsLessonFromShika == 1)
		{
			$query .= " LEFT JOIN #__tjlms_lessons AS l ON l.id = lesson_id
			WHERE l.format != 'event'";
		}

		$db->setQuery($query);
		$tracking_rows = $db->loadObjectList();

		// Calculate per tag per user totals
		foreach ($tracking_rows as $tracking_row)
		{
			$lesson_tags = (array) $this->getTagsByLesson($tracking_row->lesson_id);
			list($hours, $mins, $secs) = explode(':', $tracking_row->time_spent);
			$time_spent = ($hours * 3600) + ($mins * 60) + $secs;
			$track_user = Factory::getUser($tracking_row->user_id);

			// Collect tracking data aggregated per tag per user
			foreach ($lesson_tags as $tag)
			{
				$key_name_usertag = "{$tag}-{$tracking_row->user_id}";

				if (isset($tracking_data_user[$key_name_usertag]['course_time']))
				{
					$tracking_data_user[$key_name_usertag]['course_time'] += $time_spent;
				}
				else
				{
					$tracking_data_user[$key_name_usertag]['course_time'] = $time_spent;
				}

				// Collect tracking data aggregated per tag
				if (isset($tracking_data_tag[$tag]['course_time']))
				{
					$tracking_data_tag[$tag]['course_time'] += $time_spent;
				}
				else
				{
					$tracking_data_tag[$tag]['course_time'] = $time_spent;
				}

				// Collect tracking data aggregated per tag per group
				foreach ($track_user->groups as $group)
				{
					$key_name_grouptag = "{$tag}-{$group}";

					if (isset($tracking_data_group[$key_name_grouptag]['course_time']))
					{
						$tracking_data_group[$key_name_grouptag]['course_time'] += $time_spent;
					}
					else
					{
						$tracking_data_group[$key_name_grouptag]['course_time'] = $time_spent;
					}
				}
			}
		}

		ksort($tracking_data_tag);

		// Get all the attendance data from JTicketing
		$query = "SELECT a.owner_id, a.event_id, TIME_TO_SEC(TIMEDIFF(e.enddate, e.startdate)) AS event_time
		FROM #__jticketing_attendees a
		LEFT JOIN #__jticketing_events e ON a.event_id = e.id
		GROUP BY a.owner_id, a.event_id";

		$db->setQuery($query);
		$attendance_rows = $db->loadObjectList();

		// Update per tag per user totals
		foreach ($attendance_rows as $attendance)
		{
			$attendee_tags = $tags_helper->getItemTags('com_jticketing.event', $attendance->event_id);
			$track_user = Factory::getUser($attendance->owner_id);

			// Collect attendance data aggregated per tag per user
			foreach ($attendee_tags as $attendee_tag)
			{
				$key_name_usertag = "{$attendee_tag->tag_id}-{$attendance->owner_id}";

				if (isset($tracking_data_user[$key_name_usertag]['event_time']))
				{
					$tracking_data_user[$key_name_usertag]['event_time'] += $attendance->event_time;
				}
				else
				{
					$tracking_data_user[$key_name_usertag]['event_time'] = $attendance->event_time;
				}

				if (isset($tracking_data_tag[$attendee_tag->tag_id]['event_time']))
				{
					$tracking_data_tag[$attendee_tag->tag_id]['event_time'] += $attendance->event_time;
				}
				else
				{
					$tracking_data_tag[$attendee_tag->tag_id]['event_time'] = $attendance->event_time;
				}

				// Collect tracking data aggregated per tag per group
				foreach ($track_user->groups as $group)
				{
					$key_name_grouptag = "{$attendee_tag->tag_id}-{$group}";

					if (isset($tracking_data_group[$key_name_grouptag]['event_time']))
					{
						$tracking_data_group[$key_name_grouptag]['event_time'] += $attendance->event_time;
					}
					else
					{
						$tracking_data_group[$key_name_grouptag]['event_time'] = $attendance->event_time;
					}
				}
			}
		}

		ksort($tracking_data_tag);
		$response = array();

		foreach ($tracking_data_user as $key => $track)
		{
			$pcs = explode('-', $key);
			$report_row = new stdClass;
			$report_row->user_id = $pcs[1];
			$report_row->tag_id = $pcs[0];
			$report_row->course_time = !empty($track['course_time']) ? $track['course_time'] : 0;
			$report_row->event_time  = !empty($track['event_time']) ? $track['event_time'] : 0;

			$db->insertObject('#__tagreport_user', $report_row);
			$response['entries']['tagreport_user'][] = $report_row;
		}

		foreach ($tracking_data_group as $key => $track)
		{
			$pcs = explode('-', $key);
			$report_row = new stdClass;
			$report_row->group_id = $pcs[1];
			$report_row->tag_id = $pcs[0];
			$report_row->course_time = !empty($track['course_time']) ? $track['course_time'] : 0;
			$report_row->event_time  = !empty($track['event_time']) ? $track['event_time'] : 0;

			$db->insertObject('#__tagreport_group', $report_row);
			$response['entries']['tagreport_group'][] = $report_row;
		}

		foreach ($tracking_data_tag as $key => $track)
		{
			$report_row = new stdClass;
			$report_row->tag_id = $key;
			$report_row->course_time = !empty($track['course_time']) ? $track['course_time'] : 0;
			$report_row->event_time  = !empty($track['event_time']) ? $track['event_time'] : 0;

			$db->insertObject('#__tagreport_tag', $report_row);

			$response['entries']['tagreport_tag'][] = $report_row;
		}

		// Output json response
		header('Content-type: application/json');

		if (!empty($response))
		{
			echo json_encode($response);
		}
		else
		{
			echo json_encode(array('entries' => 'no entries'));
		}

		jexit();
	}

	/**
	 * Method to get tags by lessons
	 *
	 * @param   INT  $lesson_id  lesson id
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.3.16
	 */
	private function getTagsByLesson($lesson_id)
	{
		static $lesson_map = null;
		static $tag_map = null;
		$db = Factory::getDBO();

		// Create a lesson id > course id map
		if (!$lesson_map)
		{
			$query = "SELECT id as lesson_id, course_id FROM #__tjlms_lessons";
			$db->setQuery($query);
			$lesson_map = $db->loadAssocList('lesson_id', 'course_id');
		}

		// Create a course id > tag id map
		if (!$tag_map)
		{
			$query = "SELECT content_item_id as course_id,
			GROUP_CONCAT(tag_id SEPARATOR ',') AS tags
			FROM #__contentitem_tag_map
			WHERE type_alias = 'com_tjlms.course'
			GROUP BY content_item_id";
			$db->setQuery($query);
			$tag_map = $db->loadAssocList('course_id');
		}

		if (!empty($tag_map[$lesson_map[$lesson_id]]['tags']))
		{
			$lessonsTagsMap = explode(',', $tag_map[$lesson_map[$lesson_id]]['tags']);

			return $lessonsTagsMap;
		}

		return;
	}

	/**
	 * Method to truncate tag reports
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.3.11
	 */
	private function truncate_tag_reports()
	{
		$db = Factory::getDBO();

		$query = "TRUNCATE TABLE #__tagreport_user";
		$db->setQuery($query);
		$db->execute();

		$query = "TRUNCATE TABLE #__tagreport_group";
		$db->setQuery($query);
		$db->execute();

		$query = "TRUNCATE TABLE #__tagreport_tag";
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Method to generate Tag TimeData
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.3.11
	 */
	public function generateTagTimeData()
	{
		// Init vars
		$db       = Factory::getDbo();
		$jinput   = Factory::getApplication()->input;
		$response = array();

		// Get plugin params
		$pluginParams               = $jinput->get('pluginParams', '', 'array');
		$date                       = isset($pluginParams['date']) ? $pluginParams['date'] : date('Y-m-d');
		$skipEventAsLessonFromShika = isset($pluginParams['skipEventAsLessonFromShika']) ? $pluginParams['skipEventAsLessonFromShika'] : 1;

		// If no date passed, well, it's today
		if (!$date)
		{
			$date = date('Y-m-d');
		}

		// Set dates fo query
		$dateBeingReferred          = date('Y-m-d', strtotime($date . ' -1 day'));
		$yesterdayDateBeingReferred = date('Y-m-d', strtotime($date . ' -2 day'));

		// 1.1 SHIKA data - Get all completed / passed leson track data for given date
		// Get lesson_id, user_id, ideal_time
		$query = "SELECT tlt.id, tlt.lesson_id, tlt.timeend, tlt.user_id, tlt.attempt, tlt.lesson_status,
		DATE(timeend) AS enddate, l.ideal_time, tlt.time_spent
		 FROM #__tjlms_lesson_track AS tlt
		 LEFT JOIN #__tjlms_lessons AS l ON l.id = tlt.lesson_id
		 WHERE DATE(tlt.timeend) = DATE(' " . $dateBeingReferred . "')
		 AND (tlt.user_id, tlt.lesson_id ) NOT IN (
			SELECT user_id, lesson_id
			FROM #__tjlms_lesson_track
			WHERE (lesson_status = 'completed' OR lesson_status = 'passed')
			AND (DATE(timeend) <= DATE('" . $yesterdayDateBeingReferred . "'))
		)
		AND (tlt.lesson_status = 'completed' OR tlt.lesson_status = 'passed')";

		// Skip event as lessson from shika time calculations?
		if ($skipEventAsLessonFromShika == 1)
		{
			$query .= " AND l.format != 'event'";
		}

		$query .= " GROUP BY tlt.lesson_id, tlt.user_id";

		$db->setQuery($query);
		$tracking_rows = $db->loadObjectList();

		// Get all lesson ids
		$lessonIds = array_column($tracking_rows, 'lesson_id');
		$lessonIds = array_unique($lessonIds);

		$timeByTags = array();

		if (!empty ($lessonIds))
		{
			// 1.2 Get tags for these lesson ids
			$query = "SELECT l.id AS lesson_id, citm.content_item_id AS tag_course_id, citm.tag_id
			 FROM #__contentitem_tag_map AS citm
			 LEFT JOIN #__tjlms_lessons AS l ON l.course_id = citm.content_item_id
			 WHERE type_alias = 'com_tjlms.course'
			 AND l.id IN (" . implode(',', $lessonIds) . ")";

			$db->setQuery($query);
			$tagsList = $db->loadObjectList();

			$lessonsToTagsMap = array();

			foreach ($tagsList as $tag)
			{
				if (! in_array($tag->tag_id, $lessonsToTagsMap))
				{
					$lessonsToTagsMap[$tag->lesson_id][] = $tag->tag_id;
				}
			}

			// 1.3 Compute time by tags
			foreach ($tracking_rows as $tracking_row)
			{
				$tagsForCurrentLesson = $lessonsToTagsMap[$tracking_row->lesson_id];

				foreach ($tagsForCurrentLesson as $tag4lesson)
				{
					// Convert minutes to seconds
					if (isset($timeByTags[$tag4lesson][$tracking_row->enddate]))
					{
						$timeByTags[$tag4lesson][$tracking_row->enddate]['course_time'] += ($tracking_row->ideal_time * 60);
					}
					else
					{
						$timeByTags[$tag4lesson][$tracking_row->enddate]['course_time'] = ($tracking_row->ideal_time * 60);
					}
				}
			}

			ksort($timeByTags);
		}

		// 2.1 JTicketing data - Get ideal time for events atteneded today
		$query = "SELECT a.owner_id as user_id, a.event_id, DATE(e.enddate) AS date, e.ideal_time
		 FROM #__jticketing_attendees AS a
		 LEFT JOIN #__jticketing_events e ON a.event_id = e.id
		 LEFT JOIN #__jticketing_checkindetails AS c ON c.attendee_id = a.id
		 WHERE DATE(e.enddate) = DATE(' " . $dateBeingReferred . "')
		 AND (a.owner_id, a.event_id ) NOT IN (
			 SELECT a.owner_id as user_id, a.event_id
			 FROM #__jticketing_attendees AS a
			 LEFT JOIN #__jticketing_events e ON a.event_id = e.id
			 LEFT JOIN #__jticketing_checkindetails AS c ON c.attendee_id = a.id
			 WHERE (DATE(e.enddate) <= DATE('" . $yesterdayDateBeingReferred . "'))
			 AND c.checkin = 1
		 )
		 AND c.checkin = 1
		 group by a.event_id, a.owner_id";

		$db->setQuery($query);
		$attendee_rows = $db->loadObjectList();

		// Get  event ids
		$eventIds = array_column($attendee_rows, 'event_id');
		$eventIds = array_unique($eventIds);

		if (!empty ($eventIds))
		{
			// 2.2 Get tags for these event ids
			$query = "SELECT e.id AS event_id, citm.content_item_id AS tag_event_id, citm.tag_id
			 FROM #__contentitem_tag_map AS citm
			 LEFT JOIN #__jticketing_events AS e ON e.id = citm.content_item_id
			 WHERE type_alias = 'com_jticketing.event'
			 AND e.id IN (" . implode(',', $eventIds) . ")";

			$db->setQuery($query);
			$tagsList = $db->loadObjectList();

			$eventsToTagsMap = array();

			foreach ($tagsList as $tag)
			{
				if (! in_array($tag->tag_id, $eventsToTagsMap))
				{
					$eventsToTagsMap[$tag->event_id][] = $tag->tag_id;
				}
			}

			// 2.3 Compute time by tags
			foreach ($attendee_rows as $attendee_row)
			{
				$tagsForCurrentLesson = $eventsToTagsMap[$attendee_row->event_id];

				foreach ($tagsForCurrentLesson as $tag4event)
				{
					// Convert minutes to seconds
					if (isset($timeByTags[$tag4event][$attendee_row->date]['event_time']))
					{
						$timeByTags[$tag4event][$attendee_row->date]['event_time'] += ($attendee_row->ideal_time * 60);
					}
					else
					{
						$timeByTags[$tag4event][$attendee_row->date]['event_time'] = ($attendee_row->ideal_time * 60);
					}
				}
			}

			ksort($timeByTags);
		}

		// 2.4 Add datewise entry for tags
		foreach ($timeByTags as $tag_id => $dateWiseSpent)
		{
			foreach ($dateWiseSpent as $date => $spentTime)
			{
				$report_row              = new stdClass;

				$report_row->tag_id      = $tag_id;
				$report_row->date        = $date;
				$report_row->course_time = !empty($spentTime['course_time']) ? $spentTime['course_time'] : 0;
				$report_row->event_time  = !empty($spentTime['event_time']) ? $spentTime['event_time'] : 0;

				$query = "SELECT tag_id
				 FROM #__tagreport_tag_time
				 WHERE tag_id = " . $tag_id . "
				 AND date = '" . $date . "'";

				$db->setQuery($query);
				$existingData = $db->loadColumn();

				if (empty($existingData))
				{
					$db->insertObject('#__tagreport_tag_time', $report_row);
					$response['entries']['added'][] = $report_row;
				}
				else
				{
					$db->updateObject('#__tagreport_tag_time', $report_row, array('tag_id', 'date'));
					$response['entries']['updated'][] = $report_row;
				}
			}
		}

		// Output json response
		header('Content-type: application/json');

		if (!empty($response))
		{
			echo json_encode($response, JSON_PRETTY_PRINT);
		}
		else
		{
			echo json_encode(array('entries' => 'no entries'));
		}

		jexit();
	}
}
