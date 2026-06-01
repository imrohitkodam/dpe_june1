<?php
/*------------------------------------------------------------------------
  Mail Catcher - Email logging extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   https://www.solidres.com
  @Copyright Copyright (C) 2016 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

defined('_JEXEC') or die;

class MailcatcherModelMails extends JModelList
{
	public function __construct(array $config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'sent_from_mail', 'a.sent_from_mail',
				'sent_from_name', 'a.sent_from_name',
				'subject', 'a.subject',
				'message', 'a.message',
				'created_date', 'a.created_date',
				'receivers', 'a.receivers',
				'referer', 'a.referer',
				'ip', 'a.ip',
				'cc', 'a.cc',
				'bcc', 'a.bcc',
				'success', 'a.success',
				'mailer', 'a.mailer',
			);
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.created_date', $direction = 'DESC')
	{
		$value = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $value);

		$value = $this->getUserStateFromRequest($this->context . '.filter.created_date', 'filter_created_date', '', 'string');
		$this->setState('filter.created_date', $value);

		$value = $this->getUserStateFromRequest($this->context . '.filter.sent_status', 'filter_sent_status', '', 'string');
		$this->setState('filter.sent_status', $value);

		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.created_date');
		$id .= ':' . $this->getState('filter.from_date', '');
		$id .= ':' . $this->getState('filter.to_date', '');
		$id .= ':' . $this->getState('filter.sent_status', '');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select($this->getState('list.select', 'a.id, a.subject, a.message, a.receivers, a.referer, a.ip, a.unread,'
				. 'a.cc, a.bcc, a.created_date, a.is_html, a.attachments, a.sent_from_mail, a.sent_from_name, a.success, a.mailer, a.error_message'))
			->from($db->qn('#__mailcatcher_mails', 'a'));

		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->q('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));

				if (strpos($search, '@') !== false)
				{
					$orWhere = array(
						'a.receivers LIKE ' . $search,
						'a.cc LIKE ' . $search,
						'a.bcc LIKE ' . $search,
						'a.sent_from_mail LIKE ' . $search,
					);
					$query->where('(' . join(' OR ', $orWhere) . ')');
				}
				else
				{
					$orWhere = array(
						'a.subject LIKE ' . $search,
						'a.message LIKE ' . $search,
						'a.sent_from_mail LIKE ' . $search,
						'a.ip LIKE ' . $search,
					);
					$query->where('(' . join(' OR ', $orWhere) . ')');
				}
			}
		}

		$date     = $this->getState('filter.created_date');
		$fromDate = $this->getState('filter.from_date');
		$toDate   = $this->getState('filter.to_date');

		if (!empty($date) || (!empty($fromDate) && !empty($toDate)))
		{
			try
			{
				if (!empty($date))
				{
					$query->where('DATE(a.created_date) = DATE(' . $db->q(JDate::getInstance($date)->toSql()) . ')');
				}
				else
				{
					JDate::getInstance($fromDate);
					JDate::getInstance($toDate);
					$query->where('DATE(a.created_date) BETWEEN DATE(' . $db->q($fromDate) . ') AND DATE(' . $db->q($toDate) . ')');
				}

			}
			catch (Exception $e)
			{

			}
		}

		$sentStatus = $this->getState('filter.sent_status');

		if (is_numeric($sentStatus))
		{
			$query->where('a.success = ' . (int) $sentStatus);
		}

		$orderCol  = $this->state->get('list.ordering', 'a.created_date');
		$orderDirn = $this->state->get('list.direction', 'DESC');
		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

		return $query;
	}
}
