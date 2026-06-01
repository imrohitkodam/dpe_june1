<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;

jimport('joomla.application.component.view');
jimport('techjoomla.common');

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of Tjlms.
 *
 * @since  1.0.0
 */
class TjlmsViewCoupons extends HtmlView
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

		if (!$canDo->get('view.coupons'))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
			$app->setHeader('status', 500, true);

			return false;
		}

		$this->techjoomlacommon   = new TechjoomlaCommon;
		$this->state              = $this->get('State');
		$this->items              = $this->get('Items');
		$this->pagination         = $this->get('Pagination');
		$this->filterForm         = $this->get('FilterForm');
		$this->activeFilters      = $this->get('ActiveFilters');
		$this->ComtjlmsHelper     = new ComtjlmsHelper;
		$this->tjlmsCoursesHelper = new TjlmsCoursesHelper;

		$input = Factory::getApplication()->input;

		// Get component params
		$this->lmsparams = $this->ComtjlmsHelper->getcomponetsParams('com_tjlms');

		require_once JPATH_SITE . '/components/com_tjlms/helpers/courses.php';
		require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/coupons.php';
		$this->tjlmsCoursesHelper = new TjlmsModelCoupons;

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		TjlmsHelper::addSubmenu('coupons');

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
		$canDo = TjlmsHelper::getActions($state->get('filter.category_id'));
		$user  = Factory::getUser();

		if (JVERSION >= '3.0')
		{
			ToolbarHelper::title(Text::_('COM_TJLMS_TITLE_COUPONS'), 'list');
		}
		else
		{
			ToolbarHelper::title(Text::_('COM_TJLMS_TITLE_COUPONS'), 'coupons.png');
		}

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/coupon';

		if (file_exists($formPath))
		{
			if ($user->authorise('create.coupons', 'com_tjlms'))
			{
				ToolbarHelper::addNew('coupon.add', 'JTOOLBAR_NEW');
			}

			if ($canDo->get('core.edit') && isset($this->items[0]))
			{
				ToolbarHelper::editList('coupon.edit', 'JTOOLBAR_EDIT');
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				ToolbarHelper::divider();
				ToolbarHelper::custom('coupons.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				ToolbarHelper::custom('coupons.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
			elseif (isset($this->items[0]) && $canDo->get('core.delete'))
			{
				// If this component does not use state then show a direct delete button as we can not trash
				ToolbarHelper::deleteList('COM_TJLMS_SURE_DELETE', 'coupons.delete', 'JTOOLBAR_DELETE');
			}

			if (isset($this->items[0]->checked_out))
			{
				ToolbarHelper::custom('coupons.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
			}
		}

		// Show trash and delete for components that uses the state field
		if (isset($this->items[0]->state))
		{
			if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
			{
				ToolbarHelper::deleteList('COM_TJLMS_SURE_DELETE', 'coupons.delete', 'JTOOLBAR_EMPTY_TRASH');
				ToolbarHelper::divider();
			}
			elseif ($canDo->get('core.edit.state'))
			{
				ToolbarHelper::trash('coupons.trash', 'JTOOLBAR_TRASH');
				ToolbarHelper::divider();
			}
		}

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::preferences('com_tjlms');
		}

		// Set sidebar action - New in 3.0
		if (JVERSION >= '3.0')
		{
			JHtmlSidebar::setAction('index.php?option=com_tjlms&view=coupons');
		}

		$this->extra_sidebar = '';
	}

	/**
	 * Function use to get all sort fileds
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function getSortFields()
	{
		return array(
		'a.id'               => Text::_('JGRID_HEADING_ID'),
		'a.ordering'         => Text::_('JGRID_HEADING_ORDERING'),
		'a.published'        => Text::_('COM_TJLMS_COUPONS_PUBLISHED'),
		'a.checked_out'      => Text::_('COM_TJLMS_COUPONS_CHECKED_OUT'),
		'a.checked_out_time' => Text::_('COM_TJLMS_COUPONS_CHECKED_OUT_TIME'),
		'a.created_by'       => Text::_('COM_TJLMS_COUPONS_CREATED_BY'),
		'a.name'             => Text::_('COM_TJLMS_COUPONS_NAME'),
		'a.code'             => Text::_('COM_TJLMS_COUPONS_CODE'),
		'a.value'            => Text::_('COM_TJLMS_COUPONS_VALUE'),
		'a.val_type'         => Text::_('COM_TJLMS_COUPONS_VAL_TYPE'),
		'a.max_use'          => Text::_('COM_TJLMS_COUPONS_MAX_USE'),
		'a.max_per_user'     => Text::_('COM_TJLMS_COUPONS_MAX_PER_USER'),
		'a.from_date'        => Text::_('COM_TJLMS_COUPONS_FROM_DATE'),
		'a.exp_date'         => Text::_('COM_TJLMS_COUPONS_EXP_DATE'),
		);
	}
}
