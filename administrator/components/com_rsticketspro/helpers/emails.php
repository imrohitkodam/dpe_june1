<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RSTicketsProEmailsHelper
{
	protected static $from;
	protected static $fromName;
	protected static $replyTo;
	protected static $replyToName;

	protected static function prepareEmailAddress($department_id)
	{
		// get department
		$department = RSTicketsProHelper::getDepartment($department_id);

		// prepare from name and email address
		if ($department->email_use_global)
		{
			if (RSTicketsProHelper::getConfig('email_use_global'))
			{
				// if we are using global settings, get them from the Joomla! config
				$app                = JFactory::getApplication();
				self::$from         = $app->get('mailfrom');
				self::$fromName     = $app->get('fromname');
				self::$replyTo      = $app->get('replyto');
				self::$replyToName  = $app->get('replytoname');
			}
			else
			{
				// if we are using RSTickets! Pro settings, get them from the RSTickets! Pro config
				self::$from         = RSTicketsProHelper::getConfig('email_address');
				self::$fromName     = RSTicketsProHelper::getConfig('email_address_fullname');
				self::$replyTo      = RSTicketsProHelper::getConfig('email_address_reply_to');
				self::$replyToName  = RSTicketsProHelper::getConfig('email_address_reply_to_name');
			}
		}
		else
		{
			self::$from         = $department->email_address;
			self::$fromName     = $department->email_address_fullname;
			self::$replyTo      = $department->email_address_reply_to;
			self::$replyToName  = $department->email_address_reply_to_name;
		}
	}

	// cleaner proxy function for _getEmail()
	public static function getEmail($type, $tag = null)
	{
		// get current language
		if (is_null($tag))
		{
			$tag = JFactory::getLanguage()->get('tag');
		}

		return self::_getEmail($type, $tag);
	}

	// searches for emails defined under the $tag language
	// and reverts to english if not found
	protected static function _getEmail($type, $tag)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('*')
			->from($db->qn('#__rsticketspro_emails'))
			->where($db->qn('lang') . '=' . $db->q($tag))
			->where($db->qn('type') . '=' . $db->q($type));
		$db->setQuery($query);
		if ($email = $db->loadObject())
		{
			if ($email->published)
			{
				// found entry for the selected language, return it
				return $email;
			}
		}
		else
		{
			// default to english
			return self::_getEmail($type, 'en-GB');
		}

		return false;
	}

	// actual email sending happens here
	public static function send($recipient, $subject, $body, $mode = false, $cc = null, $bcc = null, $attachment = null)
	{
		try
		{
			$from = self::$from;
			$fromName = self::$fromName;
			$replyTo = self::$replyTo;
			$replyToName = self::$replyToName;

			$mailer = JFactory::getMailer();

			$mailer->setSender(array($from, $fromName));
			$mailer->setSubject($subject);
			$mailer->setBody($body);

			// Are we sending the email as HTML?
			if ($mode)
			{
				$mailer->IsHTML(true);
			}

			$mailer->addRecipient($recipient);
			$mailer->addCC($cc);
			$mailer->addBCC($bcc);
			$mailer->addAttachment($attachment);

			// Take care of reply email addresses
			$hasReplyTo = false;
			if (is_array($replyTo))
			{
				$numReplyTo = count($replyTo);
				for ($i = 0; $i < $numReplyTo; $i++)
				{
					if ($mailer->addReplyTo($replyTo[$i], $replyToName[$i]))
					{
						$hasReplyTo = true;
					}
				}
			}
			elseif ($replyTo)
			{
				if ($mailer->addReplyTo($replyTo, $replyToName))
				{
					$hasReplyTo = true;
				}
			}

			// Add sender to replyTo only if no replyTo received
			$mailer->setSender(array($from, $fromName, !$hasReplyTo));

			return $mailer->Send();
		}
		catch (Exception $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
			return false;
		}
	}

	// send predefined email messages
	public static function sendEmail($type, $data = array())
	{
		$lang = JFactory::getLanguage();
		$lang->load('com_rsticketspro', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('com_rsticketspro', JPATH_ADMINISTRATOR, $lang->getDefault(), true);
		$lang->load('com_rsticketspro', JPATH_ADMINISTRATOR, null, true);

		$replacements = array(
			'{live_site}' => JUri::root()
		);

		switch ($type)
		{
			// sent to the staff member when a ticket gets assigned to him
			case 'add_ticket_staff':
				self::prepareEmailAddress($data['department_id']);

				// get email
				$email = self::getEmail('add_ticket_staff');
				if (!$email) {
					return false;
				}

				// get ticket
				$ticket = &$data['ticket'];
				// get department
				$department = RSTicketsProHelper::getDepartment($data['department_id']);

				$customer = JFactory::getUser($ticket->customer_id);
				$staff    = JFactory::getUser($ticket->staff_id);

				// get latest message for ticket data
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select($db->qn('message'))
					->from($db->qn('#__rsticketspro_ticket_messages'))
					->where($db->qn('ticket_id') . '=' . $db->q($data['ticket']->id))
					->where($db->qn('user_id') . ' != ' . $db->q('-1'))
					->order($db->qn('date') . ' ' . $db->escape('desc'));
				$db->setQuery($query, 0, 1);
				$message = $db->loadResult();

				// ticket data
				$replacements['{ticket}']  = RSTicketsProHelper::route(JUri::root() . 'index.php?option=com_rsticketspro&view=ticket&cid=' . $ticket->id . ':' . JFilterOutput::stringURLSafe($ticket->subject));
				$replacements['{message}'] = $message;
				$replacements['{code}']    = $ticket->code;
				$replacements['{subject}'] = $ticket->subject;
				// department data
				$replacements['{department_name}'] = $replacements['{department-name}'] = JText::_($department->name);
				$replacements['{department_id}']   = $replacements['{department-id}'] = $department->id;
				// priority
				$replacements['{priority}'] = JText::_($ticket->priority->name);
				// status
				$replacements['{status}'] = JText::_($ticket->status->name);
				// customer data
				$replacements['{customer_name}']     = $replacements['{customer-name}'] = $customer->name;
				$replacements['{customer_email}']    = $replacements['{customer-email}'] = $customer->email;
				$replacements['{customer_username}'] = $replacements['{customer-username}'] = $customer->username;
				// staff data
				$replacements['{staff_name}']     = $replacements['{staff-name}'] = $staff->name;
				$replacements['{staff_email}']    = $replacements['{staff-email}'] = $staff->email;
				$replacements['{staff_username}'] = $replacements['{staff-username}'] = $staff->username;
				// custom fields
				$fieldsText = '';
				foreach ($ticket->fields as $field)
				{
					if (in_array($field->type, array('select', 'multipleselect', 'checkbox')))
					{
						$field->value = str_replace("\n", ', ', $field->value);
					}

					$fieldsText .= '<p>' . JText::_($field->label) . ': ' . $field->value . '</p>';
					$replacements['{field-' . $field->name . '}'] = $field->value;
				}
				$replacements['{custom_fields}'] = $replacements['{custom-fields}'] = $fieldsText;

				$emailSubject = '[' . $ticket->code . '] ' . $ticket->subject;
				$emailMessage = str_replace(array_keys($replacements), array_values($replacements), $email->message);

				self::send($staff->email, $emailSubject, $emailMessage, true, null, null, null, $ticket->id);
				break;
			case 'notification_department_change':
				self::prepareEmailAddress($data['to']);
				// get email
				$email = self::getEmail('notification_department_change');

				if (!$email) {
					return false;
				}
				// get ticket
				$ticket = &$data['ticket'];
				// get department
				$department = RSTicketsProHelper::getDepartment($data['to']);

				$customer = JFactory::getUser($ticket->customer_id);
				$staff    = JFactory::getUser($ticket->staff_id);

				// get latest message for ticket data
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select($db->qn('message'))
					->from($db->qn('#__rsticketspro_ticket_messages'))
					->where($db->qn('ticket_id') . '=' . $db->q($data['ticket']->id))
					->where($db->qn('user_id') . ' != ' . $db->q('-1'))
					->order($db->qn('date') . ' ' . $db->escape('desc'));
				$db->setQuery($query, 0, 1);
				$message = $db->loadResult();

				// ticket data
				$replacements['{ticket}']   = RSTicketsProHelper::route(JUri::root() . 'index.php?option=com_rsticketspro&view=ticket&cid=' . $ticket->id . ':' . JFilterOutput::stringURLSafe($ticket->subject));
				$replacements['{message}']  = $message;
				$replacements['{code}']     = $ticket->code;
				$replacements['{new_code}'] = $data['code'];
				$replacements['{subject}']  = $ticket->subject;
				// department data
				$replacements['{department_name}'] = $replacements['{department-name}'] = JText::_($department->name);
				$replacements['{department_id}']   = $replacements['{department-id}'] = $department->id;
				// priority
				$replacements['{priority}'] = JText::_($ticket->priority->name);
				// status
				$replacements['{status}'] = JText::_($ticket->status->name);
				// customer data
				$replacements['{customer_name}']     = $replacements['{customer-name}'] = $customer->name;
				$replacements['{customer_email}']    = $replacements['{customer-email}'] = $customer->email;
				$replacements['{customer_username}'] = $replacements['{customer-username}'] = $customer->username;
				// staff data
				$replacements['{staff_name}']      = $replacements['{staff-name}'] = $staff->name;
				$replacements['{staff_email}']     = $replacements['{staff-email}'] = $staff->email;
				$replacements['{staff_username}']  = $replacements['{staff-username}'] = $staff->username;
				$replacements['{department_from}'] = JText::_($data['ticket']->department->name);
				$replacements['{department_to}']   = JText::_($department->name);

				// custom fields
				$fieldsText = '';
				foreach ($ticket->fields as $field)
				{
					if (in_array($field->type, array('select', 'multipleselect', 'checkbox')))
					{
						$field->value = str_replace("\n", ', ', $field->value);
					}

					$fieldsText .= '<p>' . JText::_($field->label) . ': ' . $field->value . '</p>';
					$replacements['{field-' . $field->name . '}'] = $field->value;
				}
				$replacements['{custom_fields}'] = $replacements['{custom-fields}'] = $fieldsText;

				$emailSubject = '[' . $data['code'] . '] ' . $ticket->subject;
				$emailMessage = str_replace(array_keys($replacements), array_values($replacements), $email->message);

				self::send($customer->email, $emailSubject, $emailMessage, true, null, null, null, $ticket->id);
				break;
			case 'feedback_followup_email':
				self::prepareEmailAddress($data['department_id']);
				$email = self::getEmail('feedback_followup_email');
				if (!$email) {
					return false;
				}

				// get ticket
				$ticket = &$data['ticket'];
				// get department
				$department = RSTicketsProHelper::getDepartment($data['department_id']);

				$customer = JFactory::getUser($ticket->customer_id);
				$staff    = JFactory::getUser($ticket->staff_id);

				// get latest message for ticket data
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select($db->qn('message'))
					->from($db->qn('#__rsticketspro_ticket_messages'))
					->where($db->qn('ticket_id') . '=' . $db->q($data['ticket']->id))
					->where($db->qn('user_id') . ' != ' . $db->q('-1'))
					->order($db->qn('date') . ' ' . $db->escape('desc'));
				$db->setQuery($query, 0, 1);
				$message = $db->loadResult();

				// ticket data
				$replacements['{ticket}']  = RSTicketsProHelper::route(JUri::root() . 'index.php?option=com_rsticketspro&view=ticket&cid=' . $ticket->id . ':' . JFilterOutput::stringURLSafe($ticket->subject));
				$replacements['{message}'] = $message;
				$replacements['{code}']    = $ticket->code;
				$replacements['{subject}'] = $ticket->subject;
				// department data
				$replacements['{department_name}'] = $replacements['{department-name}'] = JText::_($department->name);
				$replacements['{department_id}']   = $replacements['{department-id}'] = $department->id;
				// priority
				$replacements['{priority}'] = JText::_($ticket->priority->name);
				// status
				$replacements['{status}'] = JText::_($ticket->status->name);
				// customer data
				$replacements['{customer_name}']     = $replacements['{customer-name}'] = $customer->name;
				$replacements['{customer_email}']    = $replacements['{customer-email}'] = $customer->email;
				$replacements['{customer_username}'] = $replacements['{customer-username}'] = $customer->username;
				// staff data
				$replacements['{staff_name}']     = $replacements['{staff-name}'] = $staff->name;
				$replacements['{staff_email}']    = $replacements['{staff-email}'] = $staff->email;
				$replacements['{staff_username}'] = $replacements['{staff-username}'] = $staff->username;

				$access_code = md5($ticket->id . ' | ' . $customer->email);

				$no  = RSTicketsProHelper::mailRoute('index.php?option=com_rsticketspro&view=ticket&cid=' . $ticket->id . '&task=ticket.rate&rating=1&access_code=' . $access_code);
				$yes = RSTicketsProHelper::mailRoute('index.php?option=com_rsticketspro&view=ticket&cid=' . $ticket->id . '&task=ticket.rate&rating=5&access_code=' . $access_code);

				$replacements['{no}'] = $no;
				$replacements['{yes}'] = $yes;
				$replacements['{feedback}'] = JText::sprintf('RST_FEEDBACK_EMAIL', $no, $yes);

				// custom fields
				$fieldsText = '';
				foreach ($ticket->fields as $field)
				{
					if (in_array($field->type, array('select', 'multipleselect', 'checkbox')))
					{
						$field->value = str_replace("\n", ', ', $field->value);
					}

					$fieldsText .= '<p>' . JText::_($field->label) . ': ' . $field->value . '</p>';
					$replacements['{field-' . $field->name . '}'] = $field->value;
				}
				$replacements['{custom_fields}'] = $replacements['{custom-fields}'] = $fieldsText;

				$emailSubject = '[' . $ticket->code . '] ' . $ticket->subject;
				$emailMessage = str_replace(array_keys($replacements), array_values($replacements), $email->message);

				self::send($customer->email, $emailSubject, $emailMessage, true, null, null, null, $ticket->id);
		}
	}
}