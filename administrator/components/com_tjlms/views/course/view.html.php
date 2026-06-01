<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Component\ComponentHelper;

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;

jimport('joomla.application.component.view');

/**
 * View class for a edit of Tjlms course.
 *
 * @since  1.0.0
 */
class TjlmsViewCourse extends HtmlView
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
		$app	= Factory::getApplication();
		$input	= $app->input;
		$model	= $this->getModel('course');
		$this->item = $this->get('Item');

		$this->form_extra = '';
		$user             = Factory::getUser();
		$this->canDo      = TjlmsHelper::getActions();

		if (empty($this->item->id))
		{
			$authorised = $user->authorise('core.create', 'com_tjlms') || (count($user->getAuthorisedCategories('com_tjlms', 'core.create')));
		}
		else
		{
			$authorised = $this->item->params->get('access-edit');
		}

		if ($authorised !== true)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->setHeader('status', 403, true);

			return false;
		}

		$this->state = $this->get('State');
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$tjlmsCoursesHelper = new tjlmsCoursesHelper;
		$this->courseImage  = $tjlmsCoursesHelper->getCourseImage((array) $this->item, 'S_');

		// Get component params
		$this->tjlmsparams        = ComponentHelper::getParams('com_tjlms');
		$this->characters_allowed = $this->tjlmsparams->get('allow_char_desc', '150', 'INT');
		$this->enable_tags        = $this->tjlmsparams->get('enable_tags', '0', 'INT');

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user  = Factory::getUser();
		$isNew = ($this->item->id == 0);

		if ($isNew)
		{
			$viewTitle = Text::_('COM_TJLMS_TITLE_COURSE_ADD');
		}
		else
		{
			$viewTitle = Text::_('COM_TJLMS_TITLE_COURSE_EDIT');
		}

		if (isset($this->item->checked_out))
		{
			$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$this->canDo = TjlmsHelper::getActions();

		if (JVERSION >= '3.0')
		{
			ToolbarHelper::title($viewTitle, 'pencil-2');
		}
		else
		{
			ToolbarHelper::title($viewTitle, 'course.png');
		}

		// If not checked out, can save the item.
		if (!$checkedOut && ($this->canDo->get('core.edit')||($this->canDo->get('core.create'))))
		{
			ToolbarHelper::apply('course.apply', 'JTOOLBAR_APPLY');
			ToolbarHelper::save('course.save', 'JTOOLBAR_SAVE');
			ToolbarHelper::save2new('course.save2new');
		}

		// If an existing item, can save to a copy.
		if (!$isNew && $this->canDo->get('core.create'))
		{
			ToolbarHelper::save2copy('course.save2copy');
		}

		if (!$isNew)
		{
			$canManage = TjlmsHelper::canManageCourseMaterial($this->item->id, null, $this->item->created_by);

			if ($canManage)
			{
				ToolbarHelper::custom('course.managematerial', 'list', 'list', 'COM_TJLMS_ADD_TRAINING_MATERIAL', false);
			}
		}

		if (empty($this->item->id))
		{
			ToolbarHelper::cancel('course.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolbarHelper::cancel('course.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
