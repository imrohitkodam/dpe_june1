<?php
/**    
 * SEO Glossary Catglossary model
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');

/**
 * Seoglossary model.
 */
class SeoglossaryModelCatglossary extends JModelAdmin
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_SEOGLOSSARY';
	
	public $typeAlias = 'com_seoglossary.catglossary';


	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable($type = 'Catglossary', $prefix = 'SeoglossaryTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		An optional array of data for the form to interogate.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	JForm	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app	= JFactory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_seoglossary.catglossary', 'catglossary', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_seoglossary.edit.catglossary.data', array());

		if (empty($data)) {
			$data = $this->getItem();
			$data->disable_menu=explode(',', $data->disable_menu);
			$data->disable_component=explode(',', $data->disable_component);
			$data->disable_category=explode(',', $data->disable_category);
			
		}
		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param	integer	The id of the primary key.
	 *
	 * @return	mixed	Object on success, false on failure.
	 * @since	1.6
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk)) {

			//Do any procesing on fields here if needed

		}

		return $item;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   JTable  $table  The JTable object
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function prepareTable($table)
	{
		$date = JFactory::getDate();
		$user = JFactory::getUser();

		$table->name = htmlspecialchars_decode($table->name, ENT_QUOTES);

		$table->generateAlias();

		if (empty($table->id))
		{
		
			// Set ordering to the last item if not set
			if (empty($table->ordering))
			{
				$db = JFactory::getDbo();
				$query = $db->getQuery(true)
					->select('MAX(ordering)')
					->from($db->quoteName('#__contact_details'));
				$db->setQuery($query);
				$max = $db->loadResult();

				$table->ordering = $max + 1;
			}
		}
		
	}
	public function save($data)
	{
		$input  = JFactory::getApplication()->input;
		$filter = JFilterInput::getInstance();
		$check = $input->post->get('jform', array(), 'array');
		if ( is_array( $check['disable_menu'] )) {
            $data['disable_menu'] = implode( ',', $check['disable_menu'] );
        }
		if(empty($check['disable_menu']))
		{
		    $data['disable_menu'] = "";
		}
		if ( is_array( $check['disable_category'] )) {
            $data['disable_category'] = implode( ',', $check['disable_category'] );
        }
		if(empty($check['disable_category']))
		{
		    $data['disable_category'] = "";
		}
		if ( is_array( $check['disable_component'] )) {
            $data['disable_component'] = implode( ',', $check['disable_component'] );
        }
		if(empty($check['disable_component']))
		{
		    $data['disable_component'] = "";
		}
		if (parent::save($data))
		{
			return true;
		}

		return false;
	}

	
	/**
	 * Method to change the title & alias.
	 *
	 * @param   integer  $category_id  The id of the parent.
	 * @param   string   $alias        The alias.
	 * @param   string   $name         The title.
	 *
	 * @return  array  Contains the modified title and alias.
	 *
	 * @since   3.1
	 */
	protected function generateNewTitle($category_id, $alias, $name)
	{
		// Alter the title & alias
		$table = $this->getTable();

		while ($table->load(array('alias' => $alias)))
		{
			if ($name == $table->name)
			{
				$name = JString::increment($name);
			}

			$alias = JString::increment($alias, 'dash');
		}

		return array($name, $alias);
	}
}