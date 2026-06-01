<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Multiagency
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  2017 Techjoomla
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

jimport('joomla.application.component.view');

/**
 * View class for a list of Multiagency.
 *
 * @since  1.6
 */
class MultiagencyViewLicences extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

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

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		MultiagencyHelpersMultiagency::addSubmenu('licences');

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
		$canDo = MultiagencyHelpersMultiagency::getActions();

		ToolbarHelper::title(Text::_('COM_MULTIAGENCY_TITLE_LICENCES'), 'licences.png');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/licence';

		if (file_exists($formPath))
		{
			if ($canDo->get('core.create'))
			{
				// ToolbarHelper::addNew('licence.add', 'JTOOLBAR_NEW');

				if (isset($this->items[0]))
				{
					// ToolbarHelper::custom('licences.duplicate', 'copy.png', 'copy_f2.png', 'JTOOLBAR_DUPLICATE', true);
				}
			}

			if ($canDo->get('core.edit') && isset($this->items[0]))
			{
				// ToolbarHelper::editList('licence.edit', 'JTOOLBAR_EDIT');
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				ToolbarHelper::divider();
				ToolbarHelper::custom('licences.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				ToolbarHelper::custom('licences.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
			elseif (isset($this->items[0]))
			{
				// If this component does not use state then show a direct delete button as we can not trash
				ToolbarHelper::deleteList('', 'licences.delete', 'JTOOLBAR_DELETE');
			}

			if (isset($this->items[0]->state))
			{
				ToolbarHelper::divider();
				ToolbarHelper::archiveList('licences.archive', 'JTOOLBAR_ARCHIVE');
			}

			if (isset($this->items[0]->checked_out))
			{
				ToolbarHelper::custom('licences.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
			}
		}

		// Show trash and delete for components that uses the state field
		if (isset($this->items[0]->state))
		{
			if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
			{
				ToolbarHelper::deleteList('', 'licences.delete', 'JTOOLBAR_EMPTY_TRASH');
				ToolbarHelper::divider();
			}
			elseif ($canDo->get('core.edit.state'))
			{
				ToolbarHelper::trash('licences.trash', 'JTOOLBAR_TRASH');
				ToolbarHelper::divider();
			}
		}

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::preferences('com_multiagency');
		}

		// Set sidebar action - New in 3.0
		JHtmlSidebar::setAction('index.php?option=com_multiagency&view=licences');

		$this->extra_sidebar = '';
		JHtmlSidebar::addFilter(

			Text::_('JOPTION_SELECT_PUBLISHED'),

			'filter_published',

			HTMLHelper::_('select.options', HTMLHelper::_('jgrid.publishedOptions'), "value", "text", $this->state->get('filter.state'), true)

		);
	}

	/**
	 * Method to order fields
	 *
	 * @return void
	 */
	protected function getSortFields()
	{
		return array(
			'a.`id`' => Text::_('JGRID_HEADING_ID'),
			'a.`ordering`' => Text::_('JGRID_HEADING_ORDERING'),
			'a.`state`' => Text::_('JSTATUS'),
			'a.`course_id`' => Text::_('COM_MULTIAGENCY_SUBSCRIPTIONS_COURSE_ID'),
			'a.`total_seats`' => Text::_('COM_MULTIAGENCY_SUBSCRIPTIONS_TOTAL_SEATS'),
			'a.`used_seats`' => Text::_('COM_MULTIAGENCY_SUBSCRIPTIONS_USED_SEATS'),
			'a.`start_date`' => Text::_('COM_MULTIAGENCY_SUBSCRIPTIONS_START_DATE'),
			'a.`end_date`' => Text::_('COM_MULTIAGENCY_SUBSCRIPTIONS_END_DATE'),
		);
	}
}
