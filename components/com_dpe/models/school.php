<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Date\Date;
use Joomla\Registry\Registry;

/**
 * Methods supporting a list of School Management.
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeModelSchool extends AdminModel
{
	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		return true;
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function canDelete($record='')
	{
		return Factory::getUser()->authorise('core.delete', 'com_multiagency');
	}

	/**
	 * Method to delete data
	 *
	 * @param   int     $pk         Item primary key
	 *
	 * @param   String  $modelName  entity name
	 *
	 * @return  int  The id of the deleted item
	 *
	 * @throws Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function delete(&$pk, $modelName = 'MultiagencyForm')
	{
		if ($pk == 0)
		{
			$this->setError(Text::_("COM_DPE_RECORD_DOESNT_EXIST"));

			return false;
		}

		if ($this->canDelete() !== true)
		{
			$this->setError(Text::_("JERROR_ALERTNOAUTHOR"));

			return false;
		}

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models', $modelName);
		$model = BaseDatabaseModel::getInstance($modelName, 'MultiagencyModel');

		if ($modelName == 'MultiagencyForm')
		{
			$helperPath = JPATH_COMPONENT_SITE . '/helpers/multiagency.php';

			if (! class_exists('MultiagencyFrontendHelpers'))
			{
				// Require_once $path;
				JLoader::register('MultiagencyFrontendHelpers', $helperPath);
				JLoader::load('MultiagencyFrontendHelpers');
			}

			$helperObject = new MultiagencyFrontendHelpers;
			$enrolledCount = $helperObject->getAgencyEnrollment($pk);

			if (($enrolledCount[0]->enrolled) > 0)
			{
				$this->setError(Text::sprintf("COM_DPE_COM_MULTIAGENCY_ENROLLED_USERS", Text::_('COM_DPE_ORGANISATION')));

				return false;
			}
			else
			{
				$model->delete($pk);

				return $pk;
			}
		}
		elseif ($modelName == 'LicenceForm')
		{
			$licenceTable = $model->getTable();
			$licenceTable->load($pk);

			if ($model->delete($pk))
			{
				if (property_exists($licenceTable, 'multiagency_id'))
				{
					$multiagencyId = $licenceTable->multiagency_id;
					$this->deleteUpcomingLicences($multiagencyId, $pk);
				}
			}

			return $pk;
		}
	}

	/**
	 * Method to archive licence
	 *
	 * @param   int     $licenceId  licence id
	 * @param   string  $key        key
	 *
	 * @return  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function archiveLicence($licenceId, $key = null)
	{
		$user              = Factory::getUser();
		$params            = ComponentHelper::getParams('com_dpe');
		$privateKeyCronjob = $params->get('private_key_storage_cron');

		if ($user->authorise('core.manageall', 'com_cluster') || $privateKeyCronjob === $key)
		{
			JLoader::import('components.com_multiagency.includes.multiagency', JPATH_SITE);
			JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);

			$licenceTable = Multiagency::table('licence');

			// Get active licence of agency
			$licenceTable->load(array('id' => $licenceId));

			// Check licence id is valid
			if (property_exists($licenceTable, 'id'))
			{
				if (!$licenceTable->id)
				{
					throw new Exception(Text::_('COM_MULTIAGENCY_ITEM_DOESNT_EXIST'), 404);
				}
			}

			// Archive licence activities first
			$slaModel = SlaFactory::model('SlaActivities', array('ignore_request' => true));

			if ($slaModel->updateActivitiesState($licenceId, 2))
			{
				$licenceModel = Multiagency::model('licence', array('ignore_request' => true));

				// Archive licence after activities are archived
				$licenceTable = $licenceModel->getTable();
				$licenceTable->load($licenceId);
				$licenceTable->state = 2;

				if (is_null($key))
				{
					// This will execute on manual archive licence
					if ($licenceTable->store())
					{
						PluginHelper::importPlugin('system');

						if (property_exists($licenceTable, 'multiagency_id'))
						{
							Factory::getApplication()->triggerEvent('onAfterLicenceArchive', array($licenceId, $licenceTable->multiagency_id));

							return $this->deleteUpcomingLicences($licenceTable->multiagency_id, $licenceId);
						}
					}
				}
				else
				{
					return $licenceTable->store();
				}
			}
		}
	}

	/**
	 * Method to activate licence
	 *
	 * @param   int     $licenceId  agency id
	 * @param   string  $key        cron secret key
	 *
	 * @return  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function activeLicence($licenceId, $key = null)
	{
		$user              = Factory::getUser();
		$params            = ComponentHelper::getParams('com_dpe');
		$privateKeyCronjob = $params->get('private_key_storage_cron');

		if ($user->authorise('core.manageall', 'com_cluster') || $privateKeyCronjob === $key)
		{
			JLoader::import('components.com_multiagency.includes.multiagency', JPATH_SITE);
			JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);

			$slaModel         = SlaFactory::model('SlaActivities', array('ignore_request' => true));
			$licenceModel     = Multiagency::model('licence', array('ignore_request' => true));
			$licenceTable     = $licenceModel->getTable();
			$licenceTable->id = $licenceId;

			// Make licence active by adding state 1
			$licenceTable->state = 1;

			if ($licenceTable->store())
			{
				// Set state 1 to make activities active
				return $slaModel->updateActivitiesState($licenceId, 1);
			}
		}
	}

	/**
	 * Method to delete upcoming licence
	 *
	 * @param   int  $multiAgencyId  agency id
	 * @param   int  $licenceId      licence id
	 *
	 * @return  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function deleteUpcomingLicences($multiAgencyId, $licenceId)
	{
		// If archived manually then check upcoming licences
		JLoader::register('MultiagencyModelLicences', JPATH_ADMINISTRATOR . '/components/com_multiagency/models/licences.php');
		$multiagencyModelLicences = BaseDatabaseModel::getInstance('Licences', 'MultiagencyModel');

		// State 3 used for upcoming licence
		$multiagencyModelLicences->setState('filter.state', 3);
		$multiagencyModelLicences->setState('filter.multiagency_id', $multiAgencyId);
		$upcomingLicences = $multiagencyModelLicences->getItems();
		$upcomingLicencesIds = array_column($upcomingLicences, 'id');

		// If upcoming licence available then delete all next upcoming licences
		if (! empty($upcomingLicences))
		{
			foreach ($upcomingLicencesIds as $upcomingLicencesId)
			{
				if ($upcomingLicencesId > $licenceId)
				{
					$licenceModel = Multiagency::model('licenceform', array('ignore_request' => true));
					$licenceModel->delete($upcomingLicencesId);
				}
			}
		}

		return true;
	}

	/**
	 * Method to get the  job title data for csv
	 *
	 * @param $clusterId  integer clusterId
	 * 
	 * @return array job title data
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getJobTitlesByClusterId($clusterId)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');
		$tjFieldFieldTable->load(array('name' => 'com_tjucm_role_name', 'state' => 1));
		$fieldId = json_decode($tjFieldFieldTable->id);

		$query->select($db->quoteName(array('fields.value','ucmData.id')));
		$query->from($db->quoteName('#__tjfields_fields_value', 'fields'));
		$query->leftJoin($db->quoteName('#__tj_ucm_data', 'ucmData') . ' ON (' . $db->quoteName('fields.content_id') . ' = ' . $db->quoteName('ucmData.id') . ')');
		$query->where($db->quoteName('ucmData.client') . " = " . "'com_tjucm.role'");
		$query->where($db->quoteName('ucmData.cluster_id') . ' = ' .(int) $clusterId );
		$query->where($db->quoteName('fields.field_id') . ' = ' . (int) $fieldId );
		$db->setQuery($query);

		return $db->loadObjectlist();
	}
	
	/**
	 * Method to save the job title data in xref table from csv
	 *
	 * @param $clusterId  integer clusterId
	 * 
	 * @param $userId  	  integer userId
	 * 
	 * @param $contentId  integer contentId
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function saveJobTitle($clusterId, $userId, $contentId)
	{
		if (!$clusterId || !$userId || !$contentId)
		{
			return false;
		}
		
		Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
		$jobTitleExtendTable = Table::getInstance('JobtitleExtend', 'DpeTable');
		$jobTitleSaveData    = array('cluster_id' => $clusterId, 'user_id' => $userId);
		$isPresentJobtitle   = $jobTitleExtendTable->load($jobTitleSaveData);

		if (!$isPresentJobtitle)
		{	
			// update the jobtitle
			$jobTitleExtendTable->save(array('cluster_id' => $clusterId, 'user_id' => $userId, 'ucm_id' => $contentId));
		}
		else
		{
			$jobTitleSaveData = array('id'=>$jobTitleExtendTable->id, 'cluster_id' => $clusterId, 'user_id' => $userId, 'ucm_id' => $contentId);
			$jobTitleExtendTable->save($jobTitleSaveData);
		}
	}

	// Dpe work job title
	/**
	 * This method will return ucmId/jobtitle 
	 *
	 * @param   integer  $licenseId  Id of organisation License.
	 *
	 * @param   integer  $userId  user ID.
	 * 
	 * @return  String   jobtitle
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getJobTitlebyUserData($clusterId, $userId)
	{ 
		if (!$userId || !$clusterId)
		{
			return false;
		}
		
		Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
		$jobtitleExtendInstance  = Table::getInstance('JobtitleExtend', 'DpeTable');
		$jobtitleExtendInstance->load(array('user_id' => $userId,'cluster_id' => $clusterId));

		$relatedData = array('ucm_id'=>$jobtitleExtendInstance->ucm_id, 'dpelead'=>$jobtitleExtendInstance->dpelead);

		return $relatedData;
	}

	/**
	 * This method will return Job Title
	 *
	 * @param   integer  $jobTitleId  client id of School.
	 *
	 * @return  String value  of job title 
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getJobTitleValueById($jobTitleId)
	{ 
		if (!$jobTitleId)
		{
			return false;
		}

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');
		$tjFieldFieldTable->load(array('name' => 'com_tjucm_role_name', 'state' => 1));
		$fieldId = json_decode($tjFieldFieldTable->id);

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$tjFieldFieldValueTable = Table::getInstance('fieldsvalue', 'TjfieldsTable');
		$tjFieldFieldValueTable->load(array('content_id' => $jobTitleId, 'field_id' => $fieldId)); 

		return $tjFieldFieldValueTable->value;
	}

	/**
	 * This method will return jobtitle Id 
	 *
	 * @param   integer  $userId  user ID of job title.
	 * 
	 * @return  Object   jobtitle list
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function  getJobTitlesByUserId($userId)
	{
		if (!$userId)
		{
			return false;
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__job_title_user_xref'));
		$query->where($db->quoteName('user_id') . " = " . (int) $userId);
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * This method save and update the jobtitle details for the user
	 *
	 * @param   array    $userJobTitleDetails  userId and its jobtitle ID id of organisation.
	 * 
	 * @param   array    $organisationData  row id and its jobtitle data, id of organisation from xref table .
	 *
	 * @param   integer  $userId of current user of which data is modifyig
	 * 
	 * @since   __DEPLOY_VERSION__
	 */
	public function saveUpdateJobTitle($userJobTitleDetails, $organisationData, $userId)
	{  
		
		if (!$userJobTitleDetails || !$userId)
		{
			return false;
		}
		
		Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');


		foreach ($userJobTitleDetails as $key => $value)
		{
			$clustertableInstance  = Table::getInstance('Clusters', 'ClusterTable');
			$clustertableInstance->load(array('client_id' => $value['clusterId']));
			$value['clusterId']    = $clustertableInstance->id;
			
			$tjJobstitleXrefTable = Table::getInstance('JobtitleExtend', 'DpeTable');

			$checkedValue          =  array('cluster_id' => $value['clusterId'], 'ucm_id' => $value['ucm_id'], 'user_id' => $userId, 'dpelead' => $value['dpelead']);

			$jobTitleAvailable = $tjJobstitleXrefTable->load($checkedValue);


			if ((sizeof($organisationData) != 0) && (!$jobTitleAvailable) )
			{
				if ((in_array($value['user_id'], array_column($organisationData, 'user_id','id')) && in_array($value['clusterId'], array_column($organisationData, 'cluster_id','id'))))
				{
					$tjjobtitleXrefTable = Table::getInstance('JobtitleExtend', 'DpeTable');
					$tjjobtitleXrefTable->load(array('cluster_id' => $value['clusterId'], 'user_id' => $userId));

					$updatedData = array('id' => $tjjobtitleXrefTable->id,'cluster_id' => $value['clusterId'], 'ucm_id' => $value['ucm_id'], 'user_id' => $userId,'dpelead' => $value['dpelead']);
					
					$tjJobstitleXrefTable->save($updatedData);
				}
				else
				{
					$updatedData = array('cluster_id' => $value['clusterId'], 'ucm_id' => ($value['ucm_id'])?$value['ucm_id']:"0", 'user_id' => $userId,'dpelead' => $value['dpelead']);
					$tjJobstitleXrefTable->save($updatedData);
				}		 							
			}
			elseif ((sizeof($organisationData) == 0))
			{
				$ucmId = ($value['ucm_id'])?$value['ucm_id']:'0';
				$updatedData = array('cluster_id' => $value['clusterId'], 'ucm_id' => $ucmId, 'user_id' => $userId,'dpelead' => $value['dpelead']);
				$tjJobstitleXrefTable->save($updatedData); 
			}	
		}
	}

	/**
	 * This method delete the jobtitle details for the user
	 *
	 * @param   array    $userJobTitleDetails  userId and its jobtitle ID id of School.
	 * 
	 * @param   array    $organisationData  $organisationData  row id and its jobtitle data, id of organisation from xref table
	 *
	 * @param   integer  $userId of current user of which data is modifyig
	 * 
	 * @since   __DEPLOY_VERSION__
	 */
	public function deleteJobTitle($userJobTitleDetails, $organisationData, $userId)
	{
		if (!$userJobTitleDetails || !$organisationData || !$userId)
		{
			return false;
		}

		Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
		
		// For delete from xref table 
		foreach ($organisationData as $key => $value)
		{
			$tjlmsClusterXrefTable = Table::getInstance('JobtitleExtend', 'DpeTable');

			if (!in_array($value->ucm_id, array_column($userJobTitleDetails, 'ucm_id')) && (!in_array($value->cluster_id, array_column($userJobTitleDetails, 'clusterId'))))
			{ 	
				$checkedValue      =  array('cluster_id' => $value->cluster_id, 'ucm_id' => $value->ucm_id, 'user_id' => $userId);
				$tjlmsClusterXrefTable->load($checkedValue);
				$tjlmsClusterXrefTable->delete();			 
			}
		}
	}

	/**
	 * This method save the jobtitle details for the user
	 *
	 * @param   array  $userJobTitleDetails  userId and its jobtitle ID id of School.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function saveUsersJobTitleDetails($userJobTitleDetails, $userId=null)
	{   

		if (!$userJobTitleDetails || !$userId)
		{
			return false;
		}

		$organisationData = isset($userId)?$this->getJobTitlesByUserId($userId):null;

		if ((sizeof($userJobTitleDetails) >= sizeof($organisationData)))
		{
			$this->saveUpdateJobTitle($userJobTitleDetails, $organisationData, $userId);
		}
		else
		{
			$this->deleteJobTitle($userJobTitleDetails, $organisationData, $userId);
			$organisationData = isset($userId)?$this->getJobTitlesByUserId($userId):null;

			if ((sizeof($userJobTitleDetails) >= sizeof($organisationData)))
			{
				$this->saveUpdateJobTitle($userJobTitleDetails, $organisationData, $userId);
			}
		}
	}

	/**
	 * This method will return list of tags 
	 *
	 * @param   String  $userGroup group ID of the user.
	 * 
	 * @return  Array  tags 
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getAgencyTags($userGroup = null)
	{	
		$user     = Factory::getUser();

		// Check user is from trustee group or not
		if (in_array($userGroup, $user->groups))
		{
			JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters = $clusterUserModel->getUsersClusters($user->id);
			$clientId = array();

			foreach ($clusters as $key => $cluster)
			{
				$clientId[$key] = $cluster->client_id;
			}

			// $clientId contains the id of the multiagency

			$clientId = implode(',', $clientId);
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('Distinct tags.id as value , tags.title as text');
		$query->from($db->qn('#__tags', 'tags'));
		$query->join('LEFT', $db->qn('#__contentitem_tag_map', 'tagMap') . ' ON (' . $db->qn('tagMap.tag_id') . ' = ' . $db->qn('tags.id') . ')');
		$query->where($db->quoteName('tagMap.type_alias') . " = 'com_multiagency.multiagency' ");

		if ($clientId)
		{
			$query->where($db->quoteName('tagMap.content_item_id') . " IN " . "(" . $clientId . ")");
		}
		
		$query->order('tags.title ASC');

		$db->setQuery($query);

		return $db->loadAssocList();
	}

	/**
	 * This method will return contentId and save the job title if job title is not present in the list .
	 *
	 * @param   INT  $clusterId  Id of organisation .
	 * 
	 * @param   STRING  $jobtitle  title of job .
	 * 
	 * @return  INT contentid 
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function saveJobTitleFromCsv($clusterId, $jobtitle)
	{
		if (empty($clusterId) || empty($jobtitle))
		{
			return false;
		}

		$user        = Factory::getUser();
		$currentUser = $user->id;

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjucm/tables');
		$tjucmTabletype = Table::getInstance('type', 'TjucmTable');
		$tjucmTabletype->load(array('unique_identifier' => 'com_tjucm.role'));
		$typeId = $tjucmTabletype->id;
		$date = Factory::getDate();
		$currentTime = $date->toSql(true);
		$ucmData = array('cluster_id' => $clusterId, 'parent_id' => 0, 'asset_id' => 0,'ordering' => 0,
			'state' => 1, 'category_id' => 0, 'type_id' => $typeId, 'client' => 'com_tjucm.role', 'checked_out' => 0,
			'created_by' => $currentUser,'draft' => 0,'created_date' => $currentTime);

		// Get the content Id after saving.
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjucm/tables');
		$tjucmTabledata = Table::getInstance('data', 'TjucmTable');
		$tjucmTabledata->save($ucmData);
		$contentId = $tjucmTabledata->id;

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('id', 'type','name')));
		$query->from($db->qn('#__tjfields_fields'));
		$query->where($db->quoteName('client') . " = 'com_tjucm.role' ");

		$db->setQuery($query);
		$fieldIds = $db->loadAssocList();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');

		// Save field value for jobtitle in field table
		foreach ($fieldIds as $field)
		{
			$tjFieldsFieldValueTable = Table::getInstance('fieldsvalue', 'TjfieldsTable');

			if ($field['name'] == 'com_tjucm_role_name')
			{
				$jobtitleValue = array('field_id' => $field['id'],'content_id' => $contentId, 'value' => $jobtitle,
					'option_id' => 0, 'user_id' => $currentUser, 'client' => 'com_tjucm.role');
				$tjFieldsFieldValueTable->save($jobtitleValue);
			}
			elseif($field['name'] == 'com_tjucm_role_clusterclusterid')
			{
				$clusterValue = array('field_id' => $field['id'],'content_id' => $contentId, 'value' => $clusterId,
					'option_id' => 0, 'user_id' => $currentUser, 'client' => 'com_tjucm.role');
				$tjFieldsFieldValueTable->save($clusterValue);
			}
		}

		return $contentId;
	}
	/**
	 * Method to get tools associated with a specific licence.
	 *
	 * Returns an associative array like:
	 * [
	 *   "com_tjlms.compliancemanager" => "on",
	 *   "com_tjucm.FOIlog" => "on"
	 * ]
	 *
	 * @param   integer  $licenceId  The licence ID to get associated tools for.
	 *
	 * @return  array  Associative array of enabled tools.
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getLicenceToolsList($licenceId)
	{
		try
		{
			// Load component parameters
			$params   = ComponentHelper::getParams('com_dpe');
			$allTools = new Registry($params->get('allTools'));

			// Prepare DB query
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select($db->quoteName('tlx.client'))
				->from($db->quoteName('#__tjmultiagency_licences', 'tl'))
				->join(
					'LEFT',
					$db->quoteName('#__tjmultiagency_licences_xref', 'tlx') .
					' ON ' . $db->quoteName('tl.id') . ' = ' . $db->quoteName('tlx.licence_id')
				)
				->where($db->quoteName('tl.id') . ' = ' . (int) $licenceId);

			$db->setQuery($query);
			$savedTools = $db->loadColumn();

			$toolsList = array();

			// Match saved tools with available tools
			if (!empty($savedTools) && $allTools->get('tools'))
			{
				foreach ($savedTools as $savedTool)
				{
					foreach ($allTools->get('tools') as $tool)
					{
						if (isset($tool->tool_client) && $tool->tool_client === $savedTool)
						{
							$toolsList[$savedTool] = 'on';
						}
					}
				}
			}

			return $toolsList;
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return array();
		}
	}

	/**
	 * Method to get SLA activity counts for a specific licence.
	 *
	 * Returns an associative array like:
	 * [
	 *   typeId => activityCount,
	 *   5 => 0,
	 *   4 => 0,
	 *   3 => 0
	 * ]
	 *
	 * @param   integer  $licenceId  The licence ID to get activity counts for.
	 *
	 * @return  array  Associative array of activity counts by typeId.
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getLicenceActivityCounts($licenceId)
	{
		try
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);

			$query->select('COUNT(sa.id) AS activityCount, st.id AS typeId, st.title')
				->from($db->quoteName('#__tj_sla_activity_types', 'st'))
				->join(
					'LEFT',
					$db->quoteName('#__tj_sla_activities', 'sa') .
					' ON ' . $db->quoteName('sa.sla_activity_type_id') . ' = ' . $db->quoteName('st.id') .
					' AND ' . $db->qn('sa.license_id') . ' = ' . (int) $licenceId .
					' AND ' . $db->qn('sa.sla_service_id') . ' > 0'
				)
				->group('st.id')
				->order('st.id DESC');

			$db->setQuery($query);
			$savedTypes = $db->loadObjectList();

			$activityCounts = array();

			foreach ($savedTypes as $type)
			{
				$activityCounts[$type->typeId] = (int) $type->activityCount;
			}

			return $activityCounts;
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return array();
		}
	}
	
	/**
	 * This method will return the number of licences associated with a given parent licence ID.
	 *
	 * @param   int  $parentLicenceId  ID of the parent licence.
	 * 
	 * @return  int  The count of licences plus one (for multiple licence tracking).
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getMultipleLicenceCount($parentLicenceId)
	{
		try
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);

			$query->select('count(id) as multiple_count')
				->from($db->quoteName('#__tjmultiagency_licences'))
				->where($db->quoteName('parent_id') . " =  ".(int) $parentLicenceId);

			$db->setQuery($query);
			$licenceIdCount = $db->loadObjectList();


			return $licenceIdCount[0]->multiple_count+1;

		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return array();
		}
	}

	/**
	 * Method to get active licence and SLA data for a multiagency.
	 *
	 * @param   integer  $multiagencyId  The multiagency ID.
	 *
	 * @return  object|boolean  Licence and SLA data or false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getLicenceSlaData($multiagencyId)
	{
		try
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);

			$query->select('l.id, sla.title as sla_name')
				->from($db->qn('#__tjmultiagency_licences', 'l'))
				->join('LEFT', $db->qn('#__tj_sla_cluster_xref', 'sxref') . ' ON sxref.license_id = l.id')
				->join('LEFT', $db->qn('#__tj_slas', 'sla') . ' ON sla.id = sxref.sla_id')
				->where($db->qn('l.multiagency_id') . ' = ' . (int) $multiagencyId)
				->where($db->qn('l.state') . ' = 1');

			$db->setQuery($query);

			return $db->loadObject();
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return false;
		}
	}
}
