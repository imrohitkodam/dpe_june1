<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tmt
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * TMT is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
/**
 * View to edit
 *
 * @since  1.0.0
 */
class TmtViewTests extends HtmlView
{
	/**
	 * The item authors
	 *
	 * @var  stdClass
	 */
	protected $authors;

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

	protected $cid;

	protected $mid;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since  1.0.0
	 */
	public function display($tpl = null)
	{
		$app  = Factory::getApplication();
		$canDo = TmtHelper::getActions();

		$this->canManageQB = TmtHelper::canManageQuestions();

		if (!$this->canManageQB)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
        	$app->setHeader('status', 403, true);
			
			return false;
		}

		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		
		$input = $app->input;
		$this->user	 = Factory::getUser();
		$this->user_id		= $this->user->get('id');

		if ($this->_layout == 'modal')
		{
			$this->cid = $input->get('cid', '', 'INT');
			$this->mid = $input->get('mid', '', 'INT');
		}

		TmtHelper::addSubmenu('tests');
		$this->addToolbar();
		$this->sidebar = JHtmlSidebar::render();

		return parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  Toolbar instance
	 *
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/tmt.php';

		$state	= $this->get('State');
		$canDo = TmtHelper::getActions();

		JToolBarHelper::title(Text::_('COM_TMT_TITLE_TESTS'), 'tests.png');

		if ($this->canManageQB)
		{
			JToolBarHelper::addNew('test.add', 'JTOOLBAR_NEW');

			if (isset($this->items[0]))
			{
				JToolBarHelper::editList('test.edit', 'JTOOLBAR_EDIT');
			}

			if (isset($this->items[0]->state))
			{
				JToolBarHelper::divider();
				JToolBarHelper::custom('tests.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				JToolBarHelper::custom('tests.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);

				if ($state->get('filter.state') == -2)
				{
					JToolBarHelper::deleteList('COM_TMT_SURE_DELETE', 'questions.delete', 'JTOOLBAR_EMPTY_TRASH');
					JToolBarHelper::divider();
				}
				else
				{
					JToolBarHelper::trash('tests.trash', 'JTOOLBAR_TRASH');
					JToolBarHelper::divider();
				}
			}
			elseif (isset($this->items[0]))
			{
				// If this component does not use state then show a direct delete button as we can not trash
				JToolBarHelper::deleteList('COM_TMT_SURE_DELETE', 'questions.delete', 'JTOOLBAR_DELETE');
			}
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_tjlms');
		}
	}
}
