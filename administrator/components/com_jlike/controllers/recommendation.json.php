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

JLoader::import('components.com_jlike.includes.jlike', JPATH_SITE);
JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);

/**
 * Recommendation controller
 *
 * @since  __DEPLOY_VERSION__
 */
class JlikeControllerRecommendation extends FormController
{
	/**
	 * Method to complete the record from frontend.
	 *
	 * @return  void|boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function markComplete()
	{
		$app  = Factory::getApplication();
		$user = Factory::getUser();

		if (!Session::checkToken())
		{
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		$todoId = $app->input->getInt('todo_id');
		$status = $app->input->get('status');
		$result = false;

		if ($todoId)
		{
			$data           = array();
			$data['id']     = $todoId;
			$data['status'] = $status;

			// Get Todo Cluster Xref data
			$todoClusterXrefTable = Jlike::table('TodosClusterXref');
			$todoClusterXrefTable->load($todoId);

			// DPE hack for RBACL check
			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				// Get Todo Cluster Xref data
				$todoClusterXrefTable = Jlike::table('TodosClusterXref');
				$todoClusterXrefTable->load($todoId);

				$manageNotification    = RBACL::check($user->id, 'com_cluster', 'core.manageNotificationManager', 'com_jlike', $todoClusterXrefTable->cluster_id);
				$manageOwnNotification = RBACL::check($user->id, 'com_cluster', 'core.own.manageNotifications', 'com_jlike', $todoClusterXrefTable->cluster_id);

				// Get Todo  data
				$todoTable = Jlike::table('Recommendation');
				$todoTable->load($todoId);

				// If user only have manage own access then check user should complete his own record only
				if (!$manageNotification && ($manageOwnNotification && $todoTable->assigned_to != $user->id))
				{
					echo new JsonResponse(null, Text::_("JERROR_ALERTNOAUTHOR"), true);
					$app->close();
				}

				if (!$manageNotification && !$manageOwnNotification)
				{
					echo new JsonResponse(null, Text::_("JERROR_ALERTNOAUTHOR"), true);
					$app->close();
				}
			}

			$recommendationFormModel = Jlike::model('Recommendationform', array('ignore_request' => true));
			$result                  = $recommendationFormModel->save($data);
		}

		if ($result)
		{
			echo new JsonResponse($result, Text::_('COM_JLIKE_ITEM_SAVED_SUCCESSFULLY'), false);
			$app->close();
		}
		else
		{
			echo new JsonResponse(null, Text::_('COM_JLIKE_ITEM_SAVED_FAILED'), true);
			$app->close();
		}
	}
}
