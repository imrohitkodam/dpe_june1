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

use Joomla\Utilities\ArrayHelper;

class MailcatcherControllerMails extends JControllerAdmin
{
	public function getModel($name = 'Mail', $prefix = 'MailcatcherModel', $config = array())
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function resend()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		$cid = ArrayHelper::toInteger($this->input->get('cid', array(), 'array'));
		$this->setRedirect(JRoute::_('index.php?option=com_mailcatcher&view=mails', false));

		if (!$cid)
		{
			$this->redirect();
		}

		$model = $this->getModel();
		$count = 0;

		foreach ($cid as $id)
		{
			if ($model->resend($id))
			{
				$count++;
			}
		}

		$this->setMessage(JText::plural('COM_MAILCATCHER_MAILS_RESENT', $count));
		$this->redirect();
	}
}