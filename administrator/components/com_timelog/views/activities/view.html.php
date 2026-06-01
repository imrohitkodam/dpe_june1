<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Helper\ContentHelper;

/**
 * View class for a list of Timelog Activities.
 *
 * @since  __DEPLOY_VERSION__
 */
class TimelogViewActivities extends HtmlView
{
	/**
	 * An array of items
	 *
	 * @var  array
	 */
	protected $items;

	/**
	 * The pagination object
	 *
	 * @var  Pagination
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * Form object for search filters
	 *
	 * @var  Form
	 */
	public $filterForm;

	/**
	 * Logged in User
	 *
	 * @var  Object
	 */
	public $user;

	/**
	 * The active search filters
	 *
	 * @var  array
	 */
	public $activeFilters;

	/**
	 * The sidebar markup
	 *
	 * @var  string
	 */
	protected $sidebar;

	/**
	 * The access varible
	 *
	 * @var  CMSObject
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canDo;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canCreate;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canEdit;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canCheckin;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canChange;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		// Get state
		$this->state = $this->get('State');

		// This calls model function getItems()
		$this->items = $this->get('Items');

		// Get pagination
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->user          = Factory::getUser();
		$this->canDo         = ContentHelper::getActions('com_timelog');

		$this->canCreate  = $this->user->authorise('core.create', 'com_timelog');
		$this->canEdit    = $this->user->authorise('core.edit', 'com_timelog');
		$this->canCheckin = $this->user->authorise('core.manage', 'com_timelog');
		$this->canChange  = $this->user->authorise('core.edit.state', 'com_timelog');

		// Add submenu
		TimelogHelper::addSubmenu('activities');

		// Add Toolbar
		$this->addToolbar();

		// Set sidebar
		$this->sidebar = \JHtmlSidebar::render();

		// Display the view
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function addToolbar()
	{
		$state = $this->get('State');
		$canDo = $this->canDo;

		ToolbarHelper::title(Text::_('COM_TIMELOG_TITLE_ACTIVITIES'), 'activities.png');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/activity';

		if (file_exists($formPath))
		{
			if ($canDo->get('core.create'))
			{
				ToolbarHelper::addNew('activity.add', 'JTOOLBAR_NEW');
			}

			if ($canDo->get('core.edit') && isset($this->items[0]))
			{
				ToolbarHelper::editList('activity.edit', 'JTOOLBAR_EDIT');
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				ToolbarHelper::divider();
				ToolbarHelper::custom('activities.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				ToolbarHelper::custom('activities.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
			elseif (isset($this->items[0]))
			{
				// If this component does not use state then show a direct delete button as we can not trash
				ToolbarHelper::deleteList('', 'activities.delete', 'JTOOLBAR_DELETE');
			}

			if (isset($this->items[0]->state))
			{
				ToolbarHelper::divider();
				ToolbarHelper::archiveList('activities.archive', 'JTOOLBAR_ARCHIVE');
			}

			if (isset($this->items[0]->checked_out))
			{
				ToolbarHelper::custom('activities.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
			}
		}

		// Show trash and delete for components that uses the state field
		if (isset($this->items[0]->state))
		{
			if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
			{
				ToolbarHelper::deleteList('', 'activities.delete', 'JTOOLBAR_EMPTY_TRASH');
				ToolbarHelper::divider();
			}
			elseif ($canDo->get('core.edit.state'))
			{
				ToolbarHelper::trash('activities.trash', 'JTOOLBAR_TRASH');
				ToolbarHelper::divider();
			}
		}

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::preferences('com_timelog');
		}

		// Set sidebar action - New in 3.0
		\JHtmlSidebar::setAction('index.php?option=com_timelog&view=activities');
	}
}
