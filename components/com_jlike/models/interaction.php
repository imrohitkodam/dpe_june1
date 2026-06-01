<?php
/**
 * @package     JLike
 * @subpackage  COM_JLIKE
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
jimport('joomla.application.component.modeladmin');

/**
 * Interaction model class
 *
 * @since  1.0.0
 */
class JlikeModelInteraction extends AdminModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JModelLegacy
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		$tjlmsLessonHelperPath = JPATH_ROOT . '/components/com_tjlms/helpers/lesson.php';

		if (!class_exists('TjlmsLessonHelper'))
		{
			JLoader::register('TjlmsLessonHelper', $tjlmsLessonHelperPath);
			JLoader::load('TjlmsLessonHelper');
		}

		parent::__construct($config);
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $type    The table name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table | boolean  A Table object
	 *
	 * @since   1.0.0
	 */
	public function getTable($type = 'TodosExtend', $prefix = 'JlikeTable', $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jlike/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method for getting the form from the model.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \JForm|boolean  A \JForm object on success, false on failure
	 *
	 * @since   1.0.0
	 */
	public function getForm($data = [], $loadData = true)
	{
		return false;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return	mixed	Object on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Do any procesing on fields here if needed
		}

		return $item;
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   \JForm  $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 *
	 * @since   1.0.0
	 */
	public function validate($form, $data, $group = null)
	{
		$return = $data;
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jlike/models');
		$JLikeTodoModel = BaseDatabaseModel::getInstance('Todo', 'JLikeModel');
		$todoData = $JLikeTodoModel->getContent($data['todo_id']);

		// Check the assignee of the todo
		if (empty($todoData) || $todoData->assigned_to != Factory::getUser()->id)
		{
			$this->setError(Text::_('COM_JLIKE_INTERACTION_SAVE_INVALID_USER'));

			return false;
		}

		// Check whether lesson is valid or not
		$tjlmsLessonHelper = new tjlmsLessonHelper;

		$lesson = $tjlmsLessonHelper->getLesson($todoData->element_id);

		if (!$lesson->id || $lesson->state != 1 || $todoData->element != 'com_tjlms.lesson')
		{
			$this->setError(Text::_('COM_JLIKE_INTERACTION_SAVE_INVALID_REQUEST'));

			return false;
		}

		// Now check if the lesson is complete or not
		$isLessonComplete = $this->isLessonCompleted($todoData->element_id);

		if (!$isLessonComplete)
		{
			$this->setError(Text::_('COM_JLIKE_INTERACTION_SAVE_LESSON_NOT_COMPLETED'));
			$return = false;
		}

		return $return;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   1.0.0
	 */
	public function save($data)
	{
		// Check for the lesson completion
		$save = parent::save($data);

		if (!$save)
		{
			return $save;
		}

		/**
		 * Now check which interactions are eabled for the current lesson
		 * if all the interactions are completed then complete the todo
		 */
		$item = $this->getItem($data['todo_id']);
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jlike/models');
		/** @var $JLikeTodoModel JlikeModelTodo */
		$JLikeTodoModel = BaseDatabaseModel::getInstance('Todo', 'JLikeModel');
		$todoContent = $JLikeTodoModel->getContent($data['todo_id']);
		$JLikeContentModel = BaseDatabaseModel::getInstance('Content', 'JLikeModel');

		/** @scrutinizer ignore-call */
		$contentData = $JLikeContentModel->getData($todoContent->content_id);
		$savedInteractions = json_decode($contentData->params);

		$jlikeTjlmslessonPlugin = PluginHelper::getPlugin('content', 'jlike_tjlmslesson');
		$enabledInterations = json_decode($jlikeTjlmslessonPlugin->params);
		$updateParent = true;

		foreach ($savedInteractions as $key => $value)
		{
			if ($enabledInterations->$key == 1)
			{
				switch ($key)
				{
					case 'read_interaction':
						if (property_exists($item, 'read'))
						{
							$item->read == 1 ? '' : $updateParent = false;
						}
						break;
					case 'practice_interaction':
						if (property_exists($item, 'used'))
						{
							$item->used == 1 ? '' : $updateParent = false;
						}
						break;
				}
			}
		}

		if ($updateParent && !empty($data['todo_id']))
		{
			$saveData = array();
			$saveData['id'] = $data['todo_id'];
			$saveData['assigned_to'] = Factory::getUser()->id;
			$saveData['status'] = 'C';

			return $JLikeTodoModel->save($saveData);
		}

		return $save;
	}

	/**
	 * Method to check whether lesson is completed or not
	 *
	 * @param   integer  $lessonId  The lesson id
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   1.0.0
	 */
	private function isLessonCompleted($lessonId)
	{
		$tjlmsLessonHelper = new tjlmsLessonHelper;
		$statusandscore = $tjlmsLessonHelper->getLessonScorebyAttemptsgrading($lessonId, Factory::getUser()->id);

		return (!empty($statusandscore) && ($statusandscore->lesson_status == 'completed' || $statusandscore->lesson_status == 'passed'));
	}
}
