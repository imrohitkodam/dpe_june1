<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2017. All rights reserved.
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
jimport('joomla.application.component.view');

/**
 * View class for a list of Tjlms.
 *
 * @since  1.0.0
 */
class TjlmsViewenrolluser extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $type;

	protected $canDo;

	protected $model;

	protected $filterForm;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$app                    = Factory::getApplication();
		$this->input 			= $app->input;
		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);
		$this->canDo 	 = TjlmsHelper::getActions();
		$canEnroll		 = false;
		$this->selectedcourse = $this->input->get('selectedcourse', '0', 'ARRAY');
		$this->courseInfo = $this->course_id = (int) $this->selectedcourse[0];
		$course_al = $this->input->get('course_al', '', 'INT');
		$this->type = $this->input->get('type', '');
		$this->model = $this->getModel();

		if ($this->course_id)
		{
			$tjlmsCoursesHelper = new TjlmsCoursesHelper;
			$this->courseInfo = $tjlmsCoursesHelper->getCourseColumn($this->course_id, array('title','access'));
		}

		if (!$this->courseInfo)
		{
			$app->enqueueMessage(Text::_('COM_TJLMS_COURSE_DOES_NOT_EXISTS'), 'error');
            $app->setHeader('status', 403, true);

			return false;
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
				$this->setState('filter.created_by', $userId);
			}
		}

		if (!$canEnroll && ($this->type != 'reco' || !Factory::getUser()->id))
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

		if ($this->type == 'reco')
		{
			$type = $this->model->setState('type', 'reco');
			$this->state->set('filter.subuserfilter', 0);
		}

		$this->items = $this->model->getItems();
		$this->pagination = $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Only Manager
		if ($canEnroll === -2)
		{
			$this->filterForm->removeField('subuserfilter', 'filter');
		}

		$comtjlmsHelper = new comtjlmsHelper;
		$this->aclGroups = $comtjlmsHelper->getACLGroups($this->courseInfo->access);

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$comtjlmsHelper::getLanguageConstant();

		parent::display($tpl);
	}
}
