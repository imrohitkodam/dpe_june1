<?php
/**
 * @package     Multiagency
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Date\Date;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
/**
 * Multiagency model.
 *
 * @since  __DEPLOY__VERSION__
 */
class MultiagencyModelLicenceForm extends FormModel
{
	private $item = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return void
	 *
	 * @since  __DEPLOY__VERSION__
	 */
	protected function populateState()
	{
		$app = Factory::getApplication('com_multiagency');

		// Load state from the request userState on edit or from the passed variable on default

		// DPE Hack start - For set user state from the passed variable

		/*
		if (Factory::getApplication()->input->get('layout') == 'edit')
		{
			$id = Factory::getApplication()->getUserState('com_multiagency.edit.licence.id');
		}
		else
		{
			$id = Factory::getApplication()->input->get('id');
			Factory::getApplication()->setUserState('com_multiagency.edit.licence.id', $id);
		}
		*/

		$id = $app->input->getInt('id') ? $app->input->getInt('id') : $app->getUserState('com_multiagency.edit.licence.id');

		if ($id)
		{
			$this->setState('licence.id', $id);
		}

		// DPE Hack end - For set user state from the passed variable

		// Load the parameters.
		$params = ComponentHelper::getParams('com_multiagency');
		$params_array = $params->toArray();

		/*if (isset($params_array['item_id']))
		{
			$this->setState('licence.id', $params_array['item_id']);
		}*/

		$this->setState('params', $params);
	}

	/**
	 * Method to get an ojbect.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return Object|boolean Object on success, false on failure.
	 *
	 * @throws Exception
	 */
	public function &getData($id = null)
	{
		if ($this->item === null)
		{
			$this->item = false;

			if (empty($id))
			{
				$id = $this->getState('licence.id');
			}

			// Get a level row instance.
			$table = $this->getTable();

			// Attempt to load the row.
			if ($table !== false && $table->load($id))
			{
				$user = Factory::getUser();
				$id   = $table->id;

			if ($id)
			{
			$canEdit = $user->authorise('core.edit', 'com_multiagency.licence.' . $id) || $user->authorise('core.create', 'com_multiagency.licence.' . $id);
			}
				else
				{
					$canEdit = $user->authorise('core.edit', 'com_multiagency') || $user->authorise('core.create', 'com_multiagency');
				}

				if (!$canEdit && $user->authorise('core.edit.own', 'com_multiagency.licence.' . $id))
				{
					$canEdit = $user->id == $table->created_by;
				}

				if (!$canEdit)
				{
					throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 500);
				}

				// Check published state.
				if ($published = $this->getState('filter.published'))
				{
					if (isset($table->state) && $table->state != $published)
					{
						return $this->item;
					}
				}

				// Convert the JTable to a clean JObject.
				$properties  = $table->getProperties(1);
				$this->item = ArrayHelper::toObject($properties, 'JObject');
			}
		}

		return $this->item;
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
	public function getTable($type = 'Licence', $prefix = 'MultiagencyTable', $config = array())
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Get an item by alias
	 *
	 * @param   string  $alias  Alias string
	 *
	 * @return int Element id
	 */
	public function getItemIdByAlias($alias)
	{
		$table = $this->getTable();

		$table->load(array('alias' => $alias));

		return $table->id;
	}

	/**
	 * Method to check in an item.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	public function checkin($id = null)
	{
		// Get the id.
		$id = (!empty($id)) ? $id : (int) $this->getState('licence.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Attempt to check the row in.
			if (method_exists($table, 'checkin'))
			{
				if (!$table->checkin($id))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to check out an item for editing.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	public function checkout($id = null)
	{
		// Get the user id.
		$id = (!empty($id)) ? $id : (int) $this->getState('licence.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Get the current user object.
			$user = Factory::getUser();

			// Attempt to check the row out.
			if (method_exists($table, 'checkout'))
			{
				if (!$table->checkout($user->get('id'), $id))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to get the profile form.
	 *
	 * The base form is loaded from XML
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return    Form    A Form object on success, false on failure
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_multiagency.licence', 'licenceform', array(
			'control'   => 'jform',
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
	 * @return    mixed    The data for the form.
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_multiagency.edit.licence.data', array());

		if (empty($data))
		{
			$data = $this->getData();
		}

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data
	 *
	 * @return boolean|integer
	 *
	 * @throws Exception
	 * @since __DEPLOY__VERSION__
	 */
	public function save($data)
	{
		$S_date             = $data['start_date'];
		$E_date             = $data['end_date'];
		$courseId           = (int) $data['course_id'];
		$multiagencyId      = (int) $data['multiagency_id'];
		$licenceType        = (string) $data['licence_type'];
		$data['start_date'] = HTMLHelper::date($S_date, 'Y-m-d');
		$data['end_date']   = HTMLHelper::date($E_date . ' +22 hour +59 minutes +59 seconds', 'Y-m-d H:i:s');

		/*
		 * DPE Hack start: Following code is commented because of it is updating data on old licence
			if ((int) $data['id'] == '0')
			{
				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models', 'licence');
				$MultiagencyModelLicences = BaseDatabaseModel::getInstance('Licences', 'MultiagencyModel');
				$licenceId                = $MultiagencyModelLicences->getLicenceId($multiagencyId, $courseId);
				$data['id']               = $licenceId->id;
			}
		*/
		// DPE Hack end

		$id           = (!empty($data['id'])) ? (int) $data['id'] : (int) $this->getState('licence.id');
		$state        = (!empty($data['state'])) ? 1 : 0;
		$migrateDb    = (!empty($data['migrationDb'])) ? 1 : 0;

		$user         = Factory::getUser();
		$data['type'] = $licenceType;

		PluginHelper::importPlugin('system');

		$triggerResult = Factory::getApplication()->triggerEvent('onBeforeLicenceSave', array($data));

		if (!in_array(true, $triggerResult, true))
		{
			$this->setError($triggerResult[0]);

			return false;
		}

		if ($id)
		{
			// Check the user can edit this item
			$authorised = $user->authorise('core.edit', 'com_multiagency.licence.' . $id) ||
			$authorised = $user->authorise('core.edit.own', 'com_multiagency.licence.' . $id);
			$data['oldLicenceData'] = $this->getItem($id);
		}
		else
		{
			// Check the user can create new items in this section
			$authorised = $user->authorise('core.create', 'com_multiagency');
		}

		if ($authorised !== true)
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// DPE Hack Start.
		$db = Factory::getDbo();
		$multiagencyId = (int) $data['multiagency_id'];
		
		// Step 1: Try to get lead consultant from the organisation
		$query = $db->getQuery(true)
			->select($db->quoteName('lead_consultant_id'))
			->from($db->quoteName('#__tjmultiagency_multiagency'))
			->where($db->quoteName('id') . ' = ' . $multiagencyId);
		$db->setQuery($query);
		$leadConsultantId = (int) $db->loadResult();
		
		if ($leadConsultantId > 0)
		{
			// Step 2: Get all licences for the same multiagency
			$query = $db->getQuery(true)
				->select('id')
				->from($db->quoteName('#__tjmultiagency_licences'))
				->where($db->quoteName('multiagency_id') . ' = ' . $multiagencyId);
		    $db->setQuery($query);
			$licenceIds = $db->loadColumn();
		
			// Step 3: Bulk update cluster_xref with lead consultant
			if (!empty($licenceIds))
			{
				$query = $db->getQuery(true)
					->update($db->quoteName('#__tj_sla_cluster_xref'))
					->set($db->quoteName('lead_consultant_id') . ' = ' . $leadConsultantId)
					->where($db->quoteName('license_id') . ' IN (' . implode(',', array_map('intval', $licenceIds)) . ')');
		
				$db->setQuery($query);
				$db->execute();
			}
		
			$data['lead_consultant_id'] = $leadConsultantId;
		}
		else
		{
			// Step 4: Try to inherit lead consultant from latest SLA
			$query = $db->getQuery(true)
				->select('id')
				->from($db->quoteName('#__tjmultiagency_licences'))
				->where($db->quoteName('multiagency_id') . ' = ' . $multiagencyId)
				->order($db->quoteName('end_date') . ' DESC')
				->setLimit(1);
		
			$db->setQuery($query);
			$latestLicenceId = (int) $db->loadResult();
		
			if ($latestLicenceId)
			{
				$query = $db->getQuery(true)
					->select($db->quoteName('lead_consultant_id'))
					->from($db->quoteName('#__tj_sla_cluster_xref'))
					->where($db->quoteName('license_id') . ' = ' . $latestLicenceId);
				$db->setQuery($query);
				$leadConsultantId = (int) $db->loadResult();
				$data['lead_consultant_id'] = $leadConsultantId;
			}
		}
		// DPE Hack End.
		
		$table = $this->getTable();

		$data['checked_out'] = Factory::getUser()->id;
		$data['checked_out_time'] = new Date('now');
		$data['used_seats'] = empty($data['used_seats'])?0:$data['used_seats'];     


		if ($table->save($data) === true)
		{
			// Trigger all "onAfterCourseEnrol" plugins method
			$data['id'] = $table->id;

			if ($migrateDb)
			{
				$id = 0;
			}

			Factory::getApplication()->triggerEvent('onAfterMultiagencySave', array($id, $data));
			Factory::getApplication()->triggerEvent('onAfterOrgSlaUpdateLeadstaffMember', array($multiagencyId));

			return $table->id;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to delete data
	 *
	 * @param   int  $pk  Item primary key
	 *
	 * @return  int  The id of the deleted item
	 *
	 * @throws Exception
	 *
	 * @since __DEPLOY__VERSION__
	 */
	public function delete($pk)
	{
		$user = Factory::getUser();

		if (empty($pk))
		{
			$pk = (int) $this->getState('licence.id');
		}

		if ($pk == 0 || $this->getData($pk) == null)
		{
			throw new Exception(Text::_('COM_MULTIAGENCY_ITEM_DOESNT_EXIST'), 404);
		}

		if ($user->authorise('core.delete', 'com_multiagency.licence.' . $id) !== true)
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$table = $this->getTable();
		$table->load($pk);
		$multiagencyId = $table->multiagency_id;

		if ($table->delete($pk) !== true)
		{
			throw new Exception(Text::_('JERROR_FAILED'), 501);
		}
		else
		{
			// Perform project specific Activity After Delete
			
			PluginHelper::importPlugin('system');
			$data = array('license_id' => (int) $pk, 'multiagency_id' => (int) $multiagencyId);
			Factory::getApplication()->triggerEvent('onLicenceFormAfterDelete', array($data));
		}

		return $pk;
	}

	/**
	 * Check if data can be saved
	 *
	 * @return bool
	 */
	public function getCanSave()
	{
		$table = $this->getTable();

		return $table !== false;
	}

	/**
	 * Method to check existing data
	 *
	 * @param   int     $agencyId     Item primary key
	 *
	 * @param   string  $licenceType  Item primary key
	 *
	 * @param   int     $courseId     Item primary key
	 *
	 * @return bool
	 *
	 * @since __DEPLOY__VERSION__
	 */
	public function checkExistingCourse($agencyId, $licenceType, $courseId = null)
	{
		if ($agencyId && $licenceType)
		{
			$licenceType  = ucfirst($licenceType);
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
			$licenceTable = Table::getInstance('licence', 'MultiagencyTable');

			if ($licenceType == Text::_('COM_MULTIAGENCY_LICENCE_TYPE'))
			{
				$licenceTable->load(array('multiagency_id' => $agencyId, 'type' => strtolower(Text::_('COM_MULTIAGENCY_LICENCE_TYPE_ALL'))));
				$msg = Text::sprintf('COM_MULTIAGENCY_ALL_ALREADY_PRESENT_EDIT_ERROR', Text::_('COM_MULTIAGENCY_ORGANISATION'));
			}

			if ($licenceType == Text::_('COM_MULTIAGENCY_LICENCE_TYPE_ALL'))
			{
				$licenceTable->load(array('multiagency_id' => $agencyId, 'type' => strtolower(Text::_('COM_MULTIAGENCY_LICENCE_TYPE'))));
				$msg = Text::sprintf('COM_MULTIAGENCY_ALREADY_PRESENT_EDIT_ERROR', Text::_('COM_MULTIAGENCY_ORGANISATION'));

				if (empty($licenceTable->id))
				{
					$licenceTable->load(array('multiagency_id' => $agencyId, 'type' => strtolower($licenceType)));

					if ($licenceTable->id && $licenceTable->type == strtolower(Text::_('COM_MULTIAGENCY_LICENCE_TYPE_ALL')))
					{
						$msg = Text::sprintf('COM_MULTIAGENCY_ALL_ALREADY_PRESENT_EDIT_ERROR', Text::_('COM_MULTIAGENCY_ORGANISATION'));
					}
				}
			}

			$currentDate = Factory::getDate()->toUnix();
			$licenceEndDate = Factory::getDate($licenceTable->end_date)->toUnix();
			$totalSeat = $licenceTable->total_seats;

			if ($licenceTable->id && ($totalSeat > $licenceTable->used_seats || $totalSeat == 0) && ($currentDate < $licenceEndDate))
			{
				$this->setError($msg);

				return false;
			}

			return true;
		}
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   Form   $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 *
	 * @see     FormRule
	 * @see     InputFilter
	 * @since   __DEPLOY__VERSION__
	 */
	public function validate($form, $data, $group = null)
	{
		if (!$data['multiyearlicence'])
		{
			// DPE Hack this validation nit needed for multilicence
			if ((Factory::getDate($data['start_date'], 'UTC')->toUnix()) > Factory::getDate($data['end_date'], 'UTC')->toUnix() )
			{
				$this->setError(Text::_('COM_MULTIAGENCY_LICENCES_START_END_DATE_WARNING'));

				return false;
			}
		}
		else if($data['multiyearlicence'])
		{	
			$currentTime = Factory::getDate()->toSql();
			$currentTime = Factory::getDate($currentTime)->format('Y-m-d');
			$data['end_date']= $currentTime;
		}

		if ($data['id'])
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
			$LicenceModel = Table::getInstance('Licence', 'MultiagencyTable');
			$LicenceModel->load(array('id' => $data['id']));

			if ($LicenceModel->type != $data['licence_type'])
			{
				return false;
			}
		}
		else
		{
			$currentTime = Factory::getDate()->toSql();
			$currentTime = Factory::getDate($currentTime)->format('Y-m-d');

			// DPE Hack to allow start date as a past date while adding a licence

			/*
			if (Factory::getDate($data['start_date'], 'UTC')->toSql() < $currentTime)
			{
				$this->setError(Text::_('COM_MULTIAGENCY_LICENCES_START_CURRENT_DATE_BIGGER_WARNING'));

				return false;
			}
			*/

			if ((Factory::getDate($data['end_date'], 'UTC')->toSql()) < $currentTime)
			{
				$this->setError(Text::_('COM_MULTIAGENCY_LICENCES_END_CURRENT_DATE_BIGGER_WARNING'));

				return false;
			}
		}

		if ($data['licence_type'] === 'per')
		{
			$checkExistingCourse = $this->checkExistingCourse($data['multiagency_id'], $data['licence_type'], $data['course_id']);

			if (!$checkExistingCourse)
			{
				return false;
			}
		}

		return parent::validate($form, $data, $group = null);
	}

	/**
	 * Allows preprocessing of the Form object.
	 *
	 * @param   Form   $form   The form object
	 * @param   array   $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		$app  = Factory::getApplication();

		/* Show extra fields of SLA on license
		Get plugin dpe of type 'system' */

		$plgSystemDpe           = PluginHelper::getPlugin('system', 'dpe');

		if (!empty($plgSystemDpe))
		{
			$form->loadFile(JPATH_ROOT . '/plugins/system/dpe/forms/sla_licence.xml', true);
		}

		parent::preprocessForm($form, $data, $group);
	}

	/**
	 * Method to get an ojbect.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return Object|boolean Object on success, false on failure.
	 *
	 * @throws Exception
	 */
	public function getItem($id = null)
	{
		if (empty($id))
		{
			$id = $this->getState('licence.id');
		}

		// Get a level row instance.
		$table = $this->getTable();

		// Attempt to load the row.
		if ($table !== false && $table->load($id))
		{
			$user = Factory::getUser();
			$id   = $table->id;

			// Convert the JTable to a clean JObject.
			$properties  = $table->getProperties(1);
			$this->item = ArrayHelper::toObject($properties, 'JObject');

			// Get saved licence tool clients
			$licenceModel = Multiagency::model('licence', array('ignore_request' => true));
			$savedClients = $licenceModel->getSavedClientsFromLicenceXref($id);

			$this->item->tools  = $savedClients;
		}

		return $this->item;
	}
}
