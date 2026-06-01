<?php
/**
 * @package    Subusers
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);

/**
 * Supports an HTML select list of allocated agencies
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldLeadconsultants extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.0.0
	 */
	protected $type = 'leadconsultants';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string | void    The field input markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		// Get user groups as per Config
		$params                = ComponentHelper::getParams('com_multiagency');
		$dpeAdminGroupId       = (int) $params->get('multiagency_admin_group', '0');
		$leadConsultantGroupId = (int) $params->get('multiagency_leadconsultant_group', '0');
		$dpeAdminList          = array();
		$lcList                = array();
		$options               = array();
		$user                  = Factory::getUser();

		if ($dpeAdminGroupId)
		{
			// Get DPE Admin List
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select(array('u.id', 'u.name'));
			$query->from('`#__users` AS u');
			$query->join('LEFT', $db->quoteName('#__user_usergroup_map', 'map') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('map.user_id'));
			$query->where($db->quoteName('map.group_id') . '= ' . (int) $dpeAdminGroupId);
			$query->where('u.block = 0');
			$query->group($db->quoteName('u.id'));
			$query->order($db->quoteName('u.name') . ' ASC');

			$db->setQuery($query);

			$dpeAdminList = $db->loadObjectList();
		}

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			return $options;
		}

		if ($leadConsultantGroupId)
		{
			// Get External LC List
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select(array('u.id', 'u.name'));
			$query->from('`#__users` AS u');
			$query->join('LEFT', $db->quoteName('#__user_usergroup_map', 'map') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('map.user_id'));
			$query->where($db->quoteName('map.group_id') . '= ' . (int) $leadConsultantGroupId);
			$query->where('u.block = 0');
			$query->group($db->quoteName('u.id'));
			$query->order($db->quoteName('u.name') . ' ASC');

			$db->setQuery($query);

			$lcList = $db->loadObjectList();
		}

		// Construct Drop Down
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_MULTIAGENCY_SELECT_DPE_STAFF'));
		$options[] = HTMLHelper::_('select.options', Text::_('COM_MULTIAGENCY_DPE_ADMIN_LIST_TITLE'));

		// Dpe admin users
		foreach ($dpeAdminList as $dpeAdminUser)
		{
			$options[] = HTMLHelper::_('select.option', $dpeAdminUser->id, $dpeAdminUser->name);
		}

		$options[] = HTMLHelper::_('select.options', '');

		if (!empty($lcList))
		{
			$options[] = HTMLHelper::_('select.options', Text::_('COM_MULTIAGENCY_EXTERNAL_LC_LIST_TITLE'));
		}

		// External LC
		foreach ($lcList as $lc)
		{
			$options[] = HTMLHelper::_('select.option', $lc->id, $lc->name);
		}

		return $options;
	}
}
