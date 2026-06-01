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
use Joomla\CMS\Factory;
use Joomla\CMS\Date\Date;

class MailcatcherModelDashboard extends JModelLegacy
{
	protected function getTotalMails($isSent = 1, JDate $date1 = null, JDate $date2 = null)
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(a.id)')
			->from($db->qn('#__mailcatcher_mails', 'a'))
			->where('a.success = ' . (int) $isSent);

		if (null !== $date1 && null !== $date2)
		{
			$query->where('DATE(a.created_date) BETWEEN DATE(' . $db->q($date1->toSql()) . ') AND DATE(' . $db->q($date2->toSql()) . ')');
		}
		elseif (null !== $date1 && null === $date2)
		{
			$query->where('DATE(a.created_date) = DATE(' . $db->q($date1->toSql()) . ')');
		}


		$db->setQuery($query);

		if ($count = $db->loadResult())
		{
			return $count;
		}

		return 0;
	}

	public function getTotalSent()
	{
		return $this->getTotalMails(1);
	}

	public function getTotalFail()
	{
		return $this->getTotalMails(0);
	}

	public function getSentToday()
	{
		$date = Factory::getDate();
		$date = new JDate($date); // Convert to JDate (if available)

		return $this->getTotalMails(1, $date);
	}

	public function getSentThisMonth()
	{
		$date  = Factory::getDate();
		$t     = (int) $date->format('t');
		$m     = (int) $date->format('m');
		$y     = $date->format('Y');
		$date1 = Factory::getDate($y . '-' . $m . '-01');
		$date2 = Factory::getDate($y . '-' . $m . '-' . $t);

		return $this->getTotalMails(1, $date1, $date2);
	}
}