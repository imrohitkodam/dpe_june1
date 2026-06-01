<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * View to edit
 *
 * @since  1.0.0
 */
class TmtViewTest extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

	protected $mediaLib;

	protected $extension = 'com_tmt.questions';

	protected $isPassable;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$app               = Factory::getApplication();
		$input             = $app->input;
		$layout            = $app->input->get('layout', '', 'STRING');
		$user              = Factory::getUser();
		$this->tjlmsparams = ComponentHelper::getParams('com_tjlms');
		$this->mediaLib    = TJMediaStorageLocal::getInstance();

		$this->model              = $this->getModel();
		$this->multiplicateFactor = $this->model->getMultiplicationFactor();
		$this->state              = $this->get('State');
		$this->item               = $this->get('Item');
		$this->params             = ComponentHelper::getParams('com_tmt');
		$this->form               = $this->get('Form');

		// Load all filter values
		$this->categories       = TMT::Utilities()->categories($this->extension, true);
		$this->difficultyLevels = TMT::Utilities()->questionDifficultyLevels(true);
		$this->qTypes           = TMT::Utilities()->questionTypes(true);

		// Get questions count.
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/models', 'questions');
		$questionsModel = BaseDatabaseModel::getInstance('questions', 'TmtModel', array('ignore_request' => true));
		$questionsModel->setState('filter.state', 1);
		$this->questions_count = $questionsModel->getTotal();

		$this->lid      = $input->get('lid', 0, 'INT');
		$this->cid      = $input->get('cid', 0, 'INT');
		$this->mid      = $input->get('mid', 0, 'INT');
		$this->ifintmpl = $input->get('tmpl', 'component', 'STRING');

		if (!empty($this->item->id))
		{
			$this->item->gradingtype = (string) preg_replace('/[^A-Z_]/i', '', $this->item->gradingtype);
			$this->gradingtype       = $this->item->gradingtype;
		}
		else
		{
			$this->gradingtype = $input->input->get('gradingtype', 'quiz', 'WORD');
		}

		$plugin = PluginHelper::getPlugin('tj' . $this->gradingtype, $this->gradingtype);

		$this->assessment = 0;

		if (!empty($plugin->params))
		{
			$params           = new Registry($plugin->params);
			$this->assessment = (int) $params->get('assessment', '0', 'INT');
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		if (!empty($this->item->id))
		{
			if ($this->item->gradingtype == 'quiz')
			{
				$this->docTitle = Text::sprintf('COM_TMT_HEADING_TEST_EDIT', $this->item->title);
			}
			elseif ($this->item->gradingtype == 'exercise')
			{
				$this->docTitle = Text::sprintf('COM_TMT_HEADING_TEST_EDIT_EXERCISE', $this->item->title);
			}
			else
			{
				$this->docTitle = Text::sprintf('COM_TMT_HEADING_TEST_EDIT_FEEDBACK', $this->item->title);
			}
		}
		else
		{
			$gradingtype = $this->gradingtype = $input->input->get('gradingtype', '', 'WORD');

			if ($gradingtype == 'quiz')
			{
				$this->docTitle = Text::_('COM_TMT_HEADING_TEST_CREATE');
			}
			elseif ($gradingtype == 'exercise')
			{
				$this->docTitle = Text::_('COM_TMT_HEADING_TEST_CREATE_EXERCISE');
			}
			else
			{
				$this->docTitle = Text::_('COM_TMT_HEADING_TEST_CREATE_FEEDBACK');
			}
		}

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$courseModel  = BaseDatabaseModel::getInstance('course', 'TjlmsModel');
		$moduleModel  = BaseDatabaseModel::getInstance('module', 'TjlmsModel');
		$this->course = $courseModel->getCourse($this->cid);
		$this->module = $moduleModel->getItem($this->mid);

		$lessonObj = Tjlms::lesson();
		$format = !empty($this->item->gradingtype) ? $this->item->gradingtype : $gradingtype;
		$this->isPassable = $lessonObj->checkLessonIsPassable($format);

		if ($this->cid)
		{
			TmtHelper::addSubmenu('courses');
		}
		else
		{
			TjlmsHelper::addSubmenu('lessons');
		}

		$this->sidebar = JHtmlSidebar::render();
		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return toolbar
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user  = Factory::getUser();
		$isNew = ($this->item->id == 0);

		if (isset($this->item->checked_out))
		{
			$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$title = Text::_('COM_TJLMS_SUBMENU_LESSONS') . " > " . $this->docTitle;

		$canDo = TmtHelper::getActions();

		if ($this->course->id)
		{
			$title = $this->course->title . " > " . $this->module->name . " > " . $this->docTitle;
		}

		ToolbarHelper::title($title);

		$toolbar = Toolbar::getInstance('toolbar');

		if ($this->course->id)
		{
			$button  = '<a href="' . Uri::base() . 'index.php?option=com_tjlms&view=modules&course_id=' . $this->course->id . '" class="btn btn-small">
							<span class="icon-arrow-left-2"></span>' . Text::_('COM_TJLMS_BACK_TO_COURSE_BTN') . '</a>';
			$toolbar->appendButton('Custom', $button);
		}
		else
		{
			$button  = '<a href="' . Uri::base() . 'index.php?option=com_tjlms&view=lessons" class="btn btn-small">
				<span class="icon-cancel"></span>' . Text::_('JTOOLBAR_CANCEL') . '</a>';
			$toolbar->appendButton('Custom', $button);
		}
	}
}
