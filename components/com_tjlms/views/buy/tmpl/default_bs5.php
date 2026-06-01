<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

HTMLHelper::_('behavior.formvalidator');

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.renderModal', 'a.tjmodal');
HTMLHelper::script('components/com_tjlms/assets/js/fuelux2.3loader.min.js');
HTMLHelper::script('components/com_tjlms/assets/js/steps.min.js');
HTMLHelper::stylesheet('media/techjoomla_strapper/bs3/css/bootstrap.css');
HTMLHelper::_('stylesheet', 'components/com_tjlms/bootstrap/css/bootstrap.min.css');
$document = Factory::getDocument();
$document->addScript('media/techjoomla_strapper/js/namespace.min.js');

if (JVERSION < '4.0.0')
{
	HTMLHelper::_('behavior.calendar');
}

$document = Factory::getDocument();
include_once JPATH_ROOT.DS.'administrator/components/com_tjlms/js_defines.php';

$root_url = Uri::root();
$step_no = 1;

if (empty($this->gateways))
{
	?>
		<div class="alert alert-danger">
			<?php	echo Text::_('COM_TJLMS_NOPAYMENT_GATEWAYS_CONFIGURED');?>
		</div>
	<?php

	return false;
}

if (empty($this->subsPlan))
{
	?>
		<div class="alert alert-danger">
			<?php	echo JText::_('COM_TJLMS_NOSUBSCRIPTION_PLAN_CONFIGURED');?>
		</div>
	<?php

	return false;
}

$courseLink = $this->tjlmsFrontendHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $this->course_id, false);

?>

<script type="text/javascript">

	var root_url	=	"<?php echo JURI::base();?>";

	function cancelProcess()
	{
		var r = confirm("<?php echo Text::_('COM_TJLMS_CANCEL_PROCESS',true); ?>");

		if (r==true)
		{
			window.location.assign("<?php echo $courseLink; ?>");
			//window.location.assign("http://localhost/lms32/index.php/lms-course");
		}
		else
		{
			return false;
		}
	}

</script>

<div class="techjoomla-bootstrap <?php echo COM_TJLMS_WRAPPER_DIV; ?> com_tjlms_content tjBs3">
	<div class="container-fluid">
		<div class="tjlms-form">
			<div class="wizard-example">
				<div class="tjlms_steps_parent row tjlms-filters">
					<!--MyWizard-->
					<div id="MyWizard" class="wizard row">
						<!--<ul class=" steps nav " id="tjlms-steps">-->
						<ol class="tjlms-steps-ol steps clearfix row" id="tjlms-steps">
							<li id="id_step_select_subsplan" data-target="#step_select_subsplan" class="active col-xs-4">
								<span class="badge badge-info d-inline"><?php echo $step_no; $step_no++;?></span>
								<span class="steps-title pl-10 hidden-phone hidden-tablet d-md-inline hidden-xs"><?php echo Text::_('COM_TJLMS_SELECT_SUBSCRIPTION_PLANS');?></span>
								<span class="chevron"></span>
							</li>

							<li id="id_step_billing_info" class="col-xs-4" data-target="#step_billing_info">
								<span class="badge d-inline"><?php echo $step_no; $step_no++;?></span>
								<span class="steps-title pl-10 hidden-phone hidden-tablet d-md-inline hidden-xs"><?php echo Text::_('COM_TJLMS_SELECT_BILLING_STEP');?></span>
								<span class="chevron"></span>
							</li>

							<li id="id_step_payment_info" class="col-xs-4" data-target="#step_payment_info" id="payment-info-li">
								<span class="badge d-inline"><?php echo $step_no;$step_no++;?></span>
								<span class="steps-title pl-10 hidden-phone hidden-tablet d-md-inline hidden-xs"><?php echo Text::_('COM_TJLMS_PAYMENT_STEP');?></span>
								<span class="chevron"></span>
							</li>
						</ol>
					</div>
					<!--MyWizard END-->

					<!--tab-content step-content-->

					<div class="tab-content step-content">
						<!--CONTENT FOR SELECTING SUBSCRIPTION-->
						<div class="tab-pane step-pane active" id="step_select_subsplan">
						<?php
								$selectSubsplans = $this->tjlmsFrontendHelper->getViewpath('com_tjlms','buy','selectSubsplans','SITE','SITE');

								ob_start();
								include($selectSubsplans);
								$html = ob_get_contents();
								ob_end_clean();

								echo $html;
							   ?>
						</div>

						<div class="tab-pane step-pane" id="step_billing_info">
							<?php
								$billpath = $this->tjlmsFrontendHelper->getViewpath('com_tjlms','buy','default_billing','SITE','SITE');

								ob_start();
								include($billpath);
								$html = ob_get_contents();
								ob_end_clean();

								echo $html;
							?>
						</div>
						<div class="tab-pane step-pane" id="step_payment_info">
						</div>

					</div>
					<!--End tab-content step-content-->

					<br>
					<!--prev_next_wizard_actions-->
					<?php
					/*<div class="prev_next_wizard_actions">
						<div class="form-actions">
							<div class="col-xs-12 col-sm-8 col-md-9">
								<button id="btnWizardPrev" type="button" class="btn btn-prev pull-left" style="display:none;" > <i class="fa fa-arrow-circle-o-left" ></i><?php echo Text::_('COM_TJLMS_PREV_BUTTON'); ?></button>
							</div>
							<div class="col-xs-12 col-sm-2 col-md-1">
								<button id="tjlmscancel" type="button" class="btn btn-danger" onclick="cancelProcess()"><?php echo Text::_('COM_TJLMS_CANCEL');?></button>
							</div>
							<div class="col-xs-12 col-sm-2">
								<button id="btnWizardNext" type="button" class="btn btn-success" data-last="Finish" ><?php echo Text::_('COM_TJLMS_SAVE_AND_NEXT');?><i class="fa fa-arrow-circle-o-right"></i></button>
							</div>
						</div>
					</div>
					*/?>
					<div class="prev_next_wizard_actions" style="display: block;">
						<div class="form-actions text-center">
							<span class="list-group-item-heading">
								<button id="btnWizardPrev" type="button" class="btn btn-primary mb-10 mr-5" style="display: none;">
									<i class="fa fa-arrow-circle-o-left"></i>
									<?php echo Text::_('COM_TJLMS_PREV_BUTTON');?>
								</button>
							</span>
							<span class="list-group-item-heading">
								<button id="btnWizardNext" type="button" class="btn btn-primary mb-10 mr-5" data-last="Finish">
									<?php echo Text::_('COM_TJLMS_SAVE_AND_NEXT');?>
									<i class="fa fa-arrow-circle-o-right"></i>
								</button>
							</span>
							<span class="list-group-item-heading">
								<button id="tjlmscancel" type="button" class="btn btn-primary mb-10" onclick="cancelProcess()">
									<?php echo Text::_('COM_TJLMS_CANCEL');?>
								</button>
							</span>
						</div>
					</div>
					<!--END prev_next_wizard_actions-->
				</div>
			</div>
		</div>
	</div>
</div>
