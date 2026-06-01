<?php
/**
 * @package    Dpe
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;

// Import library dependencies
jimport('joomla.plugin.plugin');
require_once JPATH_SITE . '/components/com_jlike/helper.php';
JLoader::import('components.com_dpe.includes.dpe', JPATH_SITE);
jimport('techjoomla.tjnotifications.tjnotifications');

// Load language file
$lang = Factory::getLanguage();
$lang->load('plg_jlike_dpe', JPATH_ADMINISTRATOR);

/**
 * JgiveViewReport
 *
 * @package     Dpe
 * @subpackage  Jgive report view class
 * @since       1.2.1
 */
class PlgContentjlike_Dpe extends CMSPlugin
{
	public $params;

	/**
	 * Class constructor
	 *
	 * @param   string  &$subject  The subject
	 *
	 * @param   string  $config    The config params
	 */
	public function __construct(&$subject, $config)
	{
		$this->params = ComponentHelper::getParams('com_jlike');

		parent::__construct($subject, $config);
	}

	/**
	 * Function to display the comments area
	 *
	 * @param   string   $context  The context value
	 *
	 * @param   object   &$entity  The entity data
	 *
	 * @param   object   &$params  The params data
	 *
	 * @param   integer  $page     The page number value
	 *
	 * @param   string   $url      The entity urls
	 *
	 * @return  NULL
	 *
	 * @return  string
	 */
	public function onContentAfterDisplay($context, &$entity, &$params, $page = 0, $url = null)
	{
		$user = Factory::getUser();
		JLoader::import("components.com_subusers.includes.rbacl", JPATH_ADMINISTRATOR);
		$roles = RBACL::getRoleByUser(Factory::getUser()->id);
		$memberId = ComponentHelper::getParams('com_multiagency')->get('member_role_id', '0', 'INT');

		if (in_array($memberId, $roles) && count($roles) == 1)
		{
			return '';
		}

		$input           = Factory::getApplication()->input;
		$showComments    = -1;
		$showLikeButtons = 0;
		$jlike_comments  = $this->params->get('jlike_comments');

		if ($jlike_comments)
		{
			$showComments = 0;
			$view         = $input->get('view', '', 'STRING');
			$option         = $input->get('option', '', 'STRING');

			if (($view == 'itemform' && $option == 'com_tjucm') || ($entity->ajaxCallFromUcm))
			{
				$showComments = 1;
			}
			else
			{
				return '';
			}
		}

		$showAssignBtn = 0;
		$showRecommend = 0;

		// Here Id is content Id For e.g Report Id in JGive Case
		$formId	= $entity->id;

		if (empty($formId))
		{
			$app = Factory::getApplication();
			$formId = $app->input->getInt('content_id', 0);
		}

		/* Get log created by user
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('distinct(created_by)');
		$query->from($db->quoteName('#__tj_ucm_data'));
		$query->where($db->quoteName('id') . " =  " . $db->quote($formId));
		$db->setQuery($query);
		$created_by = $db->loadResult();
		*/

		// Get Cluster ID of UCM record
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
		$UCMDataTable = Table::getInstance('data', 'TjucmTable');
		$UCMDataTable->load(array('id' => (int) $formId));

		$cluster_id = 0;

		if (property_exists($UCMDataTable, 'cluster_id'))
		{
			$cluster_id = $UCMDataTable->cluster_id;
		}

		/*
		Get all allocated agency list
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$MultiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel', array('ignore_request' => true));
		$params = ComponentHelper::getParams('com_multiagency');
		$memberRole = $params->get('member_role_id', '0', 'INT');
		$agencies = $MultiagencyModel->getAllocatedAgencies(Factory::getUser()->id, array($memberRole));
		$agencyId = array();
		$team = array();

		foreach ($agencies as $agency)
		{
			$agencyId[] = $agency->id;
		}
		*/

		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
		$cluster = FormHelper::loadFieldType('cluster', false);
		$clusterList = $cluster->getOptionsExternally();

		$usersClusters = array();

		if (!empty($clusterList))
		{
			foreach ($clusterList as $clusterList)
			{
				if (!empty($clusterList->value))
				{
					$usersClusters[] = $clusterList->value;
				}
			}
		}

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			if ((!in_array($cluster_id, $usersClusters)) || (!RBACL::check($user->id, 'com_cluster', 'core.adduser', 'com_multiagency', $cluster_id)))
			{
				return '';
			}
		}

		/*
		if (count($agencyId) > 0)
		{
			Get all users under me
			$query = $db->getQuery(true);
			$query->select('distinct(user_id)');
			$query->from($db->quoteName('#__tjsu_users'));
			$query->where($db->qn('client') . '= "com_multiagency"');
			$query->where($db->quoteName('client_id') . " in ( " . implode(',', $agencyId) . ")");

			$db->setQuery($query);
			$team = $db->loadColumn();
		}

		if (count($team) > 0 && !Factory::getUser()->authorise('core.admin') && !empty($created_by) && !$user->authorise('core.manageall', 'com_cluster'))
		{
			if (!in_array($created_by, $team))
			{
				return false;
			}
		}
		*/

		if ($url == '')
		{
			$url = substr(
				Route::_('index.php?option=com_tjucm&view=itemform&id=' . $formId . '&client=' . $entity->client),
				strlen(Uri::base(true)) + 1
			);
		}

		$input->set(
			'data',
			json_encode(
				array(
					'cont_id'           => $formId,
					'element'           => $context,
					'url'               => $url,
					'plg_name'          => 'jlike_dpe',
					'show_comments'     => $showComments,
					'show_like_buttons' => 0,
					'showrecommendbtn'  => 0,
					'plg_type'          => 'content',
					'showassignbtn'     => $showAssignBtn,
					'show_reviews'      => $showRecommend
				)
			)
		);

		require_once JPATH_SITE . '/' . 'components/com_jlike/helper.php';
		$jlikehelperObj = new ComjlikeHelper;

		return $jlikehelperObj->showlike();
	}

	/**
	 * Function to send notification after assign
	 *
	 * @param   string  $options      The context value
	 *
	 * @param   array   $data         The entity data
	 *
	 * @param   array   $elementData  The params data
	 *
	 * @return  array
	 */
	public function ongetjlike_dpeRecommendationNotificationDetails($options, $data, $elementData)
	{
		if ($data['notifyClient'] == 'com_sla')
		{
			return false;
		}

		// Get content data
		$notification              = new stdClass;
		$notification->user        = Factory::getUser($data['assigned_to'])->name;
		$notification->assigned_by = Factory::getUser($data['assigned_by'])->name;
		$notification->msg         = DPE::utilities()->urlToClickableLink($data['sender_msg']);

		$pageLink = "-";

		if ($data['params'])
		{
			$urlData  = new Registry($data['params']);
			$pageLink = '<a href="' . $urlData['current_page_link'] . '" target="_blank">' . $urlData['current_page_link'] . '</a>';
		}

		$notification->link = $pageLink;

		$task  = new stdClass;
		$task->url  = Uri::root() . substr(Route::_($data['url']), strlen(Uri::base(true)) + 1);
		$task->due_date = Factory::getDate($data['due_date'])->Format(Text::_('COM_DPE_DATE_ONLY_FORMAT'));
		$task->id = $data['id'];
		$task->title = ucwords($data['task_title']);

		$replacements               = new stdClass;
		$replacements->notification = $notification;
		$replacements->task         = $task;

		$optionsRegistryObj = new Registry;
		$optionsRegistryObj->set('task', $task);

		$notificationDetails = array();
		$notificationDetails['notifyClient'] = 'com_dpe';
		$notificationDetails['notifyKey'] = ($data['todoType'] == 'resend')?'jlike.resendrecommend.dpe':'jlike.recommend.dpe';
		$notificationDetails['replacementsObj'] = $replacements;
		$notificationDetails['optionsRegistryObj'] = $optionsRegistryObj;
		
		$this->ccUsersNotification($data);

		return $notificationDetails;
	}

	/**
	 * Function to send notification to cc users after recommendation
	 *
	 * @param   array  $data  The entity data
	 *
	 * @return  array
	 */
	public function ccUsersNotification($data)
	{
		if (! empty($data['cc_users']))
		{
			$users = array();

			if ($data['cc_users'])
			{
				$data['cc_users'] = explode(',', $data['cc_users']);
				$users = array_merge($users, $data['cc_users']);
			}

			$task  = new stdClass;
			$task->due_date = Factory::getDate($data['due_date'])->Format(Text::_('COM_DPE_DATE_ONLY_FORMAT'));
			$task->id = $data['id'];
			$task->title = ucwords($data['task_title']);

			$notification              = new stdClass;
			$notification->assigned_to = Factory::getUser($data['assigned_to'])->name;
			$notification->assigned_by = Factory::getUser($data['assigned_by'])->name;
			$notification->msg         = $data['sender_msg'];

			$pageLink = "-";

			if ($data['params'])
			{
				$urlData  = new Registry($data['params']);
				$pageLink = '<a href="' . $urlData['current_page_link'] . '" target="_blank">' . $urlData['current_page_link'] . '</a>';
			}

			$notification->link = $pageLink;

			foreach ($users as $user)
			{
				$notification->ccuser = Factory::getUser($user)->name;
				$userdata = Factory::getUser($user);

				$recipients = array (
					// Add specific to, cc (optional), bcc (optional)
					'email' => array (
						'to' => array ($userdata->email)
					)
				);

				$replacements = new stdClass;
				$replacements->task = $task;
				$replacements->notification = $notification;

				$options = new Registry;
				$options->set('task.id', $data['id']);
				$options->set('task.title', ucwords($data['task_title']));

				Tjnotifications::send("com_dpe", 'jlike.recommend.ccusers.dpe', $recipients, $replacements, $options);
			}
		}
	}
}
