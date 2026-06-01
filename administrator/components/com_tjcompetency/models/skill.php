<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\String\StringHelper;
use \Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

/**
 * Model for Competency skill.
 *
 * @since  1.0.0
 */
class TjCompetencyModelSkill extends AdminModel
{
	/**
	 * Method to get a skill.
	 *
	 * @param   integer  $pk  An optional id of the object to get, otherwise the id from the model state is used.
	 *
	 * @return  mixed    Skill data object on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function getItem($pk = null)
	{
		if ($result = parent::getItem($pk))
		{
			// Prime required properties.
			if (empty($result->id))
			{
				$result->parent_id = $this->getState('skill.parent_id');
			}
		}

		return $result;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm|boolean  A JForm object on success, false on failure
	 *
	 * @since   1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_tjcompetency.skill', 'skill', array('control' => 'jform', 'load_data' => $loadData));

		return empty($form) ? false : $form;
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable    A database object
	 */
	public function getTable($type = 'Skills', $prefix = 'TjCompetencyTable', $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjcompetency/tables');

		return Table::getInstance($type, $prefix, $config);
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
		$data = Factory::getApplication()->getUserState('com_tjcompetency.edit.skill.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function save($data)
	{
		$pk    = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('skill.id');
		$table = $this->getTable();
		$input = Factory::getApplication()->input;
		$isNew = true;

		// Load the row if saving an existing skill.
		if ($pk > 0)
		{
			$table->load($pk);
			$isNew = false;
		}

		// Set the new parent id if parent id not matched OR while New/Save as Copy .
		if ($table->parent_id != $data['parent_id'] || $data['id'] == 0)
		{
			$table->setLocation($data['parent_id'], 'last-child');
		}

		// Alter the title for save as copy
		if ($input->get('task') == 'save2copy')
		{
			$origTable = clone $this->getTable();
			$origTable->load($input->getInt('id'));

			if ($data['title'] == $origTable->title)
			{
				list($title, $alias) = $this->generateNewTitle($data['parent_id'], $data['alias'], $data['title']);
				$data['title'] = $title;
				$data['alias'] = $alias;

				if ($data['unique_code'] == $origTable->unique_code)
				{
					$data['unique_code'] = $this->generateNewUniqueCode($data['unique_code']);
				}
			}
			else
			{
				if ($data['alias'] == $origTable->alias)
				{
					$data['alias'] = '';
				}
			}

			$data['state'] = 0;
		}

		// Bind the data.
		if (!$table->bind($data))
		{
			$this->setError($table->getError());

			return false;
		}

		// Check the data.
		if (!$table->check())
		{
			$this->setError($table->getError());

			return false;
		}

		// Store the data.
		if (!$table->store())
		{
			$this->setError($table->getError());

			return false;
		}

		// Rebuild the path for the skill:
		if (!$table->rebuildPath($table->id))
		{
			$this->setError($table->getError());

			return false;
		}

		// Rebuild the paths of the skill's children:
		if (!$table->rebuild($table->id, $table->lft, $table->level, $table->path))
		{
			$this->setError($table->getError());

			return false;
		}

		$this->setState($this->getName() . '.id', $table->id);

		return true;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return   void
	 *
	 * @since    1.0.0
	 */

	protected function populateState()
	{
		$app    = Factory::getApplication('administrator');
		$jinput = $app->input;

		$parentId = $jinput->getInt('parent_id');
		$this->setState('skill.parent_id', $parentId);

		$id = ($jinput->get('id'))?$jinput->get('id'):$jinput->get('id');
		$this->setState('skill.id', $id);
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   JForm  $form  The form to validate against.
	 * @param   Array   $data  The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 *
	 * @since   12.2
	 */
	public function validate($form, $data, $group = null)
	{
		$return = parent::validate($form, $data);

		return $return;
	}

	/**
	 * Method to change the title & alias.
	 *
	 * @param   integer  $parentId  The id of the parent.
	 * @param   string   $alias     The alias.
	 * @param   string   $title     The title.
	 *
	 * @return  array    Contains the modified title and alias.
	 *
	 * @since   1.7
	 */
	protected function generateNewTitle($parentId, $alias, $title)
	{
		// Alter the title & alias
		$table = $this->getTable();

		while ($table->load(array('alias' => $alias, 'parent_id' => $parentId)))
		{
			$title = StringHelper::increment($title);
			$alias = StringHelper::increment($alias, 'dash');
		}

		return array($title, $alias);
	}

	/**
	 * Method rebuild the entire nested set tree.
	 *
	 * @return  boolean  False on failure or error, true otherwise.
	 *
	 * @since   1.0.0
	 */
	public function rebuild()
	{
		// Get an instance of the table object.
		$table = $this->getTable();

		if (!$table->rebuild())
		{
			$this->setError($table->getError());

			return false;
		}

		return true;
	}

	/**
	 * Method to save the reordered nested set tree.
	 * First we save the new order values in the lft values of the changed ids.
	 * Then we invoke the table rebuild to implement the new ordering.
	 *
	 * @param   array    $idArray   An array of primary key ids.
	 * @param   integer  $lftArray  The lft value
	 *
	 * @return  boolean  False on failure or error, True otherwise
	 *
	 * @since   1.6
	 */
	public function saveorder($idArray = null, $lftArray = null)
	{
		// Get an instance of the table object.
		$table = $this->getTable();

		if (!$table->saveorder($idArray, $lftArray))
		{
			$this->setError($table->getError());

			return false;
		}

		return true;
	}

	/**
	 * Method to unique code.
	 *
	 * @param   string   $alias     The Unique code.
	 *
	 * @return  array    Contains the modified Unique code.
	 *
	 * @since   1.7
	 */
	protected function generateNewUniqueCode($uniqueCode)
	{
		// Alter the title & alias
		$table = $this->getTable();

		while ($table->load(array('unique_code' => $uniqueCode)))
		{
			$uniqueCode = StringHelper::increment($uniqueCode, 'dash');
		}

		return $uniqueCode;
	}
}
