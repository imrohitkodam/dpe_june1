<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license     GNU General Public License version 2 or later; see LICENSE
*/

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

FormHelper::loadFieldClass('groupedlist');

JLoader::registerAlias('JFormFieldGroupedList', '\\Joomla\\CMS\\Form\\Field\\GroupedlistField');

class JFormFieldGkeywords extends JFormFieldGroupedList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $type = 'Gkeywords';
	
	protected function getGroups() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true)->select('*')->from($db->qn('#__rsseo_gkeywords'));
		$array	= array();
		
		$db->setQuery($query);
		if ($keywords = $db->loadObjectList()) {
			foreach ($keywords as $keyword) {
				$array[$keyword->site][$keyword->id] = HTMLHelper::_('select.option', $keyword->id, $keyword->name);
			}
		}
		
		return $array;
	}
}