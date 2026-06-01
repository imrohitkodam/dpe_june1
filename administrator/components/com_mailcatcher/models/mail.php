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

class MailcatcherModelMail extends JModelAdmin
{
	public function getTable($name = 'Mail', $prefix = 'MailcatcherTable', $options = array())
	{
		return JTable::getInstance($name, $prefix, $options);
	}

	public function getForm($data = array(), $loadData = true)
	{
		return;
	}

	public function resend($id)
	{
		$table = $this->getTable();

		if ($table->load($id))
		{
			$mailer      = JFactory::getMailer();
			$attachments = null;

			if (!empty($table->attachments))
			{
				$attachments = json_decode($table->attachments, true);
				$filePath    = JPATH_ROOT . '/media/com_mailcatcher/assets/attachments/' . $id;

				foreach ($attachments as &$attachment)
				{
					if (is_file($filePath . '/' . $attachment[2]))
					{
						$attachment = $filePath . '/' . $attachment[2];
					}
					else
					{
						unset($attachment);
					}
				}
			}

			$cc        = empty($table->cc) ? null : explode('][', trim($table->cc, '[]'));
			$bcc       = empty($table->bcc) ? null : explode('][', trim($table->bcc, '[]'));
			$receivers = explode('][', trim($table->receivers, '[]'));

			return $mailer->sendMail($table->sent_from_mail, $table->sent_from_name, $receivers, $table->subject, $table->message, (bool) $table->is_html, $cc, $bcc, $attachments);
		}

		return false;
	}
}