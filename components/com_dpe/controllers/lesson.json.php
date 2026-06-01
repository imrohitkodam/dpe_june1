<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

/**
 * Dpe lesson Controller
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeControllerLesson extends AdminController
{
	/**
	 * Method to create a copy of the lessons
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function copyLessons()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		$lessonId = $app->input->getInt('lessonId', 0);

		// Cluster Id
		$schoolId = $app->input->getInt('schoolId', 0);
		$singleDocument = $app->input->getInt('singleDocument', 0);

		$interaction = $app->input->get('jform', array(), 'array');

		/** @var $model DpeModelLesson */
		$model = $this->getModel('lesson');

		if (!$lessonId && !$schoolId)
		{
			echo new JsonResponse(null, Text::_('COM_DPE_COPY_DOCUMENT_INVALID_REQUEST'), true);
			$app->close();
		}

		$user = Factory::getUser();

		// Check user not manageall cluster permission & not a members of cluster
		if (!($user->authorise('core.manageall', 'com_cluster') || RBACL::check($user->id, 'com_cluster', 'core.manage.lessons', 'com_tjlms', $schoolId)))
		{
			echo new JsonResponse(null, Text::sprintf('COM_DPE_COPY_DOCUMENT_NOT_AUTHORIZED_TO_ACTION', Text::_('COM_DPE_ORGANISATION')), true);
			$app->close();
		}

		// Step 2. Get The lesson data and create a copy of lesson current state
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
		$lessonTable = Table::getInstance('lesson', 'TjlmsTable');
		$lessonTable->load(array('id' => $lessonId));

		if (!$lessonTable->get('id'))
		{
			echo new JsonResponse(null, Text::_('COM_DPE_COPY_DOCUMENT_INVALID_REQUEST'), true);
			$app->close();
		}

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');

		// Check record exist in table if not exist then do not create a copy use it
		$xrefTable = DPE::table('TjlmsClusterXref');
		$xrefTable->load(array('lesson_id' => $lessonId, 'cluster_id' => $schoolId));
		$newLessonId = $lessonId;

		if (empty($xrefTable->id))
		{
			// Check if the lesson is already present in the xref tabkle if not thenn assign current lesson to the school
			$xrefTable->reset();
			$xrefTable->id = 0;
			$xrefTable->load(array('lesson_id' => $lessonId));

			if (!empty($xrefTable->id) && !$singleDocument)
			{
				$data = $lessonTable->getProperties();
				$data['id'] = 0;

				/** @var $lessonModel TjlmsModelLesson */
				$lessonModel = BaseDatabaseModel::getInstance('lesson', 'TjlmsModel', array("ignore_request" => true));
				$newLessonId = $lessonModel->save($data);
				$xrefTable->lesson_id = $newLessonId;
			}
		}

		if (!$newLessonId)
		{
			echo new JsonResponse(null, Text::_('COM_DPE_COPY_DOCUMENT_INVALID_REQUEST'), true);
			$app->close();
		}

		// Create a copy of media if the lesson is new
		if ($newLessonId != $lessonId)
		{
			// Step 3. Create a copy of lesson media. If it's a third party document(eg. boxApi) create copy on it
			$mediaModel = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');

			/** @var $mediaModel TjlmsModelMedia */
			$mediaDetails = $mediaModel->getItem($lessonTable->media_id);
			$mediaDetails->id = 0;

			if (!$mediaModel->save($mediaDetails->getProperties()))
			{
				echo new JsonResponse(null, $mediaModel->getError(), true);
				$app->close();
			}

			// Update the new media id to lesson
			// Purposefully used table object since we want simple info
			$lessonTable->reset();
			$lessonTable->id = 0;
			$lessonTable->load(array('id' => $newLessonId));
			$lessonTable->set('media_id', $mediaModel->getState('media.id'));
			$lessonTable->save($lessonTable->getProperties());

			// Copy the physical file
			$mediaDetails = $mediaModel->getItem($lessonTable->media_id);

			require_once JPATH_ROOT . '/components/com_tjlms/libraries/storage.php';
			$tjStorage = new Tjstorage;

			$storagePath = JPATH_SITE . '/media/com_tjlms/lessons/';
			$mediaPath = $storagePath . $mediaDetails->source;

			if ($mediaDetails->storage == 's3')
			{
				$storage = $tjStorage->getStorage($mediaDetails->storage);
				$mediaPath = $storage->getURI('media/com_tjlms/lessons/' . $mediaDetails->source);
			}

			$filename = file::stripExt(basename($mediaPath)) . $newLessonId . "." . File::getExt(basename($mediaPath));
			$newMediaPath = $storagePath . $filename;

			File::copy($mediaPath, $newMediaPath);

			PluginHelper::importPlugin('tj' . $mediaDetails->format);
			$uploadStatus = Factory::getApplication()->triggerEvent(
			'upload_filesOn' . preg_replace('#\.[^.]*$#', '', $mediaDetails->sub_format), array($lessonTable->id, $filename, $newMediaPath)
			);

			// Update the new media details
			$registry = new Registry($uploadStatus[0]);
			$mediaDetails->params = $registry->toString();
			$mediaDetails->source = $filename;
			$mediaModel->save($mediaDetails->getProperties());
		}

		// Step 4. Create a interaction for the document
		$interaction['doc_interaction_id'] = $newLessonId;
		$model->saveInteraction($interaction);

		// Step 5. Add entry in the xref table
		// Check logged-in user associated with passed cluster_id
		$assignUserCount = $model->getAssignedTodoCount($newLessonId);

		// Execute the store() method if document not assigned to any user
		if (!$assignUserCount || $user->authorise('core.admin'))
		{
			$xrefTable->reset();
			$xrefTable->id = 0;
			$xrefTable->load(array('lesson_id' => $lessonId, 'cluster_id' => $schoolId));

			if ($singleDocument)
			{
				$xrefTable->reset();
				$xrefTable->id = 0;
				$xrefTable->load(array('lesson_id' => $lessonId));
			}

			$xrefTable->cluster_id = $schoolId;
			$xrefTable->lesson_id = $newLessonId;
			$xrefTable->store();

			
			PluginHelper::importPlugin('system', 'dpe_tjlms_cluster');
			Factory::getApplication()->triggerEvent('onAfterLessonSendEmail', array($xrefTable->lesson_id, $schoolId));
		}

		echo new JsonResponse($xrefTable->id, Text::_('COM_DPE_COPY_DOCUMENT_SAVED'));
		$app->close();
	}
	public function getLessonsAsPerCluster(){

		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		$clusterId  = $app->input->getInt('clusterId', 0);

		$model = $this->getModel('lesson', '', array("ignore_request" => true));
		$lessons = $model->getLessonsAsPerCluster($clusterId);

		if ($lessons)
		{
			echo new JsonResponse($lessons);
		   $app->close();
		}

	}

	/**
	 * AJAX handler to return a message if the current user has assigned To-Do items in the queue.
	 *
	 * Retrieves TODO items from the queue where:
	 * - The 'properties' column JSON contains: {"client": "jlike.todos"}
	 * - The 'body' column JSON has an 'assigned_by' value matching the given userID
	 *
	 * @return void  Outputs JSON response and terminates the request.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getTodosFromQueue()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$model = $this->getModel('lesson');

		// Get userID from the AJAX request
		$userID = $input->getInt('userID');

		// Retrieve all JLike To-Do queue items
		$todoItems = $model->getUserAssignedTodosFromQueue($userID);

		// Filter todoItems where 'assigned_by' in the 'body' JSON matches the current user
		foreach ($todoItems as $todoItem) {
			$bodyJson = json_decode($todoItem['body'], true);

			if (isset($bodyJson['assigned_by']) && (int) $bodyJson['assigned_by'] === $userID) {
				// Match found — respond with success message
				$message = Text::_("COM_DPE_TODOS_FROM_QUEUE_FOUND");
				echo new JsonResponse(['message' => $message], true);
				$app->close(); // Stop further execution
			}
		}

		// No match: return empty success response (to suppress errors on frontend)
		echo new JsonResponse(null, true);
		$app->close();
	}
}
