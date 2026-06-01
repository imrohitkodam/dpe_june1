<?php
/*------------------------------------------------------------------------
  Mail Catcher - Email logging extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   https://www.solidres.com
  @Copyright Copyright (C) 2016 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

defined('_JEXEC') or die;

class MailcatcherViewMails extends JViewLegacy
{
	protected $items;
	protected $state;
	protected $pagination;
	public $filterForm;
	public $activeFilters;

	public function display($tpl = null)
	{
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		parent::display($tpl);

		$this->addToolbar();

	}

	protected function addToolbar()
	{
		$user = JFactory::getUser();
		JToolbarHelper::title(JText::_('COM_MAILCATCHER_TOOLBAR_MAILS'));
		JToolbarHelper::link('index.php?option=com_mailcatcher', 'COM_MAILCATCHER_DASHBOARD');

		if ($user->authorise('core.delete', 'com_mailcatcher'))
		{
			JToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'mails.delete');
		}

		if ($user->authorise('core.admin', 'com_mailcatcher'))
		{
			JToolbarHelper::custom('mails.resend', 'mail', '', 'COM_MAILCATCHER_RESEND');
			JToolbarHelper::preferences('com_mailcatcher');
		}
	}
}