<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Multiagency model.
 *
 * @since  1.6
 */
class MultiagencyModelmultiagency extends AdminModel
{
	/**
	 * @var      string    The prefix to use with controller messages.
	 * @since    1.6
	 */
	protected $text_prefix = 'COM_MULTIAGENCY';

	/**
	 * @var   	string  	Alias to manage history control
	 * @since   3.2
	 */
	public $typeAlias = 'com_multiagency.multiagency';

	/**
	 * @var null  Item data
	 * @since  1.6
	 */
	protected $item = null;

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return    Table    A database object
	 *
	 * @since    1.6
	 */
	public function getTable($type = 'Multiagency', $prefix = 'MultiagencyTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form  A Form object on success, false on failure
	 *
	 * @since    1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Get the form.
		$form = $this->loadForm(
			'com_multiagency.multiagency', 'multiagency',
			array('control' => 'jform',
				'load_data' => $loadData
			)
		);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return   mixed  The data for the form.
	 *
	 * @since    1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_multiagency.edit.multiagency.data', array());

		if (empty($data))
		{
			if ($this->item === null)
			{
				$this->item = $this->getItem();
			}

			$data = $this->item;

			// Support for multiple or not foreign key field: country_id
			$array = array();

			foreach ((array) $data->country_id as $value)
			{
				if (!is_array($value))
				{
					$array[] = $value;
				}
			}

			$data->country_id = implode(',', $array);

			// Support for multiple or not foreign key field: state_id
			$array = array();

			foreach ((array) $data->state_id as $value)
			{
				if (!is_array($value))
				{
					$array[] = $value;
				}
			}

			$data->state_id = implode(',', $array);
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since    1.6
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Do any procesing on fields here if needed
		}

		return $item;
	}

	/**
	 * Method to duplicate an Multiagency
	 *
	 * @param   array  &$pks  An array of primary key IDs.
	 *
	 * @return  boolean  True if successful.
	 *
	 * @throws  Exception
	 */
	public function duplicate(&$pks)
	{
		$user = Factory::getUser();

		// Access checks.
		if (!$user->authorise('core.create', 'com_multiagency'))
		{
			throw new Exception(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
		}

		
		$context    = $this->option . '.' . $this->name;

		// Include the plugins for the save events.
		PluginHelper::importPlugin($this->events_map['save']);

		$table = $this->getTable();

		foreach ($pks as $pk)
		{
			if ($table->load($pk, true))
			{
				// Reset the id to create a new record.
				$table->id = 0;

				if (!$table->check())
				{
					throw new Exception($table->getError());
				}

				// Trigger the before save event.
				$result = Factory::getApplication()->triggerEvent($this->event_before_save, array($context, &$table, true));

				if (in_array(false, $result, true) || !$table->store())
				{
					throw new Exception($table->getError());
				}

				// Trigger the after save event.
				Factory::getApplication()->triggerEvent($this->event_after_save, array($context, &$table, true));
			}
			else
			{
				throw new Exception($table->getError());
			}
		}

		// Clean cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   Table  $table  Table Object
	 *
	 * @return void
	 *
	 * @since    1.6
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
				$db->setQuery('SELECT MAX(ordering) FROM #__tjmultiagency_multiagency');
				$max             = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * Get list of multiagency managers
	 *
	 * @return bool
	 */
	public function getMultiagencyManagers()
	{
		$this->params = ComponentHelper::getParams('com_multiagency');

		// Create a new query object
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('a.*');
		$query->from($db->quoteName('#__users') . ' AS a');
		$query->where($db->quoteName('a.block') . '= 0');

		$multiagencyManagerGroup = $this->params->get('multiagency_manager_group');

		if (!empty($multiagencyManagerGroup))
		{
			$query->join('LEFT', '#__user_usergroup_map AS map2 ON map2.user_id = a.id');

			if ($multiagencyManagerGroup)
			{
				$query->where('map2.group_id = ' . (int) $multiagencyManagerGroup);
			}
		}

		$db->setQuery($query);

		return $db->loadObjectList();
	}
}
