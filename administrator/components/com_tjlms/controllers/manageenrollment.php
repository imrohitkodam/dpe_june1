<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2020 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\MVC\Controller\FormController;

/**
 * Manageenrollment controller class.
 *
 * @since  1.5.0
 */
class TjlmsControllerManageenrollment extends FormController
{
	/**
	 * Constructor.
	 *
	 * @see     JControllerLegacy
	 *
	 * @since   1.5.0
	 *
	 * @throws  Exception
	 */
	public function __construct()
	{
		parent::__construct();

		$this->view_list = 'manageenrollments';
	}

	/**
	 * Method to save enrollment information
	 *
	 * @param   string  $key     TO ADD
	 * @param   string  $urlVar  TO ADD
	 *
	 * @return  boolean  true or false
	 *
	 * @since   1.5.0
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app          = Factory::getApplication();
		$input        = $app->input;
		$model        = $this->getModel('Manageenrollment', 'TjlmsModel');
		$table        = $model->getTable();
		$task         = $input->get('task');
		$enrolledUser = $input->get('user_id');

		// Get the user data.
		$data            = $input->get('jform', array(), 'array');
		$data['user_id'] = $enrolledUser;

		// Validate the posted data.
		$form = $model->getForm();

		if (!$form)
		{
			$app->enqueueMessage($model->getError(), 'error');

			return false;
		}

		// Validate the posted data.
		$data = $model->validate($form, $data);

		// Check for errors.
		if ($data === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

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

			// Save the data in the session.
			// Tweak.
			$app->setUserState('com_tjlms.edit.manageenrollment.data', $data);

			// Tweak *important
			$app->setUserState('com_tjlms.edit.manageenrollment.id', $data['id']);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tjlms.edit.manageenrollment.id');
			$this->setRedirect(Route::_('index.php?option=com_tjlms&view=manageenrollment&layout=edit&id=' . $id, false));

			return false;
		}

		// Determine the name of the primary key for the data.
		if (empty($key))
		{
			$key = $table->getKeyName();
		}

		// To avoid data collisions the urlVar may be different from the primary key.
		if (empty($urlVar))
		{
			$urlVar = $key;
		}

		$recordId = $input->getInt($urlVar);

		// Populate the row id from the session.
		$data[$key] = $recordId;

		/* Attempt to save the data.
		$return = $model->save($data);
		Tweaked. */
		$return = $model->save($data);

		// Check for errors.
		if ($return === false)
		{
			/* Save the data in the session.
			$app->setUserState('com_tjlms.edit.event.data', $data);
			Tweak.*/
			$app->setUserState('com_tjlms.edit.manageenrollment.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tjlms.edit.manageenrollment.id');
			$this->setMessage(Text::sprintf('COM_TJLMS_MANAGEENROLMENT_ERROR_MSG_SAVE', $model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_tjlms&view=manageenrollment&layout=edit&id=' . $id, false));

			return false;
		}

		$msg     = Text::_('COM_TJLMS_MANAGEENROLMENT_CREATED_SUCCESSFULLY');

		$id = $input->get('id');

		if (empty($id))
		{
			$id = $return;
		}

		if ($task == 'apply')
		{
			// Set the record data in the session.
			$this->holdEditId('com_tjlms.edit.manageenrollment', $id);
			$app->setUserState('com_tjlms.edit.manageenrollment.data', null);

			$redirect = Route::_('index.php?option=com_tjlms&&view=manageenrollment&layout=edit&id=' . $id, false);
			$app->enqueueMessage($msg, 'success');
			$app->redirect($redirect);
		}

		// Clear the profile id from the session.
		$app->setUserState('com_tjlms.edit.manageenrollment.id', null);

		// Check in the profile.
		if ($return)
		{
			$model->checkin($return);
		}

		// Redirect to the list screen.
		$redirect = Route::_('index.php?option=com_tjlms&view=manageenrollments', false);
		$app->enqueueMessage($msg, 'success');
		$app->redirect($redirect);

		// Flush the data from the session.
		$app->setUserState('com_tjlms.edit.manageenrollment.data', null);
	}

	/**
	 * Method to cancel an edit.
	 *
	 * @param   string  $key  The name of the primary key of the URL variable.
	 *
	 * @return  boolean  True if access level checks pass, false otherwise.
	 *
	 * @since   1.5.0
	 */
	public function cancel($key = null)
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		parent::cancel();

		$this->setRedirect(
			Route::_(
				'index.php?option=' . $this->option . '&view=' . $this->view_list
				. $this->getRedirectToListAppend(), false
			)
		);

		return true;
	}
}
