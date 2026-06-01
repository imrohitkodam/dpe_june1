<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tc
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2016-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri;

jimport('joomla.application.component.controllerform');

/**
 * Content controller class.
 *
 * @since  1.6
 */
class TcControllerContent extends JControllerForm
{
	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->view_list = 'contents';
		parent::__construct();
	}

	/**
	 * Method to check valid TC based on client & version values via AJAX.
	 *
	 * @return void
	 *
	 * @since  3.0
	 */
	public function checkDuplicateAndLatestVersionTC()
	{
		$app = Factory::getApplication();

		// Get value
		$tcVersion = $app->input->post->getFloat('tcVersion', 0.0);
		$tcClient = $app->input->post->get('tcClient', '', 'STRING');

		// Get the model.
		$model = $this->getModel('content', 'TcModel');
		$getMaxTCVersion = $model->checkDuplicateAndLatestVersionTC($tcVersion, $tcClient);

		if ($getMaxTCVersion == 'newVersion')
		{
			// If T&C is first new version[new TC]
			echo 0;
		}
		else
		{
			echo $getMaxTCVersion;
		}

		jexit();
	}

	/**
	 * Function to save field data
	 *
	 * @param   string  $key     key
	 * @param   string  $urlVar  urlVar
	 *
	 * @return  boolean|void
	 */
	public function save($key = null, $urlVar = 'tc_id')
	{
		// Initialise variables.
		$app   = Factory::getApplication();
		$input = $app->input;
		$task = $input->get('task');
		$data = $input->post->get('jform', array(), 'array');
		$model = $this->getModel('content');
		$form = $model->getForm($data, false);
		$table = $model->getTable();
		$checkin = property_exists($table, $table->getColumnAlias('checked_out'));

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

		$recordId = $this->input->getInt($urlVar);

		// Populate the row id from the session.
		$data[$key] = $recordId;

		if (!$form)
		{
			$app->enqueueMessage($model->getError(), 'error');

			return false;
		}

		// Validate the posted data.
		$validData = $model->validate($form, $data);

		// Check for errors.
		if ($validData === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					$app->enqueueMessage($errors[$i]->getMessage(), 'error');
				}
				else
				{
					$app->enqueueMessage($errors[$i], 'error');
				}
			}

			// Save the data in the session.
			$app->setUserState('com_tc.edit.content.data', $data);

			// Redirect back to the edit screen
			$this->setRedirect(Route::_('index.php?option=com_tc&view=content' . $this->getRedirectToItemAppend($recordId, $urlVar), false));

			$this->redirect();
		}

		// Attempt to save the data.
		if (!$model->save($validData))
		{
			// Save the data in the session.
			$app->setUserState('com_tc.data', $data);

			// Redirect back to the edit screen.
			$this->setError(Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()));
			$this->setMessage($this->getError(), 'error');

			$this->setRedirect(Route::_('index.php?option=com_tc&view=content' . $this->getRedirectToItemAppend($recordId, $urlVar)));

			return false;
		}
		// Save succeeded, so check-in the record.
		if ($checkin && $model->checkin($validData['tc_id']) === false)
		{
			// Save the data in the session.
			$app->setUserState('com_tc.edit.content.data', $validData);

			// Check-in failed, so go back to the record and display a notice.
			$this->setError(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()));
			$this->setMessage($this->getError(), 'error');

			$this->setRedirect(Route::_('index.php?option=com_tc&view=content' . $this->getRedirectToItemAppend($recordId, $urlVar)));

			return false;
		}

		$this->setMessage(Text::_('COM_TC_MSG_SUCCESS_SAVE_CONTENT'));

		// Redirect the user and adjust session state based on the chosen task.
		switch ($task)
		{
			case 'apply':
				// Set the record data in the session.
				$recordId = $model->getState('com_tc.edit.content.id');
				$this->holdEditId('com_tc.edit.content', $recordId);
				$app->setUserState('com_tc.edit.content.data', null);

				// Redirect back to the edit screen.
				$this->setRedirect(Route::_('index.php?option=com_tc&view=content&' . $this->getRedirectToItemAppend($recordId, $urlVar), false));

			break;

			case 'save2new':
				// Clear the record id and data from the session.
				$this->releaseEditId('com_tc.edit.content', $recordId);
				$app->setUserState('com_tc.edit.content.data', null);

				// Redirect back to the edit screen.
				$this->setRedirect(Route::_('index.php?option=com_tc&view=content' . $this->getRedirectToItemAppend(null, $urlVar), false));
			break;

			default:
				// Clear the record id and data from the session.
				$this->releaseEditId('com_tc.edit.content', $recordId);
				$app->setUserState('com_tc.edit.content.data', null);

				$url = 'index.php?option=com_tc&view=contents' . $this->getRedirectToListAppend();

				// Check if there is a return value
				$return = $this->input->get('return', null, 'base64');

				if (!is_null($return) && Uri::isInternal(base64_decode($return)))
				{
					$url = base64_decode($return);
				}

				// Redirect to the list screen.
				$this->setRedirect(Route::_($url, false));

			break;
		}
	}
}
