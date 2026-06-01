<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableTickethistory extends JTable
{
	public $id;
	public $ticket_id;
	public $user_id;
	public $ip;
	public $date;
	public $type;
	
	public function __construct(&$db)
	{
		parent::__construct('#__rsticketspro_ticket_history', 'id', $db);
	}
}