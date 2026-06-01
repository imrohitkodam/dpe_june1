<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * View to edit
 *
 * @since  1.0.0
 */
class TjCompetencyViewSkill extends HtmlView
{
	/**
	 * The JForm object
	 *
	 * @var  JForm
	 */
	protected $form;

	/**
	 * The certificate helper
	 *
	 * @var  object
	 */
	protected $TjCompetencyHelper;

	/**
	 * The active item
	 *
	 * @var  object
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * The actions the user is authorised to perform
	 *
	 * @var  JObject
	 */
	protected $canDo;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');
		$this->input = Factory::getApplication()->input;

		$frameworkId = $this->input->getInt('framework_id');

		if (!empty($frameworkId))
		{
			$this->form->setValue('framework_id', null, $frameworkId);
		}

		$this->canDo = JHelperContent::getActions('com_tjcompetency');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function addToolbar()
	{
		$app    = Factory::getApplication();
		$user   = Factory::getUser();
		$userId = $user->id;
		$isNew  = ($this->item->id == 0);

		JLoader::import('administrator.components.com_tjcompetency.helpers.tjcompetency', JPATH_SITE);

		$this->TjCompetencyHelper = new TjCompetencyHelper;
		$checkedOut = $this->isCheckedOut($userId);

		// Built the actions for new and existing records.
		$canDo = $this->canDo;
		$layout = $app->input->get("layout");

		JToolbarHelper::title(
			Text::_('COM_TJCOMPETENCY_PAGE_VIEW_COMPETENCY_SKILL')
		);

		TjCompetencyHelper::addSubmenu('skills');

		// $this->sidebar = JHtmlSidebar::render();

		// For new records, check the create permission.
		if ($layout != "default")
		{
			$app->input->set('hidemainmenu', true);

			JToolbarHelper::title(
				Text::_('COM_TJCOMPETENCY_PAGE_' . ($checkedOut ? 'VIEW_COMPETENCY_SKILL' :
					($isNew ? 'ADD_COMPETENCY_SKILL' : 'EDIT_COMPETENCY_SKILL'))
			), 'pencil-2 skill-add'
			);

			if ($isNew)
			{
				JToolbarHelper::apply('skill.apply');
				JToolbarHelper::save('skill.save');
				JToolbarHelper::save2new('skill.save2new');
			}
			else
			{
				// Can't save the record if it's checked out and editable
				$this->canSave($checkedOut);
			}

			JToolbarHelper::cancel('skill.cancel');
		}

		JToolbarHelper::divider();
	}

	/**
	 * Can't save the record if it's checked out and editable
	 *
	 * @param   boolean  $checkedOut    Checked Out
	 *
	 * @return void
	 */
	protected function canSave($checkedOut)
	{
		if (!$checkedOut)
		{
			JToolbarHelper::apply('skill.apply');
			JToolbarHelper::save('skill.save');
			JToolbarHelper::save2new('skill.save2new');
			JToolbarHelper::save2copy('skill.save2copy');
		}
	}

	/**
	 * Is Checked Out
	 *
	 * @param   integer  $userId  User ID
	 *
	 * @return boolean
	 */
	protected function isCheckedOut($userId)
	{
		return !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
	}
}
