<?php
/**
 * @package     TjGoPhish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * TjGoPhish Campaign Controller
 *
 * @since  1.0.0
 */
class TjGoPhishControllerCampaign extends FormController
{
	/**
	 * Implement to allowAdd or not
	 *
	 * @param   array  $data  Data for the form.
	 *
	 * @return bool
	 */
	protected function allowAdd($data = Array())
	{
		return Factory::getUser()->authorise("core.create", "com_tjgophish");
	}

	/**
	 * Implement to allow edit or not
	 *
	 * @param   array  $data  Data for the form.
	 *
	 * @param   INT    $key   Id.
	 *
	 * @return bool
	 */
	protected function allowEdit($data = Array(), $key = 'id')
	{
		return Factory::getUser()->authorise("core.edit", "com_tjgophish");
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

		// Get Logged in User
		$user = Factory::getUser();

		// Initialise variables.
		$app               = Factory::getApplication();
		$menu              = $app->getMenu();
		$campaignsMenuItem = $menu->getItems('link', 'index.php?option=com_tjgophish&view=campaigns', true);
		$campaignMenuItem  = $menu->getItems('link', 'index.php?option=com_tjgophish&view=campaign&layout=edit', true);

		$model = $this->getModel('Campaign', 'TjGoPhishModel');

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

		// Get com_cluster component status
		if (ComponentHelper::getComponent('com_cluster', true)->enabled)
		{
			// Get com_subusers component status
			$subUserExist = ComponentHelper::getComponent('com_subusers', true)->enabled;

			// Check user have permission to edit record of assigned cluster
			if ($subUserExist && ($data['cluster_id']) && !$user->authorise('core.manageall', 'com_cluster'))
			{
				JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

				/*
				 *  @Todo migration for client 'com_cluster' in dpe
				 *  Com_dpe - Hack - start
				 *  Original Code : RBACL::check($user->id, 'com_cluster', 'core.addItem', 'com_tjucm', $clusterId)
				 */

				// Check user has permission for mentioned cluster
				if (!RBACL::check($user->id, 'com_cluster', 'core.createCampaign', 'com_tjgophish', $data['cluster_id']))
				{
					$this->setMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

					$this->setRedirect(Route::_('index.php?option=com_tjgophish&view=campaigns&Itemid=' . $campaignsMenuItem->id, false));

					return false;
				}
			}
		}

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
			$app->setUserState('com_tjgophish.edit.campaign.data', $jform);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tjgophish.edit.campaign.id');
			$this->setRedirect(Route::_('index.php?option=com_tjgophish&view=campaign&layout=edit&id=' . $id, false));

			$this->redirect();
		}

		// Attempt to save the data.
		$return = $model->save($data);

		// Check for errors.
		if ($return === false)
		{
			// Save the data in the session.
			$app->setUserState('com_tjgophish.edit.campaign.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tjgophish.edit.campaign.id');
			$this->setMessage(Text::sprintf('COM_TJGOPHISH_CAMPAIGN_SAVE_FAILED', $model->getError()), 'error');
			$this->setRedirect(Route::_('index.php?option=com_tjgophish&view=campaign&layout=edit&id=' . $id, false));

			return false;
		}

		if ($return)
		{
			$this->setMessage(Text::_('COM_TJGOPHISH_CAMPAIGN_ADDED_SUCCESSFULLY'), 'success');
		}

		$id = (int) $model->getState('campaign.id');

		// Clear the profile id from the session.
		$app->setUserState('com_tjgophish.edit.campaign.id', null);

		$task = $this->getTask();

		// Redirect the user and adjust session state based on the chosen task.
		switch ($task)
		{
			case 'save':
				// Redirect back to the List screen.
				$this->setRedirect(Route::_('index.php?option=com_tjgophish&view=campaigns&Itemid=' . $campaignsMenuItem->id, false));
				break;

			default:

				// Redirect back to the edit screen.
				$this->setRedirect(Route::_('index.php?option=com_tjgophish&view=campaign&layout=edit&id=' . $id . '&Itemid=' . $campaignMenuItem->id, false));
				break;
		}

		// Flush the data from the session.
		$app->setUserState('com_tjgophish.edit.group.data', null);
	}

	/**
	 * Implement to allow edit or not
	 *
	 * @return bool
	 */
	public function conclude()
	{
		$jInput = Factory::getApplication()->input;
		$ccid = $jInput->get('ccid', 0, 'INT');
		$url = Route::_("index.php?option=com_tjgophish&view=campaigns", false);

		if (!empty($ccid))
		{
			$model = $this->getModel();

			if ($model->conclude($ccid))
			{
				$this->setRedirect($url, Text::_('COM_TJGOPHISH_MARK_CAMPAIGN_COMPLETE_MSG'), 'success');
			}
			else
			{
				$this->setRedirect($url, Text::_('COM_TJGOPHISH_UNABLE_TO_COMPLETE_CAMPAIGN'), 'error');
			}
		}
		else
		{
			$this->setRedirect($url, Text::_('COM_TJGOPHISH_UNABLE_TO_COMPLETE_CAMPAIGN'), 'error');
		}
	}

	/**
	 * Export campaings result as CSV
	 *
	 * @return bool
	 */
	public function getCampaignReportAsCSV()
	{
		$input = Factory::getApplication()->input;
		$id = $input->get('id', 0, 'INT');
		$exportas = $input->get('exportas', 'results', 'STRING');

		if (!empty($id))
		{
			$model = $this->getModel('campaignreport');
			$item = $model->getItem($id);

			if ($exportas == 'events')
			{
				$data = $item->report->timeline;
			}
			elseif ($exportas == 'submitted_data')
			{
				$keys = array_keys(array_column($item->report->timeline, 'message'), Text::_('COM_TJGOPHISH_CAMPAIGNS_REPORT_EXPOR_CSV_SUBMITTED_DATA_TEXT'));

				foreach ($keys as $key)
				{
					$data[] = json_decode($item->report->timeline[$key]->details)->payload;
				}
			}
			else
			{
				$data = $item->report->results;
			}

			foreach ($data as $k => $row)
			{
				if ($exportas == 'submitted_data')
				{
					foreach ($row as $key => $arrValue)
					{
						$row->$key = implode(',', $arrValue);
					}

					$data[$k] = (ARRAY) $row;
				}
				else
				{
					$data[$k] = (ARRAY) $row;
				}
			}

			if (count($data) < 1)
			{
				jexit();
			}

			$fileName = preg_replace('/[^A-Za-z0-9\-]/', '', $item->gophish_campaign_title) . '.csv';

			header("Content-Type: text/csv");
			header("Content-Disposition: attachment; filename=$fileName");
			header("Cache-Control: no-cache, no-store, must-revalidate");
			header("Pragma: no-cache");
			header("Expires: 0");

			$output = fopen("php://output", "w");

			$headerKeys = array_keys($data[0]);
			fputcsv($output, $headerKeys);

			foreach ($data as $row)
			{
				fputcsv($output, $row);
			}

			fclose($output);
		}

		jexit();
	}
}
