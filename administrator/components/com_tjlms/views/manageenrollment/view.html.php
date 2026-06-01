<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2020 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
Use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

// No direct access
defined('_JEXEC') or die;

JLoader::register('TjlmsHelper', JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php');

/**
 * View class for a edit of Tjlms manageenrollment.
 *
 * @since  1.5.0
 */
class TjlmsViewManageenrollment extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$this->item  = $this->get('Item');
		$this->state = $this->get('State');
		$this->form  = $this->get('Form');

		// End time should gets changed only for paid course
		$this->item->courseType = TjLms::course($this->item->course_id)->isPaid();

		if (empty($this->item->courseType))
		{
			$this->form->setFieldAttribute('end_time', 'disabled', 'true');
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
	 * @return  void
	 *
	 * @since   1.5.0
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user  = Factory::getUser();

		if (isset($this->item->checked_out))
		{
			$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$this->canDo = TjlmsHelper::getActions();

		ToolbarHelper::title(Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENT_EDIT'), 'pencil-2');

		// If not checked out, can save the item.
		if (!$checkedOut && ($this->canDo->get('core.edit')||($this->canDo->get('core.create'))))
		{
			ToolbarHelper::apply('manageenrollment.apply', 'JTOOLBAR_APPLY');
			ToolbarHelper::save('manageenrollment.save', 'JTOOLBAR_SAVE');
		}

		if (empty($this->item->id))
		{
			ToolbarHelper::cancel('manageenrollment.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolbarHelper::cancel('manageenrollment.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
