<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * Slas view
 *
 * @since  1.0.0
 */
class SlaViewSlaActivityTypes extends HtmlView
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
	 * @var  CMSObject
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
	 * @since  1.0.0
	 */
	protected $canDo;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		// Get state
		$this->state = $this->get('State');

		// This calls model function getItems()
		$this->items = $this->get('Items');

		// Get pagination
		$this->pagination = $this->get('Pagination');

		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		$this->user            = Factory::getUser();
		$this->canDo         = ContentHelper::getActions('com_sla');

		// Add submenu
		SlaHelper::addSubmenu('slaactivitytypes');

		// Add Toolbar
		$this->addToolbar();

		// Set sidebar
		$this->sidebar = JHtmlSidebar::render();

		// Display the view
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @since    1.0.0
	 */
	protected function addToolbar()
	{
		JToolBarHelper::title(Text::_('COM_SLAS_VIEW_SLA_ACTIVITY_TYPES'), '');
		$canDo = $this->canDo;

		if ($canDo->get('core.create'))
		{
			ToolbarHelper::addNew('slaactivitytype.add');
		}

		if ($canDo->get('core.edit'))
		{
			ToolbarHelper::editList('slaactivitytype.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			ToolbarHelper::divider();
			ToolbarHelper::publish('slaactivitytypes.publish', 'JTOOLBAR_PUBLISH', true);
			ToolbarHelper::unpublish('slaactivitytypes.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolBarHelper::archiveList('slaactivitytypes.archive', 'JTOOLBAR_ARCHIVE');
			ToolbarHelper::divider();
		}

		if ($canDo->get('core.delete'))
		{
			ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'slaactivitytypes.delete', 'JTOOLBAR_DELETE');
			ToolbarHelper::divider();
		}

		if ($canDo->get('core.admin') || $canDo->get('core.options'))
		{
			ToolbarHelper::preferences('com_sla');
			ToolbarHelper::divider();
		}
	}

	/**
	 * Method to order fields
	 *
	 * @return ARRAY
	 */
	protected function getSortFields()
	{
		return array(
			'sat.id' => Text::_('JGRID_HEADING_ID'),
			'sat.title' => Text::_('COM_SLA_LIST_SLA_ACTIVITY_TYPE_TITLE'),
			'sat.ordering' => Text::_('JGRID_HEADING_ORDERING'),
			'sat.state' => Text::_('JSTATUS'),
		);
	}
}
