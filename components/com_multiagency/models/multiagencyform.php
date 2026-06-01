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
defined('_JEXEC') or die();
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Date\Date;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Helper\TagsHelper;

JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
/**
 * MultiagencyForm model
 *
 * @since  __DEPLOY__VERSION__
 */
class MultiagencyModelMultiagencyForm extends FormModel
{
	private $item = null;
	public $typeAlias = 'com_multiagency.multiagency';
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

		$pk = $app->input->getInt('id');

		$this->setState('multiagency.id', $pk);

		// Load the parameters.
		$params = $app->getParams();
		$params_array = $params->toArray();

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
				$id = $this->getState('multiagency.id');
			}

			// Get a level row instance.
			$table = $this->getTable();

			// Attempt to load the row.
			if ($table !== false && $table->load($id))
			{
				$user = Factory::getUser();
				$id = $table->id;

				$userId = RBACL::getRoleByUser($user->id);
				if ($id)
				{
					if ($user->authorise('core.edit', 'com_multiagency.multiagency.' . $id) || $user->authorise('core.create', 'com_multiagency.multiagency.' . $id) || $user->authorise('core.own.adduser', 'com_multiagency')|| $user->authorise('core.adduser', 'com_multiagency'))
					{
						$canEdit = true;
					}
				}
				else
				{
					$canEdit = $user->authorise('core.edit', 'com_multiagency') || $user->authorise('core.create', 'com_multiagency');
				}

				if (! $canEdit && $user->authorise('core.edit.own', 'com_multiagency.multiagency.' . $id))
				{
					$canEdit = $user->id == $table->created_by;
				}

				if (! $canEdit)
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
				$properties = $table->getProperties(1);
				$this->item = ArrayHelper::toObject($properties, 'JObject');

				//DPE hack can go in core
				$this->item->tags = new TagsHelper;
				$this->item->tags->getTagIds($id, 'com_multiagency.multiagency');
			}
		}


		// DPE Hack 
		if ($this->item->lead_consultant_id == 0)
		{

		   $result = $this->getOldLeadconsult($this->item->id);

		   if($result)
		   {
		   	$this->item->lead_consultant_id = $result->lead_consultant;
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
	public function getTable($type = 'Multiagency', $prefix = 'MultiagencyTable', $config = array())
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

		$table->load(
			array
			(
				'alias' => $alias
			)
		);

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
		$id = (! empty($id)) ? $id : (int) $this->getState('multiagency.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Attempt to check the row in.
			if (method_exists($table, 'checkin'))
			{
				if (! $table->checkin($id))
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
		$id = (! empty($id)) ? $id : (int) $this->getState('multiagency.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Get the current user object.
			$user = Factory::getUser();

			// Attempt to check the row out.
			if (method_exists($table, 'checkout'))
			{
				if (! $table->checkout($user->get('id'), $id))
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
		$form = $this->loadForm('com_multiagency.multiagency', 'multiagencyform', array(
			'control' => 'jform', 'load_data' => $loadData)
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
		$data = Factory::getApplication()->getUserState('com_multiagency.edit.multiagency.data', array());

		if (empty($data))
		{
			$data = $this->getData();
		}

		return $data;
	}

	/**
	 * Method to get the subform data that should be injected in the form.
	 *
	 * @param   int     $agencyId  Agency Id
	 * @param   int     $allUser   true or false
	 * @param   string  $client    client
	 *
	 * @return    mixed    The data for the form.
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	public function loadSubFormData($agencyId, $allUser = null, $client = null)
	{
		$params = ComponentHelper::getParams('com_multiagency');
		$adminRoleId = $params->get('admin_role_id', '0', 'INT');

		// Create a new query object
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('a.id, a.name, a.email, a.username, su.id as suid');
		$query->from($db->quoteName('#__users') . ' AS a');
		$query->join('INNER', '#__tjsu_users AS su ON su.user_id = a.id');
		$query->where($db->quoteName('su.client_id') . ' =' . $db->quote($agencyId));

		if (!empty($client))
		{
			$client = $db->Quote('%' . $db->escape($client, true) . '%');
			$query->where($db->quoteName('su.client') . ' Like ' . $client);
		}

		if (empty($allUser))
		{
			$query->where($db->quoteName('a.block') . '= 0 ');
			$query->where($db->quoteName('su.role_id') . '= ' . $db->quote($adminRoleId));
		}

		$db->setQuery($query);
		$result = $db->loadObjectList();

		return $result;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since __DEPLOY__VERSION__
	 */
	public function save($data)
	{
		$table = $this->getTable();

		$id = (! empty($data['id'])) ? $data['id'] : (int) $this->getState('multiagency.id');
		$state = (! empty($data['state'])) ? 1 : 0;
		$user = Factory::getUser();

		// If there is multiagency id check user permissions
		if ($id)
		{
			$core_edit = $user->authorise('core.edit', 'com_multiagency.multiagency.' . $id);
			$authorised = $user->authorise('core.edit.own', 'com_multiagency.multiagency.' . $id);

			// Check the user can edit this item
			if ($core_edit || $authorised)
			{
				$authorised = true;
			}
		}
		else
		{
			// Check the user can create new items in this section
			$authorised = $user->authorise('core.create', 'com_multiagency');
		}

		if ($authorised !== true)
		{
			$this->setError(Text::_("JERROR_ALERTNOAUTHOR"));

			return false;
		}

		// Dpe hack to  save tags
		if (!empty($data['tags']))
		{
			$table->newTags = $data['tags'];
			
		}

		$checkoutDate = new Date('now');
		$checkoutDate = $checkoutDate->toSQL();

		$data['ordering'] = empty($data['ordering'])?0:$data['ordering'];
		$data['checked_out'] = empty($data['checked_out'])?$user->id:$data['checked_out'];
		$data['checked_out_time'] = empty($data['checked_out_time'])?$checkoutDate:$data['checked_out_time'];
		$data['created_by'] = empty($data['created_by'])?$user->id:$data['created_by'];
		$data['modified_by'] = empty($data['modified_by'])?$user->id:$data['modified_by'];
		$data['manager_id'] = empty($data['manager_id'])?0:$data['manager_id'];
		$data['country_id'] = empty($data['country_id'])?0:$data['country_id'];
		$data['address'] = empty($data['address'])?'':$data['address'];
		$data['phone_no'] = empty($data['phone_no'])?'':$data['phone_no'];

		if ($table->save($data) === true)
		{
			
			PluginHelper::importPlugin('system');

			// Added trigger for custom form saving
			$key = $table->getKeyName();
			$pk = (!empty($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');
			$isNew = true;

			try
			{
				// Load the row if saving an existing record.
				if ($pk > 0)
				{
					$table->load($pk);
					$isNew = false;
				}

				// Trigger for saving joomla custom fields value
				Factory::getApplication()->triggerEvent('onContentAfterSave', array('com_multiagency.multiagency', $table, $isNew, $data));
			}
			catch (\Exception $e)
			{
				$this->setError($e->getMessage());

				return false;
			}

			if ($id)
			{
				// AgencyId, NewManagerList, DeletedManagerList
				Factory::getApplication()->triggerEvent('onAfterUpdateAgency', array($table->id));
			}
			else
			{
				// AgencyId, NewManagerList
				Factory::getApplication()->triggerEvent('onAfterCreateAgency', array($table->id));
			}
			return $table->id;
		}
		else
		{
			return false;
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
		$agencyValidate = $this->validateAgency($data['title'], $data['id']);

		if ($agencyValidate)
		{
			$this->setError(Text::_('COM_MULTIAGENCY_TITLE_MESSAGE'));

			return false;
		}

		return parent::validate($form, $data, $group = null);
	}

	/**
	 * To check if entered is valid Agency Title
	 *
	 * @param   string  $title     title
	 * @param   string  $agencyId  agencyId
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function validateAgency($title, $agencyId)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from($db->quoteName('#__tjmultiagency_multiagency'));
		$query->where($db->quoteName('title') . ' = ' . $db->quote($title));
		$query->where($db->quoteName('id') . ' != ' . (int) $agencyId);
		$this->_db->setQuery($query);
		$agencyExist = $this->_db->loadResult();

		if ($agencyExist)
		{
			return true;
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
		$managerIds  = array();
		$managerList = array();

		// Get com_subusers component status
		if (ComponentHelper::getComponent('com_subusers', true)->enabled)
		{
			// Get record by using multiagency id
			$managerIds = $this->loadSubFormData($pk, 1, 'com_multiagency');

			$subUserModel = RBACL::model('user');

			foreach ($managerIds as $rec)
			{
				$mailRemoved[$rec->id] = array('name' => $rec->name, 'email' => $rec->email);
				$suIds[$rec->id][$rec->suid] = $rec->suid;
				$managerList[$rec->id] = $rec->id;
			}

			// Get record by using cluster id
			$ClusterModel = ClusterFactory::model('Cluster');
			$clusterInfo = $ClusterModel::getClusterByClient('com_multiagency', $pk);
			$managerIds = $this->loadSubFormData($clusterInfo->id, 1, 'com_cluster');

			foreach ($managerIds as $rec)
			{
				$mailRemoved[$rec->id] = array('name' => $rec->name, 'email' => $rec->email);
				$suIds[$rec->id][$rec->suid] = $rec->suid;
				$managerList[$rec->id] = $rec->id;
			}
		}

		// 1. Delete agency
		$agencyData = $this->getData($pk);

		$params = ComponentHelper::getParams('com_multiagency');
		$groupId = $params->get('multiagency_manager_group', '0', 'INT');
		//$managerRoleId = $params->get('manager_role_id', '0', 'INT');

		if (empty($pk))
		{
			$pk = (int) $this->getState('multiagency.id');
		}

		if ($pk == 0 || $agencyData == null)
		{
			$this->setError(Text::_("COM_MULTIAGENCY_ITEM_DOESNT_EXIST"));

			return false;
		}

		if ($user->authorise('core.delete', 'com_multiagency.multiagency.' . $id) !== true)
		{
			$this->setError(Text::_("JERROR_ALERTNOAUTHOR"));

			return false;
		}

		$table = $this->getTable();

		if ($table->delete($pk))
		{
			$helperPath = JPATH_COMPONENT_SITE . '/helpers/multiagency.php';

			if (! class_exists('MultiagencyFrontendHelpers'))
			{
				// Require_once $path;
				JLoader::register('MultiagencyFrontendHelpers', $helperPath);
				JLoader::load('MultiagencyFrontendHelpers');
			}

			$helperObject = new MultiagencyFrontendHelpers;

			/*
			if (!empty($mailRemoved))
			{
				foreach ($mailRemoved as $key => $rec)
				{
						$mailRemoved = array('name' => $rec['name'], 'email' => $rec['email'], 'title' => $agencyData->title);
						$helperObject->SendMailNewUser($mailRemoved, '', 'schoolManagerRemoving');

						// Remove subusers
						if (!empty($suIds[$key]))
						{
							$subUserModel->delete($suIds[$key]);
						}
				}
			}
			*/

			
			PluginHelper::importPlugin('system');
			Factory::getApplication()->triggerEvent('onAfterDeleteAgency', array($pk, $managerList));
		}
		else
		{
			$this->setError(Text::_("JERROR_FAILED"));

			return false;
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
	 * Get list of multiagency managers
	 *
	 * @return bool
	 */
	public function getMultiagencyManagers()
	{
		$app = Factory::getApplication();
		$this->params = $app->getParams('com_multiagency');

		// Create a new query object
		$db = $this->getDbo();
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

	/**
	 * This get Agency Managers
	 *
	 * @param   integer  $agencyId  User id
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getAgencyManagers($agencyId)
	{
		$this->params = ComponentHelper::getParams('com_multiagency');
		$multiagencyManagerGroup = $this->params->get('multiagency_manager_group', '0', 'INT');

		// Create a new query object
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('a.id, a.name, a.email, a.username, su.id as suid');
		$query->select('su.user_id');
		$query->from($db->quoteName('#__tjsu_users') . ' AS su');
		$query->join('LEFT', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('su.user_id') . ' = ' . $db->quoteName('u.id') . ')');
		$query->where($db->quoteName('u.block') . '= 0');

		if (!empty($agencyId))
		{
			$query->where($db->quoteName('su.client_id') . '= ' . $agencyId);
		}

		$query->where($db->quoteName('su.client') . '= "com_multiagency"');

		$db->setQuery($query);

		return $db->loadColumn();
	}
	/**
     * Update the Lead consultant of all liscence belongs to this org 
     *
     * @param   int  $clusterId  Organisation Id 
     * 
     * @param   int  $leadConsultantId  lead consultant Id of the Org
     * 
     * @return  boolean
     *
     * @since   4.0.0
     */
	public function updateSlaLeadConsultant($clusterId, $leadConsultantId)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$db->setQuery('UPDATE #__tj_sla_cluster_xref SET lead_consultant_id = '.$leadConsultantId.' WHERE cluster_id='.$clusterId);
		if($db->execute())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

//DPE HACK
	/* * Get the leadconsultant of the current license if the consultant in org is 0 
     *
     * @param   int  $clientId  multiagency Id 
     * 
     * 
     * @return  object
     *
     * @since   4.0.0
     */
	protected function getOldLeadconsult($clientId)
	{

		if (!$clientId)
		{
			return false;
		}
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sla/tables');		 
		$cluster = Table::getInstance('Clusters', 'ClusterTable');
		$cluster->load(array('client_id' => $this->item->id));


		$slaXref = Table::getInstance('SlaClusterXrefs', 'SlaTable');
		$slaXref->load(array('cluster_id' => $cluster->id));
		
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query = $db->getQuery(true);

		$query->select(
			$this->getState(
				'list.select', 'DISTINCT a.id, u.id AS lead_consultant'
			)
		);

		$query->from($db->qn('#__tjmultiagency_multiagency', 'a'));
		$query->join('LEFT', $db->qn('#__tjmultiagency_licences', 'l') . ' ON ' . $db->qn('l.multiagency_id') . ' = ' . $db->qn('a.id'));
		$query->join('LEFT', $db->qn('#__tj_sla_cluster_xref', 'sxref') . ' ON ' . $db->qn('sxref.license_id') . ' = ' . $db->qn('l.id'));
		$query->join('LEFT', $db->qn('#__tj_slas', 'sla') . ' ON ' . $db->qn('sla.id') . ' = ' . $db->qn('sxref.sla_id'));
		$query->join('LEFT', $db->qn('#__users', 'u') . ' ON ' . $db->qn('u.id') . ' = ' . $db->qn('sxref.lead_consultant_id'));
		$query->join('LEFT', $db->qn('#__tj_clusters', 'cl') . ' ON ' . $db->qn('cl.client_id') . ' = ' . $db->qn('a.id'));

// Adding a condition for the cluster ID
		$query->where($db->quoteName('cl.id') . ' = ' . (int) $cluster->id);
		$query->where($db->quoteName('l.state') . ' = 1');
		$db->setQuery($query);
		return $result = $db->loadObject();

	}
}
