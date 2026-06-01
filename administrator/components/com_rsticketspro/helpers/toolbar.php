<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

abstract class RSTicketsProToolbarHelper
{
	public static function addToolbar($view = '')
	{
		// load language file (.sys because the toolbar has the same options as the components dropdown)
		JFactory::getLanguage()->load('com_rsticketspro.sys', JPATH_ADMINISTRATOR);

		// add toolbar entries
		// overview
		self::addEntry('OVERVIEW', 'index.php?option=com_rsticketspro', $view == '' || $view == 'rsticketspro');
		self::addEntry('MANAGE_TICKETS', 'index.php?option=com_rsticketspro&view=tickets', $view == 'tickets');
		self::addEntry('DEPARTMENTS', 'index.php?option=com_rsticketspro&view=departments', $view == 'departments');
		self::addEntry('CUSTOM_FIELDS', 'index.php?option=com_rsticketspro&view=customfields', $view == 'customfields');
		self::addEntry('GROUPS', 'index.php?option=com_rsticketspro&view=groups', $view == 'groups');
		self::addEntry('STAFF_MEMBERS', 'index.php?option=com_rsticketspro&view=staffs', $view == 'staffs');
		self::addEntry('PRIORITIES', 'index.php?option=com_rsticketspro&view=priorities', $view == 'priorities');
		self::addEntry('STATUSES', 'index.php?option=com_rsticketspro&view=statuses', $view == 'statuses');
		self::addEntry('EMAIL_MESSAGES', 'index.php?option=com_rsticketspro&view=emails', $view == 'emails');
		if (JFactory::getUser()->authorise('core.admin', 'com_rsticketspro'))
		{
			self::addEntry('CONFIGURATION', 'index.php?option=com_rsticketspro&view=configuration', $view == 'configuration');
		}
		JFactory::getApplication()->triggerEvent('onAfterTicketsMenu');

		self::addEntry('KB_CATEGORIES', 'index.php?option=com_rsticketspro&view=kbcategories', $view == 'kbcategories');
		self::addEntry('KB_ARTICLES', 'index.php?option=com_rsticketspro&view=kbarticles', $view == 'kbarticles');
		self::addEntry('KB_CONVERSION_RULES', 'index.php?option=com_rsticketspro&view=kbrules', $view == 'kbrules');
	}

	public static function addEntry($lang_key, $url, $default = false)
	{
		JHtmlSidebar::addEntry(JText::_('COM_RSTICKETSPRO_' . $lang_key), JRoute::_($url), $default);
	}

	public static function addFilter($text, $key, $options, $noDefault = false)
	{
		JHtmlSidebar::addFilter($text, $key, $options, $noDefault);
	}

	public static function render()
	{
		return JHtmlSidebar::render();
	}
}