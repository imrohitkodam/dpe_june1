<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

class rsseoMenuHelper
{
	public static function generateSitemap() {
		$db		= Factory::getDBO();
		$query	= $db->getQuery(true);
		$return = '';
		
		// Add stylesheet
		HTMLHelper::stylesheet('com_rsseo/site.css', array('relative' => true, 'version' => 'auto'));
		
		// Get menus
		$menus = unserialize(base64_decode(rsseoMenuHelper::getConfig('sitemap_menus')));
		//get excluded items
		$excludes = unserialize(base64_decode(rsseoMenuHelper::getConfig('sitemap_excludes')));
		
		if (empty($menus)) {
			return $return;
		}
		
		$params		= self::getParams();
		$columns	= (int) $params->get('columns', 1);
		$content	= array();
		
		if (!empty($menus)) {
			foreach ($menus as $menu) {
				$params = new Registry;
				$params->set('menutype',$menu);
				$params->set('ignore',$excludes);
				
				if ($items = self::getList($params)) {
					$html = self::render($items);
					
					if (empty($html)) {
						continue;
					}
					
					$query->clear();
					$query->select($db->qn('title'))->from($db->qn('#__menu_types'))->where($db->qn('menutype').' = '.$db->q($menu));
					$db->setQuery($query);
					$title = $db->loadResult();
					
					$menuHtml = '<div id="rsseo-sitemap-'.strtolower($menu).'" class="rsseo-sitemap-menu">';
					$menuHtml .= '<div class="rsseo_title">'. $title .'</div>';
					$menuHtml .= $html;
					$menuHtml .= '</div>';
					
					$content[] = $menuHtml;
				}
			}
		}
		
		if ($content) {
			require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/adapter/adapter.php';
			
			$columns = $columns < 1 || $columns > 4 ? 1 : $columns;
			$chunks  = array_chunk($content, $columns);
			$size    = 12 / $columns;
			
			foreach ($chunks as $chunk => $items) {
				$num = count($items);
				$adjustedSize = $num == $columns ? $size : round(12 / $num);
				
				$return .= '<div class="'.RSSeoAdapterGrid::row().'">';
				foreach ($items as $i => $item) {
					$return .= '<div class="'.RSSeoAdapterGrid::column($adjustedSize).'">';
					$return .= $item;
					$return .= '</div>';
				}
				$return .= '</div>';
			}
		}
		
		return $return;
	}
	
	/**
	 * Get a list of the menu items.
	 *
	 * @param	Registry	$params	The module options.
	 *
	 * @return	array
	 * @since	1.5
	 */
	protected static function getConfig($name = null) {
		$component = ComponentHelper::getComponent('com_rsseo');
		$params = $component->params->toObject();
		
		if ($name != null) {
			if (isset($params->$name)) return $params->$name;
				else return false;
		}
		else return $params;
	}
	
	protected static function getParams() {
		$itemid = Factory::getApplication()->input->getInt('Itemid',0);
		$params = new Registry;
		
		if ($itemid) {
			$menu = Factory::getApplication()->getMenu();
			if ($active = $menu->getItem($itemid))
				$params = $active->getParams();
		}
		
		if (empty($params)) {
			$params = Factory::getApplication()->getParams();
		}
		
		return $params;
	}
	
	protected static function getList($params) {
		$app = Factory::getApplication();
		$menu = $app->getMenu();

		// Get active menu item
		$active = self::getActive();
		$user = Factory::getUser();
		$levels = $user->getAuthorisedViewLevels();
		asort($levels);
		$path    = $active->tree;
		$start   = 1;
		$end     = 0;
		$showAll = 1;
		$items   = $menu->getItems('menutype', $params->get('menutype'));
		$ignored = $params->get('ignore');

		$lastitem = 0;
		
		if ($items)
		{
			$remove = array();
			if (!empty($ignored)) {
				foreach($items as $i => $item) {
					if (in_array($item->id, $ignored)) {
						if ($elements = $menu->getItems('parent_id', (int) $item->id)) {
							foreach ($elements as $element) {
								$remove[] = $element->id;
							}
						}
						
						$remove[] = $item->id;
					}
				}
			}
			
			foreach($items as $i => $item)
			{
				if (in_array($item->id,$remove)) {
					unset($items[$i]);
					continue;
				}
				
				$params = $item->getParams();
				
				if (($start && $start > $item->level)
					|| ($end && $item->level > $end)
					|| (!$showAll && $item->level > 1 && !in_array($item->parent_id, $path))
					|| ($start > 1 && !in_array($item->tree[$start - 2], $path)))
				{
					unset($items[$i]);
					continue;
				}

				$item->deeper     = false;
				$item->shallower  = false;
				$item->level_diff = 0;

				if (isset($items[$lastitem]))
				{
					$items[$lastitem]->deeper     = ($item->level > $items[$lastitem]->level);
					$items[$lastitem]->shallower  = ($item->level < $items[$lastitem]->level);
					$items[$lastitem]->level_diff = ($items[$lastitem]->level - $item->level);
				}

				$item->parent = (boolean) $menu->getItems('parent_id', (int) $item->id, true);

				$lastitem     = $i;
				$item->active = false;
				$item->flink  = $item->link;

				// Reverted back for CMS version 2.5.6
				switch ($item->type)
				{
					case 'separator':
					case 'heading':
						// No further action needed.
					break;

					case 'url':
						if ((strpos($item->link, 'index.php?') === 0) && (strpos($item->link, 'Itemid=') === false))
						{
							// If this is an internal Joomla link, ensure the Itemid is set.
							$item->flink = $item->link . '&Itemid=' . $item->id;
						}
					break;

					case 'alias':
						// If this is an alias use the item id stored in the parameters to make the link.
						$item->flink = 'index.php?Itemid=' . $params->get('aliasoptions');
					break;

					default:
						$item->flink = 'index.php?Itemid=' . $item->id;
					break;
				}

				if (strcasecmp(substr($item->flink, 0, 4), 'http') && (strpos($item->flink, 'index.php?') !== false))
				{
					$item->flink = Route::_($item->flink, true, $params->get('secure'));
				}
				else
				{
					$item->flink = Route::_($item->flink);
				}
			}

			if (isset($items[$lastitem]))
			{
				$items[$lastitem]->deeper     = (($start?$start:1) > $items[$lastitem]->level);
				$items[$lastitem]->shallower  = (($start?$start:1) < $items[$lastitem]->level);
				$items[$lastitem]->level_diff = ($items[$lastitem]->level - ($start?$start:1));
			}
		}
		
		return $items;
	}
	
	/**
	 * Get active menu item.
	 *
	 * @return	object
	 * @since	3.0
	 */
	protected static function getActive() {
		$menu = Factory::getApplication()->getMenu();

		// If no active menu, use current or default
		$active = ($menu->getActive()) ? $menu->getActive() : $menu->getDefault();

		return $active;
	}
	
	/**
	 * Render HTML
	 *
	 * @return	object
	 * @since	3.0
	 */
	protected static function render($items) {
		$html[] = '<ul class="rsseo_links">';
		
		foreach ($items as $i => &$item) {
			$class = 'item-'.$item->id;

			if ($item->deeper) {
				$class .= ' deeper';
			}

			if ($item->parent) {
				$class .= ' parent';
			}

			if (!empty($class)) {
				$class = ' class="'.trim($class) .'"';
			}

			$html[] = '<li'.$class.'>';

			// Render the menu item.
			switch ($item->type) {
				case 'separator':
				case 'heading':
					$html[] = '<span class="separator">'.htmlspecialchars($item->title, ENT_COMPAT, 'UTF-8', false).'</span>';
				break;
				
				case 'component':
					$html[] = '<a href="'.$item->flink.'">'.htmlspecialchars($item->title, ENT_COMPAT, 'UTF-8', false).'</a>';
				break;

				case 'url':
				default:				
					$html[] = '<a href="'.OutputFilter::ampReplace(htmlspecialchars($item->flink)).'">'.htmlspecialchars($item->title, ENT_COMPAT, 'UTF-8', false).'</a>';
				break;
			}

			// The next item is deeper.
			if ($item->deeper) {
				$html[] = '<ul class="rsseo_links_small">';
			}
			// The next item is shallower.
			elseif ($item->shallower) {
				$html[] = '</li>';
				$html[] = str_repeat('</ul></li>', $item->level_diff);
			}
			// The next item is on the same level.
			else {
				$html[] = '</li>';
			}
		}
		
		$html[] = '</ul>';
		
		return implode("\n", $html);
	}
}