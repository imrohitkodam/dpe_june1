<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\Toolbar;

HTMLHelper::_('behavior.formvalidator');
jimport('joomla.application.component.view');

/**
 * View class for a list of Tjlms.
 *
 * @since  1.0.0
 */
class TjlmsViewManageenrollments extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$app  = Factory::getApplication();
		$canDo = TjlmsHelper::getActions();

		if (!$canDo->get('view.manageenrollment'))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
			$app->setHeader('status', 500, true);

			return false;
		}

		$this->state		= $this->get('State');
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$input = Factory::getApplication()->input;
		$course_id = $input->get('course_id', '', 'INT');

		if ($course_id)
		{
			$tjlmsCoursesHelper = new TjlmsCoursesHelper;
			$this->courseInfo = $tjlmsCoursesHelper->getCourseColumn($course_id, 'title');
		}
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		TjlmsHelper::addSubmenu('manageenrollments');

		$comtjlmsHelper = new comtjlmsHelper;
		$linkOfStudentCourseReport = Uri::root() . 'index.php?option=com_tjlms&view=student_course_report';
		$this->studentCourseDashboardItemid = $comtjlmsHelper->getitemid($linkOfStudentCourseReport);

		$this->addToolbar();
		$this->sidebar = '';

		if (JVERSION < '3.0')
		{
			// Creating status filter.
			$sstatus = array();
			$sstatus[] = JHTML::_('select.option', '', Text::_('COM_TJLMS_SELONE_STATE'));
			$sstatus[] = JHTML::_('select.option', 1, Text::_('JPUBLISHED'));
			$sstatus[] = JHTML::_('select.option', 0, Text::_('JUNPUBLISHED'));
			$this->sstatus = $sstatus;

			// Creating status filter.
			$coursefilter = array();
			$coursefilter[] = JHTML::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_COURSE'));
			$allcourses = $this->get('AllCourses');

			foreach ($allcourses as $c)
			{
				$coursefilter[] = HTMLHelper::_('select.option', $c->value, $c->text);
			}

			$this->coursefilter = $coursefilter;
		}

		if (!$course_id && JVERSION >= '3.0')
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		parent::display($tpl);
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
		require_once JPATH_COMPONENT . '/helpers/tjlms.php';

		$state	= $this->get('State');
		$canDo	= TjlmsHelper::getActions($state->get('filter.category_id'));

		if (JVERSION >= '3.0')
		{
			JToolBarHelper::title(Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENTS'), 'list');
		}
		else
		{
			JToolBarHelper::title(Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENTS'), 'manageenrollments.png');
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				JToolBarHelper::divider();
				JToolBarHelper::custom('manageenrollments.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				JToolBarHelper::custom('manageenrollments.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);

				// Show trash and delete for components that uses the state field
				JToolBarHelper::deleteList(Text::_('COM_TJLMS_SURE_DELETE'), 'manageenrollments.delete', 'JTOOLBAR_DELETE');
			}
		}
		// Get an instance of the Toolbar
		$toolbar = Toolbar::getInstance('toolbar');

		$button = '<a data-bs-toggle="modal" href="#import" class="btn btn-small">
						<span class="icon-upload"></span>' . Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT') . '</a>';
		$toolbar->appendButton('Custom', $button);

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_tjlms');
		}

		// Set sidebar action - New in 3.0
		if (JVERSION >= '3.0')
		{
			JHtmlSidebar::setAction('index.php?option=com_tjlms&view=manageenrollments');
		}

		$this->extra_sidebar = '';
	}
}
