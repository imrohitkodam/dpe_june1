<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Uri\Uri;

jimport('techjoomla.tjnotifications.tjnotifications');
jimport('mpdf.mpdf');

require_once JPATH_ROOT . '/libraries/vendor/autoload.php';

/**
 * DPE RsTicketsPro controller
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeControllerUcmbulkdownload extends \Joomla\CMS\MVC\Controller\BaseController
{

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional
	 * @param   array   $config  Configuration array for model. Optional
	 *
	 * @return object	The model
	 *
	 * @since	1.6
	 */
	public function &getModel($name = 'Ucmbulkdownload', $prefix = 'DpeModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}


/**
 * Method to delete a ZIP file based on the record ID.
 * This function retrieves the record ID from the request input and deletes the   corresponding ZIP file from the server and database.
 *
 * @return  array as message
 */
	public function deleteZipFile()
	{

		if (!Session::checkToken('request'))
		{
		    $message['success'] = false;
		    $message['msg'] = 'Invalid Token';
		    echo new JsonResponse($message);
            $app->close();
		}

		$recordId = Factory::getApplication()->input->get('id');
		$app = Factory::getApplication();

		if ($recordId)
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
			$bulkUcmTable = Table::getInstance('Bulkucmreport', 'DpeTable');
			if ($bulkUcmTable->load($recordId)) {
				$bulkUcmTable->state = 0;
			    // Save the record
				if (!$bulkUcmTable->store()) {
					$message['success'] = false;
					$message['msg'] = $bulkUcmTable->getError();
				}else{
					$message['success'] = True;
					$message['msg'] = Text::_('COM_DPE_BULK_FILE_DELETE_SUCCESSFULLY');
				} 
			} else {
			    $message['success'] = false;
			    $message['msg'] = Text::_('COM_TJGOPHISH_NO_RECORD_FOUND');
			}

		}else
		{
			$message['success'] = false;
			$message['msg'] = Text::_('COM_TJGOPHISH_NO_RECORD_FOUND');
		}

		 echo new JsonResponse($message);
        $app->close();
	}
}