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

use Joomla\Registry\Registry;

class MailcatcherViewDashboard extends JViewLegacy
{
	protected $reportsData;
	protected $manifest;
	protected $totalSent = 0;
	protected $totalFail = 0;
	protected $sentToday = 0;
	protected $sentThisMonth = 0;
	protected $averageDay = 0.00;

	public function display($tpl = null)
	{
		$this->totalSent     = $this->get('TotalSent');
		$this->totalFail     = $this->get('TotalFail');
		$this->sentToday     = $this->get('SentToday');
		$this->sentThisMonth = $this->get('SentThisMonth');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		if ($this->sentThisMonth > 0)
		{
			$this->averageDay = $this->sentThisMonth / (int) JFactory::getDate()->format('t');
		}

		$table = JTable::getInstance('Extension', 'JTable');
		$table->load(JComponentHelper::getComponent('com_mailcatcher')->id);
		$this->manifest = new Registry($table->manifest_cache);

		parent::display($tpl);

		$this->addToolbar();
	}

	protected function addToolbar()
	{
		JToolbarHelper::title(JText::_('COM_MAILCATCHER_DASHBOARD'));
		$user = JFactory::getUser();

		if ($user->authorise('core.admin', 'com_mailcatcher'))
		{
			JToolbarHelper::preferences('com_mailcatcher');
		}

		JToolbarHelper::back('COM_MAILCATCHER_MAILS', JRoute::_('index.php?option=com_mailcatcher&view=mails', false));
	}
}
