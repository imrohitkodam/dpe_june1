<?php
/**
 * @package     TJ-UCM
 * @subpackage  com_tjucm
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

/**
 * Item controller class.
 *
 * @since  1.6
 */
class TjucmControllerItem extends BaseController
{
	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$app = Factory::getApplication();

		$this->client = Factory::getApplication()->input->get('client');
		$this->created_by = Factory::getApplication()->input->get('created_by');

		// If client is empty then get client from menu params
		if (empty($this->client)) {
			// Get the active item
			$menuitem = $app->getMenu()->getActive();

			// Get the params
			$this->menuparams = null;
			if ($menuitem) {
				$this->menuparams = is_callable(array($menuitem, 'getParams')) ? $menuitem->getParams() : (isset($menuitem->params) ? $menuitem->params : null);
			}

			if (!empty($this->menuparams)) {
				$this->ucm_type = $this->menuparams->get('ucm_type');

				if (!empty($this->ucm_type)) {
					JLoader::import('components.com_tjfields.tables.type', JPATH_ADMINISTRATOR);
					$ucmTypeTable = Table::getInstance('Type', 'TjucmTable', array('dbo', Factory::getDbo()));
					$ucmTypeTable->load(array('alias' => $this->ucm_type));
					$this->client = $ucmTypeTable->unique_identifier;
				}
			}
		}

		// Get UCM type id from uniquue identifier
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/models');
		$tjUcmModelType = BaseDatabaseModel::getInstance('Type', 'TjucmModel');
		$this->ucmTypeId = $tjUcmModelType->getTypeId($this->client);

		$this->appendUrl = "";

		if (!empty($this->created_by)) {
			$this->appendUrl .= "&created_by=" . $this->created_by;
		}

		if (!empty($this->client)) {
			$this->appendUrl .= "&client=" . $this->client;
		}

		parent::__construct();
	}

	/**
	 * Method to check out an item for editing and redirect to the edit form.
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	public function edit()
	{
		$app = Factory::getApplication();

		// Get the previous edit id (if any) and the current edit id.
		$previousId = (int) $app->getUserState('com_tjucm.edit.item.id');
		$editId = $app->input->getInt('id', 0);

		// Set the user id for the user to edit in the session.
		$app->setUserState('com_tjucm.edit.item.id', $editId);

		// Get the model.
		$model = $this->getModel('Item', 'TjucmModel');

		// Check out the item
		if ($editId) {
			$model->checkout($editId);
		}

		// Check in the previous user.
		if ($previousId && $previousId !== $editId) {
			$model->checkin($previousId);
		}

		// Redirect to the edit screen.
		$tjUcmFrontendHelper = new TjucmHelpersTjucm;
		$link = 'index.php?option=com_tjucm&view=itemform&layout=default&client=' . $this->client . '&id=' . $editId;
		$itemId = $tjUcmFrontendHelper->getItemId($link);

		$this->setRedirect(Route::_('index.php?option=com_tjucm&view=itemform&id=' . $editId . '&Itemid=' . $itemId, false));
	}

	/**
	 * Method to save a user's profile data.
	 *
	 * @return    void
	 *
	 * @throws Exception
	 * @since    1.6
	 */
	public function publish()
	{
		// Check for request forgeries.
		(Session::checkToken('get') or Session::checkToken()) or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app = Factory::getApplication();
		$id = $app->input->getInt('id');
		$tjUcmFrontendHelper = new TjucmHelpersTjucm;

		// Checking if the user can remove object
		$canEdit = TjucmAccess::canEdit($this->ucmTypeId, $id);
		$canEditState = TjucmAccess::canEditState($this->ucmTypeId, $id);

		if ($canEdit || $canEditState) {
			$model = $this->getModel('Item', 'TjucmModel');

			// Get the user data.
			$state = $app->input->getInt('state');

			// Attempt to save the data.
			$return = $model->publish($id, $state);

			// Check for errors.
			if ($return === false) {
				$this->setMessage(Text::sprintf('COM_TJUCM_SAVE_FAILED', $model->getError()), 'warning');
			}

			// Clear the profile id from the session.
			$app->setUserState('com_tjucm.edit.item.id', null);

			// Flush the data from the session.
			$app->setUserState('com_tjucm.edit.item.data', null);

			// Redirect to the list screen.
			$this->setMessage(Text::_('COM_TJUCM_ITEM_SAVED_SUCCESSFULLY'));

			// If there isn't any menu item active, redirect to list view
			$itemId = $tjUcmFrontendHelper->getItemId('index.php?option=com_tjucm&view=items' . $this->client);
			$this->setRedirect(Route::_('index.php?option=com_tjucm&view=items' . $this->appendUrl . '&Itemid=' . $itemId, false));

			// Call trigger on after publish/unpublish the record

			Factory::getApplication()->triggerEvent('tjUcmOnAfterStateChangeItem', array($id, $state));
		} else {
			// If there isn't any menu item active, redirect to list view
			$link = 'index.php?option=com_tjucm&view=items' . $this->appendUrl;
			$itemId = $tjUcmFrontendHelper->getItemId($link);
			$this->setRedirect(Route::_($link . '&Itemid=' . $itemId, false), Text::_('COM_TJUCM_ITEM_SAVED_STATE_ERROR'), 'error');
		}
	}

	/**
	 * Remove data
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function remove()
	{
		// Check for request forgeries.
		(Session::checkToken('get') or Session::checkToken()) or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app = Factory::getApplication();
		$tjUcmFrontendHelper = new TjucmHelpersTjucm;

		// Get the user data.
		$id = $app->input->getInt('id', 0);

		// Checking if the user can remove object
		$canDelete = TjucmAccess::canDelete($this->ucmTypeId, $id);

		if ($canDelete) {
			$model = $this->getModel('Item', 'TjucmModel');

			// Attempt to save the data.
			$return = $model->delete($id);

			// Check for errors.
			if ($return === false) {
				$this->setMessage(Text::sprintf("COM_TJUCM_DELETE_FAILED", $model->getError()), 'warning');
			} else {
				// Check in the profile.
				if ($return) {
					$model->checkin($return);
				}

				// Clear the profile id from the session.
				$app->setUserState('com_tjucm.edit.item.id', null);

				// Flush the data from the session.
				$app->setUserState('com_tjucm.edit.item.data', null);

				$this->setMessage(Text::_('COM_TJUCM_ITEM_DELETED_SUCCESSFULLY'));
			}

			// If there isn't any menu item active, redirect to list view
			$link = 'index.php?option=com_tjucm&view=items' . $this->appendUrl;
			$itemId = $tjUcmFrontendHelper->getItemId($link);
			$this->setRedirect(Route::_($link . '&Itemid=' . $itemId, false));
		} else {
			// If there isn't any menu item active, redirect to list view
			$link = 'index.php?option=com_tjucm&view=items' . $this->appendUrl;
			$itemId = $tjUcmFrontendHelper->getItemId($link);
			$this->setRedirect(Route::_($link . '&Itemid=' . $itemId, false), Text::_('COM_TJUCM_ITEM_SAVED_STATE_ERROR'), 'error');
		}
	}

	/**
	 * Method to download the document
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	// public function download()
	// {
	// 	$app         = Factory::getApplication();
	// 	$document    = Factory::getDocument();
	// 	$viewType    = $document->getType();
	// 	$viewName    = $this->input->get('view', 'document');
	// 	$viewLayout  = $this->input->get('layout', 'default', 'string');
	// 	$id          = $this->input->get('id', 0, 'INT');

	// 	if (!$id)
	// 	{
	// 		$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));

	// 		return;
	// 	}

	// 	/** @var $view TjucmViewDocument */
	// 	$view        = $this->getView($viewName, $viewType, '', array('base_path' => $this->basePath, 'layout' => $viewLayout));

	// 	/** @var $model TjucmModelDocument */
	// 	$model       = $this->getModel($viewName);
	// 	Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjucm/tables');
	// 	$documentTemplate       = $model->getTable();

	// 	// Get the params for the current Document template.
	// 	$documentTemplate->load(array('id' => (int) $id));
	// 	$view->param = isset($documentTemplate->params) ? $documentTemplate->params : '';

	// 	$view->setModel($model, true);
	// 	$view->document = $document;
	// 	$view->download();
	// }

	/**
	 * Method to generate AI Insights report using Google Gemini API
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function generateAiReport()
	{
		// Check user session/login
		$user = Factory::getUser();
		if (!$user->id) {
			echo json_encode(array('success' => false, 'message' => 'Unauthorized'));
			Factory::getApplication()->close();
		}

		try {
			$id = Factory::getApplication()->input->getInt('id');
			$client = Factory::getApplication()->input->get('client');

		// 1. Load UCM item
		$model = $this->getModel('Item', 'TjucmModel');
		$itemData = null;
		if ($id > 0) {
			$itemData = $model->getItem($id);
		}

		$formDataParam = Factory::getApplication()->input->get('form_data', '', 'raw');

		if (($id <= 0 || empty($itemData)) && empty($formDataParam)) {
			echo json_encode(array('success' => false, 'message' => 'Record not found'));
			Factory::getApplication()->close();
		}

		// 2. Load UCM Type params and configurations
		$dpeParams = \Joomla\CMS\Component\ComponentHelper::getParams('com_dpe');
		$enableAi = $dpeParams->get('enable_ai', 0);
		$apiKey = $dpeParams->get('gemini_api_key', '');

		if (!$enableAi || empty($apiKey)) {
			echo json_encode(array('success' => false, 'message' => 'AI integration is disabled or API key is missing in global configuration.'));
			Factory::getApplication()->close();
		}

		JLoader::import('components.com_tjucm.tables.type', JPATH_ADMINISTRATOR);
		$typeTable = Table::getInstance('Type', 'TjucmTable', array('dbo', Factory::getDbo()));
		$typeTable->load(array('unique_identifier' => $client));
		$typeParams = json_decode($typeTable->params);

		$aiEnabledForType = isset($typeParams->ai_enable_insights) && $typeParams->ai_enable_insights == 1;

		if (!$aiEnabledForType) {
			echo json_encode(array('success' => false, 'message' => 'AI is not enabled for this content type.'));
			Factory::getApplication()->close();
		}

		// 3. Extract form fields and responses
		$viewName = explode('.', $client);
		$formExtra = $model->getFormExtra(
			array(
				"clientComponent" => 'com_tjucm',
				"client" => $client,
				"view" => $viewName[1],
				"layout" => 'edit',
				"content_id" => $id
			)
		);

		// Load fields helper to fetch data values
		$path = JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php';
		if (!class_exists('TjfieldsHelper')) {
			JLoader::register('TjfieldsHelper', $path);
			JLoader::load('TjfieldsHelper');
		}
		$tjFieldsHelper = new TjfieldsHelper;
		$fieldDataValues = array();
		if ($id > 0) {
			$fieldDataValues = $tjFieldsHelper->FetchDatavalue(array('content_id' => $id, 'client' => $client));
		}

		// Parse form_data parameter if submitted from frontend edit form
		$submittedData = array();
		if (!empty($formDataParam)) {
			$submittedDataRaw = json_decode($formDataParam, true);
			if (is_array($submittedDataRaw)) {
				foreach ($submittedDataRaw as $input) {
					if (preg_match('/^jform\[([^\]]+)\]/', $input['name'], $matches)) {
						$fieldName = $matches[1];
						if (strpos($input['name'], '[]') !== false) {
							if (!isset($submittedData[$fieldName])) {
								$submittedData[$fieldName] = array();
							}
							$submittedData[$fieldName][] = $input['value'];
						} else {
							$submittedData[$fieldName] = $input['value'];
						}
					}
				}
			}
		}

		// Build a readable context of the UCM Form data
		$formContent = "UCM Assessment Record Details:\n";
		$formContent .= "Title: " . $typeTable->title . "\n";
		$formContent .= "Record ID: " . $id . "\n\n";

		$redactionEnabled = isset($typeParams->ai_data_redaction) ? $typeParams->ai_data_redaction : 1;
		$piiFields = ($redactionEnabled && isset($typeParams->ai_pii_fields)) ? (array) $typeParams->ai_pii_fields : array();

		// Load form XML fields
		$xmlFileName = explode(".", $formExtra->getName());
		$xmlPath = JPATH_SITE . "/administrator/components/com_tjucm/models/forms/" . $xmlFileName[1] . ".xml";
		$schemaContent = "Form Fields Schema (Metadata & Descriptions):\n";
		$snapshot = array();
		if (file_exists($xmlPath)) {
			$xmlForm = simplexml_load_file($xmlPath);
			$fieldsets = $formExtra->getFieldsets();
			foreach ($fieldsets as $fieldset) {
				$sectionHeader = "### Section: " . ($fieldset->label ? Text::_($fieldset->label) : $fieldset->name) . "\n";
				$formContent .= $sectionHeader;
				$schemaContent .= $sectionHeader;
				foreach ($formExtra->getFieldset($fieldset->name) as $field) {
					$fieldName = $field->fieldname;
					if ($redactionEnabled && in_array($fieldName, $piiFields)) {
						continue;
					}
					$fieldLabel = $field->label ? strip_tags(Text::_($field->label)) : $fieldName;

					// Extract description and hint from XML element
					$fieldDesc = '';
					$fieldHint = '';
					if (isset($field->element)) {
						$fieldDesc = isset($field->element['description']) ? strip_tags(Text::_((string) $field->element['description'])) : '';
						$fieldHint = isset($field->element['hint']) ? strip_tags(Text::_((string) $field->element['hint'])) : '';
					}

					$schemaContent .= "- **" . $fieldLabel . "**:";
					if ($fieldDesc) {
						$schemaContent .= " Purpose: $fieldDesc.";
					}
					if ($fieldHint) {
						$schemaContent .= " Instructions: $fieldHint.";
					}
					if (!$fieldDesc && !$fieldHint) {
						$schemaContent .= " No description provided.";
					}
					$schemaContent .= "\n";

					// Find value
					$value = '';
					if (isset($submittedData[$fieldName])) {
						$value = $submittedData[$fieldName];
					} else {
						foreach ($fieldDataValues as $fd) {
							if ($fd->name == $fieldName) {
								$value = $fd->value;
								break;
							}
						}

						if (empty($value)) {
							$value = $formExtra->getvalue($fieldName);
						}
					}

					if (is_array($value)) {
						$tempValues = array();
						foreach ($value as $val) {
							if (is_object($val)) {
								$tempValues[] = json_encode($val);
							} elseif (is_array($val)) {
								$tempValues[] = json_encode($val);
							} else {
								$tempValues[] = (string) $val;
							}
						}
						$value = implode(', ', $tempValues);
					} elseif (is_object($value)) {
						$value = json_encode($value);
					}

					// Format checklist responses or JSON arrays nicely
					if (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
						$decoded = json_decode($value, true);
						if ($decoded) {
							$valueItems = array();
							foreach ($decoded as $k => $v) {
								if (is_array($v)) {
									$valueItems[] = "$k: " . implode(', ', $v);
								} else {
									$valueItems[] = "$k: $v";
								}
							}
							$value = implode(', ', $valueItems);
						}
					}

					$resolvedValue = '';
					if (trim($value) !== '') {
						$resolvedValue = trim(strip_tags($this->resolveValueLabel($fieldName, $fieldLabel, $value)));
					}
					if ($resolvedValue === '') {
						$resolvedValue = '[Empty / Not filled]';
					}
					$formContent .= "- **" . $fieldLabel . "**: " . $resolvedValue . "\n";
					$snapshot[$fieldName] = $resolvedValue;
				}
				$formContent .= "\n";
				$schemaContent .= "\n";
			}
			$formContent .= "\n\n" . $schemaContent;
		}

		// 4. Sanitization and Redaction (if enabled)
		if ($redactionEnabled) {
			// Redact emails
			$formContent = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[REDACTED_EMAIL]', $formContent);
			// Redact phone numbers
			$formContent = preg_replace('/(\+?[0-9]{1,3}[- ]?)?([0-9]{3}[- ]?[0-9]{3}[- ]?[0-9]{4})/', '[REDACTED_PHONE]', $formContent);
		}

		// 5. Construct Prompt and System Instruction / Guardrails
		$defaultSystemInstruction = "You are an assistant for this assessment form. You are strictly restricted to answering questions and providing analysis, summaries, or insights based *only* on the provided form data and the provided form schema (which contains descriptions and hints for each field).\nYou can help the user understand what each field is for and guide them on what to fill using the schema.\nIf the user's query is not about this specific form, its fields, or its contents, or asks you to perform unrelated tasks (e.g. general knowledge, writing code, translations of unrelated text, math problems, etc.), you must politely decline to answer, stating that you can only help with questions related to this specific assessment form.\nDo not make assumptions or use external knowledge.";
		$systemInstruction = !empty($typeParams->ai_system_instruction) ? $typeParams->ai_system_instruction : $defaultSystemInstruction;

		$enableGraph = isset($typeParams->ai_enable_graph) && $typeParams->ai_enable_graph == 1;
		if ($enableGraph) {
			$systemInstruction .= "\n\nYou are also permitted and encouraged to generate visual charts and graphs (specifically pie charts and bar charts) when summarizing or when the user asks for statistical breakdown, risk distributions, status distributions, or visual charts/graphs of the form data.\nTo generate a chart, you MUST output it on its own line in this exact format:\n[CHART:pie]\n{\n  \"title\": \"Chart Title\",\n  \"labels\": [\"Label A\", \"Label B\"],\n  \"data\": [10, 20]\n}\n[/CHART]\n\nOR for bar charts:\n[CHART:bar]\n{\n  \"title\": \"Chart Title\",\n  \"labels\": [\"Label A\", \"Label B\"],\n  \"data\": [10, 20]\n}\n[/CHART]\n\nMake sure the JSON is valid, uses double quotes, and does not contain any other text inside the [CHART] tags. For standard form summaries and reports, always prefer using 'pie' charts to represent the distribution of risks, severity levels, or field statuses to ensure visual consistency. Keep the JSON raw and simple. Ensure that the numbers/data points in the chart exactly correspond to the actual data values present in the form.";
		}

		$defaultPrompt = "Analyze the following UCM assessment data.\n\nGenerate:\n1. Executive Summary\n2. Key Findings\n3. Risks Identified\n4. Recommendations\n5. Improvement Actions\n\nOnly use the provided information. Do not make assumptions.";
		$promptTemplate = !empty($typeParams->ai_prompt_template) ? $typeParams->ai_prompt_template : $defaultPrompt;
 
 		$isCustomQuery = false;
 		// Handle custom prompt override if allowed
 		if (isset($typeParams->ai_allow_custom_prompt) && $typeParams->ai_allow_custom_prompt == 1) {
 			$customPrompt = Factory::getApplication()->input->get('custom_prompt', '', 'raw');
 			if (!empty($customPrompt)) {
 				$promptTemplate = $customPrompt;
 				$isCustomQuery = true;
 			}
 		}
 
 		if ($isCustomQuery) {
 			$fullPrompt = "System Instructions:\n" . $systemInstruction . "\n\n"
 				. "User Query: \"" . $promptTemplate . "\"\n\n"
 				. "Data to analyze:\n" . $formContent;
 		} else {
 			$fullPrompt = $promptTemplate . "\n\nData to analyze:\n" . $formContent;
 		}
 
 		// 6. Send request to Gemini API
 		$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;
 
 		$requestBody = array(
 			'contents' => array(
 				array(
 					'parts' => array(
 						array(
 							'text' => $fullPrompt
 						)
 					)
 				)
 			),
 			'systemInstruction' => array(
 				'parts' => array(
 					array(
 						'text' => $systemInstruction
 					)
 				)
 			),
 			'generationConfig' => array(
 				'maxOutputTokens' => (!empty($typeParams->ai_max_tokens) && (int)$typeParams->ai_max_tokens > 2048) ? (int)$typeParams->ai_max_tokens : 8192,
 				'temperature' => 0.0
 			)
 		);

		$options = array(
			'headers' => array(
				'Content-Type' => 'application/json'
			)
		);

		try {
			$http = \Joomla\CMS\Http\HttpFactory::getHttp();
			$maxRetries = 3;
			$retryDelay = 1; // starting delay in seconds
			$response = null;

			for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
				try {
					$response = $http->post($url, json_encode($requestBody), $options['headers']);
					if ($response->code == 200) {
						break;
					}
					// If server error (503/502/504) or rate limit (429), retry
					if ($response->code == 503 || $response->code == 429 || $response->code == 502 || $response->code == 504) {
						if ($attempt < $maxRetries) {
							sleep($retryDelay);
							$retryDelay *= 2;
							continue;
						}
					}
					throw new \Exception('Gemini API returned status code ' . $response->code . ': ' . $response->body);
				} catch (\Throwable $e) {
					if ($attempt == $maxRetries) {
						throw $e;
					}
					sleep($retryDelay);
					$retryDelay *= 2;
				}
			}

			if (!$response || $response->code != 200) {
				throw new \Exception('Gemini API request failed completely after ' . $maxRetries . ' attempts.');
			}

			$responseJson = json_decode($response->body, true);
			$generatedText = '';
			if (isset($responseJson['candidates'][0]['content']['parts'][0]['text'])) {
				$generatedText = $responseJson['candidates'][0]['content']['parts'][0]['text'];
			} else {
				throw new \Exception('Unexpected Gemini API response structure: ' . $response->body);
			}

			// 7. Store in database
			$db = Factory::getDbo();
			$db->setQuery("CREATE TABLE IF NOT EXISTS `#__tjucm_ai_reports` (
				`id` INT AUTO_INCREMENT PRIMARY KEY,
				`ucm_item_id` INT NOT NULL,
				`ucm_type_id` INT NOT NULL,
				`created_by` INT NOT NULL,
				`created_date` DATETIME NOT NULL,
				`raw_prompt` TEXT NOT NULL,
				`generated_report` MEDIUMTEXT NOT NULL,
				`form_last_modified` DATETIME NOT NULL,
				`snapshot_json` MEDIUMTEXT NULL,
				`snapshot_hash` VARCHAR(64) NULL,
				`is_downloaded` TINYINT(1) DEFAULT 0,
				`last_downloaded_at` DATETIME NULL,
				INDEX `idx_ucm_item` (`ucm_item_id`),
				INDEX `idx_ucm_type` (`ucm_type_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;");
			$db->execute();

			// Ensure columns exist if table was already created in a previous version
			$columns = $db->getTableColumns('#__tjucm_ai_reports');
			if (!isset($columns['snapshot_json'])) {
				$db->setQuery("ALTER TABLE `#__tjucm_ai_reports` ADD COLUMN `snapshot_json` MEDIUMTEXT NULL;");
				$db->execute();
			}
			if (!isset($columns['snapshot_hash'])) {
				$db->setQuery("ALTER TABLE `#__tjucm_ai_reports` ADD COLUMN `snapshot_hash` VARCHAR(64) NULL;");
				$db->execute();
			}
			if (!isset($columns['is_downloaded'])) {
				$db->setQuery("ALTER TABLE `#__tjucm_ai_reports` ADD COLUMN `is_downloaded` TINYINT(1) DEFAULT 0;");
				$db->execute();
			}
			if (!isset($columns['last_downloaded_at'])) {
				$db->setQuery("ALTER TABLE `#__tjucm_ai_reports` ADD COLUMN `last_downloaded_at` DATETIME NULL;");
				$db->execute();
			}

			// Fetch existing download tracking details before deleting
			$queryExisting = $db->getQuery(true)
				->select('is_downloaded, last_downloaded_at')
				->from('#__tjucm_ai_reports')
				->where('ucm_item_id = ' . (int) $id)
				->order('id DESC');
			$db->setQuery($queryExisting, 0, 1);
			$existingReport = $db->loadObject();

			$isDownloaded = 0;
			$lastDownloadedAt = null;
			if ($existingReport) {
				$isDownloaded = (int) $existingReport->is_downloaded;
				$lastDownloadedAt = $existingReport->last_downloaded_at;
			}

			// Delete previous reports for this item to save space (since we only show latest)
			$db->setQuery("DELETE FROM `#__tjucm_ai_reports` WHERE `ucm_item_id` = " . (int) $id);
			$db->execute();

			// Store report
			$reportObj = new \stdClass();
			$reportObj->ucm_item_id = $id;
			$reportObj->ucm_type_id = $this->ucmTypeId;
			$reportObj->created_by = $user->id;
			$reportObj->created_date = Factory::getDate()->toSql();
			$reportObj->raw_prompt = $fullPrompt;
			$reportObj->generated_report = $generatedText;
			$reportObj->form_last_modified = $itemData->modified_date ?: $itemData->created_date ?: Factory::getDate()->toSql();
			$reportObj->snapshot_json = json_encode($snapshot);
			$reportObj->snapshot_hash = hash('sha256', $reportObj->snapshot_json);
			$reportObj->is_downloaded = $isDownloaded;
			$reportObj->last_downloaded_at = $lastDownloadedAt;

			$db->insertObject('#__tjucm_ai_reports', $reportObj);

			// Return success
			echo json_encode(array(
				'success' => true,
				'report' => $generatedText,
				'created_date' => $reportObj->created_date,
				'form_last_modified' => $reportObj->form_last_modified
			));

		} catch (\Throwable $e) {
			echo json_encode(array(
				'success' => false,
				'message' => 'AI Generation failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
			));
		}
		Factory::getApplication()->close();
		} catch (\Throwable $e) {
			echo json_encode(array(
				'success' => false,
				'message' => 'AI Generation failed (Outer): ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
			));
			Factory::getApplication()->close();
		}
	}

	/**
	 * Method to get the latest cached AI report
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getLatestReport()
	{
		$user = Factory::getUser();
		if (!$user->id) {
			echo json_encode(array('success' => false, 'message' => 'Unauthorized'));
			Factory::getApplication()->close();
		}

		$id = Factory::getApplication()->input->getInt('id');

		$db = Factory::getDbo();
		$tables = $db->getTableList();
		$prefix = $db->getPrefix();
		if (!in_array($prefix . 'tjucm_ai_reports', $tables)) {
			echo json_encode(array('success' => true, 'report' => null));
			Factory::getApplication()->close();
		}

		// Ensure columns exist if table was created in a previous version
		$columns = $db->getTableColumns('#__tjucm_ai_reports');
		if (!isset($columns['snapshot_json'])) {
			$db->setQuery("ALTER TABLE `#__tjucm_ai_reports` ADD COLUMN `snapshot_json` MEDIUMTEXT NULL;");
			$db->execute();
		}
		if (!isset($columns['snapshot_hash'])) {
			$db->setQuery("ALTER TABLE `#__tjucm_ai_reports` ADD COLUMN `snapshot_hash` VARCHAR(64) NULL;");
			$db->execute();
		}
		if (!isset($columns['is_downloaded'])) {
			$db->setQuery("ALTER TABLE `#__tjucm_ai_reports` ADD COLUMN `is_downloaded` TINYINT(1) DEFAULT 0;");
			$db->execute();
		}
		if (!isset($columns['last_downloaded_at'])) {
			$db->setQuery("ALTER TABLE `#__tjucm_ai_reports` ADD COLUMN `last_downloaded_at` DATETIME NULL;");
			$db->execute();
		}

		$query = $db->getQuery(true)
			->select('generated_report, created_date, form_last_modified, snapshot_hash, is_downloaded')
			->from('#__tjucm_ai_reports')
			->where('ucm_item_id = ' . (int) $id)
			->order('id DESC');
		$db->setQuery($query, 0, 1);
		$report = $db->loadObject();

		// Check if the form has changed after the report was generated
		$model = $this->getModel('Item', 'TjucmModel');
		$itemData = $model->getItem($id);
		$formLastModified = $itemData->modified_date ?: $itemData->created_date ?: '';
		$client = $itemData->client;

		$outdated = false;
		if ($report) {
			// Calculate current snapshot hash
			$typeTable = \Joomla\CMS\Table\Table::getInstance('Type', 'TjucmTable');
			$typeTable->load(array('unique_identifier' => $client));
			$typeParams = json_decode($typeTable->params);

			$redactionEnabled = isset($typeParams->ai_data_redaction) ? $typeParams->ai_data_redaction : 1;
			$piiFields = ($redactionEnabled && isset($typeParams->ai_pii_fields)) ? (array) $typeParams->ai_pii_fields : array();

			$path = JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php';
			if (!class_exists('TjfieldsHelper')) {
				\JLoader::register('TjfieldsHelper', $path);
				\JLoader::load('TjfieldsHelper');
			}
			$tjFieldsHelper = new \TjfieldsHelper;
			$fieldDataValues = array();
			if ($id > 0) {
				$fieldDataValues = $tjFieldsHelper->FetchDatavalue(array('content_id' => $id, 'client' => $client));
			}

			$viewName = explode('.', $client);
			$formExtra = $model->getFormExtra(
				array(
					"clientComponent" => 'com_tjucm',
					"client" => $client,
					"view" => $viewName[1],
					"layout" => 'edit',
					"content_id" => $id
				)
			);

			$xmlFileName = explode(".", $formExtra->getName());
			$xmlPath = JPATH_SITE . "/administrator/components/com_tjucm/models/forms/" . $xmlFileName[1] . ".xml";

			$snapshot = array();
			if (file_exists($xmlPath)) {
				$xmlForm = simplexml_load_file($xmlPath);
				$fieldsets = $formExtra->getFieldsets();
				foreach ($fieldsets as $fieldset) {
					foreach ($formExtra->getFieldset($fieldset->name) as $field) {
						$fieldName = $field->fieldname;
						if ($redactionEnabled && in_array($fieldName, $piiFields)) {
							continue;
						}
						// Find value
						$value = '';
						foreach ($fieldDataValues as $fd) {
							if ($fd->name == $fieldName) {
								$value = $fd->value;
								break;
							}
						}
						if (empty($value)) {
							$value = $formExtra->getvalue($fieldName);
						}

						if (is_array($value)) {
							$tempValues = array();
							foreach ($value as $val) {
								if (is_object($val)) {
									$tempValues[] = json_encode($val);
								} elseif (is_array($val)) {
									$tempValues[] = json_encode($val);
								} else {
									$tempValues[] = (string) $val;
								}
							}
							$value = implode(', ', $tempValues);
						} elseif (is_object($value)) {
							$value = json_encode($value);
						}

						if (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
							$decoded = json_decode($value, true);
							if ($decoded) {
								$valueItems = array();
								foreach ($decoded as $k => $v) {
									if (is_array($v)) {
										$valueItems[] = "$k: " . implode(', ', $v);
									} else {
										$valueItems[] = "$k: $v";
									}
								}
								$value = implode(', ', $valueItems);
							}
						}

						$resolvedValue = '';
						if (trim($value) !== '') {
							$fieldLabel = $field->label ? strip_tags(\Text::_($field->label)) : $fieldName;
							$resolvedValue = trim(strip_tags($this->resolveValueLabel($fieldName, $fieldLabel, $value)));
						}
						if ($resolvedValue === '') {
							$resolvedValue = '[Empty / Not filled]';
						}
						$snapshot[$fieldName] = $resolvedValue;
					}
				}
			}

			$currentHash = hash('sha256', json_encode($snapshot));

			$isOutdatedState = false;
			if (empty($report->snapshot_hash)) {
				// Fallback to timestamp comparison for legacy reports
				if ($formLastModified && strtotime($formLastModified) > strtotime($report->form_last_modified)) {
					$isOutdatedState = true;
				}
			} else {
				if ($currentHash !== $report->snapshot_hash) {
					$isOutdatedState = true;
				}
			}

			// Change detection should only notify (set outdated = true) for reports that have been saved/downloaded
			if ($isOutdatedState && (int)$report->is_downloaded === 1) {
				$outdated = true;
			}
		}

		echo json_encode(array(
			'success' => true,
			'report' => $report ? $report->generated_report : null,
			'created_date' => $report ? $report->created_date : null,
			'outdated' => $outdated
		));

		Factory::getApplication()->close();
	}

	/**
	 * Method to track AI report downloads
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function trackDownload()
	{
		$user = Factory::getUser();
		if (!$user->id) {
			echo json_encode(array('success' => false, 'message' => 'Unauthorized'));
			Factory::getApplication()->close();
		}

		$id = Factory::getApplication()->input->getInt('id');

		$db = Factory::getDbo();
		$tables = $db->getTableList();
		$prefix = $db->getPrefix();
		if (in_array($prefix . 'tjucm_ai_reports', $tables)) {
			// Update the report download state
			$query = $db->getQuery(true)
				->update($db->quoteName('#__tjucm_ai_reports'))
				->set($db->quoteName('is_downloaded') . ' = 1')
				->set($db->quoteName('last_downloaded_at') . ' = ' . $db->quote(Factory::getDate()->toSql()))
				->where($db->quoteName('ucm_item_id') . ' = ' . (int) $id);
			$db->setQuery($query);
			$db->execute();
		}

		echo json_encode(array('success' => true));
		Factory::getApplication()->close();
	}

	/**
	 * Method to generate and download AI report as a PDF using mPDF
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function downloadAiReportPdf()
	{
		$user = Factory::getUser();
		if (!$user->id) {
			throw new \RuntimeException('Unauthorized', 403);
		}

		$id = Factory::getApplication()->input->getInt('id');
		$client = Factory::getApplication()->input->get('client');
		$reportHtml = Factory::getApplication()->input->get('report_html', '', 'RAW');

		if (empty($reportHtml)) {
			throw new \RuntimeException('Missing report content', 400);
		}

		// Update download tracking in the database
		$db = Factory::getDbo();
		$tables = $db->getTableList();
		$prefix = $db->getPrefix();
		if (in_array($prefix . 'tjucm_ai_reports', $tables)) {
			$db->setQuery(
				$db->getQuery(true)
					->update($db->quoteName('#__tjucm_ai_reports'))
					->set($db->quoteName('is_downloaded') . ' = 1')
					->set($db->quoteName('last_downloaded_at') . ' = ' . $db->quote(Factory::getDate()->toSql()))
					->where($db->quoteName('ucm_item_id') . ' = ' . (int) $id)
			);
			$db->execute();
		}

		// Fetch item details for PDF header
		$model = $this->getModel('Item', 'TjucmModel');
		$itemData = $model->getItem($id);
		$itemTitle = $itemData ? $itemData->title : 'AI Knowledge Bank Response Report';

		// Load mPDF
		jimport("mpdf.mpdf");

		$imageUrl = \Joomla\CMS\Uri\Uri::root() . "images/DataProtectionEd_Logo150H.jpg";
		
		// Determine temp directory inside workspace tmp
		$tempDir = JPATH_SITE . '/tmp';
		if (!file_exists($tempDir)) {
			mkdir($tempDir, 0755, true);
		}

		$mpdf = new \Mpdf\Mpdf([
			"mode" => "utf-8",
			"format" => "A4",
			"shrink_tables_to_fit" => 0,
			"default_font_size" => 11,
			"allow_output_buffering" => true,
			"tempDir" => $tempDir
		]);

		// CSS Styles similar to professional template
		$html = '<html><head><meta name="viewport" content="width=device-width, initial-scale=1"><meta charset="utf-8"/>';
		$html .= '<style>
		@page {
			margin-top: 100px;
			margin-bottom: 80px;
			header: html_myHeader;
			footer: html_myFooter;
		}
		body {
			font-family: "Open Sans", sans-serif !important;
			color: #334155;
			line-height: 1.6;
			padding: 0;
			margin-left: 20px;
			margin-right: 20px;
		}
		h1, h2, h3, h4 {
			color: #0087b7;
			font-weight: bold;
			margin-top: 20px;
			margin-bottom: 10px;
		}
		h1 {
			font-size: 20px;
			border-bottom: 2px solid #e2e8f0;
			padding-bottom: 6px;
		}
		h2 { font-size: 17px; }
		h3 { font-size: 15px; }
		p {
			margin-top: 0;
			margin-bottom: 10px;
			font-size: 13px;
		}
		ul, ol {
			margin-top: 0;
			margin-bottom: 15px;
			padding-left: 20px;
		}
		li {
			margin-bottom: 5px;
			font-size: 13px;
		}
		blockquote {
			border-left: 4px solid #00aeef;
			padding: 8px 12px;
			background: #f8fafc;
			margin: 15px 0;
			color: #475569;
			font-style: italic;
		}
		.disclaimer {
			font-size: 10px;
			color: #94a3b8;
			margin-top: 30px;
			border-top: 1px solid #e2e8f0;
			padding-top: 8px;
			font-style: italic;
		}
		img {
			max-width: 100%;
			height: auto;
			margin: 15px 0;
		}
		</style>';
		$html .= "</head><body>";

		// Header template
		$html .= '<htmlpageheader name="myHeader">
		<table width="100%" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
		<tr>
		<td width="60%">
			<p style="margin: 0; font-weight: bold; color: #0f172a; font-size: 14px;">' . htmlspecialchars($itemTitle) . '</p>
			<p style="margin: 0; font-size: 11px; color: #64748b;">AI Knowledge Bank Assistant Report</p>
		</td>
		<td width="40%" align="right">
			<img src="' . $imageUrl . '" style="height: 35px;">
		</td>
		</tr>
		</table>
		</htmlpageheader>';

		// Footer template
		$html .= '<htmlpagefooter name="myFooter">
		<table width="100%" style="border-top: 1px solid #e2e8f0; padding-top: 10px;">
		<tr>
		<td width="50%" style="font-size: 9px; color: #94a3b8; font-style: italic;">
			Generated on ' . Factory::getDate()->format('d M Y H:i') . '
		</td>
		<td width="50%" align="right" style="font-size: 9px; color: #94a3b8;">
			Page {PAGENO} of {nbpg}
		</td>
		</tr>
		</table>
		</htmlpagefooter>';

		// Main Content
		$html .= '<div style="margin-top: 20px;">';
		$html .= $reportHtml;
		$html .= '</div>';
		
		$html .= '<div class="disclaimer">Beta feature. AI-generated content is advisory and not authoritative.</div>';
		$html .= "</body></html>";

		$pdfName = "AI_Report_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $itemTitle) . "_" . date("YmdHis") . ".pdf";

		// Clear output buffer to prevent PDF corruption
		while (ob_get_level() > 0) {
			ob_end_clean();
		}

		$mpdf->WriteHTML($html);
		$mpdf->Output($pdfName, "D");

		Factory::getApplication()->close();
	}

	/**
	 * Method to map generateReport task to generateAiReport
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function generateReport()
	{
		$this->generateAiReport();
	}

	/**
	 * Resolves numerical database IDs to human-readable names for users/organisations
	 *
	 * @param   string  $fieldName   Field Name
	 * @param   string  $fieldLabel  Field Label
	 * @param   mixed   $value       Field Value
	 *
	 * @return  string
	 */
	private function resolveValueLabel($fieldName, $fieldLabel, $value)
	{
		if (empty($value) || !is_numeric($value)) {
			return $value;
		}

		$db = \Joomla\CMS\Factory::getDbo();
		$checkStr = strtolower($fieldName . ' ' . $fieldLabel);

		// 1. Resolve Lead Staff Member / Users
		if (strpos($checkStr, 'staff') !== false || strpos($checkStr, 'user') !== false || strpos($checkStr, 'by') !== false || strpos($checkStr, 'member') !== false) {
			$query = $db->getQuery(true)
				->select($db->quoteName('name'))
				->from($db->quoteName('#__users'))
				->where($db->quoteName('id') . ' = ' . (int) $value);
			$db->setQuery($query);
			$name = $db->loadResult();
			if ($name) {
				return $name;
			}
		}

		// 2. Resolve Organisation / Multiagency
		if (strpos($checkStr, 'organisation') !== false || strpos($checkStr, 'organization') !== false || strpos($checkStr, 'cluster') !== false) {
			// Check multiagency table first
			$query = $db->getQuery(true)
				->select($db->quoteName('title'))
				->from($db->quoteName('#__tjmultiagency_multiagency'))
				->where($db->quoteName('id') . ' = ' . (int) $value);
			$db->setQuery($query);
			$title = $db->loadResult();
			if ($title) {
				return $title;
			}

			// Check clusters table
			$query = $db->getQuery(true)
				->select($db->quoteName('name'))
				->from($db->quoteName('#__tj_clusters'))
				->where($db->quoteName('id') . ' = ' . (int) $value);
			$db->setQuery($query);
			$title = $db->loadResult();
			if ($title) {
				return $title;
			}
		}

		return $value;
	}
}

