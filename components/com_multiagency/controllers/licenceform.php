<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

use Joomla\CMS\Response\JsonResponse;

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
/**
 * Licence controller class.
 *
 * @since  1.6
 */
class MultiagencyControllerLicenceForm extends FormController
{
	/**
	 * Method to check out an item for editing and redirect to the edit form.
	 *
	 * @param   INT  $key     key
	 * @param   INT  $urlVar  urlVar
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	public function edit($key = null, $urlVar = null)
	{
		$app = Factory::getApplication();
		$itemId = $app->getMenu()->getActive()->id;

		// Get the previous edit id (if any) and the current edit id.
		$previousId = (int) $app->getUserState('com_multiagency.edit.licence.id');
		$editId     = $app->input->getInt('id', 0);

		// Set the user id for the user to edit in the session.
		$app->setUserState('com_multiagency.edit.licence.id', $editId);

		// Get the model.
		$model = $this->getModel('LicenceForm', 'MultiagencyModel');

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
// 		$this->setRedirect(Route::_('index.php?option=com_multiagency&view=licenceform&layout=edit', false));
		$this->setRedirect(Route::_('index.php?option=com_multiagency&view=licenceform&layout=edit&id=' . $editId . '&Itemid=' . $itemId, false));
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
	 * @since  1.6
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app   = Factory::getApplication();
		$model = $this->getModel('LicenceForm', 'MultiagencyModel');
		$itemId = $app->getMenu()->getActive()->id;

		// Get the user data.
		$data = Factory::getApplication()->input->get('jform', array(), 'array');

		// Validate the posted data.
		$form = $model->getForm();

		if (!$form)
		{
			throw new Exception($model->getError(), 500);
		}

		if ($data['licence_type'] === 'all')
		{
			$data['course_id'] = 0;
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
			$app->setUserState('com_multiagency.edit.licence.data', $jform);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_multiagency.edit.licence.id');
			$this->setRedirect(Route::_('index.php?option=com_multiagency&view=licenceform&layout=edit&id=' . $id . '&Itemid=' . $itemId, false));

			return false;
		}

		// Attempt to save the data.
		$return = $model->save($data);

		// Check for errors.
		if ($return === false)
		{
			// Save the data in the session.
			$app->setUserState('com_multiagency.edit.licence.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_multiagency.edit.licence.id');
			$this->setMessage(Text::sprintf('Save failed', $model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_multiagency&view=licenceform&layout=edit&id=' . $id . '&Itemid=' . $itemId, false));
		}

		// Redirect back to the list screen.
		$this->setMessage(Text::sprintf('COM_LICENCE_ITEM_SAVED_SUCCESSFULLY', $model->getError()), 'Success');

		// DPE - Hack - Start -> Redirect to custom DPE school view
		$redirectUrl = $app->input->getString('redirectUrl', '');

		if (!empty($redirectUrl))
		{
			$this->setRedirect($redirectUrl);

			return false;
		}

		// DPE - Hack - End

		$this->setRedirect(Route::_('index.php?option=com_multiagency&view=licences&Itemid=' . $itemId, false));
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
		$editId = (int) $app->getUserState('com_multiagency.edit.licence.id');

		// Get the model.
		$model = $this->getModel('LicenceForm', 'MultiagencyModel');

		// Check in the item
		if ($editId)
		{
			$model->checkin($editId);
		}

		$app->setUserState('com_multiagency.edit.licence.data', null);

		$menu = Factory::getApplication()->getMenu();
		$item = $menu->getActive();
		$url  = (empty($item->link) ? 'index.php?option=com_multiagency&view=licences' : $item->link);
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
		$model = $this->getModel('LicenceForm', 'MultiagencyModel');
		$pk    = $app->input->getInt('id');

		// Attempt to save the data
		try
		{
			$return = $model->delete($pk);

			// Check in the profile
			$model->checkin($return);

			// Clear the profile id from the session.
			$app->setUserState('com_multiagency.edit.licence.id', null);

			$menu = $app->getMenu();
			$item = $menu->getActive();
			$url = (empty($item->link) ? 'index.php?option=com_multiagency&view=licences' : $item->link);

			// Redirect to the list screen
			$this->setMessage(Text::_('COM_LICENCE_ITEM_DELETED_SUCCESSFULLY'));
			$this->setRedirect(Route::_($url, false));

			// Flush the data from the session.
			$app->setUserState('com_multiagency.edit.licence.data', null);
		}
		catch (Exception $e)
		{
			$errorType = ($e->getCode() == '404') ? 'error' : 'warning';
			$this->setMessage($e->getMessage(), $errorType);
			$this->setRedirect('index.php?option=com_multiagency&view=licences');
		}
	}

	/**
	 * Method check if licence is present
	 *
	 * @return boolean
	 *
	 * @throws Exception
	 */
	public function checkcourse()
	{
		$app = Factory::getApplication();
		$managerId = $app->input->getInt('userId');
		$courseId = $app->input->getInt('courseId');

		if ($managerId && !is_null($courseId))
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__tjmultiagency_licences'));
			$query->where($db->quoteName('multiagency_id') . '=' . $db->quote($managerId));
			$query->where($db->quoteName('course_id') . '=' . $db->quote($courseId));

			$db->setQuery($query);
			$results = $db->loadObject();

			echo new JsonResponse($results);

			jexit();
		}
	}

	/**
	 * Method check if valid licence and does not allow him to submit form if not
	 *
	 * @return boolean
	 *
	 * @throws Exception
	 */
	public function checkExistingCourse()
	{
		$app = Factory::getApplication();
		$licenceType = $app->input->get('licenceType', '', 'STRING');
		$agencyId = $app->input->getInt('agencyId');
		$courseId = $app->input->getInt('courseId');

		if ($agencyId && $licenceType)
		{
			BaseDatabaseModel::addIncludePath(JPATH_BASE . '/components/com_multiagency/model');
			$model = BaseDatabaseModel::getInstance('LicenceForm', 'MultiagencyModel');

			$courseExists = $model->checkExistingCourse($agencyId, $licenceType, $courseId);

			if (!$courseExists)
			{
				$result->msg = $model->getError();
			}

			echo new JsonResponse($result);
			jexit();
		}
	}

	/**
	 * Method to get not assign courses list
	 *
	 * @return boolean
	 *
	 * @throws Exception
	 */
	public function getNotAssignCourse()
	{
		$app = Factory::getApplication();
		$multiagencyId = $app->input->getInt('multiagencyId');

		$user = Factory::getUser();

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('course_id');
		$query->from($db->quoteName('#__tjmultiagency_licences'));
		$query->where($db->quoteName('multiagency_id') . '=' . $db->quote($multiagencyId));
		$query->where($db->quoteName('created_by') . '=' . $db->quote($user->id));
		$query->where($db->quoteName('state') . '=1');
		$db->setQuery($query);
		$assignCourse = $db->loadColumn();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('c.id, c.title');
		$query->from('`#__tjlms_courses` AS c');
		$query->where('c.state = 1');

		$query->where('c.created_by = "' . $user->id . '" AND c.type = 1');

		if (count($assignCourse) > 0)
		{
			$query->where('c.id' . " NOT IN(" . implode(',', $db->quote($assignCourse)) . ")");
		}

		$query->order($db->escape('c.title ASC'));

		$db->setQuery($query);
		$courses = $db->loadObjectList();

		$options = '<option value="" selected="selected">' . Text::_('COM_TJLMS_COUPON_COURSE') . '</option>';

		foreach ($courses as $course)
		{
			$options .= '<option value="' . $course->id . '">' . $course->title . '</option>';
		}

		echo json_encode($options);
		jexit();
	}
}
