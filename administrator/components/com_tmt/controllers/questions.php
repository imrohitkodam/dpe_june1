<?php
/**
 * @package    Com_Tmt
 * @copyright  Copyright (C) 2009 -2015 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.controlleradmin');

/**
 * Questions controller class.
 *
 * @since  1.0
 */
class TmtControllerQuestions extends AdminController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   Strign  $name    Model Name
	 * @param   Strign  $prefix  Prefix
	 * @param   Strign  $config  Config array
	 *
	 * @return  model name
	 *
	 * @since    1.6
	 */
	public function &getModel($name = 'question', $prefix = 'TmtModel', $config = Array())
	{
		$model = parent::getModel($name, $prefix, array( 'ignore_request' => true));

		return $model;
	}

	/**
	 * Method to redirect to user dashboard
	 *
	 * @since   1.0
	 *
	 * @return void
	 */
	public function backToDashboard()
	{
		// @TODO change the redirect of the view to LMS related views
		$tmtFrontendHelper = new tmtFrontendHelper;
		$itemid            = $tmtFrontendHelper->getItemId('index.php?option=com_subusers&view=userview');
		$redirect          = Route::_('index.php?option=com_subusers&view=userview&Itemid=' . $itemid, false);
		$this->setRedirect($redirect, '');
	}

	/**
	 * Method to redirect to create question form
	 *
	 * @since   1.0
	 *
	 * @return  void
	 */
	/*public function create()
	{
		$tmtFrontendHelper = new tmtFrontendHelper;
		$itemid            = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=question');
		$redirect          = Route::_('index.php?option=com_tmt&view=question&Itemid=' . $itemid, false);
		$this->setRedirect($redirect, '');
	}

	/**
	 * Method to redirect to edit question form
	 *
	 * @since   1.0
	 *
	 * @return  void
	 */
	/*public function edit()
	{
		$input = Factory::getApplication()->input;
		$post = $input->post;

		$cid   = $input->get('cid', '', 'array');
		ArrayHelper::toInteger($cid);

		$redirect = Route::_('index.php?option=com_tmt&view=question&id=' . $cid[0], false);
		$this->setRedirect($redirect, '');
	}*/

	/**
	 * Method to delete selected questions
	 *
	 * @since   1.0
	 *
	 * @return void
	 */
	public function delete()
	{
		$input = Factory::getApplication()->input;
		$app   = Factory::getApplication();

		// Get category ids to delete
		$cid = $input->get('cid', '', 'array');
		ArrayHelper::toInteger($cid);

		// Call model function
		$model        = $this->getModel('questions');
		$successCount = $model->delete($cid);

		// Show success / error message & redirect
		if ($successCount)
		{
			if ($successCount > 1)
			{
				$msg = Text::sprintf(Text::_('COM_TMT_Q_LIST_DELETED'), $successCount);
			}
			else
			{
				$msg = Text::sprintf(Text::_('COM_TMT_Q_LIST_DELETED_1'), $successCount);
			}

			$app->enqueueMessage($msg);
		}
		else
		{
			$msg = Text::_('COM_TMT_Q_LIST_ERROR_DELETE') . '<br/>' . $model->getError();
			$app->enqueueMessage($msg, 'error');
		}

		$tmtFrontendHelper = new tmtFrontendHelper;
		$itemid            = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=questions');
		$redirect          = Route::_('index.php?option=com_tmt&view=questions&Itemid=' . $itemid, false);
		$this->setRedirect($redirect);
	}

	/**
	 * Change state of an item.
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function publish()
	{
		$input = Factory::getApplication()->input;
		$post  = $input->post;

		$cid   = Factory::getApplication()->input->get('cid', array(), 'array');
		$data  = array(
			'publish' => 1,
			'unpublish' => 0,
			'archive' => 2,
			'trash' => -2,
			'report' => -3
		);
		$task  = $this->getTask();
		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		// Get some variables from the request
		if (empty($cid))
		{
			Log::add(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel('questions');

			// Make sure the item ids are integers
			ArrayHelper::toInteger($cid);

			// Publish the items.
			$count = $model->setItemState($cid, $value);

			if ($value == 1)
			{
				$ntext = $this->text_prefix . '_N_ITEMS_PUBLISHED';
			}
			elseif ($value == 0)
			{
				$ntext = $this->text_prefix . '_N_ITEMS_UNPUBLISHED';
			}
			elseif ($value == 2)
			{
				$ntext = $this->text_prefix . '_N_ITEMS_ARCHIVED';
			}
			else
			{
				$ntext = $this->text_prefix . '_N_ITEMS_TRASHED';
			}

			$this->setMessage(Text::plural($ntext, $count));
		}

		$this->setRedirect('index.php?option=com_tmt&view=questions', $msg);
	}

	/**
	 * Add question in tmt_tests_questions.
	 *
	 * @return  $result  integer
	 *
	 * @since  1.0.0
	 */
	public function testQuestions()
	{
		require_once JPATH_SITE . "/administrator/components/com_tmt/models/test.php";
		$input    = Factory::getApplication()->input;

		$question_ids = json_decode($input->get('question_ids', '', 'json'));

		$section_ids = json_decode($input->get('section_ids', '', 'json'));
		$test_id = $input->get('test_id', 0, 'INT');

		$objTmtModelTest = new TmtModelTest;
		$result = $objTmtModelTest->saveTestQuestions($question_ids, $section_ids, $test_id);

		$result = json_encode($result);
		echo $result;
		jexit();
	}

	/**
	 * Function used for saving ordering for lessons
	 *
	 * @return  void
	 *
	 * @since  1.0
	 */
	public function saveSortingForQuestions()
	{
		$input = Factory::getApplication()->input;

		// Get course ID
		$testId = $input->get('test_id', 0, 'INT');

		// Get module ID
		$sectionId     = $input->get('section_id', 0, 'INT');
		$question_Id   = $input->get('question_id', 0, 'INT');
		$sections_data = $input->get('sections_data', array(), 'ARRAY');
		$model = $this->getModel('questions');
		$i = 1;

		foreach ($sections_data as $sectionId => $questionIds)
		{
			foreach ($questionIds as $questionId)
			{
				$model->switchOrderQuestion($questionId, $i++, $testId, $sectionId);
			}
		}

		echo 1;
		jexit();
	}
}
