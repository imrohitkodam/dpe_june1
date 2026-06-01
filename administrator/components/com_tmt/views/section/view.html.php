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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Component\ComponentHelper;

/**
 * View to edit
 *
 * @since  1.0.0
 */
class TmtViewSection extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

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
		$user              = Factory::getUser();
		$this->tjlmsparams = ComponentHelper::getParams('com_tjlms');

		// Validate user login
		if (! $user->id)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
            $app->setHeader('status', 503, true);

			return false;
		}

		// Check if user has access to create
		if (!(Factory::getUser($user->id)->authorise('core.create', 'com_tmt') || (Factory::getUser($user->id)->authorise('core.edit', 'com_tmt'))))
		{
			$app->enqueueMessage(Text::_('COM_TMT_MESSAGE_NO_ACL_PERMISSION'), 'warning');
            $app->setHeader('status', 403, true);

			return false;
		}

		$this->state  = $this->get('State');
		$this->item   = $this->get('Data');
		$this->params = ComponentHelper::getParams('com_tmt');
		$this->form   = $this->get('Form');

		// Load all filter values
		$this->difficultyLevels = TMT::Utilities()->questionDifficultyLevels(true);
		$this->qTypes           = TMT::Utilities()->questionTypes(true);

		// Get questions count.
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/models', 'questions');
		$questionsModel = BaseDatabaseModel::getInstance('questions', 'TmtModel', array('ignore_request' => true));
		$questionsModel->setState('filter.state', 1);
		$this->questions_count = $questionsModel->getTotal();

		$this->course_id = $app->input->get('course_id', '', 'INT');
		$this->mod_id    = $app->input->get('mod_id', '', 'INT');
		$this->addquiz   = $app->input->get('addquiz', '', 'INT');
		$this->unique    = $app->input->get('unique', '', 'STRING');
		$this->qztype    = $app->input->get('qztype', '', 'STRING');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

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

		$canDo = TmtHelper::getActions();

		ToolbarHelper::title(Text::_('COM_TMT_TITLE_TEST'), 'test.png');

		// If not checked out, can save the item.
		if (!$checkedOut && ($canDo->get('core.edit')||($canDo->get('core.create'))))
		{
			ToolbarHelper::apply('test.apply', 'JTOOLBAR_APPLY');
			ToolbarHelper::save('test.save', 'JTOOLBAR_SAVE');
		}

		if (!$checkedOut && ($canDo->get('core.create')))
		{
			ToolbarHelper::custom('test.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
		}

		// If an existing item, can save to a copy.
		if (!$isNew && $canDo->get('core.create'))
		{
			ToolbarHelper::custom('test.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
		}

		if (empty($this->item->id))
		{
			ToolbarHelper::cancel('test.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolbarHelper::cancel('test.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
