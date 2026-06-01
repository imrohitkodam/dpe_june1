<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.controlleradmin');

/**
 * Manageenrollments list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerManageenrollments extends AdminController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   STRING  $name    model name
	 * @param   STRING  $prefix  model prefix
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function getModel($name = 'manageenrollments', $prefix = 'TjlmsModel')
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function saveOrderAjax()
	{
		// Get the input
		$input = Factory::getApplication()->input;
		$pks = $input->post->get('cid', array(), 'array');
		$order = $input->post->get('order', array(), 'array');

		// Sanitize the input
		ArrayHelper::toInteger($pks);
		ArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		Factory::getApplication()->close();
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
		$post = $input->post;

		$cid = Factory::getApplication()->input->get('cid', array(), 'array');
		$data = array('publish' => 1, 'unpublish' => 0, 'archive' => 2, 'trash' => -2, 'report' => -3);
		$task = $this->getTask();
		$value = ArrayHelper::getValue($data, $task, 0, 'int');
		$courseId = $input->get('course_id', '', 'INT');
		$courseParam = '';

		if ($courseId)
		{
			$courseParam = '&tmpl=component&course_id=' . $courseId;
		}

		// Get some variables from the request
		if (empty($cid))
		{
			Log::add(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel('manageenrollments');

			// Make sure the item ids are integers
			ArrayHelper::toInteger($cid);

			// Publish the items.
			$model->setItemState($cid, $value, $courseId);

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

			$this->setMessage(Text::plural($ntext, count($cid)));
		}

		$this->setRedirect('index.php?option=com_tjlms&view=manageenrollments' . $courseParam, $msg);
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
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		// Get items to remove from the request.
		$cid = Factory::getApplication()->input->get('cid', array(), 'array');

		if (!is_array($cid) || count($cid) < 1)
		{
			Log::add(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel('manageenrollments');

			// Make sure the item ids are integers
			jimport('joomla.utilities.arrayhelper');
			ArrayHelper::toInteger($cid);

			// Remove the items.
			if ($count = $model->delete($cid))
			{
				$this->setMessage(Text::plural($this->text_prefix . '_N_ITEMS_DELETED', $count));
			}
			else
			{
				$this->setMessage($model->getError());
			}
		}

		$this->setRedirect(Route::_('index.php?option=com_tjlms&view=manageenrollments', false));
	}
}
