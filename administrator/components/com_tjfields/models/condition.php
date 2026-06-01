<?php
/**
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2023 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die();
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;


/**
 * Item Model for Condition.
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsModelCondition extends AdminModel
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_TJFIELDS';

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable    A database object
	 */
	public function getTable($type = 'Condition', $prefix = 'TjfieldsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional ordering field.
	 * @param   boolean  $loadData  An optional direction (asc|desc).
	 *
	 * @return  JForm    $form      A JForm object on success, false on failure
	 *
	 * @since   2.2
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tjfields.condition', 'condition', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	$data  The data for the form.
	 *
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_tjfields.edit.condition.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  $item  Object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			$allConditions = new stdClass;
			
			foreach (json_decode($item->condition) as $key => $data)
			{
				$allConditions->$key  = json_decode($data);
			}
			
			$item->condition = $allConditions;
			// Do any procesing on fields here if needed
		}
	
		return $item;
	}

	/**
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param   JTable  $table  A JTable object.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');

		if (empty($table->id))
		{
			// Set ordering to the last item if not set
			if (@$table->ordering === '')
			{
				$db = Factory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM #__tjfields_fields_conditions');
				$max = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return   mixed		The user id on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function save($data)
	{
		$id = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('condition.id');

		$user = Factory::getUser();
		$app = Factory::getApplication();
		$client = $app->input->get('client', '', 'STRING');

		$table = $this->getTable();
		
		$conditions = array();
		
		foreach ($data['condition'] as $key => $item)
		{
			$conditions[$key] = json_encode($item);
		}
		
		// DPE Hack to store class for radio as btn-group is create conflict for radio field  if condition applied .
		foreach ($conditions as $key => $condition)
		{	
			$fieldType[$key] = json_decode($condition);

			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
			$fieldTable = Table::getInstance('field', 'TjfieldsTable');
			$fieldTable->load((int) $fieldType[$key]->field_on_show);
			
			$tagHelper = new TagsHelper();
            $tags = $tagHelper->getTagIds($fieldType[$key]->field_on_show, "com_tjfields.field");  

			if ($fieldTable->type == 'radio')
			{ 

				if (!empty($fieldTable->validation_class) || $fieldTable->validation_class == trim($fieldTable->validation_class) || str_contains($fieldTable->validation_class, ' ')) 
				{
				   $classes = explode(" ", $fieldTable->validation_class);

				   foreach($classes as $iterator => $class)
				   {
					   	if ($class == 'btn-group')
					   	{
					   		$classes[$iterator] = 'tj_btn_group';
					   	}
				   }

				    $validClass = implode(' ', $classes);
				    $fieldTable->validation_class = $validClass;
				    $updatData = array('id'=> $fieldType[$key]->field_on_show, 'validation_class'=>$fieldTable->validation_class);
				    $fieldTable->newTags = array($tags);
				    $fieldTable->save($updatData);

				}
			}
		}


		$data['condition'] = json_encode($conditions);
		$data['type_id'] = (INT) $app->input->get('client', 'INT');// DPE Hack
		$data['client'] = $app->input->get('client', '');
		$data['field_on_show'] = empty($data['field_on_show'])?0:$data['field_on_show'];// DPE Hack


		// Bind data
		if (!$table->bind($data))
		{
			$this->setError($table->getError());

			return false;
		}

		// Validate Condition codes to check for duplication
		if (!$table->check())
		{
			$this->setError($table->getError());

			return false;
		}

		// Attempt to save data
		if (parent::save($data))
		{
			// Generate xml here
			$TjfieldsHelper = new TjfieldsHelper;
			$client_form = explode('.', $client);
			$client_type = $client_form[1];

			$dataOfClient = array();
			$dataOfClient['client'] = $client;
			$dataOfClient['client_type'] = $client_type;
			$TjfieldsHelper->generateXml($dataOfClient);

			// End xml
		
			return true;
		}

		return false;
	}
}
