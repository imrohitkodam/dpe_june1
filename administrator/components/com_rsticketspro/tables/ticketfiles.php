<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableTicketfiles extends JTable
{
	public $id = null;
	public $ticket_id;
	public $ticket_message_id;
	public $filename;
	public $downloads;

	protected $_columnAlias = array(
		'hits' => 'downloads'
	);
	
	public function __construct(& $db)
	{
		parent::__construct('#__rsticketspro_ticket_files', 'id', $db);
	}

	public function check()
	{
		if (!$this->id)
		{
			$this->downloads = 0;
		}

		return true;
	}
	
	public function getRealPath()
	{
		$hash = md5($this->id . ' ' . $this->ticket_message_id);
		return RST_UPLOAD_FOLDER . '/' . $hash;
	}

	public function delete($pk = null)
	{
		$result = parent::delete($pk);

		if ($result)
		{
			$db = $this->getDbo();
			$query = $db->getQuery(true);
			$ticket_id = $this->ticket_id;

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

		return $result;
	}
}