<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_System_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;

jimport('joomla.filesystem.file');
jimport('joomla.html.parameter');
jimport('joomla.plugin.plugin');
jimport('joomla.application.component.helper');


// Load language file for plugin.
$lang = Factory::getLanguage();
$lang->load('plg_system_mailchimp', JPATH_ADMINISTRATOR);
$lang->load('com_tjlms', JPATH_SITE);

/**
 * Methods supporting a list of Tjlms action.
 *
 * @since  1.0.0
 */
class PlgSystemMailchimp extends CMSPlugin
{
	/**
	 * Constructor - Function used as a contructor
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An array that holds the plugin configuration
	 *
	 * @retunr  class object
	 *
	 * @since  1.0.0
	 */
	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);

		$this->comtjlmsHelperObj = '';
		$this->path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';
		$this->mailPath = JPATH_SITE . '/components/com_tjlms/helpers/mailcontent.php';

		if (File::exists($this->path))
		{
			if (!class_exists('comtjlmsHelper'))
			{
				JLoader::register('comtjlmsHelper', $this->path);
				JLoader::load('comtjlmsHelper');
			}

			if (!class_exists('TjlmsMailcontentHelper'))
			{
				JLoader::register('TjlmsMailcontentHelper', $this->mailPath);
				JLoader::load('TjlmsMailcontentHelper');
			}

			$this->comtjlmsHelperObj = new comtjlmsHelper;

			$this->TjlmsMailcontentHelper = new TjlmsMailcontentHelper;

			$this->params = ComponentHelper::getParams('com_tjlms');
		}

		$this->logparams = array();
		$this->logparams['filepath'] = JPATH_PLUGINS . '/system/mailchimp/mailchimp';
		$this->logparams['filename'] = 'log.php';
		$this->logparams['component'] = 'com_tjlms';
		$this->logparams['userid'] = Factory::getUser()->id;
		$this->logparams['logEntryTitle'] = "Mailchimp integration";
		$this->logparams['desc'] = '';
		$this->logparams['logType'] = Log::INFO;
	}

	/**
	 * Function used as a trigger after user successfully enrolled  for a course.
	 *
	 * @param   INT  $actorId     user has been enrolled
	 * @param   INT  $state       Enrollment state
	 * @param   INT  $courseId    course ID
	 * @param   INT  $enrolledBy  user who enrolled the actor
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterCourseEnrol($actorId, $state, $courseId, $enrolledBy)
	{
		$user = Factory::getUser($actorId);
		$array = explode(' ', $user->name, 2);

		if (JVERSION >= '1.6.0')
		{
			require_once JPATH_SITE . '/plugins/system/mailchimp/lib/Mailchimp.php';
		}
		else
		{
			require_once JPATH_SITE . '/plugins/mailchimp/lib/Mailchimp.php';
		}

		$pluginparams = PluginHelper::getPlugin('system', 'mailchimp');
		$api_key = json_decode($pluginparams->params)->api_key;
		$list_id = json_decode($pluginparams->params)->list_id;
		$COURSE_ID = json_decode($pluginparams->params)->field_tag;
		$application = Factory::getApplication();

		if (isset($api_key) && isset($list_id) && !empty($api_key) && !empty($list_id))
		{
			$Mailchimp = new Mailchimp($api_key);
			$Mailchimp_Lists = new Mailchimp_Lists($Mailchimp);
			$user = Factory::getUser($actorId);

			if (! isset($array[1]))
			{
				$array[1] = $array[0];
			}

			$merge_vars = array('FNAME' => $array[0],'LNAME' => $array[1],$COURSE_ID => $courseId);

			try
			{
				$subscriber = $Mailchimp_Lists->subscribe($list_id, array('email' => htmlentities($user->email)), $merge_vars);

				if ( ! empty( $subscriber['leid'] ) )
				{
					/*$application->enqueueMessage(JText::_('PLG_TJLMS_SYSTEM_MAILCHIMP_SUCCESS_MSG'), 'message');*/
					$this->logparams['desc'] = Text::_('PLG_TJLMS_SYSTEM_MAILCHIMP_SUCCESS_MSG');
					$this->logparams['logType'] = Log::INFO;
					$this->comtjlmsHelperObj->techjoomlaLog($this->logparams['filename'], $this->logparams['filepath'], $this->logparams);
				}
				else
				{
					$this->logparams['desc'] = Text::_('PLG_TJLMS_SYSTEM_MAILCHIMP_ERROR_MSG');
					$this->logparams['logType'] = Log::ERROR;
					$this->comtjlmsHelperObj->techjoomlaLog($this->logparams['filename'], $this->logparams['filepath'], $this->logparams);
					/*$application->enqueueMessage(JText::_('PLG_TJLMS_SYSTEM_MAILCHIMP_ERROR_MSG'), 'error');*/
				}
			}
			catch (Exception $e)
			{
				$this->logparams['desc'] = $e->getMessage();
				$this->logparams['logType'] = Log::ERROR;
				$this->comtjlmsHelperObj->techjoomlaLog($this->logparams['filename'], $this->logparams['filepath'], $this->logparams);

				/*$application->enqueueMessage($e->getMessage(), 'error');*/
			}
		}
		else
		{
			$this->logparams['desc'] = Text::_('PLG_TJLMS_SYSTEM_MAILCHIMP_API_KEY_NOT');
			$this->logparams['logType'] = Log::ERROR;
			$this->comtjlmsHelperObj->techjoomlaLog($this->logparams['filename'], $this->logparams['filepath'], $this->logparams);
			/*$application->enqueueMessage(JText::_('PLG_TJLMS_SYSTEM_MAILCHIMP_API_KEY_NOT'), 'error');*/
		}
	}
}
