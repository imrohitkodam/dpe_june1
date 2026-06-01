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
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.filesystem.folder');
/**
 * View class for a list of Tjlms.
 *
 * @since  1.0.0
 */
class TjlmsViewLesson extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $isPassable;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed
	 */
	public function display($tpl = null)
	{
		$app            = Factory::getApplication();
		$input          = $app->input;
		$this->lessonId = $input->get('id', 0, 'INT');
		$this->courseId = $input->get('cid', 0, 'INT');
		$this->moduleId = $input->get('mid', 0, 'INT');
		$this->model    = $this->getModel();
		$this->state    = $this->get('State');
		$this->item     = $this->get('Item');
		$this->form     = $this->get('Form');
		$this->params   = $this->state->params;
		$this->formId   = $this->moduleId . "_" . $this->lessonId;

		if (!$this->courseId && $this->lessonId)
		{
			$this->courseId = $this->item->course_id;
		}

		/*require_once JPATH_SITE . '/components/com_tjlms/models/assessments.php';

		$this->TjlmsModelAssessments = new TjlmsModelAssessments;*/

		if ($this->_layout == 'selectassociatefiles')
		{
			$allAssociatedFiles       = $this->model->getFilestoAssociate($this->lessonId);
			$this->allAssociatedFiles = $allAssociatedFiles;
		}
		else
		{
			if ($this->courseId)
			{
				BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
				$courseModel = BaseDatabaseModel::getInstance('course', 'TjlmsModel');
				$moduleModel = BaseDatabaseModel::getInstance('module', 'TjlmsModel');

				$this->course = $courseModel->getItem($this->courseId);
				$this->module = $moduleModel->getItem($this->moduleId);

				// To check if the course id is passed in the url but the course does not exists
				if ($this->courseId && !$this->course->id)
				{
					$app->enqueueMessage(Text::_('JERROR_LAYOUT_REQUESTED_RESOURCE_WAS_NOT_FOUND', 'warning'));

					return false;
				}

				$canManageMaterial = TjlmsHelper::canManageCourseMaterial($this->course->id, null, $this->course->created_by);

				if ($this->courseId && !$canManageMaterial)
				{
					$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

					return false;
				}
			}
			elseif (!$this->courseId)
			{
				$canManageTrainingMaterial = TjlmsHelper::canManageTrainingMaterial($this->lessonId);

				if (!$canManageTrainingMaterial)
				{
					$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

					return;
				}
			}

			// Check for errors.
			if (count($errors = $this->get('Errors')))
			{
				throw new Exception(implode("\n", $errors));
			}

			if ($this->item->id)
			{
				$this->format = $this->item->format;
			}
			else
			{
				$this->format = $input->input->get('ptype', '', 'WORD');
			}

			$lesson_formats_array = array(
				'scorm', 'htmlzips', 'tincanlrs', 'video',
				'document', 'textmedia', 'externaltool', 'event',
				'survey', 'form', 'quiz', 'exercise', 'feedback'
			);

			if (!in_array($this->format, $lesson_formats_array))
			{
				$app->enqueueMessage(Text::_('COM_TJLMS_FORMAT_CHOOSE_MSG'), 'error');

				return false;
			}

			$this->subformatOptions = $this->model->getallSubFormats($this->format);
			$this->ifintmpl         = $input->get('tmpl', 'component', 'STRING');
			$this->assessment       = 0;

			$lessonObj = Tjlms::lesson();
			$this->isPassable = $lessonObj->checkLessonIsPassable($this->format);
		}

		if ($this->course)
		{
			TjlmsHelper::addSubmenu('courses');
		}
		else
		{
			TjlmsHelper::addSubmenu('lessons');
		}

		$this->sidebar = JHtmlSidebar::render();
		TjlmsHelper::getLanguageConstant();
		$this->addToolbar();
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
		$title    = Text::_('COM_TJLMS_SUBMENU_LESSONS');
		$courseId = 0;

		if (isset($this->course) && isset($this->module))
		{
			$title    = $this->course->title . " > " . $this->module->name;
			$courseId = $this->course->id;
		}

		if (!$this->lessonId)
		{
			$title .= " > " . Text::_('COM_TJLMS_TITLE_ADD_LESSON');
		}
		else
		{
			$title .= " > " . $this->item->title;
		}

		ToolbarHelper::title($title);

		if ($this->course)
		{
			$toolbar = Toolbar::getInstance('toolbar');

			$button = '<a href="' . Uri::base() . 'index.php?option=com_tjlms&view=modules&course_id=' . $courseId . '" class="btn btn-small">
							<span class="icon-arrow-left-2"></span>' . Text::_('COM_TJLMS_BACK_TO_COURSE_BTN') . '</a>';
			$toolbar->appendButton('Custom', $button);
		}
		else
		{
			ToolbarHelper::cancel('lesson.cancel', 'JTOOLBAR_CANCEL');
		}
	}
}
