<?php
/**
 * @package    Com_Journey
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('jquery.token');
HTMLHelper::_('script', '/media/system/js/messages.min.js');

$options['relative'] = true;
HTMLHelper::_('script', 'com_timelog/timelog.js', $options);
HTMLHelper::_('script', 'com_sla/slaActivity.js', $options);

$app  = Factory::getApplication();
$tmpl = $app->input->getString('tmpl', '');

// Check template component set or not.
if (!empty($tmpl))
{
	$doc = Factory::getDocument();
	$doc->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
	$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
}

if (!$this->item->id)
{
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}


// DPE - Hack  - Start
JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
$params = DPE::config();
$dateTimeFormat = (String) $params->get('dateTimeFormat');
// DPE - Hack  - End
$db = Factory::getDbo();
$nullDate = $db->getNullDate();

?>

<div>
	<div class="">
		<div class="">
			<div class="">
				<div class="timelog-add-form activity-edit front-end-edit ml-20 mr-20">
					<?php if (!$this->canCreateSlaActivity) : ?>
						<h3>
							<?php throw new Exception(Text::_('COM_TIMELOG_ERROR_MESSAGE_NOT_AUTHORISED'), 403); ?>
						</h3>
					<?php endif; ?>
					<button type="button" class="close" onclick="timeLog.closePopup();">&times;</button>
					<h3 class="activity-header">
						<?php echo Text::_('COM_SLA_SLA_ACTIVITY_DETAILS'); ?>
						<!-- Add more button commented -->
					</h3>
					<div class="clearfix"></div>
					<form id="adminForm" action="" method="post" class="form-validate form-horizontal ticketPopupForm" enctype="multipart/form-data">
						<div class="control-group">
							<div class="control-label">
								<strong>
										<?php echo Text::_('COM_SLA_ORGANISATION'); ?>
								</strong>
							</div>
							<div class="controls">
								<?php echo $this->item->organisation; ?>
						   </div>
						</div>
						<div class="control-group">
							<div class="control-label">
								<strong>
										<?php echo Text::_('COM_SLA_SLA_ACTIVITY_FORM_LBL_LEAD_CONSULTANT'); ?>
								</strong>
							</div>
							<div class="controls">
								<?php echo Factory::getUser($this->item->lead_consultant_id)->name; ?>
						   </div>
						</div>
						<div class="control-group">
							<div class="control-label">
								<strong>
										<?php echo Text::_('COM_SLA_SLA_ACTIVITY_FORM_LBL_ACTIVITY_TYPE'); ?>
								</strong>
							</div>
							<div class="controls">
								<?php echo $this->item->activityTypesTitle; ?>
						   </div>
						</div>
						<div class="control-group">
							<div class="control-label">
								<strong>
										<?php echo Text::_('COM_SLA_SLA_ACTIVITY_FORM_LBL_ORGANISATION_MEMBERS'); ?>
								</strong>
							</div>
							<div class="controls">
								<?php
										if ($this->item->cluster_user)
										{
											echo Factory::getUser($this->item->cluster_user)->name;
										}
										else
										{
											echo '-';
										}
								 ?>
						   </div>
						</div>
						<div class="control-group">
							<div class="control-label">
								<strong>
										<?php echo Text::_('COM_SLA_SLA_ACTIVITY_FORM_LBL_TITLE'); ?>
								</strong>
							</div>
							<div class="controls">
								<?php echo $this->form->getValue('activity_name'); ?>
						   </div>
						</div>
						<div class="control-group">
							<div class="control-label">
								<strong>
										<?php echo Text::_('COM_SLA_SLA_ACTIVITY_FORM_LBL_DUE_DATE'); ?>
								</strong>
							</div>
							<div class="controls">
								<?php
									if ($this->form->getValue('due_date') != $nullDate)
									{
										echo HTMLHelper::_('date', $this->escape($this->form->getValue('due_date')), $dateTimeFormat, false);
									}
									else
									{
										echo '-';
									}								?>
						   </div>
						</div>
						<div class="control-group">
							<div class="control-label">
								<strong>
										<?php echo Text::_('COM_SLA_SLA_ACTIVITY_FORM_LBL_DESC'); ?>
								</strong>
							</div>
							<div class="controls">
								<?php echo $this->form->getValue('activity_desc'); ?>
							</div>
						</div>
							<div class="control-label">
								<strong>
										<?php echo Text::_('COM_SLA_SLA_ACTIVITY_LIST_VIEW_TODO_STATUS'); ?>
								</strong>
							</div>
							<div class="controls">
								<?php
										switch ($this->item->todo_status)
										{
											case "I":
												echo Text::_('COM_SLA_INCOMPLETE_STATUS');
												break;
											case "C":
												echo Text::_('COM_SLA_COMPLETE_STATUS');
												break;
											case "CN":
												echo Text::_('COM_SLA_CANCELLED_STATUS');
												break;
											default:
												echo "-";
										}
								?>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
