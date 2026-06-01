<?php
/**
 * @package     System.Plugins
 * @subpackage  Plugins,system,tjsendemail
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;

JHtml::_('jquery.token');

/**
 * Plugin to send email in a bulk.
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgSystemTjSendemail extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor - Function used as a contructor
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An array that holds the plugin configuration
	 *
	 * @retunr  class object
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);

		Text::script('PLG_SYSTEM_TJSENDEMAIL_BTN');
		Text::script('PLG_SYSTEM_TJSENDEMAIL_SELECT_CHECKBOX');
		Text::script('PLG_SYSTEM_TJSENDEMAIL_POPUP_HEADING');
		Text::script('PLG_SYSTEM_TJSENDEMAIL_POPUP_EMAIL_SUBJECT');
		Text::script('PLG_SYSTEM_TJSENDEMAIL_POPUP_EMAIL_BODY_MESSAGE');
		Text::script('PLG_SYSTEM_TJSENDEMAIL_POPUP_SEND_BTN');
		Text::script('PLG_SYSTEM_TJSENDEMAIL_INVALID_FIELD');

		HTMLHelper::stylesheet('plugins/system/tjsendemail/bulksendemail.min.css');
	}

	/**
	 * Ajax call funcation to send email
	 *
	 * @return  none
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAjaxtjsendemail()
	{
		Session::checkToken('post') or new JResponseJson(null, Text::_('JINVALID_TOKEN_NOTICE'), true);

		// Add logs
		JLog::addLogger(
			array(
				'text_file' => 'tjsendemail.log.php'
			)
		);

		if (!Factory::getUser()->id)
		{
			echo new JResponseJson(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
			jexit();
		}

		$app = Factory::getApplication();
		$config = Factory::getConfig();

		if (!$config->get('mailfrom'))
		{
			echo new JResponseJson(null, Text::_('PLG_SYSTEM_TJSENDEMAIL_ERROR_NO_FROMEMAIL'), true);

			jexit();
		}

		$templateData = $app->input->post->get('template', '', 'array');
		$emails = $app->input->post->get('emails', '', 'array');

		if (empty($emails))
		{
			echo new JResponseJson(null, Text::_('PLG_SYSTEM_TJSENDEMAIL_ADD_RECIPIENTS_OR_CHECK_PREFERENCES'), true);

			jexit();
		}

		try
		{
			// Remove duplicate emails
			$emails = array_unique($emails);

			// The mail subject.
			$emailSubject = $templateData['subject'];

			// The mail body.
			$emailBody = $templateData['message'];

			$successCnt = $failCnt = 0;
			$errorFlag = False;

			foreach ($emails as $singleEmail)
			{
				// Send Email
				$return = Factory::getMailer()->sendMail($config->get('mailfrom'), $config->get('fromname'), trim($singleEmail), $emailSubject, $emailBody, true);

				if ($return !== true)
				{
					$failCnt++;
					JLog::add(Text::sprintf('PLG_SYSTEM_TJSENDEMAIL_FAIL_TO_SENDEMAIL', $singleEmail, $return->get("message")));
				}
				else
				{
					$successCnt++;
					JLog::add(Text::sprintf('PLG_SYSTEM_TJSENDEMAIL_SUCCESSFULLY_SENDEMAIL', $singleEmail));
				}
			}

			if ($failCnt == 0)
			{
				$msg = Text::_('PLG_SYSTEM_TJSENDEMAIL_SUCCESSFULLY_SEND_ALL');
			}
			elseif ($successCnt == 0)
			{
				$msg = Text::_('PLG_SYSTEM_TJSENDEMAIL_FAIL_TO_SENDEMAIL_ALL');
				$errorFlag = true;
			}
			else
			{
				$msg = Text::_('PLG_SYSTEM_TJSENDEMAIL_OUTPUT', $successCnt, $failCnt);
			}

			echo new JResponseJson(null, $msg, $errorFlag);

			jexit();
		}
		catch (Exception $e)
		{
			echo new JResponseJson(null, Text::_('PLG_SYSTEM_TJSENDEMAIL_ERROR'), true);

			jexit();
		}
	}
}
