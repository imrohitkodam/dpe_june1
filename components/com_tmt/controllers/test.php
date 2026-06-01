<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tmt
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * TMT is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;

require_once JPATH_COMPONENT . '/controller.php';

/**
 * Test controller class.
 *
 * @since  1.0.0
 */
class TmtControllerTest extends TmtController
{
	/**
	 * function used to sync time
	 *
	 * @return  JSON
	 *
	 * @since  1.0.0
	 */
	public function syncTime()
	{
		$data     = Factory::getApplication()->input->post;
		$model    = $this->getModel('Test', 'TmtModel');
		$syncTime = $model->syncTime($data);

		// Output json response
		header('Content-type: application/json');
		echo json_encode($syncTime);
		jexit();
	}

	/**
	 * Method to save each page data.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function updatetestAttemptStatus()
	{
		$data = Factory::getApplication()->input->post;

		// Get invite id whose status to be updated
		$inviteId = $data->get('invite_id', '', 'INT');

		try
		{
			$model = $this->getModel('Test', 'TmtModel');
			$return = $model->updatetestAttemptStatus($inviteId);
		}
		catch (Exception $e)
		{
			$return = $e->getMessage();
		}

		echo json_encode($return);
		jexit();
	}

	/**
	 * Method to save finale page data.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function saveFinalPageData()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$app = Factory::getApplication();
		$data = $app->input->post->getArray();
		$model      = $this->getModel('Test', 'TmtModel');
		$attendStep = $model->saveStepPageData($data, $isFinal = 1);
		$id = $data['id'];
		$invId = $data['invite_id'];
		$course_id = $data['course_id'];
		$thankYouLink = 'index.php?option=com_tmt&view=test&tmpl=component';

		if (!$attendStep)
		{
			$this->setMessage(Text::_('COM_TMT_TEST_ERROR_SAVING_DATA_MSG'), 'info');
		}

		$this->setRedirect(Route::_($thankYouLink . '&id=' . $id . '&invite_id=' . $invId . '&layout=thankyou&course_id=' . $course_id, false));
	}

	/**
	 * Method to check out an item for editing and redirect to the edit form.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function edit()
	{
		$app = Factory::getApplication();

		// Get the previous edit id (if any) and the current edit id.
		$previousId = (int) $app->getUserState('com_tmt.edit.test.id');
		$editId     = Factory::getApplication()->input->getInt('id', null, 'array');

		// Set the user id for the user to edit in the session.
		$app->setUserState('com_tmt.edit.test.id', $editId);

		// Get the model.
		$model = $this->getModel('Test', 'TmtModel');

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
		$this->setRedirect(Route::_('index.php?option=com_tmt&view=testform&layout=edit', false));
	}

	/**
	 * Method to save a user's profile data.
	 *
	 * @return    void
	 *
	 * @since    1.0.0
	 */
	public function save()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app   = Factory::getApplication();
		$model = $this->getModel('Test', 'TmtModel');

		// Get the user data.
		$data = Factory::getApplication()->input->get('jform', array(), 'array');

		// Validate the posted data.
		$form = $model->getForm();

		if (!$form)
		{

			$app->enqueueMessage($model->getError(), 'warning');
			$app->setHeader('status', 500, true);

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
			$app->setUserState('com_tmt.edit.test.data', $app->input->get('jform'), array());

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tmt.edit.test.id');
			$this->setRedirect(Route::_('index.php?option=com_tmt&view=test&layout=edit&id=' . $id, false));

			return false;
		}

		// Attempt to save the data.
		$return = $model->save($data);

		// Check for errors.
		if ($return === false)
		{
			// Save the data in the session.
			$app->setUserState('com_tmt.edit.test.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tmt.edit.test.id');
			$this->setMessage(Text::sprintf('Save failed', $model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_tmt&view=test&layout=edit&id=' . $id, false));

			return false;
		}

		// Check in the profile.
		if ($return)
		{
			$model->checkin($return);
		}

		// Clear the profile id from the session.
		$app->setUserState('com_tmt.edit.test.id', null);

		// Redirect to the list screen.
		$this->setMessage(Text::_('COM_TMT_ITEM_SAVED_SUCCESSFULLY'));
		$menu =& JSite::getMenu();
		$item = $menu->getActive();
		$this->setRedirect(Route::_($item->link, false));

		// Flush the data from the session.
		$app->setUserState('com_tmt.edit.test.data', null);
	}

	/**
	 * Cancel link
	 *
	 * @return   void
	 *
	 * @since  1.0.0
	 */
	public function cancel()
	{
		$menu =& JSite::getMenu();
		$item = $menu->getActive();
		$this->setRedirect(Route::_($item->link, false));
	}

	/**
	 * Function used remove
	 *
	 * @return   void
	 *
	 * @since  1.0.0
	 */
	public function remove()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app   = Factory::getApplication();
		$model = $this->getModel('Test', 'TmtModel');

		// Get the user data.
		$data = Factory::getApplication()->input->get('jform', array(), 'array');

		// Validate the posted data.
		$form = $model->getForm();

		if (!$form)
		{
			
			$app->enqueueMessage($model->getError(), 'warning');
			$app->setHeader('status', 500, true);

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
			$app->setUserState('com_tmt.edit.test.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tmt.edit.test.id');
			$this->setRedirect(Route::_('index.php?option=com_tmt&view=test&layout=edit&id=' . $id, false));

			return false;
		}

		// Attempt to save the data.
		$return = $model->delete($data);

		// Check for errors.
		if ($return === false)
		{
			// Save the data in the session.
			$app->setUserState('com_tmt.edit.test.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tmt.edit.test.id');
			$this->setMessage(Text::sprintf('Delete failed', $model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_tmt&view=test&layout=edit&id=' . $id, false));

			return false;
		}

		// Check in the profile.
		if ($return)
		{
			$model->checkin($return);
		}

		// Clear the profile id from the session.
		$app->setUserState('com_tmt.edit.test.id', null);

		// Redirect to the list screen.
		$this->setMessage(Text::_('COM_TMT_ITEM_DELETED_SUCCESSFULLY'));
		$menu =& JSite::getMenu();
		$item = $menu->getActive();
		$this->setRedirect(Route::_($item->link, false));

		// Flush the data from the session.
		$app->setUserState('com_tmt.edit.test.data', null);
	}
}
