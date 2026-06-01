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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

jimport('techjoomla.common');

/*if user is on payment layout and log out at that time undefined order is is found
in such condition send to home page or provide error msg
*/

$params = ComponentHelper::getParams( 'com_tjlms' );
$user = Factory::getUser();
$jinput = Factory::getApplication()->input;
$logedin_user = $user->id;
$session = Factory::getSession();
$order_currency = $params->get('currency');
$date_format_show = $params->get('date_format_show', 'Y-m-d H:i:s');
$this->comtjlmsHelper = new comtjlmsHelper;
$this->tjlmsCoursesHelper	= new tjlmsCoursesHelper;
$this->techjoomlacommon = new TechjoomlaCommon;
$paymentStatus['I']=Text::_('COM_TJLMS_PSTATUS_INITIATED');
$paymentStatus['P']=Text::_('COM_TJLMS_PSTATUS_PENDING');
$paymentStatus['C']=Text::_('COM_TJLMS_PSTATUS_COMPLETED');
$paymentStatus['D']=Text::_('COM_TJLMS_PSTATUS_DECLINED');
$paymentStatus['E']=Text::_('COM_TJLMS_PSTATUS_FAILED');
$paymentStatus['UR']=Text::_('COM_TJLMS_PSTATUS_UNDERREVIW');
$paymentStatus['RF']=Text::_('COM_TJLMS_PSTATUS_REFUNDED');
$paymentStatus['CRV']=Text::_('COM_TJLMS_PSTATUS_CANCEL_REVERSED');
$paymentStatus['RV']=Text::_('COM_TJLMS_PSTATUS_REVERSED');

$billinfo = '';

if (isset($this->orderinfo))
{
	$coupon_code = $this->orderinfo[0]->coupon_code;

	if (isset($this->orderinfo[0]->address_type) && $this->orderinfo[0]->address_type == 'BT')
	{
		$billinfo = $this->orderinfo[0];
	}
	else if(isset($this->orderinfo[1]->address_type) && $this->orderinfo[1]->address_type == 'BT')
	{
		$billinfo = $this->orderinfo[1];
	}
}

if (isset($this->orderinfo))
{
	$where = " o.id=" . $this->orderinfo['0']->order_id;

	if ($this->orderinfo['0']->order_id)
	{
		$orderdetails = $this->comtjlmsHelper->getallCourseDetailsByOrder($where);
	}

	$this->orderinfo = $this->orderinfo[0];
}

if($this->order_authorized == 0)
{
?>
<div class="well" >
	<div class="alert alert-error">
		<span ><?php echo Text::_('COM_TJLMS_ORDER_UNAUTHORISED'); ?> </span>
	</div>
</div>
<?php
	return false;
}

	$company_name = $params->get('company_name','','STRING');
	$company_address = $params->get('company_address','','STRING');
	$company_vat_no = $params->get('company_vat_no','','STRING');
?>
<div class="row-fluid">
	<hr class="hr hr-condensed"/>
	<!--SHOW COMAPNY DETAILS ON LEFT SIDE-->
	<div class="col-md-8">
		<div class="col-md-3">
<?php
		if(isset($orderdetails[0]->image))
		{
			$imageToUse = $this->tjlmsCoursesHelper->getCourseImage((array)$orderdetails[0],'S_');
			?>
			<img class="img-polaroid com_tjlms_image_w98pc"
			src="<?php echo $imageToUse;?>">
<?php   }	?>
		</div>
		<div class="col-md-9">
		<?php
			$this->coursesItemId = $this->comtjlmsHelper->getitemid('index.php?option=com_tjlms&view=courses');
			$courselink = Route::_(Uri::root().'index.php?option=com_tjlms&view=course&id=' . $orderdetails[0]->course_id . '&Itemid=' . $this->coursesItemId);
		?>
			<a href="<?php echo $courselink; ?>">
				<span><?php echo $orderdetails[0]->title; ?></span>
			</a>
		</div>
	</div>
	<div>
		<div class="col-md-1"></div>
		<div class="pull-left col-md-6">
			<div><?php 	echo $company_name; ?></div>
			<div><?php 	echo $company_address; ?></div>
			<div><?php 	echo $company_vat_no; ?></div>
		</div>
		<div class="pull-right col-md-5" >
			<div>
				<strong><?php echo Text::_('COM_TJLMS_ORDER_ID'); ?></strong>
				<?php echo $this->orderinfo->orderid_with_prefix ; ?>
			</div>
			<!--SHOW ORDER STATUS-->
			<div class="row-fluid">
				<strong><?php echo Text::_('COM_JTJLMS_ORDER_PAYMENT_STATUS'); ?></strong>  <?php echo $paymentStatus[$this->orderinfo->status]; ?>
			</div>
			<!--SHOW ORDER STATUS-->
			<div>
				<?php $orderDate = $this->techjoomlacommon->getDateInLocal($this->orderinfo->cdate, 0, $date_format_show);	?>
				<strong><?php echo Text::_('COM_TJLMS_ORDER_DATE'); ?></strong>
				<?php echo $orderDate; ?>
			</div>
		</div>
	</div>
	<!--COMPANY DETAILS ENDS-->

	<!--USER BILLING INFO STRAT-->

<?php if ($billinfo): ?>
	<div class="col-md-12">
		<hr class="hr hr-condensed"/>
		<div class="pull-left" >
			<div><?php echo Text::_('COM_TJLMS_TO'); ?></div>
			<div><?php echo $billinfo->firstname.' '.$billinfo->lastname;?></div>
				<?php if ($billinfo->vat_number): ?>
					<div><?php echo $billinfo->vat_number;?></div>
				<?php endif ?>
			<div><?php echo $billinfo->phone;?></div>
			<div><?php echo $billinfo->user_email;?></div>
			<div><?php echo $billinfo->address.' '.$billinfo->city;?></div>
			<div><?php echo $billinfo->zipcode.' '.$billinfo->state_code.' '.$billinfo->country_code;?></div>
		</div>
	</div>
<?php endif ?>
	<!--USER INFO ENDS-->
</div><!--ROWFLUID ENDS-->

<!--SUBSCRIPTON PLANS DETAILS START HERE-->
<div class="row-fluid">
	<hr class="hr hr-condensed"/>
	<div class="col-md-12 "> <!-- plan detail start -->
		<span>
			<?php echo Text::sprintf('COM_TJLMS_PLAN_INFO',$orderdetails[0]->title); ?>
		</span>
		<div class="table-responsive">
			<table class="table">
				<tr>
					<th align="left" >
						<?php echo  Text::_('COM_TJLMS_PRODUCT_NAM'); ?>
					</th>
					<th align="left"  >
						<?php echo Text::_('COM_TJLMS_PRODUCT_PRICE'); ?>
					</th>
					<th align="left" >
						<?php echo Text::_('COM_TJLMS_PRODUCT_TPRICE'); ?>
					</th>
				</tr>
				<?php
					$showoptioncol=0;
					$order = $this->orderitems[0];
					$totalprice = 0;
					if (!isset($order->price))
					{
						$order->price=0;
					}
				?>
				<tr class="row">
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
				<tr><td colspan="3">&nbsp;</td></tr>
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
						<strong>
						<?php echo Text::_('COM_TJLMS_PRODUCT_TOTAL'); ?>
						</strong>
					</td>
					<td>
						<span>
							<?php echo $this->comtjlmsHelper->getFromattedPrice( number_format($totalprice,2),$order_currency); ?>
						</span>
					</td>
				</tr>
				<!--discount price -->
			<?php
			$coupon_code = trim($coupon_code);
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
					<td colspan ="<?php echo $col;?>"></td>
					<td align="left">
						<strong>
							<?php echo Text::_('COM_TJLMS_NET_AMT_PAY');?>
						</strong>
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
					<td colspan="<?php echo $col;?>" ></td>
					<td align="left">
						<strong>
							<?php echo Text::_('TAX_AMOUNT'); ?>
						</strong>
							<?php echo $tax_arr['percent']; ?>
					</td>
					<td>
						<span>
							<?php echo $this->comtjlmsHelper->getFromattedPrice(number_format($this->orderinfo->order_tax,2),$order_currency); ?>
						</span>
					</td>
				</tr>
		<?php 	} 	?>

				<tr>
					<td colspan="<?php echo $col;?>" > </td>
					<td align="left">
						<strong>
							<?php echo Text::_('COM_TJLMS_ORDER_TOTAL'); ?>
						</strong>
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
