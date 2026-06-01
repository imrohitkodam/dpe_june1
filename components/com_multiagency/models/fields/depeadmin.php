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
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);
/**
 * Supports an HTML select list of allocated agencies
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldDepeadmin extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.0.0
	 */
	protected $type = 'depeadmin';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		$dpeAdminGroup = Table::getInstance('Usergroup', 'JTable');
		$dpeAdminGroup->load(array('title' => 'DPE Admin'));

		if (property_exists($dpeAdminGroup, 'id'))
		{
			$dpeAdminGroupId = $dpeAdminGroup->id;
		}

		// Get a db connection.
		$db = Factory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select all records from the user which have DPE Admin group
		$query->select(array('u.id', 'u.name'));
		$query->from('`#__users` AS u');
		$query->join('LEFT', $db->quoteName('#__user_usergroup_map', 'map') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('map.user_id'));
		$query->where($db->qn('map.group_id') . ' = ' . (int) $dpeAdminGroupId);

		$query->where('u.block = 0');
		$query->group($db->quoteName('u.id'));
		$query->order($db->quoteName('u.name') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		// Load the results as a list of stdClass objects (see later for more options on retrieving data).
		$dpeAdminList = $db->loadObjectList();

		$options[] = HTMLHelper::_('select.option', 'all', 'All');

		foreach ($dpeAdminList as $staff)
		{
			$options[] = HTMLHelper::_('select.option', $staff->id, $staff->name);
		}

		return $options;
	}
}
