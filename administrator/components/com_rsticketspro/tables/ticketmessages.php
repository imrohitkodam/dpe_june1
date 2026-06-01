<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableTicketmessages extends JTable
{
	public $id;
	public $ticket_id;
	public $user_id;
	public $submitted_by_staff = 0;
	public $message;
	public $date;
	public $html;
	
	public function __construct(&$db)
	{
		parent::__construct('#__rsticketspro_ticket_messages', 'id', $db);
	}

	public function check()
	{
		if ($this->id)
		{
			$this->ticket_id = null;
			$this->user_id = null;
			$this->date = null;
			$this->submitted_by_staff = null;
		}

		return true;
	}
	
	public function delete($pk = null)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$ticket_id = $this->ticket_id;
		
		$deleted = parent::delete($pk);
		if ($deleted)
		{
			$query->clear()
				->update('#__rsticketspro_tickets')
				->set($db->qn('replies').' = '.$db->qn('replies').' - 1')
				->where($db->qn('id').' = '.$db->q($ticket_id));
			$db->setQuery($query)->execute();
			
			// delete all files
			// physical files
			$query->clear();
			$query->select($db->qn('id'))
				->select($db->qn('ticket_message_id'))
				->from($db->qn('#__rsticketspro_ticket_files'))
				->where($db->qn('ticket_message_id') . '=' . $db->q($pk));
			$db->setQuery($query);
			if ($files = $db->loadObjectList())
			{
				foreach ($files as $file)
				{
					$hash = md5($file->id . ' ' . $file->ticket_message_id);
					JFile::delete(RST_UPLOAD_FOLDER . '/' . $hash);
				}
			}
			// from the database
			$query->clear();
			$query->delete('#__rsticketspro_ticket_files')
				->where($db->qn('ticket_message_id') . '=' . $db->q($pk));
			$db->setQuery($query)->execute();

			// Check if ticket still has attachments so that we can properly set has_files
			$query->clear()
				->select($db->qn('id'))
				->from($db->qn('#__rsticketspro_ticket_files'))
				->where($db->qn('ticket_id') . ' = ' . $db->q($ticket_id));
			if (!$db->setQuery($query)->loadResult())
			{
				$query->clear()
					->update($db->qn('#__rsticketspro_tickets'))
					->set($db->qn('has_files'). ' = '. $db->q(0))
					->where($db->qn('id') . ' = ' . $db->q($ticket_id));

				$db->setQuery($query)->execute();
			}
		}
		
		return $deleted;
	}
}