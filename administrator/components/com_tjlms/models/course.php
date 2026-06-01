<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('techjoomla.common');
/*require_once JPATH_SITE . "/components/com_tjfields/filterFields.php";*/

require_once JPATH_SITE . '/components/com_tjlms/helpers/courses.php';
/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelCourse extends AdminModel
{
	/*use TjfieldsFilterField;*/

	/**
	 * The type alias for this content type (for example, 'com_tjlms.course').
	 *
	 * @var    string
	 * @since  1.3.37
	 */
	public $typeAlias = 'com_tjlms.course';

	/**
	 * Constructor.
	 *
	 * @see     JControllerLegacy
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function __construct()
	{
		$this->ComtjlmsHelper   = new ComtjlmsHelper;

		// Added by renu
		$this->coursesHelper    = new TjlmsCoursesHelper;
		$this->techjoomlacommon = new TechjoomlaCommon;

		parent::__construct();
	}

	/**
	 * @var        string    The prefix to use with controller messages.
	 * @since    1.6
	 */
	protected $text_prefix = 'COM_TJLMS';

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   type    $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return   Table    A database object
	 *
	 * @since    1.6
	 */
	public function getTable($type = 'Course', $prefix = 'TjlmsTable', $config = array())
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return    JForm    A JForm object on success, false on failure
	 *
	 * @since    1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tjlms.course', 'course', array(
																	'control' => 'jform',
																	'load_data' => $loadData
																)
								);

		if (empty($form))
		{
			return false;
		}

		// Disabled the 'expiry' field after the user get certificate against this course.
		if (!empty($form->getValue('id')))
		{
			JLoader::import('components.com_tjcertificate.models.certificates', JPATH_ADMINISTRATOR);
			$tjCertificateModel = BaseDatabaseModel::getInstance('Certificates', 'TjCertificateModel', array('ignore_request' => true));
			$tjCertificateModel->setState('filter.client', 'com_tjlms.course');
			$tjCertificateModel->setState('filter.client_id', $form->getValue('id'));
			$tjCertificateData = $tjCertificateModel->getTotal();

			if ($tjCertificateData > 0)
			{
				$form->setFieldAttribute('expiry', 'readonly', 'true');
			}
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return    mixed    The data for the form.
	 *
	 * @since    1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_tjlms.edit.course.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $id  The id of the primary key.
	 *
	 * @return   mixed  Object on success, false on failure.
	 *
	 * @since    1.6
	 */
	public function getCourse($id)
	{
		return parent::getItem($id);
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return   mixed  Object on success, false on failure.
	 *
	 * @since    1.6
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			$input           = Factory::getApplication()->input;
			$params          = ComponentHelper::getParams('com_tjlms');
			$enable_tags     = $params->get('enable_tags', '0', 'INT');
			$user            = Factory::getUser();

			// Do any procesing on fields here if needed
			if ($input->get('id', '', 'INT'))
			{
				try
				{
					$query = $this->_db->getQuery(true);
					$query->select('tsp.*');
					$query->from($this->_db->qn('#__tjlms_subscription_plans', 'tsp'));
					$query->where($this->_db->qn('tsp.course_id') . '=' . $input->get('id', '', 'INT'));
					$this->_db->setQuery($query);
					$subsplans = $this->_db->loadObjectlist();
				}
				catch (Exception $e)
				{
					$this->setError($e->getMessage());

					return false;
				}

				if ($subsplans)
				{
					$item->subsplans = $subsplans;
				}

				$item->access = explode('|', $item->access);
				$item->access = array_filter($item->access, "trim");

				if (JVERSION >= '3.0')
				{
					if ($enable_tags == 1)
					{
						if (!empty($item->id))
						{
							$item->tags = new TagsHelper;
							$item->tags->getTagIds($item->id, 'com_tjlms.course');
						}
					}
				}

				if ($params->get('social_integration', '', 'STRING') == 'easysocial')
				{
					if (isset($item->params['esbadges']) && !empty($item->params['esbadges']))
					{
						$item->esbadges = $item->params['esbadges'];
					}
				}

				// Convert parameter fields to objects.
				$registry     = new Registry;
				$item->params = $registry->loadArray($item->params);

				// Technically guest could edit an course, but lets not check that to improve performance a little.
				if (!$user->get('guest'))
				{
					$userId = $user->get('id');
					$asset  = 'com_tjlms.course.' . $item->id;

					// Check general edit permission first.
					if ($user->authorise('core.edit', $asset))
					{
						$item->params->set('access-edit', true);
					}

					// Now check if edit.own is available.
					elseif (!empty($userId) && $user->authorise('core.create', $asset))
					{
						// Check for a valid user and that they are the owner.
						if ($userId == $item->created_by)
						{
							$item->params->set('access-edit', true);
						}
					}

					// Check edit state permission.
					if ($pk)
					{
						// Existing item
						$item->params->set('access-change', $user->authorise('core.edit.state', $asset) || $user->authorise('core.create', $asset));
					}
					else
					{
						// New item.
						$catId = (int) $this->getState('course.catid');

						if ($catId)
						{
							$item->params->set('access-change', $user->authorise('core.edit.state', 'com_tjlms.category.' . $catId));
							$item->catid = $catId;
						}
						else
						{
							$item->params->set('access-change', $user->authorise('core.edit.state', 'com_tjlms') || $user->authorise('core.create', 'com_tjlms'));
						}
					}
				}

				if (empty(User::getTable()->load($item->created_by)))
				{
					$item->created_by = 0;
				}
			}
			else
			{
				// To set today's date as default for new corse
				$item->start_date = Factory::getDate();
			}
		}

		return $item;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   Table  $table  table instance
	 *
	 * @return  void
	 *
	 * @since    1.6
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');
		$query = $this->_db->getQuery(true);

		if (empty($table->id))
		{
			// Set ordering to the last item if not set
			if (@$table->ordering === '')
			{
				$query->select($this->_db->qn('MAX(ordering)'));
				$query->from($this->_db->qn('#__tjlms_courses'));
				$this->_db->setQuery($query);
				$max             = $this->_db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * Method to  save course and its subscription plans
	 *
	 * @param   ARRAY  $data              course data
	 * @param   ARRAY  $extra_jform_data  extra form data
	 *
	 * @return  mixed course ID
	 *
	 * @since    1.0.0
	 */
	public function save($data, $extra_jform_data='')
	{
		if (!empty($data['image']))
		{
			$files = $data['image'];
		}

		if (empty($data['certificate_id']))
		{
			$data['certificate_id'] = '0';
		}

		// Alter the title for save as copy
		$input = Factory::getApplication()->input;

		if ($input->get('task') == 'save2copy')
		{
			$origTable = clone $this->getTable();
			$origTable->load($input->getInt('id'));

			if ($data['title'] == $origTable->title)
			{
				list($title, $alias) = $this->generateNewTitle($data['catid'], $data['alias'], $data['title']);
				$data['title'] = $title;
				$data['alias'] = $alias;
			}
			else
			{
				if ($data['alias'] == $origTable->alias)
				{
					$data['alias'] = '';
				}
			}

			if ($data['type'] == 1)
			{
				foreach ($data['subsplans'] as $key => &$subsplan)
				{
					$subsplan['id'] = 0;
				}
			}

			$data['state'] = 0;
		}

		$id    = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('course.id');
		$table = $this->getTable();
		$table->load($id);

		$user        = Factory::getUser();
		$userId      = $user->get('id');
		$app         = Factory::getApplication();
		$params      = ComponentHelper::getParams('com_tjlms');
		$integration = $params->get('social_integration', '', 'STRING');

		$ownerId     = (int) isset($data['created_by']) ? $data['created_by'] : 0;

		if (empty($ownerId))
		{
			$data['created_by'] = $user->id;
		}

		$canCreate 	= $user->authorise('core.create', 'com_tjlms');
		$manageOwn	= $canCreate && $userId == $data['created_by'];

		if ($id)
		{
			$authorised       = $user->authorise('core.edit', 'com_tjlms.course.' . $data['id']) || $manageOwn;
			$data['modified'] = $this->techjoomlacommon->getDateInUtc(HTMLHelper::date('now', 'Y-m-d H:i:s', true));
		}
		else
		{
			$authorised      = $canCreate;
			$data['created'] = $this->techjoomlacommon->getDateInUtc(HTMLHelper::date('now', 'Y-m-d H:i:s', true));
		}

		if (empty($data['end_date']))
		{
			$data['end_date'] = '0000-00-00 00:00:00';
		}

		if (!$authorised)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->setHeader('status', 403, true);

			return false;
		}

		// Add category if new provided.
		JLoader::register('CategoriesHelper', JPATH_ADMINISTRATOR . '/components/com_categories/helpers/categories.php');

		// Cast catid to integer for comparison
		$catid = (int) $data['catid'];

		// Check if New Category exists
		if ($catid > 0)
		{
			$catid = CategoriesHelper::validateCategoryId($data['catid'], 'com_tjlms');
		}

		// Save New Category
		if ($catid == 0 && $this->canCreateCategory())
		{
			$catData              = array();
			$catData['title']     = $data['catid'];
			$catData['parent_id'] = 1;
			$catData['extension'] = 'com_tjlms';
			$catData['language']  = '*';
			$catData['published'] = 1;

			// Create new category and get catid back
			$data['catid']        = CategoriesHelper::createCategory($catData);
		}

		// Save ES badge if applicable
		if ($integration == 'easysocial' && $data['esbadges'])
		{
			$data['params']['esbadges'] = $data['esbadges'];
		}

		$ComtjlmsHelper    = new ComtjlmsHelper;
		$options['IdOnly'] = 1;
		$enrolled_users    = count($ComtjlmsHelper->getCourseEnrolledUsers($id, $options));
		$courseParams      = json_decode($table->params, true);

		if ($id && $enrolled_users && !empty($courseParams['esgroup']['onAfterEnrollEsGroups']))
		{
			unset($data['params']['esgroup']);
			$data['params']['esgroup'] = $courseParams['esgroup'];
		}

		if (!empty($data['params']))
		{
			$data['params'] = json_encode($data['params']);
		}

		if (empty($data['checked_out']))
		{
			$data['checked_out'] = '0';
		}

		// Bind data
		if (!$table->bind($data))
		{
			$this->setError($table->getError());

			return false;
		}

		// Tweak for image file upload.
		// Store uploaded file path in a temp variable.
		$tempImage = '';

		if (!empty($files['image']['name']))
		{
			$tempImage = $files['image'];
		}

		if (empty($data['id']))
		{
			$data['image'] = '';
		}

		// Attempt to save data
		if (parent::save($data))
		{
			$id = (int) $this->getState($this->getName() . '.id');

			// Trigger on after course.
			if (empty($data['id']) && $id && $data['state'] == 1)
			{
				PluginHelper::importPlugin('system');
				Factory::getApplication()->triggerEvent('onAfterCourseCreation', array(
																	$id,
																	$user->id,
																	$data['title']
																	)
									);
			}
		}

		// Save subscription plans for the course.
		if (isset($data['type']) && $data['type'] == 1)
		{
			$insert_subs_plan = $this->insert_subs_plan($id, $data);
		}

		// Restore the unsetted image file index from data array.
		if ($tempImage != '')
		{
			// Save uploaded image.
			require_once JPATH_SITE . "/components/com_tjlms/helpers/media.php";

			$tjlmsmediaHelper  = new TjlmsMediaHelper;
			$orginale_filename = $tjlmsmediaHelper->imageupload('course');

			if (!$orginale_filename)
			{
				$app->enqueueMessage(Text::_('COM_TJLMS_UPLOAD_IMAGE_ERROR'), 'error');
				$link = Uri::root() . 'administrator/index.php?option=com_tjlms&view=course&layout=edit&id=' . $id;
				$app->redirect($link);

				return false;
			}

			// Save event id into integration xref table.
			$obj          = new stdclass;
			$obj->id      = $id;
			$obj->image   = $orginale_filename;
			$obj->storage = 'local';

			if ($orginale_filename)
			{
				if (!$this->_db->updateObject('#__tjlms_courses', $obj, 'id'))
				{
					echo $this->_db->stderr();

					return false;
				}
			}
		}

		if (!$id)
		{
			return false;
		}

		PluginHelper::importPlugin('system');
		Factory::getApplication()->triggerEvent('onAftercourseCreate', array(
															$id,
															$user->id,
															$data
														)
							);

		return $id;
	}

	/**
	 * Method to  save subscription plans for a course
	 *
	 * @param   INT    $course_id  Course ID
	 * @param   ARRAY  $data       Course related data
	 *
	 * @return  boolean  true false
	 *
	 * @since    1.0.0
	 */
	public function insert_subs_plan($course_id, $data)
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn('id'));
			$query->from($this->_db->qn('#__tjlms_subscription_plans'));
			$query->where($this->_db->qn('course_id') . '=' . (int) $course_id);
			$this->_db->setQuery($query);
			$plan_ids = $this->_db->loadColumn();

			$coursType          = $data['type'];
			$subs_plans         = $data['subsplans'];
			$valid_time_measure = array('day', 'week', 'month', 'year', 'unlimited');

			// Only if course is paid
			if ($coursType == 1)
			{
				foreach ($subs_plans as $each_plan)
				{
					if ($each_plan['time_measure'] == 'unlimited')
					{
						$each_plan['duration'] = '0';
					}

					if (!in_array($each_plan['time_measure'], $valid_time_measure))
					{
						$each_plan['time_measure'] = 'day';
					}

					$obj               = new stdClass;
					$obj->course_id    = $course_id;
					$obj->time_measure = $each_plan['time_measure'];
					$obj->duration     = (int) $each_plan['duration'];
					$obj->price        = $each_plan['price'];
					$obj->title        = $each_plan['title'];
					$obj->access       = $each_plan['access'];

					if ($each_plan['id'] == '')
					{
						$obj->id = '';

						if (isset($obj->duration) && $obj->price != '')
						{
							if (!$this->_db->insertObject('#__tjlms_subscription_plans', $obj, 'id'))
							{
								echo $this->_db->stderr();

								return false;
							}
						}
					}
					else
					{
						$obj->id = $each_plan['id'];

						if (!$this->_db->updateObject('#__tjlms_subscription_plans', $obj, 'id'))
						{
							echo $this->_db->stderr();

							return false;
						}

						if (!empty($plan_ids) && ($key = array_search($each_plan['id'], $plan_ids)) !== false)
						{
							unset($plan_ids[$key]);
						}
					}
				}
			}

			ArrayHelper::toInteger($plan_ids);
			$plan_to_delet = "";

			if (is_array($plan_ids))
			{
				$plan_to_delet = implode(',', $plan_ids);
			}

			$conditions    = array(
				$this->_db->quoteName('id') . ' IN (' . $plan_to_delet . ')'
			);

			if ($plan_to_delet)
			{
				$query = $this->_db->getQuery(true);
				$query->delete($this->_db->quoteName('#__tjlms_subscription_plans'));
				$query->where($conditions);

				$this->_db->setQuery($query);
				$this->_db->execute();
			}

			return true;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function used to delete all data with respect to course
	 *
	 * @param   ARRAY  $cid  array of course ids
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function onafterCourseDelete($cid)
	{
		// Delete all enrolled user list with respect to this course
		$deleteEnrolledUser = $this->deleteEnrolledUserForCourse($cid);

		// Delete all coursetracks of the course 'helper'
		$deleteCourseTracks = $this->coursesHelper->deleteCourseTracks($cid);

		// Delete all lessons with respect to this course
		$deleteLesson = $this->deleteLessonForCourse($cid);

		// Delete all subscription plans with respect to this course
		$deletePlan = $this->deleteSubsPlanForCourse($cid);

		// Delete all orders with respect to this course
		$deleteOrder = $this->deleteOrdersForCourse($cid);

		// Delete all activity of the course
		$deleteActivity = $this->deleteLmsActivities($cid);

		// Delete all modules of the course
		$deleteModules = $this->deleteModulesForCourse($cid);

		// Trigger on after course/s delete
		PluginHelper::importPlugin('system');
		Factory::getApplication()->triggerEvent('onAfterCourseDelete', array($cid));

		return true;
	}

	/**
	 * Delete all activities of the course
	 *
	 * @param   ARRAY  $cid  array of course ids
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function deleteLmsActivities($cid)
	{
		try
		{
			ArrayHelper::toInteger($cid);
			$cidString  = implode(',', $cid);

			$query      = $this->_db->getQuery(true);
			$conditions = array(
				$this->_db->qn('parent_id') . ' IN (' . $cidString . ')'
			);

			$query->delete($this->_db->q('#__tjlms_activities'));
			$query->where($conditions);

			$this->_db->setQuery($query);
			$result = $this->_db->execute();

			return $result;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function used to delete all enrolled users with respect to course
	 *
	 * @param   ARRAY  $cid  array of course ids
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function deleteEnrolledUserForCourse($cid)
	{
		try
		{
			ArrayHelper::toInteger($cid);
			$cidString  = implode(',', $cid);

			$query      = $this->_db->getQuery(true);
			$conditions = array(
				$this->_db->quoteName('course_id') . ' IN (' . $cidString . ')'
			);

			$query->delete($this->_db->quoteName('#__tjlms_enrolled_users'));
			$query->where($conditions);

			$this->_db->setQuery($query);
			$result = $this->_db->execute();

			return $result;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function used to delete all lessons and lessontracks with respect to course
	 *
	 * @param   ARRAY  $cid  array of course ids
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function deleteLessonForCourse($cid)
	{
		ArrayHelper::toInteger($cid);
		$cidString = implode(',', $cid);

		// Get lesson Ids of selected course
		$query = $this->_db->getQuery(true);
		$query->select($this->_db->qn('id'));
		$query->from($this->_db->qn('#__tjlms_lessons'));
		$query->where($this->_db->qn('course_id') . ' IN (' . $cidString . ')');
		$this->_db->setQuery($query);

		$lessonIds   = $this->_db->loadColumn();

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$lessonModel = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');

		foreach ($lessonIds as $lessonId)
		{
			$lessonModel->delete($lessonId);
		}

		return true;
	}

	/**
	 * Function used to delete all subsplan with respect to course
	 *
	 * @param   ARRAY  $cid  array of course ids
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function deleteSubsPlanForCourse($cid)
	{
		try
		{
			ArrayHelper::toInteger($cid);
			$cidString  = implode(',', $cid);

			$query      = $this->_db->getQuery(true);
			$conditions = array(
				$this->_db->quoteName('course_id') . ' IN (' . $cidString . ')'
			);

			$query->delete($this->_db->quoteName('#__tjlms_subscription_plans'));
			$query->where($conditions);

			$this->_db->setQuery($query);
			$result = $this->_db->execute();

			return $result;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Method to toggle the featured setting of Courses.
	 *
	 * @param   array    $pks    The ids of the items to toggle.
	 * @param   integer  $value  The value to toggle to.
	 *
	 * @return  boolean  True on success.
	 */
	public function featured($pks, $value = 0)
	{
		// Sanitize the ids.
		$pks = (array) $pks;
		ArrayHelper::toInteger($pks);

		if (empty($pks))
		{
			$this->setError(Text::_('COM_TJLMS_COURSES_NO_ITEM_SELECTED'));

			return false;
		}

		$table = $this->getTable();

		try
		{
			$query = $this->_db->getQuery(true)
						->update($this->_db->qn('#__tjlms_courses'))
						->set($this->_db->qn('featured') . ' = ' . (int) $value)
						->where($this->_db->qn('id') . ' IN (' . implode(',', $pks) . ')');
			$this->_db->setQuery($query);
			$this->_db->execute();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return true;
	}

	/**
	 * Delete all modules of the course
	 *
	 * @param   ARRAY  $cid  array of course ids
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function deleteModulesForCourse($cid)
	{
		try
		{
			ArrayHelper::toInteger($cid);
			$cidString  = implode(',', $cid);

			$query      = $this->_db->getQuery(true);
			$conditions = array(
				$this->_db->quoteName('course_id') . ' IN (' . $cidString . ')'
			);

			$query->delete($this->_db->qn('#__tjlms_modules'));
			$query->where($conditions);

			$this->_db->setQuery($query);
			$result = $this->_db->execute();

			return $result;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Delete all orders for the course
	 *
	 * @param   ARRAY  $cid  array of course ids
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function deleteOrdersForCourse($cid)
	{
		ArrayHelper::toInteger($cid);
		$cidString = implode(',', $cid);

		try
		{
			// Get order Ids of selected course
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn('id'));
			$query->from($this->_db->qn('#__tjlms_orders'));
			$query->where($this->_db->qn('course_id') . ' IN (' . $cidString . ')');
			$this->_db->setQuery($query);
			$orderIds = $this->_db->loadColumn();

			ArrayHelper::toInteger($orderIds);
			$orderIdsString = implode(',', $orderIds);

			if ($orderIdsString)
			{
				// Delete order items
				$query      = $this->_db->getQuery(true);
				$conditions = array(
					$this->_db->quoteName('order_id') . ' IN (' . $orderIdsString . ')',
					$this->_db->quoteName('course_id') . ' IN (' . $cidString . ')'
				);

				$query->delete($this->_db->quoteName('#__tjlms_order_items'));
				$query->where($conditions);

				$this->_db->setQuery($query);
				$results = $this->_db->execute();

				// Delete orders for course
				$query             = $this->_db->getQuery(true);
				$conditions_course = array(
					$this->_db->quoteName('course_id') . ' IN (' . $cidString . ')'
				);

				$query->delete($this->_db->quoteName('#__tjlms_orders'));
				$query->where($conditions_course);

				$this->_db->setQuery($query);
				$result = $this->_db->execute();

				return $result;
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Method to test whether a record state can be edited.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record. Defaults to the permission for the component.
	 *
	 * @since   12.2
	 */
	protected function canEditState($record)
	{
		$user    = Factory::getUser();
		$canEdit = false;

		// Check edit own on the record asset (explicit or inherited)
		if ($user->authorise('core.edit.state', $this->option))
		{
			$canEdit = true;
		}
		elseif ($user->authorise('core.create', 'com_tjlms.course.' . $record->id))
		{
			// Grant if current user is owner of the record
			$canEdit = $user->get('id') == $record->created_by;
		}

		return $canEdit;
	}

	/**
	 * Method to test whether a record state can be edited.
	 *
	 * @param   INT  $recordId  A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record. Defaults to the permission for the component.
	 *
	 * @since   12.2
	 */
	public function canEditRecordState($recordId)
	{
		$record  = $this->getItem($recordId);
		$canEdit = $this->canEditState($record);

		return $canEdit;
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
	 *
	 * @since   12.2
	 */
	protected function canDelete($record)
	{
		$user      = Factory::getUser();
		$canDelete = false;

		// Check edit own on the record asset (explicit or inherited)
		if ($user->authorise('core.delete', $this->option))
		{
			$canDelete = true;
		}
		elseif ($user->authorise('core.create', 'com_tjlms.course.' . $record->id))
		{
			// Grant if current user is owner of the record
			$canDelete = $user->get('id') == $record->created_by;
		}

		return $canDelete;
	}

	/**
	 * Is the user allowed to create an on the fly category?
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERISION__
	 */
	private function canCreateCategory()
	{
		return Factory::getUser()->authorise('core.create', 'com_tjlms');
	}

	/**
	 * Allows preprocessing of the JForm object.
	 *
	 * @param   JForm   $form   The form object
	 * @param   array   $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERISION__
	 */
	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		if ($this->canCreateCategory())
		{
			$form->setFieldAttribute('catid', 'allowAdd', 'true');
		}

		parent::preprocessForm($form, $data, $group);
	}

	/**
	 * Method to validate the course data.
	 *
	 * @param   array  $data  The data to validate.
	 *
	 * @return  mixed  Array of filtered data if valid, false otherwise.
	 *
	 * @since   1.1
	 */
	public function validateExtra($data)
	{
		$app       = Factory::getApplication();
		$subsplans = $data['subsplans'];
		$type      = $data['type'];

		foreach ($subsplans as $subsplan)
		{
			$title = $subsplan['title'];
			$price = $subsplan['price'];

			if ($type == 1 && (empty($title) || empty($price)))
			{
				$this->setError(Text::_('COM_TJLMS_FORM_INVALID_FIELD') . Text::_('COM_TJLMS_SUBSCRIPTION_PLAN'));

				return false;
			}

			if ($type == 1 && $subsplan['duration'] == '0' && $subsplan['time_measure'] != 'unlimited')
			{
				$app->enqueueMessage(Text::_('COM_TJLMS_SUBSCRIPTION_PLAN_INVALID_DURATION'), 'warning');

				return false;
			}
		}

		if ($data['certificate_term'] && $data['certificate_id'] == '')
		{
			$this->setError(Text::_("COM_TJLMS_COURSE_NO_CERTIFICATE_TERM"));

			return false;
		}
	}

	/**
	 * Method to change the title & alias.
	 *
	 * @param   integer  $category_id  The id of the category.
	 * @param   string   $alias        The alias.
	 * @param   string   $title        The title.
	 *
	 * @return  array  Contains the modified title and name.
	 *
	 * @since    1.3.32
	 */
	protected function generateNewTitle($category_id, $alias, $title)
	{
		// Alter the title & alias
		$table = $this->getTable();

		while ($table->load(array('alias' => $alias)))
		{
			$randomNumber = mt_rand(100, 1000000);
			$title        = StringHelper::increment($title, '', $randomNumber);
			$alias        = StringHelper::increment($alias, 'dash', $randomNumber);
		}

		return array(
			$title,
			$alias
		);
	}
}
