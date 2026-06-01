<?php

/**
 * @package     Multiagency
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Table\Table;
use Joomla\CMS\Response\JsonResponse;

define('REGISTER_USER_ID', 2);
jimport('joomla.application.component.controllerform');
jimport('joomla.filesystem.folder');
jimport('joomla.html.html');
jimport('joomla.application.component.model');

/**
 * File upload controller class.
 *
 * @since  1.0.0
 */
class MultiagencyControllerUserimport extends FormController
{
	/**
	 * Upload CSV file in chunk
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function uploadCSV()
	{
		try
		{
			$user = Factory::getUser();

			/* If user is not logged in*/
			if (!$user->id)
			{
				echo new JsonResponse(null, Text::_('COM_USER_MUST_LOGIN_TO_UPLOAD'), true);
				jexit();
			}

			$app               = Factory::getApplication();
			$input             = $app->input;
			$client_id         = $input->get('client_id', '', 'INT');

			// Get agency ID using cluster details
			$clusterTable = ClusterFactory::table("Clusters");
			$clusterTable->load(array('id' => $client_id));

			// Check license is available for school
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
			$licenceTable = Table::getInstance('licence', 'MultiagencyTable');
			$licenceTable->load(array('multiagency_id' => $clusterTable->client_id, 'state' => 1));

			if (!$licenceTable->id)
			{
				echo new JsonResponse(null, Text::sprintf('COM_MULTIAGENCY_NO_LICNESE_ADDED', Text::_('COM_MULTIAGENCY_ORGANISATION')), true);
				$app->close();
			}

			$multiagencyParams = ComponentHelper::getParams('com_multiagency');

			if (!$user->authorise('core.admin'))
			{
				$adminRole = $multiagencyParams->get('multyagency_admin_role_id', '0', 'INT');
				$dpeAdmin = RBACL::getRoleByUser($user->get('id'), 'com_multiagency', 0);

				if (!in_array($adminRole, $dpeAdmin))
				{
					$addOwnUser = RBACL::authorise($user->id, 'com_multiagency', 'core.own.adduser', 'com_multiagency', $clusterTable->client_id);
					$addUser = RBACL::authorise($user->id, 'com_multiagency', 'core.adduser', 'com_multiagency', $clusterTable->client_id);

					if ((!$addOwnUser && !$addUser) || !$clusterTable->client_id)
					{
						echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
						jexit();
					}
				}
			}

			/* Save csv content to question table */
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models', 'userimport');
			$userImport = BaseDatabaseModel::getInstance('UserImport', 'MultiagencyModel');
			$result = $userImport->upload();
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * CSV file data store in entroll table of Tjlms.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function importUsers()
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');

		$user = Factory::getUser();

		/* If user is not logged in*/
		if (!$user->id)
		{
			echo new JsonResponse(null, Text::_('COM_USER_MUST_LOGIN_TO_UPLOAD'), true);
			echo json_encode($ret);
			jexit();
		}

		$app               = Factory::getApplication();
		$input             = $app->input;
		$post              = $input->post;
		$notifyUser        = $post->get('notify_user_import', '', 'INT');
		$client_id         = $post->get('client_id', '', 'INT');
		$multiagencyParams = ComponentHelper::getParams('com_multiagency');

		if (!$user->authorise('core.admin'))
		{
			$adminRole = $multiagencyParams->get('multyagency_admin_role_id', '0', 'INT');
			$dpeAdmin = RBACL::getRoleByUser($user->get('id'), 'com_multiagency', 0);

			if (!in_array($adminRole, $dpeAdmin))
			{
				$addOwnUser = RBACL::authorise($user->id, 'com_cluster', 'core.own.adduser', 'com_multiagency', $client_id);
				$addUser = RBACL::authorise($user->id, 'com_cluster', 'core.adduser', 'com_multiagency', $client_id);

				if ((!$addOwnUser && !$addUser) || !$client_id)
				{
					echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
					jexit();
				}
			}
		}

		$msg      = '';
		$return   = 1;
		$fileName = $post->get("fileName", '', "string");

		/* Save csv content to question table */
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models', 'userimport');
		$saveCsv = BaseDatabaseModel::getInstance('UserImport', 'MultiagencyModel');
		$saveCsv->phisingConsent    = $post->get('phising_consent', '0', 'INT');// DPE Hack
		$result  = $saveCsv->saveCsvContent($fileName, $notifyUser, $client_id); // DPE Hack to send the phising consent.

		echo new JsonResponse($result);
		jexit();
	}

	/**
	 * Download CSV log
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function downloadLog()
	{
		$user = Factory::getUser();

		/* If user is not logged in*/
		if (!$user->id)
		{
			echo Text::_("COM_MULTIAGENCY_ERROR_MESSAGE_NOT_AUTHORISED");
			jexit();
		}

		$app               = Factory::getApplication();
		$input             = $app->input;
		$client_id		   = $input->get('client_id', '', 'INT');
		$multiagencyParams = ComponentHelper::getParams('com_multiagency');

		if (!$user->authorise('core.admin'))
		{
			$adminRole = $multiagencyParams->get('multyagency_admin_role_id', '0', 'INT');
			$dpeAdmin = RBACL::getRoleByUser($user->get('id'), 'com_multiagency', 0);

			if (!in_array($adminRole, $dpeAdmin))
			{
				$addOwnUser = RBACL::authorise($user->id, 'com_cluster', 'core.own.adduser', 'com_multiagency', $client_id);
				$addUser = RBACL::authorise($user->id, 'com_cluster', 'core.adduser', 'com_multiagency', $client_id);

				if ((!$addOwnUser && !$addUser) || !$client_id)
				{
					echo Text::_("COM_MULTIAGENCY_ERROR_MESSAGE_NOT_AUTHORISED");
					jexit();
				}
			}
		}

		$fileName = $input->get('fileName', '', 'STRING');
		$helperPath = JPATH_COMPONENT_SITE . '/helpers/multiagency.php';

		if (!class_exists('MultiagencyFrontendHelpers'))
		{
			// Require_once $path;
			JLoader::register('MultiagencyFrontendHelpers', $helperPath);
			JLoader::load('MultiagencyFrontendHelpers');
		}

		$helperObject = new MultiagencyFrontendHelpers;

		$config   = Factory::getConfig();
		$log_path = $config->get('log_path');

		$filePath = $log_path . '/' . $fileName . 'log.php';

		$helperObject->download($filePath);
	}
}
