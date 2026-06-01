<?php
/**
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2023 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

/**
 * Countries list controller class.
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsControllerConditions extends AdminController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The name of the model.
	 * @param   string  $prefix  The prefix for the PHP class name.
	 * @param   array   $config  A named array of configuration variables.
	 *
	 * @return  JModel
	 *
	 * @since   1.6
	 */
	public function getModel($name = 'Condition', $prefix = 'TjfieldsModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

	/**
	 * Method to publish records.
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function publish()
	{
		$client = Factory::getApplication()->input->get('client', '', 'STRING');
		$cid = Factory::getApplication()->input->get('cid', array(), 'array');
		$data = array(
			'publish' => 1,
			'unpublish' => 0
		);

		$task = $this->getTask();
		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		// Get some variables from the request
		if (empty($cid))
		{
			Log::add(Text::_('COM_TJFIELDS_NO_CONDITIONS_SELECTED'), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			ArrayHelper::toInteger($cid);

			// Publish the items.
			try
			{
				$model->publish($cid, $value);

				if ($value === 1)
				{
					$ntext = 'COM_TJFIELDS_N_CONDITIONS_PUBLISHED';
				}
				elseif ($value === 0)
				{
					$ntext = 'COM_TJFIELDS_N_CONDITIONS_UNPUBLISHED';
				}
				
				// Generate xml here
				$TjfieldsHelper = new TjfieldsHelper;
				$client_form    = explode('.', $client);
				$client_type    = $client_form[1];

				$data = array();
				$data['client'] = $client;
				$data['client_type'] = $client_type;
				$TjfieldsHelper->generateXml($data);

				$this->setMessage(Text::plural($ntext, count($cid)));
			}
			catch (Exception $e)
			{
				$this->setMessage($e->getMessage(), 'error');
			}
		}

		$this->setRedirect('index.php?option=com_tjfields&view=conditions&client=' . $client);
	}
	
	public function delete()
	{
		//GET CLIENT AND CLIENT TYPE
		$app         = Factory::getApplication();
		$input       = $app->input;
		$client      = $input->get('client','','STRING');
		$client_form = explode('.',$client);
		$client_type = $client_form[1];

		// Get items to remove from the request.
		$cid = $app->input->get('cid', array(), 'array');

		if (!is_array($cid) || count($cid) < 1)
		{
			Log::add(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			ArrayHelper::toInteger($cid);

			// Remove the items.
			if ($model->delete($cid))
			{
				$TjfieldsHelper      = new TjfieldsHelper();
				$data                = array();
				$data['client']      = $client;
				$data['client_type'] = $client_type;
				$TjfieldsHelper->generateXml($data);
				$ntext = $this->text_prefix . '_N_ITEMS_DELETED';
			}
			else
			{
				$this->setMessage($model->getError());
			}
		}

		$this->setMessage(Text::plural($ntext, count($cid)));
		$this->setRedirect('index.php?option=com_tjfields&view=conditions&client='.$client, false);
	}
}
