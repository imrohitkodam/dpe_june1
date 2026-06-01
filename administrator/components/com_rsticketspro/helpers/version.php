<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RSTicketsProVersion
{
	public $version  = '3.1.0';
	public $key		 = '8TIK5J3PRO';

	// Get version
	public function __toString()
	{
		return $this->version;
	}
}