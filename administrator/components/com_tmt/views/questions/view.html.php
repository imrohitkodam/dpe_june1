<?php
/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2023. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Component\ComponentHelper;

/**
 * View class for a list of Tmt.
 *
 * @since  1.0.0
 */
class TmtViewQuestions extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $params;

	protected $canManageQB;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{

		$app           = Factory::getApplication();
		$canDo = TmtHelper::getActions();
		$this->canManageQB = TmtHelper::canManageQuestions();

		if (!$this->canManageQB)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->setHeader('status', 403, true);

			return false;
		}

		$this->user    = Factory::getUser();
		$this->user_id = $this->user->get('id');

		// Get itemid
		$tmtFrontendHelper            = new tmtFrontendHelper;
		$this->create_question_itemid = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=questionform');
		$this->state                  = $this->get('State');

		if ($this->canManageQB == -1)
		{
			$this->state->set('filter.created_by', $this->user_id);
		}

		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');

		// Get filter form.
		$this->filterForm = $this->get('FilterForm');

		// Get active filters.
		$this->activeFilters = $this->get('ActiveFilters');
		$this->params = ComponentHelper::getParams('com_tmt');

		// Get ordering filters
		$this->filter_order = $this->escape($this->state->get('list.ordering'));
		$this->filter_order_Dir = $this->escape($this->state->get('list.direction'));

		$this->gradingtype = $app->input->get('gradingtype', '', 'STRING');

		$layout = $app->input->getInt('layout', 'default');

		if ($layout == 'qpopup')
		{
			$this->unique = $app->input->get('unique', '', 'STRING');
		}

		$layout = $app->input->post->get('layout', '', 'STRING');;
		
		if ($layout == 'modal')
		{
			$this->unique = $app->input->post->get('unique', '', 'STRING');
		}

		// Modal pop up for csv user import params
		$this->questions_csv_params           = array();
		$this->questions_csv_params['height'] = "500px";
		$this->questions_csv_params['width']  = "200px";
		$this->questions_csv_params['title']  = Text::_('COM_TMT_QUESTION_CSV_IMPORT');
		$this->questions_csv_params['url']    = Route::_(URI::base(true) . '/index.php?option=com_tmt&view=questions&tmpl=component&layout=questioncsv');

		TmtHelper::addSubmenu('questions');

		if (version_compare(JVERSION, '3.0', 'ge'))
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  Toolbar instance
	 *
	 * @since  1.0.0
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/tmt.php';
		$state = $this->get('State');

		$canDo = TmtHelper::getActions();

		if (JVERSION >= '3.0')
		{
			ToolbarHelper::title(Text::_('COM_TMT_Q_LIST_HEADING_MANAGE'), 'question-circle');
		}
		else
		{
			ToolbarHelper::title(Text::_('COM_TMT_Q_LIST_HEADING_MANAGE'), 'taxprofiles.png');
		}

		if ($this->canManageQB)
		{
			ToolbarHelper::addNew('question.add', 'JTOOLBAR_NEW');

			if (isset($this->items[0]))
			{
				ToolbarHelper::editList('question.edit', 'JTOOLBAR_EDIT');
			}

			if (isset($this->items[0]->state))
			{
				ToolbarHelper::divider();
				ToolbarHelper::custom('questions.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				ToolbarHelper::custom('questions.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);

				if ($state->get('filter.state') == -2)
				{
					ToolbarHelper::deleteList('COM_TMT_SURE_DELETE', 'questions.delete', 'JTOOLBAR_EMPTY_TRASH');
					ToolbarHelper::divider();
				}
				else
				{
					ToolbarHelper::trash('questions.trash', 'JTOOLBAR_TRASH');
					ToolbarHelper::divider();
				}
			}
			elseif (isset($this->items[0]))
			{
				// If this component does not use state then show a direct delete button as we can not trash
				ToolbarHelper::deleteList('COM_TMT_SURE_DELETE', 'questions.delete', 'JTOOLBAR_DELETE');
			}

			// Get an instance of the Toolbar
			$toolbar = Toolbar::getInstance('toolbar');

			if (JVERSION < '4.0.0')
			{
				$button = '<a data-bs-toggle="modal" href="#questioncsv" class="btn btn-small">
							<span class="icon-upload"></span>' . Text::_('COM_TMT_QUESTION_CSV_IMPORT') . '</a>';
			}
			else
			{
				$button = '&nbsp;&nbsp;<a
					class="btn btn-small btn-primary"
					onclick="document.getElementById(\'questioncsv\').open();"
					href="javascript:void(0);"><span class="icon-upload icon-white"></span> ' . Text::_('COM_TMT_QUESTION_CSV_IMPORT') . '</a>';
			}

			$toolbar->appendButton('Custom', $button);
		}

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::preferences('com_tjlms');
		}
	}
}
