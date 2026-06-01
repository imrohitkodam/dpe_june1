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
class JmailalertsViewAlerts extends HtmlView
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
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		foreach ($this->items as $item)
		{
			$model           = $this->getModel('alerts');
			$item->plg_names = $model->getPluginNames($item->template);
		}

		$this->pagination = $this->get('Pagination');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		// Get the number of users subscribe for alerts
		$jmailalertsHelper = new JmailalertsHelper;
		$this->subsreport  = $jmailalertsHelper->getSubscribesCount(0);

		JmailalertsHelper::addSubmenu('alerts');

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
	 * @since  1.6
	 *
	 * @return  void
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/jmailalerts.php';

		$state = $this->get('State');
		$canDo = JmailalertsHelper::getActions('com_jmailalerts');

		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		ToolbarHelper::title(Text::_('COM_JMAILALERTS') . ': ' . Text::_('COM_JMAILALERTS_TITLE_ALERTS'), 'list');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/alert';

		if (JVERSION < '4.0.0')
		{
			if (file_exists($formPath))
			{
				if ($canDo->get('core.create'))
				{
					ToolbarHelper::addNew('alert.add', 'JTOOLBAR_NEW');
				}

				if ($canDo->get('core.edit') && isset($this->items[0]))
				{
					ToolbarHelper::editList('alert.edit', 'JTOOLBAR_EDIT');
				}
			}

			if ($canDo->get('core.edit.state'))
			{
				if (isset($this->items[0]->state))
				{
					ToolbarHelper::divider();
					ToolbarHelper::custom('alerts.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
					ToolbarHelper::custom('alerts.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
					ToolbarHelper::makeDefault('alerts.setDefault', 'COM_JMAILALERTS_TOOLBAR_SET_SETDEFAULT');
					ToolbarHelper::custom('alerts.unsetDefault', 'unfeatured state notdefault', '', 'COM_JMAILALERTS_TOOLBAR_UNSET_DEFAULT', false);
				}
				elseif (isset($this->items[0]))
				{
					// If this component does not use state then show a direct delete button as we can not trash
					ToolbarHelper::deleteList('', 'alerts.delete', 'JTOOLBAR_DELETE');
				}

				ToolbarHelper::divider();

				if (isset($this->items[0]->state))
				{
					ToolbarHelper::divider();
					ToolbarHelper::archiveList('alerts.archive', 'JTOOLBAR_ARCHIVE');
				}

				if (isset($this->items[0]->checked_out))
				{
					ToolbarHelper::custom('alerts.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
				}
			}

			// Show trash and delete for components that uses the state field
			if (isset($this->items[0]->state))
			{
				if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
				{
					ToolbarHelper::deleteList('', 'alerts.delete', 'JTOOLBAR_EMPTY_TRASH');
					ToolbarHelper::divider();
				}
				elseif ($canDo->get('core.edit.state'))
				{
					ToolbarHelper::trash('alerts.trash', 'JTOOLBAR_TRASH');
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
					$toolbar->addNew('alert.add');
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
					$childBar->edit('alert.edit')->listCheck(true);
				}
			}

			if ($canDo->get('core.edit.state'))
			{
				if (isset($this->items[0]->state))
				{
					$childBar->publish('alerts.publish')->listCheck(true);
					$childBar->unpublish('alerts.unpublish')->listCheck(true);

					$childBar->standardButton('featured')
						->text('COM_JMAILALERTS_TOOLBAR_SET_SETDEFAULT')
						->task('alerts.setDefault')
						->listCheck(true);

					$childBar->standardButton('circle')
						->text('COM_JMAILALERTS_TOOLBAR_UNSET_DEFAULT')
						->task('alerts.unsetDefault')
						->listCheck(true);
				}
				elseif (isset($this->items[0]))
				{
					// If this component does not use state then show a direct delete button as we can not trash
					$toolbar->delete('alerts.delete')
						->text('JTOOLBAR_EMPTY_TRASH')
						->message('JGLOBAL_CONFIRM_DELETE')
						->listCheck(true);
				}

				if (isset($this->items[0]->state))
				{
					$childBar->archive('alerts.archive')->listCheck(true);
				}

				if (isset($this->items[0]->checked_out))
				{
					$childBar->checkin('alerts.checkin')->listCheck(true);
				}
			}

			// Show trash and delete for components that uses the state field
			if (isset($this->items[0]->state))
			{
				if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
				{
					$toolbar->delete('alerts.delete')
						->text('JTOOLBAR_EMPTY_TRASH')
						->message('JGLOBAL_CONFIRM_DELETE')
						->listCheck(true);
				}
				elseif ($canDo->get('core.edit.state'))
				{
					$childBar->trash('alerts.trash')->listCheck(true);
				}
			}
		}

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::preferences('com_jmailalerts');
		}
	}
}
