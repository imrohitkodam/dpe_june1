<?php
/*------------------------------------------------------------------------
  Speedy Translate - Speed up translating work in Joomla!
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   https://www.solidres.com
  @Copyright Copyright (C) 2013 - 2017 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

defined('_JEXEC') or die;

/**
 * Custom script to hook into installation process
 *
 */
class pkg_mailcatcherInstallerScript
{
	function postflight($type, $parent)
	{
		$dbo = JFactory::getDbo();

		$query = $dbo->getQuery(true);

		$query->clear();
		$query->update($dbo->quoteName('#__extensions'));
		$query->set('enabled = 1');
		$query->where("element = 'mailcatcher'");
		$query->where("type = 'plugin'");
		$query->where("folder = 'system'");

		$dbo->setQuery($query);

		$result = $dbo->execute();
		if(!$result)
		{
			JFactory::getApplication()->enqueueMessage('Mailcatcher - System plugin: publishing failed', 'warning');
		}
		else
		{
			JFactory::getApplication()->enqueueMessage(JText::_('Mailcatcher - System plugin is published successfully.'));
		}
	}
}
