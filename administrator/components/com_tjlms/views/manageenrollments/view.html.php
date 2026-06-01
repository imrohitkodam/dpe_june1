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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Toolbar\ToolbarHelper;

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
		$this->state		= $this->get('State');
		$app   = Factory::getApplication();
		$input = $app->input;
		$course_id = $input->get('course_id', '', 'INT');

		$this->canDo 	= TjlmsHelper::getActions();
		$canEnroll		= false;

		$user   = Factory::getUser();
		$userId = $user->id;

		if ($course_id)
		{
			$canEnroll = TjlmsHelper::canManageCourseEnrollment($course_id);
		}
		else
		{
			$canEnroll = TjlmsHelper::canManageEnrollment();

			// Own courses enrollment access
			if ($canEnroll === -1)
			{
				$this->state->set('filter.created_by', $userId);
			}
		}

		// Only Manager
		if ($canEnroll === -2)
		{
			$this->state->set('filter.subuserfilter', 1);
		}

		if (!$canEnroll)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->setHeader('status', 403, true);

			return false;
		}

		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Modal pop up for csv user import params
		$this->user_csv_params           = array();
		$this->user_csv_params['height'] = "500px";
		$this->user_csv_params['width']  = "200px";
		$this->user_csv_params['title']  = Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT');
		$this->user_csv_params['url']    = Route::_(URI::base(true) . '/index.php?option=com_tjlms&view=manageenrollments&tmpl=component&layout=import');

		if ($course_id)
		{
			$tjlmsCoursesHelper = new TjlmsCoursesHelper;
			$this->courseInfo = $tjlmsCoursesHelper->getCourseColumn($course_id, 'title');
		}

		if ($canEnroll === -2)
		{
			$this->filterForm->removeField('subuserfilter', 'filter');
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

		// Get an instance of the Toolbar
		$toolbar = Toolbar::getInstance('toolbar');

		if (JVERSION >= '3.0')
		{
			ToolBarHelper::title(Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENTS'), 'list');
		}
		else
		{
			ToolBarHelper::title(Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENTS'), 'manageenrollments.png');
		}

		if ($canDo->get('core.edit.state'))
		{
			// Add batch button manage enrollment view

			if (isset($this->items[0]->state))
			{
				ToolBarHelper::divider();
				ToolBarHelper::custom('manageenrollments.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				ToolBarHelper::custom('manageenrollments.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);

				// Show trash and delete for components that uses the state field
				ToolBarHelper::deleteList(Text::_('COM_TJLMS_SURE_DELETE'), 'manageenrollments.delete', 'JTOOLBAR_DELETE');
			}
		}

		if (($canDo->get('core.create') || $canDo->get('core.edit')) && isset($this->items[0]))
		{
			ToolBarHelper::editList('manageenrollment.edit', 'JTOOLBAR_EDIT');
		}

		if (JVERSION < '4.0.0')
		{
			$button = '<a data-bs-toggle="modal" href="#import" class="btn btn-small">
						<span class="icon-upload"></span>' . Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT') . '</a>';
		}
		else
		{
			$button = '&nbsp;&nbsp;<a
				class="btn btn-small btn-primary"
				onclick="document.getElementById(\'import\').open();"
				href="javascript:void(0);"><span class="icon-upload icon-white"></span> ' . Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT') . '</a>';
		}

		$toolbar->appendButton('Custom', $button);
		$title = Text::_('COM_TJLMS_JTOOLBAR_BATCH_ASSIGN_TITLE');

		// Add batch button manage enrollment view
		$layout = new FileLayout('joomla.toolbar.batch');
		$dhtml = $layout->render(array('title' => $title));
		$toolbar->appendButton('Custom', $dhtml, 'batch');

		HTMLHelper::script('administrator/components/com_tjlms/assets/js/tjlms_admin.js');

		$link = "index.php?option=com_tjlms&view=enrolment&tmpl=component&selectedcourse[]=0" . "'";

		// Add New button manage enrollment view
		$toolbar->prependButton(
		'Custom', '<a class="modal btn btn-small btn-success"
		onclick="opentjlmsSqueezeBox(' . $link . ')">
		<span class="icon-new icon-white"></span>' . Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENTS_NEW') . '</a>'
		);

		//$toolbar->appendButton('Popup', 'new', 'COM_TJLMS_TITLE_MANAGEENROLLMENTS_NEW', 'index.php?option=com_tjlms&view=enrolment&tmpl=component&selectedcourse[]=0', 550, 350, '', '', '', Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENTS_NEW'));
		$toolbar->appendButton('Popup', 'new', 'COM_TJLMS_TITLE_MANAGEENROLLMENTS_NEW', $link, 750, 550, '', '', '', Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENTS_NEW'));

		if ($canDo->get('core.admin'))
		{
			ToolBarHelper::preferences('com_tjlms');
		}

		// Set sidebar action - New in 3.0
		if (JVERSION >= '3.0')
		{
			JHtmlSidebar::setAction('index.php?option=com_tjlms&view=manageenrollments');
		}

		$this->extra_sidebar = '';
	}
}
