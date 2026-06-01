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

jimport('joomla.application.component.view');
jimport('techjoomla.common');

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
		require_once JPATH_SITE . '/components/com_tjlms/helpers/courses.php';
		require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php';
		$this->comtjlmsHelper = new comtjlmsHelper;
		$canDo = TjlmsHelper::getActions();
		$user = Factory::getUser();
		$app = Factory::getApplication();

		if (!$canDo->get('view.coupons'))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

			return false;
		}

		$this->techjoomlacommon = new TechjoomlaCommon;
		$this->user			= $user;
		$this->state		= $this->get('State');
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->ComtjlmsHelper = new ComtjlmsHelper;
		$this->tjlmsCoursesHelper = new TjlmsCoursesHelper;

		// Get component params
		$this->lmsparams = $this->ComtjlmsHelper->getcomponetsParams('com_tjlms');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}
			// Creating status filter.
			$sstatus = array();
			$sstatus[] = JHTML::_('select.option', '', Text::_('COM_TJLMS_SELONE_STATE'));
			$sstatus[] = JHTML::_('select.option', 1, Text::_('JPUBLISHED'));
			$sstatus[] = JHTML::_('select.option', 0, Text::_('JUNPUBLISHED'));
			$this->sstatus = $sstatus;

			// Creating status filter.
			$ctype = array();
			$ctype[] = JHTML::_('select.option', '', Text::_('COM_TJLMS_SELONE_COUPON_TYPE'));
			$ctype[] = JHTML::_('select.option', 0, Text::_('COM_TJLMS_COUPON_FLAT'));
			$ctype[] = JHTML::_('select.option', 1, Text::_('COM_TJLMS_COUPON_PERCENTAGE'));
			$this->ctype = $ctype;

		parent::display($tpl);
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
		'a.id' => Text::_('JGRID_HEADING_ID'),
		'a.ordering' => Text::_('JGRID_HEADING_ORDERING'),
		'a.published' => Text::_('COM_TJLMS_COUPONS_PUBLISHED'),
		'a.checked_out' => Text::_('COM_TJLMS_COUPONS_CHECKED_OUT'),
		'a.checked_out_time' => Text::_('COM_TJLMS_COUPONS_CHECKED_OUT_TIME'),
		'a.created_by' => Text::_('COM_TJLMS_COUPONS_CREATED_BY'),
		'a.name' => Text::_('COM_TJLMS_COUPONS_NAME'),
		'a.code' => Text::_('COM_TJLMS_COUPONS_CODE'),
		'a.value' => Text::_('COM_TJLMS_COUPONS_VALUE'),
		'a.val_type' => Text::_('COM_TJLMS_COUPONS_VAL_TYPE'),
		'a.max_use' => Text::_('COM_TJLMS_COUPONS_MAX_USE'),
		'a.max_per_user' => Text::_('COM_TJLMS_COUPONS_MAX_PER_USER'),
		'a.from_date' => Text::_('COM_TJLMS_COUPONS_FROM_DATE'),
		'a.exp_date' => Text::_('COM_TJLMS_COUPONS_EXP_DATE'),
		);
	}
}
