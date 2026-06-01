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
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\HTML\HTMLHelper;

$document = Factory::getDocument();

// getting payment list START
$com_params = ComponentHelper::getParams('com_tjlms');
$session    = Factory::getSession();
$order_id   = $session->get('lms_orderid');

if (empty($order_id))
{
	?>
		<div class="alert alert-danger">
			<?php	echo Text::_('COM_TJLMS_ORDER_INVALID_ORDER_ID');	?>
		</div>
	<?php

	return false;
}
$selected_gateways = $com_params->get('gateways');
//getting GETWAYS
PluginHelper::importPlugin('payment');

if(!is_array($selected_gateways) )
{
	$gateway_param[] = $selected_gateways;
}
else
{
	$gateway_param = $selected_gateways;
}

if(!empty($gateway_param))
{
	$gateways = Factory::getApplication()->triggerEvent('onTP_GetInfo',array($gateway_param));
}
$this->gateways = $gateways;

?>
<script type="text/javascript">
	tjlms.gateway.init();
</script>
<div class="checkout-content row-fluid " id="payment-info-tab">
	<div id="payment-info" class="tjlms-checkout-steps pb-30 mt-25 px-25 form-horizontal container-fluid">
		<!-- show payment option start -->
		<div class="row-fluid">
			<div class="paymentHTMLWrapper span12">
				<hr class="hr hr-condensed"/>
				<div class="control-group">
						<?php
						$default = "";
						$lable = Text::_('COM_TJLMS_SEL_GATEWAY');
						$gateway_div_style = 1;

						// If only one geteway then keep it as selected
						if(!empty($this->gateways) && count($this->gateways)==1)
						{
							// Id and value is same
							$default = $this->gateways[0]->id;
							$lable = Text::_( 'COM_TJLMS_SEL_GATEWAY' );
							$gateway_div_style = 1;
						}
						?>

						<div class="col-xs-12">
							<label class="control-label steps-title txt-upper">
								<h4 class="font-600"><?php echo $lable ?></h4>
							</label>
						</div>
						<div class="col-xs-12">
							<div class="gateways-options" style="<?php echo ($gateway_div_style==1)?"" : "display:none;" ?>">
								<?php
								if(empty($this->gateways))
									echo Text::_( 'COM_TJLMS_NO_PAYMENT_GATEWAY' );
								else
								{
									$ad_fun = "onChange=tjlms.gateway.gatewayHtml(this.value,$order_id)";
									$pg_list = HTMLHelper::_('select.radiolist', $this->gateways, 'gateways', 'class="required" '.$ad_fun.'  ', 'id', 'name', $default,false);
									echo $pg_list;
								}
								?>
							</div>
						</div>

						<?php
						if(empty($gateway_div_style))
						{
							?>
						<div class="controls qtc_left_top">
						<?php echo 	$this->gateways[0]->name; // id and value is same ?>
						</div>
				<?php
						}
						?>
					<div class="clearfix"></div>
					<hr class="hr hr-condensed"/>
					<div class="clearfix"></div>
					<div id="tjlms-payHtmlDiv">
					</div>
					<!-- show payment hmtl form-->
				</div>
				<!-- end of paymentHTMLWrapper-->
			</div>
		<!-- show payment option end -->
	</div>
</div>



