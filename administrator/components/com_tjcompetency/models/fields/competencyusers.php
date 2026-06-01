<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

JFormHelper::loadFieldClass('list');
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

JLoader::import('components.com_tjcompetency.includes.tjcompetency', JPATH_ADMINISTRATOR);

/**
 * Custom field
 *
 * @since  1.0.0
 */
class JFormFieldCompetencyUsers extends JFormFieldList
{
	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   11.4
	 */
	public function getOptions()
	{
		$options = array();

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		// Create the base select statement.
		$query->select('DISTINCT (`a`.`user_id`), u.name, u.block');
		$query->from($db->quoteName('#__tjcompetency_skill_user_content_map', 'a'));
		$query->join('LEFT', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('a.user_id') . ' = ' . $db->quoteName('u.id') . ')');

		$db->setQuery($query);
		$rows = $db->loadObjectList();

		if (!empty($rows))
		{
			foreach ($rows as $user)
			{
				if ($user->block == 1)
				{
					$options[] = JHtml::_('select.option', $user->user_id, '[' . $user->name . ']');
				}
				else
				{
					$options[] = JHtml::_('select.option', $user->user_id, $user->name);
				}
			}
		}

		return $options;
	}
}
