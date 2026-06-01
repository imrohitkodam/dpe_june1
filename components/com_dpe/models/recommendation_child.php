<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Dpe
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2019. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;
use TJQueue\Admin\TJQueueProduce;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;

require_once JPATH_SITE . '/components/com_tjucm/includes/access.php';

jimport('joomla.application.component.modelitem');
jimport('joomla.event.dispatcher');
jimport('techjoomla.tjnotifications.tjnotifications');

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;

require_once JPATH_SITE . "/components/com_jlike/models/recommendation.php";

/**
 * JlikeModelrecommendation_Child model.
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeModelrecommendation_Child extends JlikeModelRecommendation
{
	/**
	 * Method to assign user.
	 *
	 * @param   array    $data    todo data
	 * @param   boolean  $notify  Allow notification flag.
	 *
	 * @return  boolean  True on success, False on error.
	 */
	public function setTodo($data, $notify = false)
	{
		// Load contentform model to get content id
		JLoader::import('contentform', JPATH_SITE . '/components/com_jlike/models');
		$contentId          = JlikeModelContentForm::getContentID($data);
		$data['content_id'] = $contentId;

		$result = self::save($data);

		if (!$result)
		{
			return false;
		}

		// If notify is true then send notification on after assign the content
		if ($notify)
		{
			$client = "jlike";
			$key    = "assignContent";

			$recipients = array (
				// Add specific to, cc (optional), bcc (optional)
				'email' => array (
					'to' => array (Factory::getUser($data['assigned_to'])->email)
				)
			);

			$app          = Factory::getApplication();
			$config       = Factory::getConfig();
			$mailfrom     = $config->get('mailfrom');
			$fromname     = $config->get('fromname');

			// Get user data
			$userInfo = Factory::getUser($data['assigned_to']);

			// Get assigner data
			$assignerInfo = Factory::getUser($data['assigned_by']);

			// Get content data
			$JlikeModelContentForm = new JlikeModelContentForm;
			$contentData           = $JlikeModelContentForm->getData((int) $contentId);

			// DPE - Hack - Send Lesson Description in assign document email.
			if (isset($contentData->element) && $contentData->element == "com_tjlms.lesson")
			{
				JLoader::import('lesson', JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
				$lessonTable = Table::getInstance('lesson', 'TjlmsTable', array());
				$lessonTable->load(array('id' => (int) $contentData->element_id));

				$contentData->lessonDesc = '';

				if (is_object($lessonTable) && property_exists($lessonTable, 'description')&& !empty($lessonTable->description))
				{
					$contentData->lessonDesc = Text::_('COM_DPE_ASSIGNED_LESSON_DESCRIPTION') . strip_tags($lessonTable->description);
				}
			}

			// DPE - Hack - Route course,lesson URL for showing in the email notification.
			$contentData->url       = Uri::root() . substr(Route::_($contentData->url), strlen(Uri::base(true)) + 1);

			if(str_contains($contentData->url, 'cli') )
			{
				 $contentData->url = Route::_($data['url']);	
				 $contentData->url = 'https://dataprotection.education/'.preg_replace('/^.*?(index)/', 'index', $contentData->url);
			}

			$replacements           = new stdClass;
			$replacements->user     = $userInfo;
			$replacements->assigner = $assignerInfo;
			$replacements->content  = $contentData;

			$options = new Registry;
			$options->set('subject', $contentData);
			$options->set('from', $mailfrom);
			$options->set('fromname', $fromname);

			Tjnotifications::send($client, $key, $recipients, $replacements, $options);

			// TRIGGER After Recommendation
			PluginHelper::importPlugin('system');
			Factory::getApplication()->triggerEvent('onAfterRecommendation', array($data));

			// store the todo in cluster xref table if the document assigned to the user in compliance manager
			//Store cluster id and todo relation
			$todoClusterXrefTable = Jlike::table('TodosClusterXref');
			$clusterXrefobj             = new stdClass;
			$clusterXrefobj->todo_id    = $this->todoId;
			$clusterXrefobj->cluster_id = $data['clusterId'];

			$todoClusterXrefTable->save($clusterXrefobj);
		}

		return true;
	}


	/**
	 * Method to used Copies the file data from the queue.
	 *
	 * This method processes the given record data and copies 
 	 * the file information from the queue to the designated location.
	 *
	 * @param   array  $messageData  record data
	 *
	 * @return  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function copyItem($messageData=null)
	{

		$sourceClient = $messageData['sourceClient'];
		$filter       = $messageData['filter']; 
		$targetClient = ($filter)?$filter['target_ucm']:''; // dpe hack php test8.1

		// DPE hack - can go in core
		$fieldGroupValues = $messageData['fieldGroupValues'];
		$isMasterList     = $messageData['isMasterList'];
		$recordTitle      = $messageData['recordTitle'];

		$params              = ComponentHelper::getParams('com_dpe');
		$codeDataFieldConfig = json_decode($params->get('coredatatitlefields'), true);

		if (!empty($sourceClient) && array_key_exists($sourceClient, $codeDataFieldConfig))
		{
			$fieldUniqueName = $codeDataFieldConfig[$sourceClient];
		}

		if (!$targetClient)
		{
			$targetClient = $sourceClient;
		}

		// Get Clusers list and conver to array
		$clusterIds = $messageData['clusterIds'];
		$clusterIds = explode(',', $clusterIds);

		// DPE Hack - Check RBACL check
		$db = Factory::getDbo();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjucm/tables');
		$typeTable = Table::getInstance('Type', 'TjucmTable', array('dbo', $db));

		if ($targetClient)
		{
			$typeTable->load(array('unique_identifier' => $targetClient));

			if (property_exists($typeTable, 'id'))
			{
				$ucmTypeId = $typeTable->id;
			}
		}

		foreach ($clusterIds as $clusterId)
		{
			$canCopyItem = TjucmAccess::canCopyItem($ucmTypeId, $messageData['userId'], $clusterId);

			if (!$canCopyItem)
			{
				return false;
			}
		}

		// Load required models
		JLoader::import('components.com_tjucm.models.type', JPATH_ADMINISTRATOR);
		$typeModel = BaseDatabaseModel::getInstance('Type', 'TjucmModel');

		if ($sourceClient != $targetClient)
		{
			// Server side Validation for source and UCM Type
			$result = $typeModel->getCompatibleUcmTypes($sourceClient, $targetClient);
		}
		else
		{
			$result = true;
		}

		if ($result)
		{
			$copyIds = $messageData['copyIds'];

			if (empty($copyIds)) {
				return false;
			}

			JLoader::import('components.com_tjfields.helpers.tjfields', JPATH_SITE);
			$tjFieldsHelper = new TjfieldsHelper;

			if (count($copyIds))
			{
				JLoader::import('models.itemform', JPATH_SITE . '/components/com_tjucm');
				$model = new TjucmModelItemForm();

					$model->setClient($targetClient);

					$tempPath = JPATH_SITE . '/tmp/copy_message_' . $messageData['userId'] . '.txt';

					foreach ($clusterIds as $clusterId)
					{

						foreach ($copyIds as $cid)
						{
							$ucmOldData = array();
							$ucmOldData['clientComponent'] = 'com_tjucm';
							$ucmOldData['content_id'] = $cid;
							$ucmOldData['layout'] = 'edit';
							$ucmOldData['client']     = $sourceClient;
							$fileFieldArray = array();

							// Get the field values
							$extraFieldsData = $model->loadFormDataExtra($ucmOldData);

							// Code to replace source field name with destination field name
							foreach ($extraFieldsData as $fieldKey => $fieldValue)
							{
								$prefixSourceClient = str_replace(".", "_", $sourceClient);
								$fieldName = explode($prefixSourceClient . "_", $fieldKey);
								$prefixTargetClient = str_replace(".", "_", $targetClient);
								$targetFieldName = $prefixTargetClient . '_' . $fieldName[1];
								$tjFieldsTable = $tjFieldsHelper->getFieldData($targetFieldName);
								$fieldId = $tjFieldsTable->id;
								$fieldType = $tjFieldsTable->type;
								$fielParams = json_decode($tjFieldsTable->params);
								$sourceTjFieldsTable = $tjFieldsHelper->getFieldData($fieldKey);
								$sourceFieldParams = json_decode($sourceTjFieldsTable->params);
								$subFormData = array();

								// DPE hack can go in core
								if (!empty($fieldGroupValues) && (!in_array($tjFieldsTable->group_id, $fieldGroupValues)))
								{
									unset($extraFieldsData[$fieldKey]);
									continue;
								}

								if ($tjFieldsTable->type == 'ucmsubform' || $tjFieldsTable->type == 'subform')
								{
									$params = json_decode($tjFieldsTable->params)->formsource;
									$subFormClient = explode('components/com_tjucm/models/forms/', $params);
									$subFormClient = explode('form_extra.xml', $subFormClient[1]);
									$subFormClient = 'com_tjucm.' . $subFormClient[0];

									$params = $sourceFieldParams->formsource;
									$subFormSourceClient = explode('components/com_tjucm/models/forms/', $params);
									$subFormSourceClient = explode('form_extra.xml', $subFormSourceClient[1]);
									$subFormSourceClient = 'com_tjucm.' . $subFormSourceClient[0];

									$subFormData = (array) json_decode($fieldValue);
								}

								if ($subFormData)
								{
									foreach ($subFormData as $keyData => $data)
									{
										$prefixSourceClient = str_replace(".", "_", $sourceClient);
										$fieldName = explode($prefixSourceClient . "_", $keyData);
										$prefixTargetClient = str_replace(".", "_", $targetClient);
										$subTargetFieldName = $prefixTargetClient . '_' . $fieldName[1];
										$data = (array) $data;

										foreach ((array) $data as $key => $d)
										{
											$prefixSourceClient = str_replace(".", "_", $subFormSourceClient);
											$fieldName = explode($prefixSourceClient . "_", $key);
											$prefixTargetClient = str_replace(".", "_", $subFormClient);
											$subFieldName = $prefixTargetClient . '_' . $fieldName[1];

											Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
											$fieldTable = Table::getInstance('field', 'TjfieldsTable');

											$fieldTable->load(array('name' => $key));

											if ($fieldName[1] == 'contentid')
											{
												$d = '';
											}

											$temp = array();
											unset($data[$key]);

											if (is_array($d))
											{
												// TODO Temprary used switch case need to modify code
												switch ($fieldTable->type)
												{
													case 'multi_select':
														foreach ($d as $option)
														{
															$temp[] = $option->value;
														}

														if (!empty($temp))
														{
															$data[$subFieldName] = $temp;
														}
													break;

													case 'tjlist':
													case 'related':

														foreach ($d as $option)
														{
															$data[$subFieldName][] = $option;
														}
													break;

													default:
														foreach ($d as $option)
														{
															$data[$subFieldName] = $option->value;
														}
													break;
												}
											}
											elseif($fieldTable->type == 'file' || $fieldTable->type == 'image')
											{
												Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
												$subDestionationFieldTable = Table::getInstance('field', 'TjfieldsTable');

												$subDestionationFieldTable->load(array('name' => $subFieldName));

												$subformFileData = array();
												$subformFileData['value'] = $d;
												$subformFileData['copy'] = true;
												$subformFileData['type'] = $fieldTable->type;
												$subformFileData['sourceClient'] = $subFormSourceClient;
												$subformFileData['sourceFieldUploadPath'] = json_decode($fieldTable->params)->uploadpath;
												$subformFileData['destFieldUploadPath'] = json_decode($subDestionationFieldTable->params)->uploadpath;
												$subformFileData['user_id'] = Factory::getUser()->id;
												$data[$subFieldName] = $subformFileData;
											}
											elseif ($fieldTable->type == 'cluster')
											{
												$data[$subFieldName] = $clusterId;
											}
											else
											{
												$data[$subFieldName] = $d;
											}
										}

										unset($subFormData[$keyData]);
										$subFormData[$subTargetFieldName] = $data;
									}

									unset($extraFieldsData[$fieldKey]);
									$extraFieldsData[$targetFieldName] = $subFormData;
								}
								else
								{
									unset($extraFieldsData[$fieldKey]);

									if ($fieldType == 'file' || $fieldType == 'image')
									{
										$fileData = array();
										$fileData['value'] = $fieldValue;
										$fileData['copy'] = true;
										$fileData['type'] = $fieldType;
										$fileData['sourceClient'] = $sourceClient;
										$fileData['sourceFieldUploadPath'] = $sourceFieldParams->uploadpath;
										$fileData['destFieldUploadPath'] = $fielParams->uploadpath;
										$fileData['user_id'] = Factory::getUser()->id;
										$extraFieldsData[$targetFieldName] = $fileData;
									}
									elseif($fieldType == 'cluster')
									{
										$extraFieldsData[$targetFieldName] = $clusterId;
									}
									else
									{
										$extraFieldsData[$targetFieldName] = $fieldValue;
									}

									if ($tjFieldsTable->name === $fieldUniqueName && !empty($recordTitle))
									{
										$extraFieldsData[$targetFieldName] = $recordTitle;
									}
								}
							}

							$ucmData = array();
							$ucmData['id']        = 0;
							$ucmData['client']    = $targetClient;
							$ucmData['parent_id'] = 0;
							$ucmData['created_by']= $messageData['userId'];

							// DPE hack - Publish records when we copy for core data ucm type

							// Copy state of record
							$ucmData['state'] = 1;
							$ucmData['draft'] = 0;

							if ($clusterId)
							{
								$ucmData['cluster_id']	 	= $clusterId;
							}

							// Save data into UCM data table
							$result = $model->save($ucmData);

							$recordId = $model->getState($model->getName() . '.id');


							// Insert Copied Record Parent Child Relationship

							PluginHelper::importPlugin('tjucmdpe');
							Factory::getApplication()->triggerEvent('onInsertCopyTrackingRecord',array($clusterId, $cid, $recordId));

							if ($recordId)
							{
								$formData = array();
								$formData['content_id'] = $recordId;
								$formData['fieldsvalue'] = $extraFieldsData;
								$formData['client'] = $targetClient;

								// If data is valid then save the data into DB
								$response = $model->saveExtraFields($formData);

								$result = ($response) ? true : false;

							}
						}
					}
			}
			if($result){
				// Extract part after 'com_tjucm.'
				$client = str_replace('com_tjucm.', '', $messageData['sourceClient']);

				// Save the success message to a temporary file specific to the user
				$message = 'The '.$client.' records have been copied successfully.';
					
				$tempPath = JPATH_SITE . '/tmp/copy_message_' . $messageData['userId'] . '.txt';
				file_put_contents($tempPath, $message);

				// Send mail after copied records
				PluginHelper::importPlugin('tjucmdpe');
				Factory::getApplication()->triggerEvent('onSendSuccessFailCopyItemEmails',array($clusterIds, $messageData['sourceClient'], $messageData['userId'] , $result));
			}
			return $result;
		}
	}
	
}
