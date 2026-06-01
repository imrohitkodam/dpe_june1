<?php
/**
 * @package     JLike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;
use Joomla\Data\DataObject;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

jimport('techjoomla.tjmail.mail');
/**
 * Methods supporting a list of Jlike records.
 *
 * @since  1.6
 */
class JlikeModelReminders extends ListModel
{
/**
	* Constructor.
	*
	* @param   array  $config  An optional associative array of configuration settings.
	*
	* @see        JController
	* @since      1.6
	*/
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'ordering', 'a.ordering',
				'state', 'a.state',
				'created_by', 'a.created_by',
				'modified_by', 'a.modified_by',
				'title', 'a.title',
				'days_before', 'a.days_before',
				'email_template', 'a.email_template',
				'subject', 'a.subject',
				'last_sent_limit', 'a.last_sent_limit',
				'content_type', 'a.content_type',
				'enable_cc', 'a.enable_cc',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function populateState($ordering = 'a.title', $direction = 'asc')
	{
		// Initialise variables.
		$app = Factory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		// Filtering content_type
		$this->setState('filter.content_type', $app->getUserStateFromRequest($this->context . '.filter.content_type', 'filter_content_type', '', 'string'));

		// Load the parameters.
		$params = ComponentHelper::getParams('com_jlike');
		$this->setState('params', $params);

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return   string A store id.
	 *
	 * @since    1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$app = Factory::getApplication();

		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 'DISTINCT a.*'
			)
		);
		$query->from('`#__jlike_reminders` AS a');
		$is_todo_specific = $this->getState('filter.is_todo_specific');

		// Join over the users for the checked out user
		$query->select("uc.name AS editor");
		$query->join("LEFT", "#__users AS uc ON uc.id=a.checked_out");

		// Join over the user field 'created_by'
		$query->select('`created_by`.name AS `created_by`');
		$query->join('LEFT', '#__users AS `created_by` ON `created_by`.id = a.`created_by`');

		// Join over the user field 'modified_by'
		$query->select('`modified_by`.name AS `modified_by`');
		$query->join('LEFT', '#__users AS `modified_by` ON `modified_by`.id = a.`modified_by`');

		// Join over the reminder_contentds field 'content_id'
		$query->select('GROUP_CONCAT(rc.content_id) AS `contents`');
		$query->join('LEFT', '#__jlike_reminder_contentids AS `rc` ON `a`.id = rc.`reminder_id`');
		
		if($is_todo_specific == 0 && !$app->isClient('administrator'))
		{
			$query->where('a.is_todo_specific = ' . $db->quote($is_todo_specific));
		}
		
		$query->group('a.id');

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where('a.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.state IN (0, 1))');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('( a.title LIKE ' . $search . '  OR  a.days_before LIKE ' . $search . '  OR  a.content_type LIKE ' . $search . ' )');
			}
		}

		// Filter by search in title
		$content_type = $this->getState('filter.content_type');

		if (!empty($content_type))
		{
			$query->where('a.content_type = ' . $db->quote($content_type));
		}

		// DPE hack - Custom content type to don't the get the specific results and this will go in core
		$custom_content_type = $this->getState('filter.custom_content_type');
		

		if (!empty($custom_content_type))
		{
			$query->where('a.content_type != ' . $db->quote($custom_content_type));
		}



		// Filtering content_type Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Get an array of data items
	 *
	 * @return mixed Array of data items on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();

		return $items;
	}

	/**
	 * Send Reminders to Users before due date
	 *
	 * @return Array reminder sent deatils
	 */
	public function sendReminders()
	{
		$reminder_sent_count = $sent   = 0;
		$sent_details = $all_todos = $send = $todos = array();
		$db                  = Factory::getDBO();
		$jlikeparams         = ComponentHelper::getParams('com_jlike');
		$batch_size          = $jlikeparams->get('reminder_batch_size', 0);

		// Load file to call api of the table
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jlike/tables');

		$jinput = Factory::getApplication()->input;
		$jinput->set('filter_published', 1);
		$this->setState('filter.custom_content_type', 'com_jlike.generic_todo');
		$this->setState('filter.is_todo_specific', 0);
		$reminders = $this->getItems();

		foreach ($reminders as $reminder)
		{
			// Date conversion to compare reminder date
			$date           = Factory::getDate();
			$reminder_date  = new Date($date . "+" . $reminder->days_before . " days");
			$reminder_date  = $reminder_date->format('Y-m-d');

			// For the general type of reminder get todos excluding content_ids of the other reminders with the same content_type
			$ltquery = $db->getQuery(true);
			$ltquery->select('distinct c.content_id');
			$ltquery->from($db->quoteName('#__jlike_reminder_contentids') . 'as c');
			$ltquery->join('LEFT', $db->quoteName('#__jlike_reminders') . 'as d on c.reminder_id=d.id');
			$ltquery->where('d.state = 1');

			if (empty($reminder->contents))
			{
				$ltquery->where('d.content_type = ' . $db->quote($reminder->content_type));
			}

			$query = $db->getQuery(true);
			$query->select(
			$db->quoteName(array('c.id', 'c.content_id', 'c.assigned_to', 'c.assigned_by', 'c.due_date', 'd.url', 'd.title', 'd.element', 'd.element_id', 'c.sender_msg'))
			);

			// Attach reminder_id in the todos with the help of select query
			$query->select($reminder->id . ' as reminder_id');
			$query->from($db->quoteName('#__jlike_todos') . 'as c');
			$query->join('LEFT', $db->quoteName('#__jlike_content') . 'as d on c.content_id=d.id');
			$query->join('LEFT', $db->quoteName('#__users') . 'as u on u.id=c.assigned_to');

			if (empty($reminder->contents))
			{
				$query->where('c.content_id not in (' . $ltquery . ')');
			}
			else
			{
				$query->where('c.content_id in (' . $reminder->contents . ')');
			}

			$query->where('d.element = ' . $db->quote($reminder->content_type));
			$query->where('date(c.due_date) = ' . $db->quote($reminder_date));
			$query->where('c.status != ' . $db->quote('C'));

			// Dont sent reminder if the user is blocked
			$query->where('u.block != 1');

			//  Dont sent reminder if already sent reminder previously
			$query->where('NOT EXISTS (select todo_id from ' . $db->quoteName('#__jlike_reminder_sent') . '
				 as rs where c.id = rs.todo_id and reminder_id = ' . $db->quote($reminder->id) . ')');
			$db->setQuery($query);
			$todo_s = $db->loadObjectList();
			$todos = array_merge($todos, $todo_s);
		}

		// Shuffle todos and apply the batch size
		shuffle($todos);
		$todos = array_slice($todos, 0, $batch_size);

			echo Text::_('COM_JLIKE_REMINDERSENT_DETAILS');

			// Add details in the logger file
			Log::addLogger(
				array(
					// Sets file name
					'text_file' => 'com_jlike.sentreminders.log'
					),
			Log::INFO,
			array('com_jlike')
			);

			if (!empty($todos))
			{
				foreach ($todos as $todo)
				{
					$user           = Factory::getUser($todo->assigned_to);

					// First parameter file name and second parameter is prefix
					$reminder_table = Table::getInstance('reminder', 'JlikeTable', array('dbo', $db));

					// Get jlike_remider_sent for per reminder Check if already reminder sent to the User
					$reminder_table->load(array('id' => (int) $todo->reminder_id));

					// Get content type
					$content_type = explode(".", $todo->element);
					$title = null;			
						// Tigger to Check content follows all criteria to send the reminder
					

					// DPE - Hack Title and todo id added in trigger param
					PluginHelper::importPlugin('content');
					
						$send       = Factory::getApplication()->triggerEvent('onAfterJlike' . $content_type[1] . 'ContentCheckforReminder', array(
																$todo->assigned_to,
																$todo->element_id,
																&$title,
																$todo->id
															));
						// Content is published and not Completed yet
						if (empty($send) || (!empty($send) && $send[0] == 1))
						{
							// Calculate reminder date with the help of current date
							$date           = Factory::getDate();

							// First parameter file name and second parameter is prefix
							$table = Table::getInstance('Remindersent', 'JlikeTable', array('dbo', $db));

							// Get all jlike_remider_sent for per reminder Check if already reminder sent to the User
							$table->load(array('todo_id' => (int) $todo->id, 'reminder_id' => (int) $todo->reminder_id));
							$recipient     = $user->email;
							$subject       = $reminder_table->subject;
							$body          = $reminder_table->email_template;
							$due_date      = HTMLHelper::date($todo->due_date, Text::_('COM_JLIKE_REMINDER_DATE_FORMAT'));

							// Store values of tags in the array
							$this->course_reminder_mail = array();
							$this->course_reminder_mail['content_due_date'] = $due_date;
							$this->course_reminder_mail['username']         = $user->username;
							$this->course_reminder_mail['name']             = $user->name;
							$content_url                                    = Route::_(JURI::base() . $todo->url);
							$this->course_reminder_mail['content_url']      = $content_url;
							$this->course_reminder_mail['content_link']     = '<a href="' . $content_url . '">' . $todo->title . '</a>';
							$this->course_reminder_mail['content_title']    = $todo->title;
							$this->course_reminder_mail['days_before']      = $reminder_table->days_before;

							// DPE hack added to make links clickable
							$this->course_reminder_mail['sender_msg']       = DPE::utilities()->urlToClickableLink($todo->sender_msg);
							$this->course_reminder_mail['content_assigned_by'] =  Factory::getUser($todo->assigned_by)->name;
							// Hack - DPE  Sending School Name in Reminder
							if (!empty($title))
							{
								$this->course_reminder_mail['school_title'] = $title;
							}

							// Replace email body tags
							$body               = TjMail::TagReplace($body, $this->course_reminder_mail);
							
							// Replace email subject tags
						    $subject  = TjMail::TagReplace($subject, $this->course_reminder_mail);

							$config = Factory::getConfig();

							$cc = !empty($reminder_table->cc) ? explode(',', $reminder_table->cc) : null;

							// If from mail is not configured in the reminder then take from Joomla config
							$from = !empty($reminder_table->mailfrom) ? $reminder_table->mailfrom : $config->get('mailfrom');

							// If from name is not configured in the reminder then take from Joomla config
							$fromName = !empty($reminder_table->fromname)?$reminder_table->fromname: $config->get('fromname');
							$replyTo = !empty($reminder_table->replyto) ? $reminder_table->replyto : null;
							$replyToName = !empty($reminder_table->replytoname) ? $reminder_table->replytoname : null;

							$result = Factory::getMailer()->sendMail($from, $fromName, $recipient, $subject, $body, true, $cc, null, null, $replyTo, $replyToName);

							if ($result)
							{
								// Update table in the jlike_reminder_logs  with the sent_on as current_date and time
								$table->reminder_id = $reminder_table->id;
								$table->todo_id     = $todo->id;
								$table->sent_on     = $date->toSql();
								$table->store();
								$sent = 1;
								$reason = Text::_('COM_JLIKE_REMINDERS_SENT_REASON');
								echo Text::sprintf('COM_JLIKE_REMINDERS_SENT', $user->username, $reminder_table->title, $reminder_table->days_before, $todo->title);
								$reminder_sent_count++;
							}
							else
							{
								// Reminder Mail not sent
								$reason = Text::_('COM_JLIKE_REMINDERS_NOT_SENT_REASON');
							}
						}
						else
						{
							// Content,content_catgory not published or content Completed
							$reason = Text::_('COM_JLIKE_REMINDERS_NOT_SENT_CONTENT_REASON');
						}

						$log = array('username' => $user->username,
									'days_before' => $reminder_table->days_before,
									'contenttitle' => $todo->title,
									'reminder_title' => $reminder_table->title,
									'reason' => $reason,
									'sent' => $sent
									);
						Log::add(json_encode($log), Log::INFO, 'com_jlike');
				}
			}
			if ($reminder_sent_count)
			{
				// Display sent reminders count
				echo Text::sprintf('COM_JLIKE_REMINDERS_SENT_COUNT', $reminder_sent_count);
			}
			else
			{
				echo Text::_('COM_JLIKE_NO_REMINDERS_TO_SENT');
			}
		return;
	}

	/**
	 * Send Todo specific reminder - This needs to move DPE specific component - DPE hack
	 *
	 * Send Reminders for DPE to Users before due date
	 *
	 * @return Array reminder sent deatils
	 */
	public function sendRemindersDpe()
	{
		$reminder_sent_count = $sent   = 0;
		$sent_details = $all_todos = $send = $todos = array();
		$db                  = Factory::getDBO();
		$jlikeparams         = ComponentHelper::getParams('com_jlike');
		$batch_size          = $jlikeparams->get('reminder_batch_size', 0);

		// Load file to call api of the table
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jlike/tables');

		// For the general type of reminder get todos excluding content_ids of the other reminders with the same content_type
		$ltquery = $db->getQuery(true);
		$ltquery->select('distinct c.content_id');
		$ltquery->from($db->quoteName('#__jlike_reminder_contentids') . 'as c');
		$ltquery->join('LEFT', $db->quoteName('#__jlike_reminders') . 'as d on c.reminder_id=d.id');
		$ltquery->where('d.state = 1');
		//$ltquery->where('d.content_type = ' . $db->quote('com_jlike.generic_todo'));

		$query = $db->getQuery(true);
		$query->select('distinct c.id');
		$query->select(
		$db->quoteName(
			array('c.content_id', 'c.assigned_to', 'c.assigned_by', 'c.due_date', 'd.url', 'd.title', 'd.element', 'd.element_id', 'c.sender_msg')
			)
		);

		// Attach reminder_id in the todos with the help of select query
		$query->from($db->quoteName('#__jlike_todos') . 'as c');

		$query->select('todoxref.reminder_id as reminder_id');
		$query->join('LEFT', $db->quoteName('#__jlike_reminders_todo_xref') . 'as todoxref on c.id=todoxref.todo_id');
		$query->join('LEFT', $db->quoteName('#__jlike_reminders') . 'as reminders on reminders.id=todoxref.reminder_id');
		$query->join('LEFT', $db->quoteName('#__jlike_content') . 'as d on c.content_id=d.id');
		$query->join('LEFT', $db->quoteName('#__users') . 'as u on u.id=c.assigned_to');
		$query->where('c.content_id in (' . $ltquery . ')');
		//$query->where('d.element = ' . $db->quote('com_jlike.generic_todo'));
		$query->where('date(c.due_date) = CURDATE() + interval reminders.days_before day');
		$query->where('c.status != ' . $db->quote('C'));

		// Dont sent reminder if the user is blocked
		$query->where('u.block != 1');
		$query->where('NOT EXISTS (select todo_id from ' . $db->quoteName('#__jlike_reminder_sent') . '
		as rs where c.id = rs.todo_id AND reminders.id = rs.reminder_id)');
		$query->setLimit($batch_size);
		$db->setQuery($query);

		$todo_s = $db->loadObjectList();
		$todos = array_merge($todos, $todo_s);

			echo Text::_('COM_JLIKE_REMINDERSENT_DETAILS');

			// Add details in the logger file
			Log::addLogger(
				array(
					// Sets file name
					'text_file' => 'com_jlike.sentreminders.log'
					),
			Log::INFO,
			array('com_jlike')
			);

			if (!empty($todos))
			{
				foreach ($todos as $todo)
				{
					$user           = Factory::getUser($todo->assigned_to);

					// First parameter file name and second parameter is prefix
					$reminder_table = Table::getInstance('reminder', 'JlikeTable', array('dbo', $db));

					// Get jlike_remider_sent for per reminder Check if already reminder sent to the User
					$reminder_table->load(array('id' => (int) $todo->reminder_id));

					// Get content type
					$content_type = explode(".", $todo->element);

					// Tigger to Check content follows all criteria to send the reminder
					PluginHelper::importPlugin('content');
					$title = null;

					// DPE - Hack Title and todo id added in trigger param
					$send  = Factory::getApplication()->triggerEvent('onAfterJlike' . $content_type[1] . 'ContentCheckforReminder',
					array(
						$todo->assigned_to,
						$todo->element_id,
						&$title,
						$todo->id
						)
					);

						// Content is published and not Completed yet
						if (empty($send) || (!empty($send) && $send[0] == 1))
						{
							// Calculate reminder date with the help of current date
							$date           = Factory::getDate();

							// First parameter file name and second parameter is prefix
							$table = Table::getInstance('Remindersent', 'JlikeTable', array('dbo', $db));

							// Get all jlike_remider_sent for per reminder Check if already reminder sent to the User
							$table->load(array('todo_id' => (int) $todo->id, 'reminder_id' => (int) $todo->reminder_id));
							$recipient     = $user->email;
							$subject       = $reminder_table->subject;
							$body          = $reminder_table->email_template;
							$due_date      = HTMLHelper::date($todo->due_date, Text::_('COM_JLIKE_REMINDER_DATE_FORMAT'));

							// Store values of tags in the array
							$this->course_reminder_mail = array();
							$this->course_reminder_mail['content_due_date'] = $due_date;
							$this->course_reminder_mail['username']         = $user->username;
							$this->course_reminder_mail['name']             = $user->name;
							$content_url                                    = Route::_(JURI::base() . $todo->url);
							$this->course_reminder_mail['content_url']      = $content_url;
							$this->course_reminder_mail['content_link']     = '<a href="' . $content_url . '">' . $todo->title . '</a>';
							$this->course_reminder_mail['content_title']    = $todo->title;
							$this->course_reminder_mail['days_before']      = $reminder_table->days_before;

							// DPE hack added to make links clickable
							$this->course_reminder_mail['sender_msg']       = DPE::utilities()->urlToClickableLink($todo->sender_msg);

							// Hack - DPE  Sending School Name in Reminder
							if (!empty($title))
							{
								$this->course_reminder_mail['school_title'] = $title;
							}

							// Replace email body tags
							$body               = TjMail::TagReplace($body, $this->course_reminder_mail);

							// Replace email subject tags
							$subject            = TjMail::TagReplace($subject, $this->course_reminder_mail);

							$config = Factory::getConfig();

							$cc = !empty($reminder_table->cc) ? explode(',', $reminder_table->cc) : null;

							// If from mail is not configured in the reminder then take from Joomla config
							$from = !empty($reminder_table->mailfrom) ? $reminder_table->mailfrom : $config->get('mailfrom');

							// If from name is not configured in the reminder then take from Joomla config
							$fromName = !empty($reminder_table->fromname)?$reminder_table->fromname: $config->get('fromname');
							$replyTo = !empty($reminder_table->replyto) ? $reminder_table->replyto : null;
							$replyToName = !empty($reminder_table->replytoname) ? $reminder_table->replytoname : null;

							$result = Factory::getMailer()->sendMail($from, $fromName, $recipient, $subject, $body, true, $cc, null, null, $replyTo, $replyToName);

							if ($result)
							{
								// Update table in the jlike_reminder_logs  with the sent_on as current_date and time
								$table->reminder_id = $todo->reminder_id;
								$table->todo_id     = $todo->id;
								$table->sent_on     = $date->toSql();
								$table->store();
								$sent = 1;
								$reason = Text::_('COM_JLIKE_REMINDERS_SENT_REASON');
								echo Text::sprintf('COM_JLIKE_REMINDERS_SENT', $user->username, $reminder_table->title, $reminder_table->days_before, $todo->title);
								$reminder_sent_count++;
							}
							else
							{
								// Reminder Mail not sent
								$reason = Text::_('COM_JLIKE_REMINDERS_NOT_SENT_REASON');
							}
						}
						else
						{
							// Content,content_catgory not published or content Completed
							$reason = Text::_('COM_JLIKE_REMINDERS_NOT_SENT_CONTENT_REASON');
						}

						$log = array('username' => $user->username,
									'days_before' => $reminder_table->days_before,
									'contenttitle' => $todo->title,
									'reminder_title' => $reminder_table->title,
									'reason' => $reason,
									'sent' => $sent
									);
						Log::add(json_encode($log), Log::INFO, 'com_jlike');
				}
			}

			if ($reminder_sent_count)
			{
				// Display sent reminders count
				echo Text::sprintf('COM_JLIKE_REMINDERS_SENT_COUNT', $reminder_sent_count);
			}
			else
			{
				echo Text::_('COM_JLIKE_NO_REMINDERS_TO_SENT');
			}

		return;
	}
}
