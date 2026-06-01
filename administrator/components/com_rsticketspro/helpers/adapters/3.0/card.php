<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

abstract class RsticketsproAdapterCard
{
	public static function render($section = '')
	{
		if ($section) {
			return '';
		} else {
			return 'well';
		}
	}
}