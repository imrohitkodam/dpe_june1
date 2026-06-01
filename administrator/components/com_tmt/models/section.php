<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

jimport('joomla.application.component.modeladmin');

/**
 * Tjlms model.
 *
 * @since  1.6
 */
class TmtModelSection extends AdminModel
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_TMT';

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   type    $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return	JTable	A database object
	 *
	 * @since	1.6
	 */
	public function getTable($type = 'section', $prefix = 'TmtTable', $config = array())
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tmt/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm  A JForm object on success, false on failure
	 *
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tmt.section', 'section', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			$mquery = $this->_db->getQuery(true);
			$mquery->select("count(tq.question_id) as qcnt, SUM(q.marks) as marks")
				->from($this->_db->qn("#__tmt_questions", "q"))
				->join("left", $this->_db->qn("#__tmt_tests_questions", "tq") . " ON tq.question_id = q.id")
				->where("tq.section_id = " . (int) $item->id);
			$this->_db->setQuery($mquery);

			$obj = $this->_db->loadObject();
			$item->marks = ($obj->marks) ? $obj->marks : 0;
			$item->qcnt = ($obj->qcnt) ? $obj->qcnt : 0;
		}

		return $item;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   array  $table  post
	 *
	 * @return	table
	 *
	 * @since	1.6
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');

		if (empty($table->id))
		{
			// Set ordering to the last item if not set
			if (@$table->ordering == 0)
			{
				$db = Factory::getDbo();
				$query = $this->_db->getQuery(true);
				$query->select("MAX(ordering)")->from($this->_db->qn("#__tmt_tests_sections"))
						->where("test_id = " . (int) $table->test_id);
				$db->setQuery($query);
				$max = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * Method to  save module along with course ID
	 *
	 * @param   array  $data  post
	 *
	 * @return	null
	 *
	 * @since	1.0.0
	 */
	/*public function save($data)
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/tables');
		$table = $this->getTable();

		$this->prepareTable($table);

		if ($table->save($data) === true)
		{
			return $table->id;
		}

		return false;
	}*/

	/**
	 * Function to render the document
	 *
	 * @param   ARRAY  $data  posted data
	 * @param   ARRAY  $row   table row
	 *
	 * @return  complete html along with script is return.
	 *
	 * @since 1.0.0
	 */
	public function renderHTML($data, $row)
	{
		// Load the layout & push variables
		ob_start();
		$layout = $this->buildLayoutPath('creator');
		include $layout;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Internal use functions
	 *
	 * @param   STRING  $layout  layout
	 *
	 * @return  file
	 *
	 * @since 1.0.0
	 */
	public function buildLayoutPath($layout)
	{
		$app = Factory::getApplication();
		$core_file 	= JPATH_ADMINISTRATOR . '/components/com_tmt/views/section/tmpl/' . $layout . '.php';

		return $core_file;
	}

	/**
	 * Function used to delete the section of a lesoon.
	 *	Ordering of rest of the sections is updated accordingly
	 *
	 * @param   int  &$section_id  section id
	 *
	 * @return boolean
	 *
	 * @since  1.0
	 **/
	public function delete(&$section_id)
	{
		// Get the order of the section which has to be deleted

		$db = Factory::getDbo();
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/tables');
		$sectionTable = Table::getInstance('Section', 'TmtTable', array('dbo', $db));
		$sectionTable->load(array('id' => $section_id));

		// Update the order for rest of the section

		$db = Factory::getDbo();

		$query = $db->getQuery(true);

		// Fields to update.
		$fields = array(
			$db->quoteName('ordering') . ' = ' . $db->quoteName('ordering') . ' - 1',
		);

		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('ordering') . ' > ' . $db->quote($sectionTable->ordering) ,
			$db->quoteName('test_id') . ' = ' . $db->quote($sectionTable->test_id)
		);

		$query->update($db->quoteName('#__tmt_tests_sections'))->set($fields)->where($conditions);

		$db->setQuery($query);

		$result = $db->execute();

		$query = $db->getQuery(true);

		// Delete all custom keys for user 1001.
		$conditions = array(
			$db->quoteName('section_id') . ' = ' . $db->quote($section_id),
			$db->quoteName('test_id') . ' = ' . $db->quote($sectionTable->test_id)
		);

		$query->delete($db->quoteName('#__tmt_tests_questions'));
		$query->where($conditions);

		$db->setQuery($query);

		$result = $db->execute();

		if ($sectionTable->delete())
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}
}
