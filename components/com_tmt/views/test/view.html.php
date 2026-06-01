<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Filesystem\File;

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\View\HtmlView;
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

	protected $params;

	protected $lesson;

	protected $mediaLib;

	protected $template;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since  1.0.0
	 */
	public function display($tpl = null)
	{
		$app = Factory::getApplication();
		$user = Factory::getUser();

		// Check if user is logged in
		if (!$user->id)
		{
			$app->enqueueMessage(Text::_('COM_TMT_MESSAGE_LOGIN_FIRST'), 'warning');

			return false;
		}

		$this->template = $app->getTemplate();
		$model          = $this->getModel();
		$this->item     = $this->get('Data');
		$this->params   = $app->getParams('com_tmt');
		$this->form     = $this->get('Form');
		$this->state    = $this->get('State');
		$this->mediaLib = TJMediaStorageLocal::getInstance();

		$id              = $app->input->get('id', '0', 'int');
		$this->testState = $app->getUserState('test.' . $id);

		$lessonTrackId = $app->input->get('invite_id', '0', 'int');
		$lessonDetails = $model->lessonDetailsFromLessonTrack($lessonTrackId);
		$this->lesson  = $lessonDetails->lesson;
		$this->lessonTrackDetails = isset($lessonDetails->lessonTrack) ? $lessonDetails->lessonTrack : '';
		$lessonId      = $this->lesson->id;

		$model->addTablePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
		$course       = $model->getTable('course', 'TjlmsTable');
		$course->load($this->lesson->course_id);
		$properties   = $course->getProperties(1);

		$this->course = ArrayHelper::toObject($properties, 'JObject');

		JLoader::import('components.com_tjlms.helpers.lesson', JPATH_SITE);
		JLoader::import('components.com_tjlms.models.lesson', JPATH_SITE);

		$tjlmsLessonHelper = new TjlmsLessonHelper;

		$lessonModel = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');

		$model->addTablePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
		$lessonTracktable = $model->getTable("lessontrack", "TjlmsTable");
		$lessonTracktable->load($lessonTrackId);

		if ($this->_layout == 'thankyou')
		{
			$this->item              = $this->get('TestAttemptData');
			$this->time_taken_format = TMT::Utilities()->timeFormat($this->item->attempt['time_spent']);
		}
		else
		{
			// Lesson layout Start

			// Convert the params field to an array.
			$registry = new Registry;
			$registry->loadString($this->lesson->params);

			$testParams = $registry;

			if (!empty($testParams->get('lesson_layout')))
			{
				$this->setLayout($testParams->get('lesson_layout'));
			}

			if (!empty($lessonId))
			{
				$fromTestView  = 1;
				$usercanAccess = $lessonModel->canUserLaunch($this->lesson->id, $user->id, $fromTestView);

				if (!empty($usercanAccess) && !$usercanAccess['access'])
				{
					$app->enqueueMessage($usercanAccess['msg'], 'error');
                	$app->setHeader('status', 403, true);

					return false;
				}

				// Check user authentication w.r.t lesson access.
				if ($lessonTracktable->user_id != $user->id)
				{
					$app->enqueueMessage(Text::_('COM_TMT_ACCESS_TEST'), 'error');
                	$app->setHeader('status', 403, true);

					return false;
				}
			}

			$this->_prepareDocument();
		}

		if (empty($this->item))
		{
			$app->enqueueMessage(Text::_('COM_TMT_MESSAGE_NO_ACL_PERMISSION'), 'warning');
            $app->setHeader('status', 403, true);

			return false;
		}

		$this->tjlmshelperObj = new comtjlmsHelper;

		$link             = "index.php?option=com_tjlms&view=lesson&lesson_id=" . $this->lesson->id . "&tmpl=component";
		$this->lesson_url = $this->tjlmshelperObj->tjlmsRoute($link, false);

		// Get itemidof all courses view
		$this->allcousresItemid = $this->tjlmshelperObj->getItemId('index.php?option=com_tjlms&view=courses&layout=all');

		$itemid = $app->input->get('Itemid', 0);

		$this->courseDetailsUrl = $this->tjlmshelperObj->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $this->course->id . '&Itemid=' . $itemid);

		// Get tjlms params
		$this->tjlmsparams               = $app->getParams('com_tjlms');
		$this->launch_lesson_full_screen = $this->tjlmsparams->get('launch_full_screen', '0', 'INT');

		if (LMS_COURSE_SCROLLTOLASTACCESSEDLESSON == '1')
		{
			$this->courseDetailsUrl .= "#" . $this->lesson->alias;
		}

		// Get test elements in JS
		$document = Factory::getDocument();
		$script  = 'const testPaginationLimit = "' . $this->item->pagination_limit . '";';
		$document->addScriptDeclaration($script, 'text/javascript');

		// Get question/palette template
		$document->addCustomTag('<script id="questionMustache" type="text/x-mustache-template">'
			. $this->getQuizTemplate('question.mustache') . '</script>'
		);
		$document->addCustomTag('<script id="paletteMustache" type="text/x-mustache-template">'
			. $this->getQuizTemplate('palette.mustache') . '</script>'
		);

		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function _prepareDocument()
	{
		$app   = Factory::getApplication();
		$menus = $app->getMenu();
		$title = null;

		// We need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_TMT_TEST_APPEAR_PAGE_HEADING'));
		}

		$title = $this->params->get('page_title', '');

		if (empty($title))
		{
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}

		$this->document->setTitle($title);

		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
	}

	/**
	 * Get question/palette template
	 *
	 * @param   string  $quizTmpl  The name of the template file.
	 *
	 * @return  String
	 *
	 * @since  1.3.32
	 */
	protected function getQuizTemplate($quizTmpl)
	{
		$app      = Factory::getApplication();
		$template = $app->getTemplate();

		$defaultTmplPath = JPATH_SITE . '/components/com_tmt/views/test/tmpl/' . $quizTmpl;
		$overRideTmplPath = JPATH_SITE . '/templates/' . $template . '/html/com_tmt/test/' . $quizTmpl;

		if (File::exists($overRideTmplPath))
		{
			$tmplContents = file_get_contents($overRideTmplPath);
		}
		else
		{
			$tmplContents = file_get_contents($defaultTmplPath);
		}

		return $tmplContents;
	}
}
