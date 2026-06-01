<?php
/**
 * @package    DPE
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Supports an HTML select list of RS ticket staff list
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldStaff extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.0.0
	 */
	protected $type = 'Staff';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string | void    The field input markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		$staffList             = array();
		$options               = array();
		$user                  = Factory::getUser();

		if (!$user->id)
		{
			return $options;
		}

		// RS ticket Staff list
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select(array('u.id', 'u.name'));
		$query->from('`#__users` AS u');
		$query->join('LEFT', $db->quoteName('#__rsticketspro_staff', 'rs') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('rs.user_id'));
		$query->join('LEFT', $db->quoteName('#__rsticketspro_staff_to_department', 'rsd')
. ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('rsd.user_id')
);

		$query->join('LEFT', $db->quoteName('#__rsticketspro_departments', 'rd') . ' ON ' .
$db->quoteName('rsd.department_id') . ' = ' . $db->quoteName('rd.id')
);
		$query->where($db->quoteName('rd.published') . '= 1');
		$query->where('u.block = 0');
		$query->group($db->quoteName('u.id'));
		$query->order($db->quoteName('u.name') . ' ASC');

		$db->setQuery($query);

		$staffList = $db->loadObjectList();

		// Return on empty list
		if (empty($staffList))
		{
			return $options;
		}

		// Construct Drop Down
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_DPE_MY_TICKETS_STAFF_FILTER_SELECT_OPTION'));
		$options[] = HTMLHelper::_('select.option', 0, Text::_('RST_UNASSIGNED'));
		$options[] = HTMLHelper::_('select.options', Text::_('COM_DPE_MY_TICKETS_SELECT_DPE_STAFF'));

		// RS ticket Staff list
		foreach ($staffList as $staff)
		{
			$options[] = HTMLHelper::_('select.option', $staff->id, $staff->name);
		}

		$options[] = HTMLHelper::_('select.options', '');

		return $options;
	}
}
