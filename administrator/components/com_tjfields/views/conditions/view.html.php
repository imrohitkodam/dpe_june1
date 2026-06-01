<?php
/**
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2023 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die();
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of countries.
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsViewConditions extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display ($tpl = null)
	{
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->input = Factory::getApplication()->input;
		$this->model               = $this->getModel();

		// Check for errors.
		$errors = $this->get('Errors');

		if (count($errors))
		{
			throw new Exception(implode("\n", $errors));
		}

		TjfieldsHelper::addSubmenu('conditions');

		if (JVERSION >= '3.0')
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolbar ()
	{
		require_once JPATH_COMPONENT . '/helpers/tjfields.php';

		// Let's get the extension name
		$client = Factory::getApplication()->input->get('client', '', 'STRING');

		$extention = explode('.', $client);

		$canDo = TjfieldsHelper::getActions($extention[0], 'condition');
		$state = $this->get('State');
		$extensionName = strtoupper($client);

		// Need to load the menu language file as mod_menu hasn't been loaded yet.
		$lang = Factory::getLanguage();
		$lang->load($client, JPATH_ADMINISTRATOR, null, false, true);

		if (JVERSION >= '3.0')
		{
			ToolbarHelper::title(Text::_('COM_TJFIELDS_TITLE_CONDITIONS'), 'list');
		}
		else
		{
			ToolbarHelper::title(Text::_('COM_TJFIELDS_TITLE_CONDITIONS'), 'conditions.png');
		}

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/condition';

		if (file_exists($formPath))
		{
			if ($canDo->get('core.create'))
			{
				ToolbarHelper::addNew('condition.add', 'JTOOLBAR_NEW');
			}

			if ($canDo->get('core.edit') && isset($this->items[0]))
			{
				ToolbarHelper::editList('condition.edit', 'JTOOLBAR_EDIT');
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				ToolbarHelper::divider();
				ToolbarHelper::custom('conditions.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				ToolbarHelper::custom('conditions.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
		}
		
		// Show trash and delete for components that uses the state field
		if (isset($this->items[0]->state))
		{
			if ($canDo->get('core.delete'))
			{
				ToolbarHelper::deleteList('', 'conditions.delete', 'JTOOLBAR_DELETE');
				ToolbarHelper::divider();
			}
		}


		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::preferences('com_tjfields');
		}

		if (JVERSION >= '3.0')
		{
			// Set sidebar action
			JHtmlSidebar::setAction('index.php?option=com_tjfields&view=conditions&client=' . $client);
		}

		$this->extra_sidebar = '';
	}
}
