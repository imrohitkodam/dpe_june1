<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelDashboard extends JModelLegacy
{
	public function getCategories($id = 0)
	{
		$query	= $this->_db->getQuery(true);
		
		$query->select('*')
			->from($this->_db->qn('#__rsticketspro_kb_categories'))
			->where($this->_db->qn('parent_id').' = '.$this->_db->q($id))
			->where($this->_db->qn('published').' = '.$this->_db->q(1))
			->order($this->_db->qn('ordering').' ASC');
		
		if (!RSTicketsProHelper::isStaff())
		{
			$query->where($this->_db->qn('private').' = '.$this->_db->q(0));
		}
		
		$this->_db->setQuery($query);
		return $this->_db->loadObjectList();
	}
	
	public function getTickets()
	{
		$query		= $this->_db->getQuery(true);
		$user_id	= (int) JFactory::getUser()->get('id');
		$limit		= (int) JFactory::getApplication()->getParams('com_rsticketspro')->get('tickets_limit', 3);
		
		$query->select($this->_db->qn('t.id'))
			->select($this->_db->qn('t.subject'))
			->select($this->_db->qn('t.last_reply_customer'))
			->select($this->_db->qn('s.name','status_name'))
			->from($this->_db->qn('#__rsticketspro_tickets','t'))
			->join('LEFT',$this->_db->qn('#__rsticketspro_statuses','s').' ON '.$this->_db->qn('t.status_id').' = '.$this->_db->qn('s.id'))
			->order($this->_db->qn('t.last_reply').' DESC');
		
		if (RSTicketsProHelper::isStaff())
		{
			$query->where($this->_db->qn('t.staff_id').' = '.$this->_db->q($user_id));
		}
		else
		{
			$query->where($this->_db->qn('t.customer_id').' = '.$this->_db->q($user_id));
		}
		
		$this->_db->setQuery($query,0,$limit);
		$tickets = $this->_db->loadObjectList();
		
		if ($tickets && $ticket_ids = $this->_implodeTickets($tickets))
		{
			$query->clear()
				->select($this->_db->qn('ticket_id'))
				->select($this->_db->qn('message'))
				->from($this->_db->qn('#__rsticketspro_ticket_messages'))
				->where($this->_db->qn('user_id').' <> '.$this->_db->q($user_id))
				->where($this->_db->qn('user_id').' <> '.$this->_db->q('-1'))
				->where($this->_db->qn('ticket_id').' IN ('.$ticket_ids.')')
				->order($this->_db->qn('date').' DESC');
			
			$this->_db->setQuery($query);
			$messages = $this->_db->loadObjectList();
			
			foreach ($tickets as $i => $ticket)
			{
				foreach ($messages as $message)
				{
					if ($ticket->id == $message->ticket_id)
					{
						$tickets[$i]->message = $message->message;
						break 2;
					}
				}
			}
		}
		
		return $tickets;
	}
	
	protected function _implodeTickets($results)
	{
		$isStaff = RSTicketsProHelper::isStaff();

		$tmp = array();
		foreach ($results as $result)
		{
			if (!$isStaff && $result->last_reply_customer)
			{
				continue;
			}

			if (isset($result->id))
			{
				$tmp[] = $result->id;
			}
		}
		
		if (count($tmp))
		{
			return implode(',', $tmp);
		}
		
		return false;
	}
}