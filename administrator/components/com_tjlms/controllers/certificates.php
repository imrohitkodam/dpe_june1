<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Tjlms
 * @author     Parth Lawate <contact@techjoomla.com>
 * @copyright  2016 Parth Lawate
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

jimport('joomla.application.component.controlleradmin');

use Joomla\Utilities\ArrayHelper;

/**
 * Certificates list controller class.
 *
 * @since       1.6
 * @deprecated  1.3.32 Use TJCertificate certificates controller instead
 */
class TjlmsControllerCertificates extends AdminController
{
	/**
	 * Method to clone existing Certificates
	 *
	 * @return void
	 */
	public function duplicate()
	{
		// Check for request forgeries
		Jsession::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Get id(s)
		$pks = $this->input->post->get('cid', array(), 'array');

		try
		{
			if (empty($pks))
			{
				throw new Exception(Text::_('COM_TJLMS_NO_ELEMENT_SELECTED'));
			}

			ArrayHelper::toInteger($pks);
			$model = $this->getModel();
			$model->duplicate($pks);
			$this->setMessage(Jtext::_('COM_TJLMS_ITEMS_SUCCESS_DUPLICATED'));
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		}

		$this->setRedirect('index.php?option=com_tjlms&view=certificates');
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    Optional. Model name
	 * @param   string  $prefix  Optional. Class prefix
	 * @param   array   $config  Optional. Configuration array for model
	 *
	 * @return  object	The Model
	 *
	 * @since    1.6
	 */
	public function getModel($name = 'certificatetemplate', $prefix = 'TjlmsModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   3.0
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
	 * Method to publish a list of items
	 *
	 * @return  MIXED
	 *
	 * @since   12.2
	 */
	public function publish()
	{
		$input = Factory::getApplication()->input;
		$model = $this->getModel('certificates');

		// Get id(s)
		$cid = $input->get('cid', array(), 'array');
		$data = array('publish' => 1, 'unpublish' => 0, 'archive' => 2, 'trash' => -2, 'report' => -3);
		$task = $this->getTask();
		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		if ($value != 1)
		{
			$assignedTmpl = $model->getCourseCertificates($cid);

			if (!empty($assignedTmpl))
			{
				Factory::getApplication()->enqueueMessage(Jtext::_('COM_TJLMS_CERTIFICATE_SHOULD_NOT_PUBLISH'), 'warning');
			}

			$result = array_diff($cid, $assignedTmpl);

			if (!empty($result))
			{
				$input->set('cid', $result);
			}
			else
			{
				$this->setRedirect('index.php?option=com_tjlms&view=certificates');

				return false;
			}
		}

		parent::publish();
	}
}
