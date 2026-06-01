<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

jimport('joomla.application.component.view');
JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

/**
 * View to edit
 *
 * @since  1.0.0
 */
class TmtViewQuestion extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

	protected $params;

	protected $tjLmsParams;

	protected $questionMediaClient = 'tjlms.question';

	protected $answerMediaClient = 'tjlms.answer';

	protected $mediaLib;

	/**
	 * Display the  view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return void
	 */
	public function display($tpl = null)
	{
		$app                = Factory::getApplication();
		$input              = $app->input;
		$user               = Factory::getUser();
		$canDo              = TmtHelper::getActions();
		$canManageQuestions = TmtHelper::canManageQuestions();

		if (!$canManageQuestions)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->setHeader('status', 403, true);

			return false;
		}

		$this->state       = $this->get('State');
		$this->item        = $this->get('Item');
		$this->params      = ComponentHelper::getParams('com_tmt');
		$this->tjLmsParams = ComponentHelper::getParams('com_tjlms');
		$this->mediaLib    = TJMediaStorageLocal::getInstance();
		$this->form        = $this->get('Form');
		$this->ifintmpl    = $input->get('tmpl', '', 'WORD');
		$this->gradingtype = $input->get('gradingtype', '', 'WORD');
		$this->target      = $input->get('target', '', 'WORD');
		$this->unique      = $input->get('unique', '', 'STRING');
		$this->forDynamic  = $input->get('forDynamic', '0', 'INT');
		$this->model       = $this->getModel();

		// Check if a questions can be deleted.
		// If question is used in 1 or more tests, it can't be deleted.
		$this->isQuestionAttempted = $this->model->isQuestionAttempted($this->item->id);

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->_prepareDocument();
		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return  void
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
			$this->params->def('page_heading', Text::_('COM_TMT_Q_FORM_HEADING_Q_CREATE'));
		}

		$title = $this->params->get('page_title', '');

		if (empty($title))
		{
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->getCfg(' sitename '), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->getCfg(' sitename '));
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
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);
		$user       = Factory::getUser();
		$userId     = $user->id;
		$isNew      = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);

		$viewTitle = ($isNew) ? Text::_('COM_TMT_TITLE_COURSE_ADD') : Text::_('COM_TMT_TITLE_COURSE_EDIT');
		JToolBarHelper::title($viewTitle, 'pencil-2');

		ToolbarHelper::apply('question.apply');
		ToolbarHelper::save('question.save');
		ToolbarHelper::save2new('question.save2new');
		ToolbarHelper::cancel('question.cancel');
	}
}
