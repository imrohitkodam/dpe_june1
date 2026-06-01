<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableTicketnotes extends JTable
{
	public $id;
	public $ticket_id;
	public $user_id;
	public $text;
	public $date;
	
	public function __construct(& $db)
	{
		parent::__construct('#__rsticketspro_ticket_notes', 'id', $db);
	}

	public function check()
	{
		if (!$this->id)
		{
			$this->date     = JFactory::getDate()->toSql();
			$this->user_id  = JFactory::getUser()->id;
		}
		else
		{
			$this->date	= null;
			$this->user_id = null;
			$this->ticket_id = null;
		}

		return true;
	}
}