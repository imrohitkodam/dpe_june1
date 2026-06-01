<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die();
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

jimport('techjoomla.common');

/*if user is on payment layout and log out at that time undefined order is is found
in such condition send to home page or provide error msg
*/
$app  = Factory::getApplication();
$session = Factory::getSession();
$order_currency = $this->params->get('currency');
$this->comtjlmsHelper = new comtjlmsHelper;
$this->tjlmsCoursesHelper	= new tjlmsCoursesHelper;

if (!$this->order_authorized)
{
	$app->enqueueMessage(Text::_('COM_TJLMS_ORDER_UNAUTHORISED'), 'error');
    $app->setHeader('status', 403, true);

	return false;
}

?>
<div class="tjBs3">
	<div id="printDiv">
		<div class="container-fluid payment-orders pb-30 mt-25 px-25">
			<div class="row mt-30">
				<div class="col-md-2 col-sm-3 col-xs-12">
				  <?php
				  if(isset($this->orderDetails->image))
				  {
					  $imageToUse = $this->tjlmsCoursesHelper->getCourseImage((array)$this->orderDetails,'S_');
					  ?>
					  <img class="img-polaroid" src="<?php echo $imageToUse;?>">
				  <?php	}	?>
				</div>
				<div class="col-md-10 col-sm-9 col-xs-12">
					<?php $isModal = $this->input->get('tmpl', '', 'STRING'); ?>
						<?php if ($isModal != 'component'): ?>
						<?php $courselink = $this->comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $this->orderDetails->course_id); ?>
						<?php if(isset($usedinemail)&& $usedinemail == 1){
							$this->coursesItemId = $this->comtjlmsHelper->getitemid('index.php?option=com_tjlms&view=courses');
							$courselink = Route::_(Uri::root().'index.php?option=com_tjlms&view=course&id=' . $this->orderDetails->course_id . '&Itemid=' . $this->coursesItemId);
							}
					?>
					<div class="col-xs-12">
						  <a class="order-heading" href="<?php echo $courselink; ?>">
						  <?php endif; ?>
							  <h1 class="mt-0"><?php echo $this->orderDetails->title; ?></h1>
						  <?php if ($isModal != 'component'): ?>
						  </a>
					  </div>
					  <?php endif; ?>
					  
					  <div class="col-xs-12 col-sm-6 fs-15">
						  <!--USER INFO START-->
						  <?php if (!empty($this->billinfo)): ?>
						  <address class="text-break mb-10">
							<!--
							  <div><?php //echo JText::_('COM_TJLMS_TO'); ?></div>
							-->
							  <div class="font-600"><?php echo $this->billinfo->firstname.' '.$this->billinfo->lastname;?></div>
							  <div><?php echo $this->billinfo->user_email;?></div>
							  <div><?php echo $this->billinfo->phone;?></div>
							  <div><?php echo $this->billinfo->address.' '.$this->billinfo->city;?></div>
							  <div><?php echo $this->billinfo->zipcode.' '.$this->billinfo->state_code.' '.$this->billinfo->country_code;?></div>
							  <?php if (!empty($this->billinfo->vat_number)): ?>
								  <div><?php echo $this->billinfo->vat_number;?></div>	
							  <?php endif;?>
						  </address>
						  <!--USER INFO ENDS-->
						  <?php endif;?>
					  </div>
					  
					  <div class="pull-right col-sm-6 col-xs-12 fs-15">
						<!--SHOW COMAPNY DETAILS-->
						<address>
						  <div><?php echo $this->params->get('company_name','','STRING'); ?></div>
						  <div><?php echo $this->params->get('company_address','','STRING'); ?></div>
						  <div><?php echo $this->params->get('company_vat_no','','STRING'); ?></div>
						</address>
						<!--COMPANY DETAILS ENDS-->
						  <div>
							  <strong><?php echo Text::_('COM_TJLMS_ORDER_ID'); ?></strong>  <?php echo $this->orderinfo->orderid_with_prefix ; ?>
						  </div>
						  <!--SHOW ORDER STATUS-->
						  <div>
							  <strong><?php echo Text::_('COM_JTJLMS_ORDER_PAYMENT_STATUS'); ?></strong>  <?php echo $this->paymentStatus[$this->orderinfo->status]; ?>
						  </div>
						  <!--SHOW ORDER STATUS-->
						  <div class="mb-10">
							  <strong><?php echo Text::_('COM_TJLMS_ORDER_DATE'); ?></strong>  <?php echo $this->orderinfo->local_cdate; ?>
						  </div>
					  </div>
				</div>
		  </div><!--ROWFLUID ENDS-->

			<!--SUBSCRIPTON PLANS DETAILS START HERE-->
			<div class="row">
				<hr class="hr hr-condensed"/>
				<div class="col-xs-12">
					<div class="table-responsive">
						<table class="table table-bordered font-600">
							 <tr>
								<th colspan="3" class="text-center"><?php echo Text::sprintf('COM_TJLMS_PLAN_INFO',$this->orderDetails->title); ?></th>
							</tr>
							<tr class="bg-grey">
								<th align="left" ><?php echo Text::_('COM_TJLMS_PRODUCT_NAM'); ?></th>
								<th align="left"  ><?php echo Text::_('COM_TJLMS_PRODUCT_PRICE'); ?></th>
								<th align="left" ><?php echo Text::_('COM_TJLMS_PRODUCT_TPRICE'); ?></th>
							</tr>

							<?php
								$showoptioncol=0;
								$i=1;
								$order = $this->orderitems[0];
									$totalprice = 0;
									if (!isset($order->price))
									{
										$order->price=0;
									}
								?>
							<tr class="row0">
								<td><?php echo $order->order_item_name;?></td>
								<td>
									<span>
										<?php echo $this->comtjlmsHelper->getFromattedPrice( number_format(($order->price),2),$order_currency);?>
									</span>
								</td>
								<td>
									<span>
										<?php $totalprice = $order->price;
										echo $this->comtjlmsHelper->getFromattedPrice(number_format($totalprice,2),$order_currency); ?>
									</span>
								</td>
							</tr>
							<tr>
								<?php
								$col = 1;

								if ($showoptioncol==1)
								{
									$col=2;
								}
								?>
								<td colspan="<?php echo $col;?>" > </td>
								<td align="left">
									<strong><?php echo Text::_('COM_TJLMS_PRODUCT_TOTAL'); ?></strong>
								</td>
								<td>
									<span>
										<?php echo $this->comtjlmsHelper->getFromattedPrice( number_format($totalprice,2),$order_currency); ?>
									</span>
								</td>
							</tr>
							<!--discount price -->
						<?php
						$coupon_code = trim($this->coupon_code);
						$total_amount_after_disc = $this->orderinfo->original_amount;

						if ($this->orderinfo->coupon_discount > 0)
						{
							$total_amount_after_disc = $total_amount_after_disc-$this->orderinfo->coupon_discount;
						?>
							<tr>
								<td colspan="<?php echo $col;?>" > </td>
								<td align="left">
									<strong>
										<?php echo sprintf(Text::_('COM_TJLMS_PRODUCT_DISCOUNT'),$this->orderinfo->coupon_code); ?>
									</strong>
								</td>
								<td>
									<span>
										<?php echo $this->comtjlmsHelper->getFromattedPrice(number_format($this->orderinfo->coupon_discount,2),$order_currency);
										?>
									</span>
								</td>
							</tr>
							<!-- total amt after Discount row-->
							<tr>
								<td colspan = "<?php echo $col;?>"></td>
								<td align="left">
									<strong><?php echo Text::_('COM_TJLMS_NET_AMT_PAY');?></strong>
								</td>
								<td>
									<span>
										<?php
											echo $this->comtjlmsHelper->getFromattedPrice(number_format($total_amount_after_disc,2),$order_currency);
										?>
									</span>
								</td>
							</tr>
					<?php	}

							if(isset($this->orderinfo->order_tax) and $this->orderinfo->order_tax>0)
							{
								$tax_json = $this->orderinfo->order_tax_details;
								$tax_arr = json_decode($tax_json,true);
							?>
							<tr>
								<td colspan="<?php echo $col;?>" > </td>
								<td align="left"><strong><?php echo Text::_('TAX_AMOUNT'); ?></strong>
									<?php echo $tax_arr['percent']; ?>
								</td>
								<td><span>
										<?php echo $this->comtjlmsHelper->getFromattedPrice(number_format($this->orderinfo->order_tax,2),$order_currency); ?>
									</span>
								</td>
							</tr>
				<?php 	} 	?>
							<tr>
								<td colspan="<?php echo $col;?>" ></td>
								<td align="left"><strong><?php echo Text::_('COM_TJLMS_ORDER_TOTAL'); ?></strong>
								</td>
								<td>
									<strong>
										<span name="final_amt_pay">
											<?php echo $this->comtjlmsHelper->getFromattedPrice(number_format($this->orderinfo->amount,2),$order_currency); ?>
										</span>
									</strong>
							  </td>
						  </tr>
			  </table>
					</div>
				</div>
			</div>
		</div><!-- CONTAINER DIV ENDS-->
	</div><!--PRINT DIV ENDS-->
	
	<div class="container-fluid px-25">
		<div class="text-center">
		  <span>
			  <input type="button" class="btn br-0 font-600 btn-primary mb-20" onclick="tjlms.printDiv('printDiv')" value="<?php echo Text::_('COM_TJLMS_PRINT');?>">
		  </span>
		  <span>
			  <a class="button btn br-0 font-600 btn-primary mb-20 event-btn" href="<?php echo $courselink; ?>">
				<?php echo Text::_('COM_TJLMS_ORDER_BACK_TO_COURSE'); ?>
			  </a>
		  </span>
		</div>
	</div><!-- CONTAINER DIV ENDS -->
</div>

<script type="text/javascript">
if (self != top) 
{
	jQuery('.event-btn').addClass('hide');
}
</script>
