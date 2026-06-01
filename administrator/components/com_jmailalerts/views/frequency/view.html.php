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
 * @package  JMailAlerts
 *
 * @since    2.5
 */
class JmailalertsViewFrequency extends HtmlView
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
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');

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
	 * @since   1.6
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user  = Factory::getUser();
		$isNew = ($this->item->id == 0);

		if (isset($this->item->checked_out))
		{
			$checkedOut	 = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$canDo = JmailalertsHelper::getActions('com_jmailalerts');

		$toolbar = Toolbar::getInstance();

		ToolbarHelper::title(Text::_('COM_JMAILALERTS') . ': ' . Text::_('COM_JMAILALERTS_TITLE_FREQUENCY'), 'pencil-2');

		if (JVERSION < '4.0.0')
		{
			// If not checked out, can save the item.
			if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
			{
				ToolbarHelper::apply('frequency.apply', 'JTOOLBAR_APPLY');
				ToolbarHelper::save('frequency.save', 'JTOOLBAR_SAVE');
			}

			if (!$checkedOut && ($canDo->get('core.create')))
			{
				ToolbarHelper::custom('frequency.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
			}

			// If an existing item, can save to a copy.
			if (!$isNew && $canDo->get('core.create'))
			{
				ToolbarHelper::custom('frequency.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}

			if (empty($this->item->id))
			{
				ToolbarHelper::cancel('frequency.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				ToolbarHelper::cancel('frequency.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		else
		{
			// If not checked out, can save the item.
			if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
			{
				$toolbar->apply('frequency.apply');
			}

			$saveGroup = $toolbar->dropdownButton('save-group');
			$childBar  = $saveGroup->getChildToolbar();

			if (!$checkedOut && ($canDo->get('core.create')))
			{
				$childBar->save('frequency.save');
			}

			if (!$checkedOut && ($canDo->get('core.create')))
			{
				$childBar->save2new('frequency.save2new');
			}

			if (!$isNew && $canDo->get('core.create'))
			{
				$childBar->save2copy('frequency.save2copy');
			}

			if (empty($this->item->id))
			{
				$toolbar->cancel('frequency.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				$toolbar->cancel('frequency.cancel', 'JTOOLBAR_CLOSE');
			}
		}
	}
}
