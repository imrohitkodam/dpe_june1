<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);
JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);

/**
 * The sla activity controller
 *
 * @since  1.0.0
 */
class SlaControllerSlaActivity extends FormController
{
	/**
	 * Function to update Jlike todos
	 *
	 * @return  object  object
	 */
	public function updateTodo()
	{ 
		if (!Session::checkToken('get'))
		{
			echo new JResponseJson(null, Text::_('JINVALID_TOKEN'), true);
		}
		else
		{
			$app = Factory::getApplication();
			$input = $app->input;

			$user            = Factory::getUser();
			$currentDateTime = Factory::getDate()->toSql();

			$todoId = $input->get('todoId', 0, 'INT');
			$todoStatus = $input->get('todoStatus', '', 'WORD');

			if (empty($todoId) || empty($todoStatus) || (!$user->id))
			{
				echo new JResponseJson(null, Text::_('JERROR_ALERTNOAUTHOR'), true);

				return;
			}

			// Get Cluster details from todo id
			$slaActivitiesTable = SlaFactory::table('slaactivities');
			$slaActivitiesTable->load(array('todo_id' => $todoId));

			if (property_exists($slaActivitiesTable, 'todo_id'))
			{
				$clusterId = $slaActivitiesTable->cluster_id;
			}

			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				if (!$clusterId)
				{
					echo new JResponseJson(null, Text::_('JERROR_ALERTNOAUTHOR'), true);

					return;
				}

				// DPE hack to check permission
				$canCreateActivity = RBACL::check($user->id, 'com_cluster', 'core.create.activity', 'com_sla', $clusterId);

				if (!$canCreateActivity)
				{
					echo new JResponseJson(null, Text::_('JERROR_ALERTNOAUTHOR'), true);

					return;
				}
			}

			JLoader::import('components.com_jlike.models.recommendationform', JPATH_SITE);
			$recommendationFormModel = BaseDatabaseModel::getInstance('RecommendationForm', 'JlikeModel', array('ignore_request' => true));

			$todoData['id']            = $todoId;
			$todoData['status']        = $todoStatus;
			$todoData['modified_date'] = $currentDateTime;
			$todoData['done_date']     = $currentDateTime;
			$todoData['done_by']       = $user->id;
			$todoData['plg_type']      = 'system';
			$todoData['plg_name']      = 'dpe';
			$todoData['notifyClient']  = 'com_sla';

			if ($todoStatus == 'I')
			{
				$todoData['done_by'] = 0;
			}

			if (!$recommendationFormModel->save($todoData))
			{
				echo new JResponseJson(null, Text::_('JERROR_ALERTNOAUTHOR'), true);

				return;
			}

			echo new JResponseJson(null, Text::_('COM_SLA_TODO_STATUS_UPDATED'), false);

			return;
		}
	}

	/**
	 * Function to delete SLA Activity
	 *
	 * @return  object  object
	 */
	public function deleteActivity()
	{
		if (!Session::checkToken('get'))
		{
			echo new JResponseJson(null, Text::_('JINVALID_TOKEN'), true);
		}
		else
		{
			$app   = Factory::getApplication();
			$input = $app->input;

			$user            = Factory::getUser();
			$currentDateTime = Factory::getDate()->toSql();

			$activityId = $input->get('activityId', 0, 'INT');
			$licenseId  = $input->get('licenseId', 0, 'INT');

			$slaSlaActivity = SlaSlaActivity::getInstance($activityId);

			if (empty($slaSlaActivity->id) || empty($licenseId) || (!$user->id))
			{
				echo new JResponseJson(null, Text::_('JERROR_ALERTNOAUTHOR'), true);

				return;
			}

			$model = $this->getModel('SlaActivity', 'SlaModel');

			/** @scrutinizer ignore-call */
			$deleteActivity = $model->delete($slaSlaActivity->id);

			if (!$deleteActivity)
			{
				echo new JResponseJson(null, Text::_('JERROR_ALERTNOAUTHOR'), true);

				return;
			}

			JLoader::import('components.com_jlike.tables.todos', JPATH_ADMINISTRATOR);
			$todoTable = Table::getInstance('Todos', 'JlikeTable');
			$todoTable->delete($slaSlaActivity->todo_id);

			// Add code to delete timelogs

			echo new JResponseJson(null, Text::_('COM_SLA_ACTIVITY_DELETED_SUCCESSFULLY'), false);

			return;
		}
	}

	/**
	 * Method to get user list depending on the client chosen.
	 *
	 * @return   null
	 *
	 * @since    1.0.0
	 */
	public function getUsersByClusterId()
	{
		$licenseId = Factory::getApplication()->input->getInt('license', 0);
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		if (!$licenseId)
		{
			echo new JsonResponse(null, Text::_("COM_SLA_ACTIVITY_LICENSE_NOT_SELECTED"), true);
			$app->close();
		}

		// Get client id by licence id
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
		$licenceTable = Table::getInstance('Licence', 'MultiagencyTable');
		$licenceTable->load(array('id' => $licenseId));
		$clientId = $licenceTable->multiagency_id;

		// Get all users from cluster
		$subusersModelUsers = RBACL::model('users', array('ignore_request' => true));
		$subusersModelUsers->setState('filter.client_id', $clientId);
		$subusersModelUsers->setState('filter.client', 'com_multiagency');
		$subusersModelUsers->setState('group_by', 'user_id');
		$subusersModelUsers->setState('filter.state', 0);
		$subusersModelUsers->setState('list.ordering', 'uc.name');
		$subusersModelUsers->setState('list.direction', 'asc');
		$userOptions = $allUsers = array();
		$allUsers = $subusersModelUsers->getItems();

		$userOptions[] = HTMLHelper::_('select.option', "", Text::_('COM_SLA_SELECT_USER'));

		if (!empty($allUsers))
		{
			foreach ($allUsers as $user)
			{
				$userOptions[] = HTMLHelper::_('select.option', $user->user_id, trim($user->name));
			}
		}

		echo new JsonResponse($userOptions);
		jexit();
	}

	/**
	 * Method to get user list depending on the client chosen.
	 *
	 * @return   null
	 *
	 * @since    1.0.0
	 */
	public function getLeadConsultantByClusterId()
	{
		// Get user groups as per Config
		$licenseId            = Factory::getApplication()->input->getInt('license', 0);
		$app                  = Factory::getApplication();
		$params               = ComponentHelper::getParams('com_multiagency');
		$leadConsultantRoleId = (int) $params->get('organization_lead_consultant_role_id', '0');
		$dpeAdminGroupId      = (int) $params->get('multiagency_admin_group', '0');
		$userOptions          = array();
		$allUsers             = array();
		$dpeAdminList         = array();
		$clientId             = 0;

		if (!Session::checkToken())
		{
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		if (!$licenseId)
		{
			echo new JsonResponse(null, Text::_("COM_SLA_ACTIVITY_LICENSE_NOT_SELECTED"), true);
			$app->close();
		}

		if ($dpeAdminGroupId)
		{
			// Get DPE Admin List
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select(array('u.id', 'u.name'));
			$query->from('`#__users` AS u');
			$query->join('LEFT', $db->quoteName('#__user_usergroup_map', 'map') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('map.user_id'));
			$query->where($db->quoteName('map.group_id') . '= ' . (int) $dpeAdminGroupId);
			$query->where('u.block = 0');
			$query->group($db->quoteName('u.id'));
			$query->order($db->quoteName('u.name') . ' ASC');

			$db->setQuery($query);

			$dpeAdminList = $db->loadObjectList();
		}

		// Get client id by licence id
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
		$licenceTable = Table::getInstance('Licence', 'MultiagencyTable');
		$licenceTable->load(array('id' => $licenseId));

		if (property_exists($licenceTable, 'multiagency_id'))
		{
			$clientId = $licenceTable->multiagency_id;
		}

		// Get all users from cluster
		$subusersModelUsers = RBACL::model('users', array('ignore_request' => true));
		$subusersModelUsers->setState('filter.client_id', $clientId);
		$subusersModelUsers->setState('filter.client', 'com_multiagency');
		$subusersModelUsers->setState('filter.role_id', $leadConsultantRoleId);
		$subusersModelUsers->setState('group_by', 'user_id');
		$subusersModelUsers->setState('filter.state', 0);
		$subusersModelUsers->setState('list.ordering', 'uc.name');
		$subusersModelUsers->setState('list.direction', 'asc');
		$allUsers = $subusersModelUsers->getItems();

		// Construct Drop Down
		$options = array();
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_SLA_SELECT_DPE_ADMIN'));

		// Add External LC in list
		if (!empty($allUsers))
		{
			foreach ($allUsers as $user)
			{
				$options[] = HTMLHelper::_('select.option', $user->user_id, trim($user->name) . Text::_('COM_SLA_SELECT_SELECT_LC_LIST_TITLE'));
			}
		}

		// Dpe admin users
		foreach ($dpeAdminList as $dpeAdminUser)
		{
			$options[] = HTMLHelper::_('select.option', $dpeAdminUser->id, trim($dpeAdminUser->name));
		}

		echo new JsonResponse($options);
		jexit();
	}

	/**
	 * Method to get activity types.
	 *
	 * @return   void
	 *
	 * @since    __DEPLOY_VERSION__
	 */
	public function getSlaActivityTypes()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		$slaId    = $app->input->get('slaId', 0, 'INT');
		$slaModel = SlaFactory::model('Sla', array('ignore_request' => true));

		if (!$slaId)
		{
			return false;
		}

		$html = $slaModel->getSlaActivityTypeHtml($slaId);

		echo new JsonResponse($html);
		$app->close();
	}

	/**
	 * Method to archive single activity.
	 *
	 * @return   boolean
	 *
	 * @since    __DEPLOY_VERSION__
	 */
	public function archiveActivity()
	{
		if (!Session::checkToken('get'))
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
		}
		else
		{
			$app   = Factory::getApplication();
			$input = $app->input;

			$user            = Factory::getUser();
			$currentDateTime = Factory::getDate()->toSql();

			$activityId = $input->get('activityId', 0, 'INT');
			$licenseId  = $input->get('licenseId', 0, 'INT');

			$slaSlaActivity        = SlaSlaActivity::getInstance($activityId);
			$slaSlaActivity->state = 2;

			if ($slaSlaActivity->save())
			{
				echo new JsonResponse(null, Text::_('COM_SLA_ACTIVITY_ARCHIVED_SUCCESSFULLY'), false);

				return;
			}
		}
	}
}
