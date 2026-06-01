<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of LMS activities
 *
 * @since  1.0.0
 */
class TjlmsModelActivities extends ListModel
{
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.0.0
	 */
	protected function getListQuery()
	{
		$user = Factory::getUser();

		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 'a.*'
			)
		);
		$query->select($db->qn('c.title'));
		$query->from($db->qn('#__tjlms_activities', 'a'));
		$query->join('INNER', $db->qn('#__tjlms_courses', 'c') . ' ON (' . $db->qn('c.id') . ' = ' . $db->qn('a.parent_id') . ')');
		$query->where($db->qn('c.state') . '=1');
		$query->where($db->qn('a.actor_id') . '=' . $db->q((int) $user->id));
		$query->where($db->qn('a.parent_id') . '<>0');
		$query->order($db->quoteName('a.added_time') . ' DESC');
		$query->order($db->quoteName('a.id') . ' DESC');

		return $query;
	}

	/**
	 * Render view.
	 *
	 * @param   string  $type    An optional associative array of configuration settings.
	 * @param   string  $prefix  An optional associative array of configuration settings.
	 * @param   array   $config  An optional associative array of configuration settings.
	 *
	 * @since   2.2
	 * @return   null
	 */
	public function getTable($type = 'activity', $prefix = 'TjlmsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return	mixed	Object on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function getItems($pk = null)
	{
		$items = parent::getItems($pk);
		$comtjlmstrackingHelper = new comtjlmstrackingHelper;
		$comtjlmshelper = new ComtjlmsHelper;
		$user_name	= '';

		if (!empty($items))
		{
			foreach ($items as &$item)
			{
				$user_name = (!empty(Factory::getUser()->id))? Text::_('COM_TJLMS_ACTIVITY_ACTOR_YOU'):Factory::getUser()->name;

				switch ($item->action)
				{
					case "ENROLL":

						if (!$item->element_url)
						{
							$item->element_url = 'index.php?option=com_tjlms&view=course&id=' . $item->element_id;
						}

						$course_link  = "<a href='" . $comtjlmshelper->tjlmsRoute($item->element_url) . "'>" . $item->element . "</a>";
						$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ENROLL', $user_name, $course_link);
						break;
					case "ATTEMPT":
						$lesson_link = "<strong>" . $item->element . "</strong>";

						$course_url  = $comtjlmshelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $item->parent_id);

						$course_link = "<a href='" . $course_url . "'>" . $item->title . "</a>";

						$params       = json_decode($item->params);
						$attempt      = $params->attempt;
						$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ATTEMPT', $user_name, $attempt, $lesson_link, $course_link);

						break;
					case "ATTEMPT_END":
						$lesson_link = "<strong>" . $item->element . "</strong>";

						$course_url  = $comtjlmshelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $item->parent_id);
						$course_link = "<a href='" . $course_url . "'>" . $item->title . "</a>";

						$params       = json_decode($item->params);
						$attempt      = $params->attempt;
						$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ATTEMPT_END', $user_name, $attempt, $lesson_link, $course_link);

						break;
					case "COURSE_CREATED":
							if (!$item->element_url)
							{
								$item->element_url = 'index.php?option=com_tjlms&view=course&id=' . $item->element_id;
							}

							$course_link  = "<a href='" . $comtjlmshelper->tjlmsRoute($item->element_url) . "'>" . $item->element . "</a>";
							$text_to_show = Text::sprintf('COM_TJLMS_COURSE_CREATED_STREAM', $user_name, $course_link);

						break;
					case "COURSE_RECOMMENDED":
							$params = json_decode($item->params);
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
								$course_link  = "<a href='" . $comtjlmshelper->tjlmsRoute($item->element_url) . "'>" . $item->element . "</a>";
								$text_to_show = Text::sprintf('COM_TJLMS_ON_RECOMMEND_COURSE_AS_LMS', $user_name, $course_link, $targetUserName);
							}
						break;
					case "COURSE_COMPLETED":
						if (!$item->element_url)
						{
							$item->element_url = 'index.php?option=com_tjlms&view=course&id=' . $item->element_id;
						}

						$course_link  = "<a href='" . $comtjlmshelper->tjlmsRoute($item->element_url) . "'>" . $item->element . "</a>";
						$text_to_show = Text::sprintf('COM_TJLMS_COURSE_COMPLETED_STREAM', $user_name, $course_link);
						break;
					default:
						$text_to_show = '';
						break;
				}

					$currentActivity = $text_to_show . ' - <small><em>' . $comtjlmstrackingHelper->time_elapsed_string($item->added_time, true) . '</em></small>';
					$item->activity = $currentActivity;
			}
		}

		return $items;
	}
}
