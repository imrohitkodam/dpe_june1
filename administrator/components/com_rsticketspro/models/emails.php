<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelEmails extends JModelList
{	
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'type', 'lang', 'subject', 'published', 'state', 'language'
			);
		}

		parent::__construct($config);
	}
	
	protected function getListQuery()
	{
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		// get filtering states
		$language 	= $this->getState('filter.language');
		$search 	= $this->getState('filter.search', '');
		$state 		= $this->getState('filter.state');
		
		$query->select('*')
			->from('#__rsticketspro_emails')
			->where($db->qn('lang') . '=' . $db->q($language));

		// search
		if (strlen($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->qn('id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->q('%'.str_replace(' ', '%', $db->escape($search, true)).'%', false);

				$query->where('(' . $db->qn('subject') . ' LIKE ' . $search . ') OR (' . $db->qn('message') . ' LIKE ' . $search . ')');
			}
		}

		// published/unpublished
		if ($state != '')
		{
			$query->where($db->qn('published') . '=' . $db->q($state));
		}

		// order by
		$query->order($db->qn($this->getState('list.ordering', 'type')).' '.$db->escape($this->getState('list.direction', 'asc')));
		
		return $query;
	}
	
	protected function checkMissingEntries()
	{
		$lang = $this->getState('filter.language');
		
		if ($lang)
		{
			$db 	= $this->getDbo();
			$query 	= $db->getQuery(true);
			
			$types = array(
				'add_ticket_customer',
				'add_ticket_notify',
				'add_ticket_reply_customer',
				'add_ticket_reply_staff',
				'add_ticket_staff',
				'notification_email',
				'reject_email',
				'new_user_email',
				'notification_max_replies_nr',
				'notification_replies_with_no_response_nr',
				'notification_not_allowed_keywords',
				'notification_department_change',
				'feedback_followup_email'
			);
			
			$query->select($db->qn('type'))
				  ->from('#__rsticketspro_emails')
				  ->where($db->qn('type') . ' IN (' . implode(',', $db->q($types)) . ')')
				  ->where($db->qn('lang') . '=' . $db->q($lang));
			$db->setQuery($query);
			
			$found = $db->loadColumn();
			
			if ($diff = array_diff($types, $found))
			{
				foreach ($diff as $type)
				{
					$row = JTable::getInstance('Emails', 'RsticketsproTable');
					$row->save(array(
						'lang' => $lang,
						'type' => $type
					));
				}
			}
		}
	}
	
	public function getItems()
	{
		// check if there are missing entries for the current language
		$this->checkMissingEntries();
		
		return parent::getItems();
	}
	
	protected function getDefaultLanguage()
	{
		return JFactory::getLanguage()->get('tag');
	}
	
	protected function populateState($ordering = 'type', $direction = 'asc')
	{
		$this->setState('filter.language', $this->getUserStateFromRequest($this->context.'.filter.language', 'filter_language', $this->getDefaultLanguage()));
		$this->setState('filter.state', $this->getUserStateFromRequest($this->context.'.filter.state', 'filter_state'));
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search'));
		
		// List state information.
		parent::populateState($ordering, $direction);
	}
}