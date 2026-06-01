<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.application.component.modellist');
jimport('joomla.filesystem.folder');
/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0
 */
class TjlmsModelModules extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see  JController
	 *
	 * @since    1.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'ordering', 'a.ordering',
				'state', 'a.state',
				'created_by', 'a.created_by',
				'name', 'a.name',
				'course_id', 'a.course_id',

			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   String  $ordering   optional Ordering
	 *
	 * @param   String  $direction  optional direction
	 *
	 * @return  void
	 *
	 * @since	1.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = Factory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_tjlms');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.name', 'asc');
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since	1.0
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 *
	 * @since	1.0
	 */
	protected function getListQuery()
	{
		$query = $this->_db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
				$this->getState(
						'list.select', 'a.*'
				)
		);
		$query->from($this->_db->qn('#__tjlms_modules', 'a'));

		// Join over the users for the checked out user
		$query->select($this->_db->qn('uc.name', 'editor'));
		$query->join('LEFT', $this->_db->qn('#__users', 'uc') . ' ON (' . $this->_db->qn('uc.id') . ' = ' . $this->_db->qn('a.checked_out') . ')');

		/* Join over the user field 'created_by'
		/*$query->select('created_by.name AS created_by');
		$query->join('LEFT', '#__users AS created_by ON created_by.id = a.created_by');*/

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where($this->_db->qn('a.state') . ' = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where($this->_db->qn('a.state') . ' IN (0, 1)');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($this->_db->qn('a.id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				$search = '%' . $this->_db->escape($search, true) . '%';
				$query->where($this->_db->quoteName('a.name') . ' LIKE ' . $this->_db->quote($search, false));
			}
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Get Items
	 *
	 * @return	void
	 *
	 * @since	1.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		// Convert date from utc to local
		return $items;
	}

	/**
	 * Function used to get the module data for a course.
	 * Used in module list view for a course. Lessons info is append to the array of the module data.
	 * Also this function is used when only module data is needed
	 *
	 * @param   INT  $modId  optional id of the module
	 *
	 * @return  void
	 *
	 * @since	1.0
	 */
	public function getModuleData($modId='')
	{
		try
		{
			$query = $this->_db->getQuery(true);

			$input = Factory::getApplication()->input;
			$courseId = $input->get('course_id', 0, 'INT');
			$params = ComponentHelper::getParams('com_tjlms');
			$allowAssocFiles = $params->get('allow_associate_files', '0', 'INT');

			if ($modId)
			{
				// Added to query id the module ID is present so only single module data is genarated.
				$where = ' AND ' . $this->_db->qn('id') . ' = ' . $this->_db->q((int) $modId);
			}
			else
			{
				$where = ' ORDER BY  `ordering` ASC ';
			}

			$query->select('*');
			$query->from($this->_db->qn('#__tjlms_modules'));
			$query->where($this->_db->qn('course_id') . ' = ' . $this->_db->q((int) $courseId) . '' . $where);

			$this->_db->setQuery($query);

			if ($modId)
			{
				// As single data has been fetch
				$moduleData = $this->_db->loadAssoc();
			}
			else
			{
				$moduleData = $this->_db->loadobjectlist();

				// For each added to append lesson data for each Module. require for module list page of a course.
				foreach ($moduleData as $modData)
				{
					$config = Factory::getConfig();
					$offset = $config->get('offset');

					// Get leesons info for each module
					$queryForLessons = $this->_db->getQuery(true);
					$queryForLessons->select('l.*');
					$queryForLessons->from($this->_db->qn('#__tjlms_lessons', 'l'));
					$queryForLessons->where($this->_db->qn('l.mod_id') . ' = ' . $this->_db->q((int) $modData->id));

					$queryForLessons->order('`ordering` ASC');

					$this->_db->setQuery($queryForLessons);
					$moduleLessons = $this->_db->loadobjectlist();

					foreach ($moduleLessons as $ind => $l_obj)
					{
						$format = $l_obj->format;
						$l_obj->assessment = 0;

						if (!empty($l_obj->media_id))
						{
							$queryForFormat = $this->_db->getQuery(true);
							$queryForFormat->select($this->_db->qn(array('m.sub_format','m.format', 'm.source')));
							$queryForFormat->from($this->_db->qn('#__tjlms_media', 'm'));
							$queryForFormat->join('LEFT', $this->_db->qn('#__tjlms_lessons', 'l') . '
							ON(' . $this->_db->qn('l.media_id') . '=' . $this->_db->qn('m.id') . ')');
							$queryForFormat->where($this->_db->qn('l.id') . ' = ' . $this->_db->q((int) $l_obj->id));

							$this->_db->setQuery($queryForFormat);
							$res = $this->_db->loadObject();

							$l_obj->sub_format = $l_obj->format = '';

							if (!empty($res))
							{
								$plg_type = 'tj' . $res->format;

								$format_subformat = explode('.', $res->sub_format);
								$plg_name = $format_subformat[0];

								// Get assessment value
								$plugin = PluginHelper::getPlugin($plg_type, $plg_name);
								$params = new Registry($plugin->params);
								$assessment = $params->get('assessment', '0');
								$l_obj->assessment = $assessment;

								PluginHelper::importPlugin($plg_type);
								$checkFormat = Factory::getApplication()->triggerEvent('onAdditional' . $plg_name . 'FormatCheck', array($l_obj->id, $res));

								if (!empty($checkFormat[0]))
								{
									$format_res = $checkFormat[0];

									if ($format_res)
									{
										$l_obj->sub_format = $format_res->sub_format;
										$l_obj->format = $format_res->format;
										$l_obj->media_source = $format_res->source;
									}
								}
							}
						}

						if ($allowAssocFiles == 1)
						{
							$query = $this->_db->getQuery(true);
							$query->select($this->_db->qn('f.org_filename', 'filename'));
							$query->select($this->_db->qn(array('af.id', 'af.media_id', 'f.source' , 'f.storage')));
							$query->from($this->_db->qn('#__tjlms_media', 'f'));
							$query->join('INNER', $this->_db->qn('#__tjlms_associated_files', 'af') . '
							ON (' . $this->_db->qn('af.media_id') . ' = ' . $this->_db->qn('f.id') . ')');
							$query->where($this->_db->qn('af.lesson_id') . ' = ' . $this->_db->q((int) $l_obj->id));

							$this->_db->setQuery($query);
							$l_obj->oldAssociateFiles = $this->_db->loadObjectList();

							if (!empty($l_obj->oldAssociateFiles))
							{
								JLoader::import('components.com_tjlms.libraries.storage', JPATH_SITE);
								$tjStorage = new Tjstorage;

								foreach ($l_obj->oldAssociateFiles as $key => $assocFile)
								{
									if ($assocFile->storage == 'invalid')
									{
										unset($l_obj->oldAssociateFiles[$key]);

										continue;
									}

									$storage 	= $tjStorage->getStorage($assocFile->storage);
									$fileExists = $storage->exists('media/com_tjlms/lessons/' . $assocFile->source);

									if (!$fileExists)
									{
										unset($l_obj->oldAssociateFiles[$key]);
									}
								}
							}
						}

						/* Durgesh added for max no_of_attempts */
						$query = $this->_db->getQuery(true);
						$query->select('max(attempt) as total, l.no_of_attempts');
						$query->from($this->_db->qn('#__tjlms_lesson_track', 'lt'));
						$query->leftjoin($this->_db->qn('#__tjlms_lessons', 'l') . ' ON (' . $this->_db->qn('l.id') . ' = ' . $this->_db->qn('lt.lesson_id') . ')');
						$query->where($this->_db->qn('lesson_id') . '  = ' . $this->_db->q((int) $l_obj->id));

						$this->_db->setQuery($query);
						$result = $this->_db->loadobject();
						$l_obj->max_attempt = $result->total;
						/* Durgesh added for max no_of_attempts */
					}

					$modData->lessons = $moduleLessons;
				}
			}

			return $moduleData;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * function is used to save sorting of modules.
	 *
	 * @param   int  $courseId  Course id
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 **/
	public function getModuleOrderList($courseId)
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn(array('id', 'ordering')));
			$query->from($this->_db->qn('#__tjlms_modules'));
			$query->where($this->_db->qn('course_id') . ' = ' . $this->_db->q((int) $courseId));
			$this->_db->setQuery($query);
			$moduleOrder = $this->_db->loadobjectlist();

			if (!empty($moduleOrder) && count($moduleOrder) > 0)
			{
				$list = array();

				foreach ($moduleOrder as $key => $m_order)
				{
					$list[ $m_order->id ] = $m_order->ordering;
				}

				return $list;
			}
			else
			{
				return false;
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 *	Save ordering for modules whose ordering has been change
	 *
	 * @param   int  $key       module_id
	 *
	 * @param   int  $newRank   new ordering
	 *
	 * @param   int  $courseId  Course id
	 *
	 * @return boolean
	 *
	 * @since	1.0
	 **/
	public function switchOrder($key, $newRank, $courseId)
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->update($this->_db->qn('#__tjlms_modules'));
			$query->set($this->_db->qn('ordering') . '=' . $this->_db->q((int) $newRank));
			$query->where($this->_db->qn('id') . '=' . $this->_db->q((int) $key));
			$query->where($this->_db->qn('course_id') . '=' . $this->_db->q((int) $courseId));
			$this->_db->setQuery($query);
			$this->_db->execute();

			return true;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function used to delete the module of a course.
	 *	Ordering of rest of the module is updated accordingly
	 *
	 * @param   int  $moduleId  module id
	 *
	 * @return boolean
	 *
	 * @since  1.0
	 **/
	public function deleteModule($moduleId)
	{
		try
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
			$moduleModel = BaseDatabaseModel::getInstance('Module', 'TjlmsModel');
			$module = $moduleModel->getItem($moduleId);
			$courseId = $module->course_id;

			// Get the order of the module which has to be deleted

			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn('ordering'))
					->from($this->_db->qn('#__tjlms_modules'))
					->where($this->_db->qn('course_id') . ' = ' . $this->_db->q((int) $courseId))
					->where($this->_db->qn('id') . ' = ' . $this->_db->q((int) $moduleId));

			$this->_db->setQuery($query);
			$currentOrder = $this->_db->loadResult();

			// Update the order for rest of the module
			$query = $this->_db->getQuery(true);
			$query->update($this->_db->qn('#__tjlms_modules'))
					->set($this->_db->qn('ordering') . ' = `ordering`-1')
					->where($this->_db->qn('ordering') . ' > ' . $this->_db->q((int) $currentOrder))
					->where($this->_db->qn('course_id') . ' = ' . $this->_db->q((int) $courseId));
			$this->_db->setQuery($query);

			// Delete the module
			$query = $this->_db->getQuery(true);
			$query->delete($this->_db->quoteName('#__tjlms_modules'))
					->where($this->_db->qn('id') . ' = ' . $this->_db->q((int) $moduleId))
					->where($this->_db->qn('course_id') . ' = ' . $this->_db->q((int) $courseId));
			$this->_db->setQuery($query);

			if ($this->_db->execute())
			{
				return 1;
			}
			else
			{
				return 0;
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function used to chage the state of the module
	 *
	 * @param   int  $moduleId  module id
	 *
	 * @param   int  $state     State to be assigned
	 *
	 * @return boolean
	 *
	 * @since  1.0
	 **/
	public function changeState($moduleId, $state)
	{
		try
		{
			$query = $this->_db->getQuery(true);

			// Update the state  of the module

			$query->update($this->_db->qn('#__tjlms_modules'))
					->set($this->_db->qn('state') . ' = ' . $this->_db->q((int) $state))
					->where($this->_db->qn('id') . ' = ' . $this->_db->q((int) $moduleId));
			$this->_db->setQuery($query);

			if ($this->_db->execute())
			{
				return 1;
			}
			else
			{
				return 0;
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function used to get the current course Info
	 *
	 * @param   INT  $courseId  id out the course
	 *
	 * @return Object of the course
	 *
	 * @since	1.0
	 * */
	public function getPresentCourseInfo($courseId)
	{
		$courseInfo = '';

		if ($courseId)
		{
			$tjlmsCoursesHelper = new tjlmsCoursesHelper;
			$courseInfo = $tjlmsCoursesHelper->getcourseInfo($courseId);

			if ($courseInfo)
			{
				// Get image to be shown for course
				$courseInfo->image = $tjlmsCoursesHelper->getCourseImage((array) $courseInfo, 'S_');
			}

			return $courseInfo;
		}
	}

	/**
	 * Function used to get Jform of lesson
	 *
	 * @param   array    $data      data to be used for form
	 *
	 * @param   boolean  $loadData  boolean value
	 *
	 * @return Object of the form
	 *
	 * @since	1.0
	 * */
	public function getLessonForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app	= Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tjlms.lesson', 'lesson', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Function used to get all sub formats
	 *
	 * @param   string  $format_name  name of the format
	 *
	 * @return select list of sub formats
	 *
	 * @since  1.0.0
	 */
	public function getallSubFormats($format_name)
	{
		$format_name = 'tj' . $format_name;
		PluginHelper::importPlugin($format_name);
		$formats = Factory::getApplication()->triggerEvent('onGetSubFormat_' . $format_name . 'ContentInfo');

		if (!empty($formats))
		{
			return $formats;
		}
		else
		{
			return '';
		}

		$subformat = array();

		foreach ($formats as $format)
		{
			$subformat[] = JHTML::_('select.option', $format['id'], $format['name']);
		}

		$subformat_options = JHTML::_('select.genericlist', $subformat, "lesson_format[" . $format_name . "_subformat]",
							'class="class_' . $format_name . '_subformat" onchange="getVideosubFormat(this);"', "value", "text");

		return $subformat_options;
	}

	/**
	 * Function used to get all sub formats
	 *
	 * @param   INT     $lesson_id   lesson_id
	 * @param   string  $format      format of the lesson
	 * @param   string  $sub_format  sub format of the lesson
	 * @param   string  $form_id     Unique form id
	 *
	 * @return  Object list of files
	 *
	 * @since  1.0.0
	 *
	 */
	public function getallSubFormats_HTML($lesson_id, $format, $sub_format, $form_id)
	{
		try
		{
			$query = $this->_db->getQuery(true);

			$query->select($this->_db->qn(array('l.mod_id', 'l.media_id', 'l.course_id')));
			$query->select($this->_db->qn(array('m.sub_format', 'm.format', 'm.params', 'm.org_filename', 'm.source')));
			$query->select($this->_db->qn(array('l.id', 'm.id', 'm.storage'), array('lesson_id', 'media_id', 'media_storage')));
			$query->from($this->_db->qn('#__tjlms_lessons', 'l'));
			$query->join('LEFT', $this->_db->qn('#__tjlms_media', 'm') . ' ON (' . $this->_db->qn('l.media_id') . ' = ' . $this->_db->qn('m.id') . ')');
			$query->where($this->_db->qn('l.id') . ' = ' . $this->_db->q((int) $lesson_id));
			$this->_db->setQuery($query);

			$lesson = $this->_db->loadObject();

			$lesson_id = $lesson->lesson_id;

			if (!empty($lesson->mod_id))
			{
				$mod_id = $lesson->mod_id;
			}

			$format = 'tj' . $format;
			$comp_params = ComponentHelper::getParams('com_tjlms');

			PluginHelper::importPlugin($format, $sub_format);

			// Call the plugin and get the result
			$results = Factory::getApplication()->triggerEvent('getSubFormat_' . $sub_format . 'ContentHTML', array($mod_id, $lesson_id, $lesson, $comp_params, $form_id));

			if (!empty($results))
			{
				return $results[0];
			}
			else
			{
				return '';
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function used to get Jform of assignment
	 *
	 * @param   int      $l_id      lesson_id
	 *
	 * @param   array    $data      data to be used for form
	 *
	 * @param   boolean  $loadData  boolean value
	 *
	 * @return Object of the form
	 *
	 * @since	1.0
	 **/
	public function getAssessmentForm($l_id, $data = array(), $loadData = true)
	{
		// Initialise variables.
		$app	= Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tjlms.assessment', 'assessment', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		if (!empty($l_id))
		{
			$form->reset();
			$dataExtra = $this->getAssessmentFields($l_id);
			$form->bind($dataExtra);
		}
		else
		{
			$form->bind(array());
			$form->reset();
		}

		return $form;
	}

	/**
	 * Function used to get assessment details of the lesson
	 *
	 * @param   int  $id  lesson_id
	 *
	 * @return Object of the form
	 *
	 * @since	1.0
	 **/
	public function getAssessmentFields($id)
	{
		$item = new stdClass;
		$item->add_assessment = 0;
		$item->aseessment_params = '';

		if (!empty($id))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('aset.*');
			$query->from('`#__tjlms_assessmentset_lesson_xref` as xref');
			$query->leftjoin('`#__tjlms_assessment_set` as aset ON aset.id = xref.set_id');
			$query->where('xref.lesson_id = ' . $id);
			$db->setQuery($query);
			$item = $db->loadObject();

			if ($item)
			{
				$item->add_assessment = 1;

				$query = $db->getQuery(true);
				$query->select('params.*');
				$query->from('`#__tjlms_assessment_rating_parameters` as params');
				$query->where('params.set_id = ' . $item->id);
				$query->order('ordering');

				$db->setQuery($query);
				$item->aseessment_params = json_encode($db->loadObjectlist());
			}
		}

		if (!empty($item))
		{
			return $item;
		}
	}
}
