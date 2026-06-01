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
use Joomla\CMS\Table\Table;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Component\Users\Administrator\Model\UserModel;


require_once JPATH_COMPONENT . '/controller.php';

/**
 * Users list controller class.
 *
 * @since  1.6
 */
class MultiagencyControllerUsers extends \Joomla\CMS\MVC\Controller\BaseController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional
	 * @param   array   $config  Configuration array for model. Optional
	 *
	 * @return  object | Boolean  The model
	 *
	 * @since	1.6
	 */
	public function &getModel($name = 'Users', $prefix = 'MultiagencyModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

	/**
	 * Method to block the user.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.0
	 */
	public function blockUser()
	{
		$app   = Factory::getApplication();
		$user       = Factory::getUser();
		$removeOwnUser  = $user->authorise('core.own.removeuser', 'com_multiagency');
		$removeUser  = $user->authorise('core.removeuser', 'com_multiagency');

		if (!$removeOwnUser && !$removeUser)
		{
			$app->enqueueMessage(Text::_('COM_MULTIAGENCY_USERS_NO_DELETE_PERMITTED'), 'error');

			return false;
		}

		$ids    = $app->input->get('cid', array(), 'array');
		$agencies = $app->input->getInt('agencies');

		if (empty($ids))
		{
			$app->enqueueMessage(Text::_('COM_MULTIAGENCY_USERS_NO_ITEM_SELECTED'), 'error');

			return false;
		}

		JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));

		// Check logged-in user having a remove user permission of selected user's cluster
		if (!$user->authorise('core.admin') && !$user->authorise('core.removeuser', 'com_multiagency'))
		{
			foreach ($ids as $id)
			{
				$clusters = $clusterUserModel->getUsersClusters($id);

				foreach ($clusters as $cluster)
				{
					$allowRemoveUser = RBACL::check($user->id, 'com_multiagency', 'core.own.removeuser', 'com_multiagency', $cluster->client_id);

					if ($allowRemoveUser)
					{
						$allowedUsers[] = $id;
					}
				}
			}

			$ids = $allowedUsers;
		}

		// Get user groups as per name
		$leadConsultantGroupId = 0;
		$usersGroups = array();
		$leadConsultantGroup = Table::getInstance('Usergroup', 'JTable');
		$leadConsultantGroup->load(array('title' => 'External Lead Consultant'));

		if (property_exists($leadConsultantGroup, 'id'))
		{
			$leadConsultantGroupId = $leadConsultantGroup->id;
		}

		unset($allowedUsers);
		$allowedUsers = array();

		foreach ($ids as $id)
		{
			$usersGroups = Factory::getUser($id)->groups;

			if (!in_array($leadConsultantGroupId, $usersGroups))
			{
				$allowedUsers[] = $id;
			}
		}

		$ids = $allowedUsers;
				
		// JLoader::register('UsersModelUser', JPATH_ADMINISTRATOR . '/components/com_users/models/user.php');
		// $usersModelUser = BaseDatabaseModel::getInstance('User', 'UsersModel', array('ignore_request' => true));

		// Block the users
	    $usersModelUser = new UserModel();
		$usersModelUser->block($ids);

		if (count($ids))
		{	

			//DPE hack
			foreach ($ids as $key => $id)
			{

				$checkedValue =  array('user_id' => $id);
				Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
				$tjlmsClusterXrefTable = Table::getInstance('JobtitleExtend', 'DpeTable');
				
				if ($tjlmsClusterXrefTable->load($checkedValue))
				{
					$tjlmsClusterXrefTable->delete();
				};
				
			}
			// Hack End
			$this->setMessage(Text::plural('COM_MULTIAGENCY_USER_DELETED_SUCCESSFULLY', count($ids)));
		}

		// Joomla 4 redirect to the users lsit page

		$menu       = $app->getMenu();
		$detailMenu = $menu->getItems('link', 'index.php?option=com_multiagency&view=users', true);
		$redirlink = 'index.php?option=com_multiagency&view=users&Itemid=' . $detailMenu->id;

		$this->setRedirect(Route::_($redirlink, false));
	}
}
