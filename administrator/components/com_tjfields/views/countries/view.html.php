<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die();
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

jimport('joomla.application.component.view');

/**
 * View class for a list of countries.
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsViewCountries extends HtmlView
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

		// Check for errors.
		$errors = $this->get('Errors');

		if (count($errors))
		{
			throw new Exception(implode("\n", $errors));
		}

		TjfieldsHelper::addSubmenu('countries');

		$this->publish_states = array(
			'' => Text::_('JOPTION_SELECT_PUBLISHED'),
			'1'  => Text::_('JPUBLISHED'),
			'0'  => Text::_('JUNPUBLISHED')
		);

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

		$canDo = TjfieldsHelper::getActions($extention[0], 'country');

		$extensionName = strtoupper($client);

		// Need to load the menu language file as mod_menu hasn't been loaded yet.
		$lang = Factory::getLanguage();
		$lang->load($client, JPATH_ADMINISTRATOR, null, false, true);

		if (JVERSION >= '3.0')
		{
			JToolBarHelper::title(Text::_($extensionName) . ': ' . Text::_('COM_TJFIELDS_TITLE_COUNTRIES'), 'list');
		}
		else
		{
			JToolBarHelper::title(Text::_($extensionName) . ': ' . Text::_('COM_TJFIELDS_TITLE_COUNTRIES'), 'countries.png');
		}

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/country';

		if (file_exists($formPath))
		{
			if ($canDo->get('core.create'))
			{
				JToolBarHelper::addNew('country.add', 'JTOOLBAR_NEW');
			}

			if ($canDo->get('core.edit') && isset($this->items[0]))
			{
				JToolBarHelper::editList('country.edit', 'JTOOLBAR_EDIT');
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				JToolBarHelper::divider();
				JToolBarHelper::custom('countries.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				JToolBarHelper::custom('countries.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_tjfields');
		}

		if (JVERSION >= '3.0')
		{
			// Set sidebar action
			JHtmlSidebar::setAction('index.php?option=com_tjfields&view=countries');
		}

		$this->extra_sidebar = '';
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since   3.0
	 */
	protected function getSortFields ()
	{
		return array(
			'a.ordering' => Text::_('COM_TJFIELDS_ORDERING'),
			'state' => Text::_('COM_TJFIELDS_STATUS'),
			'a.country' => Text::_('COM_TJFIELDS_COUNTRIES_COUNTRY'),
			'a.country_3_code' => Text::_('COM_TJFIELDS_COUNTRIES_COUNTRY_3_CODE'),
			'a.country_code' => Text::_('COM_TJFIELDS_COUNTRIES_COUNTRY_CODE'),
			'a.country_jtext' => Text::_('COM_TJFIELDS_COUNTRIES_COUNTRY_JTEXT'),
			'a.id' => Text::_('COM_TJFIELDS_COUNTRIES_COUNTRY_ID')
		);
	}
}
