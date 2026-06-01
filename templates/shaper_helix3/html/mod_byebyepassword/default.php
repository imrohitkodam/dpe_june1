<?php
/**
* @package Joomla Module for byebye password
* @copyright Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
* @license GNU General Public License version 2 or later; see LICENSE
* @author Rimjhim
*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

$input   = Factory::getApplication()->input;
$success = $input->get('success');

JText::script('PLG_BBPASS_LOGIN_LINK_SENT');
JText::script('PLG_BBPASS_LOGIN_ACCOUNT_NOT_EXIST');
JText::script('MOD_BBPASS_LOGIN_POPUP_CLOSING_MSG1');
JText::script('MOD_BBPASS_LOGIN_POPUP_CLOSING_MSG2');
$document = Factory::getDocument();
$document->addScript(Uri::root() . 'media/vendor/jquery/js/jquery.min.js');

$session = Factory::getSession();
$invalidMsg = $session->get('bbpassInvalidEmail');
$session->clear('bbpassInvalidEmail');

if($invalidMsg)
{
	$message = 0;
}else
{
	$message = 1;
}

?>

<script>
<?php if ($success): ?>

	let timmer = 5;

	setInterval(function(){
		timmer = (timmer - 1);

		var msg = (<?php echo $message;?>)?Joomla.Text._('PLG_BBPASS_LOGIN_LINK_SENT'):Joomla.Text._('PLG_BBPASS_LOGIN_ACCOUNT_NOT_EXIST')

		jQuery("#countermsg").removeClass("d-none");
		jQuery("#countermsg").text(msg +Joomla.Text._('MOD_BBPASS_LOGIN_POPUP_CLOSING_MSG1')+timmer+Joomla.Text._('MOD_BBPASS_LOGIN_POPUP_CLOSING_MSG2'));

		if (timmer == 0)
		{
			window.parent.SqueezeBox.close();
		}

	}, 1000);
<?php endif; ?>
</script>
<div class="">
<div id="countermsg" class="alert alert-info d-none">
</div>
  <div class="col-md-6 col-md-offset-3">
		<div class="form-group ucm-form-styling">
  <form id="login-register" method="post" action="index.php?option=plg_bbpass&action=loginregister&currentUrl=<?php echo base64_encode(Juri::getInstance()->toString());?>">
					<p><input type="email" placeholder="your@email.com" name="email" autofocus class="input-large"/></p>
					<p><?php echo Text::_("MOD_BBPASS_LOGIN_LINK_HELP");?></p>
				<button type="submit" class="btn btn-blue btn-primary"><?php echo Text::_("MOD_BBPASS_LOGIN_REGISTER");?></button>
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		</div>
     </div>
</div>
<script type="text/javascript">
	
	setTimeout(function(){
	jQuery('.addtoany_container').css('display','none');

	},1000)
</script>
