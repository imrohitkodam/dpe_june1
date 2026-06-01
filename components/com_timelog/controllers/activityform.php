<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Session\Session;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

/**
 * Activity controller class.
 *
 * @since  1.0.0
 */
class TimelogControllerActivityForm extends BaseController
{
	/**
	 * Method to check out an item for editing and redirect to the edit form.
	 *
	 * @param   INT  $key     key
	 * @param   INT  $urlVar  urlVar
	 *
	 * @return void
	 *
	 * @since    1.0.0
	 */
	public function edit($key = null, $urlVar = null)
	{
		$app = Factory::getApplication();

		// Get the previous edit id (if any) and the current edit id.
		$previousId = (int) $app->getUserState('com_timelog.edit.activity.id');
		$editId     = $app->input->getInt('id', 0);

		// Set the user id for the user to edit in the session.
		$app->setUserState('com_timelog.edit.activity.id', $editId);

		// Get the model.
		$model = $this->getModel('ActivityForm', 'TimelogModel');

		// Check out the item
		if ($editId)
		{
			$model->checkout($editId);
		}

		// Check in the previous user.
		if ($previousId)
		{
			$model->checkin($previousId);
		}

		// Redirect to the edit screen.
		$this->setRedirect(Route::_('index.php?option=com_timelog&view=activityform&layout=edit', false));
	}

	/**
	 * Method to save a user's profile data.
	 *
	 * @param   INT  $key     key
	 * @param   INT  $urlVar  urlVar
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since  1.0.0
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app   = Factory::getApplication();

		$model = $this->getModel('ActivityForm', 'TimelogModel');

		// Get the user data.
		$data = $app->input->get('jform', array(), 'array');

		// Validate the posted data.
		$form = $model->getForm();

		if (!$form)
		{
			throw new Exception($model->getError(), 500);
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

			$input = $app->input;
			$jform = $input->get('jform', array(), 'ARRAY');

			// Save the data in the session.
			$app->setUserState('com_timelog.edit.activity.data', $jform);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_timelog.edit.activity.id');
			$this->setRedirect(Route::_('index.php?option=com_timelog&view=activityform&layout=edit&id=' . $id, false));

			$this->redirect();
		}

		// Attempt to save the data.
		$return = $model->save($data);

		// Check for errors.
		if ($return === false)
		{
			// Save the data in the session.
			$app->setUserState('com_timelog.edit.activity.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_timelog.edit.activity.id');
			$this->setMessage(Text::sprintf('Save failed', $model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_timelog&view=activityform&layout=edit&id=' . $id, false));
		}

		// Check in the profile.
		if ($return)
		{
			$model->checkin($return);
		}

		// Clear the profile id from the session.
		$app->setUserState('com_timelog.edit.activity.id', null);

		// Redirect to the list screen.
		$this->setMessage(Text::_('COM_TIMELOG_ITEM_SAVED_SUCCESSFULLY'));
		$menu = $app->getMenu();
		$item = $menu->getActive();
		$url  = (empty($item->link) ? 'index.php?option=com_timelog&view=activities' : $item->link);
		$this->setRedirect(Route::_($url, false));

		// Flush the data from the session.
		$app->setUserState('com_timelog.edit.activity.data', null);
	}

	/**
	 * Method to abort current operation
	 *
	 * @param   INT  $key  key
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function cancel($key = null)
	{
		$app = Factory::getApplication();

		// Get the current edit id.
		$editId = (int) $app->getUserState('com_timelog.edit.activity.id');

		// Get the model.
		$model = $this->getModel('ActivityForm', 'TimelogModel');

		// Check in the item
		if ($editId)
		{
			$model->checkin($editId);
		}

		$menu = $app->getMenu();
		$item = $menu->getActive();
		$url  = (empty($item->link) ? 'index.php?option=com_timelog&view=activities' : $item->link);
		$this->setRedirect(Route::_($url, false));
	}

	/**
	 * Method to remove data
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since 1.6
	 */
	public function remove()
	{
		$app   = Factory::getApplication();

		$model = $this->getModel('ActivityForm', 'TimelogModel');
		$pk    = $app->input->getInt('id');

		// Attempt to save the data
		try
		{
			$return = $model->delete($pk);

			// Check in the profile
			$model->checkin($return);

			// Clear the profile id from the session.
			$app->setUserState('com_timelog.edit.activity.id', null);

			$menu = $app->getMenu();
			$item = $menu->getActive();
			$url = (empty($item->link) ? 'index.php?option=com_timelog&view=activities' : $item->link);

			// Redirect to the list screen
			$this->setMessage(Text::_('COM_TIMELOG_ITEM_DELETED_SUCCESSFULLY'));
			$this->setRedirect(Route::_($url, false));

			// Flush the data from the session.
			$app->setUserState('com_timelog.edit.activity.data', null);
		}
		catch (Exception $e)
		{
			$errorType = ($e->getCode() == '404') ? 'error' : 'warning';
			$this->setMessage($e->getMessage(), $errorType);
			$this->setRedirect('index.php?option=com_timelog&view=activities');
		}
	}
}
