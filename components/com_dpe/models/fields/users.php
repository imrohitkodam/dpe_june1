<?php
/**
 * @package    Subusers
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\UserField;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of allocated agencies
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldUsers extends \JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.0.0
	 */
	protected $type = 'users';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$multiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel');
		$agencies = $multiagencyModel->getAllocatedAgencies();
		$agencyIds = array();

		foreach ($agencies as $agencyData)
		{
			$agencyIds[] = $agencyData->id;
		}

		// Initialize array to store dropdown options
		$options = array();

		$options[] = HTMLHelper::_('select.option', "", Text::_('COM_DPE_SELECT_USERS'));

		if (count($agencyIds) > 0)
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('distinct(u.id), u.name');
			$query->from($db->quoteName('#__tjsu_users', 'su'));
			$query->join('INNER', '#__users AS u ON su.user_id = u.id');
			$query->where($db->quoteName('su.client_id') . ' in (' . implode(',', $agencyIds) . ')');
			$query->where($db->qn('su.client') . " = 'com_multiagency'");
			$query->where($db->qn('u.block') . ' = 0');
			$query->order($db->escape('u.name' . ' ' . 'asc'));
			$db->setQuery($query);
			$users = $db->loadObjectList();

			foreach ($users as $user)
			{
				$options[] = JHTML::_('select.option', $user->id, $user->name);
			}
		}

		return $options;
	}
}
