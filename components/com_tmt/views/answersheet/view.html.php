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

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * View to edit
 *
 * @since  1.0.0
 */
class TmtViewAnswersheet extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

	protected $params;

	protected $mediaLib;

	protected $isAdmin;

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
		$app               = Factory::getApplication();
		$this->user        = Factory::getUser();
		$this->tjlmsparams = ComponentHelper::getParams('com_tjlms');
		$this->ltId        = $app->input->get('ltId', 0, 'INT');

		$this->mediaLib    = TJMediaStorageLocal::getInstance();

		$adminKey        = $app->input->get('adminKey', '', 'STRING');
		$this->fromAdmin = 0;
		$this->item      = $this->get('Data');
		$isSite          = $app->input->get('isSite', 0);
		$isroot          = $this->user->authorise('core.admin');
		$this->isAdmin   = $app->input->get('isAdmin', 0);

		if (!$this->user->id && !$this->isAdmin)
		{
			// Get curent url
			$current = Uri::getInstance()->toString();
			$url     = base64_encode($current);
			$msg     = Text::_('COM_TJLMS_MESSAGE_LOGIN_FIRST');
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, false), $msg);

			return false;
		}

		if (!$isroot)
		{
			if ($this->item->test_attendee != $this->user->id && $isSite)
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

				return false;
			}
		}

		if ($isroot)
		{
			$this->fromAdmin = 1;
		}

		$model = $this->getModel('answersheet', 'TmtModel');

		JLoader::import('assessments', JPATH_SITE . '/components/com_tjlms/models');
		$this->assessModel = BaseDatabaseModel::getInstance('assessments', 'TjlmsModel');

		JLoader::import('lesson', JPATH_SITE . '/components/com_tjlms/models');
		$this->lessonModel = BaseDatabaseModel::getInstance('lesson', 'TjlmsModel');

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$lessonTrackTable  = Table::getInstance('Lessontrack', 'TjlmsTable');
		$lessonTrackTable->load($this->ltId);
		$properties        = $lessonTrackTable->getProperties(1);
		$this->lessonTrack = ArrayHelper::toObject($properties, 'JObject');
		$this->lessonId    = $this->lessonTrack->lesson_id;

		$lessonTable  = Table::getInstance('lesson', 'TjlmsTable');
		$lessonTable->load($this->lessonId);
		$properties   = $lessonTable->getProperties(1);
		$this->lesson = ArrayHelper::toObject($properties, 'JObject');

		if (!$this->lesson->id || !$this->lessonTrack->id)
		{
			$app->enqueueMessage(Text::_('COM_TJLMS_LESSON_INVALID_URL'), 'warning');

			return false;
		}

		if ($this->lessonTrack->user_id != $this->user->id && empty($adminKey))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

			return false;
		}

		$id = $model->getTestIdFromLessonTrack($this->ltId);

		$this->candid_id = $this->lessonTrack->user_id;

		$this->state             = $this->get('State');
		$this->params            = $app->getParams('com_tmt');
		$this->form              = $this->get('Form');
		$this->time_taken_format = TMT::Utilities()->timeFormat($this->item->time_spent);

		$this->tjlmshelperObj    = new comtjlmsHelper;
		$this->courseDetailsUrl  = $this->tjlmshelperObj->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $this->lesson->course_id);

		$this->_prepareDocument();

		/*Check if assessment is added against the lesson/test*/
		$this->showAssessments  = false;
		$this->lessonAssessment = $this->assessModel->getLessonAssessments($this->lesson->id);

		if (!empty($this->lessonAssessment->set_id))
		{
			if ($this->lessonAssessment->assessment_answersheet == 1)
			{
				$ansOptions = json_decode($this->lessonAssessment->answersheet_options);

				if ($ansOptions->assessments == 1)
				{
					$this->showAssessments = true;
					$this->submissions     = $this->assessModel->getAssessmentSubmissions($this->ltId);
				}
			}
		}

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

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
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
}
