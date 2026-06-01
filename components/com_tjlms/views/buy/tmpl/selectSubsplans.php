<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

if (JVERSION <= '3.0')
{
	HTMLHelper::_('behavior.formvalidator');
}

$course_info      = $this->course_info;
$user             = Factory::getUser();
$input            = Factory::getApplication()->input;
$document         = Factory::getDocument();
$baseurl          = Route::_(Uri::root() . 'index.php');
$js_array         = json_encode($this->subsPlan);
$lmsparams        = ComponentHelper::getParams('com_tjlms');
$dateFormatToShow = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');
$techjoomlacommon = new TechjoomlaCommon;

$document->addScriptDeclaration("var tjlms_baseurl='{$baseurl}';");
$document->addScriptDeclaration("var array_sub_plan='{$js_array}';");
$document->addScriptDeclaration("var currency='{$this->currency}';");

$this->tjlmsCoursesHelper	= new tjlmsCoursesHelper;

foreach ($this->subsPlan as $s_plans)
{
	if ($s_plans->time_measure == "unlimited")
	{
		$s_plans->duration = "";
	}

	$subsplan_radio[] = HTMLHelper::_('select.option', $s_plans->id, $s_plans->title . ' (' . $s_plans->duration . ' ' . ucfirst($s_plans->time_measure) . ') ' . Text::_('COM_TJLMS_PLAN_ACCESS'));
}

?>
<script type="text/javascript">
jQuery(document).ready(function($) {
	tjlms.subscription.init();
});
</script>
<form action="<?php echo $this->tjlmsFrontendHelper->tjlmsRoute('index.php?option=com_tjlms&view=buy&course_id='. $this->course_id); ?>" method="post" name="selectionSubsPlanForm" id="selectionSubsPlanForm"	class="">
	<div id="subsplan-info" class="container-fluid">
		<!--course-select-plan-->
		<div class="course-select-plan mt-25 px-30 pb-20 col-xs-12 col-md-8 font-400 fs-15">
			<div class="steps-title txt-upper my-30">
				<h4 class="text-green text-shadow-grey font-600"><?php echo Text::_('COM_TJLMS_SELECT_SUB_PLAN');?></h4>
			</div>
			<div class="row">
				<div class="col-xs-6 col-sm-6">
					<?php if (count($subsplan_radio) == 1)
				{
				?>
					<div class="pt-20"> <?php echo $subsplan_radio[0]->text; ?> </div>

					<input id="coursesubsplan_radio" name="course[subsplan_radio]" type="hidden" value="<?php echo $subsplan_radio[0]->value;?>">
				<?php
				}
				else
				{
					echo HTMLHelper::_('select.genericlist', $subsplan_radio, 'course[subsplan_radio]', 'onchange="tjlms.subscription.caltotal(this.value)" ', "value", "text");
				}
				?>
				</div>
				<div class="col-xs-6 col-sm-6 text-right pt-20">
					<span name="total_amt" id="total_amt" class="">0</span>
					<input type="hidden" value="0"	name="total_amt_inputbox"	id="total_amt_inputbox">
				</div>
			</div>

			<hr class="hr hr-condensed">
			<div id="coupon_troption">
				<div class="form-inline row">
					<div class="col-xs-12">
						<label class="checkbox mr-10"><?php echo Text::_('TJLMS_HAVE_COP');?></label>
						<input type="checkbox" aria-invalid="false" class="ml-5" id="coupon_chk" name="coupon_chk" value="" size="10" onchange="tjlms.subscription.show_cop()">
					</div>
					<div class="col-xs-12 col-sm-6 d-inline-flex mt-20" id="coupon_plan_div">
						<input id="coupon_code" type="text" style="display:none; " class="input-small focused" placeholder="<?php echo Text::_('COM_TJLMS_CUPCODE');?>" name="coupon_code" value="" size="5">
						<input type="button" style="display: none;" name="coup_button" id="coup_button" class="btn btn-primary br-0 btn-medium" onclick="tjlms.subscription.applycoupon()" value="<?php echo Text::_('COM_TJLMS_COUPON_APPLY');?>">
					</div>
				</div>
			</div>

			<hr class="hr hr-condensed">
			<div class="row" style="display:none " id= "dis_cop">
				<div class="col-xs-12 col-sm-6">
					<?php echo Text::_('TJLMS_COP_DISCOUNT');?>
				</div>
				<div class="col-xs-12 col-sm-6 text-right font-700">
					<span id="dis_cop_amt"></span>
					<input type="hidden" value="0"	name="total_amt_inputbox"	id="total_amt_inputbox">
				</div>
				<div class="col-xs-12">
					 <hr class="hr hr-condensed">
				</div>
			</div>

			<div class="row" id="dis_amt">
				<div class="col-xs-6 col-sm-10">
					<?php echo Text::_( 'TJLMS_TOTALPRICE_PAY' ); ?>
				</div>
				<div class="col-xs-6 col-sm-2 text-right font-700">
					<div  class=""><span id="net_amt_pay"	name="net_amt_pay">0</span><?php echo ' '.$this->currency;?></div>
					<input type="hidden" class="inputbox" value="0"	name="net_amt_pay_inputbox"	id="net_amt_pay_inputbox">
				</div>
			</div>

			<?php	if($this->allow_taxation && isset($this->tax_per) and $this->tax_per>0):	?>
			<hr class="hr hr-condensed">
			<div class="row tax_tr">
				<div class="col-xs-6 col-sm-10" >
					<?php echo Text::sprintf('TJLMS_TAX_AMOOUNT',$this->tax_per)."%"; ?>
				</div>
				<div class="col-xs-6 col-sm-2 text-right font-700">
					<span id="tax_to_pay"	name="tax_to_pay">0</span><?php echo ' '.$this->currency;?>
					<input type="hidden" class="inputbox" value="0"	name="tax_to_pay_inputbox"	id="tax_to_pay_inputbox">
				</div>
			</div>

			<hr class="hr hr-condensed">
			<div class="row tax_tr">
				<div class="col-xs-6 col-sm-10">
					<?php echo Text::_( 'TJLMS_TOTALPRICE_PAY_AFTER_TAX' ); ?>
				</div>
				<div class="col-xs-6 col-sm-2 text-right font-700">
					<span id="net_amt_after_tax"	name="net_amt_after_tax">0</span><?php echo ' '.$this->currency;?>
					<input type="hidden" class="inputbox" value="0"	name="net_amt_after_tax_inputbox"	id="net_amt_after_tax_inputbox">
				</div>
			</div>
			<?php endif;	?>
		</div>

		<div class="course-info mt-25 col-xs-12 col-md-4 fs-15 font-400" id="course-info-tab">
			<div class="course-info-inner">
				<div class="steps-title txt-upper my-30">
					<h4 class="text-shadow-grey font-600"><?php echo Text::_('COM_TJLMS_COURSE_INFO');?></h4>
				</div>
				<div class="row">
					<div class="col-xs-12 col-sm-5 pr-0">
					<?php
						if(isset($this->course_info->image))
						{
							$imageToUse = $this->tjlmsCoursesHelper->getCourseImage((array)$this->course_info,'S_');
						?>
							<img src="<?php echo $imageToUse;?>">
					<?php	}	?>
					</div>
					<div class="col-xs-12 col-sm-7">
						<div class="fs-15"><?php echo $course_info->title; ?></div>
					</div>
				</div>

				<div class="row mt-10">
					<div class="col-xs-12 col-sm-6">
						<?php echo Text::_( 'TJLMS_COURSE_PUB_DATE' ); ?>
					</div>
					<div class="col-xs-12 col-sm-6">
						<span><?php echo $techjoomlacommon->getDateInLocal($course_info->start_date, 0, $dateFormatToShow);	?>
						</span>
					</div>
				</div>

				<div class="row mt-5">
					<div class="col-xs-12 col-sm-6">
						<?php	echo Text::_('TJLMS_COURSE_CREATOR');	?>
					</div>
					<div class="col-xs-12 col-sm-6">
						<span><?php	echo $this->creator == 'name' ? $course_info->creator_name->name : $course_info->creator_name->username;	?></span>
					</div>
				</div>
			</div>
		</div>
		<!--course-info-tab-->

		<input type="hidden" name="allow_taxation" id="allow_taxation" value="<?php if($this->allow_taxation and isset($this->tax_per) and $this->tax_per>0) echo $this->allow_taxation;else echo 0;?>" />
		<input type="hidden" name="selected_plan" id="selected_plan" value="" />
		<input type="hidden" name="order_tax" id="order_tax" value="0" />
		<input type="hidden" name="course_id" id="course_id" value="<?php	echo $input->get('course_id','','INT');	?>" />
		<input type="hidden" name="option" value="com_tjlms">
	</div><!--end subsplan-info-->
</form>
