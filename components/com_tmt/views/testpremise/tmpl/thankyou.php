<?php
/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */
// no direct access
defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');

JHtml::_('jquery.framework');

$document=JFactory::getDocument();
$document->addStylesheet(JUri::root().'components/com_tmt/assets/css/tmt.css');
// Load countdown js and css
$document->addStylesheet(JUri::root().'components/com_tmt/assets/css/jquery.countdown.css');
$document->addScript(JUri::root().'components/com_tmt/assets/js/jquery.countdown.js');

//Load admin language file
$lang = JFactory::getLanguage();
$lang->load('com_tmt', JPATH_ADMINISTRATOR);
$canEdit = JFactory::getUser()->authorise('core.edit', 'com_tmt');
if (!$canEdit && JFactory::getUser()->authorise('core.edit.own', 'com_tmt')) {
	$canEdit = JFactory::getUser()->id == $this->item->created_by;
}
?>

<script type="text/javascript">
	jQuery(function()
	{
		jQuery('#countdown_timer').countdown({
			until:<?php echo ($this->item->attempt['time_taken'])?>,
			onExpiry: liftOff,
			onTick: watchCountdown
		});
	});
</script>

<div id="tmt_test" class="row-fluid">
	<div class="span12">

		<!-- set componentheading -->
		<h2 class="componentheading"><?php echo JText::_('COM_TMT_TEST_APPEAR_THANKYOU_PAGE_HEADING');?></h2>

		<div class="row-fluid">
			<div class="span12">
				<hr class="hr hr-condensed"/>
			</div>
		</div><!--row-fluid-->

		<div class="row-fluid">
			<div class="span12">

				<div class="well">
					<div class="row-fluid">
						<div class="span7">
							<?php
							if( isset($this->item->attempted_count) && isset($this->item->q_count) && ($this->item->q_count > 0) )
							{
								$question_progress =( 100 * $this->item->attempted_count ) / $this->item->q_count;
							}
							?>

							<div class="row-fluid">
								<div class="span12">
									<strong><?php echo JText::_('COM_TMT_TEST_APPEAR_MSG_THANK_YOU'); ?></strong>
								</div><!--span12-->
							</div><!--row-fluid-->

							<div class="row-fluid">
								<div class="span8">
									<div class="progress progress-success progress-mini no-margin">
										<div class="bar" style="width:<?php echo $question_progress;?>%;"></div>
									</div>
								</div>
								<div class="span4">
									<strong class="small">
										<?php
										if( isset($this->item->attempted_count) && isset($this->item->q_count) && ($this->item->q_count > 0) )
										{
											echo JText::sprintf('COM_TMT_TEST_APPEAR_ATTEMPTED_OF', $this->item->attempted_count, $this->item->q_count);
										}
										?>
									</strong>
								</div>
							</div><!--row-fluid-->
						</div>

						<div class="span5">

							<div class="row-fluid">
								<div class="span12">

									<div class="row-fluid">
										<div class="span12">
											<strong><?php echo JText::_('COM_TMT_TEST_APPEAR_TIME_TAKEN');?></strong>
										</div>
									</div>

									<div class="row-fluid">
										<div class="span4">
											<?php echo intval($this->item->attempt['time_taken'] / (60*60));?>
										</div>
										<div class="span4">
											<?php echo intval($this->item->attempt['time_taken'] / 60 );?>
										</div>
										<div class="span4">
											<?php echo intval( $this->item->attempt['time_taken'] % (60) );?>
										</div>
									</div>

									<div class="row-fluid">
										<div class="span4">
											<?php echo JText::_('COM_TMT_HOURS');?>
										</div>
										<div class="span4">
											<?php echo JText::_('COM_TMT_MINUTES');?>
										</div>
										<div class="span4">
											<?php echo JText::_('COM_TMT_SECONDS');?>
										</div>
									</div>


								</div><!--span12-->
							</div><!--row-fluid-->

							<div id='countdown_timer'></div>
						</div>

					</div><!--row-fluid-->
				</div>

			</div><!--span12-->
		</div><!--row-fluid-->

	</div><!--span12-->
</div><!--row-fluid-->
