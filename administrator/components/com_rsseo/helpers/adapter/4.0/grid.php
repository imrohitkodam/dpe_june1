<?php
/**
* @package RSJoomla! Adapter
* @copyright (C) 2020 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die('Restricted access');

abstract class RSSeoAdapterGrid {
	
	public static function nav() {
		return 'nav flex-column';
	}
	
	public static function inputGroup($input, $prepend = null, $append = null) {
		$html = array();
		
		$html[] = '<div class="input-group">';
		
		if ($prepend) {
			$html[] = strpos($prepend, 'button') !== false ? $prepend : '<span class="input-group-text">'.$prepend.'</span>';
		}
		
		$html[] = $input;
		
		if ($append) {
			$html[] = strpos($append, 'button') !== false ? $append : '<span class="input-group-text">'.$append.'</span>';
		}
		
		$html[] = '</div>';
		
		return implode("\n", $html);
	}
	
	public static function badge($type = 'secondary') {
		$type = empty($type) ? 'secondary' : $type;
		
		return 'badge bg-'.$type;
	}
	
	public static function card() {
		return 'card';
	}
	
	public static function row() {
		return 'row';
	}

	public static function column($size) {
		return 'col-md-' . (int) $size;
	}

	public static function fdirection($direction) {
		return $direction == 'pull-right' ? 'float-end' : 'float-start';
	}

	public static function sidebar() {
		return '<div id="j-main-container" class="j-main-container">';
	}
}