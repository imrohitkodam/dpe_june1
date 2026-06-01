<?php
/*------------------------------------------------------------------------
  Mail Catcher - Email logging extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   https://www.solidres.com
  @Copyright Copyright (C) 2016 - 2019 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

defined('_JEXEC') or die;

use MailCatcher\Mailer;
use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory as CMSFactory;
use Joomla\CMS\Plugin\CMSPlugin;

class PlgSystemMailcatcher extends CMSPlugin
{
	public function onAfterInitialise()
	{
		// die("jiku biswal");
		require_once __DIR__ . '/mailer.php';
		$conf       = CMSFactory::getConfig();
		$smtpauth   = ($conf->get('smtpauth') == 0) ? null : 1;
		$smtpuser   = $conf->get('smtpuser');
		$smtppass   = $conf->get('smtppass');
		$smtphost   = $conf->get('smtphost');
		$smtpsecure = $conf->get('smtpsecure');
		$smtpport   = $conf->get('smtpport');
		$mailfrom   = $conf->get('mailfrom');
		$fromname   = $conf->get('fromname');
		$mailer     = $conf->get('mailer');
		$mail       = Mailer::getInstance();
		$mailfrom   = MailHelper::cleanLine($mailfrom);

		define('MC_ISJ4', (explode('.', JVERSION)[0] == '4'));
		define('MC_ISJ3', (explode('.', JVERSION)[0] == '3'));

		if (MailHelper::isEmailAddress($mailfrom))
		{
			try
			{
				if ($mail->setFrom($mailfrom, MailHelper::cleanLine($fromname), false) === false)
				{
					Log::add(__METHOD__ . '() could not set the sender data.', Log::WARNING, 'mail');
				}
			}
			catch (phpmailerException $e)
			{
				Log::add(__METHOD__ . '() could not set the sender data.', Log::WARNING, 'mail');
			}
		}

		switch ($mailer)
		{
			case 'smtp':
				$mail->useSmtp($smtpauth, $smtphost, $smtpuser, $smtppass, $smtpsecure, $smtpport);
				break;

			case 'sendmail':
				$mail->isSendmail();
				break;

			default:
				$mail->isMail();
				break;
		}

		CMSFactory::$mailer = $mail;
	}

	public function onAfterRender()
	{
		$app = CMSFactory::getApplication();

		if ($app->input->get('option') == 'com_mailcatcher'
			&& $app->input->get('view') == 'mails'
			&& $app->input->get('layout') == 'message'
			&& $app->input->get('tmpl') == 'component'
			&& ($id = $app->input->getInt('id')))
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_mailcatcher/tables');
			$table = Table::getInstance('Mail', 'MailcatcherTable');

			if ($table->load($id))
			{
				if ($table->is_html)
				{
					$body = $table->message;
				}
				else
				{
					$body = preg_replace('#<body[^>]*>.*</body>#mis', '<body><div class="container">' . $table->message . '</div></body>', $app->getBody());

				}

				$app->setBody($body);
				$table->set('unread', 0);
				$table->store();
			}
		}
	}
}