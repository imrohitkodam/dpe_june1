<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
	
class JHtmlRSTicketsProIcon
{	
	// Deprecated
	public static function notify($is_staff, $ticket, $attribs = null) {
		if (!$is_staff || !RSTicketsProHelper::getConfig('autoclose_enabled') || $ticket->last_reply_customer || $ticket->autoclose_sent || $ticket->status_id == 2) {
			return;
		}
		
		$interval = RSTicketsProHelper::getConfig('autoclose_email_interval') * 86400;
		if ($interval < 86400) {
			$interval = 86400;
		}
		
		$date = JFactory::getDate();
		$date = $date->toUnix();
		$date = RSTicketsProHelper::getCurrentDate($date);
		
		$last_reply_interval = RSTicketsProHelper::getCurrentDate($ticket->last_reply) + $interval;
		
		if ($last_reply_interval > $date)
			return;
		
		$overdue = floor(($date - $last_reply_interval) / 86400);
		
		$url = RSTicketsProHelper::route('index.php?option=com_rsticketspro&task=ticket.notify&cid='.$ticket->id);
		$img = JHtml::image('com_rsticketspro/notify.gif', JText::_('RST_TICKET_NOTIFY'),  'class="rst_notify_ticket"', true);
		
		$return = '<span class="'.RSTicketsProHelper::tooltipClass().'" title="'.RSTicketsProHelper::tooltipText(JText::sprintf('RST_TICKET_NOTIFY_DESC', $overdue)).'" '.$attribs.'><a href="'.$url.'">'.$img.'</a></span>';
		
		return $return;
	}
}

class JHtmlRSTicketsProCalendar
{
	public static function calendar($show_time = false, $value, $name, $id, $format = '%Y-%m-%d', $attribs = null)
	{
		$params = array(
			'showTime' => $show_time ? 'true' : '0'
		);
		
		return JHtml::_('calendar', $value, $name, $id, $format, $params);
	}
}