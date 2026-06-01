<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

$userData        = Factory::getUser();

// Show set goal
if ($this->item->enrolled == 1 && $this->item->userEnrollment->state == 1 && $this->item->canEnroll == 1 && $this->item->userCourseTrack->status != "C")
{
	$result = Factory::getApplication()->triggerEvent('onShowSetGoalBtn',array('com_tjlms.course',$this->item->id, $this->item->title));

	if(!empty($result))
	{
		echo $result[0];
	}
}
elseif (!$userData->guest && !$this->item->enrolled && !$this->item->type && $this->canEnroll)
{
	$result = Factory::getApplication()->triggerEvent('onShowSetGoalBtn',array('com_tjlms.course',$this->item->id, $this->item->title));

	if(!empty($result))
	{
		echo $result[0];
	}

}
