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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

JLoader::import('components.com_jlike.includes.jlike', JPATH_SITE);

/**
 * Recommendation form controller
 *
 * @since  __DEPLOY_VERSION__
 */
class JlikeControllerRecommendationForm extends FormController
{
	/**
	 * Method to save a recommendation data.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean|void  Incase of error boolean and in case of success void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		$this->checkToken();
		$app      = Factory::getApplication();
		$user     = Factory::getUser();
		$ownTodo  = false;

		if (!$user->id)
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$data  = $app->input->get('jform', array(), 'array');

		$model = $this->getModel();

		// Validate the posted data.
		$form = $model->getForm($data, false);

		if (!$form)
		{
			throw new \Exception($model->getError(), 500);
		}

		// Make assigned_to_users field false for edit recommendation
		$form->setFieldAttribute('assigned_to_users', 'required', 'false');

		// DPE hack to remove server side validation
		if (!$data['id'])
		{
			$form->setFieldAttribute('assigned_to', 'required', 'false');
		}

		// DPE Hack for check RBACL

		if (ComponentHelper::getComponent('com_cluster', true)->enabled)
		{
			if ($data['id'])
			{
				$todoTable = $model->getTable();
				$todoTable->load(array('id' => $data['id']));

				if ($todoTable->assigned_to == $user->id)
				{
					$ownTodo = true;
				}
			}

			// Get com_subusers component status
			$subUserExist = ComponentHelper::getComponent('com_subusers', true)->enabled;

			// Check user have permission to edit record of assigned cluster
			if ($subUserExist && ($data['clusters']) && !$user->authorise('core.manageall', 'com_cluster'))
			{
				JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

				$manageAllNotifications = true;
				$manageOwnNotifications = true;

				// Check user has permission for admin cluster or staff cluster
				if (!RBACL::check($user->id, 'com_cluster', 'core.manageNotificationManager', 'com_jlike', $data['clusters']))
				{
					$manageAllNotifications = false;
				}

				if (!RBACL::check($user->id, 'com_cluster', 'core.own.manageNotifications', 'com_jlike', $data['clusters']))
				{
					$manageOwnNotifications = false;
				}

				// Throw error if no access to notifications
				if (!$manageAllNotifications && !$manageOwnNotifications)
				{
					$app->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
					$app->setHeader('status', 403, true);

					return;
				}

				// If user is only staff then assigned to logged-in user
				if (!$manageAllNotifications && $manageOwnNotifications)
				{
					$data['assigned_to'] = $user->id;
				}
			}

			// Check if own todo then assigned to logged in user
			if ($ownTodo)
			{
				$data['assigned_to'] = $user->id;
			}
		}


		$validData = $model->validate($form, $data);

		// Check for validation errors.
		if ($validData === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			if (!empty($errors))
			{
				// Push up to three validation messages out to the user.
				for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
				{
					if ($errors[$i] instanceof Exception)
					{
						$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
					}
					else
					{
						$app->enqueueMessage($errors[$i], 'warning');
					}
				}
			}

			// Save the data in the session.
			$app->setUserState('com_jlike.edit.recommendationform.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->input->getInt('id');

			$this->setRedirect(Route::_('index.php?option=com_jlike&view=recommendationform&layout=edit&id=' . $id, false));

			$this->redirect();
		}

		$validData['state']    = 1;
		$validData['cc_users'] = implode(',', (array) $validData['cc_users']); // DPE Hack phptest 8.1

		if (!$validData['id'])
		{
			$validData['status']      = 'I';
			$validData['assigned_by'] = Factory::getUser()->id;
			$validData['context']     = "";
			$validData['type']        = "assign";
		}

		$contentData               = array();
		$contentData['element']    = $data['element'];
		$contentData['element_id'] = $data['element_id'];
		$contentData['url']        = $data['url'];
		$contentData['title']      = $data['content_title'];

		if ($data['content_id'])
		{
			$validData['content_id'] = $data['content_id'];
		}
		else
		{
			$contentFormModel = Jlike::model('Contentform', array('ignore_request' => true));
			$validData['content_id'] = $contentFormModel->getContentID($contentData);
		}

		// DPE Hack to store URL in params
		if ($validData['currentLink'] || $data['todoeditForm'])
		{
			$urlData                      = array();
			$urlData['current_page_link'] = $data['current_page_link'];
			$validData['params']          = json_encode($urlData);
		}

		$result = $model->save($validData);

		// Check for errors.
		if ($result === false)
		{
			// Save the data in the session.
			$app->setUserState('com_jlike.edit.recommendationform.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_jlike.edit.recommendationform.id');
			$this->setMessage(Text::sprintf('COM_JLIKE_RECOMMENDATION_SAVE_FAILED', $model->getError()), 'error');
			$this->setRedirect(Route::_('index.php?option=com_jlike&view=recommendationform&layout=edit&id=' . $id, false));

			return false;
		}

		if ($result)
		{
			// If agency avalable then only store relation
			if ($validData['clusters'])
			{
				// Store cluster id and todo relation
				$todoClusterXrefTable = Jlike::table('TodosClusterXref');
				$obj             = new stdClass;
				$obj->todo_id    = $result;
				$obj->cluster_id = $validData['clusters'];

				$todoClusterXrefTable->save($obj);
			}

			$this->setMessage(Text::_('COM_JLIKE_RECOMMENDATION_SAVE_SUCCESSFULLY'), 'success');

			// Redirect to the list screen.

			if (!empty($app->input->getString('tmpl', '')))
			{
				$this->setRedirect(
					Route::_('index.php?option=com_jlike&tmpl=component&task=recommendationform.edit&id=' . $result, false)
				);
			}
			else
			{
				$this->setRedirect(
					Route::_('index.php?option=com_jlike&view=recommendations', false)
				);
			}
		}

		// Flush the data from the session.
		$app->setUserState('com_jlike.edit.recommendationform.data', null);
	}
}
