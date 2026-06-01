<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

if (!class_exists('RSInput')) {
	class RSInput {
		public static function create($source=null, $filter=null) {
			if (is_null($filter)) {
				$filter = JFilterInput::getInstance(array(), array(), 1, 1, 0);
			}
			
			return $input = new JInput($source, array('filter' => $filter));
		}
	}
}