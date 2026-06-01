<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Log\Log;

jimport('joomla.application.component.controlleradmin');

jimport('techjoomla.common');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

/**
 * Courses list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerAttemptreport extends AdminController
{
	/**
	 * Function used to change the lesson status
	 *
	 * @return  redirect
	 *
	 * @since   1.0.0
	 */
	public function changeLessonStatus()
	{
		$mainframe         = Factory::getApplication();
		$input             = $mainframe->input;
		$post              = $input->post;
		$attempt_id        = $post->get('attempt_id', '', 'INT');
		$lesson_status     = $post->get('lesson_status', '', 'STRING');
		$usedAsPopupReport = $post->get('usedAsPopupReport', '0', 'INT');

		if (empty($attempt_id) && empty($lesson_status))
		{
			$attemptIds    = $post->get('cid', '', 'ARRAY');
			$lesson_status = $post->get('attempt_status', '', 'STRING');
		}
		else
		{
			$attemptIds = (array) $attempt_id;
		}

		if (!empty($attemptIds))
		{
			foreach ($attemptIds as $attemptId)
			{
				$model = $this->getModel('attemptreport');
				$data  = $model->updateAttemptData($attemptId, '', $lesson_status);

				if ($data)
				{
					// Add a message to the message queue
					$model->updateCouserTrack($attemptId);
					$mainframe->enqueueMessage(Text::_('COM_TJLMS_LESSON_STATUS_CHANGE'));
				}
				else
				{
					// Add a message to the message queue
					$mainframe->enqueueMessage(Text::_('COM_TJLMS_LESSON_STATUS_CHANGE_FAILED'), 'error');
				}

				$additionalParam = '';

				if ($usedAsPopupReport == 1)
				{
					$additionalParam = '&usedAsPopupReport=1&tmpl=component';
				}

				$this->setRedirect(Route::_('index.php?option=com_tjlms&view=attemptreport' . $additionalParam, false));
			}
		}
	}

	/**
	 * Function used to change the attempt data
	 *
	 * @return  json
	 *
	 * @since   1.0.0
	 */
	public function updateAttemptData()
	{
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$input      = Factory::getApplication()->input;
		$post       = $input->post;
		$attempt_id = $post->get('attemptId', '', 'INT');
		$score      = $post->get('score', '', 'INT');

		$data = 0;

		if (!empty($attempt_id))
		{
			$model = $this->getModel('attemptreport');
			$data  = $model->updateAttemptData($attempt_id, $score);
		}

		echo json_encode($data, /** @scrutinizer ignore-type */ true);
		jexit();
	}

	/**
	 * Removes an item.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function delete()
	{
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Get items to remove from the request.
		$cid = Factory::getApplication()->input->get('cid', array(), 'array');

		if (!is_array($cid) || count($cid) < 1)
		{
			Log::add(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel('attemptreport');

			// Make sure the item ids are integers
			jimport('joomla.utilities.arrayhelper');
			/** @scrutinizer ignore-deprecated */ ArrayHelper::toInteger($cid);

			// Remove the items.
			if ($model->delete($cid))
			{
				$this->setMessage(Text::/** @scrutinizer ignore-call */ plural($this->text_prefix . '_N_ITEMS_DELETED', count($cid)));
			}
			else
			{
				$this->setMessage($model->getError(), 'error');
			}
		}

		$this->setRedirect(Route::_('index.php?option=com_tjlms&view=attemptreport', false));
	}

	/**
	 * Archive an item.
	 *
	 * @return  void
	 *
	 * @since   1.4.0
	 */
	public function archiveAttempts()
	{
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Get items to remove from the request.
		$cid = Factory::getApplication()->input->get('cid', array(), 'array');

		if (!is_array($cid) || count($cid) < 1)
		{
			Log::add(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel('attemptreport');

			// Make sure the item ids are integers
			ArrayHelper::toInteger($cid);

			// Remove the items.
			if ($model->archiveAttempts($cid))
			{
				$this->setMessage(Text::/** @scrutinizer ignore-call */ plural($this->text_prefix . '_N_ITEMS_ARCHIVED', count($cid)));
			}
			else
			{
				$this->setMessage($model->getError(), 'error');
			}
		}

		$this->setRedirect(Route::_('index.php?option=com_tjlms&view=attemptreport', false));
	}
}
