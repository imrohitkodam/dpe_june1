<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_System_Tjlms_Cluster
 * @copyright  Copyright (C) 2005 - 2019. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;

jimport('joomla.plugin.plugin');
jimport('joomla.application.component.helper');
JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);
JLoader::import('components.com_multiagency.helpers.multiagency', JPATH_SITE);
jimport('techjoomla.tjnotifications.tjnotifications');

JLoader::import('components.com_dpe.includes.dpe', JPATH_SITE);

use Joomla\CMS\Factory;

/**
 * Methods supporting cluster action for shika & dpe.
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgSystemDpe_Tjlms_Cluster extends CMSPlugin
{
	/**
	 * Adds additional fields to the lesson editing form
	 *
	 * @param   Form  $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   3.9.0
	 */
	public function onContentPrepareForm($form, $data)
	{
		$app = Factory::getApplication();

		if (!$app->isClient('site'))
		{
			return;
		}

		if (!($form instanceof Form))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		// Check we are manipulating a valid form - we only display this on lesson form (In DPE compliance manager menu) while editing.
		$name = $form->getName();

		// We only display this if user has not consented before
		if (is_object($data) && $name == 'com_tjlms.lesson')
		{
			// Add the extra fields to the form.
			Form::addFormPath(dirname(__FILE__) . '/forms');
			$form->loadFile('tjlms_cluster', false);

			$superUser = Factory::getUser()->authorise('core.admin');
			$lessonId  = isset($data->id) ? $data->id : 0;

			$spelessonModel = DPE::model('lesson');

			// Get Assigned users count for passed document ID
			$assignUserCount = $spelessonModel->getAssignedTodoCount($lessonId);

			if (!empty($lessonId))
			{
				$form->setFieldAttribute('cluster_id', 'multiple', 'false');

				$table = DPE::table('TjlmsClusterXref');
				$table->load(array('lesson_id' => $lessonId));

				if (!empty($table->id))
				{
					$data->cluster_id = $table->cluster_id;
				}
			}

			// Check document assigned to any user and logged-in user not super user
			if ($assignUserCount && !$superUser)
			{
				$form->setFieldAttribute('cluster_id', 'readonly', 'true');
			}
		}
	}

	/**
	 * This function sends an emails to the selected school members for informing that new document has been created for that school
	 *
	 * @param   integer  $lessonId   Lesson Id
	 * @param   integer  $clusterId  Cluster Id
	 *
	 * @return  null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAfterLessonSendEmail($lessonId, $clusterId)
	{
		// To get Document creator information
		$user     = Factory::getUser();
		$app      = Factory::getApplication();
		$config   = Factory::getConfig();
		$mailfrom = $config->get('mailfrom');
		$fromname = $config->get('fromname');

		// To get School Name
		JLoader::import('clusters', JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
		$clustersTable            = Table::getInstance('clusters', 'ClusterTable', array());
		$clustersTable->load(array('id' => $clusterId));

		// To get school members and their information
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models/');
		$usersModel = BaseDatabaseModel::getInstance('Users', 'MultiagencyModel', array('ignore_request' => true));
		$usersModel->setState('filter.agencies', $clustersTable->id);
		$schoolMemberData = $usersModel->getItems();

		if (!empty($schoolMemberData))
		{
			foreach ($schoolMemberData as $key => $member)
			{
				if ($member->role_title == 'Staff' || $member->role_title == 'Trustee' || $member->email == $user->email)
				{
					unset($schoolMemberData[$key]);
				}
				else
				{
					$recipients[] = array (

						// Add specific to, cc (optional), bcc (optional)
						'email' => array (
							'to' => array ($member->email)
						)
					);
				}
			}
		}

		// Remove duplicate email id

		// To get lesson information
		JLoader::import('lesson', JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
		$lessonTable = Table::getInstance('lesson', 'TjlmsTable', array());
		$lessonTable->load(array('id' => $lessonId,'in_lib' => '1'));

		$client       = "jlike";
		$key          = "createLesson";

		$replacements = new stdClass;

		// Creator Info
		$replacements->user     = $user;

		$roleIdArr = RBACL::getRoleByUser($user->id);

		// To get user role
		JLoader::import('role', JPATH_ADMINISTRATOR . '/components/com_subusers/tables');
		$roleTable = Table::getInstance('role', 'SubusersTable', array());
		$roleTable->load(array('id' => $roleIdArr[0]));

		$replacements->role = $roleTable;

		// School Info
		$replacements->school  = $clustersTable;

		$menu = $app->getMenu();
		$menuItem = $menu->getItems('link', 'index.php?option=com_tjlms&view=managelessons', true);

		// Lesson Info
		$replacements->content = $lessonTable;
		$contentUrl = 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $lessonTable->id . '&Itemid=' . $menuItem->id;
		$replacements->content->url = Uri::root() . substr(Route::_($contentUrl), strlen(Uri::base(true)) + 1);

		$options = new Registry;
		$options->set('subject', $lessonTable);
		$options->set('from', $mailfrom);
		$options->set('fromname', $fromname);

		foreach($recipients as $recipient)
		{
			Tjnotifications::send($client, $key, $recipient, $replacements, $options);
		}		
	}

	/**
	 * Function is triggered when agency is create
	 *
	 * @param   String  $query      Query String
	 *
	 * @param   INT     $clusterId  Cluster Id
	 *
	 * @param   String  $context    Context
	 *
	 * @return  null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onTjlmsGetListQuery($query, $clusterId, $context, $agencyTags)
	{

		$db   = Factory::getDbo();
		$user = Factory::getUser();
		$params     			  = ComponentHelper::getParams('com_multiagency');
		$multiagencyTrusteeRoleId = (int) $params->get('multiagency_trustee_group');
		$isTrustee 				  = in_array($multiagencyTrusteeRoleId, $user->groups);	

		// checked for DPE admin and trustee here and checked for tags if tags present  then get the clusterIdsbytags
		if ($agencyTags && (!$clusterId) && ($user->authorise('core.manageall', 'com_cluster') || $isTrustee))
		{	
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
			$dashBoardModel = BaseDatabaseModel::getInstance('Dashboard', 'DpeModel');
			$clusterId =      $dashBoardModel->getClusterIdsByTags($agencyTags);
		}

		switch ($context)
		{
			case 'com_tjlms.managelessons' :

				// Join with tjlms_lesson_cluster_xref and tj_clusters to get cluster associated documents
				$query->select('cl.name AS cluster_name');
				$query->join('INNER', $db->qn('#__tjlms_lesson_cluster_xref', 'lc') . 'ON(' . $db->qn('a.id') . '=' . $db->qn('lc.lesson_id') . ')');
				$query->join('INNER', $db->qn('#__tj_clusters', 'cl') . 'ON(' . $db->qn('lc.cluster_id') . '=' . $db->qn('cl.id') . ')');
				$query->where($db->quoteName('cl.state') . ' = 1');

				if ($clusterId)
				{				
					if (is_array($clusterId))
					{
						$query->where($db->quoteName('lc.cluster_id') . ' IN ( ' . implode(',', $clusterId ) . ')');	
					}
					else
					{
						$query->where($db->quoteName('lc.cluster_id') . ' =  ' . (int) $clusterId );	
					}

					
				}


				// Cluster filter is not set then should display all master record of loggedin user
				$ClusterModel = ClusterFactory::model('ClusterUsers', array('ignore_request' => true));
				$ClusterModel->setState('list.group_by_client_id', 1);
				$ClusterModel->setState('filter.published', 1);

				if (!$user->authorise('core.manageall.cluster', 'com_cluster'))
				{
					$ClusterModel->setState('filter.user_id', $user->id);

					// Get all assigned cluster entries
					$clusters = $ClusterModel->getItems();

					$clusterData = array();			

					if (!empty($clusters))
					{
						foreach ($clusters as $key => $cluster)
						{
							$manageLessons = RBACL::check($user->id, 'com_cluster', 'core.manage.lessons', 'com_tjlms', $cluster->cluster_id);

							if ($manageLessons)
							{
								$clusterData[] = $cluster->cluster_id;
							}
						}
					}

					$query->where($db->quoteName('lc.cluster_id') . " IN ('" . implode("','", $clusterData) . "')");
				}

			break;
		}
	}

	/**
	 * Function is triggered when agency is create
	 *
	 * @param   Object  $tjlmsObject  Manage Lesson Object
	 *
	 * @param   String  $context      Context
	 *
	 * @return  null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onTjlmsPopulateState($tjlmsObject, $context)
	{
		$app = Factory::getApplication();

		$clusterID = $app->getUserStateFromRequest($context . '.clusters', 'clusters');

		$organisationTag = $app->getUserStateFromRequest($context .'.filter_tags', 'filter_tags');

		if ($clusterID)
		{
			$tjlmsObject->setState('filter.clusters', $clusterID);
		}

		if ($organisationTag)
		{
			$tjlmsObject->setState('filter.tags', $organisationTag);
		}
	}
}
