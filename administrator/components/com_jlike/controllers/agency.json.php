<?php
/**
 * @package     Jlike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * The Tj Jlike controller
 *
 * @since  __DEPLOY_VERSION__
 */
class JlikeControllerAgency extends FormController
{
	protected $comMultiAgency = 'com_multiagency';

	/**
	 * Function to get agency users
	 *
	 * @return  void|boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getAgencyUsers()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		// Get the current user id
		$user = Factory::getuser();

		if (!$user->id)
		{
			return false;
		}

		if (!$user->authorise('core.manage.own.agency.user', $this->comMultiAgency))
		{
			return false;
		}

		$clusterId = $app->input->get('cluster_id', 0, 'INT');
		$model     = $this->getModel();

		if ($clusterId)
		{
			if (!$model->validateUserAgency($clusterId))
			{
				$app->enqueueMessage(Text::_('COM_TJCERTIFICATE_INVALID_ORGANIZATION'), 'error');
				echo new JsonResponse(null, null, true);
				$app->close();
			}
		}

		$userOptions = array();
		$users = $model->getUsers($clusterId);

		if (!empty($users))
		{
			foreach ($users as $user)
			{
				// Dpe Hack trim($user->email)
				$userOptions[] = HTMLHelper::_('select.option', $user->id, $user->name.' ('.$user->email.')');

				// Dpe Hack end 
			}
		}

		echo new JsonResponse($userOptions);
		$app->close();
	}
}
