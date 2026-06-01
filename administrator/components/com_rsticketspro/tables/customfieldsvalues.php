<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableCustomfieldsvalues extends JTable
{
	public $id = null;
	public $custom_field_id = 0;
	public $ticket_id = 0;
	public $value = '';
	
	public function bind($src, $ignore = array())
	{
		if (isset($src['value']) && is_array($src['value']))
		{
			$src['value'] = implode("\n", $src['value']);
		}
		return parent::bind($src, $ignore);
	}
	
	public function __construct(&$db)
	{
		parent::__construct('#__rsticketspro_custom_fields_values', 'id', $db);
	}
}