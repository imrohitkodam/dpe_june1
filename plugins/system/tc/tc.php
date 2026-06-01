<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_System_Tc
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2016-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die( 'Restricted access');

jimport('joomla.filesystem.file');
jimport('joomla.application.application');
jimport('joomla.html.parameter');
jimport('joomla.plugin.plugin');

/**
 * Methods supporting a list of Tjlms action.
 *
 * @since  1.0.0
 */
class PlgSystemTc extends JPlugin
{
	public $app;

	/**
	 * Load plugin language file automatically so that it can be used inside component
	 *
	 * @var    boolean
	 * @since  2.3.4
	 */
	protected $autoloadLanguage = true;
	/**
	 * Method to handle an error condition.
	 *
	 * @param   Error   &$subject  The Error object to be handled.
	 *
	 * @param   Config  $config    The Config object to be handled.
	 *
	 * @since   1.6
	 */
	public function __construct(&$subject, $config)
	{
		$this->app = JFactory::getApplication();

		parent::__construct($subject, $config);
	}

	/**
	 * Function used as a trigger after User login
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */

	public function onAfterRoute()
	{
		if ($this->app->isAdmin())
		{
			return true;
		}

		$user = JFactory::getUser();
		$loggedInUserId = $user->get('id');

		if (!$loggedInUserId)
		{
			return true;
		}

		$uri = JUri::getInstance();
		$url = base64_encode($uri->toString());

		require_once JPATH_ADMINISTRATOR . '/components/com_tc/models/content.php';
		$model = JModelLegacy::getInstance('Content', 'TcModel');

		$input  = JFactory::getApplication()->input;
		$option = $input->get('option', '', 'STRING');
		$view   = $input->get('view', '', 'STRING');
		$tc_id   = $input->get('tc_id', '', 'INT');

		// First get all global TC id's
		$globalTCIdList = $model->getGlobalTCIdList();

		$getGlobalTCAcceptIdList = $model->hasUserAcceptedTC($loggedInUserId, $globalTCIdList);

		if (count($getGlobalTCAcceptIdList))
		{
			for ($i = 0; $i < count($getGlobalTCAcceptIdList); $i++)
			{
				// Check if current url is mapped to this global TC
				$isGlobalTCIdList = $model->getGlobalTCValidationStatus($getGlobalTCAcceptIdList[$i]->tc_id);

				if ($isGlobalTCIdList == '1' && !JFactory::getUser()->get('requireReset', 0))
				{
					// If this is global TC and if this page is not be skipped, redirect to TC page
					if ($option != 'com_tc' && $view != 'content' && $tc_id != $getGlobalTCAcceptIdList[$i]->tc_id)
					{
						$tc_url = 'index.php?option=com_tc&view=content&tc_id=';

						// Redirect to Terms and condtitions view
						$this->app->redirect(JRoute::_(JURI::root() . $tc_url . $getGlobalTCAcceptIdList[$i]->tc_id . '&return=' . $url, false), JText::_('PLG_SYSTEM_TC_TERMS_AND_CONDITIONS_MSG'), 'Notice');
					}
				}
			}
		}

		if ($option && $view && !JFactory::getUser()->get('requireReset', 0))
		{
			$matchingTCIds = $model->getMatchingTCs($option, $view);

			$TCAcceptIdList = $model->hasUserAcceptedTC($loggedInUserId, $matchingTCIds);

			if (count($TCAcceptIdList))
			{
				for ($i = 0; $i < count($TCAcceptIdList); $i++)
				{
					$TCValidationInfo = $model->getTCValidationStatus($TCAcceptIdList[$i]->tc_id);

					if ($TCValidationInfo == '1')
					{
						// Redirect to Terms and condtitions view
						$this->app->redirect(JRoute::_(JURI::root() . 'index.php?option=com_tc&view=content&tc_id=' . $TCAcceptIdList[$i]->tc_id . '&return=' . $url));
					}
				}
			}
		}
	}
}
