<?php

/**
 * @package     DPE
 * @subpackage  com_dpe
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



/**
 * School import controller class.
 *
 * @since  1.0.0
 */
class DpeControllerSchoolimport extends FormController
{
	/**
	 * Upload CSV file in chunk
	 *
	 * @return  void
	 *
	 * @since   J5.0.0
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

			/* Save csv content to school table */
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'schoolimport');
			$schoolImport = BaseDatabaseModel::getInstance('SchoolImport', 'DpeModel');
			$schoolImport->upload();
		}
		catch (Exception $e)
		{
			echo new JsonResponse(null, $e->getMessage(), true);
			jexit();
		}
	}

	/**
	 * CSV file data fetch all rows and return to frontend.
	 *
	 * @return  void
	 *
	 * @since   J5.0.0
	 */
	public function importSchools()
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');

		$user = Factory::getUser();

		/* If user is not logged in*/
		if (!$user->id)
		{
			echo new JsonResponse(null, Text::_('COM_USER_MUST_LOGIN_TO_UPLOAD'), true);
			jexit();
		}

		$app               = Factory::getApplication();
		$input             = $app->input;
		$post              = $input->post;
		$notifySchool      = $post->get('notify_school', '', 'INT');
		$cluster_id        = $post->get('cluster_id', 0, 'INT'); // Default to 0 if not provided

		$fileName = $post->get("fileName", '', "string");

		/* Fetch all CSV data and return */
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'schoolimport');
		$csvModel = BaseDatabaseModel::getInstance('SchoolImport', 'DpeModel');
		$result   = $csvModel->getAllCsvData($fileName, $cluster_id);

		echo new JsonResponse($result);
		jexit();
	}
}