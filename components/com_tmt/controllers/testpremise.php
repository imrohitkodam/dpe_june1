<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

require_once JPATH_COMPONENT . '/controller.php';

/**
 * Testpremise controller class.
 *
 * @since  1.0
 */
class TmtControllerTestpremise extends TmtController
{
	/**
	 * Method to start later
	 *
	 * @since  1.0
	 *
	 * @return void
	 */
	public function startLater()
	{
		$tmtFrontendHelper = new tmtFrontendHelper;
		$itemid            = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=mytestinv');
		$redirect          = Route::_('index.php?option=com_tmt&view=mytestinv&Itemid=' . $itemid, false);
		$this->setRedirect($redirect, '');
	}

	/**
	 * Method to start fresh test attempt
	 *
	 * @return   void
	 *
	 * @since  1.0.0
	 */
	public function startfreshTest()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$data = Factory::getApplication()->input->post;

		// Delete the old attempt and answers given
		$model  = $this->getModel('Testpremise', 'TmtModel');
		$result = $model->deleteOldAttempt($data);

		// Launch new attempt
		$this->startTest();
	}

	/**
	 * Method to start  test
	 *
	 * @return   void
	 *
	 * @since  1.0.0
	 */
	public function startTest()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$app = Factory::getApplication();
		$post = $app->input->post;
		$db   = Factory::getDbo();

		$data              = array();
		$data['course_id'] = $courseId = $post->get('course_id', 0, 'int');
		$data['lesson_id'] = $post->get('lesson_id', 0, 'int');
		$data['id']        = $post->get('id', 0, 'int');

		$model         = $this->getModel('Testpremise', 'TmtModel');
		$lessonTrackId = $model->registerAttempt($data);

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/tables');
		$attendee = Table::getInstance('TestAttendees', 'TmtTable', array('dbo', $db));
		$attendee->load(array('invite_id' => $lessonTrackId));
		$testId = $attendee->test_id;

		$tmtFrontendHelper = new tmtFrontendHelper;
		$itemid            = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=test');

		$testlink = 'index.php?option=com_tmt&view=test';
		$redirect = Uri::root() . $testlink . '&id=' . $testId . '&invite_id=' . $lessonTrackId . '&Itemid=' . $itemid . '&cid=' . $courseId;
		$this->setRedirect($redirect);
	}

	/**
	 * Method to reject invite
	 *
	 * @return   void
	 *
	 * @since  1.0.0
	 */
	public function rejectInvite()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$data              = Factory::getApplication()->input->post;
		$model             = $this->getModel('Testpremise', 'TmtModel');
		$rejectInvite      = $model->rejectInvite($data);
		$tmtFrontendHelper = new tmtFrontendHelper;
		$itemid            = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=mytestinv');
		$redirect          = Route::_('index.php?option=com_tmt&view=mytestinv&Itemid=' . $itemid, false);

		$msg = Text::_('COM_TMT_TEST_INVITE_REJECTED');
		$this->setRedirect($redirect, $msg);
	}
}
