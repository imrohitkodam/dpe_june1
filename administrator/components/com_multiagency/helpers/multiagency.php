<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Object\CMSObject;

/**
 * Multiagency helper.
 *
 * @since  1.6
 */
class MultiagencyHelpersMultiagency
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $vName  string
	 *
	 * @return void
	 */
	public static function addSubmenu($vName = '')
	{
		JHtmlSidebar::addEntry(
			Text::_('COM_MULTIAGENCY_TITLE_MULTIAGENCES'),
			'index.php?option=com_multiagency&view=multiagences',
			$vName == 'multiagences'
		);

		JHtmlSidebar::addEntry(
			Text::_('COM_MULTIAGENCY_TITLE_LICENCES'),
			'index.php?option=com_multiagency&view=licences',
			$vName == 'licences'
		);

		if (ComponentHelper::isEnabled('com_fields'))
		{
			JHtmlSidebar::addEntry(
				Text::_('COM_MULTIAGENCY_MULTIAGENCES_JOOMLA_FIELD'),
				'index.php?option=com_fields&context=com_multiagency.multiagency',
				$vName == 'fields.fields'
			);

			JHtmlSidebar::addEntry(
				Text::_('COM_MULTIAGENCY_MULTIAGENCES_JOOMLA_FIELD_GROUPS'),
				'index.php?option=com_fields&view=groups&context=com_multiagency.multiagency',
				$vName == 'fields.groups'
			);
		}
	}

	/**
	 * Gets the files attached to an item
	 *
	 * @param   int     $pk     The item's id
	 *
	 * @param   string  $table  The table's name
	 *
	 * @param   string  $field  The field's name
	 *
	 * @return  array  The files
	 */
	public static function getFiles($pk, $table, $field)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select($field)
			->from($table)
			->where('id = ' . (int) $pk);

		$db->setQuery($query);

		return explode(',', $db->loadResult());
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return    CMSObject
	 *
	 * @since    1.6
	 */
	public static function getActions()
	{
		$user   = Factory::getUser();
		$result = new CMSObject;

		$assetName = 'com_multiagency';

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}
}
