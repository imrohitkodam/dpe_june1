<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$course = $this->item;

if ($this->oluser->guest != 1)
{
	if ($course->type == 1)
	{
		if (!empty($this->courseUserOrderInfo) && ($this->courseUserOrderInfo->status == 'C' || ($this->courseUserOrderInfo->status == 'P' && $this->courseUserOrderInfo->processor != '')) && $this->checkIfUserEnroled === '0')
		{
			$enrolment_pending = 1;
			$showbuy           = 0;
			$warningMessage    = Text::_('TJLMS_APPROVAL_REMAINING');
		}

		if ($this->checkIfUserEnroled && (!isset($this->courseUserOrderInfo->status) || empty($this->courseUserOrderInfo->status)))
		{
			$enrolment_pending   = 1;
			$this->userCanAccess = 0;
			$showbuy             = 1;
			$warningMessage      = Text::_('COM_TJLMS_ADMIN_APPROVE_BUY_COURSE');
		}
	}
	elseif ($this->checkIfUserEnroled === '0')
	{
		$enrolment_pending = 1;
		$showbuy           = 0;
		$warningMessage    = Text::_('TJLMS_APPROVAL_REMAINING');
	}
}

if ($course->enrolled == 1 && $this->courseProgress['status'] != 'C' && !empty($course->assignmentDueDate))
{
	$dateFormatShow = $this->tjlmsparams->get('date_format_show', 'Y-m-d H:i:s');

	$date       = Factory::getDate();
	$todaysDate = new DateTime($date);
	$assignDate = new DateTime($course->assignmentDueDate);
	$dateDiff   = date_diff($todaysDate, $assignDate);
	$days       = $dateDiff->d;
	$hours      = $dateDiff->h;

	if ($date > $course->assignmentDueDate)
	{
		$warningMessage = Text::sprintf('COM_TJLMS_DUE_DATE_TOC', $days, $hours, $this->techjoomlaCommon->getDateInLocal($course->assignmentDueDate, 0, $dateFormatShow));
	}
	else
	{
		$infoWarning = Text::sprintf('COM_TJLMS_ASSIGN_GOING_TO_EXPIRE', $this->techjoomlaCommon->getDateInLocal($course->assignmentDueDate, 0, $dateFormatShow));
	}
}

if ($this->autoEnroll && !$course->type && $this->oluser_id && !$course->enrolled && !$this->userCanAccess && $this->canEnroll)
{
	$infoWarning = Text::sprintf('COM_TJLMS_ENROLLMENT_LATER_MESSAGE');
}

if (!empty($warningMessage))
{
	?>
	<div class="alert alert-warning">
		<?php echo $warningMessage; ?>
	</div>
	<?php
}

if (!empty($infoWarning))
{
	?>
	<div class="alert alert-info">
		<?php echo $infoWarning; ?>
	</div>
	<?php
}
