<?php
/**
 * @package    Com_Tmt
 * @copyright  Copyright (C) 2009 -2015 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

/**
 * Tmt model.
 *
 * @since  1.0
 */
class  TmtModelQuizRules extends AdminModel
{
	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $type    The table name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable  A JTable object
	 *
	 * @since   1.6
	 */
	public function getTable($type = 'QuizRules', $prefix = 'TmtTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed    A JForm object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getForm($data = array() , $loadData = true)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tmt.quizrules', 'quizrules', array( 'control' => 'jform', 'load_data' => $loadData ));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   1.6
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_tmt.edit.quizrules.data', array());

		if (empty($data))
		{
			$data = $this->getData();
		}

		return $data;
	}

/**
	* Method to get the Id's which are already stored against Quiz.
	*
	* @param   int  $test_id  This is quiz id/ test_id
	*
	* @return  Array  The id's of saved rules.
	*
	* @since   1.0
	*/
	public function getStoredRulesData ($test_id)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__tmt_quiz_rules'));
		$query->where($db->quoteName('quiz_id') . ' = ' . $db->quote($test_id));

		$db->setQuery($query);
		$results = $db->loadColumn();

		return $results;
	}
}
