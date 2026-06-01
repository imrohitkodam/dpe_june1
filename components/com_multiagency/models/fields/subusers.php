<?php
/**
 * @package    Subusers
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

JFormHelper::loadFieldClass('list');
JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);
/**
 * Supports an HTML select list of allocated agencies
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldSubusers extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.0.0
	 */
	protected $type = 'subusers';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		$subUsersModel = RBACL::model("users", array('ignore_request' => true));

		$coreAction = $this->getAttribute('action');
		$subUserClient = $this->getAttribute('client');
		$subUserCoach = SubusersAction::loadActionByCode($coreAction, $subUserClient);

		$subUserRole = $subUserCoach->getAuthorizedRoles();

		$subUsersModel->setState('filter.role_id', $subUserRole[0]);
		$subUsersModel->setState('filter.client', $subUserClient);
		$subUsersModel->setState('list.ordering', 'name');
		$subUsersModel->setState('list.direction', 'asc');

		// Check if any coach assigned
		$dpeAdminList = $subUsersModel->getItems();

		$options[] = HTMLHelper::_('select.option', "", Text::_('COM_MULTIAGENCY_SELECT_DPE_STAFF'));

		foreach ($dpeAdminList as $staff)
		{
			$options[] = JHTML::_('select.option', $staff->user_id, Factory::getUser($staff->user_id)->name);
		}

		return $options;
	}
}
