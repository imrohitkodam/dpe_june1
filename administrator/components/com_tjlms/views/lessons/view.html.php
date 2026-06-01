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
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of Tjlms.
 *
 * @since  1.3.4
 */
class TjlmsViewlessons extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	public $filterForm;

	protected $toolbar;

	protected $user;

	protected $canManageMaterial;

	protected $canManageMaterialOwn;

	/*protected $canCreate;*/

	/*protected $canEdit;*/

	/*protected $canCheckin;*/

	/*protected $canChangeStatus;

	protected $canDelete;*/

	protected $ComtjlmsHelper;

	protected $techjoomlacommon;

	protected $lmsparams;

	protected $showUserOrUsername;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		$app = Factory::getApplication();

		// Get ACL actions
		$this->user                 = Factory::getUser();
		$this->canManageMaterial    = $this->user->authorise('core.manage.material', 'com_tjlms');
		$this->canManageMaterialOwn = $this->user->authorise('core.own.manage.material', 'com_tjlms');

		if (!$this->canManageMaterial && !$this->canManageMaterialOwn)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

			return;
		}

		JLoader::register('TjlmsHelper', JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php');

		$this->canDo = TjlmsHelper::getActions();

		require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php';
		$this->ComtjlmsHelper = new ComtjlmsHelper;

		$this->techjoomlacommon = new TechjoomlaCommon;

		// This calls model function getItems()
		$this->items = $this->get('Items');

		// Get state
		$this->state = $this->get('State');

		// Get pagination
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Get component params
		$this->lmsparams = $this->ComtjlmsHelper->getcomponetsParams('com_tjlms');

		$this->showUserOrUsername = $this->lmsparams->get('show_user_or_username', 0, 'INT');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		TjlmsHelper::addSubmenu('lessons');
		$this->sidebar = JHtmlSidebar::render();
		$this->addToolbar();

		// Display the view
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  Toolbar instance
	 *
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		$lang = Factory::getLanguage();
		$lang->load('com_tjlms', JPATH_SITE, null, true, true);

		$state = $this->get('State');

		ToolbarHelper::title(Text::_('COM_TJLMS_MANAGELESSONS_VIEW_DEFAULT_TITLE'), 'courses.png');

		/*if ($this->canDo->get('core.create'))
		{
			ToolbarHelper::addNew('lesson.edit', 'TJTOOLBAR_NEW');
		}*/

		if ($this->canDo->get('core.create'))
		{
			ToolbarHelper::modal('selectLessonType', 'icon-new', 'COM_TJLMS_TITLE_ADD_LESSON');
		}

		if (($this->canDo->get('core.create') || $this->canDo->get('core.edit')) && isset($this->items[0]))
		{
			ToolbarHelper::editList('lesson.edit', 'TJTOOLBAR_EDIT');
		}

		if (($this->canDo->get('core.create') || $this->canDo->get('core.edit.state')))
		{
			ToolbarHelper::divider();
			ToolbarHelper::custom('lessons.publish', 'publish.png', 'publish_f2.png', 'TJTOOLBAR_PUBLISH', true);
			ToolbarHelper::custom('lessons.unpublish', 'unpublish.png', 'unpublish_f2.png', 'TJTOOLBAR_UNPUBLISH', true);
		}

		if ($this->canDo->get('core.edit.state'))
		{
			ToolbarHelper::custom('lessons.checkin', 'checkin.png', 'checkin_f2.png', 'TJTOOLBAR_CHECKIN', true);
		}

		if ($state->get('filter.state') == -2 && ($this->canDo->get('core.create') || $this->canDo->get('core.delete')))
		{
			ToolbarHelper::deleteList('COM_TJLMS_COURSES_DELETE_MSG', 'managelessons.delete', 'TJTOOLBAR_EMPTY_TRASH');
		}
		elseif ($this->canDo->get('core.create') || $this->canDo->get('core.edit.state'))
		{
			ToolbarHelper::trash('lessons.trash', 'TJTOOLBAR_TRASH');
		}
	}
}
