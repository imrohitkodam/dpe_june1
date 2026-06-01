<?php
/**    
 * SEO Glossary Glossary model
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
use Joomla\String\StringHelper; 
jimport('joomla.application.component.modeladmin');

/**
 * Seoglossary model.
 */
class SeoglossaryModelGlossary extends JModelAdmin
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_SEOGLOSSARY';
	
	public $typeAlias = 'com_seoglossary.glossary';


	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable($type = 'Glossary', $prefix = 'SeoglossaryTable', $config = array())
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
                jimport('joomla.form.form');
                JForm::addFieldPath('JPATH_ADMINISTRATOR/components/com_seoglossary/models/fields');
		$form = $this->loadForm('com_seoglossary.glossary', 'glossary', array('control' => 'jform', 'load_data' => $loadData));
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
		$data = JFactory::getApplication()->getUserState('com_seoglossary.edit.glossary.data', array());

		if (empty($data)) {
			$data = $this->getItem();
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
	public function getItem( $pk = null )
	{
		if ( $item = parent::getItem( $pk ) )
		{
			if (!empty($item->id))
		{
			$item->tags = new JHelperTags;
			$item->tags->getTagIds($item->id, 'com_seoglossary.glossary');
		}
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

		$table->tterm = htmlspecialchars_decode($table->tterm, ENT_QUOTES);

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
			if ($name == $table->tterm)
			{
				$name = StringHelper::increment($name);
			}

			$alias = StringHelper::increment($alias, 'dash');
		}

		return array($name, $alias);
	}
	public function save($data)
	{
		$input  = JFactory::getApplication()->input;
		$filter = JFilterInput::getInstance();
		if ($input->get('task') == 'save2copy')
		{
			$origTable = clone $this->getTable();
			$origTable->load($input->getInt('id'));

			if ($data['title'] == $origTable->title)
			{
				list($title, $alias) = $this->generateNewTitle($data['catid'], $data['alias'], $data['title']);
				$data['title'] = $title;
				$data['alias'] = $alias;
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
		if (parent::save($data))
		{
			return true;
		}

		return false;
	}

}