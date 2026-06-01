<?php
/**
 * @package     Jlike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

JFormHelper::loadFieldClass('list');

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

/**
 * Custom field to list all public and logged-in user's private certificate templates
 *
 * @since  1.0.0
 */
class JFormFieldGetClientList extends JFormFieldList
{
	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   11.4
	 */
	protected function getOptions()
	{
		$options = array();

		$user = Factory::getUser();
		$db   = Factory::getDbo();

		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_JLIKE_RECOMMENDATION_CONTENT_TYPE_SELECT'));

		// Create a new query object.
		$query = $db->getQuery(true);

		// DPE Hack to get the client which is related to notification manager
		$query->select('DISTINCT content.element');
		$query->from($db->quoteName('#__jlike_content', 'content'));
		$query->join('INNER', $db->quoteName('#__jlike_todos', 'todo')
			. ' ON ' . $db->quoteName('todo.content_id') . ' = ' . $db->quoteName('content.id')
		);
		$query->join('INNER', $db->quoteName('#__jlike_todos_cluster_xref', 'tcxref')
			. ' ON ' . $db->quoteName('tcxref.todo_id') . ' = ' . $db->quoteName('todo.id')
		);

		// DPE Hack end

		$db->setQuery($query);

		$listobjects = $db->loadObjectList();

		if (!empty($listobjects))
		{
			foreach ($listobjects as $obj)
			{
				if ($obj->element)
				{
					$client    = str_replace(".", "_", $obj->element);
					$langConst = strtoupper("COM_JLIKE_CLIENT_" . $client);
					$options[] = JHtml::_('select.option', $obj->element, TEXT::_($langConst));
				}
			}
		}

		return $options;
	}
}
