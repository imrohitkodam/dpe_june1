<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tc
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2016-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');
use \Joomla\CMS\Component\ComponentHelper;
/**
 * View class for a list of Tc.
 *
 * @since  1.6
 */
class TcViewUsertcs extends JViewLegacy
{
	protected $items;

	protected $pagination;

	protected $state;

	public $filterForm;

	public $activeFilters;

	protected $component;

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
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->component = $this->state->get('filter.component');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		TcHelpersTc::addSubmenu('usertcs');

		// To get the foematted date
		$comParams = ComponentHelper::getParams('com_tc');
		$this->dateFormat = $comParams->get('date_format_show');

		if ($this->dateFormat == "custom")
		{
			$this->dateFormat = $comParams->get('custom_format');
		}

		$this->addToolbar();

		$this->sidebar = JHtmlSidebar::render();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	protected function addToolbar()
	{
		$state = $this->get('State');
		$canDo = TcHelpersTc::getActions();

		JToolBarHelper::title(JText::_('COM_TC_TITLE_USERTCS'), 'list');

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]))
			{
				// If this component does not use state then show a direct delete button as we can not trash
				//JToolBarHelper::deleteList('', 'usertcs.delete', 'JTOOLBAR_DELETE');
			}
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_tc');
		}
	}
}
