<?php
/**
 * Joomlatools DOCman
 *
 * @package     DOCman
 * @copyright   Copyright (C) 2020 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/docman for the canonical source repository
 */

class DocmanHelper extends JHelperContent
{
	public static $extension = 'com_docman';

	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $vName  The name of the active view.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public static function addSubmenu($vName)
	{
		JHtmlSidebar::addEntry(
			JText::_('Documents'),
			'index.php?option=com_docman&view=documents',
			$vName == 'documents'
		);
		JHtmlSidebar::addEntry(
			JText::_('Categories'),
			'index.php?option=com_docman&view=categories',
			$vName == 'categories'
		);

		JHtmlSidebar::addEntry(
			JText::_('Tags'),
			'index.php?option=com_docman&view=tags',
			$vName == 'tags'
		);

		JHtmlSidebar::addEntry(
			JText::_('Users'),
			'index.php?option=com_docman&view=users',
			$vName == 'users'
		);

		JHtmlSidebar::addEntry(
			JText::_('JGLOBAL_FIELDS'),
			'index.php?option=com_fields&context=com_docman.document',
			$vName == 'fields.fields'
		);

		JHtmlSidebar::addEntry(
			JText::_('JGLOBAL_FIELD_GROUPS'),
			'index.php?option=com_fields&view=groups&context=com_docman.document',
			$vName == 'fields.groups'
		);

		JHtmlSidebar::addEntry(
			JText::_('Activities'),
			'index.php?option=com_docman&view=activities',
			$vName == 'activities'
		);

		JHtmlSidebar::addEntry(
			JText::_('Settings'),
			'index.php?option=com_docman&view=config',
			$vName == 'settings'
		);
	}
}
