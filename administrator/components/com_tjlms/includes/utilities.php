<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * TJLms utility class for common methods
 *
 * @since  _DEPLOY_VERSION_
 */
class TjlmsUtilities
{
	/**
	 * Change seconds to readable format
	 *
	 * @param   int      $init  Time in second
	 * @param   boolean  $full  flag for get full time format
	 *
	 * @return  string
	 */
	public function secToHours($init, $full = true)
	{
		$hours   = floor($init / 3600);
		$minutes = floor(($init / 60) % 60);
		$seconds = $init % 60;

		$hoursLang = Text::sprintf('COM_TJLMS_HOUR', $hours);

		if ($hours > 1)
		{
			$hoursLang = Text::sprintf('COM_TJLMS_HOURS', $hours);
		}

		$minsLang = Text::sprintf('COM_TJLMS_MINUTE', $minutes);

		if ($minutes > 1)
		{
			$minsLang = Text::sprintf('COM_TJLMS_MINUTES', $minutes);
		}

		$secsLang = Text::sprintf('COM_TJLMS_SECOND', $seconds);

		if ($seconds > 1)
		{
			$secsLang = Text::sprintf('COM_TJLMS_SECONDS', $seconds);
		}

		if ($hours)
		{
			if ($full)
			{
				return $hoursLang . $minsLang . $secsLang;
			}

			return $hoursLang . $minsLang;
		}
		elseif ($minutes)
		{
			if ($full)
			{
				return $hoursLang . $minsLang . $secsLang;
			}

			return $minsLang;
		}
		else
		{
			if ($full)
			{
				return $minsLang . $secsLang;
			}

			return $secsLang;
		}
	}

	/**
	 * Give time to start the upcoming course
	 *
	 * @param   date  $courseStartDate  course start date
	 *
	 * @return  string|void
	 */
	public function enrolBtnText($courseStartDate)
	{
		$currentDateTime = Factory::getDate()->toSql();

		if ($courseStartDate >= $currentDateTime)
		{
			$startDate      = new DateTime(Factory::getDate($courseStartDate, 'UTC'));
			$currentDate    = new DateTime(Factory::getDate($currentDateTime, 'UTC'));
			$dateDiff       = date_diff($currentDate, $startDate);
			$remainingDays  = $dateDiff->d;
			$remainingHours = $dateDiff->h;

			if ($remainingDays == 1)
			{
				$enrolBtnText = Text::_('COM_TJLMS_COURSE_START_TOMORROW');
			}
			elseif ($remainingDays == 0)
			{
				$enrolBtnText = Text::sprintf('COM_TJLMS_COURSE_START_HOURS', $remainingHours);
			}
			else
			{
				$enrolBtnText = Text::sprintf('COM_TJLMS_COURSE_START_DAYS', $remainingDays);
			}

			return $enrolBtnText;
		}
	}
}
