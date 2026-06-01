<?php
/**
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of allocated cluster
 *
 * @since  __DEPLOY_VERSION__
 */

class JFormFieldAssignee extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $type = 'Assignee';

	/**
	 * Method to get a list of options for cluster field.
	 *
	 * @return array An array of JHtml options.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		$user         = Factory::getUser();
		$clusterAware = $this->getAttribute("clusterAware");

		// Initialize array to store dropdown options
		$options = array();

		// If user is not logged in user then dont show any users data
		if (!$user->id)
		{
			return $options;
		}

		$fields = $this->form->getFieldset();

		// Check if cluster field is there in the form
		$clusterFieldId = str_replace('assignee', 'clusterclusterid', $this->id);

		// If cluster field is not there in the form or if the assignee field is not cluster aware then show list of all users
		if (!array_key_exists($clusterFieldId, $fields) || !$clusterAware)
		{
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_users/models');
			$userModel = JModelLegacy::getInstance('Users', 'UsersModel', array('ignore_request' => true));
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

		$doc = Factory::getDocument();
		$doc->addScript(JUri::root() . 'administrator/components/com_tjfields/assets/js/assigneefield.min.js');

		$data = $this->getLayoutData();

		$fieldValue = (array) $data['field']->value;  // DPE hack

		// Used to keep pre selected user value in 'Assignee' type field
		echo '<input name="assignee_user" id="' . $this->id . 'value' . '" type="hidden" value="' . implode(',', $fieldValue) . '" />';

		return $options;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getInput()
	{
		$fields       = $this->form->getFieldset();
		$clusterAware = $this->getAttribute("clusterAware");
		$multiple     = $this->getAttribute("multiple");

		if ($clusterAware)
		{
			// Check if cluster field is there in the form
			$clusterFieldId = str_replace('assignee', 'clusterclusterid', $this->id);

			// If cluster field is not there in the form then show list of all users
			if (array_key_exists($clusterFieldId, $fields))
			{
				// Add script to initialise assignee field
				$document = JFactory::getDocument();
				$document->addScriptDeclaration('jQuery(document).ready(function() {
					var dataFields = {cluster_id: 0, user_id: 0, multiple: "' . $multiple . '"};
					assignee.setUsers(dataFields, "' . $clusterFieldId . '");
				});');

				// Add script to update assignee field onchange of cluster field
				$document->addScriptDeclaration('jQuery(document).ready(function() {
					jQuery("#' . $clusterFieldId . '").change(function(){
						assignee.updateAssigneeField(this,"' . $multiple . '");
					});
				});');
			}
		}

		return parent::getInput();
	}
}
