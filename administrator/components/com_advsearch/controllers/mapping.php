<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_Advsearch
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * Class Searchindexer list controller class.
 *
 * @since  3.3
 */
class AdvsearchControllerMapping extends JControllerAdmin
{
	/**
	 * Method proxy for getModel
	 *
	 * @param   string  $name    name
	 * @param   string  $prefix  prefix
	 *
	 * @return  model
	 *
	 * @since   1.6
	 */
	public function getModel($name = 'mapping', $prefix = 'AdvsearchModel')
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
		$jinput = JFactory::getApplication()->input;
		$pks = $jinput->get('cid', array(), 'array');
		$order = $jinput->get('order', array(), 'array');

		// Sanitize the input
		Joomla\Utilities\ArrayHelper::toInteger($pks);
		Joomla\Utilities\ArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		JFactory::getApplication()->close();
	}

	/**
	 * Method to delete.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function delete()
	{
		$model = $this->getModel('mapping');
		$model->deleteMapping();
		$link = JURI::Base() . "index.php?option=com_advsearch&view=mapping";
		$msg = "Search Indexer Removed successfully";
		$this->setRedirect($link, $msg);
	}
}
