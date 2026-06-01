<?php
/**
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2020 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Table\Table;
use Joomla\Utilities\ArrayHelper;

/**
 * Class MultiagencyFrontendHelper
 *
 * @since  1.6
 */
class MultiagencyFrontendHelpers
{
	/**
	 * Get an instance of the named model
	 *
	 * @param   string  $name  Model name
	 *
	 * @return null|object
	 */
	public static function getModel($name)
	{
		$model = null;

		// If the file exists, let's
		if (file_exists(JPATH_SITE . '/components/com_multiagency/models/' . strtolower($name) . '.php'))
		{
			require_once JPATH_SITE . '/components/com_multiagency/models/' . strtolower($name) . '.php';
			$model = BaseDatabaseModel::getInstance($name, 'MultiagencyModel');
		}

		return $model;
	}

	/**
	 * Gets the files attached to an item
	 *
	 * @param   int     $pk     The item's id
	 *
	 * @param   string  $table  The table's name
	 *
	 * @param   string  $field  The field's name
	 *
	 * @return  array  The files
	 */
	public static function getFiles($pk, $table, $field)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select($field)
			->from($table)
			->where('id = ' . (int) $pk);

		$db->setQuery($query);

		return explode(',', $db->loadResult());
	}

	/**
	 * Gets the edit permission for an user
	 *
	 * @param   mixed  $item  The item
	 *
	 * @return  bool
	 */
	public static function canUserEdit($item)
	{
		$permission = false;
		$user       = Factory::getUser();

		if ($user->authorise('core.edit', 'com_multiagency'))
		{
			$permission = true;
		}
		else
		{
			if (isset($item->created_by))
			{
				if ($user->authorise('core.edit.own', 'com_multiagency') && $item->created_by == $user->id)
				{
					$permission = true;
				}
			}
			else
			{
				$permission = true;
			}
		}

		return $permission;
	}

	/**
	 * Method to create the new user.
	 *
	 * @param   array   $data            The form data
	 *
	 * @param   string  $randomPassword  randam password
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 * @since 1.6
	 */
	public function createnewuser($data, $randomPassword = null)
	{
		jimport('joomla.user.helper');
		jimport('joomla.application.component.helper');

		$app  = Factory::getApplication();
		// $authorize = Factory::getACL();

		$user = clone Factory::getUser($data['id']);

		// Added to fetch data of organsiation form
		$input = Factory::getApplication()->input;
		$formData = new Registry($input->get('jform', '', 'array'));

		$user->set('id', $data['id']);
		$user->set('usertype', 'Registered');
		$params = ComponentHelper::getParams('com_multiagency');

		if (empty($user->id))
		{
			$user->set('groups', array('2'));
		}

			$user->set('name', $data['name']);
			$user->set('email', $data['email']);
			$user->set('username', str_replace( array( '\''), '', $data['email']));			
			
			if (!$data['id'])
			{
				$user->set('lastvisitDate', '');
				$user->set('requireReset', 1);
			}

			// Check if password is entered
			// Todo: Need update the function if user added password manually then no need to requireReset
			if ($data['reset_password'] && !$randomPassword)
			{
				$user->set('password', UserHelper::hashPassword($data['password']));
				$user->set('requireReset', 1);
			}
			elseif ($randomPassword)
			{
				$user->set('password', UserHelper::hashPassword($data['password']));
				$user->set('requireReset', 0);
			}

		$message = '';

		$date = Factory::getDate();

		if (!$data['id'])
		{
			$user->set('registerDate', $date->toSql());
		}

		// True on success, false otherwise

		$user->save();

		return $user;
	}

	/**
	 * Method to create joomla user using the easysocial API
	 *
	 * @param   array  $data  The form data
	 *
	 * @return userid
	 *
	 * @throws Exception
	 * @since 1.6
	 */
	public function createESuser($data)
	{
		if (!class_exists('ES'))
		{
			$path = JPATH_ADMINISTRATOR . "/components/com_easysocial/includes/easysocial.php";

			JLoader::register('ES', $path);
			JLoader::load('ES');
		}

		// Get field id based on unique name of field @TODO need to be get from easysocial but there no method
		$fieldid = $this->getESFieldId("JOOMLA_FULLNAME");

		// Build ES field name and object
		$data[SOCIAL_FIELDS_PREFIX . $fieldid] = new stdClass;
		$data[SOCIAL_FIELDS_PREFIX . $fieldid]->first = $data['first_name'];
		$data[SOCIAL_FIELDS_PREFIX . $fieldid]->middle = '';
		$data[SOCIAL_FIELDS_PREFIX . $fieldid]->last = $data['last_name'];
		$data[SOCIAL_FIELDS_PREFIX . $fieldid]->name = $data['name'];

		// Process required data for ES user registration
		ES::load("user");

		// Default user profile
		$profileId = 1;

		// Create empty ES user object
		$user = new SocialUser;

		// Create an options array for custom fields
		$options = array();

		// Set the profile id
		$options['profile_id'] = $profileId;

		// Set the group
		$options['group'] = SOCIAL_FIELDS_GROUP_USER;

		// Since this is at auto registration so we assume admin is editing someone else.
		$options['visible'] = SOCIAL_PROFILES_VIEW_REGISTRATION;

		// Get fields model
		$fieldsModel = ES::model('Fields');

		// Get the custom fields
		$fields = $fieldsModel->getCustomFields($options);

		// Now create profile type object for default config
		$profile = FD::table('Profile');
		$profile->load($profileId);

		$user->bind($data);

		$model = ES::model('Users');
		$newUser = $model->create($data, $user, $profile);

		if (!$newUser)
		{
			echo $model->getError();
			$user->setError($model->getError(), SOCIAL_MSG_ERROR);

			return $user;
		}

		else
		{
			$user = $newUser;

			// Reconstruct args
			$args = array(&$data, &$user);

			// Get the fields lib
			$fieldsLib = ES::fields();

			// @trigger onEditAfterSave
			$fieldsLib->trigger('onAdminEditAfterSave', SOCIAL_FIELDS_GROUP_USER, $fields, $args);

			// Bind the custom fields for the user.
			$user->bindCustomFields($data);

			// Approve user
			$user->approve(false);

			return $user;
		}
	}

	/**
	 * Create a random character generator for password
	 *
	 * @param   string  $uniquename  custom field unique name
	 *
	 * @return  int/false the field id
	 *
	 * @since    1.6
	 */
	public function getESFieldId($uniquename = "")
	{
		if ($uniquename)
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select($db->qn("id"));
			$query->from($db->qn("#__social_fields"));
			$query->where($db->qn("unique_key") . " = " . $db->q($uniquename));
			$db->setQuery($query);

			return $db->loadResult();
		}
	}

	/**
	 * Function to send mail to registered user
	 *
	 * @param   array   $data            The form data
	 *
	 * @param   vachar  $randomPassword  randam password
	 *
	 * @param   vachar  $key             key
	 *
	 * @return userid
	 *
	 * @throws Exception
	 *
	 */
	public function SendMailNewUser($data, $randomPassword, $key)
	{
		$app = Factory::getApplication();

		// Added to fetch data of user form
		$input = Factory::getApplication()->input;

		$options = new Registry;

		$recipients = array (
			// Add specific to, cc (optional), bcc (optional)
			'email' => array (
				'to' => array ($data['email'])
			)
		);

		$replacements = new stdClass;
		$replacements->user = new stdClass;

		$replacements->user->password = $randomPassword;
		$replacements->user->username = $data['username'];
		$replacements->user->name = $data['name'];
		$replacements->user->uname = $data['email'];
		$replacements->user->title = $data['title'];
		$replacements->user->siteurl = JURI::root();

		// Get com_tjnotifications component status
		if (ComponentHelper::getComponent('com_tjnotifications', true)->enabled)
		{
			$res = Tjnotifications::send("com_multiagency", $key, $recipients, $replacements, $options);
		}

		return true;
	}

	/**
	 * This is to check the user in multiagency user or not
	 *
	 * @param   integer  $userId    The user id
	 * @param   integer  $agencyId  The agency id
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function checkMultiagencyUser($userId, $agencyId)
	{
		if ($userId && $agencyId)
		{
			$result = RBACL::getRoleByUser($userId, 'com_multiagency', $agencyId);

			if (count($result) > 0 )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if installed
	 *
	 * @param   String  $component  (string) name of component
	 *
	 * @return  if exit override view then return path
	 *
	 * @since 1.0
	 */
	public function Checkifinstalled($component)
	{
		$componentpath = JPATH_ROOT . '/components/' . $component;

		if (Folder::exists($componentpath))
		{
			return 1;
		}
	}

	/**
	 * Method to get an agency name
	 *
	 * @param   string  $multiagencyId  multiagencyId to be checked
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function getmultiagency($multiagencyId)
	{
		if ($multiagencyId)
		{
			$db = Factory::getDbo();
			$query = "select title from #__tjmultiagency_multiagency where id=" . $db->q($multiagencyId);
			$db->setQuery($query);
			$results = $db->loadResult();

			return $results;
		}
	}

	/**
	 * Method to get an agency name
	 *
	 * @param   string   $multiagencyId       multiagencyId to be checked
	 * @param   boolean  $byCourseEnrollment  course wise count
	 *
	 * @return  int An count of users.
	 */
	public function getAgencyEnrollment($multiagencyId, $byCourseEnrollment = false)
	{
		if ($multiagencyId)
		{
			// Create a new query object
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$select = 'COUNT(DISTINCT(eu.id)) as enrolled';

			// Select the required fields from the table.

			$query->from($db->quoteName('#__tjlms_enrolled_users') . ' AS eu');
			$query->join('INNER', '#__tjsu_users AS su ON su.user_id = eu.user_id');
			$query->join('INNER', '#__tjmultiagency_licences AS ml ON (ml.multiagency_id = su.client_id AND eu.course_id = ml.course_id)');

			if ($byCourseEnrollment)
			{
				$select = ' count(DISTINCT(eu.id)), su.user_id, su.client_id, ml.course_id, ml.id';
				$query->group('su.client_id, ml.course_id, ml.id');
			}

			$query->select($select);

			$query->where($db->quoteName('su.client_id') . '=' . $db->quote((int) $multiagencyId));
			$query->where($db->quoteName('su.client') . ' = "com_multiagency"');

			$db->setQuery($query);
			$results = $db->loadObjectList();

			return $results;
		}

		return 0;
	}

	/**
	 * This method will return the role id of given client content TODO - need to move in subusers
	 *
	 * @param   integer  $userId     Id of the user for which to check authorisation.
	 * @param   string   $client     The name of the client to authorise. com_content
	 * @param   integer  $contentId  The content key. null check with role and allowed actions.
	 * @param   integer  $state      State of the role
	 *
	 * @return  mixed An array of data on success, false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getRoleClient($userId, $client = 'com_multiagency', $contentId = null, $state = 0)
	{
		if ($userId)
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select('distinct(tu.role_id), tu.client_id');
			$query->from($db->quoteName('#__tjsu_users', 'tu'));

			if ($state)
			{
				$query->join('INNER', $db->quoteName('#__tjsu_roles', 'tr')
			. ' ON (' . $db->quoteName('tr.id') . ' = ' . $db->quoteName('tu.role_id') . ')');

				$query->where($db->quoteName('tr.state') . " = " . (int) $state);
			}

			$query->where($db->quoteName('tu.user_id') . " = " . (int) $userId);

			if ($client)
			{
				$query->where($db->quoteName('tu.client') . " = " . $db->q($client));
			}

			if (!empty($contentId))
			{
				if (is_array($contentId))
				{
					$query->where($db->quoteName('tu.client_id') . 'IN (' . implode(',', $db->quote($contentId)) . ')');
				}
				else
				{
					$query->where($db->quoteName('tu.client_id') . " = " . $db->quote($contentId));
				}
			}

			$db->setQuery($query);

			return $db->loadAssocList();
		}
	}

	/**
	 * This method will return the role id of given client content
	 *
	 * @param   integer  $userId    Id of the user for which to check authorisation.
	 * @param   string   $roleid    The name of the client to authorise. com_content
	 * @param   integer  $clientId  The content key. null check with role and allowed actions.
	 * @param   string   $client    The client to authorise. com_content
	 *
	 * @return  mixed An array of data on success, false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getIdsUserAgencyRoleMap($userId, $roleid, $clientId = null, $client = 'com_multiagency')
	{
		if ($userId)
		{
			$db        = Factory::getDbo();
			$query     = $db->getQuery(true);
			$params    = ComponentHelper::getParams('com_dpe');
			$dpeTools  = (array) json_decode($params->get('dpe_role_ids'));
			$coreRoles = array_keys($dpeTools);

			$query->select('id');
			$query->from($db->quoteName('#__tjsu_users'));
			$query->where($db->quoteName('user_id') . " = " . (int) $userId);
			$query->where($db->quoteName('client') . " = " . $db->q($client));

			if (!in_array($roleid, $coreRoles))
			{
				$query->where($db->quoteName('role_id') . " = " . $db->q($roleid));
			}

			if ($clientId)
			{
				$query->where($db->quoteName('client_id') . " = " . $db->q($clientId));
			}

			$db->setQuery($query);

			return $db->loadColumn();
		}
	}

	/**
	 * This method will return list of licenses by Consultant or All licenses
	 *
	 * @param   integer  $userId  Id of the DPE lead consultant.
	 *
	 * @return  object  License object list
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getLicensesByDpeConsultant($userId = 0)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select(array ('ml.id', 'm.title', 'sl.title AS sla_title', 'm.id AS client_id', 'ml.start_date', 'ml.end_date'));
		$query->from($db->quoteName('#__tjmultiagency_licences', 'ml'));
		$query->join('INNER', $db->quoteName('#__tjmultiagency_multiagency', 'm')
			. ' ON (' . $db->quoteName('m.id') . ' = ' . $db->quoteName('ml.multiagency_id') . ')');
		$query->join('INNER', $db->quoteName('#__tj_sla_cluster_xref', 'scx')
			. ' ON (' . $db->quoteName('ml.id') . ' = ' . $db->quoteName('scx.license_id') . ')');
		$query->join('INNER', $db->quoteName('#__tj_slas', 'sl')
			. ' ON (' . $db->quoteName('sl.id') . ' = ' . $db->quoteName('scx.sla_id') . ')');

		$user = Factory::getUser();

		// School Manager, School Admin can see only own school associated activities.
		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			$query->join('INNER', $db->quoteName('#__tj_cluster_nodes', 'cnode')
					. ' ON (' . $db->quoteName('cnode.cluster_id') . ' = ' . $db->quoteName('scx.cluster_id') . ')');
			$query->where('cnode.user_id = ' . (int) $userId);
		}

		// Get slactivities page filters
		$input       = Factory::getApplication()->input;
		$filters     = $input->get('filter');
		$stateFilter = $input->get('state');

		// By default load active licence if no state
		if (!isset($filters['state']))
		{
			$filters['state'] = $stateFilter ? $stateFilter : 1;
		}

		if (!$input->get('licence_id') && $input->get('layout') === "edit")
		{
			// Show active and upcoming licences on add activity view
			$filters['state'] = array(1,3);
		}

		// On changing filter show licence as per status
		if (is_numeric($filters['state']))
		{
			$query->where($db->quoteName('ml.state') . ' = ' . $filters['state']);
		}
		elseif (is_array($filters['state']))
		{
			$query->where($db->quoteName('ml.state') . ' IN (' . implode(',', ArrayHelper::toInteger($filters['state'])) . ')');
		}

		$query->order($db->qn('m.title') . ' asc');

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * This method will return multiagency object
	 *
	 * @param   integer  $licenseId  Id of School License.
	 *
	 * @return  object  Multiagency object list
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getMultiagencyByLicense($licenseId = 0)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select(array ('ml.id', 'm.title', 'sl.title AS sla_title'));
		$query->from($db->quoteName('#__tjmultiagency_licences', 'ml'));
		$query->join('INNER', $db->quoteName('#__tjmultiagency_multiagency', 'm')
			. ' ON (' . $db->quoteName('m.id') . ' = ' . $db->quoteName('ml.multiagency_id') . ')');

		$query->join('LEFT', $db->quoteName('#__tj_sla_cluster_xref', 'scx')
			. ' ON (' . $db->quoteName('ml.id') . ' = ' . $db->quoteName('scx.license_id') . ')');
		$query->join('LEFT', $db->quoteName('#__tj_slas', 'sl')
			. ' ON (' . $db->quoteName('sl.id') . ' = ' . $db->quoteName('scx.sla_id') . ')');

		$query->where($db->quoteName('ml.id') . " = " . (int) $licenseId);

		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * download the file
	 *
	 * @param   STRING  $file  file path eg /var/www/j30/media/com_quick2cart/qtc_pack.zip
	 *
	 * @return  html
	 */
	public function download($file)
	{
		if (!File::exists($file))
		{
			echo Text::_("COM_MULTIAGENCY_NO_FILE_FOUND");

			return false;
		}

		// $productHelper = new productHelper;
		clearstatcache();

		// If set the option for direct link to the file
		$filename       = basename($file);
		$ctype          = 'application/x-php';

		ob_end_clean();

		//  Needed for MS IE - otherwise content disposition is not used?
		if (ini_get('zlib.output_compression'))
		{
			ini_set('zlib.output_compression', 'Off');
		}

		header("Cache-Control: public, must-revalidate");
		header('Cache-Control: pre-check=0, post-check=0, max-age=0');
		header("Expires: 0");
		header("Content-Description: File Transfer");

		// Header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Content-Type: " . $ctype);
		$len = filesize($file);
		header("Content-Length: " . (string) $len);

		//  If valid extention
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		//  set_time_limit doesn't work in safe mode
		if (!ini_get('safe_mode'))
		{
			@set_time_limit(0);
		}

		@readfile($file);

		jexit();
	}

	/**
	 * This method will return list of roles assigned by licence using licence xref table tools TODO : This need to moved to core
	 *
	 * @param   integer  $multiagencyId  Id of School License.
	 *
	 * @return  Array  Multiagency object list
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getLicenceAssignedRoleIds($multiagencyId)
	{
		// Check license is available for school
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
		$licenceTable = Table::getInstance('licence', 'MultiagencyTable');

		$licenceTable->load(array('multiagency_id' => $multiagencyId, 'state' => 1));

		$licenceModel = Multiagency::model('licence', array('ignore_request' => true));

		$licenceId = 0;

		if (property_exists($licenceTable, 'id'))
		{
			$licenceId = $licenceTable->id;
		}

		if ($licenceId)
		{
			$savedClients = $licenceModel->getSavedClientsFromLicenceXref($licenceId);
			$params       = ComponentHelper::getParams('com_dpe');
			$allTools     = new Registry($params->get('allTools'));
			$allToolsdata = $allTools->get('tools');

			$licenceAssignedRoleIds = array();

			foreach ($savedClients as $savedClient)
			{
				if ($allToolsdata->$savedClient->role_ids)
				{
					$licenceAssignedRoleIds = array_merge($licenceAssignedRoleIds, $allToolsdata->$savedClient->role_ids);
				}
			}

			return $licenceAssignedRoleIds;
		}
	}

	/**
	 * This method will return the related role id of given client content id and role id TODO : This method needs to go in core
	 *
	 * @param   integer  $userId     Id of the user for which to check authorisation.
	 * @param   string   $client     The name of the client to authorise. com_content
	 * @param   integer  $contentId  The content key. null check with role and allowed actions.
	 * @param   integer  $roleId     Role id
	 *
	 * @return  mixed An array of data on success, false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getRelatedRoleUserData($userId, $client = 'com_multiagency', $contentId = 0, $roleId =0)
	{
		if (!$userId || !$contentId)
		{
			return false;
		}

		$params                   = ComponentHelper::getParams('com_multiagency');
		$dpeParams                = ComponentHelper::getParams('com_dpe');
		$relatedRoles             = new Registry($dpeParams->get('related_roles'));
		$MultiagencyTrusteeRoleId = $params->get('organization_trustee_role_id', 'INT');
		$memberRoleId             = $params->get('member_role_id', 'INT');

		if ($roleId != $memberRoleId && $roleId != $MultiagencyTrusteeRoleId)
		{
			return false;
		}

		$usersModel = RBACL::model('users', array('ignore_request' => true));
		$usersModel->setState('filter.user_id', (int) $userId);
		$usersModel->setState('filter.client', $client);
		$usersModel->setState('filter.client_id', (int) $contentId);
		$usersModel->setState('filter.role_id', $relatedRoles->get('related_roles'));
		$usersData = $usersModel->getItems();

		$relatedData = array();

		foreach ($usersData as $key => $user)
		{
			$relatedData[$key]['role_id']   = $user->role_id;
			$relatedData[$key]['client_id'] = $user->client_id;
		}

		return $relatedData;
	}
}
