<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Object\CMSObject;
use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.modeladmin');
jimport('joomla.filesystem.folder');
jimport('techjoomla.common');

/**
 * Tjlms model.
 *
 * @since  1.0.0
 */
class TjlmsModelAssessment extends AdminModel
{
	protected $text_prefix = 'COM_TJLMS';

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   type    $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return    JTable    A database object
	 *
	 * @since    1.6
	 */
	public function getTable($type = 'assessmentset', $prefix = 'TjlmsTable', $config = array())
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $setId  The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 */
	public function getItem($setId = null)
	{
		if ($item = parent::getItem($setId))
		{
			$db    = Factory::getDbo();

			$query = $db->getQuery(true);
			$query->select('params.*');
			$query->from('`#__tjlms_assessment_rating_parameters` as params');
			$query->where('params.id = ' . $item->id);

			$db->setQuery($query);
			$item->aseessment_params = json_encode($db->loadObjectlist());
		}

		return $item;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $lessonId  The id of the primary key.
	 *
	 * @return   mixed  Object on success, false on failure.
	 *
	 * @since  1.0.0
	 */
	public function getLessonItem($lessonId)
	{
		$item = new stdClass;

		if ($lessonId)
		{
			$db    = Factory::getDbo();

			$query = $db->getQuery(true);
			$query->select('aset.*');
			$query->from('`#__tjlms_assessmentset_lesson_xref` as xref');
			$query->leftjoin('`#__tjlms_assessment_set` as aset ON aset.id = xref.set_id');
			$query->where('xref.lesson_id = ' . $lessonId);
			$db->setQuery($query);
			$item = $db->loadObject();

			if ($item)
			{
				$query = $db->getQuery(true);
				$query->select('params.*');
				$query->from('`#__tjlms_assessment_rating_parameters` as params');
				$query->where('params.set_id = ' . $item->id);

				$db->setQuery($query);
				$item->assessment_params = json_encode($db->loadObjectlist());
			}
		}

		return $item;
	}

	/**
	 * Function used to get Jform of assignment
	 *
	 * @param   array    $data      data to be used for form
	 *
	 * @param   boolean  $loadData  boolean value
	 *
	 * @return Object of the form
	 *
	 * @since	1.0
	 **/
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app	= Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tjlms.assessment', 'assessment', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Function used to store assessment parameters
	 *
	 * @param   array  $assessmentData  data
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function save($assessmentData)
	{
		// Get post data
		$db    = Factory::getDBO();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$assessxreftable = Table::getInstance('Assessmentxref', 'TjlmsTable', array('dbo', $db));
		$assessxreftable->load(array('lesson_id' => $assessmentData['lesson_id']));
		$properties = $assessxreftable->getProperties(1);
		$assessxreftable = ArrayHelper::toObject($properties, CMSObject::class);

		$set_id = 0;

		if ($assessxreftable->id && $assessxreftable->set_id)
		{
			$set_id = $assessxreftable->set_id;
		}

		// To check if the assessment is set to yes
		if ($assessmentData['add_assessment'] == 1)
		{
			/*
			 *If assessment set id is not provided, that means new assessment is created
			 *For now we will keep the lesson title as set title
			 */
			$assessettable = $this->getTable('Assessmentset', 'TjlmsTable', array('dbo', $db));
			$assessettable->id = $set_id;
			$assessettable->assessment_title = "Assessment for " . $assessmentData['lesson_id'];
			$assessettable->assessment_attempts = $assessmentData['assessment_attempts'];
			$assessettable->assessment_attempts_grade = $assessmentData['assessment_attempts_grade'];
			$assessettable->allow_attachments = $assessmentData['allow_attachments'];
			$assessettable->assessment_answersheet = $assessmentData['assessment_answersheet'];
			$assessettable->answersheet_options = '';
			$assessettable->assessment_student_name = $assessmentData['assessment_student_name'];

			if ($assessettable->assessment_answersheet == 1)
			{
				$assessettable->answersheet_options = json_encode($assessmentData['answersheet_options']);
			}

			$assessettable->store();

			$set_id = $assessettable->id;

			$assessxreftable = Table::getInstance('Assessmentxref', 'TjlmsTable', array('dbo', $db));
			$assessxreftable->load(array('lesson_id' => $assessmentData['lesson_id'], 'set_id' => $set_id));

			if (!$assessxreftable->id)
			{
				$assessxreftable->lesson_id = $assessmentData['lesson_id'];
				$assessxreftable->set_id = $set_id;
				$assessxreftable->store();
			}

			$paramsTable = Table::getInstance('Assessmentparameter', 'TjlmsTable', array('dbo', $db));

			$tempParams = array();

			// Add assessment criterias/params
			foreach ($assessmentData["assessment_params"] as $criteria)
			{
				if ($criteria['title'])
				{
					$paramsTable->id = $criteria['id'];
					$paramsTable->set_id = $set_id;
					$paramsTable->title = $criteria['title'];
					$paramsTable->value = $criteria['value'];
					$paramsTable->type = $criteria['type'];
					$paramsTable->weightage = $criteria['weightage'];
					$paramsTable->description = $criteria['description'];
					$paramsTable->allow_comment = $criteria['allow_comment'];
					$paramsTable->ordering = 0;
					$paramsTable->params = '';
					$paramsTable->store();

					$tempParams[] = $paramsTable->id;
				}
			}

			if (!empty($tempParams))
			{
				// Delete params not given in an array
				$query      = $db->getQuery(true);
				$conditions = array(
					$db->quoteName('id') . ' NOT IN (' . implode(",", $tempParams) . ')',
					$db->quoteName('set_id') . ' = ' . (int) $set_id
				);

				$query->delete($db->quoteName('#__tjlms_assessment_rating_parameters'));
				$query->where($conditions);
				$db->setQuery($query);
				$result = $db->execute();
			}

			return $set_id;
		}
		elseif ($assessmentData['add_assessment'] == 0)
		{
			$query = $db->getQuery(true);
			$conditions_set = array($db->quoteName('id') . ' = ' . $db->quote($assessmentData['set_id']));
			$query->delete($db->quoteName('#__tjlms_assessment_set'));
			$query->where($conditions_set);
			$db->setQuery($query);
			$result = $db->execute();

			$query = $db->getQuery(true);
			$conditions_para = array($db->quoteName('set_id') . ' = ' . $db->quote($assessmentData['set_id']));
			$query->delete($db->quoteName('#__tjlms_assessment_rating_parameters'));
			$query->where($conditions_para);
			$db->setQuery($query);
			$result = $db->execute();

			$query = $db->getQuery(true);
			$conditions_xref = array($db->quoteName('set_id') . ' = ' . $db->quote($assessmentData['set_id']));
			$query->delete($db->quoteName('#__tjlms_assessmentset_lesson_xref'));
			$query->where($conditions_xref);
			$db->setQuery($query);
			$result = $db->execute();

			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$lessonTable = Table::getInstance('lesson', 'TjlmsTable', array('dbo', $db));
			$lessonTable->load(array('id' => $assessmentData['lesson_id']));

			$lessonTable->total_marks = 0;
			$lessonTable->passing_marks = 0;
			$lessonTable->store();

			return true;
		}
	}
}
