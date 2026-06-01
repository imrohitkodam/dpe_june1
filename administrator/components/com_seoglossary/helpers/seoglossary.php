<?php
/**    
 * SEO Glossary Seoglossary helper
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access
defined('_JEXEC') or die;
require_once(JPATH_SITE . '/components/com_seoglossary/helpers/compat.php');
$application = JFactory::getApplication();
if ($application->isClient('site'))
		{
		}
		else
		{
/**
 * Seoglossary helper.
 */
class SeoglossaryBackendHelper
{
	            /**
	 * @var    JObject  A cache for the available actions.
	 * @since  1.6
	 */
	protected static $actions;

	/**
	 * Configure the Linkbar.
	 */
	public static function addSubmenu($vName = '')
	{
		if (defined('SEOGJ3')) {
			JHtmlSidebar::addEntry(
			JText::_('COM_SEOGLOSSARY_PANEL_GLOSSARIES'),
			'index.php?option=com_seoglossary&view=panel',
			$vName == 'panel'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_SEOGLOSSARY_TITLE_CATGLOSSARIES'),
			'index.php?option=com_seoglossary&view=catglossaries',
			$vName == 'catglossaries'
		);
                JHtmlSidebar::addEntry(
			JText::_('COM_SEOGLOSSARY_TITLE_GLOSSARIES'),
			'index.php?option=com_seoglossary&view=glossaries',
			$vName == 'glossaries'
		);
				 JHtmlSidebar::addEntry(
			JText::_('COM_SEOGLOSSARY_IMPORT'),
			'index.php?option=com_seoglossary&view=import',
			$vName == 'import'
		);
		}
		else {
		
                JSubMenuHelper::addEntry(
			JText::_('COM_SEOGLOSSARY_PANEL_GLOSSARIES'),
			'index.php?option=com_seoglossary&view=panel',
			$vName == 'panel'
		);
		JSubMenuHelper::addEntry(
			JText::_('COM_SEOGLOSSARY_TITLE_CATGLOSSARIES'),
			'index.php?option=com_seoglossary&view=catglossaries',
			$vName == 'catglossaries'
		);
                JSubMenuHelper::addEntry(
			JText::_('COM_SEOGLOSSARY_TITLE_GLOSSARIES'),
			'index.php?option=com_seoglossary&view=glossaries',
			$vName == 'glossaries'
		);
				 JSubMenuHelper::addEntry(
			JText::_('COM_SEOGLOSSARY_IMPORT'),
			'index.php?option=com_seoglossary&view=import',
			$vName == 'Import'
		);
		}

	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return	JObject
	 * @since	1.6
	 */
	public static function getActions()
	{
		if (empty(self::$actions))
		{
			$user = JFactory::getUser();
			self::$actions = new JObject;

			$actions = JHelperContent::getActions('com_seoglossary');

			foreach ($actions as $name=>$action)
			{
				self::$actions->set($name, $user->authorise($name, 'com_seoglossary'));
			}
		}

		return self::$actions;
	}
	public static function loadLanguage() {
            $lang = JFactory::getLanguage();
            $lang->load('com_seoglossary', JPATH_ADMINISTRATOR.'/components/com_seoglossary');
            $lang->load('com_seoglossary.override',JPATH_ADMINISTRATOR.'/components/com_seoglossary',null, true );
            $lang->load('com_seoglossary.sys',JPATH_ADMINISTRATOR.'/components/com_seoglossary',null, true );
        }
		public static function checkCms()
			{
       if (version_compare(JVERSION, '4.0', 'ge'))
				{
					return true;
                }
            else
            {
            return false;
            }
        
        }
	}
}
