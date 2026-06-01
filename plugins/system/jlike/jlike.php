<?php
/**
 * @package    Jlike
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

JLoader::import('components.com_jlike.includes.jlike', JPATH_SITE);

$lang = Factory::getLanguage();
$lang->load('com_jlike', JPATH_SITE);

/**
 * Jilke System Plugin
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgSystemJlike extends CMSPlugin
{
	/**
	 * Function is triggered to extend the recommendation getlistQuery
	 *
	 * @param   Object  $query  Query Object
	 *
	 * @param   Object  $obj    Current object
	 *
	 * @return  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRecommendationsModelGetListQuery($query, $obj)
	{
		$input  = Factory::getApplication()->input;
		$view   = $input->get('view');
		$user   = Factory::getUser();

		if ($view != "recommendations" && $view != "staffdashboard")
		{
			return true;
		}

		if (ComponentHelper::isEnabled('com_multiagency') && ComponentHelper::getParams('com_jlike')->get('enable_multiagency'))
		{
			$db = Factory::getDbo();
			$query->select(array('clusters.id as cluster_id', 'clusters.name as agency_title'));
			$query->join('LEFT', $db->quoteName('#__jlike_todos_cluster_xref', 'todoxref')
				. ' ON (' . $db->qn('todoxref.todo_id') . ' = ' . $db->qn('a.id') . ')');
			$query->join('LEFT', $db->quoteName('#__tj_clusters', 'clusters')
				. ' ON (' . $db->qn('clusters.id') . ' = ' . $db->qn('todoxref.cluster_id') . ')');
			$query->join('LEFT', $db->quoteName('#__tjmultiagency_multiagency', 'tm')
				. ' ON (' . $db->qn('tm.id') . ' = ' . $db->qn('clusters.client_id') . ')');
			$query->where('tm.state = 1');
			$query->where('clusters.state = 1');

			$agencyId   = $obj->getstate('filter.agency_id');
			$agencyTags = $obj->getstate('filter.tags');

			// DPE Hack  checked for dpe admin and check the agencytags 
			if (!empty($agencyTags) && (!$agencyId) && $user->authorise('core.manageall', 'com_cluster'))
			{	
				JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
				$dashBoardModel = JModelLegacy::getInstance('Dashboard', 'DpeModel');
				$agencyId = $dashBoardModel->getClusterIdsByTags($agencyTags);
			}

			// Dpe Hack end 

			if (is_array($agencyId))
			{
				$query->where('clusters.id IN ( ' . implode(',', $agencyId) . ')');
			}
			elseif ($agencyId)
			{
				$query->where('clusters.id = ' . (int) $agencyId);
			}
		}
	}

	/**
	 * Method is called after todo data is stored in the database.
	 *
	 * @param   Object  $data  todo data
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onAfterJlikeTodoSave($data)
	{
		// Method name is changes as per Joomla4 onAfterJlikeTodoSave
		
		if ($data['reminder'])
		{
			$currentReminder = array_column($data['reminder'], 'id');

			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__jlike_reminders_todo_xref'));
			$query->where($db->qn('todo_id') . ' = ' . (int) $data['id']);
			$db->setQuery($query);

			$reminders         = $db->loadAssocList();
			$existsReminder   = array_column($reminders, 'reminder_id');
			$invalidReminders = array_diff($existsReminder, $currentReminder);

			if (! empty($invalidReminders))
			{
				$reminderModel = Jlike::model('Reminder', array('ignore_request' => true));
				$reminderModel->delete($invalidReminders);
			}

			$reminderData                   = array();
			$reminderData['select_content'] = (array) $data['content_id'];
			$reminderData['title']          = $data['task_title'];

			if ($data['status'] === "C")
			{
				$reminderData['state'] = 0;
			}
			else
			{
				$reminderData['state'] = 1;
			}

			$reminderData['subject']        = Text::sprintf('COM_JLIKE_REMINDER_EMAIL_SUBJECT', $data['id'], $data['task_title']);
			$reminderData['email_template'] = Text::_('COM_JLIKE_REMINDER_EMAIL_TEMPLATE');
			$reminderData['content_type']   = $data['element'];
			$reminderData['todo_id']        = $data['id'];

			foreach ($data['reminder'] as $reminder)
			{
				if ($reminder['time_measure'] && $reminder['duration'])
				{
					$reminderData['id']           = $reminder['id'];
					$reminderData['time_measure'] = $reminder['time_measure'];
					$reminderData['duration']     = $reminder['duration'];

					if ($reminder['time_measure'] === "days")
					{
						$reminderData['days_before'] = $reminder['duration'];
					}
					elseif ($reminder['time_measure'] === "weeks")
					{
						$reminderData['days_before'] = $reminder['duration'] * 7;
					}

					$reminderModel = Jlike::model('Reminder', array('ignore_request' => true));
					$reminderModel->save($reminderData);
				}
			}
		}

		// Store cluster todo relation
		if ($data['clusters'])
		{
			// Store cluster id and todo relation
			$todoClusterXrefTable = Jlike::table('TodosClusterXref');
			$obj             = new stdClass;
			$obj->todo_id    = $data['id'];
			$obj->cluster_id = $data['clusters'];

			$todoClusterXrefTable->save($obj);
		}
	}
}
