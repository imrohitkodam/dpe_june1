<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Toolbar\Toolbar;
jimport('joomla.application.component.view');
jimport('joomla.filesystem.folder');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * View class for a list of Tjlms.
 *
 * @since  1.0.0
 */
class TjlmsViewModules extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $passableLessons;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$app  = Factory::getApplication();
		$input = $app ->input;
		$model	= $this->getModel('modules');

		$layout = $input->getCmd('layout', 'default');
		$this->state		= $this->get('State');
		$lesson_id = $input->get('lesson_id', '0', 'INT');

		require_once JPATH_SITE . '/components/com_tjlms/models/assessments.php';

		$this->TjlmsModelAssessments = new TjlmsModelAssessments;

		TjlmsHelper::addSubmenu('courses');

		// Get current course info
		$this->course_id	=	$courseId = $input->get('course_id', '', 'INT');

		if (!$this->course_id && $lesson_id)
		{
			$this->course_id  = TjlmsHelper::getLessonCourse($lesson_id);
		}

		$this->CourseInfo = $model->getPresentCourseInfo($this->course_id);

		// Get total enrolled users

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models', 'TjlmsModel');
		$manageEnrollmentsModel = BaseDatabaseModel::getInstance('Manageenrollments', 'TjlmsModel', array('ignore_request' => true));
		$manageEnrollmentsModel->setState('filter.coursefilter', $this->course_id);
		$this->enrolled_users = $manageEnrollmentsModel->getTotal();

		if (!$this->course_id || empty($this->CourseInfo))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->setHeader('status', 403, true);

			return false;
		}

		$canManageMaterial	= TjlmsHelper::canManageCourseMaterial($this->course_id, null, $this->CourseInfo->created_by);

		if (!$canManageMaterial)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->setHeader('status', 403, true);

			return false;
		}

		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->params = ComponentHelper::getParams('com_tjlms');
		$this->moduleImagePath = $this->params->get('module_image_upload_path', 'media/com_tjlms/images/modules/');

		// Get modules and lessons details for a particlular module.

		$this->moduleData = $model->getModuleData();

		// $this->videoSubFormats = $model->getallSubFormats('video');
		$this->model = $model;

		$this->lessonform	=	$model->getLessonForm();

		$this->addToolbar();

		JLoader::import('com_tjlms.models.lessons', JPATH_SITE . '/components');
		$courseObj = Tjlms::course($this->CourseInfo->id);
		$this->passableLessons = $courseObj->getPassableLessons();

		if (JVERSION >= '3.0')
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		// Import helper for declaring language constant
		JLoader::import('TjlmsHelper', Uri::root() . 'administrator/components/com_tjlms/helpers/tjlms.php');

		// Call helper function
		TjlmsHelper::getLanguageConstant();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since   1.0
	 *
	 * @return  void
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/tjlms.php';

		$state	= $this->get('State');

		if (JVERSION > 3.0)
		{
			ToolbarHelper::title(Text::_('COM_TJLMS_TITLE_TJMODULES'), 'list');
		}
		else
		{
			ToolbarHelper::title(Text::_('COM_TJLMS_TITLE_TJMODULES'), 'modules.png');
		}

		$toolbar = Toolbar::getInstance('toolbar');

		$button = '<a href="' . Uri::base() . 'index.php?option=com_tjlms&view=courses" class="btn btn-small">
						<span class="icon-arrow-left-2"></span>' . Text::_('COM_TJLMS_BACK_TO_COURSES_BTN') . '</a>';
		$toolbar->appendButton('Custom', $button);

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/module';
	}
}
