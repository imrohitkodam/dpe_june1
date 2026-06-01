<?php
/**
 * @package     MailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Mail\Mail;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('bootstrap.tooltip');

$doc = Factory::getDocument();
HTMLHelper::_('stylesheet', Uri::root() . 'components/com_jmailalerts/assets/css/jmailalerts.css');
$doc->addStyleDeclaration('.ui-accordion-header {margin: 1px 0px !important}');

$js = '
	function divhide(thischk)
	{
		if(thischk.checked){
			document.getElementById(thischk.value).style.display="block";
		}
		else{
			document.getElementById(thischk.value).style.display="none";
		}
	}

	function divhide1(thischk)
	{
		if(thischk.value==0){
			document.getElementById("ac").style.display="none";
		}
		else{
			document.getElementById("ac").style.display="block";
		}
	}
';

$doc->addScriptDeclaration($js);
?>

<?php
// Added in 2.4.3
// Newly added for JS toolbar inclusion
if (Folder::exists(JPATH_SITE . '/components/com_community') && $this->params->get('jstoolbar') == '1')
{
	$jsFile = JPATH_ROOT . '/components/com_community/libraries/toolbar.php';

	if (File::exists($jsFile))
	{
		require_once $jsFile;
		$toolbar = CFactory::getToolbar();
		$tool    = CToolbarLibrary::getInstance(); ?>
		<div id="community-wrap">
			<?php
				echo $tool->getHTML(); ?>
		</div>
		<?php
	}
}

if ($this->params['enable_login'] == 1)
{
	$class = "col-sm-12 col-md-8 col-lg-8";
}
else
{
	$class = "col-sm-12 col-md-12 col-lg-12";
}
// Eoc for JS toolbar inclusion
?>
<style>
  .form-horizontal .form-actions {
    padding-left: 0px;
}
</style>
<!--div for registration of guest user.-->
<div class="<?php echo JMAILALERTS_WRAPPER_CLASS; ?> well jma_plugin_background" id="jmailalerts-emails">
	<div class="col100" id="e-mail_alert">
		<?php if ($this->params->get('show_page_heading')): ?>
			<div class="page-header">
				<h1>
					<?php echo $this->escape($this->params->get('page_heading')); ?>
				</h1>
			</div>
		<?php endif; ?>

		<form action="" class="form-validate form-horizontal" method="POST" id="adminform" name="adminform" ENCTYPE="multipart/form-data">
			<?php
			// If enable guest user registration then show name and email field.
			if (!$this->user->id && $this->params->get('guest_subcription') == 1)
			{
				?>
				<div class="row" ><!--1-->
					<div class="<?php echo $class; ?>"><!--2-->
						<!-- <div class="well">
							<div class="page-header">
								<h2><?php echo Text::_('COM_JMAILALERT_USER_REG'); ?> </h2>
								<?php echo Text::_('COM_JMAILALERT_UN_REGISTER'); ?>
							</div>
							<div class="control-group">
								<label class="control-label"  for="user_name">
									<?php echo Text::_('COM_JMAILALERT_USER_NAME'); ?>
								</label>
								<div class="controls">
									<input class="inputbox required validate-name input input-medium"
										type="text" name="user_name" id="user_name"
										size="30" maxlength="50" value="" />
								</div>
							</div>
							<div class="control-group">
								<label class="control-label"  for="user_email">
									<?php echo Text::_('COM_JMAILALERT_USER_EMAIL'); ?>
								</label>
								<div class="controls">
									<input class="inputbox required validate-email input input-medium"
										type="text" name="user_email" id="user_email"
										size="30" maxlength="100" value="" />
								</div>
							</div>
						</div> -->
					</div>

					<?php
					if ($this->params['enable_login'] == 1)
					{
						?>
						<div class="col-sm-12 col-md-12 col-lg-12">
							<div class="well input">
								<div class="page-header">
									<h2><?php echo Text::_('COM_JMAILALERT_LOGIN'); ?> </h2>
									<?php echo Text::_('COM_JMAILALERT_REGISTER'); ?>
								</div>

								<a href='<?php
									$msg = Text::_('LOGIN');
									// Get current url.
									$current = Uri::getInstance()->toString();
									$url     = base64_encode($current);
									echo Route::_('index.php?option=com_users&view=login&return=' . $url, false); ?>' >

									<div style="margin-left:auto;margin-right:auto;" class="control-group">
										<input id="LOGIN" class="btn btn-large btn-success validate" type="button" value="<?php echo Text::_('COM_JMAILALERT_SIGN_IN'); ?>" style="background: #22b8f0;">
									</div>
								</a>
							</div>
						</div>
						<?php
					} ?>
				</div>
				<?php
			}
			elseif (!$this->user->id && $this->params->get('guest_subcription') == 0)
			{
				?>
				<div class="alert alert-block">
					<?php echo Text::_('YOU_NEED_TO_BE_LOGGED_IN'); ?>
				</div>
			</div><!--Techjoomla bootstrap ends if not logged in-->
		</div><!--Mail_alert ends if not logged in-->
				<?php

				return false;
			}

			// Take Component parameter as no config file present now.
			if ($this->params->get('intro_msg') != '' && ($this->user->id))
			{
				?>
				<div class="jma_email_intro">
					<h4>
						<?php echo Text::_($this->params->get('intro_msg')); ?>
					</h4>
				</div>
				<?php
			}

			$disp_none = " ";

			if (trim($this->cntalert) == 0)
			{
				$disp_none = "display:none";
			}

			if ( ($this->user->id))
			{
			?>

			<table class="jma_table">
				<tr>
					<td>

					</td>
				</tr>
				<tr>
					<td>
						<?php $maplist[] = HTMLHelper::_('select.option', '0', Text::_('N0_FREQUENCY'), 'value', 'text'); ?>
						<div id="ac" style="<?php echo $disp_none; ?>">
							<?php
							if (trim($this->cntalert) != 0)
							{
								echo $this->loadTemplate('alerts_bs3');
							}
							?>
						</div>
					</td>
				</tr>
			</table>
<?php } ?>
			<div id="manual_div">
				<?php 
				if (trim($this->cntalert) != 0 && ($this->user->id))
				{
					?>
					<div class="form-actions">
					<button class="btn btn-primary validate" type="submit" ><?php echo Text::_('BUTTON_SAVE'); ?></button>
					</div>
					<?php
				}
				?>

				<input type="hidden" name="option" value="com_jmailalerts">
				<input type="hidden" id="task" name="task" value="savePref">
			</div>
		</form>
	</div>
</div>
