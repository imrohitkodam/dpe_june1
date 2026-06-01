<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelKbconvert extends JModelAdmin
{
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.kbconvert', 'kbconvert', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}
	
	protected function loadFormData()
	{
		$app 			= JFactory::getApplication();
		$modelTicket 	= $this->getInstance('Ticket', 'RsticketsproModel');
		$ticketId		= $app->input->getInt('ticket_id');
		$ticket			= $modelTicket->getTicket($ticketId);
		$data = array(
			'name'		=> $ticket->subject,
			'ticket_id' => $ticketId
		);
		
		return $data;
	}
	
	public function save($data)
	{
		$ticketId	 	= $data['ticket_id'];
		$modelTicket 	= $this->getInstance('Ticket', 'RsticketsproModel');
		$ticket		 	= $modelTicket->getTicket($ticketId);
		$ticketMessages = $modelTicket->getTicketMessages($ticketId, true);
		
		$params = (object) array(
			'name' 				=> $data['name'],
			'category_id' 		=> $data['category_id'],
			'publish_article' 	=> $data['publish_article'],
			'private' 			=> $data['private']
		);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/ticket.php';

		try
		{
			$result = RSTicketsProTicketHelper::convert($ticket, $ticketMessages, $params);
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
			return false;
		}

		return $result;
	}
}