<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View to edit
 *
 * @since  2.5.0
 */
class JmailalertsViewSubscriber extends HtmlView
{
	/**
	 * The \JForm object
	 *
	 * @var \Joomla\CMS\Form\Form
	 */
	protected $form;

	/**
	 * The active item
	 *
	 * @var  object
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		$this->state	 = $this->get('State');
		$this->item		 = $this->get('Item');
		$this->form		 = $this->get('Form');

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
	 * @since  1.6
	 *
	 * @return  void
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user = Factory::getUser();

		if (isset($this->item->checked_out))
		{
			$checkedOut	 = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$canDo   = JmailalertsHelper::getActions();
		$toolbar = Toolbar::getInstance();

		ToolbarHelper::title(Text::_('COM_JMAILALERTS') . ': ' . Text::_('COM_JMAILALERTS_TITLE_SUBSCRIBER'), 'list');

		if (JVERSION < '4.0.0')
		{
			// If not checked out, can save the item.
			if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
			{
				ToolbarHelper::apply('subscriber.apply', 'JTOOLBAR_APPLY');
				ToolbarHelper::save('subscriber.save', 'JTOOLBAR_SAVE');
			}

			if (!$checkedOut && ($canDo->get('core.create')))
			{
				ToolbarHelper::custom('subscriber.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
			}

			// If an existing item, can save to a copy.

			/*
			if (!$isNew && $canDo->get('core.create'))
			{
				ToolbarHelper::custom('subscriber.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}
			*/

			if (empty($this->item->id))
			{
				ToolbarHelper::cancel('subscriber.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				ToolbarHelper::cancel('subscriber.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		else
		{
			// If not checked out, can save the item.
			if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
			{
				$toolbar->apply('subscriber.apply');
			}

			$saveGroup = $toolbar->dropdownButton('save-group');
			$childBar  = $saveGroup->getChildToolbar();

			if (!$checkedOut && ($canDo->get('core.create')))
			{
				$childBar->save('subscriber.save');
			}

			if (!$checkedOut && ($canDo->get('core.create')))
			{
				$childBar->save2new('subscriber.save2new');
			}

			/*
			if (!$isNew && $canDo->get('core.create'))
			{
				$childBar->save2copy('subscriber.save2copy');
			}
			*/

			if (empty($this->item->id))
			{
				$toolbar->cancel('subscriber.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				$toolbar->cancel('subscriber.cancel', 'JTOOLBAR_CLOSE');
			}
		}
	}
}
