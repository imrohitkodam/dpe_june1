<?php
/**
 * @package     JTicketing
 * @subpackage  com_jticketing
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var $this JLikeViewExtendedTodos */
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
foreach($this->items as $item)
{
?>
	<div>
		<div class="col-xs-12">
			<div class="row  py-5 border-top">
				<div class="col-xs-6 col-sm-6">
			<strong><?php echo $this->escape($item->userName);?></strong>
				</div>
				<div class="col-xs-4 col-sm-4">
					<?php
						$dueDate = Factory::getDate($item->due_date);
						$timeleft = $dueDate->toUnix() - Factory::getDate()->toUnix();
						$daysleft = round((($timeleft/24)/60)/60);

						if ($daysleft < 0)
						{?>
							<i class="fa fa-clock-o" aria-hidden="true"></i> <?php echo HTMLHelper::date($dueDate, Text::_('COM_JLIKE_PAST_DATE_FORMAT'));?>
						<?php
						}
						else
						{
							/**
							 * $days = date_diff(date_create($now),date_create($dueDate));
							 * $diffDays = $days->format(Text::_('COM_JLIKE_DATE_DIFFERENCE_FORMAT'));
							 * $daysOnly = $days->format(Text::_('COM_JLIKE_DATE_DIFFERENCE_FORMAT_DAYS'));
							 */

							switch($daysleft)
							{
								case 0:
									$dueDays = Text::_('COM_JLIKE_DATE_TODAY');
									break;
								case +1:
									$dueDays = Text::_('COM_JLIKE_DATE_TOMORROW');
									break;
								default:
									$dueDays = Text::sprintf('COM_JLIKE_DATE_IN_DAYS', $daysleft);
							}
							?>
							<i class="fa fa-clock-o" aria-hidden="true"></i> <?php echo $dueDays;?>
						<?php
						}
						?>
				</div>
				<div class="col-xs-1 col-sm-1">
					<?php
					if ($item->read)
					{?>
						<i class="fa fa-check-circle-o" aria-hidden="true" title="<?php echo HTMLHelper::date($item->read_date, Text::_('COM_SLA_TIME_FORMAT'));?>"></i>
					<?php
					}
					else
					{?>
						<i class="fa fa-circle-o" aria-hidden="true"></i>
					<?php
					}
					?>
				</div>
				<div class="col-xs-1 col-sm-1">
					<?php
					if ($item->used)
					{?>
						<i class="fa fa-check-circle-o" aria-hidden="true"
						title="<?php echo HTMLHelper::date($item->used_date, Text::_('COM_SLA_TIME_FORMAT'));?>"></i>
						<?php
					}
					else
					{?>
						<i class="fa fa-circle-o" aria-hidden="true"></i>
					<?php
					}
					?>
				</div>
			</div>
		</div>
	</div>
<?php
}
?>
