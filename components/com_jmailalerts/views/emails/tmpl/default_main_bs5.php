<?php
/**
 * @package     JMailAlerts
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

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('bootstrap.tooltip');

$doc = Factory::getDocument();

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
if ($this->params['enable_login'] == 1)
{
	$class = "col-xs-8 col-sm-8 col-md-8 col-lg-8";
}
else
{
	$class = "col-xs-12 col-sm-12 col-md-12 col-lg-12";
}
?>

<div class="<?php echo JMAILALERTS_WRAPPER_CLASS; ?>" id="jma-emails">
	<?php if ($this->params->get('show_page_heading')): ?>
		<div class="page-header">
			<h1>
				<?php echo $this->escape($this->params->get('page_heading')); ?>
			</h1>
		</div>
	<?php endif; ?>

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
	?>

	<?php if (!$this->user->id && $this->params->get('guest_subcription') == 0): ?>
		<div class="row">
			<div class="alert alert-block">
				<?php echo Text::_('YOU_NEED_TO_BE_LOGGED_IN'); ?>
			</div>
		</div>
	</div>
		<?php
		return false;
	endif; ?>

	<form action="" class="form-validate form-horizontal" method="POST" id="adminform" name="adminform" ENCTYPE="multipart/form-data">
		<div class="card">
			<div class="card-body">
				<?php // If guest user registration enabled, then show name and email field ?>
				<?php if (!$this->user->id && $this->params->get('guest_subcription') == 1): ?>
					<div class="mb-3 row">
						<div class="<?php echo $class; ?>">
							<div class="card">
								<div class="card-header">
									<h2><?php echo Text::_('COM_JMAILALERT_USER_REG'); ?> </h2>
									<?php echo Text::_('COM_JMAILALERT_UN_REGISTER'); ?>
								</div>

								<div class="card-body">
									<div class="mb-3 row">
										<div class="col-sm-4">
											<label class="" for="user_name">
												<?php echo Text::_('COM_JMAILALERT_USER_NAME'); ?>
											</label>
										</div>
										<div class="col-sm-8">
											<input class="required validate-name form-control"
												type="text" name="user_name" id="user_name"
												size="30" maxlength="50" value="" />
										</div>
									</div>

									<div class="mb-3 row">
										<div class="col-sm-4">
											<label class=""  for="user_email">
												<?php echo Text::_('COM_JMAILALERT_USER_EMAIL'); ?>
											</label>
										</div>
										<div class="col-sm-8">
											<input class="required validate-email form-control"
												type="text" name="user_email" id="user_email"
												size="30" maxlength="100" value="" />
										</div>
									</div>
								</div>
							</div>
						</div>

						<?php if ($this->params['enable_login'] == 1): ?>
							<div class="col-xs-4 col-sm-12 col-md-12 col-lg-12">
								<div class="card">
									<div class="card-header">
										<h2><?php echo Text::_('COM_JMAILALERT_LOGIN'); ?></h2>
										<?php echo Text::_('COM_JMAILALERT_REGISTER'); ?>
									</div>

									<div class="card-body text-center">
										<a href='<?php
											$msg = Text::_('LOGIN');
											// Get current url
											$current = Uri::getInstance()->toString();
											$url     = base64_encode($current);
											echo Route::_('index.php?option=com_users&view=login&return=' . $url, false); ?>'>

											<input id="jma_login" class="btn btn-success validate" type="button" value="<?php echo Text::_('COM_JMAILALERT_SIGN_IN'); ?>">
										</a>
									</div>
								</div>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ($this->params->get('intro_msg') != '' && ($this->user->id)): ?>
					<div class="mb-3 row">
						<div class="m-1">
							<h4><?php echo Text::_($this->params->get('intro_msg')); ?></h4>
						</div>
					</div>
				<?php endif; ?>

				<?php
				$displayNone = "";

				if (trim($this->cntalert) == 0)
				{
					$displayNone = "display:none";
				}

				$maplist[] = HTMLHelper::_('select.option', '0', Text::_('N0_FREQUENCY'), 'value', 'text');
				?>

				<div class="mb-3 row" id="ac" style="<?php echo $displayNone; ?>">
					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
						<?php
						if (trim($this->cntalert) != 0):
							echo $this->loadTemplate('alerts_bs5');
						endif;
						?>
					</div>
				</div>

				<div class="mb-3 row" id="manual_div">
					<?php if (trim($this->cntalert) != 0 && ($this->user->id)): ?>
						<div class="form-actions text-center">
							<button class="btn btn-primary validate" type="submit">
								<?php echo Text::_('BUTTON_SAVE'); ?>
							</button>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<input type="hidden" name="option" value="com_jmailalerts">
		<input type="hidden" id="task" name="task" value="savePref">
	</form>
</div>
