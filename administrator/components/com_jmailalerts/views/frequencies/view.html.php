<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of Jmailalerts.
 *
 * @package  JMailAlerts
 *
 * @since    2.5
 */
class JmailalertsViewFrequencies extends HtmlView
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
	 * @var  \JPagination
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var  \JObject
	 */
	protected $state;

	/**
	 * Form object for search filters
	 *
	 * @var  \JForm
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var  array
	 */
	public $activeFilters;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$this->state         = $this->get('State');
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		JmailalertsHelper::addSubmenu('frequencies');
		$this->addToolbar();

		if (JVERSION < '4.0.0')
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		parent::display($tpl);
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
		require_once JPATH_COMPONENT . '/helpers/jmailalerts.php';

		$state = $this->get('State');
		$canDo = JmailalertsHelper::getActions('com_jmailalerts');

		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		ToolbarHelper::title(Text::_('COM_JMAILALERTS') . ': ' . Text::_('COM_JMAILALERTS_TITLE_FREQUENCIES'), 'list');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/frequency';

		if (JVERSION < '4.0.0')
		{
			if (file_exists($formPath))
			{
				if ($canDo->get('core.create'))
				{
					ToolbarHelper::addNew('frequency.add', 'JTOOLBAR_NEW');
				}

				if ($canDo->get('core.edit') && isset($this->items[0]))
				{
					ToolbarHelper::editList('frequency.edit', 'JTOOLBAR_EDIT');
				}
			}

			if ($canDo->get('core.edit.state'))
			{
				if (isset($this->items[0]->state))
				{
					ToolbarHelper::divider();
					ToolbarHelper::custom('frequencies.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
					ToolbarHelper::custom('frequencies.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
				}
				elseif (isset($this->items[0]))
				{
					// If this component does not use state then show a direct delete button as we can not trash
					ToolbarHelper::deleteList('', 'frequencies.delete', 'JTOOLBAR_DELETE');
				}

				if (isset($this->items[0]->state))
				{
					ToolbarHelper::divider();
					ToolbarHelper::archiveList('frequencies.archive', 'JTOOLBAR_ARCHIVE');
				}

				if (isset($this->items[0]->checked_out))
				{
					ToolbarHelper::custom('frequencies.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
				}
			}

			// Show trash and delete for components that uses the state field
			if (isset($this->items[0]->state))
			{
				if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
				{
					ToolbarHelper::deleteList('', 'frequencies.delete', 'JTOOLBAR_EMPTY_TRASH');
					ToolbarHelper::divider();
				}
				elseif ($canDo->get('core.edit.state'))
				{
					ToolbarHelper::trash('frequencies.trash', 'JTOOLBAR_TRASH');
					ToolbarHelper::divider();
				}
			}
		}
		else
		{
			if (file_exists($formPath))
			{
				if ($canDo->get('core.create'))
				{
					$toolbar->addNew('frequency.add');
				}
			}

			$dropdown = $toolbar->dropdownButton('status-group')
				->text('JTOOLBAR_CHANGE_STATUS')
				->toggleSplit(false)
				->icon('icon-ellipsis-h')
				->buttonClass('btn btn-action')
				->listCheck(true);

			$childBar = $dropdown->getChildToolbar();

			if (file_exists($formPath))
			{
				if ($canDo->get('core.edit') && isset($this->items[0]))
				{
					$childBar->edit('frequency.edit')->listCheck(true);
				}
			}

			if ($canDo->get('core.edit.state'))
			{
				if (isset($this->items[0]->state))
				{
					$childBar->publish('frequencies.publish')->listCheck(true);
					$childBar->unpublish('frequencies.unpublish')->listCheck(true);
				}
				elseif (isset($this->items[0]))
				{
					// If this component does not use state then show a direct delete button as we can not trash
					$toolbar->delete('frequencies.delete')
						->text('JTOOLBAR_EMPTY_TRASH')
						->message('JGLOBAL_CONFIRM_DELETE')
						->listCheck(true);
				}

				if (isset($this->items[0]->state))
				{
					$childBar->archive('frequencies.archive')->listCheck(true);
				}

				if (isset($this->items[0]->checked_out))
				{
					$childBar->checkin('frequencies.checkin')->listCheck(true);
				}
			}

			// Show trash and delete for components that uses the state field
			if (isset($this->items[0]->state))
			{
				if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
				{
					$toolbar->delete('frequencies.delete')
						->text('JTOOLBAR_EMPTY_TRASH')
						->message('JGLOBAL_CONFIRM_DELETE')
						->listCheck(true);
				}
				elseif ($canDo->get('core.edit.state'))
				{
					$childBar->trash('frequencies.trash')->listCheck(true);
				}
			}
		}

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::preferences('com_jmailalerts');
		}
	}
}
