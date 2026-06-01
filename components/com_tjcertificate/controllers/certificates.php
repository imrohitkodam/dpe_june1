<?php
/**
 * @package     TJCertificate
 * @subpackage  com_tjcertificate
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session; // DPE Hack
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;




/**
 * Certificate list controller class.
 *
 * @since  __DEPLOY_VERSION__
 */
class TjCertificateControllerCertificates extends AdminController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   STRING  $name    model name
	 * @param   STRING  $prefix  model prefix
	 *
	 * @return  object  The model.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getModel($name = 'Certificate', $prefix = 'TjCertificateModel', $config = [])
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}

	/**
	 * Method to publish a list of records.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function publish()
	{
		$user = Factory::getUser();

		if (!$user->authorise('certificate.external.manage', 'com_tjcertificate'))
		{
			JError::raiseWarning(403, Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));

			return false;
		}

		$cid = Factory::getApplication()->input->get('cid', array(), 'array');
		$data = array(
			'publish' => 1,
			'unpublish' => 0
		);

		$task = $this->getTask();
		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		// Get some variables from the request
		if (empty($cid))
		{
			throw new Exception(Text::_('COM_TJCERTIFICATE_NO_RECORD_SELECTED'));
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			ArrayHelper::toInteger($cid);

			// Publish the items.
			try
			{
				$model->publish($cid, $value);

				if ($value == 1)
				{
					$ntext = 'COM_TJCERTIFICATE_N_RECORD_PUBLISHED';
				}
				elseif ($value == 0)
				{
					$ntext = 'COM_TJCERTIFICATE_N_RECORD_UNPUBLISHED';
				}

				$this->setMessage(Text::plural($ntext, count($cid)));
			}
			catch (Exception $e)
			{
				$this->setMessage($e->getMessage(), 'error');
			}
		}

		$this->setRedirect('index.php?option=com_tjcertificate&view=certificates&layout=my');
	}

	/**
	 * DPE Hack Can go in core
	 * Fetch certificate data for bulk download.
	 *
	 * Validates the request for CSRF token and user permissions, then applies
	 * filters based on agency, client, and state. Returns matching certificate
	 * records as a JSON response.
	 *
	 * @return void
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function fetchCertificatesForBulkDownload()
	{
		$app = Factory::getApplication();

		// CSRF token check
		if (!Session::checkToken('get')) {
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		// Authorization check
		$user = Factory::getUser();
		if (
			!$user->authorise('certificate.external.manage', 'com_tjcertificate') &&
			!$user->authorise('core.manageall', 'com_cluster')
		) {
			echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
			$app->close();
		}

		// Input retrieval
		$agencyId = $app->input->getInt('agency_id', 0);
		$client   = $app->input->get('client', '', 'STRING');
		$state    = $app->input->get('state', '', 'STRING');

		// Validation checks
		if (empty($client)) {
			echo new JsonResponse(null, Text::_('COM_TJCERTIFICATE_CERTIFICATE_FILTER_CERTIFICATE_TYPE_OR_TYPE_SELECT'), true);
			$app->close();
		}

		if ($client === 'external') {
			echo new JsonResponse(null, Text::_('COM_TJCERTIFICATE_CERTIFICATE_FILTER_CERTIFICATE_CLIENT_IS_EXTERNAL'), true);
			$app->close();
		}

		if ($state !== '') {
			echo new JsonResponse(null, Text::_('COM_TJCERTIFICATE_CERTIFICATE_FILTER_CERTIFICATE_CLIENT_IS_USED_STATE'), true);
			$app->close();
		}

		if ($agencyId === 0) {
			echo new JsonResponse(null, Text::_('COM_TJCERTIFICATE_CERTIFICATE_FILTER_CERTIFICATE_CLIENT_OR_SCHOOL_SELECT'), true);
			$app->close();
		}

		// Load model and set filters
		$model = $this->getModel('Certificates', 'TjCertificateModel', []);
		$model->setState('filter.client', $client);
		$model->setState('filter.agency_id', $agencyId);
		$model->setState('list.limit', 0);

		// Fetch certificate data
		$certificateDatas = $model->getItems();

		// Output as JSON
		echo new JsonResponse($certificateDatas);
		$app->close();
	}


	/**
	 * DPE Hack Can go in core
	 * Function to download bulk certificate
	 * 
	 * @return  Null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function bulkCertificateDownload()
	{
		$app = Factory::getApplication();

		$certificateDatas = $app->input->get('certificates', [], 'array');
		$agencyId         = $app->input->getInt('agency_id', 0);
	
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
		$multiagencyTable = Table::getInstance('multiagency', 'MultiagencyTable');
		$multiagencyTable->load(['id' => $agencyId]);
	
		$folderPath   = JPATH_SITE . '/media/com_tjcertificate/certificates';
		$zipFileName  = $multiagencyTable->title . '.zip';
		$zipFilePath  = $folderPath . '/' . $zipFileName;
	
		$zip = new ZipArchive();
	
		if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true)
		{
			foreach ($certificateDatas as $certificateData)
			{
				$filePath = $folderPath . '/' . $certificateData['unique_certificate_id'] . '.png';
	
				if (file_exists($filePath))
				{
					$zip->addFile($filePath, basename($filePath));
				}
				else
				{
					error_log("Missing file: " . $filePath);
				}
			}
	
			$zip->close();
	
			if (file_exists($zipFilePath))
			{
				if (ob_get_level())
				{
					ob_end_clean();
				}
	
				header('Content-Type: application/zip');
				header('Content-Disposition: attachment; filename="' . basename($zipFilePath) . '"');
				header('Content-Length: ' . filesize($zipFilePath));
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Expires: 0');
				header('Pragma: no-cache');
	
				readfile($zipFilePath);
				unlink($zipFilePath);
			}
			else
			{
				$app->enqueueMessage(Text::_('COM_TJCERTIFICATE_CERTIFICATE_ZIP_FILE_NOT_FOUND'), 'error');
				$this->setRedirect('index.php?option=com_tjcertificate&view=certificates&layout=my');
			}
		}
		else
		{
			$app->enqueueMessage(Text::_('COM_TJCERTIFICATE_CERTIFICATE_ZIP_FILE_CREATION_FAILED'), 'error');
			$this->setRedirect('index.php?option=com_tjcertificate&view=certificates&layout=my');
		}
	}
	
}
