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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;

jimport('joomla.application.component.view');
jimport('techjoomla.common');

require_once JPATH_SITE . '/administrator/components/com_tjlms/helpers/tjlms.php';
/**
 * View to edit
 *
 * @since  1.0.0
 */
class TjlmsViewCoupon extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

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

		$this->techjoomlacommon = new TechjoomlaCommon;
		$this->state = $this->get('State');
		$this->item = $this->get('Item');
		$this->form = $this->get('Form');

		// Import helper for declaring language constant
		JLoader::import('TjlmsHelper', Uri::root() . 'administrator/components/com_tjlms/helpers/tjlms.php');

		// Call helper function
		TjlmsHelper::getLanguageConstant();

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
	 * @since	1.0.0
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user = Factory::getUser();
		$isNew = ($this->item->id == 0);

		if (isset($this->item->checked_out))
		{
			$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$canDo		= TjlmsHelper::getActions();

		if ($isNew)
		{
			$viewTitle = Text::_('COM_TJLMS_TITLE_COUPON_ADD');
		}
		else
		{
			$viewTitle = Text::_('COM_TJLMS_TITLE_COUPON_EDIT');
		}

		if (JVERSION >= '3.0')
		{
			ToolbarHelper::title($viewTitle, 'pencil-2');
		}
		else
		{
			ToolbarHelper::title($viewTitle, 'coupon.png');
		}

		// If not checked out, can save the item.
		if (!$checkedOut && ($canDo->get('core.edit')||($canDo->get('core.create'))))
		{
			ToolbarHelper::apply('coupon.apply', 'JTOOLBAR_APPLY');
			ToolbarHelper::save('coupon.save', 'JTOOLBAR_SAVE');
			ToolbarHelper::save2new('coupon.save2new');
		}

		if (empty($this->item->id))
		{
			ToolbarHelper::cancel('coupon.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolbarHelper::cancel('coupon.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
