<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('jquery.framework');

HTMLHelper::stylesheet('media/techjoomla_strapper/bs3/css/bootstrap.css');
HTMLHelper::_('stylesheet', 'components/com_tjlms/bootstrap/css/bootstrap.min.css');
$baseurl = Route::_(Uri::root() . 'index.php');
$rootURL = Uri::root();

if (!empty($this->userbill))
{
	foreach ($this->userbill as &$value)
	{
		$value = $this->escape($value);
	}
}
?>

<script type="text/javascript">
	jQuery(document).ready(function() {
		var DBuserbill="<?php echo (isset($this->userbill->state_code))?$this->userbill->state_code:''; ?>";
		tjlms.billing.init(DBuserbill,"<?php echo Text::_('ADS_BILLIN_SELECT_STATE');?>","<?php echo $rootURL ?>");
		jQuery('#country_mobile_code').val("<?php echo $this->defaultCountryMobileCode; ?>");
	});
</script>

<!-- Start OF billing_info_tab-->
<div id="billing-info" class="tjlms-checkout-steps">
	<form action="<?php echo $this->tjlmsFrontendHelper->tjlmsRoute('index.php?option=com_tjlms&view=buy&course_id=' . $this->course_id); ?>" name="billing_info_form" action="" id="billing_info_form" class="form-validate">

	<!--Start User Details Tab-->
	<?php
	if (!$this->user->id)
	{
		return false;
	}
	?>
	<!--End User Details Tab-->
		<div class="checkout-content pb-30 mt-25 px-25 checkout-first-step-billing-info container-fluid" id="billing-info-tab">
			<div class="row-fluid form-horizontal tjlms-filters">
				<div class="section-billing">
					<div class="form-group" id="">
						<span class="help-inline" id="billmail_msg"></span>
					</div>
					<div class="form-group">
						<label  for="req" class="redColor col-xs-12">
							<i><?php echo Text::_('COM_TJLMS_BILLIN_REQ')?></i>
						</label>
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="col-xs-12 col-sm-6 section-billing section-line">
					<div class="form-group">
						<label  for="fnam" class="col-xs-12">
							<?php echo Text::_('COM_TJLMS_BILLIN_FNAM') . Text::_('COM_TJLMS_STAR');?>
						</label>
						<div class="col-xs-12">
							<!-- This pattern will take only character and space -->
							<input id="fnam" class=" input-style bill inputbox required validate-name" type="text" value="<?php echo (isset($this->userbill->firstname))?$this->userbill->firstname:''; ?>" maxlength="250" size="32" name="bill[fnam]" title="<?php echo Text::_('COM_TJLMS_BILLIN_FNAM_DESC')?>" pattern="[a-zA-Z\s\.]+">
						</div>
					</div>

					<div class="form-group">
						<label for="lnam" class="col-xs-12">
							<?php echo Text::_('COM_TJLMS_BILLIN_LNAM') . Text::_('COM_TJLMS_STAR');?>
						</label>
						<div class="col-xs-12">
							<!-- This pattern will take only character and space -->
							<input id="lnam" class="input-style  bill inputbox required validate-name" type="text" value="<?php echo (isset($this->userbill->lastname))?$this->userbill->lastname:''; ?>" maxlength="250" size="32" name="bill[lnam]" title="<?php echo Text::_('COM_TJLMS_BILLIN_LNAM_DESC')?>" pattern="[a-zA-Z\s\.]+">
						</div>
					</div>
					<div class="form-group">
						<label for="email1" class="col-xs-12">
							<?php echo Text::_('COM_TJLMS_BILLIN_EMAIL') . Text::_('COM_TJLMS_STAR');?>
						</label>
							<!-- This pattern will take character, number and special symbols like . and @ only -->
						<div class="col-xs-12"><input id="email1" class="input-style bill inputbox required validate-email"  type="email" value="<?php echo (isset($this->userbill->user_email))?$this->userbill->user_email:'' ; ?>" maxlength="250" size="32" name="bill[email1]"  title="<?php echo Text::_('COM_TJLMS_BILLIN_EMAIL_DESC')?>" pattern="([\w\.-]*[a-zA-Z0-9_]@[\w\.-]*[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z])*">
						</div>
					</div>
					<?php
					if($this->enable_bill_vat=="1")
					{
						?>
						<div class="form-group">
							<label for="vat_num"  class="col-xs-12">
								<?php echo Text::_('COM_TJLMS_BILLIN_VAT_NUM');?>
							</label>
							<div class="col-xs-12">
							<input id="vat_num" class="input-style bill inputbox validate-integer" type="text" value="<?php echo (isset($this->userbill->vat_number))?$this->userbill->vat_number:''; ?>" size="32" name="bill[vat_num]" title="<?php echo Text::_('COM_TJLMS_BILLIN_VAT_NUM_DESC')?>">
							</div>
						</div>
						<?php
					} ?>
					<div class="form-group">
						<label for="phon" class="col-xs-12">
							<?php echo Text::_('COM_TJLMS_BILLIN_PHON') . Text::_('COM_TJLMS_STAR');?>
						</label>
						<div class="col-xs-12 d-inline-flex">

						<?php
							$mobileCountryCode = $this->country;
							$default_code      = ((isset($this->userbill->country_mobile_code)) ? $this->userbill->country_mobile_code : '');

							$options = array();
							$options[] = HTMLHelper::_('select.option', "", Text::_('COM_TJLMS_BILLIN_SELECT_COUNTRY'));

							foreach ($mobileCountryCode as $key => $value)
							{
								$countryMobileCode = $value['country'] . ' (+' . $value['country_dial_code'] . ')';
								$options[] = HTMLHelper::_('select.option', $value['id'], $countryMobileCode);
							}

							echo $this->dropdown = HTMLHelper::_('select.genericlist',$options,'bill[country_mobile_code]','class="input-style lms_select bill col-xs-4 mr-5"  required="required" aria-invalid="false" size="1" ', 'value', 'text', $default_code, 'country_mobile_code');
							?>

							<!-- This pattern will take only numbers -->
							<input id="phon" class="input-style bill inputbox required validate-numeric col-xs-8 ml-5" type="text"  maxlength="50" value="<?php echo (isset($this->userbill->phone))?$this->userbill->phone:''; ?>" size="32" name="bill[phon]" title="<?php echo Text::_('COM_TJLMS_BILLIN_PHON_DESC')?>">
						</div>
					</div>
				</div>

				<div class="col-xs-12 col-sm-6 section-billing">
					<div class="form-group">
						<label for="addr"  class="col-xs-12">
							<?php echo Text::_('COM_TJLMS_BILLIN_ADDR') . Text::_('COM_TJLMS_STAR');?>
						</label>
						<div class="col-xs-12">
						<textarea id="addr" class="input-style-text bill inputbox required" name="bill[addr]"  maxlength="250" rows="3" title="<?php echo 		Text::_('COM_TJLMS_BILLIN_ADDR_DESC')?>" ><?php echo (isset($this->userbill->address))?$this->userbill->address:''; ?></textarea>
						<p class="help-block">
							<span id="characterLeft" style="width: 24px;border: none;color: grey;">
						</span></p>
						</div>
					</div>
					<div class="row">
						<div class="col-xs-12 col-sm-6">
							<div class="form-group">
								<label for="country" class="col-xs-12">
									<?php echo Text::_('COM_TJLMS_BILLIN_COUNTRY').Text::_('COM_TJLMS_STAR');?>
								</label>
								<div class="col-xs-12">
								<?php
										$country = $this->country;
										$default_country = '';
										$default_country = ((isset($this->userbill->country_code)) ? $this->userbill->country_code : '');

										$options = array();
										$options[] = HTMLHelper::_('select.option', "", Text::_('COM_TJLMS_BILLIN_SELECT_COUNTRY'));

										foreach ($country as $key => $value)
										{
											$options[] = HTMLHelper::_('select.option', $value['id'], $value['country']);
										}

										$tprice = 1;
										echo $this->dropdown = HTMLHelper::_('select.genericlist',$options,'bill[country]','class="input-style lms_select bill col-sm-12"  required="required" aria-invalid="false" size="1" onchange=\'tjlms.billing.generateState(id,"",' . $tprice . ')\' ', 'value', 'text', $default_country, 'country');
								?>
								</div>
							</div>
							<div class="form-group">
								<label for="city" class="col-xs-12">
									<?php echo Text::_('COM_TJLMS_BILLIN_CITY');?>
								</label>
								<div class="col-xs-12">
									<!-- This pattern will take only characters and space -->
									<input id="city" class="input-style bill inputbox col-sm-12 " type="text" value="<?php echo (isset($this->userbill->city))?$this->userbill->city:''; ?>" maxlength="250" size="32" name="bill[city]" title="<?php echo Text::_('COM_TJLMS_BILLIN_CITY_DESC')?>" pattern="[a-zA-Z\s\.]+">
								</div>
							</div>
						</div>

						<div class="col-xs-12 col-sm-6">
							<div class="form-group">
								<label for="state" id="state_lbl" class="col-xs-12"><?php echo Text::_('COM_TJLMS_BILLIN_STATE');?><span id="state_star"></span></label>
								<div class="col-xs-12">
									<select name="bill[state]" id="state" class="input-style lms_select bill col-sm-12">
										<option selected="selected" value="" ><?php echo Text::_('COM_TJLMS_BILLIN_SELECT_STATE');?></option>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label for="zip"  class="col-xs-12"><?php echo Text::_('COM_TJLMS_BILLIN_ZIP') . Text::_('COM_TJLMS_STAR');?></label>
								<div class="col-xs-12">
									<!-- This pattern will take only numbers -->
									<input id="zip"  class="input-style bill inputbox required col-sm-12" type="text" value="<?php echo (isset($this->userbill->zipcode))?$this->userbill->zipcode:''; ?>" onblur="" maxlength="20" size="32" name="bill[zip]" title="<?php echo Text::_('COM_TJLMS_BILLIN_ZIP_DESC')?>" pattern="[a-zA-Z0-9\s\.]+">
								</div>
							</div>
						</div>
					</div>

					<?php

					if ($this->tnc && $this->doesArticleExists)
					{
						?>
						<div class="form-group term-condition">
							<label for="state" class="col-sm-3 col-xs-12">
								<?php
								$link = Route::_(Uri::root() . "index.php?option=com_content&view=article&id=" . $this->article . "&tmpl=component");
								?>
									<?php
									$modalConfig = array('width' => '800px', 'height' => '600px', 'modalWidth' => 80, 'bodyHeight' => 70);
									$modalConfig['url'] = $link;
									$modalConfig['title'] = Text::_('COM_TJLMS_TERMS_CONDITION');
									echo HTMLHelper::_('bootstrap.renderModal', 'termsandconditions', $modalConfig);
									?>
									<a data-bs-target="#termsandconditions" data-bs-toggle="modal" onclick="jQuery('#termsandconditions').removeClass('fade');" class="af-relative af-d-block ">
										<?php echo Text::_('COM_TJLMS_TERMS_CONDITION');?>
									</a>
							</label>
							<div class="col-xs-12 col-sm-9 billingcheckbox">
								<input class="inputbox " type="checkbox" name="accpt_terms" id="accpt_terms" size="30" <?php echo (!empty($this->userbill->ptnc)?"checked":""); ?> />&nbsp;&nbsp;<?php echo Text::_('COM_TJLMS_YES'); ?>

							</div>
						</div>
						<?php
					}
					?>
				</div>
			</div>	<!-- END OF row-fluid-->
		</div>
	</form><!-- END OF Form-->
</div>
<!-- END OF billing_info_tab-->
