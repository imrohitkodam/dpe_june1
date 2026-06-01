<?php
/**
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Form\Form;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

/**
 * Admin Model for an ROP cluster.
 *
 * @since  1.0.0
 */
class DpeModelRopForm extends FormModel
{
	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_dpe.ropform', 'ropform', array('control' => 'jform', 'load_data' => $loadData));

		return empty($form) ? false : $form;
	}
}
