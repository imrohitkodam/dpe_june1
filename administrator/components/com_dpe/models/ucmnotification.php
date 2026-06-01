<?php
/**
 * @version    SVN: <svn_id>
 * @package    TJField
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2014-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

/**
 * Tjfields model.
 *
 * @since  2.5
 */
class DpeModelUcmnotification extends AdminModel
{


	/**
	 * Method to get the record form.
	 *
	 * @param   Array    $data      An optional array of data for the form to interogate.
	 * @param   Boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm|boolean  A JForm object on success, false on failure
	 *
	 * @since  1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpe/models/forms');
		$form = $this->loadForm('com_dpe.ucmnotification', 'ucmnotification', array('control' => 'jform','load_data' => $loadData));

		// Load field params in the field form
		if (!empty($data['type']))
		{
			$path = JPATH_SITE . '/administrator/components/com_dpe/models/forms/' . $data['type'] . '.xml';

			// If category XML esists then add global fields XML in current JForm object else create new object of Global Fields
			if (!empty($form) && File::exists($path))
			{
				$form->loadFile($path, true, '/form/*');
			}
		}

		if (!empty($form))
		{
			// Dont allow to save radio/single/multi selects without any options
			if (isset($data['type']))
			{
				if ($data['type'] == 'radio' || $data['type'] == 'single_select' || $data['type'] == 'multi_select' || $data['type'] == 'tjlist')
				{
					$form->setFieldAttribute('fieldoption', 'required', true);
				}
			}
		}

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the table
	 *
	 * @param   string  $type    Name of the Table class
	 * @param   string  $prefix  Optional prefix for the table class name
	 * @param   array   $config  Optional configuration array for Table object
	 *
	 * @return  Table|boolean Table if found, boolean false on failure
	 */
	public function getTable($type = 'NotificationConfigXref', $prefix = 'DpeTable', $config = array())
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');

		return Table::getInstance($type, $prefix, $config);
	}



	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  \JForm|boolean  \JForm object on success, false on error.
	 *
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		$app = Factory::getApplication();
		$input = $app->input;

		// Check the session for previously entered form data.
		$data = $app->getUserState('com_tjfields.edit.field.data', array());
		$id = $input->get('id', '0', 'INT');

		if (!empty($id))
		{
			$data = $this->getItem();
		}

		$this->preprocessData('com_tjfields.field', $data);

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   Integer  $pk  PK
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since  1.6
	 */
	public function getItem($pk = null)
	{
		$input = Factory::getApplication()->input;

		if(!$pk)
		{
			$pk = $input->get('id');
		}

		$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select("*")
			      ->from($db->quoteName('#__dpe_ucm_field_notification_configurations_xref'))
			      ->where($db->quoteName('ucm_field_id') . ' = ' . $db->quote($pk));
			$db->setQuery($query);

			$item = $db->loadObjectList(); 	
				
		return $item;
	}



	/**
	 * Method to get the fields by client id
	 *
	 * @param   String    $clientId     
	 *
	 * @return  Array  of the field lists
	 *
	 * @since  1.6
	 */
	public function getFieldByClient($clientId)
	{

			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select($db->quoteName(['id', 'title']))
			      ->from($db->quoteName('#__tjfields_fields'))
			      ->where($db->quoteName('state') . ' = 1')
			      ->where($db->quoteName('client') . ' = ' . $db->quote($clientId))
			      ->where($db->quoteName('type').' IN ( ' . "'assignee','email','ownership'" . ")")
			      ->order('title ASC');

				$db->setQuery($query);

			return $db->loadObjectList();
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 1.6
	 */
	public function save($data)
	{ 
		if(empty($data))
		{
			return false;
		}

		foreach($data as $saveData)
		{
			$parentId = $saveData['ucm_field_id'];
			$table    = $this->getTable();

			if ($parentId > 0)
			{
				$table->load(array('ucm_field_id'=>$parentId,'ucm_field_option_id'=>$saveData['ucm_field_option_id']));
				$saveData['id'] = $table->id;
			}

			if ($table->save($saveData) === false)
			{
				return false;
			}
		}
		return true;
	}



}