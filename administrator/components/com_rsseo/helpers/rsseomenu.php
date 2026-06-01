<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

abstract class JHtmlRSSeoMenu {
	
	protected static $menus = null;
	protected static $items = null;
	
	/**
	 * Get a list of the available menus.
	 *
	 * @return  string
	 *
	 * @since   11.1
	 */
	public static function menus() {
		if (empty(self::$menus)) {
			$db		= Factory::getDbo();
			$query	= $db->getQuery(true);
			
			$query->select($db->qn('menutype','value'))
				->select($db->qn('title','text'))
				->from($db->qn('#__menu_types'))
				->order($db->qn('title'));
				
			$db->setQuery($query);
			self::$menus = $db->loadObjectList();
		}

		return self::$menus;
	}
	
	/**
	 * Returns an array of menu items grouped by menu.
	 *
	 * @param   array  $config  An array of configuration options.
	 *
	 * @return  array
	 */
	public static function menuitems($config = array()) {
		if (empty(self::$items)) {
			$db		= Factory::getDbo();
			$query	= $db->getQuery(true);
			
			$query->select($db->qn('menutype','value'))
				->select($db->qn('title','text'))
				->from($db->qn('#__menu_types'))
				->order($db->qn('title'));
			
			$db->setQuery($query);
			$menus = $db->loadObjectList();

			$query->clear()
				->select($db->qn('id','value'))
				->select($db->qn('title','text'))
				->select($db->qn('level'))
				->select($db->qn('menutype'))
				->from($db->qn('#__menu'))
				->where($db->qn('parent_id').' > 0')
				->where($db->qn('type').' <> '.$db->q('url'))
				->where($db->qn('client_id').' = 0');

			// Filter on the published state
			if (isset($config['published'])) {
				if (is_numeric($config['published'])) {
					$query->where($db->qn('published').' = '.(int) $config['published']);
				} elseif ($config['published'] === '') {
					$query->where($db->qn('published').' IN (0,1)');
				}
			}

			$query->order($db->qn('lft'));

			$db->setQuery($query);
			$items = $db->loadObjectList();

			// Collate menu items based on menutype
			$lookup = array();
			foreach ($items as &$item) {
				if (!isset($lookup[$item->menutype])) {
					$lookup[$item->menutype] = array();
				}
				$lookup[$item->menutype][] = &$item;

				$item->text = str_repeat('- ', $item->level) . $item->text;
			}
			self::$items = array();

			foreach ($menus as &$menu) {
				// Menu items:
				if (isset($lookup[$menu->value])) {
					foreach ($lookup[$menu->value] as &$item) {
						self::$items[$menu->text][] = HTMLHelper::_('select.option', $item->value, $item->text);
					}
				}
			}
		}

		return self::$items;
	}
	
	/**
	 * Displays an HTML select list of menu items.
	 *
	 * @param   string  $name      The name of the control.
	 * @param   string  $selected  The value of the selected option.
	 * @param   string  $attribs   Attributes for the control.
	 * @param   array   $config    An array of options for the control.
	 *
	 * @return  string
	 */
	public static function menuitemlist($name, $selected = null, $attribs = null, $config = array()) {
		static $count;

		$options = self::menuitems($config);

		return HTMLHelper::_(
			'select.groupedlist', $options, $name,
			array(
				'id' => isset($config['id']) ? $config['id'] : 'assetgroups_' . (++$count),
				'list.attr' => (is_null($attribs) ? 'class="inputbox" size="1"' : $attribs),
				'list.select' => $selected, 'group.items' => null,
				'list.translate' => false
			)
		);
	}
}