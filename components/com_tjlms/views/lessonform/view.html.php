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
use Joomla\CMS\Router\Route;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * View class for a list of Tjlms.
 *
 * @since  1.0.0
 */
class TjlmsViewLessonform extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed
	 */
	public function display($tpl = null)
	{
		$app                  = Factory::getApplication();
		$input                = $app->input;
		$this->comtjlmsHelper = new comtjlmsHelper;
		$this->lessonId       = $input->get('id', 0, 'INT');
		$this->courseId       = $input->get('cid', 0, 'INT');
		$this->moduleId       = $input->get('mid', 0, 'INT');
		$this->model          = $this->getModel();
		$this->state          = $this->get('State');
		$this->item           = $this->get('Item');
		$this->form           = $this->get('Form');
		$this->params         = $this->state->params;
		$this->user           = Factory::getUser();

		// Set "Show lesson in lesson library" value to Yes
		// Also hide the field so that user would not change the value
		$this->form->setValue('in_lib', '', '1');
		$this->form->setFieldAttribute('in_lib', 'type', 'hidden');

		if (!$this->user->id)
		{
			$msg     = Text::_('COM_TJLMS_MESSAGE_LOGIN_FIRST');
			$current = Uri::getInstance()->toString();
			$url     = base64_encode($current);
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, false), $msg);
		}

		$this->formId = $this->moduleId . "_" . $this->lessonId;

		if (!$this->courseId && $this->lessonId)
		{
			$this->courseId = $this->item->course_id;
		}

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$courseModel = BaseDatabaseModel::getInstance('course', 'TjlmsModel');
		$moduleModel = BaseDatabaseModel::getInstance('module', 'TjlmsModel');

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
				$this->course = $courseModel->getItem($this->courseId);
				$this->course = $this->course->course_info;
				$this->module = $moduleModel->getItem($this->moduleId);

				// To check if the course id is passed in the url but the course does not exists
				if ($this->courseId && !$this->course->id)
				{
					$app->enqueueMessage(Text::_('JERROR_LAYOUT_REQUESTED_RESOURCE_WAS_NOT_FOUND'), 'error');
					$app->setHeader('status', 403, true);

					return false;
				}

				$canManageMaterial = TjlmsHelper::canManageCourseMaterial($this->course->id, null, $this->course->created_by);

				if ($this->courseId && !$canManageMaterial)
				{
					$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
					$app->setHeader('status', 403, true);

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
				'scorm', 'htmlzips', 'tincanlrs', 'video', 'document',
				'textmedia', 'externaltool', 'event', 'survey',
				'form', 'quiz', 'exercise', 'feedback'
			);

			if ($this->format && !in_array($this->format, $lesson_formats_array))
			{
				$app->enqueueMessage(Text::_('COM_TJLMS_FORMAT_CHOOSE_MSG'), 'error');

				return false;
			}

			$this->subformatOptions = $this->model->getallSubFormats($this->format);
			$this->ifintmpl         = $input->get('tmpl', 'component', 'STRING');
			$this->assessment       = 0;
		}

		$this->comtjlmsHelper->getLanguageConstant();

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
		$this->toolbar = Toolbar::getInstance('toolbar');
		ToolbarHelper::cancel('lessonform.cancel', 'COM_TJLMS_LESSONFORM_VIEW_CANCEL_BUTTON');

		$title = Text::_("COM_TJLMS_MANAGELESSONS_VIEW_DEFAULT_TITLE");

		if ($this->courseId)
		{
			$title = $this->course->title . " > " . $this->module->name;
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
	}
}
