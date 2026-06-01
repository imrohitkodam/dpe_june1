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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.controlleradmin');

/**
 * Courses list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerCourses extends AdminController
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JControllerLegacy
	 * @since   1.0.0
	 * @throws  Exception
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->text_prefix = 'COM_TJLMS_COURSES';
		$this->registerTask('unfeatured', 'featured');
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   STRING  $name    model name
	 * @param   STRING  $prefix  model prefix
	 * @param   STRING  $config  model options
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function getModel($name = 'course', $prefix = 'TjlmsModel', $config = Array())
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
		$pks   = $input->post->get('cid', array(), 'array');
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
	 * Function that allows child controller access to model data
	 * after the item has been deleted.
	 *
	 * @param   JModelLegacy  $model  The data model object.
	 * @param   integer       $id     The validated data.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function postDeleteHook(BaseDatabaseModel $model, $id = null)
	{
		// Get the model
		$model     = $this->getModel();
		$ifSuccess = $model->onafterCourseDelete($id);

		return $ifSuccess;
	}

	/**
	 * Method to toggle the featured setting of a list of courses.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function featured()
	{
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app   = Factory::getApplication();
		$user   = Factory::getUser();
		$ids    = $this->input->get('cid', array(), 'array');
		$values = array('featured' => 1, 'unfeatured' => 0);
		$task   = $this->getTask();
		$value  = ArrayHelper::getValue($values, $task, 0, 'int');

		// Access checks.
		foreach ($ids as $i => $id)
		{
			if (!$user->authorise('core.edit.state', 'com_tjlms.course.' . (int) $id))
			{
				// Prune items that you can't change.
				unset($ids[$i]);
				$app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 'error');
            	$app->setHeader('status', 403, true);
			}
		}

		if (empty($ids))
		{
			$app->enqueueMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
            $app->setHeader('status', 500, true);
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Publish the items.
			if (!$model->featured($ids, $value))
			{
				$app->enqueueMessage($model->getError(), 'warning');
            	$app->setHeader('status', 500, true);
			}

			if ($value == 1)
			{
				$message = Text::plural('COM_TJLMS_COURSES_N_ITEMS_FEATURED', count($ids));
			}
			else
			{
				$message = Text::plural('COM_TJLMS_COURSES_N_ITEMS_UNFEATURED', count($ids));
			}
		}

		$this->setRedirect(Route::_('index.php?option=com_tjlms&view=courses', false), $message);
	}
}
