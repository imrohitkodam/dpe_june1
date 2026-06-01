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
use Joomla\CMS\Object\CMSObject;

/**
 * Scalesets view
 *
 * @since  1.0.0
 */
class TjCompetencyViewScalesets extends HtmlView
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
	 * @var  JPagination
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
	 * @var  JForm
	 */
	public $filterForm;

	/**
	 * Logged in User
	 *
	 * @var  JObject
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
	 * @param   string  $tpl  The name of the scaleset file to parse; automatically searches through the scaleset paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error objea.
	 */
	public function display($tpl = null)
	{
		$this->items         = $this->get('Items');
		$this->state         = $this->get('State');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->user          = Factory::getUser();
		$this->canDo         = JHelperContent::getActions('com_tjcompetency');

		// Add submenu
		TjCompetencyHelper::addSubmenu('scalesets');

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
		JToolBarHelper::title(Text::_('COM_TJCOMPETENCY_VIEW_COMPETENCY_SCALESETS'), '');
		$canDo = $this->canDo;

		if ($canDo->get('core.create'))
		{
			JToolbarHelper::addNew('scaleset.add');
		}

		if ($canDo->get('core.edit'))
		{
			JToolbarHelper::editList('scaleset.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::divider();
			JToolbarHelper::publish('scalesets.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('scalesets.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolBarHelper::archiveList('scalesets.archive', 'JTOOLBAR_ARCHIVE');
			JToolbarHelper::divider();
		}

		if ($canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'scalesets.delete', 'JTOOLBAR_DELETE');
			JToolbarHelper::divider();
		}

		if ($canDo->get('core.admin') || $canDo->get('core.options'))
		{
			JToolbarHelper::preferences('com_tjcompetency');
			JToolbarHelper::divider();
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
			'a.id'         => Text::_('JGRID_HEADING_ID'),
			'a.title'      => Text::_('COM_COMPETENCY_COMPETENCY_SCALESET_LIST_VIEW_TITLE'),
			'a.state'      => Text::_('JSTATUS'),
			'a.created_by' => Text::_('COM_COMPETENCY_COMPETENCY_SCALESET_LIST_VIEW_CREATED_BY'),
		);
	}
}
