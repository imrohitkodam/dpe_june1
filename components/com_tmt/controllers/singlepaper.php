<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;

require_once JPATH_COMPONENT . '/controller.php';

/**
 * SinglePaper controller class.
 *
 * @since       1.0.0
 *
 * @deprecated  1.4.0  This class will be removed no replacements will be provided
 */
class TmtControllerSinglepaper extends TmtController
{
	/**
	 * Method to check out an item for editing and redirect to the edit form.
	 *
	 * @return	void
	 *
	 * @since	1.6
	 *
	 */
	public function edit()
	{
		$data = Factory::getApplication()->input->post;

		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app	= Factory::getApplication();
		$model = $this->getModel('QuestionForm', 'TmtModel');

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
			$errors	= $model->getErrors();

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
			$app->setUserState('com_tmt.edit.question.data', $app->input->get('jform'), array());

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tmt.edit.question.id');
			$this->setRedirect(Route::_('index.php?option=com_tmt&view=questionform&id=' . $id, false));

			return false;
		}

		// Attempt to save the data.
		$return	= $model->save($data);

		// Check for errors.
		if ($return === false)
		{
			// Save the data in the session.
			$app->setUserState('com_tmt.edit.question.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tmt.edit.question.id');
			$this->setMessage(Text::sprintf('Save failed', $model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_tmt&view=questionform&id=' . $id, false));

			return false;
		}

		// Check in the profile.
		if ($return)
		{
			$model->checkin($return);
		}

		// Clear the profile id from the session.
		$app->setUserState('com_tmt.edit.question.id', null);

		$tmtFrontendHelper = new tmtFrontendHelper;
		$itemid = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=questionform');
		$redirect = Route::_('index.php?option=com_tmt&view=questionform&Itemid=' . $itemid, false);
		$msg = Text::_('COM_TMT_MESSAGE_SAVE_QUESTION');
		$this->setRedirect($redirect, $msg);

		// Flush the data from the session.
		$app->setUserState('com_tmt.edit.question.data', null);
	}

	/**
	 * Method to remove
	 *
	 * @return	void
	 *
	 * @since	1.6
	 */
	public function remove()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app	= Factory::getApplication();
		$model = $this->getModel('Singlepaper', 'TmtModel');

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
			$errors	= $model->getErrors();

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
			$app->setUserState('com_tmt.edit.question.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tmt.edit.question.id');
			$this->setRedirect(Route::_('index.php?option=com_tmt&view=question&layout=edit&id=' . $id, false));

			return false;
		}

		// Attempt to save the data.
		$return	= $model->delete($data);

		// Check for errors.
		if ($return === false)
		{
			// Save the data in the session.
			$app->setUserState('com_tmt.edit.question.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tmt.edit.question.id');
			$this->setMessage(Text::sprintf('Delete failed', $model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_tmt&view=question&layout=edit&id=' . $id, false));

			return false;
		}

		// Check in the profile.
		if ($return)
		{
			$model->checkin($return);
		}

		// Clear the profile id from the session.
		$app->setUserState('com_tmt.edit.question.id', null);

		// Redirect to the list screen.
		$this->setMessage(Text::_('COM_TMT_ITEM_DELETED_SUCCESSFULLY'));
		$menu = & JSite::getMenu();
		$item = $menu->getActive();
		$this->setRedirect(Route::_($item->link, false));

		// Flush the data from the session.
		$app->setUserState('com_tmt.edit.question.data', null);
	}

	/**
	 * Method to save a user's profile data.
	 *
	 * @return	void
	 *
	 * @since	1.6
	 */
	public function apply()
	{
		$data = Factory::getApplication()->input->post;

		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app	= Factory::getApplication();
		$model = $this->getModel('Singlepaper', 'TmtModel');

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
			$errors	= $model->getErrors();

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
			$app->setUserState('com_tmt.edit.question.data', $app->input->get('jform'), array());

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tmt.edit.question.id');
			$this->setRedirect(Route::_('index.php?option=com_tmt&view=questionform&id=' . $id, false));

			return false;
		}

		// Attempt to save the data.
		$return	= $model->save($data);

		// Check for errors.
		if ($return === false)
		{
			// Save the data in the session.
			$app->setUserState('com_tmt.edit.question.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tmt.edit.question.id');
			$this->setMessage(Text::sprintf('Save failed', $model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_tmt&view=questionform&id=' . $id, false));

			return false;
		}

		// Check in the profile.
		if ($return)
		{
			$model->checkin($return);
		}

		// Clear the profile id from the session.
		$app->setUserState('com_tmt.edit.question.id', null);

		// Redirect to the edit screen.
		$tmtFrontendHelper = new tmtFrontendHelper;
		$itemid = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=questionform');
		$redirect = Route::_('index.php?option=com_tmt&view=questionform&id=' . $return . '&Itemid=' . $itemid, false);
		$msg = Text::_('COM_TMT_MESSAGE_SAVE_QUESTION');
		$this->setRedirect($redirect, $msg);

		// Flush the data from the session.
		$app->setUserState('com_tmt.edit.question.data', null);
	}

	/**
	 * Method to save a user's profile data.
	 *
	 * @return	void
	 *
	 * @since	1.6
	 */
	public function save()
	{
		$data = Factory::getApplication()->input->post;
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app	= Factory::getApplication();
		$model = $this->getModel('Singlepaper', 'TmtModel');

		// Attempt to save the data.
		$return	= $model->save($data);

		// Check for errors.
		if ($return === false)
		{
			// Save the data in the session.
			$tmtFrontendHelper = new tmtFrontendHelper;
			$itemid = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=checkpaper');
			$this->setMessage(Text::sprintf('Save failed', $model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_tmt&view=checkpaper&Itemid=' . $itemid, false));

			return false;
		}

		// Check in the profile.
		if ($return)
		{
			$model->checkin($return);
		}

		$tmtFrontendHelper = new tmtFrontendHelper;
		$itemid = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=checkpaper');
		$this->setRedirect(Route::_('index.php?option=com_tmt&view=checkpaper&Itemid=' . $itemid, false), Text::_('COM_TMT_SINGLEPAPER_MESSAGE_SAVE'));
	}

	/**
	 * Method to cancel.
	 *
	 * @return	void
	 *
	 * @since	1.6
	 */
	public function cancel()
	{
		$tmtFrontendHelper = new tmtFrontendHelper;
		$itemid = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=questions');
		$redirect = Route::_('index.php?option=com_tmt&view=questions&Itemid=' . $itemid, false);
		$msg = Text::_('COM_TMT_MESSAGE_CANCEL');
		$this->setRedirect($redirect, $msg);
	}
}
