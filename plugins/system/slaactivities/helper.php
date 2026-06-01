<?php
/**
 * @package    SLA_Activities
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;

jimport('joomla.plugin.plugin');
jimport('joomla.application.component.model');
jimport('techjoomla.jsocial.jsocial');
jimport('joomla.application.component.helper');

BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_activitystream/models', 'ActivityStreamModel');
JLoader::import('components.com_multiagency.includes.multiagency', JPATH_SITE);

/**
 * Plugin for Sla_activities
 *
 * @package     Sla_Activities
 * @subpackage  site
 * @since       __DEPLOY_VERSION__
 */
class PlgSystemSlaActivitiesHelper
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		if (ComponentHelper::isEnabled('com_activitystream'))
		{
			// Load activity component models
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_activitystream/models');

			// Load activity component models
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_activitystream/models');

			// Load activity component tables
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_activitystream/tables');
		}
	}

	/**
	 * Method to get actor data
	 *
	 * @param   Integer  $userId  user id
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getActorData($userId)
	{
		$user             = Factory::getUser($userId);
		$userData         = array();
		$userData['type'] = 'person';
		$userData['id']   = $user->id;
		$userData['name'] = $user->name;

		return $userData;
	}

	/**
	 * Function to add activity for new licence added
	 *
	 * @param   Array  $licenceData  licence data
	 *
	 * @return  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function addLicenceActivity($licenceData)
	{
		$user                     = Factory::getUser();
		$activityData             = array();
		$activityData['id']       = '';
		$actorData                = $this->getActorData($user->id);
		$activityData['actor']    = json_encode($actorData);
		$activityData['actor_id'] = $user->id;
		$params                   = ComponentHelper::getParams('com_dpe');
		$allTools                 = new Registry($params->get('allTools'));
		$currentTools             = new Registry($licenceData['tools']);
		$savedTools               = array();

		foreach ($currentTools as $key => $currentTool)
		{
			foreach ($allTools->get('tools') as $tool)
			{
				if ($tool->tool_client === $key)
				{
					$savedTools[] = $tool->tool_name;
				}
			}
		}

		$multiagencytable = Multiagency::table('multiagency');
		$multiagencytable->load($licenceData['multiagency_id']);

		$objectData                = array();
		$objectData['type']        = 'sla';
		$objectData['id']          = $licenceData['multiagency_id'];
		$objectData['tools']       = implode(', ', $savedTools);
		$objectData['agency']      = $multiagencytable->title;
		$objectData['message']     = $licenceData['message'];
		$objectData['start_date']  = HTMLHelper::date($licenceData['start_date'], 'd-m-Y');
		$objectData['end_date']    = HTMLHelper::date($licenceData['end_date'], 'd-m-Y');
		$objectData['state']       = $licenceData['state'];

		$activityData['object']    = json_encode($objectData);
		$activityData['object_id'] = $licenceData['id'];
		$targetData                = array();
		$targetData['type']        = 'sla';
		$targetData['id']          = $licenceData['multiagency_id'];
		$activityData['target']    = json_encode($targetData);
		$activityData['target_id'] = $licenceData['id'];
		$activityData['client']    = "com_multiagency";
		$activityData['type']      = $licenceData['type'];
		$activityData['template']  = $licenceData['template'];

		$activityStreamModelActivity = BaseDatabaseModel::getInstance('Activity', 'ActivityStreamModel');
		$result = $activityStreamModelActivity->save($activityData);

		return $result;
	}
}
