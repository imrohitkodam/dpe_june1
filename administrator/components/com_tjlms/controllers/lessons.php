<?php
/**
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
jimport('joomla.application.component.controlleradmin');
jimport('joomla.filesystem.folder');
use Joomla\Utilities\ArrayHelper;

/**
 * Lessons list controller class.
 *
 * @since  1.0
 */
class TjlmsControllerLessons extends AdminController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since   1.0
	 */
	public function getModel($name = 'lesson', $prefix = 'TjlmsModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array( 'ignore_request' => true ));

		return $model;
	}

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   1.0
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
	 * Function used to remove associated files
	 *
	 * @return  JSON
	 *
	 * @since  1.0
	 */
	public function removeAssocFiles()
	{
		$input = Factory::getApplication()->input;
		$mediaId = $input->get('media_id', '0', 'INT');
		$lessonId = $input->get('lesson_id', '0', 'INT');
		$model = $this->getModel('lessons');
		$removeAssocFiles = $model->removeAssocFiles($mediaId, $lessonId);
		echo json_encode($removeAssocFiles);
		jexit();
	}

	/**
	 * Method to change the statuses a list of items.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function delete()
	{
		// Check for request forgeries
		\Session::checkToken() or die(\Text::_('JINVALID_TOKEN'));

		if ($this->removeAttemptedLessonsFromList())
		{
			parent::delete();
		}
	}

	/**
	 * Method to change the statuses a list of items.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	private function removeAttemptedLessonsFromList()
	{
		$cid = $this->input->get('cid', array(), 'array');
		$cid = ArrayHelper::toInteger($cid);
		$model     = $this->getModel('managelessons');
		$lessonObj = Tjlms::lesson();

		$usedLessons = $model->getAttemptedLessons($cid);

		if (!empty($usedLessons))
		{
			$result = array_diff($usedLessons, $cid);

			$lessonTitles = $lessonObj->getLessonTitles($usedLessons);
			$this->setMessage(Text::sprintf("COM_TJLMS_LESSONLIST_CANNOT_CHANGE_STATE", implode(",", $lessonTitles)), 'info');

			if (!empty($result))
			{
				$this->input->set('cid', $result);

				return true;
			}
			else
			{
				$this->setRedirect(\Route::_('index.php?option=com_tjlms&view=managelessons', false));

				return false;
			}
		}

		return true;
	}
}
