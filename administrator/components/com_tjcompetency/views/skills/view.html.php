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

jimport('techjoomla.tjtoolbar.button.csvexport');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Object\CMSObject;

/**
 * Skills view
 *
 * @since  1.0.0
 */
class TjCompetencyViewSkills extends HtmlView
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
	 * @param   string  $tpl  The name of the skill file to parse; automatically searches through the skill paths.
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

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		// Preprocess the list of items to find ordering divisions.
		foreach ($this->items as &$item)
		{
			$this->ordering[$item->parent_id][] = $item->id;
		}

		// Levels filter - Used in Hathor.
		$this->f_levels = array(
			JHtml::_('select.option', '1', JText::_('J1')),
			JHtml::_('select.option', '2', JText::_('J2')),
			JHtml::_('select.option', '3', JText::_('J3')),
			JHtml::_('select.option', '4', JText::_('J4')),
			JHtml::_('select.option', '5', JText::_('J5')),
			JHtml::_('select.option', '6', JText::_('J6')),
			JHtml::_('select.option', '7', JText::_('J7')),
			JHtml::_('select.option', '8', JText::_('J8')),
			JHtml::_('select.option', '9', JText::_('J9')),
			JHtml::_('select.option', '10', JText::_('J10')),
		);

		$this->user          = Factory::getUser();
		$this->canDo         = JHelperContent::getActions('com_tjcompetency');

		// Add submenu
		TjCompetencyHelper::addSubmenu('skills');

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
		JToolBarHelper::title(Text::_('COM_TJCOMPETENCY_VIEW_COMPETENCY_SKILLS'), '');
		$canDo = $this->canDo;

		if ($canDo->get('core.create'))
		{
			JToolbarHelper::addNew('skill.add');

			// Add CSV import toolbar
			JToolbarHelper::modal('importcsv', 'icon-upload icon-white', 'COM_TJCOMPETENCY_IMPORT_CSV');
		}

		$bar = JToolBar::getInstance('toolbar');

		if (!empty($this->items) && $canDo->get('core.create') && $this->user->id)
		{
			$message               = array();
			$message['success']    = Text::_("COM_TJCOMPETENCY_EXPORT_FILE_SUCCESS");
			$message['error']      = Text::_("COM_TJCOMPETENCY_EXPORT_FILE_ERROR");
			$message['inprogress'] = Text::_("COM_TJCOMPETENCY_EXPORT_FILE_NOTICE");
			$message['text']       = Text::_("COM_TJCOMPETENCY_EXPORT_TOOLBAR_TITLE");

			$bar->appendButton('CsvExport', $message);
		}

		if ($canDo->get('core.edit'))
		{
			JToolbarHelper::editList('skill.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::divider();
			JToolbarHelper::publish('skills.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('skills.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolBarHelper::archiveList('skills.archive', 'JTOOLBAR_ARCHIVE');
			JToolbarHelper::divider();
		}

		if ($canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'skills.delete', 'JTOOLBAR_DELETE');
			JToolbarHelper::divider();
		}

		if ($canDo->get('core.admin'))
		{
			JToolbarHelper::custom('skills.rebuild', 'refresh.png', 'refresh_f2.png', 'JTOOLBAR_REBUILD', false);
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
			'a.id'           => Text::_('JGRID_HEADING_ID'),
			'a.framework_id' => Text::_('COM_COMPETENCY_COMPETENCY_SKILL_LIST_VIEW_FRAMEWORK'),
			'a.title'        => Text::_('COM_COMPETENCY_COMPETENCY_SKILL_LIST_VIEW_TITLE'),
			'a.state'        => Text::_('JSTATUS'),
			'a.created_by'   => Text::_('COM_COMPETENCY_COMPETENCY_SKILL_LIST_VIEW_CREATED_BY'),
		);
	}
}
