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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

jimport('joomla.application.component.view');

/**
 * View class for a list of Tjlms.
 *
 * @since  1.0.0
 */
class TjlmsViewenrolment extends HtmlView
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
		$app             = Factory::getApplication();
		$input 			 = $app->input;

		$this->canDo 	 = TjlmsHelper::getActions();
		$canEnroll		 = false;
		$selectedcourse = $input->get('selectedcourse', '', 'ARRAY');
		$course_al = $input->get('course_al', '', 'INT');

		if (!empty($selectedcourse))
		{
			$tjlmsCoursesHelper = new TjlmsCoursesHelper;
			$this->courseInfo = $tjlmsCoursesHelper->getCourseColumn($selectedcourse[0], 'title');
		}

		$this->course_id = 0;

		if ($course_al && isset($selectedcourse[0]))
		{
			$this->course_id = $selectedcourse[0];
		}

		if ($this->course_id)
		{
			$canEnroll = TjlmsHelper::canManageCourseEnrollment($this->course_id);
		}
		else
		{
			$canEnroll = TjlmsHelper::canManageEnrollment();

			// Own courses enrollment access
			if ($canEnroll === -1)
			{
				// $this->setState('filter.created_by', $userId);
			}
		}

		if (!$canEnroll)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->setHeader('status', 403, true);

			return false;
		}

		$this->state = $this->get('State');

		// Only Manager
		if ($canEnroll === -2)
		{
			$this->state->set('filter.subuserfilter', 1);
		}

		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		if ($canEnroll === -2)
		{
			$this->filterForm->removeField('subuserfilter', 'filter');
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		TjlmsHelper::addSubmenu('enrolment');
		$this->addToolbar();

		if (JVERSION >= '3.0')
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
	 * @since	1.0.0
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/tjlms.php';
		$state = $this->get('State');
		ToolbarHelper::title(Text::_('COM_TJLMS_TITLE_ENROLLMENT'), 'list');

		// Set sidebar action - New in 3.0
		if (JVERSION >= '3.0')
		{
			JHtmlSidebar::setAction('index.php?option=com_tjlms&view=enrolment');
		}

		$this->extra_sidebar = '';
	}
}
