<?php
/**
 * @package    DPE
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Filter\InputFilter;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\ComponentHelper;
JLoader::import('components.com_dpe.includes.dpe', JPATH_SITE);

/**
 * Class SubusersController
 *
 * @since  1.0.0
 */

class DpeController extends BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   mixed    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link InputFilter::clean()}.
	 *
	 * @return   JController This object to support chaining.
	 *
	 * @since    1.0.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		// Set the default view for the component
		$app  = Factory::getApplication();
		$view = $app->input->getCmd('view', 'user_dashboards');
		$app->input->set('view', $view);

		parent::display($cachable, $urlparams);

		return $this;
	}

	/**
	 * Redirect user after copying embed code.
	 * 
	 * @return void
	 */
	public function embedCopyRedirect()
	{
		$app      = Factory::getApplication();
		$itemId   = $app->input->getInt('Itemid');
		$clusterId = $app->input->getInt('clusterId');

		$this->setMessage(Text::_('COM_DPE_EMBED_CODE_COPIED_SUCCESSFULLY'));
		$this->setRedirect(Route::_('index.php?option=com_tjlms&view=managelessons&Itemid=' . $itemId, false));
	}

	/**
	 * Function to delete document
	 * Todo : This function will be change after Shika upgrade
	 *
	 * @return void
	 */
	public function deleteDocument()
	{
		$app  = Factory::getApplication();
		$user = Factory::getUser();
		$itemId = $app->input->getInt('Itemid');
		$clusterId = $app->input->getInt('clusterId');

		// Check com_tjlms delete permission

		if (!$user->authorise('core.delete', 'com_tjlms'))
		{
			$app->enqueueMessage(Text::_('COM_DPE_DELETE_DOCUMENT_ERROR'), 'error');
			$this->setRedirect(Route::_('index.php?option=com_tjlms&view=managelessons&Itemid=' . $itemId, false));

			return false;
		}

		// Check can delete document
		if (ComponentHelper::getComponent('com_subusers', true)->enabled)
		{
			JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

			if (!$user->authorise('core.manageall', 'com_cluster') && !RBACL::check($user->id, 'com_cluster', 'core.delete.lesson', 'com_multiagency', $clusterId))
			{
				$app->enqueueMessage(Text::_('COM_DPE_DELETE_DOCUMENT_ERROR'), 'error');
				$this->setRedirect(Route::_('index.php?option=com_tjlms&view=managelessons&Itemid=' . $itemId, false));

				return false;
			}
		}

		// Check the document associated with logged in school admin's school

		$lessonId = $app->input->getInt('id');

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
		$clustertableInstance = Table::getInstance('TjlmsClusterXref', 'DpeTable');
		$clustertableInstance->load(array('lesson_id' => $lessonId));

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			// Check logged-in user is a member of cluster and having permission
			if (!RBACL::check($user->id, 'com_cluster', 'core.manage.lessons', 'com_tjlms', $clustertableInstance->cluster_id))
			{
				$app->enqueueMessage(Text::_('COM_DPE_DELETE_DOCUMENT_ERROR'), 'error');
				$this->setRedirect(Route::_('index.php?option=com_tjlms&view=managelessons&Itemid=' . $itemId, false));

				return false;
			}
		}

		JLoader::import('helper', JPATH_SITE . '/components/com_jlike');
		$jlikeHelper = new ComjlikeHelper;

		$contentId = $jlikeHelper->getContentId($lessonId, 'com_tjlms.lesson');

		JLoader::import('models.lesson', JPATH_ADMINISTRATOR . '/components/com_tjlms');
		$tjlmsLessonModel = new TjlmsModelLesson;

		$return = $tjlmsLessonModel->delete($lessonId);

		// Redirect to the list screen
		if ($return)
		{
			// Get todos to delete all the assigned entries for the content
			JLoader::import('models.recommendations', JPATH_SITE . '/components/com_jlike');
			$JLikeRecommendationModel = new JlikeModelRecommendations;
			$JLikeRecommendationModel->setState("content_id", $contentId);
			$todos = $JLikeRecommendationModel->getItems();

			if ($contentId)
			{
				JLoader::import('models.recommendation', JPATH_SITE . '/components/com_jlike');
				$jlikeModelRecommendation = new JlikeModelRecommendation;

				// Delete entries form todo extended

				Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
				$todoExtendTable = Table::getInstance('TodosExtend', 'DpeTable');

				foreach ($todos as $todo)
				{
					$jlikeModelRecommendation->delete($todo->id);
					$todoExtendTable->delete($todo->id);
				}
			}

			// Delete entry from content table
			JLoader::import('models.content', JPATH_SITE . '/components/com_jlike');
			$jlikeModelContent = new JlikeModelContent;
			$jlikeModelContent->delete($contentId);

			// Delete entry from #__tjlms_lesson_cluster_xref table
			if (!empty($clustertableInstance->id))
			{
				$clustertableInstance->delete();
			}

			$this->setMessage(Text::_('COM_DPE_LESSON_DELETED_SUCCESSFULLY'));
		}

		$this->setRedirect(Route::_('index.php?option=com_tjlms&view=managelessons&Itemid=' . $itemId, false));
	}

	/**
	 * Method to import master records
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since 1.0
	 */
	public function csvImport()
	{
		jimport('joomla.html.html');
		jimport('joomla.filesystem.file');
		jimport('joomla.utilities.date');

		$user       = Factory::getUser();
		$app        = Factory::getApplication();
		$input      = $app->input;

		// Get the user data.
		$data      = $input->get('jform', array(), 'array');
		$clusterId = $data['cluster_id'];
		$ucmClient = $input->get('client');
		$viewInteractions = true;

		// Check permission to import
		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

			$viewInteractions = RBACL::check($user->id, 'com_cluster', 'core.view.interactions', 'com_tjlms', $clusterId);
		}

		if (!$viewInteractions)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

			return false;
		}

		$config     = Factory::getConfig();
		$fileArray  = $input->files->get('csvfile');

		// Start file heandling functionality *
		$fileName = File::stripExt($fileArray['name']);

		if (empty($fileName) || empty($clusterId))
		{
			$app->enqueueMessage(Text::_('COM_DPE_ERROR_IN_FILE_UPLOAD'), 'error');
			$this->setRedirect(Route::_('index.php?option=com_dpe&view=import&tmpl=component&client=' . $ucmClient, false));

			return false;
		}

		File::makeSafe($fileName);

		$uploads_dir = $config->get('tmp_path') . '/' . $fileArray['name'];

		/*
		if ($fileArray['type'] != 'text/csv')
		{
			$app->enqueueMessage(Text::_('COM_DPE_NOT_CSV_MSG'), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_dpe&view=import&tmpl=component&client=' . $ucmClient, false));

			return;
		}
		*/

		if (!File::upload($fileArray['tmp_name'], $uploads_dir))
		{
			$app->enqueueMessage(Text::_('COM_DPE_ERROR_IN_FILE_UPLOAD'), 'warning');

			return;
		}

		if ($file = fopen($uploads_dir, "r"))
		{
			$ext = File::getExt($uploads_dir);

			if ($ext != 'csv')
			{
				$app->enqueueMessage(Text::_('COM_DPE_NOT_CSV_MSG'), 'warning');
				$this->setRedirect(Route::_('index.php?option=com_dpe&view=import&tmpl=component&client=' . $ucmClient, false));

				return;
			}

			$rowNum = 0;
			$headers = array();

			while (($data = fgetcsv($file)) !== false)
			{
				// Parsing the CSV header

				if ($rowNum == 0)
				{
					foreach ($data as $d)
					{
						$headers[] = $d;
					}
				}
				else
				{
					// Parsing the data rows
					$rowData = array();

					foreach ($data as $d)
					{
						$rowData[] = $d;
					}

					$masterData[] = array_combine($headers, $rowData);
				}

				$rowNum++;
			}

			fclose($file);
		}
		else
		{
			$app->enqueueMessage(Text::_('COM_DPE_SOME_ERROR_OCCURRED'), 'error');

			$this->setRedirect(Route::_('index.php?option=com_dpe&view=import&tmpl=component&client=' . $ucmClient, false));

			return;
		}

		$output               = array();
		$output['return']     = 1;
		$output['successmsg'] = '';
		$output['errormsg']   = '';
		$offset               = $config->get('offset');

		if (empty($masterData))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('COM_DPE_IMPORT_BLANK_FILE'), 'error');
			$this->setRedirect(Route::_('index.php?option=com_dpe&view=import&tmpl=component&client=' . $ucmClient, false));

			return false;
		}

		$success = 0;

		if (!empty($masterData))
		{
			$totalEvents             = count($masterData);
			$validData               = array();
			$validData['client']     = $ucmClient;
			$validData['state']      = 1;
			$validData['created_by'] = $user->id;
			$validData['draft']      = 0;
			$validData['status']     = 'save';
			$validData['cluster_id'] = $clusterId;

			$data         = array();
			$clusterField = '';

			JLoader::import('models.itemform', JPATH_SITE . '/components/com_tjucm');
			$tjucmModelItemForm = new TjucmModelItemForm;

			// To get type of UCM
			if (!empty($ucmClient))
			{
				$client       = explode(".", $ucmClient);
				$clusterField = $client[0] . '_' . $client[1] . '_clusterclusterid';
			}

			$data[$clusterField] = $clusterId;

			foreach ($masterData as $eachType)
			{
				foreach ($eachType as $key => $value)
				{
					if ($ucmClient === 'com_tjucm.ithardware')
					{
						switch ($key)
						{
							case 'Name' :

								if (!empty ($value))
								{
									$data['com_tjucm_ithardware_hardwaretype'] = $value;
								}

							break;

							case 'Deployment year' :

								if (!empty ($value))
								{
									$data['com_tjucm_ithardware_deploymentyear'] = $value;
								}

							break;

							default :

								$data['extra_jform_data'] = $value;

							break;
						}
					}

					if ($ucmClient === 'com_tjucm.software')
					{
						switch ($key)
						{
							case 'Name' :

								if (!empty ($value))
								{
									$data['com_tjucm_software_name'] = $value;
								}

							break;

							case 'Version' :

								if (!empty ($value))
								{
									$data['com_tjucm_software_version'] = $value;
								}

							break;

							default :

								$data['extra_jform_data'] = $value;

							break;
						}
					}

					if ($ucmClient === 'com_tjucm.role')
					{
						switch ($key)
						{
							case 'Name' :

								if (!empty ($value))
								{
									$data['com_tjucm_role_name'] = $value;
								}

							break;

							default :

								$data['extra_jform_data'] = $value;

							break;
						}
					}

					if ($ucmClient === 'com_tjucm.ropvendors')
					{
						switch ($key)
						{
							case 'Organisation Name' :

								if (!empty ($value))
								{
									$data['com_tjucm_ropvendors_organisationname'] = $value;
								}

							break;

							case 'Ico-registered' :

								if (!empty ($value))
								{
									$data['com_tjucm_ropvendors_istheorganisationregisteredwiththeico'] = $value;
								}

							break;

							case 'Contract in place' :

								if (!empty ($value))
								{
									$data['com_tjucm_ropvendors_isthereacontractinplacewiththisorganisation'] = $value;
								}

							break;

							case 'Act on instructions' :

								if (!empty ($value))
								{
									$data['com_tjucm_ropvendors_actinstructions'] = $value;
								}

							break;

							case 'Data confidentiality' :

								if (!empty ($value))
								{
									$data['com_tjucm_ropvendors_doesthecontractincludetermsondataconfidentiality1'] = $value;
								}

							break;

							case 'Security standards' :

								if (!empty ($value))
								{
									$data['com_tjucm_ropvendors_aretheorganisationsinformationsecuritystandardsincludedinthecontract'] = $value;
								}

							break;

							case 'Require sub-processor' :

								if (!empty ($value))
								{
									$data['com_tjucm_ropvendors_doesthecontractrequiretheuseofasubprocessor'] = $value;
								}

							break;

							case 'Sub processor approval' :

								if (!empty ($value))
								{
									$data['com_tjucm_ropvendors_doesthecontrollerapproveandauthorisethesubprocessorsinthecontract'] = $value;
								}

							break;

							case 'Data deleted or returned' :

								if (!empty ($value))
								{
									$data['com_tjucm_ropvendors_datawillbedeletedorreturnedstorageisrequiredbylaw'] = $value;
								}

							break;

							case 'Agree to audits and inspections' :

								if (!empty ($value))
								{
									$data['com_tjucm_ropvendors_doestheprocessoragreetoauditsandinspections'] = $value;
								}

							break;

							case 'Article 28 obligations' :

								if (!empty ($value))
								{
									$data['com_tjucm_ropvendors_informationitneedstoensuretheyarebothmeetingtheirarticle28obligations'] = $value;
								}

							break;

							default :

								$data['extra_jform_data'] = $value;

							break;
						}
					}
				}

				if ($tjucmModelItemForm->save($validData, $data))
				{
					$success ++;
				}
			}
		}

		if ($success > 0)
		{
			$app->enqueueMessage(Text::sprintf('COM_DPE_MASTER_RECORDS_IMPORT_SUCCESSFULLY', $success), 'success');
		}

		$this->setRedirect(Route::_('index.php?option=com_dpe&view=import&tmpl=component&client=' . $ucmClient, false));

		return true;
	}

	/**
	 * Function to migrate the document todo
	 *
	 * @return void
	 */
	public function migrateLessonTodo()
	{
		$tjlmsLessonHelper = new tjlmsLessonHelper;
		$app = Factory::getApplication();
		$limit = $app->input->get('limit', 15, 'uint');
		$start = $app->input->get('start', 0, 'uint');

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__jlike_content'));
		$query->where($db->quoteName('element') . ' = ' . $db->quote('com_tjlms.lesson'));
		$db->setQuery($query, $start, $limit);
		$lessons = $db->loadObjectList();

		if (empty($lessons))
		{
			die("Content migrated successfully");
		}

		jimport('joomla.log.logger.formattedtext');

		// Add the logger.
		Log::addLogger(
				// Pass an array of configuration options
				array(
						'text_file' => 'lessonTodoMigration.log',
						'text_file_path' => 'logs'
				),
				Log::ALL
				);

		Log::add("LESSON_ID |  TODO_ID  | USER_ID | STATUS");

		$jlikeTjlmslessonPlugin = PluginHelper::getPlugin('content', 'jlike_tjlmslesson');
		$enabledInterations = json_decode($jlikeTjlmslessonPlugin->params);

		// Get the todo of lesson
		foreach ($lessons as $lesson)
		{
			$savedInteractions = json_decode($lesson->params);

			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__jlike_todos'));
			$query->where($db->quoteName('content_id') . ' = ' . $db->quote($lesson->id));
			$query->where($db->quoteName('content_id') . ' = ' . $db->quote($lesson->id));
			$db->setQuery($query);
			$todos = $db->loadObjectList();

			// Now for each assigned todos check the lesson status
			foreach ($todos as $todo)
			{
				$statusandscore = $tjlmsLessonHelper->getLessonScorebyAttemptsgrading($lesson->element_id, $todo->assigned_to);

				if (!empty($statusandscore))
				{
					$status = 'I';

					if ($statusandscore->lesson_status == 'completed' || $statusandscore->lesson_status == 'passed')
					{
						$status = "C";
					}

					$query = $db->getQuery(true);
					$query->select('*');
					$query->from($db->quoteName('#__jlike_todos_extended'));
					$query->where($db->quoteName('todo_id') . ' = ' . $db->quote($todo->id));
					$db->setQuery($query);
					$extendedTodos = $db->loadObject();

					$parentStatus = $status;

					$fields = array();

					if (!empty($extendedTodos))
					{
						if ($status == 'I')
						{
							// If the lesson is incompleted and the interaction are completed then revert it
							foreach ($savedInteractions as $key => $value)
							{
								if ($enabledInterations->$key == 1)
								{
									switch ($key)
									{
										case 'read_interaction':
											if ($extendedTodos->read == 1)
											{
												array_push($fields, $db->qn('read') . " = 0");
												$parentStatus = "I";
											}
											break;
										case 'practice_interaction':
											if ($extendedTodos->used == 1)
											{
												array_push($fields, $db->qn('used') . " = 0");
												$parentStatus = "I";
											}
											break;
										case 'consent_interaction':
											if ($extendedTodos->consented == 1)
											{
												array_push($fields, $db->qn('consented') . " = 0");
												$parentStatus = "I";
											}
											break;
									}
								}
							}
						}
						else
						{
							/**
							 * Lesson is completed check whether all the todos are completed or not
							 * If all the todos are completed then update the parent todo status
							 * This is neccessary beacuse we are using the todo status to send a remonsders
							 */
							foreach ($savedInteractions as $key => $value)
							{
								if ($enabledInterations->$key == 1)
								{
									switch ($key)
									{
										case 'read_interaction':
											if ($extendedTodos->read != 1)
											{
												$parentStatus = "I";
											}
											break;
										case 'practice_interaction':
											if ($extendedTodos->used != 1)
											{
												$parentStatus = "I";
											}
											break;
										case 'consent_interaction':
											if ($extendedTodos->consented != 1)
											{
												$parentStatus = "I";
											}
											break;
									}
								}
							}
						}

						// $updateParent extended todos update if the extended todos are changed
						if (!empty($fields))
						{
							$query = $db->getQuery(true);
							$conditions = array($db->quoteName('todo_id') . ' = ' . $todo->id);
							$query->update($db->quoteName('#__jlike_todos_extended'))->set($fields)->where($conditions);
							$db->setQuery($query);
							$db->execute();
							Log::add(" Updating the extended todos " . $lesson->id . " | " . $todo->id . " | " . $todo->assigned_to . " | " . $status);
						}
					}
					else
					{
						$parentStatus = 'I';
					}

					if ($todo->status != $parentStatus)
					{
						$query = $db->getQuery(true);
						$fields = array($db->quoteName('status') . ' = ' . $db->q($parentStatus));
						$conditions = array($db->quoteName('id') . ' = ' . $todo->id);
						$query->update($db->quoteName('#__jlike_todos'))->set($fields)->where($conditions);
						$db->setQuery($query);
						$db->execute();
						Log::add($lesson->id . " | " . $todo->id . " | " . $todo->assigned_to . " | " . $status);
					}
				}
				else
				{
					Log::add("Track not present " . $lesson->id . " | " . $todo->id . " | " . $todo->assigned_to . " | " . $todo->status);
				}
			}
		}

		$start = $limit + $start;
		$app->redirect('index.php?option=com_dpe&task=migrateLessonTodo&limit=' . $limit . '&start=' . $start);
	}

	/**
	 * Cron function to archive expired licence
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function archiveExpiredLicencesCron()
	{
		$params            = ComponentHelper::getParams('com_dpe');
		$privateKeyCronjob = $params->get('private_key_storage_cron');
		$input             = Factory::getApplication()->input;
		$privateKeyInUrl   = $input->get('pkey', '', 'STRING');

		if ($privateKeyCronjob != $privateKeyInUrl)
		{
			echo Text::_("COM_DPE_NOT_AUTHORISED_CRON");

			return;
		}

		// If the user didn't set a timezone, it will return the server timezone
		$tz          = Factory::getUser()->getTimezone();
		$date        = Factory::getDate('now');
		$currentdate = $date->setTimezone($tz);
		$db          = Factory::getDbo();
		$query       = $db->getQuery(true);

		// Query to get activated licesce school(s) of logged in user
		$query->select('ml.id');
		$query->from($db->qn('#__tjmultiagency_licences', 'ml'));
		$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'tm') . ' ON (' . $db->qn('tm.id') . ' = ' . $db->qn('ml.multiagency_id') . ')');
		$query->where($db->quoteName('ml.state') . ' = 1');
		$query->where($db->quoteName('ml.end_date') . ' < ' . $db->quote($currentdate));
		$query->setLimit($params->get('cron_limit'));

		$db->setQuery($query);

		$expiredLicences  = $db->loadObjectList();
		$dpeSchoolModel   = DPE::model('school', array('ignore_request' => true));

		foreach ($expiredLicences as $expiredLicence)
		{
			$dpeSchoolModel->archiveLicence($expiredLicence->id, $privateKeyInUrl);
		}
	}

	/**
	 * Cron function to active upcoming licence
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function activateUpcomingLicencesCron()
	{
		$params            = ComponentHelper::getParams('com_dpe');
		$privateKeyCronjob = $params->get('private_key_storage_cron');
		$input             = Factory::getApplication()->input;
		$privateKeyInUrl   = $input->get('pkey', '', 'STRING');

		if ($privateKeyCronjob != $privateKeyInUrl)
		{
			echo Text::_("COM_DPE_NOT_AUTHORISED_CRON");

			return;
		}

		// If the user didn't set a timezone, it will return the server timezone
		$tz          = Factory::getUser()->getTimezone();
		$date        = Factory::getDate('now');
		$currentdate = $date->setTimezone($tz)->format('Y-m-d 00:00:00');
		$db          = Factory::getDbo();
		$query       = $db->getQuery(true);

		// Query to get activated licesce school(s) of logged in user
		$query->select('ml.id');
		$query->from($db->qn('#__tjmultiagency_licences', 'ml'));
		$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'tm') . ' ON (' . $db->qn('tm.id') . ' = ' . $db->qn('ml.multiagency_id') . ')');

		// Licences is in upcoming state
		$query->where($db->quoteName('ml.state') . ' = 3');
		$query->where($db->quoteName('ml.start_date') . ' = ' . $db->quote($currentdate));
		$query->setLimit($params->get('cron_limit'));

		$db->setQuery($query);

		$upcomingLicences = $db->loadObjectList();
		$dpeSchoolModel   = DPE::model('school', array('ignore_request' => true));

		foreach ($upcomingLicences as $upcomingLicence)
		{
			$dpeSchoolModel->activeLicence($upcomingLicence->id, $privateKeyInUrl);
		}
	}
}
