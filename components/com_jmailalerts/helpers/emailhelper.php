<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\CMS\Router\Route;

/**
 * class will contain function to store alerts records
 *
 * @package     Com_Jmailaalerts
 * @subpackage  site
 * @since       1.0
 */
class Jmailalertsemailhelper
{
	/**
	 * Function to actually send the content of the mail
	 * It is called by the function processMailAlerts() above and also from the backend model simulate
	 *
	 * @param   object   $userdata      user data
	 * @param   integer  $flag          flag
	 * @param   array    $tmplTags      tags
	 * @param   array    $rememberTags  remember tags
	 *
	 * @since   1.0
	 * @return   null
	 */
	public function getMailcontent($userdata, $flag, $tmplTags, $rememberTags)
	{
		$log = array();
		$jmailalertsModelEmails = new jmailalertsModelEmails;
		$db = Factory::getDBO();
		$helperPath = JPATH_SITE . '/components/com_jmailalerts/models/emails.php';

		if (!class_exists('jmailalertsModelEmails'))
		{
			//  require_once $path;
			JLoader::register('jmailalertsModelEmails', $helperPath);
			JLoader::load('jmailalertsModelEmails');
		}

		$params = ComponentHelper::getParams('com_jmailalerts');
		$today  = Factory::getDate();

		if (!empty($userdata))
		{
			$this->loadComponentLanguageFiles($userdata);

			$app      = Factory::getApplication();
			$frommail = $app->get('mailfrom');
			$site     = $app->get('sitename');

			$messageBody    = stripslashes($userdata->template);
			$messageSubject = stripslashes($userdata->email_subject);
			$emailofuser    = trim($userdata->email_id);

			$userPlug         = $jmailalertsModelEmails->getUserPlugData($userdata->plugins_subscribed_to);
			$finalTriggerTags = $jmailalertsModelEmails->get_final_trigger_tags($tmplTags, $userPlug, $userdata->user_id);

			$count = 0;

			foreach ($finalTriggerTags as $ftt)
			{
				if (isset($ftt['plug_trigger']))
				{
					// $userSubscribedArray[$count][0]=$ftt['plug_trigger'];
					$usa[$count] = $ftt['plug_trigger'];

					// Needed for log
				}

				// $userSubscribedArray[$count][1]=$ftt['tag_to_replace'];
				$count++;
			}

			/*
			if($params->get('enb_debug') && $flag == 2 )
			{
				$this->log[] = Text::sprintf("PRO_FOR",$userdata->name,$userdata->id);
				$plug=implode(',',$usa);
				$this->log[] = Text::sprintf("APPLICABLE_PLUG",$plug);
			}
			*/

			//  If verbose debug is ON
			if ($params->get('enb_debug') && $flag == 2)
			{
				$log[] = "*** " . Text::sprintf("COM_JMAILALERTS_PRO_FOR", $userdata->name, $userdata->user_id);

				if (is_array($usa) && count($usa))
				{
					$plug = implode(', ', $usa);
					$log[] = Text::sprintf("COM_JMAILALERTS_APPLICABLE_PLUG", $plug);

					// @echo Text::sprintf("COM_JMAILALERTS_APPLICABLE_PLUG", $plug);
				}
				else
				{
					$log[] = Text::sprintf("COM_JMAILALERTS_APPLICABLE_PLUG", "No applicable plugin found");

					// @TODO add lang. string

					// @echo Text::sprintf("COM_JMAILALERTS_APPLICABLE_PLUG", "No applicable plugin found"); //@TODO add lang. string
				}
			}

			// $pluginsData = jmailalertsModelEmails::gettriggerPlugins($userdata->id, $userdata->date, $finalTriggerTags, $params->get('enb_latest'));

			if (isset($userdata->respect_last_email_date))
			{
				$respectLastEmailDate = $userdata->respect_last_email_date;
			}
			else
			{
				$respectLastEmailDate = 0;
			}

			$pluginsData = $jmailalertsModelEmails->gettriggerPlugins($userdata->user_id, $userdata->date, $finalTriggerTags, $respectLastEmailDate);


			/*
			foreach ($pluginsData as $pd){
				$pluginsDataName[]=$pd[0];
			}
			*/

			// Rebuild array for tag repalcement in the same order as the plugins were triggered
			$count = 0;
			$userSubscribedArray = array();

			if ($pluginsData)
			{
				foreach ($pluginsData as $pd)
				{
					$pluginsDataName[] = $pd[0];

					if (isset($pd[0]))
					{
						// @plug_trigger
						$userSubscribedArray[$count][0] = $pd[0];
					}

					if (isset($pd[3]))
					{
						// @tag_to_replace
						$userSubscribedArray[$count][1] = $pd[3];
					}

					$count++;
				}
			}

			// DPE hack  can go in core.

			$itemid  = $jmailalertsModelEmails->getItemid();
            $prefUrl = ltrim(Route::link('site', 'index.php?option=com_jmailalerts&Itemid='.$itemid), '/');

			$sitelink     = "<a href = '" . Uri::root() . "'>" . Text:: _("COM_JMAILALERTS_CLICK") . "</a>";
			$prefSitelink = '<a href = "' . Uri::root() .Route::_($prefUrl).'">' . Text::_("COM_JMAILALERTS_CLICK") . '</a>';

			$find    = array('[SITENAME]', '[NAME]', '[SITELINK]', '[PREFRENCES]', '[mailuser]');
			$replace = array($site, $userdata->name, $sitelink, $prefSitelink, $emailofuser);

			$messageBody    = str_replace($find, $replace, $messageBody);
			$messageSubject = str_replace('[SITENAME]', $site, $messageSubject);

			$noMail  = 0;
			$cssdata = '';
			$i       = 0;

			foreach ($userSubscribedArray as $plug)
			{
				if (isset($pluginsData[$i]))
				{
					$messageBody = str_replace($plug[1], $pluginsData[$i][1], $messageBody);
					$cssdata     .= $pluginsData[$i][2];

					if (!($pluginsData[$i][1] == ''))
					{
						$noMail = 1;
					}
				}

				$i++;
			}

			// Replace all tags that are not part of user preferences directly with ''
			// @TODO need to take care of when processing special plugins
			foreach ($rememberTags as $rt)
			{
				$messageBody = str_replace($rt, '', $messageBody);
			}


			// DPE Hack to remove URL from mailto and tel from template if present.
			$mailto = strpos($messageBody, Uri::root().'mailto');
			$tel = strpos($messageBody, Uri::root().'tel');

			if ($mailto)
			{
				$messageBody = str_replace(Uri::root().'mailto', "mailto",$messageBody);
			}
			
			if($tel)
			{
				$messageBody = str_replace(Uri::root().'tel', "tel",$messageBody);
			}

			// Hack end

			$return = array();

			if (!($noMail == 0))
			{
				$cssdata .= $userdata->template_css;

				$commonPluginCssFile = JPATH_SITE . "/components/com_jmailalerts/assets/css/common_plugin.css";
				$cssdata .= file_get_contents($commonPluginCssFile);

				$mailData = $jmailalertsModelEmails->getEmogrify($messageBody, $cssdata);

				// Flag=1 => mail simulation
				if ($flag == 1)
				{
					echo $mailData;
					jexit();
				}

				// Send email
				$mode        = 1;
				$cc          = null;
				$bcc         = null;
				$bcc         = null;
				$attachment  = null;
				$replyto     = null;
				$replytoname = null;

				try
				{
					$status = Factory::getMailer()->sendMail(
						$frommail, $site, $emailofuser, $messageSubject, $mailData,
						$mode, $cc, $bcc, $attachment, $replyto, $replytoname
					);
				}
				catch (Exception $e)
				{
					$errorMessage = $e->getMessage();

					$log[] = $errorMessage . " " . Text::sprintf("COM_JMAILALERTS_MAIL_SEND_FAILED", $emailofuser, $today);
					$status = 4;

					array_push($return, $log, $status);

					return $return;
				}

				// $status = JMail::sendMail($frommail, $site, $emailofuser, $messageSubject, $mailData, true); //2.4

				// Mask email in log?
				$maskEmailInLog = $params->get('mask_email_log', 1);

				if ($maskEmailInLog)
				{
					$emailofuser = $this->maskEmail($emailofuser);
				}

				if (isset($status->code) && $status->code == 500)
				{
					$log[] = $status->message . " " . Text::sprintf("COM_JMAILALERTS_MAIL_SEND_FAILED", $emailofuser, $today);
					$status = 4;

					array_push($return, $log, $status);

					return $return;
				}
				elseif ($status)
				{
					$log[] = Text::sprintf("COM_JMAILALERTS_MAIL_SEND_SUCCESS", $emailofuser, $today);

					//  flag=2 => actual sending of email

					if ($flag == 2)
					{
						$query = "UPDATE `#__jma_subscribers` SET `date` ='"
						. $today . "' WHERE `user_id` = " . $userdata->user_id . " AND alert_id = " . $userdata->alert_id;
						$db->setQuery($query);
						$db->execute();
					}

					$status = 1;
					array_push($return, $log, $status);

					return $return;
				}
			}
			else
			{
				// When there is no content to send in the mail
				// @flag=2 => actual sending of email
				if ($flag == 2)
				{
					$query = "UPDATE `#__jma_subscribers` SET `date` ='"
					. $today . "' WHERE `user_id` = " . $userdata->user_id . " AND alert_id = " . $userdata->alert_id;
					$db->setQuery($query);
					$db->execute();
				}

				$status = 3;
				array_push($return, $log, $status);

				return $return;
			}
		}
	}

	/**
	 * Mask email
	 * This replaces some of the characters from email address with * <br/>Eg. user1@mail.com will be logged as us***@**il.com
	 * https://stackoverflow.com/a/42877897/1143337
	 *
	 * @param   string  $email  Email address
	 *
	 * @return  array
	 */
	public function maskEmail($email)
	{
		$mailParts = explode("@", $email);
		$length    = strlen($mailParts[0]);

		if ($length <= 4 && $length > 1)
		{
			$show = 1;
		}
		else
		{
			$show = floor($length / 2);
		}

		$hide    = $length - $show;
		$replace = str_repeat("*", $hide);

		return substr_replace($mailParts[0], $replace, $show, $hide) . "@" . substr_replace($mailParts[1], "**", 0, 2);
	}

	/**
	 * This method loads language files on basis of user selected language for backend and frontend or default site language configuration
	 *
	 * @param   Object  $userData  User object
	 *
	 * @return  void
	 *
	 * @since   2.6.3
	 */
	public function loadComponentLanguageFiles($userData)
	{
		$app  = Factory::getApplication();
		$lang = Factory::getLanguage();

		if (isset($userData->params) && !empty($userData->params))
		{
			$userParams = new Registry;
			$userParams->loadString($userData->params);
			$userAdminLanguage = $userParams->get('admin_language');
			$userSiteLanguage  = $userParams->get('language');

			if (!empty($userAdminLanguage) && $app->isClient('administrator'))
			{
				$lang->load('com_jmailalerts', JPATH_ADMINISTRATOR, $userAdminLanguage, true);
			}

			if (!empty($userSiteLanguage) && $app->isClient('site'))
			{
				$lang->load('com_jmailalerts', JPATH_SITE, $userSiteLanguage, true);
			}
		}
		else
		{
			$lang->load('com_jmailalerts', JPATH_SITE);
		}
	}
}
