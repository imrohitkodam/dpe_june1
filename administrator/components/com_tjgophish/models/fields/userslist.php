<?php
/**
 * @package     TjGoPhish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of users
 *
 * @since  1.0.0
 */

class JFormFieldUsersList extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $type = 'Userslist';

	/**
	 * Method to get a list of options for field.
	 *
	 * @return array An array of JHtml options.
	 *
	 * @since   1.0.0
	 */
	protected function getOptions()
	{
		$doc = Factory::getDocument();
		$doc->addScript(JUri::root() . 'media/com_tjgophish/js/ui/userslistfield.min.js');

		$user = Factory::getUser();
		$clusterAware = $this->getAttribute("clusterAware");
		$clusterField = $this->getAttribute("clusterField");
		$multiple = $this->getAttribute("multiple");
		$fieldName = $this->getAttribute("name");

		// If user is not logged in user then dont show any users data
		if (!$user->id)
		{
			return $options;
		}

		$firstOptionLabel = ($multiple) ? Text::_('COM_TJGOPHISH_SELECT_USERS') : Text::_('COM_TJGOPHISH_SELECT_USER');

		// Initialize array to store dropdown options
		$options = array();
		$options[] = HTMLHelper::_('select.option', "", $firstOptionLabel);
		$options[0]->disable = true;

		$fields = $this->form->getFieldset();

		// Check if cluster field is there in the form
		$clusterFieldId = str_replace($fieldName, $clusterField, $this->id);

		// If cluster field is not there in the form or if the userslist field is not cluster aware then show list of all users
		if (!array_key_exists($clusterFieldId, $fields) || !$clusterAware)
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_users/models');
			$userModel = BaseDatabaseModel::getInstance('Users', 'UsersModel', array('ignore_request' => true));
			$userModel->setState('filter.state', 0);
			$allUsers = $userModel->getItems();

			if (!empty($allUsers))
			{
				foreach ($allUsers as $user)
				{
					$options[] = HTMLHelper::_('select.option', $user->id, trim($user->username));
				}
			}
		}
		else
		{
			$fields = $this->form->getFieldset();
			$clusterId = $fields[$clusterFieldId]->value;

			$clusterUsers = array();

			// Check cluster selected or not
			if ($clusterId)
			{
				JLoader::import('components.com_cluster.models.clusterusers', JPATH_ADMINISTRATOR);
				$clusterObj = BaseDatabaseModel::getInstance('ClusterUsers', 'ClusterModel', array('ignore_request' => true));
				$clusterObj->setState('filter.block', 0);
				$clusterObj->setState('filter.cluster_id', $clusterId);
				$clusterObj->setState('list.group_by_user_id', 1);
				$clusterUsers = $clusterObj->getItems();
			}

			if (!empty($clusterUsers))
			{
				foreach ($clusterUsers as $user)
				{
					$options[] = HTMLHelper::_('select.option', $user->user_id, trim($user->uname . ' (' . $user->uemail . ')'));
				}
			}
		}

		return $options;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.0.0
	 */
	protected function getInput()
	{
		$clusterAware = $this->getAttribute("clusterAware");
		$clusterField = $this->getAttribute("clusterField");
		$fieldName = $this->getAttribute("name");

		if ($clusterAware)
		{
			$fields = $this->form->getFieldset();

			// Check if cluster field is there in the form
			$clusterFieldId = str_replace($fieldName, $clusterField, $this->id);

			// If cluster field is not there in the form then show list of all users
			if (array_key_exists($clusterFieldId, $fields))
			{
				// Add script to initialise userslist field
				$document = JFactory::getDocument();

				// Add script to update userslist field onchange of cluster field
				$document->addScriptDeclaration('jQuery(document).ready(function() {
					jQuery("#' . $clusterFieldId . '").change(function(){
						userslist.updateUserslistField("' . $clusterFieldId . '", "' . $this->id . '");
					});
				});');
			}
		}

		return parent::getInput();
	}
}
