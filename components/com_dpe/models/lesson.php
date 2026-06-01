<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;


JLoader::import('components.com_tjlms.models.lesson', JPATH_ADMINISTRATOR);

/**
 * Lesson Model to perform dpe specific operations
 *
 * @since  1.0.0
 */
class DpeModelLesson extends TjlmsModelLesson
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
	 * Function getAssignedTodoCount to get assigned user count
	 *
	 * @param   int  $lessonId  The lesson id
	 *
	 * @return  integer
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getAssignedTodoCount($lessonId)
	{
		$assignUserCount = 0;

		if (!$lessonId)
		{
			return $assignUserCount;
		}

		// Get com_jlike component status
		$jlikeExist = ComponentHelper::getComponent('com_jlike', true)->enabled;

		if ($jlikeExist)
		{
			// Include helper file to get todoid and contentid
			$path = JPATH_SITE . '/components/com_jlike/helper.php';

			if (!class_exists('ComjlikeHelper'))
			{
				JLoader::register('ComjlikeHelper', $path);
				JLoader::load('ComjlikeHelper');
			}

			$comJlikeHelper = new ComjlikeHelper;

			// To get content_id of jlike based on lesson_id and element 'com_tjlms.lesson'
			$contentId = $comJlikeHelper->getContentId($lessonId, 'com_tjlms.lesson');

			if (!empty($contentId))
			{
				require_once JPATH_SITE . '/components/com_jlike/models/recommendations.php';
				$jlikeModel      = BaseDatabaseModel::getInstance('Recommendations', 'JlikeModel');
				$assignUserCount = $jlikeModel->getTotalRecommendation($contentId);
			}
		}

		return $assignUserCount;
	}

	/**
	 * Function to add interactions against the documents
	 *
	 * @param   array  $data  the form data with the interaction values
	 *
	 * @return  integer
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function saveInteraction($data)
	{
		$docInteraction = $data['doc_interaction_id'];
		$pluginObj      = PluginHelper::getPlugin('content', 'jlike_tjlmslesson');
		$pluginParams   = new Registry($pluginObj->params);

		// Load contentform model to get content id
		JLoader::import('contentform', JPATH_SITE . '/components/com_jlike/models');
		$jlikeModelContentForm = new JlikeModelContentForm;

		$intParams = array();

		foreach ($data as $key => $interactionWay)
		{
			if ($pluginParams->get($key))
			{
				$intParams[$key] = $pluginParams->get($key);
			}
		}

		// As Read & Understood is compulsory and bydefault set Yes
		if (!$intParams['read_interaction'])
		{
			$intParams['read_interaction'] = 1;
		}

		$data = array();
		$data['element']    = 'com_tjlms.lesson';
		$data['url']        = 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $docInteraction;
		$data['element_id'] = $docInteraction;
		$data['title']      = $this->getLessonDetails($docInteraction)->title;
		$data['params']     = (!empty($intParams)) ? json_encode($intParams) : '';
		$contentId          = JlikeModelContentForm::getContentID($data);

		// This will execute in edit case
		$data['id'] = $contentId;
		$contentId  = $jlikeModelContentForm->save($data);

		return $contentId ? $contentId : 0;
	}

	public function getLessonsAsPerCluster($clusterId)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('l.id, l.title');
		$query->from('`#__tjlms_lessons` AS l');
		$query->join('INNER', $db->qn('#__tjlms_lesson_cluster_xref', 'tjc') . 'ON(' . $db->qn('tjc.lesson_id') . '=' . $db->qn('l.id') . ')');
		$query->order($db->escape('l.title ASC'));

		if ((int) $clusterId)
		{
			$query->where('tjc.cluster_id=' . (int) $clusterId);
		}

		$db->setQuery($query);

		// Get all lessons as per .
		$allLessons = $db->loadObjectList();

		$options = array();

		foreach ($allLessons as $l)
		{
			$options[] = HTMLHelper::_('select.option', $l->id, $l->title);
		}

		if (!empty($options))
		{
			return $options;	
		}else
		{
			return $result['suceess'] = 'fail';
		}
		
	}
	/**
	 * Retrieves TODO items from the queue that are assigned by a specific user.
	 *
	 * This method fetches records from the `#__enqueue` table where the `properties` field contains
	 * the JSON value `"client": "jlike.todos"`. It is used to identify To-Do queue items related to JLike.
	 *
	 * @param   int  $userID  The user ID to check assigned todos for.
	 *
	 * @return  array  List of matched To-Do queue items (with 'id' and 'body' fields).
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getUserAssignedTodosFromQueue($userID)
	{
		// Get the database object
		$db = Factory::getDbo();

		// Build query: fetch rows where properties contain '"client":"jlike.todos"'
		$query = $db->getQuery(true)
			->select($db->quoteName(['id', 'body']))
			->from($db->quoteName('#__enqueue')) // Replace with your actual enqueue table name if needed
			->where($db->quoteName('properties') . ' LIKE ' . $db->quote('%"client":"jlike.todos"%'));

		$db->setQuery($query);

		// Fetch all matching To-Do queue items
		$todoItems = $db->loadAssocList();

		return $todoItems;
	}
}
