<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_System_Tjlms
 * @copyright  Copyright (C) 2005 - 2015. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

defined('_JEXEC') or die;

use MailCatcher\Mailer;

/**
 * MailcatcherControllerDashboard
 *
 * @package     Mailcatcher
 * @subpackage  site
 * @since       2.2
 */
class PlgSystemMailcatcher extends JPlugin
{
	/**
	 * loadChartData
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	public function onAfterInitialise()
	{
		require_once __DIR__ . '/mailer.php';
		$conf       = JFactory::getConfig();
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
		$mailfrom   = JMailHelper::cleanLine($mailfrom);

		if (JMailHelper::isEmailAddress($mailfrom))
		{
			try
			{
				if ($mail->setFrom($mailfrom, JMailHelper::cleanLine($fromname), false) === false)
				{
					JLog::add(__METHOD__ . '() could not set the sender data.', JLog::WARNING, 'mail');
				}
			}
			catch (phpmailerException $e)
			{
				JLog::add(__METHOD__ . '() could not set the sender data.', JLog::WARNING, 'mail');
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

		JFactory::$mailer = $mail;
	}

	/**
	 * loadChartData
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	public function onAfterRender()
	{
		$app = JFactory::getApplication();

		if ($app->input->get('option') == 'com_mailcatcher'
			&& $app->input->get('view') == 'mails'
			&& $app->input->get('layout') == 'message'
			&& $app->input->get('tmpl') == 'component'
			&& ($id = $app->input->getInt('id')))
		{
			\JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_mailcatcher/tables');
			$table = \JTable::getInstance('Mail', 'MailcatcherTable');

			if ($table->load($id) && $table->is_html)
			{
				$app->setBody($table->message);
			}
		}
	}
}
