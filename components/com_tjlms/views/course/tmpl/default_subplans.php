<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\PluginHelper;

	// If user is already enrolled for course and order status of user is completed(C).
	if ($this->checkIfUserEnroled == 1 && !$this->item->allowBuy && !$this->item->allowEnroll && $this->item->userOrder->status == "C")
	{
		$this->sub_msg = ($this->item->userEnrollment->unlimited_plan) ? Text::_('COM_TJLMS_UNLIMITED_SUB_PLAN') : Text::sprintf('COM_TJLMS_SUB_PLAN_EXPIRE_DATE', $this->item->userEnrollment->end_time);
		?>

		<div class="center alert alert-success"><?php echo $this->sub_msg;?></div>
		<?php
	}
	elseif ($this->item->userEnrollment->expired == 1 && $this->item->allowBuy)
	{
		?>
		<div class="center alert alert-danger"><?php echo Text::_('COM_TJLMS_SUBS_EXPIRED');?></div>
	<?php
	}

	if ($this->item->userOrder->status != "C" || ($this->item->userOrder->status == "C") && $this->item->userEnrollment->expired == 1)
	{?>
		<div class="enrollHtml panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-info-circle"></i>
				<span class="course_block_title"><?php echo Text::_('COM_TJLMS_SUB_PLAN_INFO')?></span>
			</div>
			<div class="panel-content tjlms_course_plan">

				<table class="table table-condensed ">
					<?php
					if (empty($this->item->subscriptionPlans))
					{
						?>
						<div class="alert alert-danger"><?php echo Text::_('COM_TJLMS_NO_SUBSCRIPTION_PLAN_ACCESS');?></div>
						<?php
					}
					else
					{
						foreach ($this->item->subscriptionPlans as $sub_plans)
						{
							?>
							<tr>
								<td class="plan_name">
							<?php	if ($sub_plans->time_measure == 'unlimited')
										echo $sub_plans->time_measure;
									else
										echo $sub_plans->duration." ".$sub_plans->time_measure;
									?>
								</td>
								<td class="plan_value textright green tjlms-bold-text">
									<?php echo $this->tjlmshelperObj->getFromattedPrice($sub_plans->price, $currency);	?>
								</td>
							</tr>
						<?php
						}
					}?>
				</table>

	<?php if ($this->item->userOrder->id && $this->item->userOrder->status == "P"){ ?>

		<div class="alert alert-warning"><?php	echo Text::_('TJLMS_ORDER_PENDING_STATE');	?></div>
	<?php }
		elseif (!empty($this->item->userOrder) && $this->item->allowBuy && $this->item->userEnrollment->expired != 1)
		{
			switch ($this->item->userOrder->status)
			{
				case 'D':
					$orderStatus = Text::_('LMS_PSTATUS_DECLINED');
				break;
				case 'E':
					$orderStatus = Text::_('LMS_PSTATUS_FAILED');
				break;
				case 'UR':
					$orderStatus = Text::_('LMS_PSTATUS_UNDERREVIW');
				break;
				case 'RF':
					$orderStatus = Text::_('LMS_PSTATUS_REFUNDED');
				break;
				case 'CRV':
					$orderStatus = Text::_('LMS_PSTATUS_CANCEL_REVERSED');
				break;
				case 'RV':
					$orderStatus = Text::_('LMS_PSTATUS_REVERSED');
				break;
			}

			?>
				<div class="alert alert-warning"><?php	echo Text::sprintf('TJLMS_ORDER_STATE', $orderStatus);	?></div>
				<?php
		}

	?>
		<?php
			if ($this->item->allowBuy && !empty($this->item->subscriptionPlans))
			{
				$courseData = array();
				$courseData['id'] = $this->item->id;
				$courseData['allowBuy'] = $this->item->allowBuy;
				$courseData['checkPrerequisiteCourseStatus'] = $this->checkPrerequisiteCourseStatus;
				echo LayoutHelper::render('course.buy', $courseData);

				// Plugin trigger to render course plans
				PluginHelper::importPlugin('payplans', 'tjlms');
				Factory::getApplication()->triggerEvent('onContentRenderPlans', array($this->item));
			}
			?>
		</div>
	</div>
<?php
	}
?>
