<?php
/**
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * Multiagency controller class.
 *
 * @since  1.6
 */
class MultiagencyControllerMultiagencyForm extends FormController
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

		// Get the previous edit id (if any) and the current edit id.
		$previousId = (int) $app->getUserState('com_multiagency.edit.multiagency.id');
		$editId     = $app->input->getInt('id', 0);

		// Set the user id for the user to edit in the session.
		$app->setUserState('com_multiagency.edit.multiagency.id', $editId);

		// Get the model.
		$model = $this->getModel('MultiagencyForm', 'MultiagencyModel');

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

		$app->setUserState('com_multiagency.edit.multiagency.data', null);

		// Redirect to the edit screen.
		$this->setRedirect(Route::_('index.php?option=com_multiagency&view=multiagencyform&layout=edit&id=' . $editId, false));
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
		$model = $this->getModel('MultiagencyForm', 'MultiagencyModel');

		// Get the user data.
		$data = Factory::getApplication()->input->get('jform', array(), 'array');
		$data['com_fields']['school-address'] = strip_tags($data['com_fields']['school-address']);

		// Validate the posted data.
		$form = $model->getForm();

		if (!$form)
		{
			throw new Exception($model->getError(), 500);
		}

		// Validate the posted data.
		$data   = $model->validate($form, $data);

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
			$app->setUserState('com_multiagency.edit.multiagency.data', $jform);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_multiagency.edit.multiagency.id');
			$this->setRedirect(Route::_('index.php?option=com_multiagency&view=multiagencyform&layout=edit&id=' . $id, false));
		}

		$return = false;
		// DPE hack

		if (!empty($data['id']))
		{
			$model->getTable();
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_multiagency/tables');
		   	$tjMultiagency = Table::getInstance('multiagency', 'MultiagencyTable');
		   	$tjMultiagency->load(array('id' => $data['id']));
		   	$leadConsultantId = json_decode($tjMultiagency->lead_consultant_id);
		}

		// Attempt to save the data.
		if (!empty($data))
		{
			$return = $model->save($data);
			// DPE hack

			if ($return && ($leadConsultantId != $data['lead_consultant_id']))
			{
					$multiagencyId = $return;
					PluginHelper::importPlugin("dpe");
  					Factory::getApplication()->triggerEvent('onAfterOrgSlaUpdateLeadstaffMember', array($multiagencyId,$data['lead_consultant_id']));

			}
		}
		else
		{
			return false;
		}

		// Check for errors.
		if ($return === false)
		{
			// Save the data in the session.
			$app->setUserState('com_multiagency.edit.multiagency.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_multiagency.edit.multiagency.id');
			$this->setMessage(Text::sprintf($model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_multiagency&view=multiagencyform&layout=edit&id=' . $id, false));
		}
		else
		{
			$model->checkin($return);

			// Clear the profile id from the session.
			$app->setUserState('com_multiagency.edit.multiagency.id', null);

			// Flush the data from the session.
			$app->setUserState('com_multiagency.edit.multiagency.data', null);

			// Redirect to the list screen.
			$this->setMessage(Text::sprintf('COM_MULTIAGENCY_ITEM_SAVED_SUCCESSFULLY', Text::_('COM_MULTIAGENCY_ORGANISATION')));

			// DPE - Hack - Start -> Redirect to custom DPE school view
			$redirectUrl = $app->input->getString('redirectUrl', '');

			if (!empty($redirectUrl))
			{
				$this->setRedirect($redirectUrl);

				return false;
			}

			// DPE - Hack - End

			$menu = Factory::getApplication()->getMenu();
			$item = $menu->getActive();
			$url  = 'index.php?option=com_multiagency&view=multiagences';
			$this->setRedirect(Route::_($url, false));
		}
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
		$editId = (int) $app->getUserState('com_multiagency.edit.multiagency.id');

		// Get the model.
		$model = $this->getModel('MultiagencyForm', 'MultiagencyModel');

		// Check in the item
		if ($editId)
		{
			$model->checkin($editId);
		}

		$menu = $app->getMenu();
		$item = $menu->getActive();
		$url  = (empty($item->link) ? 'index.php?option=com_multiagency&view=multiagences' : $item->link);
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
		$model = $this->getModel('MultiagencyForm', 'MultiagencyModel');
		$pk    = $app->input->getInt('id');

		if (!$pk)
		{
			$this->setMessage(Text::_('COM_MULTIAGENCY_ITEM_DOESNT_EXIST'), 'warning');
			$this->setRedirect('index.php?option=com_multiagency&view=multiagences');

			return false;
		}

		// Attempt to save the data
		try
		{
			// Get com_tjlms component status
			if (ComponentHelper::getComponent('com_tjlms', true)->enabled)
			{
				$helperPath = JPATH_COMPONENT_SITE . '/helpers/multiagency.php';

				if (! class_exists('MultiagencyFrontendHelpers'))
				{
					// Require_once $path;
					JLoader::register('MultiagencyFrontendHelpers', $helperPath);
					JLoader::load('MultiagencyFrontendHelpers');
				}

				$helperObject = new MultiagencyFrontendHelpers;
				$enrolledCount = $helperObject->getAgencyEnrollment($pk);

				if (($enrolledCount[0]->enrolled) > 0)
				{
					$this->setMessage(Text::sprintf('COM_MULTIAGENCY_ENROLLED_USERS', Text::_('COM_MULTIAGENCY_ORGANISATION')), 'warning');
					$this->setRedirect('index.php?option=com_multiagency&view=multiagences');

					return false;
				}
			}

			$return = $model->delete($pk);

			// Check in the profile
			$model->checkin($return);

			// Clear the profile id from the session.
			$app->setUserState('com_multiagency.edit.multiagency.id', null);

			$menu = $app->getMenu();
			$item = $menu->getActive();

			// Redirect to the list screen
			$this->setMessage(Text::sprintf('COM_MULTIAGENCY_ITEM_DELETED_SUCCESSFULLY', Text::_('COM_MULTIAGENCY_ORGANISATION')));

			$this->setRedirect(Route::_('index.php?option=com_multiagency&view=multiagences', false));

			// Flush the data from the session.
			$app->setUserState('com_multiagency.edit.multiagency.data', null);
		}
		catch (Exception $e)
		{
			$errorType = ($e->getCode() == '404') ? 'error' : 'warning';
			$this->setMessage($e->getMessage(), $errorType);
			$this->setRedirect('index.php?option=com_multiagency&view=multiagences');
		}
	}

	/**
	 * Method to Get region.
	 *
	 * @return bool
	 *
	 * @since 1.6
	 */
	public function getRegions()
	{
		$path = JPATH_SITE . '/components/com_tjfields/helpers/geo.php';

		if (!class_exists('tmtTestsHelper'))
		{
			// Require_once $path
			JLoader::register('TjGeoHelper', $path);
			JLoader::load('TjGeoHelper');
		}

		$tjGeoHelper = TjGeoHelper::getInstance('TjGeoHelper');
		$input = Factory::getApplication()->input;
		$country_id = $input->get('country_id', '0', 'INT');

		$defaultState = array();
		$defaultState['id'] = '';
		$defaultState['region'] = Text::_('COM_MULTIAGENCY_SELECT_REGION');
		$stateList = (array) $tjGeoHelper->getRegionList($country_id);
		$stateList = array_merge(array($defaultState), $stateList);

		echo HTMLHelper::_('select.genericlist', $stateList, 'jform[state_id]', 'class="chzn-done" size="1"', 'id', 'region', '', 'jform_state_id');

		jexit();
	}

	/**
	 * Method to Get region.
	 *
	 * @return bool
	 *
	 * @since 1.6
	 */
	public function getUser()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$user  = Factory::getUser();

		// Check the user can edit this item
		$authorised = $user->authorise('core.adduser', 'com_multiagency')
		|| $user->authorise('core.own.adduser', 'com_multiagency')
		|| $user->authorise('core.own.edituser', 'com_multiagency')
		|| $user->authorise('core.edituser', 'com_multiagency');

		if ($authorised !== true)
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$input = Factory::getApplication()->input;
		$userId = $input->get('userId', '', 'INT');

		if ($userId)
		{
			$juser = Factory::getUser($userId);
			echo json_encode($juser);
			jexit();
		}
	}
}
