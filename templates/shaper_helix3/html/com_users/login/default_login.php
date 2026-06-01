<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;

HTMLHelper::_('behavior.keepalive');
$loginwithoutpassword = json_decode(ComponentHelper::getParams('com_dpe')->get('loginwithoutpassword'));
HTMLHelper::script('media/com_dpe/js/userloginwithotp.js');

Text::script('COM_DPE_USERNAME_REQUIRED');
Text::script('COM_DPE_PASSWORD_REQUIRED');


$table = Table::getInstance('content');
// Load the article using the article ID
$table->load($loginwithoutpassword->articleId);

$artcleLink           = '';

if ($loginwithoutpassword->articleId && $loginwithoutpassword->menuId)
{
	$artcleLink = Route::_('index.php?option=com_content&view=article&id='.$loginwithoutpassword->articleId.'&itemid='.$loginwithoutpassword->menuId.'&tmpl=component');
}


?>
<script>
	function openLoinWithoutPasswordPopups(url) {
		SqueezeBox.open(url ,{handler: 'iframe', size: {x: window.innerWidth-350, y: window.innerHeight-280},classWindow: 'squeeze-popup login-without-password-popup', onClose: function(content){
			window.parent.jQuery('.shadow').removeClass('squeeze-popup login-without-password-popup');
			if (content.refreshparent)
			{
				window.location.reload(true);
			}
		}});
	}
</script>
<div class="row justify-content-center">
	<div class="col-lg-5">
		<div class="login<?php echo $this->pageclass_sfx?>">
			<?php if ($this->params->get('show_page_heading')) : ?>
				<h1>
					<?php echo $this->escape($this->params->get('page_heading')); ?>
				</h1>
			<?php endif; ?>

			<?php if (($this->params->get('logindescription_show') == 1 && str_replace(' ', '', !empty($this->params->get('login_description')) ? $this->params->get('login_description') : '') != '') || $this->params->get('login_image') != '') : ?>
			<div class="login-description">
			<?php endif; ?>

			<?php if ($this->params->get('logindescription_show') == 1) : ?>
				<?php echo $this->params->get('login_description'); ?>
			<?php endif; ?>

			<?php if (($this->params->get('login_image') != '')) :?>
				<img src="<?php echo $this->escape($this->params->get('login_image')); ?>" class="login-image" alt="<?php echo Text::_('COM_USERS_LOGIN_IMAGE_ALT')?>"/>
			<?php endif; ?>

			<?php if (($this->params->get('logindescription_show') == 1 && str_replace(' ', '', !empty($this->params->get('login_description')) ? $this->params->get('login_description') : '') != '') || $this->params->get('login_image') != '') : ?>
		</div>
	<?php endif; ?>

	<form action="<?php echo Route::_('index.php?option=com_users&task=user.login'); ?>" method="post" class="form-validate">

		<?php /* Set placeholder for username, password and secretekey */
		$this->form->setFieldAttribute( 'username', 'hint', Text::_('COM_USERS_LOGIN_USERNAME_LABEL') );
		$this->form->setFieldAttribute( 'password', 'hint', Text::_('JGLOBAL_PASSWORD') );
		$this->form->setFieldAttribute( 'secretkey', 'hint', Text::_('JGLOBAL_SECRETKEY') );

		?>

		<?php foreach ($this->form->getFieldset('credentials') as $field) : ?>
			<?php if (!$field->hidden) : ?>
				<div class="mb-3">
					<?php echo $field->input; ?>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>

		<?php if ($this->tfa): ?>
			<div class="mb-3">
				<?php echo $this->form->getField('secretkey')->input; ?>
			</div>
		<?php endif; ?>

		<div class="shadow-sm p-3 mb-5 bg-white rounded" role="group" aria-label="">

			<label for="subscribe">
				<input type="checkbox" class="form-check-input"id="loginwithlink" name="loginwithlink" value="loginwithlink">
				<?php echo Text::_('COM_DPE_LOGIN_WITHLOGIN_URL');?>
			</label> &nbsp &nbsp
			<label for="terms">
				<input type="checkbox" class="form-check-input"id="loginwithotp" name="loginwithotp" value="loginwithotp">
				<?php echo Text::_('COM_DPE_LOGIN_WITHLOGIN_OTP');?>
			</label>
		</div>
		<?php if (PluginHelper::isEnabled('system', 'remember')) : ?>
			<div class="mb-3 form-check" style="margin-top: -41px;margin-left: 15px">
				<input id="remember" type="checkbox" name="remember" class="form-check-input" value="yes">
				<label class="form-check-label" for="remember"><?php echo Text::_('COM_USERS_LOGIN_REMEMBER_ME') ?></label>
			</div>
		<?php endif; ?>


		<div class="mb-3"> 
			<button type="submit" id ="loginbtn" class="btn btn-primary btn-block">
				<?php echo Text::_('JLOGIN'); ?>
			</button>
			<p  class="btn btn-primary btn-block hide" id="getotp" onclick="getOtp();" style="margin-top: 11px;">
				<?php echo Text::_('Get OTP'); ?>
			</p>
		</div>

		<?php $return = $this->form->getValue('return', '', $this->params->get('login_redirect_url', $this->params->get('login_redirect_menuitem'))); ?>
		<input type="hidden" name="return" id='return'value="<?php echo !is_null($return) ? base64_encode($return) : ''; ?>" />
		<input type="hidden" name="loginPage" id ='loginPage' value="fromloginpage">
		<input type="hidden" name="otpsuccess" id ='otpsuccess' value="">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>

<div class="form-links">
	<ul>
		<li>
			<a href="<?php echo Route::_('index.php?option=com_users&view=reset'); ?>">
				<?php echo Text::_('COM_USERS_LOGIN_RESET'); ?></a>
			</li>


			<li>
				<a href="<?php echo Route::_('index.php?option=com_users&view=remind'); ?>">
					<?php echo Text::_('COM_USERS_LOGIN_REMIND'); ?></a>
				</li>
				<?php
				$usersConfig = ComponentHelper::getParams('com_users');
				if ($usersConfig->get('allowUserRegistration')) : ?>
					<li>
						<a href="<?php echo Route::_('index.php?option=com_users&view=registration'); ?>">
							<?php echo Text::_('COM_USERS_LOGIN_REGISTER'); ?></a>
						</li>
					<?php endif; ?>
					<?php 
					if ($table->state == 1){
						?>
						<li>

							<a href="javascript:void(0);" onclick="openLoinWithoutPasswordPopups('<?php echo $artcleLink; ?>')" id="assign-modal-link" title="<?php echo JText::_('MOD_BBPASS_LOGIN_WITHOUT_PASSWORD_DESC'); ?>">
								<?php echo JText::_('MOD_BBPASS_LOGIN_WITHOUT_PASSWORD'); ?>
							</a>
						</li>

						<?php
					}
					?>
				</ul>
			</div>
		</div>
	</div>