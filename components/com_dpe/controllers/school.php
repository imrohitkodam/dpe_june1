<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

/**
 * School controller class.
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeControllerSchool extends FormController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional
	 * @param   array   $config  Configuration array for model. Optional
	 *
	 * @return object	The model
	 *
	 * @since	__DEPLOY_VERSION__
	 */
	public function &getModel($name = 'School', $prefix = 'DpeModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * Method to delete data
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function delete()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$model = $this->getModel();

		$app       = Factory::getApplication();
		$modelName = $app->input->getString('entityName', '');
		$pk        = $app->input->getInt('entityRecordId');

		if (!$pk || $modelName == '')
		{
			$this->setMessage(Text::_('COM_DPE_RECORD_DOESNT_EXIST'), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_dpe&view=schools' . '&Itemid=' . $app->getMenu()->getActive()->id, false));

			return false;
		}

		// Attempt to save the data
		try
		{
			if ($model->delete($pk, $modelName))
			{
				if ($modelName == 'MultiagencyForm')
				{
					$this->setMessage(Text::sprintf('COM_DPE_SCHOOL_DELETED_SUCCESSFULLY', Text::_('COM_MULTIAGENCY_ORGANISATION')));
				}
				elseif ($modelName == 'LicenceForm')
				{
					$this->setMessage(Text::_('COM_DPE_LICENCE_DELETED_SUCCESSFULLY'));
				}
			}
			else
			{
				$this->setMessage($model->getError(), 'warning');
			}
		}
		catch (Exception $e)
		{
			$errorType = ($e->getCode() == '404' || '403') ? 'error' : 'warning';
			$this->setMessage($e->getMessage(), $errorType);
		}

		$this->setRedirect(Route::_('index.php?option=com_dpe&view=schools' . '&Itemid=' . $app->getMenu()->getActive()->id, false));
	}

	/**
	 * Method to archive licence
	 *
	 * @return  void
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function archiveLicence()
	{
		$app          = Factory::getApplication();
		$pk           = $app->input->getInt('entityRecordId');
		$redirectLink = 'index.php?option=com_dpe&view=schools' . '&Itemid=' . $app->getMenu()->getActive()->id;

		if (!$pk)
		{
			$this->setMessage(Text::_('COM_MULTIAGENCY_LICENCEID_DOESNT_EXIST'), 'warning');
			$this->setRedirect(Route::_($redirectLink, false));

			return false;
		}

		$model = $this->getModel();

		if ($model->archiveLicence($pk))
		{
			$app->enqueueMessage(Text::_('COM_MULTIAGECNY_LICENCE_ARCHIVE_SUCCESSFULLY'), 'success');
		}
		else
		{
			$app->enqueueMessage($model->getError(), 'error');
		}

		$app->redirect(Route::_($redirectLink, false));
	}

	/**
	 * Method to get agency user Job title list
	 *
	 * @return json data
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getJobTitleByCluster()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app      = Factory::getApplication();
		$agencyId = $app->input->get('agencyId', 0, 'INT');

		// Create Helper Object
		BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/components/com_dpe/models');
		$schoolModel = BaseDatabaseModel::getInstance('School', 'DpeModel');

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_cluster/models');
		$clusterModel = BaseDatabaseModel::getInstance('Cluster', 'ClusterModel');
		$clusterId   = $clusterModel->getClusterByClient($client=null, $agencyId);

		// Get Licence Assigned role ids
		$jobTitle = $schoolModel->getJobTitlesByClusterId($clusterId->id);	
		$options  = '<option value="">'.Text::_('COM_MULTIAGENCY_SELECT_TITLE_OPTION').'</option>';

		foreach($jobTitle as $value)
		{
			
				 $options .=  '<option value="' . $value->id . '">'.$value->value . '</option>'	;
		}

		echo new JsonResponse($options);
		$app->close();
	}

	/**
	 * Method to get user Jobtitle details
	 *
	 * @return json data
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getJobtitleByuserDetails()
	{
		$app      = Factory::getApplication();
		$clusterId = $app->input->get('agencyId', 0, 'INT');
		$userid = $app->input->get('userid', 0, 'INT');

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
		$clustertableInstance  = Table::getInstance('Clusters', 'ClusterTable');
		$clustertableInstance->load(array('client_id' => $clusterId));
		$clusterId    = $clustertableInstance->id;

		// Create Model Object
		BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/components/com_dpe/models');
		$schoolModel = BaseDatabaseModel::getInstance('School', 'DpeModel');

		// Get Licence Assigned role ids
		$jobTitle = $schoolModel->getJobTitlebyUserData($clusterId,$userid);

		echo new JsonResponse($jobTitle);
		$app->close();
	}

	// Jobtitle End;
	
	/** Method to get agency tags list
	 *
	 * @return array tags list
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getTagsList()
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models');
		$model = BaseDatabaseModel::getInstance('school', 'DpeModel');   

		return $model->getAgencyTags();	
	}

	/** Method to get agency tags list
	 *
	 * @return Json Array tags list
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getJsonTagsList()
	{	
		$app      = Factory::getApplication();
		$userRole = $app->input->get('role', null, 'STRING');

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models');
		$model = BaseDatabaseModel::getInstance('school', 'DpeModel');
		
		$tags  = $model->getAgencyTags($userRole);

		echo new JsonResponse($tags);
		$app->close();
	}
	
	/** Method to get licence details
	 *
	 * @return Json Array licence details
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getLicenceDetails()
	{
		$app    = Factory::getApplication();
		$input  = $app->input;
		$licenceId = $input->getInt('id');

		// Include Multiagency model
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models', 'MultiagencyModel');
		$licenceModel = BaseDatabaseModel::getInstance('Licences', 'MultiagencyModel');

		// Include Multiagency table
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
		$licenceTable = Table::getInstance('Licence', 'MultiagencyTable');
		$licenceTable->load(['id' => $licenceId]);
		$data = (array) $licenceTable->getProperties();

		// Include SLA Xref table
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sla/tables');
		$xrefTable = Table::getInstance('SlaClusterXrefs', 'SlaTable');
		$xrefTable->load(['license_id' => $licenceId]);
		$xrefLicenceData = (array) $xrefTable->getProperties();

		// Merge Xref data
		$data['notify_dpe_admin'] = $xrefLicenceData['notify_dpe_admin'];
		$data['dpeadmins']        = json_decode($xrefLicenceData['dpeadmins'] ?? '[]', true);
		$data['sla_id']           = $xrefLicenceData['sla_id'];
		$data['licence_type']     = $licenceData['type'];
		$data['checked_out']      = '';
		$data['checked_out_time'] = '';

		$model = $this->getModel();

		// Get tools and activity counts
		$data['tools']    = $model->getLicenceToolsList($licenceId);
		$data['activity'] = $model->getLicenceActivityCounts($licenceId);

		// Remove unnecessary keys
		$unsetKeys = ['id', 'state', 'ordering', 'asset_id', 'created_by', 'modified_by', 'type', 'used_seats','typeAlias'];
		foreach ($unsetKeys as $key) {
			unset($data[$key]);
		}

		// Handle start and end date
		if (($data['parent_id']==0) || $xrefLicenceData['params'] == null) {

			$originalStart = new DateTime($data['start_date']);
			$originalEnd   = new DateTime($data['end_date']);
			$daysDiff      = (int)$originalStart->diff($originalEnd)->format('%a');

			$newStart = new DateTime('today');
			$newEnd   = clone $newStart;
			$newEnd->modify("+{$daysDiff} days");

			$data['start_date'] = $newStart->format('d-m-Y');
			$data['end_date']   = $newEnd->format('d-m-Y');
		} else {
			// Multiple licence logic
			$multiple_count = $model->getMultipleLicenceCount($data['parent_id']);
			$params         = json_decode($xrefLicenceData['params'] ?? '{}', true);

			$duration       = $params['duration'];
			$timeMeasure    = $params['time_measure'];

			$newStart       = new DateTime('today');

			$data['start_date']       = $newStart->format('d-m-Y');
			$data['multiyearlicence'] = 1;
			$data['time_measure']     = $timeMeasure;
			$data['duration']         = $duration;
			$data['multiple_count']   = $multiple_count;
			$data['end_date']         = '';
		}

		// Ensure course_id is set
		if (empty($data['course_id'])) {
			$data['course_id'] = 0;
		}

		// Return JSON response
		echo new JsonResponse($data);
		$app->close();
	}

}
